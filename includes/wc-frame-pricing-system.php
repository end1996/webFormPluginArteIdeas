<?php
/**
 * Sistema de precios de marcos WooCommerce
 * 
 * Este archivo implementa un sistema personalizado de precios para productos
 * con marcos opcionales en WooCommerce. Permite mostrar precios desglosados
 * entre el producto base y el marco seleccionado.
 * 
 * Funcionalidades incluidas:
 * - Separación visual de precios base y marcos en el carrito
 * - Cálculo automático de fees por tipo de marco
 * - Agrupación de marcos por tipo con cantidades
 * - Compatibilidad con AJAX del carrito
 * - Manejo de múltiples productos con marcos diferentes
 * 
 * Casos de uso:
 * - Tienda de cuadros, fotografías o arte con marcos opcionales
 * - Productos personalizables con accesorios de precio variable
 * - Cualquier producto que requiera mostrar precios desglosados
 * 
 * Estructura de datos esperada en cart_item:
 * - 'frame': tipo de marco seleccionado
 * - 'frame_price': precio adicional del marco
 * 
 * @package WebForm
 * @author Tu Nombre
 * @version 1.0
 * @since 1.0
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modifica la visualización del precio individual en el carrito
 * 
 * Durante las actualizaciones AJAX del carrito, muestra únicamente el precio
 * base del producto cuando hay un marco seleccionado, para evitar confusión
 * en la visualización de precios.
 * 
 * @param string $price_html HTML del precio formateado
 * @param array $cart_item Datos del item del carrito
 * @param string $cart_item_key Clave única del item en el carrito
 * @return string HTML del precio modificado
 */
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

/**
 * Personaliza la visualización del subtotal en el carrito
 * 
 * Cuando un producto tiene marco seleccionado, muestra el desglose
 * del subtotal separando el precio base del producto y el precio del marco,
 * proporcionando transparencia en los costos.
 * 
 * @param string $subtotal HTML del subtotal original
 * @param array $cart_item Datos del item del carrito
 * @param string $cart_item_key Clave única del item en el carrito
 * @return string HTML del subtotal modificado con desglose
 */
add_filter('woocommerce_cart_item_subtotal', function ($subtotal, $cart_item, $cart_item_key) {
    if (!empty($cart_item['frame_price']) && $cart_item['frame'] !== 'sin-marco') {
        $frame_price = floatval($cart_item['frame_price']);
        $quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
        
        // Calcular subtotales individuales
        $base_subtotal = wc_price($cart_item['data']->get_price() * $quantity);
        $frame_subtotal = wc_price($frame_price * $quantity);
        $total = ($cart_item['data']->get_price() + $frame_price) * $quantity;
        $total_html = wc_price($total);
        
        // Crear HTML desglosado
        $subtotal = '<div class="subtotal-base">' . $base_subtotal . '</div>';
        $subtotal .= '<div class="subtotal-marco" style="color:#666;"><strong>' . $frame_subtotal . '</strong></div>';
        //$subtotal .= '<div class="subtotal-total" style="margin-top:4px;font-weight:bold;">Total: ' . $total_html . '</div>';
    }
    return $subtotal;
}, 10, 3);

/**
 * Calcula y añade fees por marcos al carrito
 * 
 * Esta función procesa todos los items del carrito que tienen marcos
 * seleccionados, los agrupa por tipo de marco y añade fees correspondientes
 * al total del carrito. Maneja múltiples productos con el mismo tipo de marco.
 * 
 * @param WC_Cart $cart Objeto del carrito de WooCommerce
 * @return void
 */
add_action('woocommerce_cart_calculate_fees', function ($cart) {
    // Evitar ejecución en admin (excepto AJAX)
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Arrays para acumular los precios de marcos por tipo
    $frame_totals = array();
    $frame_quantities = array();

    // Procesar cada item del carrito
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
            // Mostrar cantidad si es mayor a 1
            $quantity_text = $frame_quantities[$frame_type] > 1
                ? ' (x' . $frame_quantities[$frame_type] . ')'
                : '';

            $fee_name = 'Marco: ' . esc_html($frame_type) . $quantity_text;
            $cart->add_fee($fee_name, $total_price, false);
        }
    }
});