<?php
/*
Plugin Name: Web Form Plugin
Description: Un plugin para integrar mi aplicación HTML, CSS y JS en WordPress con WooCommerce.
Version: 3.5.5
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

// Mostrar los datos personalizados en el carrito y checkout

function display_cart_product($product_images, $cart_item, $cart_item_key)
{
    if (isset($cart_item['print_image_data']) && is_array($cart_item['print_image_data'])) {
        $images = [];
        foreach ($cart_item['print_image_data'] as $img) {
            $images[] = (object) [
                'id' => 0,
                'src' => $img['image_url'],
                'thumbnail' => $img['image_url'],
                'srcset' => '',
                'sizes' => '',
                'name' => 'Imagen personalizada del producto',
                'alt' => 'Imagen personalizada del producto',
            ];
        }
        return $images;
    }
    return []; // Retornar array vacío en lugar de $product_images
}
add_filter('woocommerce_store_api_cart_item_images', 'display_cart_product', 10, 3);

function display_print_image_cart_item_data(
    $item_data,
    $cart_item
) {
    if (isset($cart_item['print_image_data'])) {
        $images = $cart_item['print_image_data'];
        // Si es un solo string (caso antiguo), conviértelo en array
        if (!is_array($images) || (isset($images['image_url']) && is_string($images['image_url']))) {
            $images = [$images];
        }

        if (isset($cart_item['custom_width']) && isset($cart_item['custom_height'])) {
            $item_data[] = array(
                'key' => 'Tamaño',
                'value' => $cart_item['custom_width'] . ' X ' . $cart_item['custom_height']
            );
        }

        // Solo mostrar imágenes si es array de arrays
        if (is_array($images)) {
            // Mostrar miniaturas de todas las imágenes
            $thumbnails = '<div class="image-thumbnails-container">';
            foreach ($images as $img) {
                if (is_array($img) && !empty($img['image_url'])) {
                    $thumbnails .= '<div class="image-thumb">';
                    $thumbnails .= '<img src="' . esc_url($img['image_url']) . '" alt="Imagen subida" />';
                    $thumbnails .= '</div>';
                }
            }
            if (!empty($thumbnails)) {
                $item_data[] = array(
                    'key' => 'Imágenes',
                    'value' => $thumbnails  // agrego salto de línea aquí
                );
            }
        }

        if (isset($cart_item['frame']) && $cart_item['frame'] !== 'sin-marco') {
            $frame_value = esc_html($cart_item['frame']);
            $item_data[] = array('key' => 'Marco', 'value' => $frame_value);
        }

        if (!empty($cart_item['comments'])) {
            $item_data[] = array('key' => 'Comentarios', 'value' => $cart_item['comments']);
        }
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_print_image_cart_item_data', 10, 2);


// Guardar datos personalizados en el pedido
function save_print_image_order_item_meta($item, $cart_item_key, $values, $order)
{
    if (isset($values['print_image_data']) && is_array($values['print_image_data'])) {
        $image_urls = [];
        foreach ($values['print_image_data'] as $img) {
            if (isset($img['image_url'])) {
                $image_urls[] = esc_url($img['image_url']);
            }
        }

        if (
            isset($values['is_custom_size']) && $values['is_custom_size'] &&
            !empty($values['custom_width']) && !empty($values['custom_height'])
        ) {
            $dimension_text = $values['custom_width'] . ' X ' . $values['custom_height'];
            $item->add_meta_data('Tamaño', $dimension_text);
        }

        // Guardar todas las URLs de imágenes como un solo meta dato
        if (!empty($image_urls)) {
            $item->add_meta_data('Imágenes', implode(', ', $image_urls));
        }

        if (isset($values['frame']) && $values['frame'] !== 'sin-marco') {
            $item->add_meta_data('Marco', $values['frame']);
        }
        if (!empty($values['comments'])) {
            $item->add_meta_data('Comentarios', '<div style="max-width:300px; word-wrap: break-word; white-space: normal;">' . esc_html($values['comments']) . '</div>', true);
        }
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

// Cargar el script del carrito en las páginas necesarias
function load_cart_handler_script()
{
    // Solo cargar en páginas que contengan el carrito
    if (is_cart() || is_checkout() || is_product()) {
        wp_enqueue_script('cart-handler-js', plugin_dir_url(__FILE__) . 'assets/js/cart-handler.js', array('jquery', 'wc-cart'), '1.0.2', true);
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

// Mostrar miniaturas en el detalle del pedido en el admin
add_filter('woocommerce_order_item_display_meta_value', function ($value, $meta, $item) {
    if ($meta->key === 'Imágenes') {
        $image_urls = is_array($meta->value) ? $meta->value : explode(', ', $meta->value);
        // Estilos en línea mínimos para correos (solo en <img>)
        $inline_img_style = 'width:120px; height:120px; object-fit:cover; border:1px solid #ccc; border-radius:4px; display:block;';

        $thumbnails = '<div class="image-thumbnails-container">';
        
        foreach ($image_urls as $img_url) {
            if (empty(trim($img_url))) continue;

            $img_url = esc_url(trim($img_url));
            $thumbnails .= '<div class="image-thumb">'
                . '<img src="' . $img_url . '" alt="Imagen subida" style="' . esc_attr($inline_img_style) . '" />';

            if (is_admin()) {
                $thumbnails .= '<a href="' . $img_url . '" target="_blank" style="font-size:11px;">Descargar</a>';
            }

            $thumbnails .= '</div>';
        }

        $thumbnails .= '</div>';
        return $thumbnails;
    }

    return $value;
}, 10, 3);



// Añadir botón de seguir comprando en vista de checkout
remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);

add_action('woocommerce_proceed_to_checkout', 'mi_boton_personalizado_checkout', 20);

function mi_boton_personalizado_checkout()
{
    ?>
    <div class="wc-proceed-to-checkout">
        <p>
            <a href="https://webformwp.reflexoperu.com.pe/" class="button wc-backward"
                style=" width: 100%; height: 35%; text-align: center; font-size: 16px;"> Seguir comprando </a>
        </p>
    </div>
    <?php
}

/**
 * Cambia la URL del logo en WordPress
 */
function cambiar_url_logo_personalizada($html)
{
    // Reemplaza la URL actual con tu nueva URL deseada
    $nueva_url = 'https://webformwp.reflexoperu.com.pe/home/'; // Cambia esto por tu URL

    // Modifica el HTML del logo
    $html = preg_replace('/href="(.*?)"/', 'href="' . esc_url($nueva_url) . '"', $html);

    return $html;
}
add_filter('get_custom_logo', 'cambiar_url_logo_personalizada');

// Redirección al login de vista de impresión

add_filter('login_redirect', function ($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        return 'https://webformwp.reflexoperu.com.pe';
    }
    return $redirect_to;
}, 10, 3);

add_filter('woocommerce_cart_item_price', function ($price_html, $cart_item, $cart_item_key) {
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'woocommerce_get_refreshed_fragments') {
        // Si hay precio de marco, solo muestra el precio base
        if (!empty($cart_item['frame_price']) && $cart_item['frame'] !== 'sin-marco') {
            $base_price = $cart_item['data']->get_price();
            return wc_price($base_price);
        }
    }
    return $price_html;
}, 10, 3);

add_filter('woocommerce_cart_item_subtotal', function ($subtotal, $cart_item, $cart_item_key) {
    if (!empty($cart_item['frame_price']) && $cart_item['frame'] !== 'sin-marco') {
        $frame_price = floatval($cart_item['frame_price']);
        $quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
        $base_subtotal = wc_price($cart_item['data']->get_price() * $quantity);
        $frame_subtotal = wc_price($frame_price * $quantity);
        $total = ($cart_item['data']->get_price() + $frame_price) * $quantity;
        $total_html = wc_price($total);
        $subtotal = '<div class="subtotal-base">' . $base_subtotal . '</div>';
        $subtotal .= '<div class="subtotal-marco" style="color:#666;"><strong>' . $frame_subtotal . '</strong></div>';
        //$subtotal .= '<div class="subtotal-total" style="margin-top:4px;font-weight:bold;">Total: ' . $total_html . '</div>';
    }
    return $subtotal;
}, 10, 3);

add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Array para acumular los precios de marcos por tipo
    $frame_totals = array();
    $frame_quantities = array();

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['frame_price']) && $cart_item['frame'] !== 'sin-marco') {
            $frame_type = $cart_item['frame'];
            $frame_price = floatval($cart_item['frame_price']);
            $quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
            if ($frame_price > 0) {
                // Acumular por tipo de marco
                if (!isset($frame_totals[$frame_type])) {
                    $frame_totals[$frame_type] = 0;
                    $frame_quantities[$frame_type] = 0;
                }

                $frame_totals[$frame_type] += $frame_price * $quantity;
                $frame_quantities[$frame_type] += $quantity;
            }
        }
    }

    // Agregar un fee por cada tipo de marco
    foreach ($frame_totals as $frame_type => $total_price) {
        if ($total_price > 0) {
            $quantity_text = $frame_quantities[$frame_type] > 1
                ? ' (x' . $frame_quantities[$frame_type] . ')'
                : '';

            $fee_name = 'Marco: ' . esc_html($frame_type) . $quantity_text;
            $cart->add_fee($fee_name, $total_price, false);
        }
    }
});

// // Mostrar solo el nombre base del producto en el carrito y checkout, sin atributos ni enlaces
// add_filter('woocommerce_cart_item_name', function ($product_name, $cart_item, $cart_item_key) {
//     if (isset($cart_item['data']) && is_object($cart_item['data'])) {
//         // Solo el nombre base, sin enlace ni atributos
//         return esc_html($cart_item['data']->get_title());
//     }
//     return $product_name;
// }, 99, 3);

// 1. Filtro principal para nombres de productos en órdenes
add_filter('woocommerce_order_item_name', function ($product_name, $item, $is_visible = true) {
    $product = $item->get_product();
    if ($product && is_object($product)) {
        // Obtener solo el nombre base del producto padre
        if ($product->is_type('variation')) {
            $parent_product = wc_get_product($product->get_parent_id());
            if ($parent_product) {
                return esc_html($parent_product->get_name());
            }
        }
        return esc_html($product->get_name());
    }
    return $product_name;
}, 99, 3);

// 2. Filtro específico para el administrador de WooCommerce
add_filter('woocommerce_admin_order_item_name', function ($product_name, $item) {
    $product = $item->get_product();
    if ($product && is_object($product)) {
        if ($product->is_type('variation')) {
            $parent_product = wc_get_product($product->get_parent_id());
            if ($parent_product) {
                return esc_html($parent_product->get_name());
            }
        }
        return esc_html($product->get_name());
    }
    return $product_name;
}, 99, 2);

// 3. Filtro para correos electrónicos
add_filter('woocommerce_email_order_item_name', function ($product_name, $item) {
    $product = $item->get_product();
    if ($product && is_object($product)) {
        if ($product->is_type('variation')) {
            $parent_product = wc_get_product($product->get_parent_id());
            if ($parent_product) {
                return esc_html($parent_product->get_name());
            }
        }
        return esc_html($product->get_name());
    }
    return $product_name;
}, 99, 2);

// 4. Prevenir que se añadan atributos automáticamente al nombre
add_filter('woocommerce_product_variation_title_include_attributes', '__return_false');

// Redirigir después de cerrar sesión a /wp-login.php
add_action('wp_logout', 'custom_redirect_after_logout');
function custom_redirect_after_logout()
{
    wp_redirect('https://webformwp.reflexoperu.com.pe/login');
    exit();
}

// Agregar botón de registro al formulario de login en el checkout
add_action('woocommerce_before_checkout_form', 'add_custom_register_button_to_checkout_login', 15);
function add_custom_register_button_to_checkout_login()
{
    if (!is_user_logged_in()) {
        echo '<div class="custom-register-button" style="margin-top: 15px; text-align: center;">';
        echo '<a href="' . esc_url(home_url('/login?action=register')) . '" class="button" style="background-color: #e76f51; color: white; display: inline-block; padding: 10px 20px;">¿Nuevo cliente? Regístrate aquí</a>';
        echo '</div>';
    }
}

add_filter('login_redirect', 'redirect_to_checkout_after_login', 9999, 3);

// Redireccionar a la vista de checkout luego de loguearse
function redirect_to_checkout_after_login($redirect_to, $requested_redirect_to, $user) {
    // Si ya hay una redirección definida, no la modifiques
    if ($requested_redirect_to) {
        return $requested_redirect_to;
    }
    
    // Verificar si hay un pedido en proceso (WooCommerce)
    if (function_exists('WC') && WC()->session && !empty(WC()->session->get('store_api_draft_order'))) {
        return wc_get_checkout_url();
    }
    
    // Alternativa: verificar si hay items en el carrito
    if (function_exists('WC') && !WC()->cart->is_empty()) {
        return wc_get_checkout_url();
    }
    
    // Alternativa 2: verificar si viene de la página de checkout
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'checkout') !== false) {
        return wc_get_checkout_url();
    }
    
    return $redirect_to;
}


// Redirecciona al login personalizado si no esta logueado al intentar finalizar compra
add_action('template_redirect', 'redirect_guest_checkout_to_loginpress');
function redirect_guest_checkout_to_loginpress() {
    if (is_checkout() && !is_user_logged_in()) {
        // URL de tu página de login con LoginPress
        $login_url = 'https://webformwp.reflexoperu.com.pe/login'; 
        // Añadir parámetro para redirigir al checkout después del login
        wp_redirect(add_query_arg('redirect_to', urlencode(wc_get_checkout_url()), $login_url));
        exit;
    }
}