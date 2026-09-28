# SIMAC · Asambleas y sorteos

Sistema del **Sindicato de Maestros de Casanare** para llevar el padrón docente, registrar la asistencia
a las asambleas y hacer sorteos transparentes con acta.

Tiene tres puestos, cada uno con su rol:

| Puesto | Dirección | Rol | Para qué |
| --- | --- | --- | --- |
| Panel | `/inicio` | Administrador | Padrón, municipios y colegios, carnés, jornadas, sorteos, historial y usuarios |
| Mesa de registro | `/registro` | Registrador (y administrador) | Entradas y salidas de la jornada abierta, con buscador o lector de códigos de barras |
| Pantalla de proyección | `/pantalla` | Proyector (y administrador) | La animación del sorteo y el ganador, para el videoproyector |

Después de iniciar sesión, cada rol llega directo a su puesto.

## Qué hace

- **Padrón:** docentes con código `SIM-###`, afiliación sindical, municipios y colegios. Retirar a alguien no borra su historial.
- **Jornadas:** una abierta a la vez, con quórum por número o porcentaje de afiliados, medido en vivo.
- **Mesa de registro:** un solo campo. `Enter` registra la entrada, `Shift+Enter` la salida y `Esc` borra. Cada movimiento se puede deshacer.
- **Carnés:** tamaño tarjeta de identificación, 8 por hoja carta, con un código de barras que la mesa lee de un toque.
- **Sorteos:**
  - el servidor elige a los ganadores con un generador criptográfico;
  - el acta queda sellada **antes** de la animación, con ganadores, filtros, la lista completa de participantes y si había quórum;
  - la pantalla solo muestra un resultado ya decidido;
  - se pueden sortear varios ganadores por acta.
- **Historial:** actas en PDF con espacio para firmas. Mientras un sorteo está en pantalla, ni el historial ni el PDF revelan a los ganadores que aún no salieron.
- **Tiempo real:** la mesa, el panel, la consola y la pantalla se actualizan solos con **Laravel Reverb**. Si Reverb se cae, nada se detiene: la pantalla y la consola siguen al día preguntando cada pocos segundos.

## Tecnología

Laravel 13 · PHP 8.4 · Livewire 4 · Flux UI · Tailwind CSS 4 · Fortify (contraseña, 2FA y passkeys) ·
spatie/laravel-permission · Laravel Reverb · dompdf (actas) · picqer/php-barcode-generator (carnés) · Pest.

Requisitos: PHP 8.4 con `pdo_sqlite` o `pdo_mysql`, `mbstring`, `dom`, `intl` y `fileinfo`; Composer 2;
Node 22 o superior (solo para compilar los assets).

## Desarrollo local

```bash
composer run setup          # dependencias, .env, clave, migraciones y assets
php artisan db:seed         # datos de demostración (ver abajo)
composer run dev            # servidor, cola, Vite, Reverb y logs en una sola terminal
```

Para las actualizaciones en vivo, pon valores en `REVERB_APP_ID`, `REVERB_APP_KEY` y `REVERB_APP_SECRET` del
`.env`. Sirve cualquier cadena aleatoria, por ejemplo `php -r "echo bin2hex(random_bytes(16));"`. Sin esos
valores la aplicación funciona igual, pero sin actualizaciones en vivo.

`php artisan db:seed` crea un usuario por rol, todos con la contraseña `password`:

| Usuario | Rol |
| --- | --- |
| `admin@simac.test` | Administrador |
| `registro@simac.test` | Registrador |
| `pantalla@simac.test` | Proyector |

Además crea los 19 municipios de Casanare, colegios y docentes de ejemplo, una jornada abierta y una jornada
pasada con tres actas.

### Pruebas y calidad

```bash
php artisan test --compact  # suite de Pest
vendor/bin/pint             # formato
vendor/bin/phpstan analyse  # análisis estático (Larastan)
composer test               # las tres cosas, como en CI
```

## Despliegue en producción

Pensado para un servidor Linux con Nginx y PHP-FPM. La base de datos puede ser MySQL, PostgreSQL o SQLite.
**Nunca corras `php artisan db:seed` sin `--class` en producción**: crea los usuarios de demostración con
contraseña `password`.

### 1. Código y dependencias

```bash
git clone <repositorio> /var/www/simac && cd /var/www/simac
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

`storage/` y `bootstrap/cache/` deben poder escribirse por el usuario del servidor web (por ejemplo `www-data`).

### 2. Variables de entorno (`.env`)

| Variable | Valor en producción |
| --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | La dirección pública con **HTTPS** (las passkeys solo funcionan con HTTPS) |
| `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | La base de datos |
| `SESSION_DRIVER`, `CACHE_STORE` | `database` (ya vienen así) |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Un servidor SMTP: lo usa «¿Olvidaste tu contraseña?» |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | Cadenas aleatorias propias (no reutilices las de desarrollo) |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | El dominio público, `443` y `https` (ver Reverb abajo) |
| `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT` | Dónde escucha Reverb dentro del servidor: `127.0.0.1` y `8080` |

Las variables `VITE_REVERB_*` toman su valor de las `REVERB_*` y **se incrustan en el JavaScript al compilar**.
Si cambias las de Reverb, vuelve a correr `npm run build`.

### 3. Assets, base de datos y primer administrador

```bash
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force   # roles y permisos (en cada despliegue)
php artisan db:seed --class=CitySeeder --force                  # los 19 municipios (solo agrega los que falten)
php artisan app:create-admin-user                               # pide nombre, correo y contraseña
php artisan optimize
```

Los demás usuarios se crean desde **Usuarios** en el panel. El registro público está desactivado.

### 4. Reverb (tiempo real)

Reverb es un proceso aparte que debe estar siempre corriendo. Con Supervisor, en
`/etc/supervisor/conf.d/simac-reverb.conf`:

```ini
[program:simac-reverb]
command=php /var/www/simac/artisan reverb:start
directory=/var/www/simac
user=www-data
autostart=true
autorestart=true
stopwaitsecs=10
redirect_stderr=true
stdout_logfile=/var/www/simac/storage/logs/reverb.log
```

Cada conexión abierta ocupa un descriptor de archivo. Si esperas muchas pantallas y mesas a la vez, sube
`minfds=10000` en la sección `[supervisord]` de `/etc/supervisor/supervisord.conf`.

Nginx recibe las conexiones en el mismo dominio y las pasa a Reverb. `/app` es el WebSocket de los
navegadores y `/apps` es la API por la que Laravel publica los eventos. Dentro del bloque `server` del sitio:

```nginx
location ~ ^/apps?/ {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_read_timeout 120s;
    proxy_pass http://127.0.0.1:8080;
}
```

No hace falta un worker de colas ni el programador de tareas: los eventos en vivo se envían al momento y la
aplicación no tiene tareas programadas.

### 5. Actualizar a una versión nueva

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan optimize
php artisan reverb:restart
php artisan up
```

### 6. Copias de seguridad

Las actas son el respaldo legal de cada sorteo: respalda la base de datos al menos después de cada asamblea.
Con SQLite basta copiar el archivo de la base; con MySQL, `mysqldump`.

## El día de la asamblea

1. **Antes de abrir la puerta:** abre la jornada desde **Jornadas** y ajusta el quórum.
2. **Proyector:**
   - en el computador del videoproyector, entra con el usuario proyector (llega a `/pantalla`);
   - pulsa `F` para pantalla completa y toca la pantalla una vez para activar el sonido;
   - en la consola del sorteo debe verse «1 pantalla conectada». Sin pantalla conectada, «¡Ya!» queda bloqueado.
3. **Mesas:**
   - cada mesa entra con un usuario registrador, desde un celular o un computador;
   - un lector de códigos de barras USB funciona sin configurar nada más, siempre que envíe `Enter` al final (el sufijo de fábrica en casi todos);
   - el código de barras lleva `SIM012`, sin guion, así que funciona con cualquier distribución de teclado.
4. **Carnés:** imprímelos desde **Carnés** en tamaño carta, escala 100 % y con «Gráficos de fondo».

## Problemas frecuentes

- **La página se ve sin estilos ni JavaScript en desarrollo.** Quedó un `public/hot` de un `npm run dev` que ya
  no corre. Bórralo, o arranca `composer run dev`.
- **Nada se actualiza en vivo.** Revisa que Reverb esté corriendo y que las credenciales `REVERB_*` no estén
  vacías; si cambiaste las `VITE_REVERB_*`, vuelve a compilar con `npm run build`.
- **La consola no detecta la pantalla.** La pantalla debe estar abierta en `/pantalla`. Recargarla la anuncia
  al instante, y cerrarla la quita del conteo.

## Más información

Las decisiones del proyecto, el modelo de datos, los permisos y la bitácora de cada fase están en
[PLAN.md](PLAN.md).
