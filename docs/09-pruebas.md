# 09. Estrategia de pruebas

## Prioridad

Primero verifica los requisitos obligatorios. Las pruebas automáticas son un opcional en el enunciado, pero aportan evidencia de calidad y reducen regresiones.

## Backend: pruebas de funcionalidad

### Autenticación y autorización

- Login con credenciales correctas.
- Login con contraseña incorrecta.
- Ruta protegida sin token.
- Logout y uso posterior del token revocado.
- Usuario desactivado.
- Usuario desactivado usando un token previamente emitido.
- Rol de solo consulta intentando administrar usuarios.
- Administrador ejecutando acciones permitidas.

### Endpoints y persistencia

- Listado de competiciones.
- Detalle de competición e importación.
- Listado y detalle de equipos locales.
- Listado de jugadores locales.
- Detalle de jugador local y jugador inexistente.
- Recurso inexistente devuelve 404.
- Entrada inválida devuelve 422.
- Importación repetida no crea duplicados.
- Relaciones entre competición, equipos y jugadores quedan consistentes.

### Integración externa y límite

- Respuesta correcta del proveedor.
- Timeout.
- Fallo de conexión.
- Error 4xx/5xx del proveedor.
- Cuota agotada.
- Límite de diez llamadas devuelve HTTP 429 cuando no existe dato local.
- Respuesta correcta queda cacheada para evitar llamadas redundantes.
- Solicitudes simultáneas de importación de la misma competición (pendiente de prueba de concurrencia real).
- Consulta local cuando el proveedor está caído.
- No se filtra el token en logs o respuestas.

Usa mocks/fakes HTTP para que las pruebas sean deterministas y no consuman el cupo real.

## Frontend: pruebas sugeridas

- Renderizado del login y validaciones.
- Login exitoso e incorrecto.
- Redirección tras sesión expirada.
- Vista sin permiso.
- Estados de carga, vacío y error.
- Importación de competición y feedback al usuario.
- Acciones administrativas condicionadas por permisos.
- Diseño responsive revisado en tamaños relevantes.

## Comandos

Documenta los comandos reales disponibles en el proyecto. En Laravel suele usarse `php artisan test`; en Vue depende del runner configurado y de los scripts de `package.json`. No declares comandos hasta verificar que existen.

Comandos disponibles y verificados en este proyecto: `php artisan test` desde `backend/` y `npm run build` desde `frontend/`. El test de cuota verifica diez llamadas externas en secuencia; la coordinación concurrente se garantiza mediante locks compartidos, pero no se ha ejecutado un test de estrés de varios workers.

## Matriz de aceptación manual

| Escenario | Resultado esperado |
|---|---|
| Usuario no autenticado abre una ruta privada | `401` en API y redirección a login en SPA |
| Usuario sin permiso ejecuta acción | `403` y mensaje claro |
| Equipo no existe | `404` |
| Entrada inválida | `422` |
| Se agota el límite local de llamadas externas | `429` si corresponde al limitador local |
| El proveedor rechaza por cuota o no responde | Error normalizado `502`/`503` o mapeo documentado |
| Se consulta un equipo ya guardado mientras el proveedor falla | La API responde desde la base local |
| Se importa dos veces la misma competición | No aparecen duplicados |
| Proyecto se clona en una carpeta limpia | Puede instalarse y ejecutarse siguiendo README |

## Informe de resultados

Antes de entregar, anota qué pruebas se ejecutaron, cuáles pasaron, cuáles fallaron y qué quedó pendiente. No afirmes que hay cobertura automatizada si solo se hicieron pruebas manuales.

### Verificación ejecutada el 12 de octubre de 2026

- Backend instalado desde una copia limpia: `composer install`, migraciones, seeders, claves y cliente personal de Passport; todos los pasos terminaron correctamente.
- Suite backend en el proyecto y en la copia limpia: 25 pruebas, 79 aserciones, todas pasaron con PHP 8.5.11.
- `composer validate --no-check-publish`, `composer check-platform-reqs` y `composer install --dry-run`: correctos; Sodium y los requisitos de Composer están habilitados.
- Frontend desde una copia limpia: `npm ci` y `npm run build` correctos; no se reportaron vulnerabilidades durante `npm ci`.
- Se verificó manualmente en la SPA el listado de jugadores, el detalle local de un jugador y el detalle de equipo con su plantilla.
- La cuota se probó con diez llamadas permitidas y la siguiente rechazada; la coordinación usa locks compartidos, pero no se ejecutó un test de estrés con múltiples workers.
- Las pruebas del proveedor usan respuestas simuladas; esta verificación no consume cuota real de football-data.org.
