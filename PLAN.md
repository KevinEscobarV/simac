# SIMAC · Plan de trabajo

Reconstrucción en Laravel de la demo `PROJECTS/demo-sorteos-simac` (React + Express + SQLite),
con las prácticas de Laravel y un diseño igual o mejor. Este archivo es la lista de control del
proyecto: se marca cada casilla al terminar y se anota en la **Bitácora** al final.

**Convenciones:** el código va en inglés (modelos, tablas, métodos) y la interfaz y las URL en
español. Cada fase termina con sus tests en verde y `vendor/bin/pint --dirty` aplicado.

---

## 1. Qué hace la demo (resumen del análisis)

Sistema de sorteos del Sindicato de Maestros de Casanare. Tres puestos, tres pantallas:

| Puesto | URL demo | Qué hace |
| --- | --- | --- |
| Administrador | `/` | Padrón, ciudades y colegios, jornadas y asistencia, sorteos, historial |
| Mesa de registro | `/registro` | Solo entradas y salidas de la jornada abierta (pensada para celular) |
| Proyector | `/pantalla` | Solo la animación del sorteo y el ganador (fondo oscuro, ceremonial) |

En la demo la separación es solo por URL (no hay login). Aquí se hace con **roles**.

### Reglas de negocio que hay que conservar

**Padrón**
- La cédula es obligatoria y única. El **código de profesor** (`SIM-001`…) lo asigna el sistema.
- Un colegio pertenece a una ciudad. Su nombre es único dentro de esa ciudad.
- No se elimina una ciudad o un colegio que tenga registros asociados. El mensaje dice cuántos hay.
- No se borra a un docente que ya ganó un sorteo: el acta lo protege.

**Jornadas y asistencia**
- Solo una jornada abierta a la vez. Es la que define quiénes son «los presentes».
- Una jornada cerrada conserva su historial, pero no admite movimientos. Se puede reabrir si no
  hay otra abierta.
- Entrada: si es nueva, queda como `entrada`; si el docente ya estaba presente, `repetido`; si
  había salido, `reingreso` (conserva la hora de entrada original y borra la salida).
- Solo se registra la salida si hay una entrada activa.
- Deshacer:
  - una `entrada` anula el registro;
  - un `reingreso` vuelve a marcar la salida (con la hora actual);
  - una `salida` reabre la asistencia.
- Resolver al docente en la mesa:
  1. por id;
  2. si no, por clave exacta (cédula o código, sin distinguir mayúsculas);
  3. si no, por búsqueda de texto: 1 resultado lo resuelve, 0 da error y más de 1 pide elegir.

**Quórum**
- Se mide **sobre los afiliados** y cuenta a los **presentes en ese momento**.
- Tipos:
  - `número`: requerido = ⌈valor⌉;
  - `porcentaje`: requerido = ⌈afiliados × valor / 100⌉.
  - También puede haber jornada sin quórum.
- El valor debe ser mayor que 0. El porcentaje no puede pasar de 100. Si se piden más afiliados de
  los que hay en el padrón, el requerido no se recorta: el quórum queda inalcanzable y se muestra así.
- Se puede ajustar sin cerrar la jornada.
- Sin quórum se avisa pero se deja sortear. El acta queda marcada «Sin quórum»: el dato se
  congela al sortear.

**Sorteo (transparencia)**
- El ganador lo elige el **servidor** con un generador criptográfico, sobre la lista real de la base
  de datos.
- El acta se guarda **antes** de la animación: ganador, filtro aplicado, lista completa de
  participantes, jornada y si se cumplía el quórum.
- La animación solo representa un resultado ya sellado. «Repetir» muestra al mismo ganador.
- Filtros: solo afiliados, ciudad, colegio y solo presentes (este último exige una jornada abierta).
- Mínimo 2 participantes.

**Proyección**
- Fases: `espera` → `preparado` («PREPÁRENSE») → `animando` → `ganador`.
- El estado vive en el servidor. La pantalla puede encenderse tarde, recargarse o haber varias:
  todas ven lo mismo.
- «¡Ya!» y «Repetir» suben el `intento`, y eso reinicia la animación. La pantalla avisa cuando
  termina y el servidor ignora los avisos de intentos anteriores.
- La consola del admin no muestra al ganador hasta que es público, salvo con «Ver antes».
- «Liberar» devuelve la pantalla al reposo. El acta sigue en el historial.

**Mesa de registro**
- Un solo campo, siempre enfocado.
- Atajos:
  - `Enter`: entrada;
  - `Shift+Enter`: salida;
  - `Esc`: borra el campo.
- Sin término de búsqueda no se muestra el padrón.
- Confirmación grande que se retira sola (~6 s) y se puede deshacer.
- Muestra los últimos movimientos, contadores y quórum en vivo.
- Si la jornada se cierra, la mesa se entera sola.

---

## 2. Decisiones tomadas

| Tema | Decisión |
| --- | --- |
| Stack | Laravel 13 · Livewire 4 (componentes de clase, **sin** single-file) · Flux 2 (gratis) · Tailwind 4 · Fortify · spatie/laravel-permission 8 · Pest 5 |
| Tiempo real | **Laravel Reverb** + Echo (WebSockets). Eventos con `ShouldBroadcastNow` para no depender de la cola en plena asamblea |
| Idioma | Código en inglés, interfaz y URL en español (`lang/es`), zona horaria `America/Bogota` |
| Lógica de dominio | Clases *Action* en `app/Actions/*` (el starter kit ya usa `app/Actions/Fortify`), enums en `app/Enums`, políticas y *Form Objects* de Livewire |
| Azar | `Random\Randomizer` con motor `Random\Engine\Secure` (CSPRNG), que además soporta elegir N ganadores sin repetir |
| Estado de la proyección | Persistido en el servidor (no en memoria), para que sobreviva a un reinicio, y difundido por Reverb |
| Base de datos | SQLite en desarrollo. El código queda portable a MySQL o PostgreSQL gracias a Eloquent y las migraciones |
| Registro público | Desactivado (ya lo está en Fortify). Los usuarios los crea el administrador |
| Fuera de alcance por ahora | Importación masiva desde Excel |

### Mejoras sobre la demo (aprobadas)
- **Nombre del premio** en cada sorteo. Se muestra en la pantalla, en la consola y en el acta.
- **Excluir ganadores previos** de la misma jornada (opción del sorteo, activada por defecto).
- **Varios ganadores por sorteo**, con acta única. Revelado secuencial: cada «¡Ya!» anima al
  siguiente ganador y al final se muestra el cuadro de honor. Regla: participantes > ganadores.
- **Usuarios y roles** desde la interfaz.
- **Actas en PDF**.
- **Carné** con el código de profesor como código de barras.

### Por confirmar al llegar a cada fase
- Dependencias nuevas:
  - ~~`laravel/reverb` + `laravel-echo`/`pusher-js` (fase 6)~~ aprobadas e instaladas;
  - `barryvdh/laravel-dompdf` (fase 11);
  - `picqer/php-barcode-generator` (fase 12).

### Decididas durante el trabajo
- **Modo oscuro**: se conserva el selector de apariencia del starter kit (claro, oscuro o sistema).
  Los tokens de la marca tienen variante oscura.
- **«Municipio»** en la interfaz en lugar de «ciudad» (en el código sigue siendo `City`).
- **Catálogo de municipios**: los 19 de Casanare vienen sembrados; el admin puede agregar, renombrar o
  eliminar.
- **Docentes**: se retiran y reincorporan (soft delete), nunca se borran. La cédula es solo numérica.
- **Jornadas**: solo se elimina una jornada sin registros de asistencia; las demás son historial.
- **Estructura**: los objetos de valor van en `app/Support`.
- **Textos neutros**: los mensajes que nombran a un docente no asumen su género («Se registró la
  afiliación de :name»).

---

## 3. Modelo de datos

```
cities ──< schools ──< teachers
                          │
assemblies ──< attendances┤          (entrada / salida por jornada)
     │                    │
     └──< raffles ──< raffle_entries   (participantes; winner_position marca a los ganadores)
users ── roles/permissions (spatie)
```

| Tabla | Campos principales |
| --- | --- |
| `cities` | name (único) |
| `schools` | city_id (restrict), name · único (city_id, name) |
| `teachers` | school_id (restrict), name, normalized_name, document_number (único, solo dígitos), is_union_member, soft deletes. El código `SIM-###` se deriva del id |
| `assemblies` (jornadas) | name, date, location?, quorum_type? (`count`/`percentage`), quorum_value?, closed_at? (null = abierta), opened_by |
| `attendances` | assembly_id (cascade), teacher_id, checked_in_at, checked_out_at?, registered_by · único (assembly_id, teacher_id) |
| `raffles` (actas) | assembly_id? (null on delete), prize, winners_count, animation, filters (json), filter_description, participants_count, quorum_met?, drawn_by, drawn_at |
| `raffle_entries` | raffle_id (cascade), teacher_id (restrict), winner_position? · PK (raffle_id, teacher_id) · único (raffle_id, winner_position) |
| `projections` | una sola fila: phase, raffle_id?, attempt, winner_position (el ganador cargado o en pantalla) |

---

## 4. Roles y permisos

| Permiso | Administrador | Registrador | Proyector |
| --- | :-: | :-: | :-: |
| Gestionar usuarios y roles | ✓ | | |
| Gestionar padrón, ciudades y colegios | ✓ | | |
| Imprimir carnés | ✓ | | |
| Abrir, cerrar y ajustar jornadas | ✓ | | |
| Registrar asistencia (panel y mesa) | ✓ | ✓ | |
| Realizar sorteos y dirigir la proyección | ✓ | | |
| Ver historial y descargar actas | ✓ | | |
| Ver la pantalla de proyección | ✓ | | ✓ |

En código son los enums `App\Enums\Role` y `App\Enums\Permission`:

| Permiso (enum) | Cubre |
| --- | --- |
| `users.manage` | Usuarios y roles |
| `roll.manage` | Padrón, ciudades, colegios y carnés |
| `assemblies.manage` | Jornadas y quórum |
| `attendance.register` | Entradas y salidas |
| `raffles.draw` | Sorteos y proyección |
| `raffles.view` | Historial y actas |
| `screen.view` | Pantalla de proyección |

La aplicación siempre pregunta por el permiso, nunca por el rol. Cada usuario tiene exactamente un rol.

Después del login, cada rol aterriza en su puesto (`User::homeRoute()`):

| Rol | Destino |
| --- | --- |
| Administrador | panel (`/inicio`) |
| Registrador | `/registro` |
| Proyector | `/pantalla` |

## 5. Pantallas y rutas

| URL | Nombre de ruta | Rol |
| --- | --- | --- |
| `/inicio` | `dashboard` | Admin: métricas, jornada en curso, último sorteo |
| `/docentes` | `teachers.index` | Admin |
| `/docentes/carnes` | `teachers.cards` | Admin |
| `/lugares` | `locations.index` | Admin |
| `/jornadas` | `assemblies.index` | Admin |
| `/sorteos/nuevo` | `raffles.create` | Admin (se convierte en consola mientras hay algo proyectándose) |
| `/sorteos` | `raffles.index` | Admin |
| `/sorteos/{raffle}` | `raffles.show` | Admin |
| `/sorteos/{raffle}/pdf` | `raffles.pdf` | Admin |
| `/usuarios` | `users.index` | Admin |
| `/registro` | `desk` | Admin, Registrador |
| `/pantalla` | `screen` | Admin, Proyector |

## 6. Diseño

- Identidad de la demo: **verde institucional + oro ceremonial sobre neutros cálidos**, como tokens
  en `@theme` (`brand`, `gold`, `stage`). Las escalas `zinc` y `red` se sustituyen por los neutros
  verdosos y el rojo cálido de la demo, así los componentes de Flux heredan la identidad.
- Tipografías: Inter (interfaz) y Fraunces (títulos y cifras).
- Marca propia en SVG (birrete sobre escudo). Sin emoji.
- Ambientes:
  - **Panel**: claro y sobrio, con barra lateral verde oscura, textura de puntos y acento dorado.
  - **Mesa**: cabecera oscura y cuerpo claro, pensada primero para celular.
  - **Pantalla**: oscura y ceremonial, legible a distancia.
- Flux para formularios, modales, tablas y toasts, con el acento de Flux en el verde de la marca.
- Animaciones en **Alpine.js** (ruleta SVG, tómbola, cuenta regresiva y revelado letra a letra),
  confeti, sonidos con WebAudio y `prefers-reduced-motion`.

---

## 7. Fases

### Fase 0 · Análisis y plan
- [x] Analizar la demo (backend, reglas, pantallas, diseño)
- [x] Revisar el proyecto base (Laravel 13, Livewire 4, Flux 2, Fortify, spatie/permission)
- [x] Resolver dudas con el usuario y escribir este plan

### Fase 1 · Fundaciones y sistema de diseño ✅
- [x] Configuración:
  - [x] `APP_NAME=SIMAC`, `APP_LOCALE=es`, zona horaria `America/Bogota`, faker `es_ES` (Faker no trae `es_CO`)
  - [x] Traducciones `lang/es` (validación, auth, contraseñas, paginación) y `lang/es.json` con los textos de Fortify, Flux y el starter kit
- [x] Sistema de diseño en `app.css`:
  - [x] Tokens `brand`, `gold`, `stage`, más `zinc` y `red` sustituidos por los neutros verdosos y el rojo cálido de la demo, para que Flux los herede
  - [x] Inter y Fraunces alojadas en el proyecto (funcionan sin internet)
  - [x] Sombras, animaciones y utilidades `bg-dots` y `bg-institutional`
  - [x] Acento de Flux en el verde de la marca (claro y oscuro)
- [x] Componentes de marca `<x-brand.mark>` y `<x-brand.logo>`. Favicon SVG, `.ico` y `apple-touch-icon`
- [x] Layouts:
  - [x] Panel admin: barra lateral verde oscura en los dos modos, con indicador dorado del ítem actual y cabecera móvil. `<x-page-header>`
  - [x] Autenticación: pantalla dividida con panel institucional
  - Mesa (quiosco) y Escenario (pantalla): pasan a las fases 7 y 10, donde tienen página que los use
- [x] Login, 2FA, recuperación y confirmación de contraseña con la identidad SIMAC
- [x] Quitados `welcome`, logos y layouts sin uso del starter kit. `/` redirige a `/inicio`
- [x] Tests (27), Pint y Larastan en verde. Revisado en el navegador: claro, oscuro, escritorio y celular

### Fase 2 · Roles, permisos y usuarios ✅
- [x] Enums `Role` y `Permission`, y `RolesAndPermissionsSeeder` idempotente (sincroniza la base con los enums; correrlo en cada despliegue)
- [x] Administrador inicial con `php artisan app:create-admin-user`: pide los datos por consola, así la contraseña no queda en `.env`. `DatabaseSeeder` crea un usuario por rol para desarrollo
- [x] Rutas protegidas con el middleware `can:` y `UserPolicy`. Se usa `can:` y no el middleware de spatie porque Livewire lo vuelve a aplicar en cada petición interna
- [x] Usuarios desactivados (`deactivated_at`): el middleware `EnsureUserIsActive` cierra su sesión por cualquier vía de entrada (contraseña, 2FA, passkey, «recordarme»)
- [x] Módulo **Usuarios** (`/usuarios`): búsqueda, filtro por rol, crear, editar, cambiar contraseña, desactivar y reactivar
  - Nadie puede cambiar su propio rol ni desactivarse (así nunca queda el sistema sin administrador)
- [x] Se quitó «Eliminar cuenta» de Ajustes
- [x] Traducciones de las páginas de error de Laravel
- [x] Tests (58), Pint y Larastan en verde. Revisado en el navegador: admin, registrador y usuario desactivado; claro, oscuro y celular
- Redirección por rol después del login: pasa a las fases 7 y 10

### Fase 3 · Municipios y colegios ✅
- [x] Migraciones, modelos, factories y relaciones `City` y `School` (en la interfaz, «municipios»)
- [x] Seeders:
  - `CitySeeder`: los 19 municipios de Casanare. Solo agrega los que faltan, así que se puede correr en producción al instalar (`php artisan db:seed --class=CitySeeder --force`)
  - `DemoSchoolSeeder`: los 8 colegios de la demo, solo para desarrollo
- [x] Módulo **Municipios y colegios** (`/lugares`):
  - municipios a la izquierda con su número de colegios; colegios del municipio elegido a la derecha (el municipio va en la URL)
  - búsqueda de colegios en todos los municipios
  - campo rápido para agregar colegios; modales para renombrar, mover un colegio de municipio y eliminar
  - orden alfabético que ignora tildes («Támara» antes de «Tauramena»); los nombres se guardan sin espacios de más
- [x] Un municipio con colegios no se puede eliminar: `DeleteCity` explica cuántos quedan
- [x] Tests (35 en las carpetas tocadas), Pint y Larastan en verde. Revisado en el navegador: claro, oscuro y celular
- El conteo de docentes y el bloqueo de borrado de colegios con docentes pasan a la fase 4, cuando exista `Teacher`

### Fase 4 · Padrón de docentes ✅
- [x] Migración, modelo `Teacher` (soft deletes), factory y `DemoTeacherSeeder` con los 18 docentes de la demo
- [x] Municipios y colegios: conteo de docentes; un colegio con docentes (aunque estén retirados) no se puede eliminar (`DeleteSchool`)
- [x] Código `SIM-###` derivado del id: único, nunca se reutiliza y no necesita contador. Se lee como «SIM-012», «sim-12» o «SIM12»
- [x] Búsqueda sin tildes y por palabras sueltas («hector nino» → «Héctor Fabio Niño»): columna `normalized_name` en municipios, colegios y docentes (trait `HasNormalizedName`), que también ordena en SQL
- [x] Scopes: búsqueda (nombre, cédula con o sin puntos, código, colegio, municipio), municipio, afiliados. «Presentes en una jornada» pasa a la fase 5
- [x] Módulo **Docentes** (`/docentes`):
  - [x] Métricas: docentes, afiliados (con %), colegios y municipios con docentes
  - [x] Búsqueda y filtros en la URL (municipio → colegio en cascada, afiliación)
  - [x] Tabla paginada con pestañas «Activos» y «Retirados»
  - [x] Alta y edición en modal: municipio → colegio en cascada, y el colegio que falta se agrega sin salir del diálogo
  - [x] Cambio rápido de afiliación con un toque
  - [x] Retirar (soft delete) y reincorporar. Nada se borra: el historial queda intacto
- [x] Cédula: solo números, 5 a 12 dígitos; se acepta con puntos y se guardan los dígitos. Única también frente a los retirados
- [x] Tests (69 en las carpetas tocadas), Pint y Larastan en verde. Revisado en el navegador: claro, oscuro y celular

### Fase 5 · Jornadas y asistencia (panel admin) ✅
- [x] Migraciones y modelos `Assembly` y `Attendance` (única por jornada y docente)
- [x] Scope `Teacher::presentAt(Assembly)` (presentes en una jornada)
- [x] Enum `QuorumType` y objeto de valor `App\Support\Quorum`: requeridos (hacia arriba y sin ruido de coma flotante), faltantes, cumplido, alcanzable, avance
- [x] Actions:
  - [x] Jornada: abrir, cerrar, reabrir y eliminar. Un lock (`Cache::lock`) garantiza una sola abierta. Solo se elimina una jornada sin registros
  - [x] Asistencia: `CheckIn` (entrada, ya presente o reingreso, a prueba de dos mesas a la vez con `createOrFirst`), `CheckOut`, `VoidAttendance` y `UndoMovement` (rechaza si el registro cambió)
  - [x] `ResolveTeacher`: cédula o código exactos primero, luego nombre (uno resuelve, cero o varios dan error). La mesa de la fase 7 lo reutiliza
- [x] Módulo **Jornadas** (`/jornadas`, grupo «Asamblea»):
  - [x] Tarjeta oscura de jornada en curso: contadores y quórum con barra (componente `x-assemblies.quorum`, reutilizable en la mesa)
  - [x] Padrón de la jornada con filtros (todos, presentes, ya salieron, sin registrar); en celular el filtro es un select
  - [x] Entrada con `Enter` desde el buscador (cédula con o sin puntos, código o nombre)
  - [x] Ajuste del quórum sin cerrar la jornada, con la traducción a personas («harán falta 7 de 13 afiliados»)
  - [x] Jornadas anteriores: reabrir y eliminar
- [x] `DemoAssemblySeeder`: jornada abierta como la de la demo (desarrollo)
- [x] Tests (110 en las carpetas tocadas, incluidos unitarios del quórum), Pint y Larastan en verde. Revisado en el navegador: claro, oscuro y celular

### Fase 6 · Tiempo real (Reverb) ✅
- [x] Reverb 1.12, Echo 2 y pusher-js instalados con `php artisan install:broadcasting --reverb`
  - Reverb exige Guzzle 7: Composer bajó `guzzlehttp/guzzle` de 8 a 7
  - `echo.js` no crea Echo si faltan las credenciales: la app funciona igual, sin actualizaciones en vivo
  - `<meta name="csrf-token">` en el `<head>`: Echo lo necesita para autorizar canales privados
- [x] Canal privado `assemblies` (`routes/channels.php`), autorizado por `AssemblyPolicy::followLive`: administrador y registrador sí, proyector no. Se quitó el canal de ejemplo `App.Models.User.{id}`
- [x] Eventos `AttendanceChanged` (entrada, reingreso, salida, anular y deshacer) y `AssemblyChanged` (abrir, ajustar quórum, cerrar, reabrir y eliminar)
  - Llevan solo el id de la jornada: quien escucha vuelve a leer los datos. Ningún dato personal viaja por el WebSocket
  - `ShouldBroadcastNow` (sin cola), `ShouldDispatchAfterCommit` y `ShouldRescue`: si Reverb está caído la entrada se guarda igual y el error queda en el log
  - Se emiten con `toOthers()`: la pestaña que hizo el cambio no se refresca dos veces
  - Tiempo de espera corto hacia Reverb (conexión 1 s, total 3 s), así un servidor caído o inalcanzable no frena la mesa
- [x] Nueva action `AdjustQuorum` (antes el componente actualizaba el modelo directamente)
- [x] El panel de jornadas escucha los dos eventos y se actualiza solo (probado con Reverb: entrada desde otra «mesa», cierre y reapertura)
- [x] Reverb se agrega solo a `composer run dev` (`php artisan dev` lo incluye desde que el paquete está instalado)
- [x] Tests (97 en las carpetas tocadas): eventos emitidos y no emitidos, falla de Reverb sin perder la entrada, autorización del canal por rol. Pint y Larastan en verde

### Fase 7 · Mesa de registro (`/registro`) ✅
- [x] Layout de quiosco `layouts::kiosk`: sin menú lateral; la cabecera oscura la pone la página
- [x] Después del login, el registrador aterriza en `/registro`
  - `User::homeRoute()` decide por permisos y `/inicio` redirige a quien trabaja en otro puesto; así funciona igual con contraseña, 2FA, passkey o «recordarme»
  - En el menú lateral, «Inicio» solo aparece a quien usa el panel. Nuevo ítem «Mesa de registro» en «Asamblea» (administrador y registrador)
  - Permiso de la ruta: `AssemblyPolicy::useDesk` (registrar asistencia)
- [x] Componente `Desk\Index`:
  - [x] Campo grande siempre enfocado y búsqueda con *debounce*; vuelve a enfocarse después de cada movimiento
  - [x] `Enter` (entrada), `Shift+Enter` (salida) y `Esc` (borra). La clave viaja tal cual al servidor, así el lector de códigos de barras no espera la lista. Un segundo Enter mientras se procesa el primero se ignora
  - [x] Resolución por cédula, código o nombre (`ResolveTeacher`); varias coincidencias muestran el error y la lista para elegir. Sin término no se muestra el padrón (máximo 8 resultados)
- [x] Confirmación grande (entrada verde, salida dorada, aviso neutro) con **Deshacer**, barra de cuenta regresiva y retiro solo a los 6 s. Los errores se quedan hasta el siguiente intento
- [x] Cabecera: contadores, reloj y tira de quórum (`x-assemblies.quorum`). Últimos 8 movimientos de todas las mesas
- [x] Estado «sin jornada abierta» que se actualiza solo (Reverb, con un sondeo cada 30 s de respaldo). Si cierran la jornada, la mesa borra lo escrito y vuelve a esperar
- [x] Menú de usuario con «Panel administrativo» para quien lo tiene (`x-user-menu` acepta enlaces propios de la página)
- [x] Diseño primero para celular; revisado en claro, oscuro, escritorio y celular, y con entradas desde otra «mesa» en vivo
- [x] Arreglo que también afecta al panel de jornadas: Livewire solo conserva entre peticiones los errores con nombre de propiedad, y el envío diferido del buscador que sigue a un Enter borraba el error («varias coincidencias»). Ahora se reportan sobre `search` (trait `ReportsOnSearch`)
- [x] `Attendance::teacher()` incluye docentes retirados: el registro es historial y conserva a su docente
- [x] Tests (160 en las carpetas tocadas): acceso por rol y destino después del login, Enter y Shift+Enter por clave, clave ambigua, error que sobrevive a la petición siguiente, lista solo con término, deshacer, «ya presente» sin deshacer, últimos movimientos en orden, jornada cerrada en medio. La resolución de claves ya la cubre `ResolveTeacherTest`

### Fase 8 · Motor del sorteo ✅
- [x] Migraciones y modelos `Raffle` (acta; `drawn_at` es su única marca de tiempo) y `RaffleEntry` (pivote con `winner_position`, único por sorteo). Enums `RaffleAnimation` (ruleta, tómbola, revelado) y `ProjectionPhase`
- [x] Objeto de valor `App\Support\RaffleFilters`: la misma consulta cuenta a los participantes mientras se arma el sorteo (fase 9) y los elige al sortear. Solo docentes activos; «presentes» y «sin ganadores anteriores» usan la jornada abierta
- [x] Action `DrawRaffle`, todo en una transacción:
  - [x] Filtros, excluir ganadores previos de la misma jornada (activado por defecto) y N ganadores con `Random\Randomizer` + `Engine\Secure` (barajado completo, sin repetir)
  - [x] Premio, descripción del filtro en palabras (se guarda tal cual, aunque luego cambien los nombres) y participantes en el acta
  - [x] Jornada en curso y quórum congelado (`quorum_met`: sí, no, o nada si no había quórum)
  - [x] Mínimo 2 participantes y más participantes que ganadores
  - [x] Deja el sorteo cargado en la pantalla; no se puede sortear otro mientras haya uno en pantalla
- [x] Estado de la proyección en una tabla de una fila (`Projection`, aprobado): preparar, «¡Ya!» (anima al ganador cargado o, con uno en pantalla, al siguiente), repetir, terminado (ignora intentos viejos) y liberar. Cada cambio bloquea la fila, así la consola y las pantallas no se pisan
- [x] Evento `ProjectionUpdated` en el canal privado `projection` (administrador y proyector; `ProjectionPolicy`). No lleva nombres: el ganador lo muestra el servidor cuando la fase lo permite
- [x] Contador de pantallas conectadas por latido (`Projection::recordScreen()` / `connectedScreens()`, en caché, 45 s de gracia). Una pantalla nueva se anuncia al momento
- [x] `RafflePolicy` (sortear: `raffles.draw`; historial: `raffles.view`)
- [x] Una jornada con sorteos tampoco se puede eliminar: es historial
- [x] Tests (169 en las carpetas tocadas): participantes y filtros, exclusión, varios ganadores en orden, acta completa, quórum congelado, reglas de mínimo, pantalla ocupada, transiciones, intentos viejos, anuncios, latido, canal y políticas
- Siembra de un sorteo de demostración: pasa a la fase 11, donde el historial lo usa

### Fase 9 · Nuevo sorteo y consola de proyección (admin) ✅
- [x] `/sorteos/nuevo` (`Raffles\Create`, grupo «Sorteos» del menú): formulario mientras la pantalla descansa, consola mientras hay un sorteo cargado. Recargar la página no pierde nada: todo sale del servidor
- [x] Formulario en tres pasos a la vista (`RaffleForm`):
  - [x] ¿Quiénes participan?: solo presentes (se desactiva sin jornada abierta), solo afiliados, sin ganadores anteriores, municipio → colegio en cascada
  - [x] Premio y número de ganadores (1 a 20)
  - [x] Animación con ilustraciones SVG: ruleta, tómbola de nombres y cuenta regresiva. Aviso si la ruleta tiene más de 40 participantes
- [x] Resumen en vivo: participantes (con sus nombres cortos), filtros en palabras, ganadores, premio, aviso de quórum y pantallas conectadas. Se actualiza con cada entrada o salida de la jornada (Reverb) y cada 15 s
- [x] Consola:
  - [x] Pasos (cargado → en pantalla → ganador), título y detalle según la fase, datos del acta
  - [x] «¡Ya!», «Siguiente ganador (2 de 3)», «Repetir animación», «Terminar y liberar» y «Cancelar proyección» (con confirmación)
  - [x] «Ver antes», solo para quien dirige; cuadro de honor con los ganadores ya revelados
  - [x] Una orden que ya no aplica (otra consola o una pantalla se adelantaron) es solo un aviso
- [x] Indicador «EN VIVO» en la navegación (`Raffles\LiveBadge`), en vivo por Reverb y por evento de la propia página
- [x] Trait `ReportsOnSearch` generalizado a `ReportsOnProperty` (los errores del sorteo quedan en `form.draw` y no se pierden)
- [x] `Teacher::short_name` («María Fernanda Rojas» → «María Rojas»), `Projection::isLive()` y `revealed()`
- [x] Tests (207 en las carpetas tocadas): acceso, sorteo desde el formulario, validación, conteo en vivo, cascada, error que se queda, ganador oculto y «Ver antes», recorrido con dos ganadores siguiendo a la pantalla, orden que ya no aplica y «EN VIVO». Revisado en el navegador con una pantalla simulada: claro, oscuro, escritorio y celular
- El enlace a `/pantalla` desde la consola y el resumen llega con la fase 10

### Fase 10 · Pantalla de proyección (`/pantalla`) ✅
- [x] Layout `layouts::stage`, siempre oscuro (el `<head>` omite el selector de apariencia) y con su propio JS (`resources/js/screen.js` + módulos en `resources/js/screen/`)
- [x] Después del login, el proyector aterriza en `/pantalla` (`User::homeRoute()`). En el menú, «Pantalla de proyección» se abre en otra pestaña; la consola y el resumen también la enlazan
- [x] Escenario: degradado, orbes, textura y viñeta. Reposo (marca, jornada en curso, «Esperando el sorteo») y «PREPÁRENSE» con el premio, los participantes y los ganadores
- [x] Animaciones en Alpine (el servidor pinta y solo manda al ganador cuando empieza su animación):
  - [x] Ruleta SVG con aro dorado, marcas y puntero; nombres hasta 40 gajos
  - [x] Tómbola de nombres
  - [x] Cuenta regresiva con revelado letra a letra y barrido dorado
  - Con listas largas giran hasta 48 nombres (el ganador y una muestra de los demás, sin los que ya ganaron), barajados con semilla del sorteo para que todas las pantallas muestren lo mismo
- [x] Confeti y sonidos WebAudio (tic y fanfarria); botón «Activar sonido», porque el navegador no deja sonar hasta el primer gesto
- [x] Tarjeta del ganador («¡Felicitaciones!», colegio, municipio, afiliación, premio y acta), «Ya ganaron» con los anteriores y cuadro de honor 6 s después del último
- [x] Pantalla completa (botón y tecla `F`), controles y cursor que se ocultan a los 3 s
- [x] Aviso «Reconectando con el servidor…»; sin Reverb la pantalla pregunta cada 5 s y sigue al día (probado apagando Reverb)
- [x] Entra en la fase actual si se abre tarde (en «ganador», la animación aparece ya detenida)
- [x] Latido cada 15 s (una id por pestaña; recargar no cuenta como otra pantalla) y aviso de fin de animación (`finish`, que ignora intentos viejos)
- [x] `prefers-reduced-motion`: sin giro ni confeti, el resultado aparece directo
- [x] Variante `short` (alto ≤ 820 px) para proyectores de 720p. Revisado a 1280×720, 1440×900 y 1920×1080
- [x] Tests (254, toda la suite): acceso por rol y destino del proyector, reposo, «PREPÁRENSE» sin revelar al ganador, carrete con el ganador y sin los anteriores, misma ruleta en todas las pantallas, fin de animación e intento viejo, cuadro de honor y latido

### Fase 11 · Historial y actas
- [ ] Historial: acta destacada, lista con filtros por jornada, insignia «Sin quórum»
- [ ] Detalle del acta: ganadores, participantes, filtro, jornada y quórum
- [ ] PDF del acta, con espacio para firmas
- [ ] Tests: acceso y contenido del PDF

### Fase 12 · Carnés
- [ ] Vista imprimible del carné con el código de barras `SIM-###` (individual y por lote de colegio o ciudad)
- [ ] Comprobar que el escáner (que escribe el código y pulsa `Enter`) funciona en la mesa

### Fase 13 · Inicio y pulido
- [ ] Inicio del admin: métricas, jornada en curso, último sorteo y accesos rápidos
- [ ] Páginas 403 y 404 con la marca. Hoy la 403 es la de Laravel y muestra el mensaje de la excepción en inglés («This action is unauthorized.»). Estados vacíos y de carga
- [ ] Revisión responsive, accesibilidad (foco, `aria-live`) y `prefers-reduced-motion`

### Fase 14 · Cierre
- [ ] Suite completa, Pint y Larastan en verde
- [ ] Guía de despliegue: Reverb, queue y scheduler, variables de entorno, seeders de roles y municipios, usuario admin inicial
  - Reverb: `REVERB_APP_ID/KEY/SECRET` vienen vacíos en `.env.example` (cualquier valor aleatorio sirve); `npm run build` después de fijar las `VITE_REVERB_*`; `php artisan reverb:start` bajo un supervisor

---

## 8. Bitácora

| Fecha | Fase | Nota |
| --- | --- | --- |
| 2026-09-28 | 0 | Análisis de la demo y plan de trabajo creados. Decisiones: Reverb, código en inglés, usuarios/roles, PDF, carnés, premio, excluir ganadores previos y varios ganadores. |
| 2026-09-28 | 1 | Fundaciones y sistema de diseño. Se conserva el modo oscuro. El panel queda en `/inicio`. Los layouts de mesa y pantalla se crean en sus fases. |
| 2026-09-28 | 2 | Roles, permisos y usuarios. Se quitó la autoeliminación de cuentas; los usuarios se desactivan. El admin inicial se crea con `app:create-admin-user`. La redirección por rol pasa a las fases 7 y 10. |
| 2026-09-28 | 2 | Arreglo: `app:create-admin-user` no mostraba las preguntas en Windows (el `db:seed` silencioso previo se quedaba con la salida de Prompts). Ahora corre el seeder directamente. |
| 2026-09-28 | 3 | Municipios y colegios. En la interfaz se dice «municipio». Se siembran los 19 municipios de Casanare (seguro en producción) y los colegios de la demo solo en desarrollo. |
| 2026-09-28 | 4 | Padrón de docentes. Retirar = soft delete con reincorporación. Código SIM derivado del id. Búsqueda sin tildes con `normalized_name`. |
| 2026-09-28 | 5 | Jornadas y asistencia. Una jornada solo se elimina si no tiene registros. Objetos de valor en `app/Support` (aprobado). |
| 2026-09-28 | 6 | Tiempo real con Reverb (dependencias aprobadas). El evento de jornada se llama `AssemblyChanged` porque también cubre el ajuste del quórum. Una caída de Reverb nunca impide registrar. |
| 2026-09-28 | 7 | Mesa de registro. `/inicio` envía a cada rol a su puesto. Los últimos movimientos son de todas las mesas (como en la demo). Arreglado el error que desaparecía tras Enter, también en el panel. |
| 2026-09-28 | 8 | Motor del sorteo. Estado de la proyección en una tabla de una fila (aprobado). No se sortea otro mientras haya uno en pantalla. Una jornada con sorteos es historial. |
| 2026-09-28 | 9 | Nuevo sorteo y consola. Máximo 20 ganadores por sorteo. «Ver antes» solo en la consola. |
| 2026-09-28 | 10 | Pantalla de proyección. Sonido tras el primer gesto (política de los navegadores). Con listas largas, la ruleta y la tómbola muestran una muestra de 48 nombres con el ganador. Sin Reverb la pantalla sigue por sondeo. |
