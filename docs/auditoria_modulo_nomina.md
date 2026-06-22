# Plan de fortalecimiento del módulo de nómina

Fecha base: 22 de junio de 2026

## 1. Objetivo

Este documento es la guía de trabajo para llevar el módulo de nómina de Nexus a
un estado seguro, auditable y preparado para producción.

Cada fase debe completarse y validarse antes de avanzar a la siguiente.

## 2. Alcance

El plan cubre:

- contratación y ajustes salariales;
- asistencia y kiosko;
- reconocimiento facial;
- permisos, licencias e incapacidades;
- horas extra, comisiones y novedades retroactivas;
- descuentos y préstamos;
- preliquidación y liquidación de nómina;
- vacaciones y prestaciones;
- liquidación definitiva;
- desprendibles y certificados;
- integración contable PUC;
- autorización, auditoría y pruebas.

## 3. Flujo objetivo

```text
Contrato activo
    ↓
Asistencia y novedades
    ↓
Validación del período
    ↓
Preliquidación
    ↓
Revisión
    ↓
Aprobación
    ↓
Liquidación
    ↓
Aprobación contable
    ↓
Pago
    ↓
Cierre del período
```

Una vez cerrado el período, los registros deben ser inmutables. Cualquier
corrección posterior debe realizarse mediante reversión o novedad retroactiva.

## 4. Estado actual

### 4.1 Implementado

- [x] Portal del empleado separado de la administración.
- [x] Kiosko con autenticación propia del dispositivo.
- [x] Rutas administrativas protegidas con `auth:sanctum`.
- [x] Rutas administrativas protegidas con
  `es_responsable_del_departamento`.
- [x] Solicitudes personales de permisos bajo `/api/nomina/portal`.
- [x] Solicitudes personales de licencias bajo `/api/nomina/portal`.
- [x] Registro personal de incapacidades bajo `/api/nomina/portal`.
- [x] Solicitudes personales de vacaciones bajo `/api/nomina/portal`.
- [x] El backend asigna el usuario autenticado en solicitudes del portal.
- [x] Vacaciones ordinarias pagadas separadamente.
- [x] Días de vacaciones ordinarias excluidos de la nómina.
- [x] Vacaciones compensadas pagadas únicamente mediante prestaciones.
- [x] Prevención de doble liquidación de una solicitud de vacaciones.
- [x] Uso de transacciones en procesos principales.
- [x] Validación de períodos de nómina cruzados.
- [x] Preliquidación con estados de revisión y aprobación.
- [x] Integración contable PUC para la nómina ordinaria.

### 4.2 Validación disponible

- [x] Pruebas automatizadas actuales ejecutadas correctamente.
- [x] Build del frontend ejecutado correctamente.
- [x] Rutas administrativas verificadas con middleware.
- [ ] Migraciones verificadas en una base MySQL disponible.
- [ ] Flujo completo validado con datos reales de prueba.

## 5. Orden obligatorio de implementación

Las fases se ejecutarán en este orden:

1. Seguridad e integridad financiera.
2. Segregación de funciones y auditoría.
3. Kiosko y biometría.
4. Descuentos y novedades.
5. Motor laboral.
6. Contabilidad y pagos.
7. Pruebas integrales.
8. Refactorización y mantenimiento.
9. Integraciones legales externas.

---

# Fase 1. Seguridad e integridad financiera

Objetivo: impedir cambios no autorizados o posteriores al cierre.

## 1.1 Segunda capa de autorización

- [ ] Crear políticas o reglas reutilizables para el módulo.
- [ ] Reemplazar los `return true` sensibles de los `FormRequest`.
- [ ] Autorizar contratos y configuración.
- [ ] Autorizar liquidación de nómina.
- [ ] Autorizar prestaciones y retiro.
- [ ] Autorizar aprobación contable.
- [ ] Autorizar administración del kiosko y biometría.

### Criterio de aceptación

Un usuario autenticado que no sea administrador ni responsable no puede invocar
una operación administrativa, aunque la ruta se mueva accidentalmente fuera del
middleware.

## 1.2 Inmutabilidad de nóminas

- [ ] Bloquear edición cuando `estado_contable` sea `aprobado`.
- [ ] Bloquear edición cuando `estado_contable` sea `exportado`.
- [ ] Bloquear edición cuando `estado_contable` sea `cerrado`.
- [ ] Bloquear eliminación en esos estados.
- [ ] Bloquear modificaciones de comisiones, descuentos y novedades aplicadas.

### Criterio de aceptación

Una nómina aprobada o cerrada no puede cambiarse mediante interfaz, API o método
de servicio.

## 1.3 Eliminar persistencia financiera manual

- [ ] Deshabilitar `POST /nominas` para creación directa.
- [ ] Deshabilitar actualización manual de valores calculados.
- [ ] Persistir nómina solamente desde una preliquidación aprobada.
- [ ] Evaluar si la liquidación directa individual debe eliminarse.
- [ ] Mantener ajustes únicamente dentro del flujo auditado.

### Criterio de aceptación

Ningún cliente puede enviar directamente salario neto, deducciones o devengados
para crear una nómina.

## 1.4 Reversión formal

- [ ] Definir estados `anulada` y `reversada`.
- [ ] Crear motivo obligatorio.
- [ ] Registrar usuario y fecha de reversión.
- [ ] Restaurar comisiones y novedades asociadas.
- [ ] Generar movimiento contable inverso cuando aplique.
- [ ] Conservar siempre el registro original.

### Criterio de aceptación

Una nómina liquidada no se elimina físicamente ni por borrado lógico como método
normal de corrección.

## Validación de la fase 1

- [ ] Prueba de acceso no autorizado.
- [ ] Prueba de nómina aprobada inmutable.
- [ ] Prueba de período cerrado inmutable.
- [ ] Prueba de reversión.
- [ ] Prueba de restauración de novedades.

---

# Fase 2. Segregación de funciones y auditoría

Objetivo: evitar que una sola persona controle todo el proceso financiero.

## 2.1 Roles operativos

Definir capacidades para:

- [ ] elaborador;
- [ ] revisor;
- [ ] aprobador;
- [ ] liquidador;
- [ ] contabilidad;
- [ ] administrador.

## 2.2 Reglas de separación

- [ ] Quien genera no puede aprobar.
- [ ] Quien agrega un ajuste no puede aprobarlo.
- [ ] Quien aprueba nómina no debe cerrar contabilidad.
- [ ] El administrador puede intervenir, dejando auditoría reforzada.

## 2.3 Bitácora

Registrar:

- [ ] entidad afectada;
- [ ] UUID;
- [ ] acción;
- [ ] usuario;
- [ ] fecha y hora;
- [ ] dirección IP;
- [ ] valores anteriores;
- [ ] valores nuevos;
- [ ] motivo.

### Criterio de aceptación

Debe ser posible reconstruir quién creó, cambió, aprobó, liquidó, exportó,
reversó o cerró cualquier nómina.

## Validación de la fase 2

- [ ] Prueba de autoaprobación rechazada.
- [ ] Prueba de auditoría de ajustes.
- [ ] Prueba de auditoría de cierre.
- [ ] Reporte de bitácora por período.

---

# Fase 3. Kiosko y biometría

Objetivo: limitar exposición de datos y fortalecer las marcaciones.

## 3.1 Restricción por dispositivo

- [ ] Mostrar solamente empleados de la sede del kiosko.
- [ ] Aplicar bodega o área cuando corresponda.
- [ ] Cargar únicamente jornadas relacionadas.
- [ ] Cargar únicamente fotografías necesarias.
- [ ] Impedir marcaciones de empleados no autorizados para la sede.

## 3.2 Acceso temporal

- [ ] Separar sesión física y acceso invitado.
- [ ] Definir capacidades permitidas por token.
- [ ] Reducir vigencia del acceso temporal.
- [ ] Permitir revocación inmediata.
- [ ] Registrar qué tipo de credencial realizó la marcación.

## 3.3 Biometría

- [ ] Mover fotografías a almacenamiento privado.
- [ ] Cambiar caché a `private, no-store`.
- [ ] Registrar accesos a imágenes.
- [ ] Implementar prueba de vida.
- [ ] Documentar consentimiento.
- [ ] Definir retención y eliminación de datos biométricos.

## 3.4 Operación

- [ ] Alertas de sesiones abiertas.
- [ ] Estado en línea de dispositivos.
- [ ] Última sincronización.
- [ ] Bloqueo remoto.
- [ ] Rotación de credenciales.
- [ ] Evaluar funcionamiento sin conexión.

### Criterio de aceptación

Un kiosko solo puede identificar y marcar empleados autorizados para su sede, y
una URL temporal no expone la base biométrica completa.

## Validación de la fase 3

- [ ] Prueba de empleado de otra sede.
- [ ] Prueba de token vencido.
- [ ] Prueba de dispositivo revocado.
- [ ] Prueba de marcación repetida.
- [ ] Prueba de acceso biométrico no autorizado.

---

# Fase 4. Descuentos, préstamos y novedades

Objetivo: garantizar que cada valor aplicado pueda rastrearse y conciliarse.

## 4.1 Libro de cuotas

Crear movimientos para:

- [ ] cuota programada;
- [ ] cuota aplicada;
- [ ] cuota pagada;
- [ ] cuota reversada;
- [ ] saldo restante.

Cada movimiento debe relacionarse con la nómina que lo originó.

## 4.2 Límites

- [ ] Impedir cobrar más que el monto total.
- [ ] Impedir cuotas después de terminar el descuento.
- [ ] Tratar correctamente períodos superiores a una quincena.
- [ ] Definir prioridad de descuentos.
- [ ] Definir manejo de saldo en retiro.

## 4.3 Cruce de novedades

Crear un validador común para detectar:

- [ ] vacaciones contra incapacidad;
- [ ] vacaciones contra licencia;
- [ ] incapacidad contra asistencia;
- [ ] licencia contra asistencia;
- [ ] permisos contra horas extra;
- [ ] horas extra durante ausencias aprobadas.

### Criterio de aceptación

Cada descuento y novedad aplicada puede relacionarse con un período, una nómina
y un saldo o estado posterior.

## Validación de la fase 4

- [ ] Descuento mensual.
- [ ] Descuento quincenal.
- [ ] Reversión de cuota.
- [ ] Saldo en liquidación definitiva.
- [ ] Cruces incompatibles de novedades.

---

# Fase 5. Motor laboral

Objetivo: centralizar días, horarios, novedades y reglas de cálculo.

## 5.1 Calendario laboral

- [ ] Festivos colombianos.
- [ ] Domingos.
- [ ] Sábados laborables por jornada.
- [ ] Jornadas especiales.
- [ ] Cierres por sede.
- [ ] Días compensatorios.

## 5.2 Jornadas

- [ ] Turnos que cruzan medianoche.
- [ ] Turnos rotativos.
- [ ] Jornada parcial.
- [ ] Tolerancias configurables.
- [ ] Pausas configurables.
- [ ] Horarios temporales.

## 5.3 Incapacidades

- [ ] Origen común.
- [ ] Origen laboral.
- [ ] Tramos por días.
- [ ] Responsable del pago.
- [ ] Porcentaje por tramo.
- [ ] Valor asumido por empleador.
- [ ] Valor reconocido por EPS o ARL.
- [ ] Valor pendiente de recobro.

## 5.4 Asistencia

- [ ] Bloqueo configurable por sesiones abiertas.
- [ ] Ausencias injustificadas.
- [ ] Correcciones manuales auditadas.
- [ ] Conciliación de horas trabajadas y aprobadas.

### Criterio de aceptación

Nómina, vacaciones, prestaciones y retiro utilizan el mismo servicio de días y
reglas laborales.

## Validación de la fase 5

- [ ] Turno nocturno cruzado.
- [ ] Festivo.
- [ ] Sábado laborable.
- [ ] Incapacidad entre dos quincenas.
- [ ] Vacaciones entre dos quincenas.
- [ ] Ausencia sin justificar.

---

# Fase 6. Contabilidad y pagos

Objetivo: cerrar el ciclo financiero completo.

## 6.1 Prestaciones y retiro en PUC

Crear comprobantes para:

- [ ] prima;
- [ ] cesantías;
- [ ] intereses de cesantías;
- [ ] vacaciones ordinarias;
- [ ] vacaciones compensadas;
- [ ] liquidación definitiva;
- [ ] indemnización.

## 6.2 Estados contables

- [ ] pendiente;
- [ ] aprobado;
- [ ] exportado;
- [ ] contabilizado;
- [ ] cerrado;
- [ ] reversado.

## 6.3 Estado de pago

- [ ] pendiente;
- [ ] autorizado;
- [ ] enviado al banco;
- [ ] pagado;
- [ ] rechazado;
- [ ] reversado.

## 6.4 Conciliación

- [ ] Nómina contra comprobante contable.
- [ ] Nómina contra archivo bancario.
- [ ] Nómina contra pago confirmado.
- [ ] Diferencias y reprocesos.
- [ ] Centros de costo por empresa, sede o área.

### Criterio de aceptación

`pagos_realizados` debe representar pagos confirmados y no simplemente la suma
de la nómina neta.

## Validación de la fase 6

- [ ] Payload PUC cuadrado para nómina.
- [ ] Payload PUC cuadrado para prestaciones.
- [ ] Payload PUC cuadrado para retiro.
- [ ] Exportación y confirmación de pago.
- [ ] Reversión contable.

---

# Fase 7. Pruebas integrales

Objetivo: proteger los cálculos y flujos antes de producción.

## 7.1 Seguridad

- [ ] Empleado no puede consultar nómina ajena.
- [ ] Empleado no puede administrar contratos.
- [ ] Responsable puede gestionar.
- [ ] Usuario no autorizado recibe `403`.

## 7.2 Nómina

- [ ] Salario mensual completo.
- [ ] Primera quincena.
- [ ] Segunda quincena.
- [ ] Contrato iniciado durante el período.
- [ ] Cambio salarial durante el período.
- [ ] Nómina duplicada.
- [ ] Liquidación masiva.

## 7.3 Novedades

- [ ] Horas extra.
- [ ] Comisión.
- [ ] Permiso remunerado.
- [ ] Permiso no remunerado.
- [ ] Incapacidad.
- [ ] Licencia.
- [ ] Novedad retroactiva.

## 7.4 Vacaciones

- [x] Vacaciones desde el día 3 dejan 2 días de salario en una quincena.
- [ ] Vacaciones entre dos períodos.
- [ ] Vacaciones compensadas.
- [ ] Doble liquidación rechazada.
- [ ] Retiro con vacaciones pendientes.

## 7.5 Contabilidad

- [ ] Aprobación.
- [ ] Exportación.
- [ ] Cierre.
- [ ] Inmutabilidad.
- [ ] Reversión.

### Criterio de aceptación

Los flujos críticos deben tener pruebas de integración, no solamente pruebas de
métodos aislados.

---

# Fase 8. Refactorización y mantenimiento

Objetivo: reducir complejidad y facilitar cambios futuros.

## 8.1 Dividir `NominaService`

- [ ] `PeriodoNominaService`.
- [ ] `CalculadorSalarioService`.
- [ ] `CalculadorHorasService`.
- [ ] `CalculadorNovedadesService`.
- [ ] `CalculadorSeguridadSocialService`.
- [ ] `CalculadorDescuentosService`.
- [ ] `PersistenciaNominaService`.
- [ ] `CierreNominaService`.

## 8.2 Frontend

- [ ] Corregir deuda global de ESLint.
- [ ] Centralizar estados y etiquetas.
- [ ] Reducir lógica financiera en componentes.
- [ ] Manejar errores de autorización de forma uniforme.
- [ ] Dividir paquetes grandes mediante carga diferida.

## 8.3 Documentación

- [ ] Reglas de cálculo.
- [ ] Matriz de permisos.
- [ ] Manual de cierre.
- [ ] Manual de reversión.
- [ ] Manual del kiosko.
- [ ] Catálogo de conceptos PUC.

---

# Fase 9. Integraciones legales externas

Objetivo: completar las obligaciones externas del proceso de nómina.

- [ ] Nómina electrónica DIAN.
- [ ] Notas de ajuste de nómina electrónica.
- [ ] Integración o archivo PILA.
- [ ] Retención en la fuente.
- [ ] Embargos y libranzas con prioridad.
- [ ] Certificados tributarios.
- [ ] Provisiones mensuales.
- [ ] Reportes regulatorios.

---

# 6. Indicadores de avance

Actualizar estos indicadores después de cada fase:

| Indicador | Estado inicial | Meta |
|---|---:|---:|
| Rutas administrativas con middleware | Completado | 100 % |
| `FormRequest` sensibles autorizados | Bajo | 100 % |
| Nóminas cerradas inmutables | No | Sí |
| Flujos con reversión formal | 0 | 100 % críticos |
| Pruebas automatizadas | 5 | Cobertura de flujos críticos |
| Aserciones automatizadas | 7 | Incremento por fase |
| Servicios principales divididos | No | Sí |
| Prestaciones integradas a PUC | No | Sí |
| Estado real de pago | No | Sí |

# 7. Regla para marcar una tarea como completada

Una tarea solamente se marca `[x]` cuando:

1. el código está implementado;
2. tiene validación de autorización;
3. tiene prueba automatizada;
4. el frontend fue verificado si aplica;
5. la documentación fue actualizada;
6. no rompe el build;
7. fue probada con una base de datos real cuando requiere migraciones.

# 8. Próximo paso

El siguiente bloque que debe implementarse es:

**Fase 1.2: inmutabilidad de nóminas aprobadas, exportadas y cerradas.**

Después:

**Fase 1.3: eliminar creación y edición financiera manual.**

