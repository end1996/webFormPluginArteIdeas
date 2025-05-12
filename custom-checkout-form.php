<?php
// =====================
// 1. Campos Personalizados (Teléfono y Ubicación)
// =====================
add_filter('woocommerce_checkout_fields', 'campos_personalizados_checkout');
function campos_personalizados_checkout($fields) {
    // Eliminar campos innecesarios
    unset($fields['billing']['billing_phone']);
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['shipping']['shipping_company']);

    // --- NOMBRES Y APELLIDOS EN LA PRIMERA FILA ---
    $fields['billing']['billing_first_name']['class'] = array('form-row-first', 'custom-two-col');
    $fields['billing']['billing_first_name']['priority'] = 10;
    $fields['billing']['billing_last_name']['class'] = array('form-row-last', 'custom-two-col');
    $fields['billing']['billing_last_name']['priority'] = 11;

    // --- TELÉFONO EN LA SEGUNDA FILA ---
    $fields['billing']['billing_phone_code'] = array(
        'label'       => __('Código de área', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-first', 'custom-two-col'),
        'type'        => 'select',
        'options'     => array(
            '+51' => 'Perú (+51)',
            '+56' => 'Chile (+56)'
        ),
        'priority'    => 12
    );
    $fields['billing']['billing_phone_number'] = array(
        'label'       => __('Número de teléfono', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-last', 'custom-two-col'),
        'type'        => 'tel',
        'inputmode'   => 'numeric',
        'pattern'     => '[0-9]*',
        'maxlength'   => '9',
        'placeholder' => __('Ej: 987654321', 'woocommerce'),
        'priority'    => 13
    );

    // --- UBICACIÓN ---
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_postcode']);
    // Perú
    $fields['billing']['billing_peru_departamento'] = array(
        'label'       => __('Departamento', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-first', 'peru-field', 'ubicacion-field'),
        'priority'    => 70,
        'input_class' => array('input-text')
    );
    $fields['billing']['billing_peru_provincia'] = array(
        'label'       => __('Provincia', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-wide', 'peru-field', 'ubicacion-field'),
        'priority'    => 71,
        'input_class' => array('input-text')
    );
    $fields['billing']['billing_peru_distrito'] = array(
        'label'       => __('Distrito', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-last', 'peru-field', 'ubicacion-field'),
        'priority'    => 72,
        'input_class' => array('input-text')
    );
    // Chile
    $fields['billing']['billing_chile_region'] = array(
        'label'       => __('Región', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-first', 'chile-field', 'ubicacion-field'),
        'priority'    => 70,
        'input_class' => array('input-text')
    );
    $fields['billing']['billing_chile_provincia'] = array(
        'label'       => __('Provincia', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-wide', 'chile-field', 'ubicacion-field'),
        'priority'    => 71,
        'input_class' => array('input-text')
    );
    $fields['billing']['billing_chile_comuna'] = array(
        'label'       => __('Comuna', 'woocommerce'),
        'required'    => true,
        'class'       => array('form-row-last', 'chile-field', 'ubicacion-field'),
        'priority'    => 72,
        'input_class' => array('input-text')
    );

    // --- REORDENAR ARRAY DE CAMPOS ---
    $ordered_fields = array();
    $ordered_keys = array(
        'billing_first_name',
        'billing_last_name',
        'billing_phone_code',
        'billing_phone_number',
    );
    // Añadir los campos en el orden deseado
    foreach ($ordered_keys as $key) {
        if (isset($fields['billing'][$key])) {
            $ordered_fields[$key] = $fields['billing'][$key];
        }
    }
    // Añadir el resto de los campos
    foreach ($fields['billing'] as $key => $field) {
        if (!isset($ordered_fields[$key])) {
            $ordered_fields[$key] = $field;
        }
    }
    $fields['billing'] = $ordered_fields;
    return $fields;
}

// =====================
// 2. Scripts de Visibilidad por País
// =====================
add_action('wp_footer', 'control_visibilidad_campos_personalizados');
function control_visibilidad_campos_personalizados() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function actualizarCamposUbicacion() {
                var pais = $('#billing_country').val();
                $('.ubicacion-field').hide().removeClass('visible-field');
                if (pais === 'PE') {
                    $('.peru-field').show().addClass('visible-field');
                    $('.peru-field').removeClass('form-row-first form-row-wide form-row-last');
                    $('.billing_peru_departamento').addClass('form-row-first');
                    $('.billing_peru_provincia').addClass('form-row-wide');
                    $('.billing_peru_distrito').addClass('form-row-last');
                } else if (pais === 'CL') {
                    $('.chile-field').show().addClass('visible-field');
                    $('.chile-field').removeClass('form-row-first form-row-wide form-row-last');
                    $('.billing_chile_region').addClass('form-row-first');
                    $('.billing_chile_provincia').addClass('form-row-wide');
                    $('.billing_chile_comuna').addClass('form-row-last');
                }
                $('body').trigger('resize');
            }
            $('#billing_country').change(actualizarCamposUbicacion);
            $(document).on('updated_checkout', actualizarCamposUbicacion);
            actualizarCamposUbicacion();
        });
        </script>
        <?php
    }
}

// =====================
// 3. Estilos CSS Unificados
// =====================
add_action('wp_head', 'estilos_personalizados_checkout');
function estilos_personalizados_checkout() {
    if (is_checkout()) {
        echo '<style>
            /* Teléfono */
            #billing_phone_code_field select {
                padding: 12px;
                height: auto;
            }
            #billing_phone_number_field input {
                padding: 12px;
            }
            /* Forzar dos columnas para las filas personalizadas usando IDs */
            #billing_first_name_field, #billing_phone_code_field {
                width: 48% !important;
                float: left !important;
                margin-right: 4% !important;
                clear: none !important;
            }
            #billing_last_name_field, #billing_phone_number_field {
                width: 48% !important;
                float: right !important;
                margin-right: 0 !important;
                clear: none !important;
            }
            @media (max-width: 768px) {
                #billing_first_name_field, #billing_last_name_field,
                #billing_phone_code_field, #billing_phone_number_field {
                    width: 100% !important;
                    float: none !important;
                    margin-right: 0 !important;
                }
            }
            /* Ubicación */
            .ubicacion-field {
                display: none;
                clear: both;
                margin-bottom: 15px;
            }
            .visible-field {
                display: block !important;
            }
            .woocommerce-billing-fields .form-row {
                width: 100%;
                float: none;
                margin: 0 0 15px 0;
                padding: 0;
                clear: both;
            }
            .woocommerce form .form-row input.input-text,
            .woocommerce form .form-row select {
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box !important;
                padding: 12px !important;
                height: auto !important;
                line-height: 1.5 !important;
            }
            @media (min-width: 768px) {
                .form-row-first {
                    width: 30% !important;
                    float: left !important;
                    margin-right: 5% !important;
                    clear: none !important;
                }
                .form-row-wide {
                    width: 30% !important;
                    float: left !important;
                    margin-right: 5% !important;
                    clear: none !important;
                }
                .form-row-last {
                    width: 30% !important;
                    float: right !important;
                    margin-right: 0 !important;
                    clear: none !important;
                }
            }
            @media (max-width: 768px) {
                #billing_phone_code_field, 
                #billing_phone_number_field {
                    width: 100% !important;
                    float: none !important;
                    margin-right: 0 !important;
                }
            }
            .select2-container {
                width: 100% !important;
                z-index: 999999 !important;
            }
        </style>';
    }
}

// =====================
// 4. Validación de Campos
// =====================
add_action('woocommerce_checkout_process', 'validar_campos_personalizados');
function validar_campos_personalizados() {
    // Teléfono
    $codigo = isset($_POST['billing_phone_code']) ? $_POST['billing_phone_code'] : '';
    $numero = isset($_POST['billing_phone_number']) ? $_POST['billing_phone_number'] : '';
    if ($codigo === '+51' && strlen($numero) !== 9) {
        wc_add_notice(__('El número de teléfono peruano debe tener 9 dígitos', 'woocommerce'), 'error');
    }
    if ($codigo === '+56' && strlen($numero) !== 9) {
        wc_add_notice(__('El número de teléfono chileno debe tener 9 dígitos', 'woocommerce'), 'error');
    }
    if (!empty($numero) && !preg_match('/^[0-9]+$/', $numero)) {
        wc_add_notice(__('El número de teléfono solo puede contener dígitos', 'woocommerce'), 'error');
    }
    // Ubicación
    $pais = isset($_POST['billing_country']) ? $_POST['billing_country'] : '';
    if ($pais === 'PE') {
        if (empty($_POST['billing_peru_departamento'])) {
            wc_add_notice(__('Por favor selecciona tu departamento', 'woocommerce'), 'error');
        }
        if (empty($_POST['billing_peru_provincia'])) {
            wc_add_notice(__('Por favor selecciona tu provincia', 'woocommerce'), 'error');
        }
        if (empty($_POST['billing_peru_distrito'])) {
            wc_add_notice(__('Por favor selecciona tu distrito', 'woocommerce'), 'error');
        }
    } elseif ($pais === 'CL') {
        if (empty($_POST['billing_chile_region'])) {
            wc_add_notice(__('Por favor selecciona tu región', 'woocommerce'), 'error');
        }
        if (empty($_POST['billing_chile_provincia'])) {
            wc_add_notice(__('Por favor selecciona tu provincia', 'woocommerce'), 'error');
        }
        if (empty($_POST['billing_chile_comuna'])) {
            wc_add_notice(__('Por favor selecciona tu comuna', 'woocommerce'), 'error');
        }
    }
}

// =====================
// 5. Guardar Campos Personalizados
// =====================
add_action('woocommerce_checkout_update_order_meta', 'guardar_campos_personalizados');
function guardar_campos_personalizados($order_id) {
    // Teléfono
    if (!empty($_POST['billing_phone_code'])) {
        update_post_meta($order_id, '_billing_phone_code', sanitize_text_field($_POST['billing_phone_code']));
    }
    if (!empty($_POST['billing_phone_number'])) {
        update_post_meta($order_id, '_billing_phone_number', sanitize_text_field($_POST['billing_phone_number']));
    }
    // Ubicación
    $pais = isset($_POST['billing_country']) ? $_POST['billing_country'] : '';
    if ($pais === 'PE') {
        if (!empty($_POST['billing_peru_departamento'])) {
            update_post_meta($order_id, '_billing_peru_departamento', sanitize_text_field($_POST['billing_peru_departamento']));
        }
        if (!empty($_POST['billing_peru_provincia'])) {
            update_post_meta($order_id, '_billing_peru_provincia', sanitize_text_field($_POST['billing_peru_provincia']));
        }
        if (!empty($_POST['billing_peru_distrito'])) {
            update_post_meta($order_id, '_billing_peru_distrito', sanitize_text_field($_POST['billing_peru_distrito']));
        }
    } elseif ($pais === 'CL') {
        if (!empty($_POST['billing_chile_region'])) {
            update_post_meta($order_id, '_billing_chile_region', sanitize_text_meta($_POST['billing_chile_region']));
        }
        if (!empty($_POST['billing_chile_provincia'])) {
            update_post_meta($order_id, '_billing_chile_provincia', sanitize_text_field($_POST['billing_chile_provincia']));
        }
        if (!empty($_POST['billing_chile_comuna'])) {
            update_post_meta($order_id, '_billing_chile_comuna', sanitize_text_field($_POST['billing_chile_comuna']));
        }
    }
}

// =====================
// 6. Combinar campos de teléfono para WooCommerce
// =====================
add_action('woocommerce_checkout_process', 'combinar_campos_telefono_unificado');
function combinar_campos_telefono_unificado() {
    if (!empty($_POST['billing_phone_code']) && !empty($_POST['billing_phone_number'])) {
        $_POST['billing_phone'] = $_POST['billing_phone_code'] . $_POST['billing_phone_number'];
    }
}

// =====================
// 7. Mostrar en Admin y Emails
// =====================
add_action('woocommerce_admin_order_data_after_billing_address', 'mostrar_campos_personalizados_admin', 10, 1);
function mostrar_campos_personalizados_admin($order) {
    // Teléfono
    $phone_code = $order->get_meta('_billing_phone_code');
    $phone_number = $order->get_meta('_billing_phone_number');
    if ($phone_code || $phone_number) {
        echo '<p><strong>' . __('Teléfono completo:', 'woocommerce') . '</strong> ' . esc_html($phone_code . ' ' . $phone_number) . '</p>';
    }
    // Ubicación
    $pais = $order->get_billing_country();
    if ($pais === 'PE') {
        echo '<p><strong>' . __('Departamento:', 'woocommerce') . '</strong> ' . $order->get_meta('_billing_peru_departamento') . '</p>';
        echo '<p><strong>' . __('Provincia:', 'woocommerce') . '</strong> ' . $order->get_meta('_billing_peru_provincia') . '</p>';
        echo '<p><strong>' . __('Distrito:', 'woocommerce') . '</strong> ' . $order->get_meta('_billing_peru_distrito') . '</p>';
    } elseif ($pais === 'CL') {
        echo '<p><strong>' . __('Región:', 'woocommerce') . '</strong> ' . $order->get_meta('_billing_chile_region') . '</p>';
        echo '<p><strong>' . __('Provincia:', 'woocommerce') . '</strong> ' . $order->get_meta('_billing_chile_provincia') . '</p>';
        echo '<p><strong>' . __('Comuna:', 'woocommerce') . '</strong> ' . $order->get_meta('_billing_chile_comuna') . '</p>';
    }
}

add_filter('woocommerce_email_order_meta_fields', 'mostrar_telefono_en_emails_unificado', 10, 3);
function mostrar_telefono_en_emails_unificado($fields, $sent_to_admin, $order) {
    $phone_code = $order->get_meta('_billing_phone_code');
    $phone_number = $order->get_meta('_billing_phone_number');
    if ($phone_code && $phone_number) {
        $fields['billing_phone_formatted'] = array(
            'label' => __('Teléfono', 'woocommerce'),
            'value' => $phone_code . ' ' . $phone_number,
        );
    }
    return $fields;
}

// =====================
// 8. Columna personalizada en lista de pedidos
// =====================
add_filter('manage_edit-shop_order_columns', 'añadir_columna_telefono_personalizado_unificado');
function añadir_columna_telefono_personalizado_unificado($columns) {
    $new_columns = array();
    foreach ($columns as $key => $name) {
        $new_columns[$key] = $name;
        if ('billing_address' === $key) {
            $new_columns['billing_phone_formatted'] = __('Teléfono', 'woocommerce');
        }
    }
    return $new_columns;
}
add_action('manage_shop_order_posts_custom_column', 'mostrar_columna_telefono_personalizado_unificado', 10, 2);
function mostrar_columna_telefono_personalizado_unificado($column, $post_id) {
    if ('billing_phone_formatted' === $column) {
        $order = wc_get_order($post_id);
        $phone_code = $order->get_meta('_billing_phone_code');
        $phone_number = $order->get_meta('_billing_phone_number');
        if ($phone_code && $phone_number) {
            echo esc_html($phone_code . ' ' . $phone_number);
        } else {
            echo esc_html($order->get_billing_phone());
        }
    }
}
// =====================
// 9. Formatear teléfono para admin
// =====================
add_filter('woocommerce_order_get_billing_phone', 'formatear_telefono_para_admin_unificado', 10, 2);
function formatear_telefono_para_admin_unificado($phone, $order) {
    $phone_code = $order->get_meta('_billing_phone_code');
    $phone_number = $order->get_meta('_billing_phone_number');
    if ($phone_code && $phone_number) {
        return $phone_code . ' ' . $phone_number;
    }
    return $phone;
}

// Quitar la sección de información adicional (notas del pedido)
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

// Deshabilitar los cupones en el checkout
add_filter( 'woocommerce_coupons_enabled', '__return_false' ); 