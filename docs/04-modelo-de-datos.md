# 04. Modelo de datos implementado

El esquema se implementa con migraciones, modelos y relaciones Eloquent. Las tablas OAuth de Passport y las tablas operativas de caché y colas complementan las entidades descritas aquí.

## Entidades principales

### `users`

| Campo | Propósito |
|---|---|
| `id` | Clave primaria local |
| `name` | Nombre del usuario |
| `email` | Correo único |
| `password` | Hash de contraseña |
| `is_active` | Indica si puede acceder; valor inicial `true` |
| `created_at`, `updated_at` | Auditoría básica |

### `roles`

| Campo | Propósito |
|---|---|
| `id` | Clave primaria |
| `name` | Nombre del rol |
| `slug` | Identificador único estable |
| `created_at`, `updated_at` | Auditoría básica |

### `permissions`

| Campo | Propósito |
|---|---|
| `id` | Clave primaria |
| `name` | Nombre legible |
| `slug` | Código único de permiso |

### Tablas de relación ACL

- `role_user`: relaciona usuarios con roles mediante una relación muchos-a-muchos.
- `permission_role`: relaciona roles con permisos.

### `competitions`

| Campo | Propósito |
|---|---|
| `id` | Clave primaria local |
| `external_id` | Identificador del proveedor, con índice único |
| `name` | Nombre de la competición |
| `code` | Código externo, nullable si no existe |
| `area_name` | Nombre del área o país, si está disponible |
| `emblem` | Emblema, nullable |
| `season` | Fecha de inicio de temporada recibida del proveedor, nullable |
| `last_synced_at` | Momento de última sincronización exitosa |

### `teams`

| Campo | Propósito |
|---|---|
| `id` | Clave primaria local |
| `external_id` | Identificador externo, único y requerido |
| `name` | Nombre del equipo |
| `short_name` | Nombre abreviado, nullable |
| `tla` | Sigla, nullable |
| `crest` | URL de escudo, nullable |

### `competition_team`

Relación muchos-a-muchos entre competiciones y equipos. Las claves foráneas eliminan relaciones huérfanas y una clave primaria compuesta evita vínculos duplicados.

### `players`

| Campo | Propósito |
|---|---|
| `id` | Clave primaria local |
| `external_id` | Identificador externo único y requerido para persistir al jugador |
| `team_id` | Equipo asociado; nullable y protegido por clave foránea con `ON DELETE SET NULL` |
| `name` | Nombre del jugador |
| `position` | Posición |
| `shirt_number` | Número de camiseta, nullable |

La posición y el dorsal son nullable porque el proveedor puede omitirlos. La sincronización actual omite registros externos de competición, equipo o jugador que no traigan identificador externo.
El identificador externo del jugador es globalmente único; al sincronizarlo asociado a otro equipo, se actualizan el registro y su `team_id`.

## Relaciones Eloquent esperadas

- `User` ↔ `Role`: muchos-a-muchos mediante `role_user`.
- `Role` ↔ `Permission`: muchos-a-muchos.
- `Competition` ↔ `Team`: muchos-a-muchos.
- `Team` ↔ `Player`: uno-a-muchos mediante `players.team_id`.

## Restricciones e índices

- Correo único en `users`.
- `slug` único en roles y permisos.
- `external_id` único en competiciones.
- `external_id` único en competiciones, equipos y jugadores.
- Índice único compuesto en tablas pivote.
- Claves foráneas con políticas de borrado explícitas.

## Consideraciones

- No eliminar datos locales solo porque una importación externa no los incluya temporalmente.
- Usar transacciones durante importaciones.
- No asumir que los identificadores de una tabla externa equivalen a los IDs locales.
- Las importaciones omiten registros externos sin identificador; posición y dorsal pueden permanecer nulos sin inventar valores.
