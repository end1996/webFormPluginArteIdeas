<?php
/*
Plugin Name: Web Form Plugin
Description: Un plugin para integrar mi aplicación HTML, CSS y JS en WordPress con WooCommerce.
Version: 3.0.6
Author: Enmanuel
*/

// Verifica que WooCommerce esté activo
function check_woocommerce_active()
{
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>El plugin Web Form requiere que WooCommerce esté instalado y activado.</p></div>';
        });
        return false;
    }
    return true;
}

// Obtiene la URL de los assets dentro de frames
function get_asset_url($asset_name)
{
    return plugin_dir_url(__FILE__) . 'assets/frames/' . $asset_name;
}

// Función para el shortcode que muestra el formulario
function web_form_shortcode()
{
    if (!check_woocommerce_active()) {
        return '<p>Este formulario requiere WooCommerce para funcionar correctamente.</p>';
    }

    // Ruta de a los archivos de la aplicación
    $css_path = plugin_dir_url(__FILE__) . 'styles.css';
    $js_path = plugin_dir_url(__FILE__) . 'main.js';
    $picker_path = plugin_dir_url(__FILE__) . 'ios-picker.js';

    // Cargar CSS
    wp_enqueue_style('web-form-styles', $css_path, array(), '5.1.5');

    // Cargar Swiper CSS desde CDN
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');

    // Cargar Viewer CSS desde CDN
    wp_enqueue_style('viewer-css', 'https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.css', array(), '1.11.7');

    // Cargar IOS-Picker
    wp_enqueue_script('picker-script-js', $picker_path, array('jquery'), '2.0.1', true);

    // Cargar JS
    wp_enqueue_script('script-js', $js_path, array('jquery'), '5.6', true);

    // Cargar Swiper JS desde CDN (depende de jQuery)
    wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array('jquery'), '11.0.0', true);

    // Cargar Viewer JS desde CDN
    wp_enqueue_script('viewer-js', 'https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.min.js', array('jquery'), '1.11.7', true);

    // Aumentar límites para POST y uploads
    @ini_set('post_max_size', '64M');
    @ini_set('upload_max_filesize', '32M');
    @ini_set('max_execution_time', '300');
    @ini_set('max_input_time', '300');

    // Añadir variable para AJAX
    wp_localize_script('script-js', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('web_form_nonce')
    ));

    // Incluir el archivo HTML
    ob_start(); // Inicia el almacenamiento en búfer de salida
    include plugin_dir_path(__FILE__) . 'index.php';
    return ob_get_clean();
}

add_shortcode('web_form', 'web_form_shortcode'); // Registrar un shortcode

// Función para procesar los datos del formulario y añadir al carrito de WooCommerce
function process_print_image_to_cart()
{
    check_ajax_referer('web_form_nonce', 'nonce');

    // Verificamos si tenemos una imagen
    if (empty($_FILES['image_data'])) {
        error_log('Error: No se ha recibido ningún archivo de imagen');
        wp_send_json_error('No se ha subido ninguna imagen');
        return;
    }

    // Información de debug
    error_log('🔍 Procesando imagen: ' . print_r($_FILES['image_data'], true));

    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0.0;
    $comments = isset($_POST['comments']) ? sanitize_textarea_field($_POST['comments']) : '';
    $frame = isset($_POST['frame']) ? sanitize_text_field($_POST['frame']) : 'sin-marco';
    $custom_width = isset($_POST['custom_width']) ? intval($_POST['custom_width']) : 0;
    $custom_height = isset($_POST['custom_height']) ? intval($_POST['custom_height']) : 0;
    $is_custom_size = isset($_POST['is_custom_size']) ? filter_var($_POST['is_custom_size'], FILTER_VALIDATE_BOOLEAN) : false;
    $image_index = isset($_POST['image_index']) ? intval($_POST['image_index']) : 0;

    error_log('📊 Datos recibidos: Tamaño=' . $size . ', Marco=' . $frame . ', Índice=' . $image_index);

    // Obtener el ID del producto base
    $product_id = get_option('print_image_product_id', 159);
    if (!$product_id || !wc_get_product($product_id)) {
        error_log('Error: No se ha configurado un producto base para impresiones');
        wp_send_json_error('No se ha configurado un producto base para impresiones');
        return;
    }

    // Configuración para guardar la imagen
    $upload_dir = wp_upload_dir();
    $image_url = '';

    // Procesar el archivo subido
    $file = $_FILES['image_data'];

    // Verificar si hay errores en la subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log('❌ Error en la subida: ' . $file['error']);
        wp_send_json_error('Error al subir la imagen: ' . $file['error']);
        return;
    }

    // Verificar tipo de archivo (solo permitir imágenes)
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');

    // Obtener el tipo MIME real del archivo
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    $file_type = $file_info['type'] ? $file_info['type'] : $file['type'];

    error_log('🖼️ Tipo de archivo: ' . $file_type);

    if (!in_array($file_type, $allowed_types)) {
        error_log('❌ Tipo de archivo no permitido: ' . $file_type);
        wp_send_json_error('Solo se permiten imágenes (JPEG, PNG, GIF, WEBP)');
        return;
    }

    // Generar nombre único para el archivo
    $filename = 'print_image_' . time() . '_' . $image_index . '_' . sanitize_file_name($file['name']);
    $filepath = $upload_dir['path'] . '/' . $filename;

    // Mover el archivo subido a la carpeta de uploads
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Obtener URL de la imagen
        $image_url = $upload_dir['url'] . '/' . $filename;
        error_log('✅ Imagen guardada correctamente en: ' . $image_url);
    } else {
        error_log('❌ Error al mover el archivo subido de ' . $file['tmp_name'] . ' a ' . $filepath);
        wp_send_json_error('Error al guardar la imagen en el servidor');
        return;
    }

    // Guardar la imagen en los metadatos del carrito
    $cart_item_data = array(
        'print_image_data' => array(
            'id'=> $filename, // ID único para la imagen
            'image_url' => esc_url($image_url),
            'size' => $is_custom_size ? "{$custom_width}x{$custom_height}" : $size,
            'price' => $price,
            'is_custom_size' => $is_custom_size,
            'frame' => $frame,
            'comments' => $comments,
            'image_index' => $image_index // Guarda el índice para referencia
        ),
        'unique_key' => md5(microtime() . rand() . $image_index) // Asegura que cada item sea único
    );

    error_log('🛒 Datos del carrito para imagen ' . $image_index . ': ' . print_r($cart_item_data, true));

    // Añadir al carrito
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);


    if ($cart_item_key) {
        error_log('✅ Producto ' . $image_index . ' añadido al carrito correctamente');
        wp_send_json_success(array(
            'message' => 'Producto añadido al carrito',
            'cart_url' => wc_get_cart_url(),
            'image_index' => $image_index
        ));
    } else {
        // Obtener errores de WooCommerce y formatearlos correctamente
        $error_messages = array();
        $wc_errors = wc_get_notices('error');

        foreach ($wc_errors as $error) {
            if (is_array($error) && isset($error['notice'])) {
                $error_messages[] = $error['notice'];
            } elseif (is_string($error)) {
                $error_messages[] = $error;
            }
        }

        // Limpiar los errores para no mostrarlos duplicados después
        wc_clear_notices();

        $error_text = !empty($error_messages)
            ? implode(', ', $error_messages)
            : 'Error desconocido al añadir al carrito';

        error_log('❌ Errores reales del carrito: ' . $error_text);
        wp_send_json_error($error_text);
        return;
    }

    wp_die();
}
add_action('wp_ajax_process_print_image', 'process_print_image_to_cart');
add_action('wp_ajax_nopriv_process_print_image', 'process_print_image_to_cart');


// Mostrar los datos personalizados en el carrito y checkout

function display_cart_product($product_images, $cart_item, $cart_item_key)
{
    // Verifica si el artículo tiene una imagen personalizada
    if (isset($cart_item['print_image_data']['image_url'])) {
        $image_url = $cart_item['print_image_data']['image_url'];

        return [
            (object) [
                'id' => 0,
                'src' => $image_url,  // Imagen personalizada
                'thumbnail' => $image_url,  // Miniatura personalizada
                'srcset' => '',
                'sizes' => '',
                'name' => 'Imagen personalizada del producto',
                'alt' => 'Imagen personalizada del producto',
            ]
        ];
    }

    // Si no hay imagen personalizada, devuelve la imagen original del producto
    return $product_images;
}

add_filter('woocommerce_store_api_cart_item_images', 'display_cart_product', 10, 3);
function reemplazar_thumbnail_por_print_image($thumbnail, $item) {
    // Si $item es un integer, se asume que es el ID del item, por lo que cargamos el objeto
    if (is_int($item)) {
        error_log('ℹ️ Recibido ID de item: ' . $item);
        $item = new WC_Order_Item_Product($item);
    }

    // Verificar que ahora sí es un objeto válido
    if ($item instanceof WC_Order_Item_Product) {
        $print_image_url = $item->get_meta('URL', true);
        error_log('🖼️ Metadato _print_image_url: ' . $print_image_url);

        if (!empty($print_image_url)) {
            $thumbnail = '<img src="' . esc_url($print_image_url) . '" alt="Imagen personalizada" height: auto; />';
        }
    } else {
        // Aún no es el objeto esperado, log más detallado
        $tipo = is_object($item) ? get_class($item) : gettype($item);
        error_log('⚠️ Tipo de $item inesperado después de conversión: ' . $tipo);
    }

    return $thumbnail;
}
add_filter('woocommerce_admin_order_item_thumbnail', 'reemplazar_thumbnail_por_print_image', 10, 2);


// Actualizar el precio del producto en el carrito
function update_cart_item_price($cart) {
    if (did_action('woocommerce_before_calculate_totals') >= 2 || is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Definir precios por tamaño y cantidad
    $size_prices = [
        "10X15" => 5.00,
        "13X18" => 7.00,
        "15X21" => 8.00,
        "20X25" => 15.00,
        "20X30" => 18.00,
        "25X38" => 25.00,
        "30X45" => 35.00,
        "30X60" => 45.00,
    ];
    // Descuentos por cantidad mayor a 4
    $bulk_size_prices = [
        "10X15" => 3.00,
        "13X18" => 5.00,
        "15X21" => 6.00,
        "20X25" => 13.00,
        "20X30" => 15.00,
        "25X38" => 20.00,
        "30X45" => 30.00,
        "30X60" => 40.00,
    ];
    // Descuentos por cantidad mayor a 12
    $bulk_size_prices_12 = [
        "10X15" => 2.50,
        "13X18" => 4.00,
        "15X21" => 5.00,
        "20X25" => 10.00,
        "20X30" => 12.00,
        "25X38" => 18.00,
        "30X45" => 25.00,
        "30X60" => 35.00,
    ];
    // Recorrer los artículos del carrito
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $quantity = $cart_item['quantity'];

        // Obtener el tamaño del producto (asumiendo que se guarda en un campo personalizado)
        $size = isset($cart_item['print_image_data']['size']) ? $cart_item['print_image_data']['size'] : '';

        // Aplicar precio según tamaño y cantidad
        if (!empty($size)) {
            if ($quantity >= 12 && isset($bulk_size_prices_12[$size])) {
                $precio_final = $bulk_size_prices_12[$size];
            } elseif ($quantity >= 4 && isset($bulk_size_prices[$size])) {
                $precio_final = $bulk_size_prices[$size];
            } elseif (isset($size_prices[$size])) {
                $precio_final = $size_prices[$size];
            }

            // Asignar el precio final al producto
            if (isset($precio_final)) {
                $product->set_price($precio_final);
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'update_cart_item_price', 20, 1);

function display_print_image_cart_item_data($item_data, $cart_item)
{
    if (isset($cart_item['print_image_data'])) {
        $print_data = $cart_item['print_image_data'];

        // Mostrar el tamaño
        $item_data[] = array(
            'key' => 'Tamaño',
            'value' => $print_data['size']
        );

        // Mostrar el marco (si no es "sin-marco")
        if ($print_data['frame'] !== 'sin-marco') {
            $item_data[] = array(
                'key' => 'Marco',
                'value' => $print_data['frame']
            );
        }

        // Mostrar los comentarios (si existen)
        if (!empty($print_data['comments'])) {
            $item_data[] = array(
                'key' => 'Comentarios',
                'value' => $print_data['comments']
            );
        }
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_print_image_cart_item_data', 10, 2);

// Guardar datos personalizados en el pedido
function save_print_image_order_item_meta($item, $cart_item_key, $values, $order)
{
    if (isset($values['print_image_data'])) {
        $print_data = $values['print_image_data'];

        $item->add_meta_data('Tamaño', $print_data['size']);

        if ($print_data['frame'] !== 'sin-marco') {
            $item->add_meta_data('Marco', $print_data['frame']);
        }

        if (!empty($print_data['comments'])) {
            $item->add_meta_data('Comentarios', $print_data['comments']);
        }

        // Guardar la URL de la imagen para referencia
        $item->add_meta_data('URL', $print_data['image_url'], true);

        // Guardar el ID único de la imagen
        $item->add_meta_data('ID', $print_data['id'], true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'save_print_image_order_item_meta', 10, 4);

// Añadir página de configuración en el panel de administración
function print_image_settings_page()
{
    add_submenu_page(
        'woocommerce',
        'Configuración de Impresión de Imágenes',
        'Impresión de Imágenes',
        'manage_options',
        'print-image-settings',
        'print_image_settings_page_content'
    );
}
add_action('admin_menu', 'print_image_settings_page');

// Aumentar límites de PHP para permitir subida de imágenes grandes
function increase_upload_limits()
{
    @ini_set('post_max_size', '64M');
    @ini_set('upload_max_filesize', '32M');
    @ini_set('max_execution_time', '300');
    @ini_set('max_input_time', '300');
}
add_action('init', 'increase_upload_limits');

// Contenido de la página de configuración
function print_image_settings_page_content()
{
    // Guardar configuración
    if (isset($_POST['print_image_settings_nonce']) && wp_verify_nonce($_POST['print_image_settings_nonce'], 'save_print_image_settings')) {
        if (isset($_POST['print_image_product_id'])) {
            update_option('print_image_product_id', intval($_POST['print_image_product_id']));
        }
        echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
    }

    $product_id = get_option('print_image_product_id', 0);
    ?>
    <div class="wrap">
        <h1>Configuración de Impresión de Imágenes</h1>
        <form method="post" action="">
            <?php wp_nonce_field('save_print_image_settings', 'print_image_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Producto Base</th>
                    <td>
                        <select name="print_image_product_id">
                            <option value="0">Selecciona un producto</option>
                            <?php
                            $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
                            foreach ($products as $product) {
                                echo '<option value="' . esc_attr($product->get_id()) . '"' . selected($product_id, $product->get_id(), false) . '>' . esc_html($product->get_name()) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Selecciona el producto base que se utilizará para las impresiones de
                            imágenes.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Cambios">
            </p>
        </form>
    </div>
    <?php
}