# Web Form Plugin para WordPress

## Índice
1. [Descripción](#descripción)
2. [Características](#características)
3. [Requisitos](#requisitos)
4. [Instalación](#instalación)
5. [Estructura del Plugin](#estructura-del-plugin)
6. [Uso](#uso)
7. [Documentación Técnica](#documentación-técnica)
8. [Solución de Problemas](#solución-de-problemas)
9. [Información Adicional](#información-adicional)

## Descripción
El Web Form Plugin es una solución completa para integrar un formulario personalizado de impresión de imágenes en WordPress con WooCommerce. Permite a los usuarios subir imágenes, seleccionar tamaños, marcos y personalizar sus impresiones antes de añadirlas al carrito de compras.

## Características
- Integración completa con WooCommerce
- Carga múltiple de imágenes
- Selección de tamaños predefinidos y personalizados
- Opciones de marcos con precios adicionales
- Vista previa de imágenes
- Carrito de compras personalizado
- Sistema de notificaciones personalizado
- Redirecciones inteligentes para usuarios no autenticados

## Requisitos
- WordPress 5.0 o superior
- WooCommerce 3.0 o superior
- PHP 7.2 o superior
- Memoria PHP recomendada: 128M
- Tiempo máximo de ejecución: 300 segundos
- Límite de subida de archivos: 32M

## Instalación
1. Sube el plugin a la carpeta `/wp-content/plugins/` de tu instalación de WordPress
2. Activa el plugin desde el panel de administración de WordPress
3. Configura el producto base para impresiones en WooCommerce > Impresión de Imágenes
4. Asegúrate de que WooCommerce esté correctamente configurado

## Estructura del Plugin

### Archivos Principales
- `web-form-plugin.php`: Archivo principal del plugin que contiene toda la lógica de integración con WordPress y WooCommerce
  - Manejo de shortcodes
  - Integración con WooCommerce
  - Procesamiento de formularios
  - Gestión de carrito
  - Configuración del panel de administración

### Directorio de Assets

#### JavaScript (`assets/js/`)
- `main.js`:
  - Lógica principal del formulario
  - Manejo de eventos de usuario
  - Validación de formularios
  - Procesamiento de imágenes
  - Integración con el carrito
  
  **Descripción detallada:**
  Este es el archivo principal que coordina toda la funcionalidad del formulario. Maneja la inicialización de componentes, la carga de imágenes, la validación de datos y la comunicación con el backend. Implementa un sistema de estados para controlar el flujo del formulario y gestiona las interacciones del usuario con los diferentes elementos de la interfaz.

- `cart-handler.js`:
  - Actualización del mini carrito
  - Manejo de eventos del carrito
  - Cálculo de precios en tiempo real
  - Actualización de fragmentos de WooCommerce
  
  **Descripción detallada:**
  Gestiona todas las interacciones relacionadas con el carrito de compras. Implementa un sistema de actualización en tiempo real que mantiene sincronizado el mini carrito con el carrito principal de WooCommerce. Maneja los cálculos de precios, incluyendo descuentos y costos adicionales, y actualiza la interfaz de usuario de manera dinámica.

- `custom-notifications.js`:
  - Sistema de notificaciones personalizado
  - Mensajes de éxito/error
  - Notificaciones de carga
  - Animaciones de notificación
  
  **Descripción detallada:**
  Implementa un sistema de notificaciones personalizado que muestra mensajes al usuario de forma elegante y no intrusiva. Utiliza un sistema de colas para manejar múltiples notificaciones y asegura que se muestren en el orden correcto. Incluye animaciones suaves y diferentes estilos para distintos tipos de mensajes (éxito, error, advertencia, información).

- `events.js`:
  - Gestión de eventos del formulario
  - Validación de campos
  - Manejo de interacciones del usuario
  - Eventos personalizados
  
  **Descripción detallada:**
  Centraliza la gestión de eventos del formulario. Implementa un sistema de validación en tiempo real para los campos del formulario y maneja las interacciones del usuario con los diferentes elementos. Utiliza un patrón de eventos personalizados para mantener un código limpio y desacoplado.

- `ios-picker.js`:
  - Selector estilo iOS para opciones
  - Animaciones suaves
  - Soporte táctil
  - Personalización de estilos
  
  **Descripción detallada:**
  Implementa un selector de opciones con estilo iOS que proporciona una experiencia de usuario móvil mejorada. Incluye animaciones suaves para las transiciones, soporte completo para gestos táctiles y es altamente personalizable. Se integra perfectamente con el diseño general del formulario.

- `main-menu-redirection.js`:
  - Redirecciones del menú principal
  - Manejo de URLs personalizadas
  - Integración con el sistema de navegación
  
  **Descripción detallada:**
  Gestiona las redirecciones del menú principal y la navegación del sitio. Implementa un sistema inteligente de redirección que mantiene el estado del usuario y asegura una navegación fluida entre las diferentes secciones del sitio.

- `mini-cart-style-script.js`:
  - Estilos del mini carrito
  - Animaciones de actualización
  - Diseño responsive
  
  **Descripción detallada:**
  Maneja los estilos y animaciones del mini carrito. Implementa un diseño responsive que se adapta a diferentes tamaños de pantalla y proporciona animaciones suaves para las actualizaciones del carrito. Se integra con el sistema de temas de WordPress.

- `picker-placeholder.js`:
  - Manejo de placeholders en selectores
  - Validación de selecciones
  - Estilos personalizados
  
  **Descripción detallada:**
  Gestiona los placeholders y la validación de los selectores del formulario. Implementa un sistema de validación visual que proporciona feedback inmediato al usuario y asegura que las selecciones sean válidas antes de enviar el formulario.

- `requestHttp.js`:
  - Gestión de peticiones AJAX
  - Manejo de errores
  - Procesamiento de respuestas
  - Integración con WordPress
  
  **Descripción detallada:**
  Centraliza todas las peticiones HTTP y AJAX del formulario. Implementa un sistema robusto de manejo de errores y reintentos, y procesa las respuestas del servidor de manera eficiente. Se integra con el sistema de nonces de WordPress para garantizar la seguridad.

#### CSS (`assets/css/`)
- `styles.css`: Estilos principales del formulario
- `cart-page-style.css`: Estilos para la página del carrito
- `checkout-page-style.css`: Estilos para la página de checkout
- `notifications-loading-styles.css`: Estilos para notificaciones y estados de carga
- `order-details-style.css`: Estilos para detalles de pedidos
- `placeholder-loader.css`: Estilos para placeholders y estados de carga

#### Frames (`assets/frames/`)
- Imágenes de marcos disponibles
- Recursos visuales para previsualizaciones
- Assets para la interfaz de usuario

## Uso

### Shortcode
Para mostrar el formulario en cualquier página o post, utiliza el shortcode:
```
[web_form]
```

### Configuración
1. Ve a WooCommerce > Impresión de Imágenes
2. Selecciona el producto base que se utilizará para las impresiones
3. Guarda la configuración

### Personalización del Formulario
El formulario se puede personalizar mediante:
- Filtros de WordPress
- Acciones de WooCommerce
- CSS personalizado
- JavaScript personalizado

## Documentación Técnica

### Hooks y Filtros

#### Filtros Disponibles
```php
// Modificar el precio del producto
add_filter('woocommerce_product_get_price', 'custom_price_calculation', 10, 2);

// Personalizar datos del carrito
add_filter('woocommerce_add_cart_item_data', 'custom_cart_item_data', 10, 3);

// Modificar la visualización del carrito
add_filter('woocommerce_cart_item_name', 'custom_cart_item_name', 10, 3);
```

#### Acciones Disponibles
```php
// Antes de procesar el pedido
add_action('woocommerce_before_checkout_process', 'custom_before_checkout');

// Después de añadir al carrito
add_action('woocommerce_add_to_cart', 'custom_after_add_to_cart', 10, 6);
```

## Solución de Problemas

### Problemas Comunes
1. Error de carga de imágenes:
   - Verificar límites de PHP
   - Comprobar permisos de directorio
   - Validar formato de archivo

2. Problemas con el carrito:
   - Limpiar caché del navegador
   - Verificar sesión de WooCommerce
   - Comprobar configuración de cookies

3. Errores de JavaScript:
   - Verificar consola del navegador
   - Comprobar conflictos con otros plugins
   - Validar versiones de jQuery

## Información Adicional
- Versión actual: 3.5.5
- Última actualización: [05/06/2025]
- Autor: Enmanuel

## Documentación de Archivos JavaScript

### main.js
**Descripción General:**
El archivo `main.js` es el núcleo del plugin, encargado de coordinar todas las funcionalidades del formulario de impresión de imágenes. Este archivo maneja la lógica principal de la aplicación y actúa como el punto de entrada para todas las interacciones del usuario.

**Funcionalidades Principales:**
- Inicialización y configuración de componentes
- Gestión de imágenes y previsualizaciones
- Interacción con el usuario
- Integración con WooCommerce
- Comunicación con el backend

### cart-handler.js
**Descripción General:**
El archivo `cart-handler.js` es responsable de toda la lógica relacionada con el carrito de compras. Implementa un sistema de actualización en tiempo real que mantiene sincronizado el mini carrito con el carrito principal de WooCommerce.

**Funcionalidades Principales:**
- Actualización dinámica del mini carrito
- Cálculo de precios en tiempo real
- Gestión de fragmentos de WooCommerce
- Manejo de eventos del carrito
- Actualización de la interfaz de usuario

### custom-notifications.js
**Descripción General:**
El archivo `custom-notifications.js` implementa un sistema de notificaciones personalizado y elegante que proporciona feedback al usuario de manera no intrusiva.

**Funcionalidades Principales:**
- Sistema de colas para notificaciones
- Diferentes tipos de mensajes (éxito, error, advertencia)
- Animaciones suaves
- Gestión de tiempo de visualización
- Personalización de estilos

### events.js
**Descripción General:**
El archivo `events.js` centraliza la gestión de eventos del formulario, implementando un sistema robusto de validación y manejo de interacciones del usuario.

**Funcionalidades Principales:**
- Validación en tiempo real de campos
- Manejo de eventos personalizados
- Gestión de interacciones del usuario
- Sistema de validación de formularios
- Patrón de eventos desacoplado

### ios-picker.js
**Descripción General:**
El archivo `ios-picker.js` implementa un selector de opciones con estilo iOS que mejora significativamente la experiencia de usuario en dispositivos móviles.

**Funcionalidades Principales:**
- Selector estilo iOS nativo
- Soporte completo para gestos táctiles
- Animaciones suaves
- Personalización de estilos
- Integración con el diseño general

### main-menu-redirection.js
**Descripción General:**
El archivo `main-menu-redirection.js` gestiona las redirecciones del menú principal y la navegación del sitio, asegurando una experiencia de usuario fluida.

**Funcionalidades Principales:**
- Sistema inteligente de redirección
- Manejo de URLs personalizadas
- Integración con la navegación
- Mantenimiento del estado del usuario
- Gestión de rutas

### mini-cart-style-script.js
**Descripción General:**
El archivo `mini-cart-style-script.js` maneja los estilos y animaciones del mini carrito, proporcionando una experiencia visual agradable y responsive.

**Funcionalidades Principales:**
- Estilos del mini carrito
- Animaciones de actualización
- Diseño responsive
- Integración con temas
- Personalización visual

### picker-placeholder.js
**Descripción General:**
El archivo `picker-placeholder.js` gestiona los placeholders y la validación de los selectores del formulario, mejorando la experiencia de usuario.

**Funcionalidades Principales:**
- Manejo de placeholders
- Validación visual de selecciones
- Estilos personalizados
- Feedback inmediato al usuario
- Integración con formularios

### requestHttp.js
**Descripción General:**
El archivo `requestHttp.js` centraliza todas las peticiones HTTP y AJAX del formulario, implementando un sistema robusto de manejo de errores y respuestas.

**Funcionalidades Principales:**
- Gestión de peticiones AJAX
- Manejo de errores robusto
- Sistema de reintentos
- Procesamiento de respuestas
- Integración con WordPress

## Archivo Principal: main.js

### Descripción General
El archivo `main.js` es el núcleo del plugin, encargado de coordinar todas las funcionalidades del formulario de impresión de imágenes. Este archivo maneja la lógica principal de la aplicación y actúa como el punto de entrada para todas las interacciones del usuario.

**Funcionalidades Principales:**

#### 1. Inicialización y Configuración
- Inicialización de componentes del formulario
- Configuración de eventos y listeners
- Carga de dependencias necesarias
- Verificación de compatibilidad del navegador

#### 2. Gestión de Imágenes
- Carga y validación de imágenes
- Previsualización de imágenes
- Optimización de imágenes antes de subir
- Manejo de múltiples archivos
- Validación de formatos y tamaños

#### 3. Interacción con el Usuario
- Manejo de eventos de arrastrar y soltar
- Selección de tamaños y marcos
- Validación en tiempo real de formularios
- Feedback visual para acciones del usuario
- Manejo de errores y mensajes

#### 4. Integración con WooCommerce
- Comunicación con el carrito de compras
- Actualización de precios en tiempo real
- Manejo de variaciones de productos
- Procesamiento de pedidos

#### 5. Comunicación con el Backend
- Envío de datos mediante AJAX
- Manejo de respuestas del servidor
- Gestión de errores de red
- Actualización de la interfaz según respuestas

### Estructura del Código
```javascript
// Ejemplo de estructura básica
class WebForm {
    constructor() {
        this.initializeComponents();
        this.setupEventListeners();
        this.setupWooCommerceIntegration();
    }

    initializeComponents() {
        // Inicialización de componentes
    }

    setupEventListeners() {
        // Configuración de eventos
    }

    setupWooCommerceIntegration() {
        // Integración con WooCommerce
    }
}
```

### Dependencias
- jQuery
- WooCommerce
- Swiper.js (para galerías)
- Viewer.js (para previsualización de imágenes)

### Consideraciones Técnicas
- Compatible con navegadores modernos
- Optimizado para dispositivos móviles
- Manejo de memoria eficiente
- Sistema de caché para mejor rendimiento

### Ejemplos de Uso
```javascript
// Inicialización básica
const webForm = new WebForm();

// Manejo de eventos
webForm.on('imageUploaded', (image) => {
    // Procesar imagen subida
});

// Actualización de precios
webForm.updatePrice({
    size: '10x15',
    frame: 'classic',
    quantity: 2
});
```

### Mejores Prácticas
1. Siempre validar datos antes de enviar
2. Implementar manejo de errores robusto
3. Optimizar imágenes antes de subir
4. Mantener el estado del formulario
5. Proporcionar feedback al usuario

### Solución de Problemas Comunes
1. Errores de carga de imágenes
   - Verificar formato y tamaño
   - Comprobar permisos de archivo
   - Validar conexión de red

2. Problemas de integración con WooCommerce
   - Verificar versión de WooCommerce
   - Comprobar configuración del carrito
   - Validar sesión de usuario

3. Errores de JavaScript
   - Revisar consola del navegador
   - Verificar dependencias
   - Comprobar compatibilidad 

## Documentación de Archivos CSS

### styles.css
**Descripción General:**
El archivo `styles.css` contiene los estilos principales del formulario, definiendo la apariencia y el comportamiento visual de todos los componentes.

**Características Principales:**
- Estilos base del formulario
- Diseño responsive
- Temas y variantes
- Animaciones y transiciones
- Soporte para temas oscuros/claros

### cart-page-style.css
**Descripción General:**
El archivo `cart-page-style.css` maneja los estilos específicos de la página del carrito, asegurando una experiencia de usuario consistente.

**Características Principales:**
- Diseño del carrito
- Estilos de productos
- Animaciones de actualización
- Diseño responsive
- Integración con temas

### checkout-page-style.css
**Descripción General:**
El archivo `checkout-page-style.css` define los estilos de la página de checkout, optimizando el proceso de pago.

**Características Principales:**
- Diseño del checkout
- Estilos de formularios
- Indicadores de progreso
- Validación visual
- Diseño responsive

### notifications-loading-styles.css
**Descripción General:**
El archivo `notifications-loading-styles.css` maneja los estilos de las notificaciones y estados de carga.

**Características Principales:**
- Estilos de notificaciones
- Indicadores de carga
- Animaciones de transición
- Estados de error/éxito
- Personalización de mensajes

### order-details-style.css
**Descripción General:**
El archivo `order-details-style.css` define los estilos para la visualización de detalles de pedidos.

**Características Principales:**
- Diseño de detalles de pedido
- Estilos de miniaturas
- Información de producto
- Estados de pedido
- Diseño responsive

### placeholder-loader.css
**Descripción General:**
El archivo `placeholder-loader.css` maneja los estilos de los placeholders y estados de carga.

**Características Principales:**
- Estilos de placeholders
- Animaciones de carga
- Estados de transición
- Diseño responsive
- Personalización de estilos

## Seguridad

### Medidas de Seguridad Implementadas
1. **Validación de Datos**
   - Sanitización de entradas
   - Validación de tipos de archivo
   - Comprobación de tamaños
   - Verificación de formatos

2. **Protección contra Ataques**
   - Nonces de WordPress
   - Validación de permisos
   - Protección CSRF
   - Sanitización de URLs

3. **Manejo de Archivos**
   - Validación de tipos MIME
   - Límites de tamaño
   - Escaneo de malware
   - Almacenamiento seguro

4. **Autenticación y Autorización**
   - Verificación de roles
   - Control de acceso
   - Sesiones seguras
   - Tokens de autenticación

### Buenas Prácticas
1. Mantener actualizado el plugin
2. Usar HTTPS
3. Implementar rate limiting
4. Realizar backups regulares
5. Monitorear logs de seguridad

## Personalización Avanzada

### Hooks y Filtros Personalizados
```php
// Personalizar el procesamiento de imágenes
add_filter('web_form_process_image', 'custom_image_processing', 10, 2);

// Modificar el cálculo de precios
add_filter('web_form_calculate_price', 'custom_price_calculation', 10, 3);

// Personalizar la validación de formularios
add_filter('web_form_validate_field', 'custom_field_validation', 10, 2);
```

### Personalización de Estilos
```css
/* Personalizar el contenedor principal */
.web-form-container {
    --primary-color: #your-color;
    --secondary-color: #your-color;
    --font-family: your-font;
}

/* Personalizar componentes específicos */
.web-form-input {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
```

### Personalización de JavaScript
```javascript
// Extender funcionalidad del formulario
WebForm.prototype.customMethod = function() {
    // Tu código personalizado
};

// Agregar eventos personalizados
document.addEventListener('webForm:customEvent', function(e) {
    // Manejar evento personalizado
});
```

## Uso del Shortcode

### Shortcode Básico
```
[web_form]
```

### Shortcode con Opciones
```
[web_form 
    product_id="123" 
    show_preview="true" 
    max_files="5" 
    allowed_types="jpg,png"
]
```

### Parámetros Disponibles
| Parámetro     | Descripción                 | Valores          | Default     |
|---------------|-----------------------------|------------------|-------------|
| product_id    | ID del producto base        | número           | configurado |
| show_preview  | Mostrar previsualización    | true/false       | true        |
| max_files     | Máximo de archivos          | número           | 10          |
| allowed_types | Tipos de archivo permitidos | jpg,png,gif      | todos       |
| theme         | Tema visual | light/dark    | light            |             |
| layout        | Diseño del formulario       | standard/compact | standard    |

### Ejemplos de Uso
```php
// Shortcode con tema oscuro
[web_form theme="dark"]

// Shortcode con límite de archivos
[web_form max_files="3" allowed_types="jpg,png"]

// Shortcode con diseño compacto
[web_form layout="compact" show_preview="false"]
```

### Integración con Temas
```php
// En tu archivo de tema
echo do_shortcode('[web_form]');

// Con parámetros personalizados
echo do_shortcode('[web_form theme="dark" layout="compact"]');
```
