<?php
/*
Plugin Name: Web Form Plugin
Description: Un plugin para integrar mi aplicación HTML, CSS y JS en WordPress con WooCommerce.
Version: 3.5.7
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
    $css_path = plugin_dir_url(__FILE__) . 'assets/css/styles.css';
    $mini_cart_js_path = plugin_dir_url(__FILE__) . 'assets/js/mini-cart-style-script.js';
    $js_path = plugin_dir_url(__FILE__) . 'assets/js/main.js';
    $picker_path = plugin_dir_url(__FILE__) . 'assets/js/ios-picker.js';

    // Cargar CSS
    wp_enqueue_style('web-form-styles', $css_path, array(), '5.3.1');

    // Cargar Mini-Cart Style CSS
    wp_enqueue_script('mini-cart-style-script', $mini_cart_js_path, array(), '1.0.3');

    wp_enqueue_style('picker-placeholder-css', plugin_dir_url(__FILE__) . 'assets/css/placeholder-loader.css', array(), '1.0.1');

    // Cargar Swiper CSS desde CDN
    wp_enqueue_style('swiper-css', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');

    // Cargar Viewer CSS desde CDN
    wp_enqueue_style('viewer-css', 'https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.7/viewer.css', array(), '1.11.7');

    // Cargar Picker Placeholder JS
    wp_enqueue_script('picker-placeholder-js', plugin_dir_url(__FILE__) . 'assets/js/picker-placeholder.js', array('jquery'), '1.0.0', true);

    // Cargar IOS-Picker
    wp_enqueue_script('picker-script-js', $picker_path, array('jquery'), '2.0.5', true);

    // Cargar JS
    wp_enqueue_script('script-js', $js_path, array('jquery'), '5.9.7', true);
    wp_enqueue_script('events-js', plugin_dir_url(__FILE__) . 'assets/js/events.js', array('jquery'), '1.0.8', true);
    wp_enqueue_script('requestHttp-js', plugin_dir_url(__FILE__) . 'assets/js/requestHttp.js', array('jquery'), '1.1.0', true);

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

    // Asegurarse de que WooCommerce cargue sus fragmentos
    if (function_exists('wc_enqueue_js')) {
        wc_enqueue_js('
            jQuery(document.body).on("wc_fragment_refresh", function() {
                jQuery(document.body).trigger("wc_fragments_refreshed");
            });
        ');
    }

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

    // Verificamos si tenemos imágenes
    if (empty($_FILES['image_data'])) {
        error_log('Error: No se ha recibido ningún archivo de imagen');
        wp_send_json_error('No se ha subido ninguna imagen');
        return;
    }

    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : '';
    $variation_id = isset($_POST['variation_id']) ? sanitize_text_field($_POST['variation_id']) : 0;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0.0;
    $comments = isset($_POST['comments']) ? sanitize_textarea_field($_POST['comments']) : '';
    $frame = isset($_POST['frame']) ? sanitize_text_field($_POST['frame']) : 'sin-marco';
    $custom_width = isset($_POST['custom_width']) ? intval($_POST['custom_width']) : 0;
    $custom_height = isset($_POST['custom_height']) ? intval($_POST['custom_height']) : 0;
    $is_custom_size = isset($_POST['is_custom_size']) ? filter_var($_POST['is_custom_size'], FILTER_VALIDATE_BOOLEAN) : false;
    $frame_price = isset($_POST['frame_price']) ? floatval($_POST['frame_price']) : 0.0;

    // ID DEL PRODUCTO
    $product_id = get_option('print_image_product_id', 244);
    if (!$product_id || !wc_get_product($product_id)) {
        error_log('Error: No se ha configurado un producto base para impresiones');
        wp_send_json_error('No se ha configurado un producto base para impresiones');
        return;
    }

    error_log('ID DE LA VARIACION: ' . $variation_id);

    $upload_dir = wp_upload_dir();
    $images_data = [];

    // Soportar tanto un solo archivo como múltiples archivos
    $files = $_FILES['image_data'];
    $file_count = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $file_count; $i++) {
        $file = [
            'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
            'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
            'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
            'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
            'size' => is_array($files['size']) ? $files['size'][$i] : $files['size'],
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log('❌ Error en la subida: ' . $file['error']);
            continue;
        }

        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        $file_type = $file_info['type'] ? $file_info['type'] : $file['type'];

        if (!in_array($file_type, $allowed_types)) {
            error_log('❌ Tipo de archivo no permitido: ' . $file_type);
            continue;
        }

        $filename = 'print_image_' . time() . '_' . $i . '_' . sanitize_file_name($file['name']);
        $filepath = $upload_dir['path'] . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $image_url = $upload_dir['url'] . '/' . $filename;
            $images_data[] = [
                'id' => $filename,
                'image_url' => esc_url($image_url),
                'size' => $is_custom_size ? "{$custom_width}x{$custom_height}" : $size,
                'is_custom_size' => $is_custom_size,
                'frame' => $frame,
                'comments' => $comments,
            ];
        } else {
            error_log('❌ Error al mover el archivo subido de ' . $file['tmp_name'] . ' a ' . $filepath);
        }
    }

    if (empty($images_data)) {
        wp_send_json_error('No se pudieron procesar las imágenes.');
        return;
    }

    // Guardar todas las imágenes en el array print_image_data
    $cart_item_data = array(
        'print_image_data' => $images_data,
        //'size' => $is_custom_size ? "{$custom_width} X {$custom_height}" : $size,
        'price' => $price,
        'is_custom_size' => $is_custom_size,
        'frame' => $frame,
        'comments' => $comments,
        'frame_price' => $frame_price,
        'unique_key' => md5(microtime() . rand())
    );

    // Solo agregar dimensiones si son personalizadas
    if ($is_custom_size) {
        $cart_item_data['custom_width'] = $custom_width;
        $cart_item_data['custom_height'] = $custom_height;
    }

    // --- NUEVO: Añadir variación correctamente ---
    if (!$is_custom_size && !empty($size)) {
        $variation['attribute_pa_dimensiones'] = $size;
    }

    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);

    if ($cart_item_key) {
        wp_send_json_success(array(
            'message' => 'Producto añadido al carrito',
            'cart_url' => wc_get_cart_url()
        ));
    } else {
        $error_messages = array();
        $wc_errors = wc_get_notices('error');
        foreach ($wc_errors as $error) {
            if (is_array($error) && isset($error['notice'])) {
                $error_messages[] = $error['notice'];
            } elseif (is_string($error)) {
                $error_messages[] = $error;
            }
        }
        wc_clear_notices();
        $error_text = !empty($error_messages)
            ? implode(', ', $error_messages)
            : 'Error desconocido al añadir al carrito';
        wp_send_json_error($error_text);
    }

    wp_die();
}
add_action('wp_ajax_process_print_image', 'process_print_image_to_cart');
add_action('wp_ajax_nopriv_process_print_image', 'process_print_image_to_cart');

add_action('woocommerce_before_calculate_totals', 'set_custom_price_for_cart_item', 20, 1);
function set_custom_price_for_cart_item($cart)
{
    if (is_admin() && !defined('DOING_AJAX'))
        return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['print_image_data']) && isset($cart_item['price'])) {
            $custom_price = floatval($cart_item['price']);
            if ($custom_price > 0) {
                $cart_item['data']->set_price($custom_price);
            }
        }
    }
}

// Cargar el script del carrito en las páginas necesarias
function load_cart_handler_script()
{
    // Solo cargar en páginas que contengan el carrito
    if (is_cart() || is_checkout() || is_product()) {
        wp_enqueue_script('cart-handler-js', plugin_dir_url(__FILE__) . 'assets/js/cart-handler.js', array('jquery', 'wc-cart'), '1.0.6', true);
    }
}
add_action('wp_enqueue_scripts', 'load_cart_handler_script');
// Cargar los estilos personalizados para vista de carrito
function load_cart_page_styles()
{
    // Cargar solo en la página del carrito
    if (is_cart()) {
        wp_enqueue_style('cart-page-css', plugin_dir_url(__FILE__) . 'assets/css/cart-page-style.css', array(), '1.1.0');
    }
}
add_action('wp_enqueue_scripts', 'load_cart_page_styles');

function load_order_details_styles()
{
    if (is_order_received_page()) {
        wp_enqueue_style('order-details-css', plugin_dir_url(__FILE__) . 'assets/css/order-details-style.css', array(), '1.0.3');
    }
}
add_action('wp_enqueue_scripts', 'load_order_details_styles');

function load_order_details_admin_styles($hook)
{
    // Asegurarnos de que estamos en la vista de edición de pedidos de WooCommerce
    if (isset($_GET['page']) && $_GET['page'] === 'wc-orders' && isset($_GET['action']) && $_GET['action'] === 'edit') {
        wp_enqueue_style('order-details-css-admin', plugin_dir_url(__FILE__) . 'assets/css/order-details-style.css', array(), '1.0.4');
    }
}
add_action('admin_enqueue_scripts', 'load_order_details_admin_styles');


function load_checkout_page_styles()
{
    if (is_checkout()) {
        wp_enqueue_style('checkout-page-css', plugin_dir_url(__FILE__) . 'assets/css/checkout-page-style.css', array(), '1.0.2');
    }
}
add_action('wp_enqueue_scripts', 'load_checkout_page_styles');

// Cargar custom-checkout.php SOLO en la página de checkout
function load_custom_checkout_script()
{
    // Verificar si WooCommerce está activo y si es la página de checkout
    if (class_exists('WooCommerce') && is_checkout() && !is_order_received_page()) {
        require_once plugin_dir_path(__FILE__) . 'includes/custom-checkout.php';
    }
}
add_action('wp', 'load_custom_checkout_script');

// Cargar woocommerce custom-product-display
require_once plugin_dir_path(__FILE__) . 'includes/wc-custom-product-display.php';

// Cargar custom redirections
require_once plugin_dir_path(__FILE__) . 'includes/custom-redirections.php';

// Cargar customizations
require_once plugin_dir_path(__FILE__) . 'includes/customizations.php';

// Cargar simple products filter
require_once plugin_dir_path(__FILE__) . 'includes/wc-simple-products-name.php';

// Cargar página de configuración de producto base
require_once plugin_dir_path(__FILE__) . 'includes/wc-print-setting-page.php';

// Cargar sistema de precios para marcos
require_once plugin_dir_path(__FILE__) . 'includes/wc-frame-pricing-system.php';

// Cargar personalización de muestra de productos
require_once plugin_dir_path(__FILE__) . 'includes/wc-custom-product-display.php';
function load_custom_lost_password_page()
{
    if (is_wc_endpoint_url('lost-password')) {
        wp_enqueue_style('lost-password-page-css', plugin_dir_url(__FILE__) . 'assets/css/lost-password-page-style.css', array(), '1.0.0');
    }
}
add_action('wp_enqueue_scripts', 'load_custom_lost_password_page');

function cargar_custom_notifications_js()
{
    wp_enqueue_script(
        'custom-notifications-js',
        plugin_dir_url(__FILE__) . 'assets/js/custom-notifications.js',
        array('jquery'),
        '1.0.1',
        true
    );
}
add_action('wp_enqueue_scripts', 'cargar_custom_notifications_js');

function load_custom_notifications_loading_css()
{
    wp_enqueue_style(
        'custom-notifications-loading-css',
        plugin_dir_url(__FILE__) . 'assets/css/notifications-loading-styles.css',
        array(),
        '1.0.1'
    );
}

add_action('wp_enqueue_scripts', 'load_custom_notifications_loading_css');

function load_main_menu_redirection_js()
{
    wp_enqueue_script(
        'main-menu-redirection-js',
        plugin_dir_url(__FILE__) . 'assets/js/main-menu-redirection.js',
        array('jquery'),
        '1.0.0',
        true
    );
}

add_action('wp_enqueue_scripts', 'load_main_menu_redirection_js');