<?php
/**
 * Personalización del Checkout de WooCommerce
 * - Modifica campos de facturación
 * - Añade validaciones
 * - Deshabilita secciones innecesarias
 */

// ==============================================
// 1. Eliminar campos innecesarios y personalizar
// ==============================================
add_filter('woocommerce_checkout_fields', 'customizar_checkout_woocommerce');

function customizar_checkout_woocommerce($fields) {
    // Eliminar campo de empresa
    unset($fields['billing']['billing_company']);
    
    // Modificar campo de teléfono ORIGINAL (evita errores de campo requerido)
    $fields['billing']['billing_phone'] = array(
        'label'       => __('Número de teléfono', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-last'),
        'type'        => 'tel',
        'inputmode'   => 'numeric',
        'pattern'     => '[0-9]*',
        'maxlength'   => '9',
        'placeholder' => __('Ej: 987654321', 'woocommerce'),
        'priority'    => 26,
        'validate'    => array('phone')
    );
    
    // Añadir campo de código de área (nuevo)
    $fields['billing']['billing_phone_code'] = array(
        'label'       => __('Código de área', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-first'),
        'type'        => 'select',
        'options'     => array(
            '+51' => 'Perú (+51)',
            '+56' => 'Chile (+56)'
        ),
        'priority'    => 25
    );
    
    // Modificar validación de código postal
    $fields['billing']['billing_postcode']['type'] = 'text';
    $fields['billing']['billing_postcode']['inputmode'] = 'numeric';
    $fields['billing']['billing_postcode']['pattern'] = '[0-9]*';
    $fields['billing']['billing_postcode']['maxlength'] = '5';
    
    return $fields;
}

// ==============================================
// 2. Validaciones en PHP (backend)
// ==============================================
add_action('woocommerce_checkout_process', 'validar_campos_personalizados');

function validar_campos_personalizados() {
    // Validar teléfono (9 dígitos numéricos)
    if (isset($_POST['billing_phone']) && !preg_match('/^[0-9]{9}$/', $_POST['billing_phone'])) {
        wc_add_notice(__('<strong>Número de teléfono</strong> debe contener exactamente 9 dígitos.'), 'error');
    }
    
    // Validar código postal (solo números)
    if (isset($_POST['billing_postcode']) && !preg_match('/^[0-9]+$/', $_POST['billing_postcode'])) {
        wc_add_notice(__('<strong>Código postal</strong> debe contener solo números.'), 'error');
    }
}

// ==============================================
// 3. Validaciones en JavaScript (frontend)
// ==============================================
add_action('wp_footer', 'agregar_validaciones_js');

function agregar_validaciones_js() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Bloquear caracteres no numéricos en teléfono
                $('#billing_phone').on('keypress', function(e) {
                    return (e.which >= 48 && e.which <= 57) || e.which == 0 || e.which == 8;
                });
                
                // Bloquear caracteres no numéricos en código postal
                $('#billing_postcode').on('keypress', function(e) {
                    return (e.which >= 48 && e.which <= 57) || e.which == 0 || e.which == 8;
                });
            });
        </script>
        <?php
    }
}

// ==============================================
// 4. Deshabilitar secciones innecesarias
// ==============================================
add_filter('woocommerce_enable_order_notes_field', '__return_false'); // Notas de pedido
add_filter('woocommerce_coupons_enabled', '__return_false');          // Cupones