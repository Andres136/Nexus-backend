# 🎨 PALETA DE COLORES SETASPLAST - GUÍA COMPLETA

## 🌿 **COLORES CORPORATIVOS PRINCIPALES**

### Verde Corporativo (Identidad de Marca)
```css
--verde-principal: #208040      /* Tu color corporativo principal */
--verde-claro: #27ae60          /* Para hover y elementos activos */
--verde-oscuro: #1a6b32         /* Para texto y bordes */
--verde-muy-claro: #d4edda      /* Fondos suaves */
--verde-transparente: rgba(32, 128, 64, 0.1)  /* Overlay suave */
```

### Crema/Beige (Complementario Cálido)  
```css
--crema-principal: #fff0db      /* Tu gradiente base */
--crema-claro: #fef7ed          /* Fondos muy suaves */
--crema-medio: #f4e4c1          /* Bordes suaves */
--crema-oscuro: #e6d3b7         /* Texto suave */
```

## ⚖️ **COLORES DE ESTADO Y FUNCIONALES**

### Estados de Alerta
```css
--rojo-urgente: #e74c3c         /* Tareas vencidas, errores críticos */
--rojo-claro: #f8d7da           /* Fondos de alerta */
--rojo-texto: #721c24           /* Texto de error */

--amarillo-atencion: #f39c12    /* Órdenes de trabajo, advertencias */
--amarillo-claro: #fff3cd       /* Fondos de advertencia */
--amarillo-texto: #856404       /* Texto de advertencia */

--azul-info: #3498db            /* Tareas nuevas, información */
--azul-claro: #cce5ff           /* Fondos informativos */
--azul-texto: #0056b3           /* Texto informativo */

--purpura-pqr: #9b59b6          /* PQRs, procesos especiales */
--purpura-claro: #f0e6ff        /* Fondos de PQR */
--purpura-texto: #6f42c1        /* Texto de PQR */
```

### Grises Profesionales
```css
--gris-texto: #2c3e50           /* Texto principal */
--gris-secundario: #7f8c8d      /* Texto secundario */
--gris-claro: #ecf0f1           /* Fondos neutros */
--gris-medio: #bdc3c7           /* Bordes suaves */
--gris-oscuro: #34495e          /* Footer, elementos oscuros */
```

## 🎯 **APLICACIÓN POR TIPO DE NOTIFICACIÓN**

### 🛒 Órdenes de Compra
- **Color Principal**: Verde corporativo (#208040)
- **Fondo de Tarjeta**: Crema claro (#fef7ed) 
- **Borde**: Verde principal (#208040)
- **Botón Principal**: Gradiente verde (#208040 → #27ae60)

### ⚠️ Tareas Vencidas/Urgentes  
- **Color Principal**: Rojo urgente (#e74c3c)
- **Fondo de Alerta**: Gradiente rojo (#e74c3c → #c0392b)
- **Fondo de Tarjeta**: Rojo claro (#f8d7da)
- **Botón de Acción**: Rojo con animación pulse

### ✅ Tareas Nuevas
- **Color Principal**: Azul info (#3498db) 
- **Fondo de Tarjeta**: Azul claro (#cce5ff)
- **Borde**: Azul principal (#3498db)
- **Botón Principal**: Gradiente azul (#3498db → #2980b9)

### 📋 PQRs
- **Color Principal**: Púrpura (#9b59b6)
- **Fondo de Tarjeta**: Púrpura claro (#f0e6ff)
- **Borde**: Púrpura principal (#9b59b6)  
- **Botón Principal**: Gradiente púrpura (#9b59b6 → #8e44ad)

### 🔧 Órdenes de Trabajo
- **Color Principal**: Amarillo/Naranja (#f39c12)
- **Fondo de Tarjeta**: Amarillo claro (#fff3cd)
- **Borde**: Naranja principal (#f39c12)
- **Botón Principal**: Gradiente naranja (#f39c12 → #e67e22)

## 📱 **GRADIENTES RECOMENDADOS**

### Header Principal
```css
background: linear-gradient(135deg, #fff0db 0%, #208040 100%);
```

### Botones por Tipo
```css
/* Verde - Órdenes normales */
background: linear-gradient(135deg, #208040 0%, #27ae60 100%);

/* Rojo - Urgente */  
background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);

/* Azul - Tareas */
background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);

/* Púrpura - PQRs */
background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);

/* Naranja - Trabajo */
background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
```

## 🏷️ **BADGES Y ETIQUETAS**

### Estados de Prioridad
```css
.priority-alta { background: #e74c3c; color: white; }
.priority-media { background: #f39c12; color: white; }  
.priority-baja { background: #208040; color: white; }
```

### Estados de PQR
```css
.pqr-peticion { background: #d4edda; color: #155724; }
.pqr-queja { background: #f8d7da; color: #721c24; }
.pqr-reclamo { background: #fff3cd; color: #856404; }
.pqr-consulta { background: #cce5ff; color: #0056b3; }
```

### Estados de Tareas
```css
.task-pendiente { background: #fff3cd; color: #856404; }
.task-proceso { background: #cce5ff; color: #0056b3; } 
.task-completada { background: #d4edda; color: #155724; }
.task-vencida { background: #f8d7da; color: #721c24; }
```

## 💡 **RECOMENDACIONES DE USO**

### ✅ **SÍ Usar**
- Verde corporativo para elementos principales de marca
- Crema como complemento cálido en fondos
- Colores de estado consistentes (rojo=urgente, azul=info, etc.)
- Gradientes suaves para profundidad
- Suficiente contraste para accesibilidad

### ❌ **NO Usar**
- Más de 3 colores principales por email
- Colores muy saturados que lastimen la vista
- Texto con poco contraste sobre fondos
- Demasiados gradientes en un mismo elemento

### 🎨 **Tips de Diseño**
1. **Jerarquía**: Verde para lo más importante, grises para secundario
2. **Consistencia**: Mismo color para mismo tipo en todos los emails
3. **Balance**: 60% neutro, 30% corporativo, 10% acento
4. **Accesibilidad**: Contraste mínimo 4.5:1 para texto normal

---

**Esta paleta garantiza coherencia visual y profesionalismo en todas tus notificaciones.** 🌟
