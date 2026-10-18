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
  como nueva versión, nunca reescribas las anteriores), sesión `rp_sid`, flash y rate limit en MySQL.
- `app/auth.php`: entrada **solo con cuenta ZERO**. ZERO firma un pase HMAC-SHA256 (`aud=repeat`, 2 min, nonce de
  un solo uso en `rp_sso_nonces`) y redirige a `/auth/zero?ticket=`.
- `app/cards.php`: negocio, clientes (teléfono normalizado a +569…), sellos (`add_stamp` convierte cada tarjeta
  llena en un premio disponible), canje, deshacer (10 min), estadísticas. **Toda consulta filtra por el negocio**.
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
