# Repeat by ZERO

Tarjetas de sellos digitales para pymes chilenas: barberías, cafés, peluquerías y todo negocio al que la gente
vuelve. El cliente escanea un QR, deja su nombre y teléfono, y tiene su tarjeta en el celular (en el navegador, y en
Apple Wallet o Google Wallet cuando estén conectados). El negocio suma sellos con la cámara de su celular y, al
completar la tarjeta, el cliente gana su premio.

Es una aplicación de **ZERO Solutions**: los negocios entran con su **cuenta ZERO** (zerosolutions.cl). Está hecha en
**PHP 8.1+ y MySQL**, sin dependencias ni build, para el mismo hosting cPanel que ZERO.

```
public_html/                 <- esto va a la carpeta del subdominio en cPanel
├── index.php                enrutador: portada, panel, tarjetas y servicio de Apple Wallet
├── .htaccess                URLs limpias, HTTPS y bloqueo de app/
├── assets/                  CSS, JS, logo (repeat-mark.svg) y logos subidos por los negocios (uploads/)
└── app/                     código privado (bloqueado por .htaccess)
    ├── config.example.php   plantilla -> copiar como config.php
    ├── bootstrap.php        base de datos (crea las tablas rp_* sola), sesión, CSRF, vistas
    ├── auth.php             inicio de sesión con cuentas ZERO (pase firmado de un solo uso)
    ├── cards.php            negocios, clientes, sellos, premios y estadísticas
    ├── wallet_google.php    Google Wallet: enlace "Agregar" (JWT) y actualización de la tarjeta
    ├── wallet_apple.php     Apple Wallet: .pkpass firmado, servicio web y avisos push
    └── views/               páginas (public/ para clientes, panel/ para el negocio)
```

## Páginas

- `/` portada · `/entrar` lleva a zerosolutions.cl/abrir/repeat
- `/panel` inicio con estadísticas · `/panel/escanear` sumar sellos (cámara o búsqueda) · `/panel/clientes`
- `/panel/tarjeta` diseño y premio · `/panel/compartir` QR, enlace y afiche para imprimir (`/panel/afiche`)
- `/c/<negocio>` el cliente se une · `/t/<código>` su tarjeta (se actualiza sola cuando le suman un sello)

## Instalar en cPanel (repeat.zerosolutions.cl)

1. **Subdominio**: cPanel → *Dominios* → *Crear un dominio nuevo* → `repeat.zerosolutions.cl`, con la raíz del
   documento en `repeat` (queda `/home3/<cuenta>/repeat`). Activa SSL con *SSL/TLS Status → Run AutoSSL*.
2. **Archivos**: sube el contenido de `public_html/` a esa carpeta (ZIP → *Extraer*).
3. **Base de datos**: puede ser la misma de ZERO (las tablas de Repeat empiezan con `rp_`).
4. **Configurar**: en `app/` copia `config.example.php` como `config.php` y completa:
   - `app_url` = `https://repeat.zerosolutions.cl`
   - `db`: los mismos datos de ZERO
   - `app_secret`: una clave larga (`php -r "echo bin2hex(random_bytes(32));"`)
   - `zero_sso_secret`: **igual** a `sso_secret` del `config.php` de ZERO
5. **En ZERO** (`public_html/app/config.php` de zerosolutions.cl) agrega Repeat a las apps:
   ```php
   'apps' => ['flowdesk' => 'https://zerosolutions.cl/flowdesk', 'repeat' => 'https://repeat.zerosolutions.cl'],
   ```
   Desde ese momento Repeat aparece en zerosolutions.cl/productos y en la cuenta de cada persona.

Las tablas se crean solas en la primera visita.

## Billeteras (opcional)

Sin esto la tarjeta funciona igual en el navegador del celular, con su QR y sus sellos en vivo. Con esto aparecen los
botones "Agregar a Google Wallet" y "Agregar a Apple Wallet", y cada sello actualiza la tarjeta guardada.

### Google Wallet (gratis)

1. En [pay.google.com/business/console](https://pay.google.com/business/console) crea la cuenta de emisor (Google
   Wallet API) y completa el perfil del negocio. Copia el **Issuer ID**.
2. En [Google Cloud](https://console.cloud.google.com) crea un proyecto, activa la **Google Wallet API**, crea una
   **cuenta de servicio** y descarga su clave **JSON**.
3. En la consola de Google Wallet → *Usuarios*, invita el correo de esa cuenta de servicio.
4. Sube el JSON a `app/keys/google-wallet.json` y pon el Issuer ID en `config.php` → `google_wallet.issuer_id`.
5. Mientras Google no apruebe la cuenta (modo demo), solo las cuentas de prueba que agregues pueden guardar tarjetas.

### Apple Wallet (US$99 al año)

1. Inscribe a ZERO en el [Apple Developer Program](https://developer.apple.com/programs/enroll/) como organización
   (pide número D-U-N-S, que es gratis pero puede tardar).
2. *Certificates, IDs & Profiles* → *Identifiers* → **Pass Type ID** `pass.cl.zerosolutions.repeat`.
3. Crea su certificado (*Pass Type ID Certificate*), ábrelo en un Mac y expórtalo desde Llavero como `.p12`. Conviértelo:
   ```bash
   openssl pkcs12 -in Certificados.p12 -out pass.pem -nodes -legacy
   ```
4. Descarga el certificado intermedio **Apple WWDR G4** y conviértelo a PEM:
   ```bash
   openssl x509 -inform der -in AppleWWDRCAG4.cer -out wwdr.pem
   ```
5. Sube `pass.pem` y `wwdr.pem` a `app/keys/` y completa `apple_wallet` en `config.php` (`pass_type_id`, `team_id`).

Los iPhone se registran solos en `/wallet/v1/...` y reciben un aviso (APNs) con cada sello.

## Desarrollo local

```bash
cp public_html/app/config.example.php public_html/app/config.php   # app_url http://127.0.0.1:8090
php -S 127.0.0.1:8090 -t public_html public_html/index.php
```

Para entrar en local hace falta ZERO corriendo (por ejemplo en :8080) con `apps.repeat` apuntando a :8090 y el
mismo secreto de inicio de sesión en ambos.
