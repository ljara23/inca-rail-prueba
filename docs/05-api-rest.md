# 05. Contrato de API REST

Las rutas siguientes respetan el enunciado. Los detalles de payload y respuesta son una propuesta de contrato y deben mantenerse consistentes entre backend y frontend.

## Convenciones

- Prefijo recomendado: `/api`.
- Formato: JSON.
- Rutas protegidas con autenticación Passport, excepto login.
- Aplicar autorización por permiso en el servidor.
- Fechas en ISO 8601.
- Los identificadores de ruta corresponden a IDs locales o externos según se indique explícitamente; no mezclar ambos.

## Endpoints requeridos

| Método | Ruta | Descripción | Acceso sugerido |
|---|---|---|---|
| `POST` | `/api/login` | Autenticar y emitir token | Público |
| `POST` | `/api/logout` | Invalidar token actual | Autenticado |
| `GET` | `/api/competitions` | Listar competiciones disponibles | `competitions.view` |
| `GET` | `/api/competitions/{competition_id}` | Obtener detalle local; importar si el usuario tiene `competitions.import` | `competitions.view` |
| `GET` | `/api/team` | Listar equipos guardados localmente | `teams.view` |
| `GET` | `/api/team/{team_id}` | Detalle de equipo local | `teams.view` |
| `GET` | `/api/players` | Listar jugadores guardados localmente | `players.view` |
| `GET` | `/api/players/{player_id}` | Obtener detalle de un jugador local | `players.view` |

La prueba enumera cinco endpoints de fútbol, aunque la lista contiene seis rutas de fútbol al contar ambos endpoints de competiciones, ambos de equipo y el de jugadores. Implementa todas las rutas listadas literalmente en el enunciado.

## Administración ACL

El enunciado exige gestionar usuarios, roles y permisos, pero no especifica rutas para ello. Define y documenta las rutas adicionales que implemente el proyecto. Ejemplo de propuesta:

| Método | Ruta | Propósito |
|---|---|---|
| `GET` | `/api/me` | Perfil y permisos efectivos |
| `GET`, `POST` | `/api/users` | Listar y crear usuarios |
| `GET`, `PUT`, `PATCH` | `/api/users/{id}` | Consultar o editar usuario |
| `PATCH` | `/api/users/{id}/status` | Activar/desactivar usuario |
| `GET`, `POST` | `/api/roles` | Listar y crear roles |
| `GET`, `PUT` | `/api/roles/{id}` | Consultar o editar rol |
| `GET` | `/api/permissions` | Listar permisos disponibles |

Estas rutas son una propuesta, no rutas explícitamente prescritas por el enunciado.

La ruta de detalle de jugador complementa F3 de la prueba: permite navegar desde los listados de jugadores y desde la plantilla de un equipo a una ficha local. El rol `Solo consulta` tiene permisos de lectura y no recibe `competitions.import`.

## Ejemplos de payload

### Login

```json
{
  "email": "admin@example.test",
  "password": "password-de-desarrollo"
}
```

La respuesta real debe ajustarse a la configuración de Passport y nunca incluir el hash de la contraseña.

### Formato de respuesta sugerido

Éxito:

```json
{
  "data": {},
  "meta": {}
}
```

Error:

```json
{
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "No se encontró el recurso.",
    "details": []
  }
}
```

La API implementa esta envoltura de error con `details` como lista. Los códigos incluyen `VALIDATION_FAILED`, `UNAUTHENTICATED`, `FORBIDDEN`, `RESOURCE_NOT_FOUND`, `RATE_LIMIT_EXCEEDED` y `FOOTBALL_DATA_UNAVAILABLE`.

## Códigos HTTP

| Código | Uso |
|---|---|
| `200` | Consulta correcta o actualización con respuesta |
| `201` | Recurso creado |
| `204` | Operación correcta sin cuerpo, si se adopta esta convención |
| `400` | Solicitud mal formada, si corresponde |
| `401` | Falta autenticación o token válido |
| `403` | Usuario autenticado sin permiso |
| `404` | Recurso inexistente |
| `422` | Error de validación |
| `429` | Límite de solicitudes excedido |
| `503` | Servicio externo temporalmente no disponible |

## Validación y paginación

- Usa Form Requests para validar datos entrantes.
- Define paginación para listas potencialmente grandes.
- Valida `page`, `per_page` y filtros si se implementan.
- No devuelvas trazas, secretos ni mensajes internos en producción.
- Documenta qué rutas consultan exclusivamente datos locales y cuáles pueden llamar al proveedor.
