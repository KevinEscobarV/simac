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
  - `laravel/reverb` + `laravel-echo`/`pusher-js` (fase 6);
  - `barryvdh/laravel-dompdf` (fase 11);
  - `picqer/php-barcode-generator` (fase 12).

### Decididas durante el trabajo
- **Modo oscuro**: se conserva el selector de apariencia del starter kit (claro, oscuro o sistema).
  Los tokens de la marca tienen variante oscura.

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
| `teachers` | school_id (restrict), name, document_number (único), code `SIM-###` (único, lo genera el sistema), is_union_member, soft deletes |
| `assemblies` (jornadas) | name, date, location?, quorum_type? (`count`/`percentage`), quorum_value?, closed_at? (null = abierta), opened_by |
| `attendances` | assembly_id (cascade), teacher_id, checked_in_at, checked_out_at?, registered_by · único (assembly_id, teacher_id) |
| `raffles` (actas) | assembly_id? (null on delete), prize, winners_count, animation, filters (json), filter_description, participants_count, quorum_met?, drawn_by, drawn_at |
| `raffle_entries` | raffle_id (cascade), teacher_id (restrict), winner_position? · PK (raffle_id, teacher_id) |
| estado de proyección | fase, raffle_id, intento, ganador revelado (índice). Se guarda en el servidor (caché o tabla de una fila; se decide en la fase 8) |

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

Después del login, cada rol aterriza en su puesto:

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

### Fase 2 · Roles, permisos y usuarios
- [ ] Enums `Role` y `Permission` y seeder de roles y permisos (idempotente)
- [ ] Usuario administrador inicial por seeder (credenciales desde `.env`)
- [ ] Redirección por rol después del login (respuesta de login de Fortify)
- [ ] Protección de rutas por permiso (middleware de spatie) y políticas
- [ ] Módulo **Usuarios**: listar, crear, editar, asignar rol, desactivar y restablecer contraseña
- [ ] Decidir qué pasa con «Eliminar cuenta» en Ajustes. El starter kit deja que cualquier usuario borre su propia cuenta, incluso el único administrador
- [ ] Tests: acceso por rol a cada ruta, CRUD de usuarios, redirección

### Fase 3 · Ciudades y colegios
- [ ] Migraciones, modelos, factories y relaciones `City` y `School`
- [ ] Seeder con las ciudades y colegios de la demo
- [ ] Módulo **Lugares**: CRUD de ciudades y colegios con contadores y bloqueo de borrado si hay dependencias
- [ ] Tests: unicidad, bloqueo de borrado, permisos

### Fase 4 · Padrón de docentes
- [ ] Migración, modelo `Teacher` (soft deletes), factory y seeder con los 18 docentes de la demo
- [ ] Código `SIM-###` automático y único
- [ ] Scopes de consulta: búsqueda (nombre, cédula, código, colegio, ciudad), ciudad, colegio, afiliados, presentes en una jornada
- [ ] Módulo **Docentes**:
  - [ ] Métricas
  - [ ] Búsqueda y filtros en la URL
  - [ ] Tabla paginada
  - [ ] Alta y edición en modal (ciudad → colegio en cascada, con opción de crear el colegio al vuelo)
  - [ ] Cambio rápido de afiliación
  - [ ] Retirar, con protección si tiene actas
- [ ] Tests: validación (cédula única), código generado, filtros, protección de borrado

### Fase 5 · Jornadas y asistencia (panel admin)
- [ ] Migraciones y modelos `Assembly` y `Attendance`
- [ ] Objeto de valor `Quorum`, con el cálculo y el estado
- [ ] Actions:
  - [ ] Jornada: abrir, cerrar, reabrir, eliminar y ajustar el quórum
  - [ ] Asistencia: entrada, salida, anular y deshacer
- [ ] Módulo **Jornadas**:
  - [ ] Tarjeta de jornada en curso: contadores y quórum con barra
  - [ ] Padrón de la jornada con filtros (todos, presentes, ya salieron, sin registrar)
  - [ ] Entrada con `Enter` desde el buscador
  - [ ] Jornadas anteriores
- [ ] Tests: una sola jornada abierta, entrada/repetido/reingreso, deshacer, cálculo del quórum (unitarios)

### Fase 6 · Tiempo real (Reverb)
- [ ] Instalar y configurar Reverb y Echo (`php artisan install:broadcasting`)
- [ ] Canales privados autorizados por permiso (`routes/channels.php`)
- [ ] Eventos `AttendanceChanged` y `AssemblyStatusChanged`. El panel de jornadas se actualiza en vivo
- [ ] Añadir Reverb a `composer run dev`
- [ ] Tests: los eventos se difunden (`Event::fake`) y la autorización de los canales

### Fase 7 · Mesa de registro (`/registro`)
- [ ] Layout de quiosco (cabecera oscura, cuerpo claro, primero para celular)
- [ ] Componente de quiosco:
  - [ ] Campo grande siempre enfocado y búsqueda con *debounce*
  - [ ] `Enter`, `Shift+Enter` y `Esc`
  - [ ] Resolución por cédula, código o nombre
- [ ] Confirmación grande con **Deshacer**, que se retira sola
- [ ] Últimos movimientos, contadores, reloj y tira de quórum
- [ ] Estado «sin jornada abierta» que se actualiza solo (Reverb)
- [ ] Diseño primero para celular
- [ ] Tests: resolución de clave (exacta, única, ambigua, inexistente), movimientos y deshacer

### Fase 8 · Motor del sorteo
- [ ] Migraciones y modelos `Raffle` y `RaffleEntry`. Enums `RaffleAnimation` y `ProjectionPhase`
- [ ] Action `DrawRaffle`:
  - [ ] Filtros, excluir ganadores previos y N ganadores con CSPRNG
  - [ ] Premio y descripción del filtro
  - [ ] Quórum congelado y acta en transacción
- [ ] Máquina de estados de la proyección: preparar, lanzar, repetir, siguiente ganador, terminado (valida el intento) y liberar
- [ ] Evento `ProjectionUpdated` y contador de pantallas conectadas (latido)
- [ ] Tests: reglas de participantes, exclusión, varios ganadores sin repetir, acta completa y transiciones de fase

### Fase 9 · Nuevo sorteo y consola de proyección (admin)
- [ ] Formulario en pasos:
  - [ ] ¿Quiénes participan?
  - [ ] Premio y número de ganadores
  - [ ] Animación (con ilustraciones)
- [ ] Resumen en vivo: participantes, filtros, aviso de quórum y pantallas conectadas
- [ ] Consola:
  - [ ] Pasos (cargado → en pantalla → ganador)
  - [ ] Botones «¡Ya!», «Repetir», «Siguiente ganador» y «Liberar»
  - [ ] «Ver antes»
- [ ] Indicador «EN VIVO» en la navegación
- [ ] Tests del componente (Livewire)

### Fase 10 · Pantalla de proyección (`/pantalla`)
- [ ] Layout de escenario, siempre oscuro
- [ ] Escenario: degradado, orbes, textura y viñeta. Estados de espera y «PREPÁRENSE»
- [ ] Animaciones en Alpine:
  - [ ] Ruleta SVG
  - [ ] Tómbola
  - [ ] Cuenta regresiva con revelado
- [ ] Confeti y sonidos
- [ ] Tarjeta del ganador y cuadro de honor para varios ganadores
- [ ] Pantalla completa (botón y tecla `F`, que se ocultan solos) y aviso de reconexión
- [ ] Entra en la fase actual si se abre tarde

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
- [ ] Páginas 403 y 404 con la marca. Estados vacíos y de carga
- [ ] Revisión responsive, accesibilidad (foco, `aria-live`) y `prefers-reduced-motion`

### Fase 14 · Cierre
- [ ] Suite completa, Pint y Larastan en verde
- [ ] Guía de despliegue: Reverb, queue y scheduler, variables de entorno, usuario admin inicial

---

## 8. Bitácora

| Fecha | Fase | Nota |
| --- | --- | --- |
| 2026-09-28 | 0 | Análisis de la demo y plan de trabajo creados. Decisiones: Reverb, código en inglés, usuarios/roles, PDF, carnés, premio, excluir ganadores previos y varios ganadores. |
| 2026-09-28 | 1 | Fundaciones y sistema de diseño. Se conserva el modo oscuro. El panel queda en `/inicio`. Los layouts de mesa y pantalla se crean en sus fases. |
