# Repeat: contexto para Claude

Tarjetas de sellos digitales (fidelización) para pymes chilenas. Producto de **ZERO Solutions** (zerosolutions.cl,
repo `fwaldbaum/zero-backup` y carpeta `zero/` en `fwaldbaum/flowdesk`). El dueño escribe en español: **responde en
español**. Código, comentarios y commits en inglés; **toda la UI en español**. Sin emojis en la UI (iconos Lucide en
`app/icons.php`).

## Stack y despliegue
- **PHP 8.1+ y MySQL**, sin framework ni build: el hosting es cPanel compartido (LiteSpeed, sin Node).
- `public_html/` va a la carpeta del subdominio `repeat.zerosolutions.cl`; `app/` está bloqueado por `.htaccess`.
- `app/config.php` (fuera de git) tiene la BD, `app_secret`, `zero_sso_secret` y las claves de las billeteras.
  Nunca subas secretos al repositorio.
- Guía de instalación en `README.md`.

## Arquitectura
- `index.php`: enrutador (`switch` + `preg_match`). Todo POST exige CSRF (`csrf_ok`), salvo `/wallet/*` (Apple).
- `app/bootstrap.php`: helpers (`u()` respeta `base_path`, `url()` absoluta, `render()`, `partial()`, `icon()`,
  `asset()` con cache-busting), BD con migración idempotente por versión (tablas `rp_*`; agrega cambios al final
  como nueva versión con `add_column()`, que funciona en MySQL y MariaDB; nunca reescribas las anteriores), sesión
  `rp_sid`, flash y rate limit en MySQL.
- `app/auth.php`: entrada **solo con cuenta ZERO**. ZERO firma un pase HMAC-SHA256 (`aud=repeat`, 2 min, nonce de
  un solo uso en `rp_sso_nonces`) y redirige a `/auth/zero?ticket=`.
- `app/cards.php`: negocio, clientes (teléfono normalizado a +569…), sellos (`add_stamp` convierte cada tarjeta
  llena en un premio disponible), canje, deshacer (10 min), estadísticas. **Toda consulta filtra por el negocio**.
- `app/pro.php`: **Repeat Pro** ($6.990/mes, antes $14.990; también incluido en ZERO Plus). El plan se compra en
  ZERO (`repeat_pro` en `zero/public_html/app/plans.php`) y llega por `POST /zero/plan` (firma `X-Zero-Signature`
  sobre el cuerpo) y por el claim `plan` del pase de inicio de sesión; vive en `rp_users.plan`/`plan_expires_at`
  y `apply_zero_plan` solo avanza. `is_pro($business)` se revisa al usar cada función: sin logo de Repeat (gratis lo
  muestra en tarjetas, página para unirse, afiche y billeteras), ícono de sello (`STAMP_ICONS`), regalo de
  cumpleaños (`maybe_birthday_gift`, una vez por año en la semana del cumpleaños), sellos dobles por horario
  (`double_active`), trae a un amigo (`?ref=` + `reward_referral`) y exportar CSV. Gratis: clientes por recuperar
  (pestaña con WhatsApp) y cumpleaños del mes.
- `app/wallet_google.php` / `app/wallet_apple.php`: opcionales según `config.php`. `wallet_sync()` corre después
  de enviar la respuesta (`register_shutdown_function` + `litespeed_finish_request`).
- Vistas: `views/public/` (portada, unirse, tarjeta del cliente, error), `views/panel/` (inicio, escanear, clientes,
  cliente, tarjeta, compartir, afiche), `views/partials/` (`scene` = cielo y montañas, `loyalty_card`, etc.).
- JS: `assets/js/repeat.js` (revelado, QR con qrcode-generator, vista previa en vivo, tooltips del gráfico, sondeo
  de la tarjeta del cliente cada 4 s) y `assets/js/scanner.js` (cámara con BarcodeDetector o jsQR, acciones).

## Diseño
Noche sobre montañas moradas: fondo `#07040f`, superficies de vidrio (`.glass`), degradado de marca
`#2563eb → #5b3fe0 → #8b5cf6`, tipografías Sora (títulos) e Inter. Animaciones suaves y con
`prefers-reduced-motion`. La tarjeta del cliente usa los colores del negocio (`card_bg`, `card_accent`, `card_fg`).

## Verificar cambios
`php -l` en los archivos tocados y una prueba real en navegador (Playwright con Chromium en `/opt/pw-browsers`),
idealmente con ZERO local para el inicio de sesión. Commits en inglés y descriptivos.
