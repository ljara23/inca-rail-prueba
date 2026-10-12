# 06. Seguridad, autenticación y autorización

## Autenticación

- Laravel Passport gestiona tokens de acceso.
- `POST /api/login` valida correo y contraseña y entrega el token.
- `POST /api/logout` revoca el token actual.
- Todas las rutas privadas requieren un token válido.
- Las contraseñas se almacenan con el mecanismo de hash de Laravel.
- Define expiración y revocación de tokens según la configuración compatible con la versión instalada.

## Autorización

El servidor es la autoridad. Las comprobaciones de Vue solo mejoran la experiencia de usuario y no reemplazan políticas, gates o middleware del backend.

### Roles iniciales

| Rol | Descripción |
|---|---|
| Administrador | Gestiona usuarios, roles y permisos; consulta e importa datos de fútbol. |
| Solo consulta | Consulta competiciones, equipos y jugadores; no administra usuarios ni permisos. |

Esta matriz es una propuesta inicial que debe revisarse y aplicarse mediante seeders.

### Catálogo inicial de permisos sugerido

- `competitions.view`
- `competitions.import`
- `teams.view`
- `players.view`
- `users.view`
- `users.create`
- `users.update`
- `users.deactivate`
- `roles.view`
- `roles.manage`
- `permissions.view`

No hace falta exponer una operación para editar permisos si el diseño decide que el catálogo de permisos es fijo; en ese caso, documenta cómo se asignan a los roles.

## Usuarios desactivados

- Rechazar el login de cuentas inactivas.
- Rechazar solicitudes autenticadas de usuarios inactivos, aunque tengan un token no expirado.
- Definir si la desactivación revoca además todos sus tokens.
- No confiar únicamente en que el usuario desaparezca de la interfaz.

## Secretos y configuración

- `FOOTBALL_DATA_TOKEN` solo debe existir en el entorno del backend.
- No exponer tokens de Passport ni el token externo en logs o respuestas.
- Añadir `.env` al `.gitignore`.
- Versionar `.env.example` sin secretos.
- Configurar CORS para el origen del frontend.
- En producción, usar HTTPS, `APP_DEBUG=false` y credenciales robustas.

## Manejo de errores

- `401`: no autenticado o token inválido.
- `403`: autenticado, pero sin permiso.
- `422`: validación de entrada fallida.
- `429`: cuota o rate limit excedido.
- No devolver detalles internos que faciliten ataques.

## Pruebas mínimas

- Login correcto e incorrecto.
- Acceso sin token.
- Token revocado.
- Usuario desactivado con token previo.
- Rol de solo consulta intentando administrar usuarios.
- Administrador ejecutando operaciones autorizadas.
