<?php
/**
 * WordPress Login & Checkout Redirect Functions
 * 
 * Este archivo maneja las redirecciones personalizadas del sistema de login
 * y checkout, integrando LoginPress con WooCommerce para una experiencia
 * de usuario fluida durante el proceso de compra.
 * 
 * Funcionalidades incluidas:
 * - Redirección automática al checkout después del login
 * - Detección de pedidos en proceso y carritos con productos
 * - Redirección forzada al login personalizado para usuarios no logueados en checkout
 * - Redirección personalizada después del logout
 * - Manejo de diferentes escenarios de login según roles de usuario
 * 
 * @package     WordPress_Login_Redirects
 * @version     1.0.0
 * @author      Enmanuel
 * @since       1.0.0
 * 
 * Hooks utilizados:
 * - login_redirect: Personaliza redirecciones después del login
 * - template_redirect: Controla acceso a páginas específicas
 * - wp_logout: Maneja redirecciones después del logout
 * 
 * Dependencias:
 * - WooCommerce activo
 * - LoginPress plugin (para página de login personalizada)
 * 
 * URLs configuradas:
 * - Login personalizado: https://webformwp.reflexoperu.com.pe/login
 * - Página principal: https://webformwp.reflexoperu.com.pe
 * 
 * Condiciones de redirección:
 * - Pedidos en proceso (store_api_draft_order)
 * - Carrito con productos
 * - Acceso desde página de checkout
 * - Usuarios no logueados en checkout
 */

// Prevenir acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}
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
add_filter('login_redirect', 'redirect_to_checkout_after_login', 9999, 3);

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

function custom_redirect_after_logout()
{
    wp_redirect('https://webformwp.reflexoperu.com.pe/login');
    exit();
}

// Redirigir después de cerrar sesión a /wp-login.php
add_action('wp_logout', 'custom_redirect_after_logout');

// Redirección al login de vista de impresión

add_filter('login_redirect', function ($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        return 'https://webformwp.reflexoperu.com.pe';
    }
    return $redirect_to;
}, 10, 3);