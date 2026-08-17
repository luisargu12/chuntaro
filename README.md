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
