<?php
/**
 * WooCommerce Custom Product Display Functions
 * 
 * Este archivo contiene las funciones personalizadas para mostrar y gestionar
 * datos adicionales de productos en el carrito, checkout y pedidos de WooCommerce.
 * 
 * Funcionalidades incluidas:
 * - Mostrar imágenes personalizadas de productos en el carrito
 * - Visualizar dimensiones personalizadas (ancho x alto)
 * - Mostrar marcos seleccionados
 * - Mostrar comentarios del cliente
 * - Guardar metadatos personalizados en los pedidos
 * - Mostrar miniaturas de imágenes en la orden del administrador
 * 
 * @package     WooCommerce_Custom_Product_Display
 * @version     1.0.0
 * @author      Enmanuel
 * @since       1.0.0
 * 
 * Hooks utilizados:
 * - woocommerce_store_api_cart_item_images
 * - woocommerce_get_item_data
 * - woocommerce_checkout_create_order_line_item
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
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