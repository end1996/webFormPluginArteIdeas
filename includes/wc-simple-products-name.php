<?php
/**
 * Simplificación de nombres de productos WooCommerce
 * 
 * Este archivo personaliza la visualización de nombres de productos en WooCommerce
 * para mostrar únicamente el nombre del producto padre en lugar de incluir
 * las variaciones y atributos específicos.
 * 
 * Funcionalidades incluidas:
 * - Simplifica nombres de productos en órdenes (frontend y backend)
 * - Limpia nombres de productos en correos electrónicos
 * - Previene la adición automática de atributos al nombre del producto
 * - Mantiene consistencia en toda la experiencia de compra
 * 
 * Casos de uso:
 * - Cuando se desea mostrar solo "Camiseta" en lugar de "Camiseta - Talla M, Color Rojo"
 * - Para simplificar la apariencia de facturas y correos
 * - Para mantener nombres limpios en el panel de administración
 * 
 * @package WebForm
 * @author Enmanuel
 * @version 1.0
 * @since 1.0
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filtro principal para nombres de productos en órdenes
 * 
 * Modifica la visualización de nombres de productos en órdenes para mostrar
 * únicamente el nombre del producto padre, sin incluir variaciones.
 * 
 * @param string $product_name Nombre original del producto
 * @param WC_Order_Item_Product $item Item de la orden
 * @param bool $is_visible Si el item es visible o no
 * @return string Nombre simplificado del producto
 */
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

/**
 * Filtro específico para el administrador de WooCommerce
 * 
 * Aplica la misma lógica de simplificación de nombres en el panel
 * de administración de WooCommerce para mantener consistencia.
 * 
 * @param string $product_name Nombre original del producto
 * @param WC_Order_Item_Product $item Item de la orden
 * @return string Nombre simplificado del producto
 */
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

/**
 * Filtro para correos electrónicos
 * 
 * Asegura que los nombres de productos en correos electrónicos
 * (confirmaciones de pedido, facturas, etc.) también muestren
 * únicamente el nombre del producto padre.
 * 
 * @param string $product_name Nombre original del producto
 * @param WC_Order_Item_Product $item Item de la orden
 * @return string Nombre simplificado del producto
 */
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

/**
 * Prevenir que se añadan atributos automáticamente al nombre
 * 
 * Evita que WooCommerce añada automáticamente los atributos de variación
 * al título del producto, manteniendo nombres limpios y simples.
 * 
 * @return bool Siempre retorna false para desactivar la inclusión automática
 */
add_filter('woocommerce_product_variation_title_include_attributes', '__return_false');