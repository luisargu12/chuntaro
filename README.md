# Chuntaro — MVC artesanal

PHP + Bootstrap 5 + GSAP (CDN). Sin Vue/npm.

## Ambientes (solo `.env`)

| Variable | Local (XAMPP) | Hostinger |
|----------|---------------|-----------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `BASE_PATH` | `/chuntaro` | *(vacío)* |
| `APP_URL` | `http://localhost/chuntaro` | `https://chuntaro.com.mx` |
| `DB_*` | tu MySQL local | MySQL Hostinger |

Plantilla prod: `.env.production.example`

## Setup local

1. Importa `setup.sql` en phpMyAdmin
2. `composer install` (si falta `vendor`)
3. Revisa `.env`
4. Abre: http://localhost/chuntaro/  → **vista pública**
5. Admin: http://localhost/chuntaro/admin/login → `admin` / `admin123`

## Estructura

- `app/views/public/` — sitio público
- `app/views/admin/` — panel
- `public/` — CSS, JS, imágenes, front controller
- `app/Config/App.php` — lee `.env` (paths, debug, DB)

## Sincronización de EA Clubs

EA bloquea las IP de Hostinger. Los datos se consultan desde la PC local y se
envían al caché de producción mediante un endpoint protegido.

1. Genera un token: `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`
2. Coloca el mismo token en `EA_SYNC_TOKEN` del `.env` local y de Hostinger.
3. En Hostinger usa `EA_REMOTE_FETCH_ENABLED=false`.
4. Despliega el código.
5. Desde la raíz local ejecuta: `php tools/sync-ea.php`

El endpoint receptor es `POST /api/sync/ea`. Nunca publiques el token ni lo
guardes en Git.
