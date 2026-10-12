# Instrucciones para agentes de código

## Objetivo

Implementar la prueba técnica Inca Rail Football respetando el enunciado original. Prioriza todos los requisitos obligatorios antes de implementar funcionalidades opcionales.

## Stack obligatorio

- Backend: PHP 8.5, Laravel 13 y Laravel Passport compatible.
- Frontend: Vue 3 y Vue Router.
- Persistencia relacional con migraciones, modelos y relaciones Eloquent.
- Integración externa con football-data.org.

No cambies estas tecnologías sin una razón documentada y sin confirmar compatibilidad.

## Antes de modificar código

1. Inspecciona la estructura existente y los archivos de dependencias.
2. Lee `README.md` y los documentos relevantes de `docs/`.
3. Comprueba versiones instaladas y comandos disponibles; no inventes scripts.
4. Identifica los requisitos B, F o D que cubre el cambio.
5. Evita reescrituras grandes si una modificación localizada es suficiente.

## Backend

- Mantén los controladores delgados.
- Usa Form Requests para validar entradas y API Resources para respuestas.
- Aísla las llamadas al proveedor externo en una clase cliente dedicada.
- Coloca la lógica de aplicación en servicios o clases específicas.
- Usa Eloquent para persistencia y relaciones; evita SQL manual para el acceso habitual a datos.
- Usa transacciones cuando una importación deba mantener consistencia entre varias entidades.
- Evita duplicados al importar repetidamente la misma competición.
- Devuelve JSON consistente y códigos HTTP correctos.
- No confíes en la autorización del frontend: aplica autorización en el servidor.
- No registres tokens, contraseñas ni datos sensibles.

## Seguridad y configuración

- No hardcodees secretos.
- Usa variables de entorno y mantén `.env.example` sin valores reales.
- No desactives middleware de autenticación para facilitar pruebas.
- Al desactivar un usuario, impide nuevas autenticaciones y define cómo invalidar o rechazar sus tokens existentes.
- Aplica hash seguro a las contraseñas mediante los mecanismos de Laravel.
- Configura CORS de forma explícita para los orígenes necesarios.

## football-data.org

- Respeta el máximo documentado de 10 peticiones por minuto.
- Evita llamadas externas redundantes mediante persistencia local y caché cuando corresponda.
- No hagas que las consultas locales dependan de la disponibilidad del proveedor.
- Maneja timeout, errores de red, respuestas no exitosas y límites del proveedor.
- No uses reintentos agresivos; un reintento también consume cuota.
- La configuración del límite debe funcionar de forma segura si hay solicitudes concurrentes.
- Documenta las decisiones y sus limitaciones.

## Frontend

- Usa Vue Router para navegación.
- Implementa estados de carga, error, vacío y éxito.
- Gestiona sesión expirada y errores 401/403 con mensajes comprensibles.
- Oculta acciones no autorizadas, pero recuerda que el servidor es la autoridad.
- Diseña vistas responsive y componentes reutilizables cuando aporte valor.
- No incluyas el token de football-data.org en el frontend.

## Calidad

- Añade o actualiza pruebas para la funcionalidad modificada.
- Ejecuta las pruebas y verificaciones disponibles; informa con precisión qué se ejecutó y qué falló.
- No afirmes que una prueba pasó si no fue ejecutada.
- Mantén los cambios enfocados y explica supuestos importantes.
- Actualiza la documentación cuando cambie el contrato, la arquitectura o la configuración.

## Orden de prioridad

1. Requisitos obligatorios del enunciado.
2. Seguridad, consistencia de datos y manejo del límite externo.
3. Pruebas y documentación reproducible.
4. Opcionales, solo si los anteriores están completos.
