# 08. Frontend: Vue 3 SPA

## Objetivo

Crear una aplicación responsive para iniciar sesión, consultar competiciones, importar ligas y explorar equipos/jugadores, además de administrar usuarios, roles y permisos.

## Tecnologías

- Vue 3.
- Vue Router.
- El uso de TypeScript, Pinia, Vuetify y CASL es opcional según el enunciado.
- Centralizar las llamadas HTTP en un módulo de API para no repetir configuración.

## Pantallas sugeridas

| Vista | Contenido |
|---|---|
| Login | Correo, contraseña, validaciones y mensajes de error. |
| Dashboard | Resumen y accesos según permisos. |
| Competiciones | Listado y acción para abrir/importar detalle. |
| Detalle de competición | Datos de liga y equipos asociados; estado de importación. |
| Equipos | Listado de equipos guardados, búsqueda/paginación opcional. |
| Detalle de equipo | Información del equipo y jugadores asociados cuando estén disponibles. |
| Jugadores | Listado de jugadores guardados, posición y dorsal. |
| Usuarios | Listar, crear, editar y desactivar usuarios según permisos. |
| Roles y permisos | Consultar roles y administrar asignaciones según el alcance implementado. |
| Acceso denegado | Explicar que la cuenta no tiene permiso para la acción. |

## Navegación

- Usa Vue Router.
- Protege rutas privadas mediante una comprobación de sesión.
- Usa guards para redirigir a login cuando no exista sesión válida.
- Comprueba permisos para mejorar la navegación, pero el backend debe volver a autorizarlos.
- Define una estrategia clara para el destino tras login y logout.

## Gestión de sesión

- Guarda y envía el token según la estrategia de seguridad elegida.
- No guardes el token de football-data.org en el cliente.
- Ante `401`, limpia el estado de sesión y dirige al login con un mensaje.
- Ante `403`, muestra acceso denegado sin asumir que el token expiró.
- Evita redirecciones infinitas entre guards y login.

La decisión entre almacenamiento en memoria, `sessionStorage`, `localStorage` u otro mecanismo debe tomarse teniendo en cuenta el modelo de amenazas y documentarse. No guardes credenciales del usuario en texto plano.

### Decisión de sesión implementada

- El token Passport se guarda en `sessionStorage` por pestaña y se elimina al cerrar sesión, al recibir `401` o al cerrar la pestaña.
- No se guarda la contraseña ni el token de football-data.org en el navegador.
- `sessionStorage` limita la persistencia frente a `localStorage`, pero el token sigue accesible para JavaScript; no protege frente a XSS. En producción se requiere HTTPS y conviene evaluar cookies `HttpOnly` con un flujo compatible con Passport.

### Ejecución local

Desde `frontend/`, ejecuta `npm install` y `npm run dev`. El Vite proxy reenvía `/api` a `http://127.0.0.1:8000`, por lo que también debe estar iniciado Laravel. La URL del backend se puede reemplazar con `VITE_API_BASE_URL`; para despliegues en otro origen se debe configurar CORS explícitamente en el backend.
El backend permite el origen indicado por `FRONTEND_URL` (por defecto `http://localhost:5173`) para las rutas `/api/*`. Si el frontend se sirve desde otro host/origen, actualiza esa variable.

## Estados de interfaz

Todas las vistas que consulten datos deben manejar:

- Cargando.
- Resultado con datos.
- Lista vacía.
- Error de validación.
- No autenticado (`401`).
- Sin permiso (`403`).
- Límite excedido (`429`).
- Proveedor temporalmente no disponible (`502`/`503`).

Los mensajes deben explicar el problema y, cuando sea útil, la acción que el usuario puede tomar.

## Componentes reutilizables sugeridos

- `AppLayout`
- `AppSidebar` o navegación superior
- `PageHeader`
- `LoadingState`
- `EmptyState`
- `ErrorState`
- `ConfirmDialog`
- `DataTable`
- `PermissionGate`

No es necesario crear componentes genéricos antes de identificar usos repetidos.

## Diseño responsive y accesibilidad

- Probar escritorio y móvil.
- Etiquetar campos de formularios.
- Mantener foco visible y navegación por teclado.
- No comunicar errores únicamente mediante color.
- Mostrar confirmación antes de desactivar usuarios.
- Deshabilitar botones mientras una acción está en curso para reducir solicitudes duplicadas.

## Contrato con backend

Mantén alineados los tipos y respuestas de frontend con `05-api-rest.md`. Si cambia una ruta o formato, actualiza ambos lados y añade pruebas cuando sea posible.
