<?php
/**
*
* WebForm Arte Ideas Backend Plugin
* Description: Plugin que contiene todas las funcionalidades especificas del proyecto relacionado con el backend
* @version 4.6.7
* @author Piero Jimenez, Enmanuel Nava
*/

// FUNCION PARA CREAR LAS TABLAS
function generador_tablas()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Definir los nombres de las tablas con prefijo de WordPress
    $marcos = $wpdb->prefix . 'product_frames';
    $dimensiones = $wpdb->prefix . 'product_dimensions';
    $precio_dimensiones = $wpdb->prefix . 'product_price_dimensions';

    // SQL para la tabla Marcos(frames)
    $sql1 = "CREATE TABLE IF NOT EXISTS $marcos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        frame_type VARCHAR(50) NOT NULL,
        frame_img TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        product_id BIGINT NOT NULL
    ) $charset_collate;";

    // SQL para la tabla Dimensiones(dimensions)
    $sql2 = "CREATE TABLE IF NOT EXISTS $dimensiones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        width INT NOT NULL,
        height INT NOT NULL,
        unit VARCHAR(10) NOT NULL DEFAULT 'cm',
        is_custom BOOLEAN NOT NULL DEFAULT 0,
        custom_variation_id BIGINT DEFAULT NULL
    ) $charset_collate;";

    // SQL para la tercera tabla
    $sql3 = "CREATE TABLE IF NOT EXISTS $precio_dimensiones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        dimension_id INT NOT NULL,
        product_id BIGINT NOT NULL,
        variation_id BIGINT NOT NULL,
        is_custom BOOLEAN NOT NULL DEFAULT 0
    ) $charset_collate;";

    // Incluir el archivo necesario para ejecutar dbDelta
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Ejecutar la creación de tablas
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);

    //Insertar los datos a las tabla al momneto de crearse y activar el plugin
    /*$wpdb->query("INSERT IGNORE INTO $dimensiones (width, height) VALUES
        (10,15), (13,18), (15,21), (20,25), (20,30), (25,38), (30,45), (30,60)");*/


    $wpdb->query("INSERT IGNORE INTO $marcos (frame_type, frame_img, price, product_id) VALUES
        
        ('marco1', 'marco1.png', 10.00, 0),
        ('marco2', 'marco2.png', 12.20, 0),
        ('marco3', 'marco3.png', 8.50, 0),
        ('marco4', 'marco4.png', 15.10, 0) ");
}

function eliminar_tablas()
{
    global $wpdb;
    $marcos = $wpdb->prefix . 'product_frames';
    $dimensiones = $wpdb->prefix . 'product_dimensions';
    $precio_dimensiones = $wpdb->prefix . 'product_price_dimensions';

    $wpdb->query("DROP TABLE IF EXISTS $dimensiones;");
    $wpdb->query("DROP TABLE IF EXISTS $marcos;");
    $wpdb->query("DROP TABLE IF EXISTS $precio_dimensiones;");
}

// FUNCION PARA GUARDAR LOS DATOS DEL FORMULARIO DE PRODUCTOS(SI COMIENZA CON MARCO) A LA TABLA MARCO
function guardar_datos_marcos($product_id)
{

    $product = wc_get_product($product_id);
    if (!$product)
        return;

    $nombre = $product->get_name();
    $precio = $product->get_price();

    if (stripos($nombre, 'Marco') !== 0)
        return;

    // Imagen
    $imagen_id = $product->get_image_id();
    $imagen_url = wp_get_attachment_url($imagen_id);
    $imagen = basename($imagen_url);

    $destino_plugin = WP_PLUGIN_DIR . '/web-form-plugin/assets/frames/' . $imagen;

    if ($imagen_url && !file_exists($destino_plugin)) {
        $contenido_imagen = file_get_contents($imagen_url);
        if ($contenido_imagen) {
            file_put_contents($destino_plugin, $contenido_imagen);
        }
    }

    global $wpdb;
    $tabla = $wpdb->prefix . 'product_frames';

    $existe = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tabla WHERE product_id = %d", $product_id));

    if ($existe) {
        $wpdb->update($tabla, [
            'frame_type' => $nombre,
            'frame_img' => $imagen,
            'price' => $precio
        ], [
            'product_id' => $product_id
        ]);
    } else {
        $wpdb->insert($tabla, [
            'frame_type' => $nombre,
            'frame_img' => $imagen,
            'price' => $precio,
            'product_id' => $product_id
        ]);
    }

}

// FUNCION PARA ELIMINAR LOS MARCOS SI SE DESECHAN DESDE LA TABLA PRODUCTOS
function eliminar_marco($post_id)
{
    if (get_post_type($post_id) !== 'product')
        return;

    global $wpdb;
    $tabla = $wpdb->prefix . 'product_frames';

    $wpdb->delete($tabla, ['product_id' => $post_id]);
}

// FUNCIÓN PARA GUARDAR LOS DATOS DE LAS VARIACIONES PERSONALIZADAS EN LA BD
function guardar_datos_variaciones_personalizados($product)
{
    global $wpdb;

    if (!function_exists('wc_get_product'))
        return;
    if ($product->get_type() !== 'variation')
        return;

    $variation_id = $product->get_id();
    $product_id = $product->get_parent_id();
    if (!$product_id)
        return;

    error_log("Procesando variación ID: $variation_id del producto padre: $product_id");

    $tabla_dimensiones = $wpdb->prefix . 'product_dimensions';
    $tabla_precios = $wpdb->prefix . 'product_price_dimensions';

    $attributes = $product->get_attributes();
    $precio = $product->get_price();

    $width = null;
    $height = null;
    $is_custom = false;

    // Extraer dimensiones
    foreach ($attributes as $key => $value) {
        if (is_object($value) && method_exists($value, 'get_name')) {
            $value = $value->get_name();
        }

        error_log("Atributo $key => $value");

        if (stripos($key, 'Dimensiones') !== false) {
            if ($value == 'personalizado') {
                $is_custom = true;
                $width = 0;
                $height = 0;

            } else if (preg_match('/(\d+)\s*[xX]\s*(\d+)/', $value, $matches)) {
                $width = $matches[1];
                $height = $matches[2];
            }
        }
    }

    if ($is_custom) {
        // Verificar si ya existe una dimensión personalizada para esta variación
        $id_dimension = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_dimensiones WHERE is_custom = 1 AND custom_variation_id = %d",
            
            $variation_id
        ));

        if (!$id_dimension) {
            // Crear dimensión personalizada asociada a esta variación
            $wpdb->insert($tabla_dimensiones, [
                'width' => 0,
                'height' => 0,
                'unit' => 'cm',
                'is_custom' => 1,
                'custom_variation_id' => $variation_id
            ]);
            $id_dimension = $wpdb->insert_id;
        } else {
            // Actualizar las dimensiones existentes si han cambiado
            $wpdb->update($tabla_dimensiones, [
                'width' => $width,
                'height' => $height
            ], [
                'id' => $id_dimension
            ]);
        }

        // Insertar o actualizar el precio personalizado
        $wpdb->replace($tabla_precios, [
            'price' => $precio,
            'dimension_id' => $id_dimension,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'is_custom' => 1
        ]);

    } else {
        if (!$width || !$height || !$precio) {
            error_log("Datos incompletos: width=$width, height=$height, precio=$precio");
            return;
        }

        $id_dimension = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_dimensiones WHERE width = %d AND height = %d AND is_custom = 0",
            
            $width, $height
        ));

        if (!$id_dimension) {
            $wpdb->insert($tabla_dimensiones, [
                'width' => $width,
                'height' => $height,
                'unit' => 'cm',
                'is_custom' => 0
            ]);
            $id_dimension = $wpdb->insert_id;
        }

        $wpdb->replace($tabla_precios, [
            'price' => $precio,
            'dimension_id' => $id_dimension,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'is_custom' => 0
        ]);
    }
}

// FUNCION ELIMINAR VARIACION DE PRECIO
function eliminar_precios_personalizados_variacion($post_id)
{
    global $wpdb;

    // Asegurarse de que sea una variación
    if (get_post_type($post_id) !== 'product_variation') {
        return;
    }

    $tabla_precios = $wpdb->prefix . 'product_price_dimensions';

    // Obtener el objeto del producto
    $product = wc_get_product($post_id);
    if (!$product)
        return;

    // Obtener atributos
    $attributes = $product->get_attributes();
    $product_id = $product->get_parent_id();

    $width = null;
    $height = null;

    foreach ($attributes as $key => $value) {
        if (stripos($key, 'Dimensiones') !== false && preg_match('/(\d+)\s*[xX]\s*(\d+)/', $value, $matches)) {
            $width = $matches[1];
            $height = $matches[2];
        }
    }

    if (!$width || !$height)
        return;

    // Obtener ID de la dimensión
    $id_dimension = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}product_dimensions WHERE width = %d AND height = %d",
        $width,
        $height
    ));

    if ($id_dimension) {
        $wpdb->delete($tabla_precios, [
            'product_id' => $product_id,
            'dimension_id' => $id_dimension,
        ]);
    }
}

// FUNCIONES PARA OBTENER DATOS DE LA BD -----------------------------------

// Dimensiones
function obtenerDimensionesBD()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'product_dimensions';
    return $wpdb->get_results("SELECT * FROM $tabla", ARRAY_A) ?: [];
}

// Marcos
function obtenerMarcosBD()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'product_frames';
    return $wpdb->get_results("SELECT * FROM $tabla", ARRAY_A) ?: [];
}

// Precios
function obtenerPreciosDimensionBD()
{
    global $wpdb;
    $tabla = $wpdb->prefix . 'product_price_dimensions';
    return $wpdb->get_results("SELECT * FROM $tabla ", ARRAY_A) ?: [];
}

// ENDPOINT PERSONALIZADO --------------------------------------------------

// Dimensiones
add_action('rest_api_init', function () {
    register_rest_route('api/v1', '/dimensiones', [
        'methods' => 'GET',
        'callback' => 'obtener_dimensiones',
        'permission_callback' => '__return_true'
    ]);
});

// Marcos
add_action('rest_api_init', function () {
    register_rest_route('api/v1', '/marcos', [
        'methods' => 'GET',
        'callback' => 'obtener_marcos',
        'permission_callback' => '__return_true'
    ]);
});

// FUNCIONES PARA DEVOLVER LOS DATOS EN EL API

// Dimensiones
function obtener_dimensiones(WP_REST_Request $request)
{
    $dimensiones = obtenerDimensionesBD();

    $dimensiones = array_map(fn($d) => [
        'id' => sanitize_text_field($d['id']),
        'width' => sanitize_text_field($d['width']),
        'height' => sanitize_text_field($d['height']),
        'unit' => sanitize_text_field($d['unit']),
        'is_custom' => isset($d['is_custom']) ? (bool) $d['is_custom'] : false,
        'custom_variation_id' => isset($d['custom_variation_id']) ? $d['custom_variation_id'] : null,
    ], $dimensiones);

    return new WP_REST_Response([
        'global_unique_id' => uniqid(),
        'custom_dimensions_data' => $dimensiones
    ], 200);
}

// Marcos
function obtener_marcos(WP_REST_Request $request)
{
    $marcos = obtenerMarcosBD();

    $marcos = array_map(fn($m) => [
        'id' => sanitize_text_field($m['id']),
        'frame_type' => sanitize_text_field($m['frame_type']),
        'frame_img' => sanitize_text_field($m['frame_img']),
        'price' => sanitize_text_field($m['price']),
        'product_id' => sanitize_text_field($m['product_id'])
    ], $marcos);

    return new WP_REST_Response([
        'global_unique_id' => uniqid(),
        'frames_data' => $marcos
    ], 200);
}

// Precio
// 1. Registrar el nuevo endpoint para precios base
add_action('rest_api_init', function () {
    register_rest_route('api/v1', '/dimensiones_precio_base', [
        'methods' => 'GET',
        'callback' => 'obtener_precios_base_dimension',
        'permission_callback' => '__return_true'
    ]);
});

// 2. Función para obtener precios base (sin lógica de cantidades)
function obtener_precios_base_dimension(WP_REST_Request $request)
{
    global $wpdb;

    $tabla_precios = $wpdb->prefix . 'product_price_dimensions';
    $tabla_dimensiones = $wpdb->prefix . 'product_dimensions';

    // Consulta optimizada que une ambas tablas
    $precios = $wpdb->get_results("
        SELECT 
            pd.id,
            pd.price,
            pd.dimension_id,
            pd.variation_id,
            d.width,
            d.height
        FROM $tabla_precios pd
        JOIN $tabla_dimensiones d ON pd.dimension_id = d.id
        WHERE pd.is_custom = 0
    ", ARRAY_A) ?: [];

    // Formatear respuesta
    $precios_formateados = array_map(function ($p) {
        return [
            'id' => $p['id'],
            'price' => $p['price'],
            'dimension_id' => $p['dimension_id'],
            'variation_id' => $p['variation_id'],
            'dimension' => $p['width'] . 'X' . $p['height']
        ];
    }, $precios);

    return new WP_REST_Response([
        'global_unique_id' => uniqid(),
        'price_data' => $precios_formateados
    ], 200);
}
