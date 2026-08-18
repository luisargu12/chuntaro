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

EA bloquea las IP de Hostinger. **Nunca** se consulta `proclubs.ea.com` desde
el dominio. La PC local pide los datos a EA y los sube a
`https://chuntaro.com.mx/api/sync/ea`.

Ese POST actualiza:

- el caché en `storage/cache/` (plantilla / home)
- `tab_partidos` (historial para el calendario: liga, playoff, amistoso)

### En Hostinger

1. `EA_REMOTE_FETCH_ENABLED=false`
2. El mismo `EA_SYNC_TOKEN` que en local (mínimo 32 caracteres)
3. Tabla `tab_partidos` creada (`sql/tab_partidos.sql`)
4. Código desplegado (`Partido.php` + sync actualizado)

### Desde esta PC (XAMPP / local)

```bash
php tools/sync-ea.php
```

El script usa `EA_SYNC_URL` y `EA_MATCH_LIMIT` del `.env` local (por defecto 10
partidos por tipo). El endpoint receptor es `POST /api/sync/ea`. Nunca publiques
el token ni lo guardes en Git.
