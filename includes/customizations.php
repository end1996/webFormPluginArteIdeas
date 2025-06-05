<?php
/**
 * Customizaciones WooCommerce y WordPress
 * 
 * Este archivo contiene funciones personalizadas para modificar el comportamiento
 * de WooCommerce y WordPress en el sitio web.
 * 
 * Funcionalidades incluidas:
 * - Personalización del botón "Proceder al checkout" en el carrito
 * - Modificación de la URL del logo de WordPress
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
add_action('woocommerce_proceed_to_checkout', 'mi_boton_personalizado_checkout', 20);
// Añadir botón de seguir comprando en vista de checkout
remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);

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