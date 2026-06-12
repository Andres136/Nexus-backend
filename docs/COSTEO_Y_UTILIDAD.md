# Costeo y utilidad por producto

## 1. Objetivo del modulo

El modulo de Costeo muestra una estimacion del margen bruto obtenido por cada
producto, cruzando:

- Las facturas de compra, usadas para calcular el costo promedio del producto.
- Las ordenes de compra de clientes, usadas como fuente de ventas.
- Los kilogramos ejecutados, usados como cantidad vendida para calcular el costo.

Actualmente el modulo es un reporte calculado. No existe una tabla propia de
`costeos` ni se guardan cierres historicos del resultado.

## 2. Como consultar el costeo

1. Registrar las facturas de compra con sus productos, cantidades y precios.
2. Registrar las ordenes de compra de clientes con sus productos y valores.
3. Ejecutar o descontar inventario para actualizar los kilogramos ejecutados.
4. Ingresar a **Contabilidad > Costeo**.
5. Aplicar, si se necesitan, los filtros de empresa, producto, texto o fechas.
6. Revisar el resumen global y el detalle por producto.
7. Usar **Exportar Excel** para descargar el resultado.

Un producto solo aparece cuando:

- Tiene al menos una linea en una factura de compra.
- Tiene al menos una linea en una orden de compra de cliente.
- La suma de `cantidad_ejecutada_kg` es mayor que cero.

## 3. Fuentes de datos

| Concepto | Tabla y campo |
| --- | --- |
| Producto | `products` |
| Cantidad comprada | `detalles_factura_compra.cantidad` |
| Base de compra sin IVA | `cantidad * precio_unitario` |
| Empresa de la compra | `factura_compras.empresa_id` |
| Cantidad vendida/ejecutada | `orden__compra__detalles.cantidad_ejecutada_kg` |
| Ingreso de venta sin IVA | `cantidad * valor_unitario` |
| Empresa de la venta | `orden__compras.empresa_id` |
| Fecha usada por el filtro | `orden__compra__detalles.created_at` |

`cantidad_ejecutada_kg` aumenta cuando el sistema descuenta inventario para una
orden de trabajo.

## 4. Formulas actuales

### Costo promedio sin IVA

```text
costo_promedio = suma(kilos comprados * precio unitario sin IVA)
                 / suma(kilos comprados)
```

### Costo total estimado de las ventas

```text
costo = kg ejecutados * costo_promedio
```

### Ingreso sin IVA

```text
ingreso = suma(unidades vendidas * valor unitario sin IVA)
```

### Utilidad bruta estimada

```text
utilidad = ingreso - costo
```

### Margen

```text
margen_porcentaje = utilidad / ingreso * 100
```

El margen global se calcula dividiendo la utilidad total entre el ingreso total.
No corresponde al promedio simple de los margenes individuales.

## 5. Ejemplo

Compras registradas para un producto:

| Cantidad | Total linea |
| ---: | ---: |
| 100 kg | $500.000 |
| 50 kg | $300.000 |

```text
costo_promedio = ($500.000 + $300.000) / (100 + 50)
costo_promedio = $5.333,33 por kg
```

Si la orden de cliente tiene un ingreso de `$1.200.000` y se ejecutaron `180 kg`:

```text
costo = 180 * $5.333,33 = $960.000
utilidad = $1.200.000 - $960.000 = $240.000
margen = $240.000 / $1.200.000 * 100 = 20%
```

## 6. Filtros

| Filtro | Comportamiento actual |
| --- | --- |
| Empresa | Filtra compras y ventas por la misma empresa. |
| Producto | Filtra por el ID exacto del producto. |
| Busqueda | Busca en nombre y descripcion del producto. |
| Fecha inicial/final | Filtra ventas por `created_at` del detalle de la orden. |

Importante: las fechas no filtran las facturas usadas para calcular el costo
promedio. El costo promedio siempre usa todas las compras historicas disponibles
para la empresa.

## 7. Revision y hallazgos

### Criticos

1. **El costo se calcula sin IVA.** El reporte usa
   `detalles_factura_compra.cantidad * precio_unitario`, evitando el campo
   `total` que puede contener impuestos del detalle.

2. **No se excluyen documentos anulados.** El reporte no filtra facturas de
   compra anuladas ni ordenes de venta anuladas/inactivas. Esos documentos
   pueden seguir afectando costo, ingreso y utilidad.

3. **Las unidades deben ser equivalentes.** El costo promedio divide el total de
   compra entre `cantidad`, pero el costo vendido se multiplica por
   `cantidad_ejecutada_kg`. El resultado solo es correcto si la cantidad comprada
   tambien representa kilogramos para ese producto.

### Altos

4. **El ingreso se calcula sin IVA, pero puede reconocer toda la linea antes de ejecutarla completamente.**
   Se suma `cantidad * valor_unitario`, aunque `cantidad_ejecutada_kg` sea
   parcial.
   Esto puede sobrestimar temporalmente la utilidad. Para una ejecucion parcial,
   el ingreso deberia prorratearse o provenir de una factura de venta.

5. **El filtro de fechas no define el periodo del costo.** Filtra por la fecha de
   creacion del detalle de venta, no por fecha de entrega, ejecucion o factura.
   El costo promedio sigue siendo historico.

6. **El costo no es historico por transaccion.** Si mañana se registra una compra
   mas costosa, cambia el costo promedio y tambien cambia retroactivamente la
   utilidad mostrada para ventas anteriores.

### Medios

7. **La exportacion incluye todos los productos filtrados.** El listado web se
   pagina, pero la exportacion ejecuta la consulta completa.

8. **El modulo es exclusivamente de consulta.** Solo expone las rutas de listado
   y exportacion porque el costeo se calcula a partir de otros movimientos.

9. **La consulta del resumen carga todos los resultados en memoria.** Puede
   volverse costosa cuando aumente el volumen de productos y transacciones.

## 8. Interpretacion correcta del reporte actual

El resultado debe interpretarse como:

> Margen bruto estimado por producto, calculado con costo promedio historico de
> compras y valores de ordenes de clientes con ejecucion de inventario.

No representa actualmente:

- Costo real historico de cada venta.
- Utilidad neta contable.
- Costos de produccion completos.
- Mano de obra, energia, transporte, gastos administrativos o financieros.
- Rentabilidad basada exclusivamente en facturas de venta.

## 9. Metodologia recomendada

Para obtener un costeo confiable:

1. Definir la unidad base de inventario de cada producto, por ejemplo kilogramos.
2. Guardar por separado subtotal, impuestos y gastos de cada compra.
3. Definir si el metodo sera promedio ponderado, FIFO o costo estandar.
4. Guardar el costo aplicado en cada movimiento de salida de inventario.
5. Reconocer el ingreso desde facturas de venta o prorratearlo segun la cantidad
   realmente ejecutada.
6. Excluir documentos anulados.
7. Usar fechas contables o fechas de movimiento, no solamente `created_at`.
8. Incorporar costos indirectos cuando se requiera utilidad operacional.

Con costo promedio ponderado, cada salida deberia conservar el costo vigente en
el momento de la salida. Asi, compras futuras no modifican la utilidad historica.

## 10. Flujo tecnico

### API

- Listado: `GET /api/costeos`
- Exportacion: `GET /api/costeos/export`

Parametros aceptados:

```text
producto_id
empresa_id
search
fecha_inicio
fecha_fin
page
```

### Archivos principales

- `Api_SIG/app/Services/contabilidad/CostoeService.php`
- `Api_SIG/app/Http/Controllers/contabilidad/CosteoController.php`
- `Api_SIG/app/Exports/CosteoUtilidadExport.php`
- `Frontebd_SIG/src/views/contabilidad/PageCosteos.jsx`
- `Frontebd_SIG/src/hooks/contabilidad/useGetCosteos.js`
- `Frontebd_SIG/src/hooks/contabilidad/useExportCosteos.js`

## 11. Pruebas recomendadas

Antes de usar el reporte para decisiones financieras, validar como minimo:

1. Una compra y una venta completa del mismo producto.
2. Dos compras con precios diferentes para confirmar el promedio ponderado.
3. Una ejecucion parcial de una orden.
4. Una factura de compra con impuestos.
5. Una factura y una orden anuladas.
6. Productos comprados en unidades diferentes a kilogramos.
7. Un reporte con mas de 50 productos y su exportacion.
8. Filtros de empresa y fechas comparados contra consultas directas de base de datos.
