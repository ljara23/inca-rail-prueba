# 03. Reglas de negocio

Estas reglas traducen el enunciado a comportamiento verificable. Las reglas marcadas como propuesta deben confirmarse durante la implementación.

## Usuarios y acceso

1. Un usuario se autentica con correo y contraseña.
2. La contraseña se guarda mediante hash; nunca en texto plano.
3. Cada usuario tiene uno o más roles según el diseño implementado.
4. Los roles agrupan permisos.
5. El backend valida los permisos para cada acción protegida.
6. Un usuario desactivado no puede iniciar sesión. La API también debe rechazar sus tokens existentes según la estrategia de invalidación definida.
7. El sistema incluye al menos los roles `Administrador` y `Solo consulta`.
8. El rol `Solo consulta` puede leer competiciones, equipos, jugadores y permisos; no administra cuentas ni dispara importaciones externas.

## Competiciones

1. `GET /competitions` devuelve las competiciones disponibles según el contrato de la integración.
2. `GET /competitions/{competition_id}` obtiene el detalle. Usuarios con `competitions.import` sincronizan y persisten la competición, sus equipos y jugadores; usuarios de solo consulta reciben únicamente los datos locales.
3. Importar la misma competición más de una vez no debe crear duplicados.
4. Si el proveedor falla, los datos ya almacenados deben seguir disponibles a través de los endpoints locales; el detalle con equipos locales responde con `meta.source=local`, `meta.stale=true` y `meta.warning`.
5. Si la importación no se completa, la API debe comunicar el fallo con claridad y no dejar una importación parcialmente inconsistente.

## Equipos

1. `GET /team` devuelve equipos guardados localmente.
2. `GET /team/{team_id}` devuelve el detalle de un equipo guardado.
3. Un equipo inexistente devuelve 404.
4. La clave externa del proveedor debe guardarse para relacionar registros importados y facilitar la idempotencia.

## Jugadores

1. `GET /players` devuelve los jugadores guardados localmente.
2. Cada jugador conserva como mínimo nombre, posición y número de camiseta.
3. Se recomienda almacenar el identificador externo cuando esté disponible para reconocer al jugador en importaciones posteriores.
4. `GET /players/{player_id}` devuelve el detalle local; si el jugador no existe, responde 404.
5. El identificador externo identifica globalmente al jugador: si otra importación lo asocia a un equipo distinto, se actualizan sus datos y su equipo en vez de insertar otro registro.

## Integridad e idempotencia

- Definir restricciones únicas para identificadores externos y relaciones que deban ser únicas.
- Actualizar los atributos existentes cuando corresponda, en lugar de insertar siempre.
- Usar transacciones para que una importación falle completa o se confirme de forma consistente.
- Definir explícitamente cómo se representan los campos opcionales que no vengan en la respuesta externa.

## Cuota externa

- No realizar una llamada al proveedor por cada lectura de equipos o jugadores locales.
- Consultar caché y estado de sincronización antes de importar.
- Respetar el máximo de 10 solicitudes por minuto.
- Ante cuota agotada, no entrar en un bucle de reintentos.
- Informar al usuario si el proveedor no permite completar una nueva importación, manteniendo disponibles los datos locales.

## Decisiones implementadas

- Si el detalle de una competición se sirve desde caché cuando la copia local está actualizada.
- Qué política de actualización se usa para equipos y jugadores.
- Qué campos de la respuesta externa se persisten además de los mínimos.
- Qué acciones exactas puede ejecutar el rol de solo consulta: acceso de lectura, sin administración ni importación externa.
