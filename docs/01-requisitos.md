# 01. Requisitos y alcance

## Objetivo

Construir una plataforma interna para consultar competiciones, equipos y jugadores de fútbol, conservar localmente los datos consultados y gestionar el acceso mediante usuarios, roles y permisos.

## Backend: requisitos obligatorios

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| B1 | Autenticación Passport | Login con correo y contraseña, entrega token; logout invalida el token; rutas privadas protegidas; contraseñas con hash. |
| B2 | Usuarios, roles y permisos | Crear y editar usuarios, asignar roles, desactivar usuarios y autorizar acciones en el servidor. Seeders con administrador y solo consulta. |
| B3 | Endpoints | Implementar las rutas exactas documentadas en `05-api-rest.md`. |
| B4 | Persistencia Eloquent | Migraciones, modelos y relaciones; almacenar nombre, posición y número de camiseta de cada jugador; evitar duplicados. |
| B5 | Proveedor externo | Consumir football-data.org, respetar 10 peticiones/minuto y responder desde datos locales cuando el proveedor no esté disponible. Token en `.env`. |
| B6 | Errores | JSON consistente; 404, 422, 401, 403, 429 y error de proveedor 502/503 según corresponda. |
| B7 | Organización | Controladores delgados, servicios/clases dedicadas, Form Requests, Resources y un único cliente aislado para football-data.org. |

## Frontend: requisitos obligatorios

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| F1 | SPA Vue 3 | Vue Router y diseño responsive para escritorio y móvil. |
| F2 | Login | Login contra la API y mensajes claros ante sesión vencida o falta de permisos. |
| F3 | Fútbol | Listar competiciones, importar una competición consultando su detalle, ver equipos y jugadores guardados y consultar el detalle de un equipo. |
| F4 | Administración | Pantallas de usuarios, roles y permisos con acciones condicionadas por permisos. |
| F5 | Experiencia de uso | Estados de carga, listas vacías, errores del proveedor y límite de peticiones explicados claramente. |

## Documentación obligatoria

- Crear `docs/` con al menos un diagrama de arquitectura que muestre SPA, API Laravel, base de datos, football-data.org, Passport y el tratamiento del límite de peticiones.
- Incluir imagen exportada PNG, SVG o PDF y, si es posible, fuente editable.
- Mantener un `README.md` con instalación, configuración, migraciones, usuarios de prueba, decisiones, diagramas, supuestos y pendientes.

## Opcionales

No comenzar opcionales hasta que los requisitos obligatorios funcionen. Entre los opcionales del enunciado están Clean Architecture o arquitectura hexagonal, DDD, CQRS, eventos, pruebas adicionales, CI, Vuetify, Pinia, TypeScript, CASL, Docker y jobs/colas.

## Checklist de cierre

- [ ] B1–B7 verificados.
- [ ] F1–F5 verificados.
- [ ] D1–D2 verificados.
- [ ] Prueba de ejecución desde cero completada.
- [ ] Límite externo probado con concurrencia o solicitudes repetidas.
- [ ] Ningún secreto versionado.
- [ ] Supuestos y pendientes visibles en el README.
