# 10. Registro de decisiones técnicas

Registra aquí decisiones relevantes, alternativas consideradas y consecuencias. No marques como implementada una decisión hasta verificarla en el código.

## ADR-001: arquitectura por capas pragmática

**Estado:** propuesta.

**Contexto:** el sistema es acotado y debe desarrollarse en un plazo de 48 horas. Se necesita separar controladores, lógica de aplicación, persistencia e integración externa sin sobrediseñar.

**Decisión propuesta:** controladores delgados, Form Requests, Resources, servicios de aplicación, modelos Eloquent y un cliente aislado para football-data.org.

**Alternativas:** Clean Architecture completa, arquitectura hexagonal, DDD y arquitectura mínima basada solo en controladores.

**Consecuencias:** mantiene responsabilidades claras y facilita probar la integración externa; si el dominio crece, las capas pueden evolucionar. No introduce interfaces o abstracciones sin un beneficio concreto.

## ADR-002: persistencia local como fuente de consulta

**Estado:** propuesta.

**Contexto:** el proveedor tiene límite de 10 peticiones por minuto y puede estar no disponible.

**Decisión propuesta:** almacenar los datos importados y responder los listados locales desde Eloquent; reservar llamadas externas para importación/sincronización según la política definida.

**Consecuencias:** mejora resiliencia y reduce consumo de cuota; los datos pueden quedar desactualizados, por lo que conviene registrar `last_synced_at`.

## ADR-003: protección del límite externo

**Estado:** pendiente de confirmar implementación.

**Contexto:** un contador en memoria o una caché sin coordinación no garantiza un límite global entre procesos.

**Opciones:** limitador atómico compartido, locks y/o cola que serialice las llamadas externas.

**Criterio de decisión:** escoger la solución que pueda implementarse, probarse y explicarse con claridad en el plazo disponible. Documentar el comportamiento bajo concurrencia.

## ADR-004: base de datos

**Estado:** pendiente.

**Decisión:** registrar el motor finalmente elegido y la razón (compatibilidad del entorno, facilidad de ejecución, disponibilidad y soporte Eloquent).

**Consecuencias:** actualizar `.env.example`, migraciones, instrucciones de instalación y pruebas.

## ADR-005: gestión de roles y permisos

**Estado:** pendiente de implementación.

**Decisión:** documentar si un usuario puede tener uno o varios roles, cómo se modelan los permisos y cómo se serializan para el frontend.

**Consecuencias:** el servidor aplica las reglas de autorización; Vue usa los permisos para adaptar la interfaz, sin sustituir la autorización del backend.

## Plantilla para nuevas decisiones

### ADR-XXX: título

- **Estado:** propuesta / aceptada / sustituida.
- **Contexto:** problema que requiere una decisión.
- **Opciones:** alternativas consideradas.
- **Decisión:** opción seleccionada y motivo.
- **Consecuencias:** ventajas, costes y limitaciones.
- **Requisitos relacionados:** IDs B, F o D.
