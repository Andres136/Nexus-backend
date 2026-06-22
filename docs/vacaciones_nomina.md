# Vacaciones y su integración con nómina

## Objetivo

El módulo usa una sola regla de saldo para solicitudes, aprobación, vacaciones
compensadas y liquidación de retiro. La causación se calcula con año laboral
de 360 días:

`días causados = días laborales comerciales × 15 / 360`

## Autorización

- El empleado consulta y crea solicitudes únicamente por
  `/api/nomina/portal/vacaciones`. El backend asigna siempre `Auth::id()` y no
  acepta que el cliente elija otro empleado.
- Listar globalmente, editar, eliminar, aprobar, rechazar y consultar el resumen
  de otro empleado requiere `es_responsable_del_departamento`.
- Consultar, preliquidar y liquidar prestaciones también requiere ese
  middleware.
- El middleware también permite al administrador con `role_id = 1`.

## Estados del saldo

- **Causados:** derecho acumulado en el contrato activo.
- **Consumidos:** vacaciones ordinarias aprobadas y vacaciones compensadas que
  ya tienen una liquidación de prestaciones.
- **Comprometidos:** solicitudes pendientes y compensadas aprobadas pendientes
  de liquidar.
- **Disponibles:** causados menos consumidos menos comprometidos.

Las solicitudes pendientes reservan saldo. Al aprobar se recalcula el saldo
dentro de una transacción para evitar que dos solicitudes consuman los mismos
días.

## Contrato

Las nuevas solicitudes guardan `contratacion_id`. El cálculo solamente considera
el contrato activo. Para datos anteriores a esta relación se admiten registros
sin `contratacion_id` cuya fecha de inicio sea posterior al inicio del contrato.

## Validaciones

- El empleado debe tener contrato activo.
- Las fechas deben estar dentro del contrato.
- Los días hábiles no pueden superar los días calendario informados.
- No se permiten cruces con otra solicitud pendiente o aprobada.
- No se puede solicitar ni aprobar más del saldo disponible.
- Una solicitud aprobada no se puede editar ni eliminar.

Los días hábiles continúan siendo informados explícitamente porque el sistema no
dispone aún de un calendario corporativo de festivos y jornadas por empleado.
Cuando exista ese calendario, este campo debe calcularse en backend.

## Vacaciones compensadas

Las solicitudes ordinarias y compensadas aprobadas se liquidan por el flujo de
prestaciones. La relación `vacacion_id` es única y evita liquidar una solicitud
dos veces.

- Las ordinarias se pagan por separado antes de procesar la nómina del período.
  La nómina resta los días calendario comerciales cubiertos por el descanso y
  paga solamente los días de salario restantes. Por ejemplo, si la vacación
  inicia el día 3 de una quincena y cubre el resto del período, la nómina paga
  2 días y la liquidación separada paga las vacaciones aprobadas.
- Las compensadas se pagan exclusivamente por prestaciones y no afectan los
  días salariales de la nómina.

Si una vacación ordinaria aprobada cruza el período y todavía no tiene
liquidación, la nómina se bloquea para impedir que el empleado quede sin el pago
separado o que posteriormente se genere un doble pago.

## Liquidación de retiro

La liquidación de retiro descuenta:

- vacaciones ordinarias aprobadas;
- vacaciones compensadas que ya fueron liquidadas.

Una compensada aprobada pero no liquidada no se descuenta del derecho pendiente,
de modo que el valor no se pierda al terminar el contrato.

## Pruebas mínimas recomendadas

- creación por el propio empleado y rechazo de suplantación;
- autorización de responsable/administrador;
- reserva de saldo por pendientes;
- revalidación concurrente al aprobar;
- rechazo de fechas cruzadas;
- una sola liquidación por solicitud compensada;
- ausencia de pago compensado en nómina mensual;
- retiro con compensadas liquidadas y pendientes.
