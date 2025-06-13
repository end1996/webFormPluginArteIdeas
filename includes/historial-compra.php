<?php
/**
 * Historial de compras
 * 
 * Agrega una pestaña "Historial de compras" en la página Mi Cuenta de WooCommerce.
 * @version 2.7
 * @author Robin Monroy
 */

// Registrar endpoint personalizado
add_action( 'init', 'add_purchased_products_endpoint' );
function add_purchased_products_endpoint() {
    add_rewrite_endpoint( 'productos-adquiridos', EP_ROOT | EP_PAGES );
}

// Enlace en el menú de Mi Cuenta
add_filter( 'woocommerce_account_menu_items', 'add_purchased_products_link' );
function add_purchased_products_link( $items ) {
    $new_items = [];
    foreach ( $items as $key => $value ) {
        $new_items[ $key ] = $value;
        if ( 'orders' === $key ) {
            $new_items['productos-adquiridos'] = 'Historial de compras';
        }
    }
    return $new_items;
}

add_action( 'woocommerce_account_productos-adquiridos_endpoint', 'purchased_products_content' );
// Contenido del endpoint
function purchased_products_content() {
    // Obtener valores de filtros desde la URL
    $order_sort = isset( $_GET['orden_fecha'] ) && $_GET['orden_fecha'] === 'asc' ? 'ASC' : 'DESC';
    $estado_filtro = isset( $_GET['estado_pedido'] ) ? sanitize_text_field( $_GET['estado_pedido'] ) : '';

    // Obtener todos los estados de pedido disponibles
    $estados_pedido = wc_get_order_statuses();

    // Formulario de filtros
    echo '<form method="get" class="historial-filtros-form" style="margin-bottom: 20px; ">';
    echo '<input type="hidden" name="productos-adquiridos" value="1">'; // Mantener el endpoint activo

    // Selector de orden por fecha
    echo '<label for="orden_fecha" style="font-size: 17px;">Orden fecha:</label>';
    echo '<select name="orden_fecha" onchange="this.form.submit()">';
    echo '<option value="desc"' . selected( $order_sort, 'DESC', false ) . '>Más recientes </option>';
    echo '<option value="asc"' . selected( $order_sort, 'ASC', false ) . '>Más antiguos </option>';
    echo '</select>';

    // Selector de estado del pedido
    echo '<label for="estado_pedido" style="font-size: 17px;">Estado pedido:</label>';
    echo '<select name="estado_pedido" onchange="this.form.submit()">';
    echo '<option value="">Todos</option>';
    foreach ( $estados_pedido as $slug => $label ) {
        $selected = selected( $estado_filtro, str_replace( 'wc-', '', $slug ), false );
        echo '<option value="' . esc_attr( str_replace( 'wc-', '', $slug ) ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
    echo '</form>';

    // Armar los parámetros para wc_get_orders
    $args = array(
        'customer_id' => get_current_user_id(),
        'orderby'     => 'date',
        'order'       => $order_sort,
    );

    if ( ! empty( $estado_filtro ) ) {
        $args['status'] = array( $estado_filtro );
    } 

    $customer_orders = wc_get_orders( $args );

    // Mostrar los pedidos
    if ( $customer_orders ) {
        echo '<table class="woocommerce-table shop_table shop_table_responsive">';
        echo '<thead><tr><th>Imagen</th><th>Producto</th><th>Precio</th><th>Número de Pedido</th></tr></thead><tbody>';

        foreach ( $customer_orders as $order ) {
            foreach ( $order->get_items( 'line_item' ) as $item ) {
                //Vista para ver si se reciben los datos de las imagenes
                /*echo '<pre>';
                    print_r( $item->get_meta_data() );
                echo '</pre>';*/

                if ( is_callable( [ $item, 'get_product' ] ) ) {
                    // Indicamos que $item es un producto del pedido para que el editor reconozca sus métodos    
                    /** @var WC_Order_Item_Product $item */
                    $product = $item->get_product();
                    if ( $product ) {
                        echo '<tr>';

                        // Obtener todos los metadatos del ítem
                        $meta_data = $item->get_meta_data();

                        // Buscar todas las imágenes personalizadas
                        $imagenes_personalizadas = [];
                        foreach ( $meta_data as $meta ) {
                            $key = $meta->get_data()['key'];
                            $value = $meta->get_data()['value'];

                            if ( strpos( $key, '_imagen_personalizada_' ) === 0 ) {
                                $imagenes_personalizadas[] = esc_url( $value );
                            }
                        }

                        // Mostrar las imágenes
                        if ( ! empty( $imagenes_personalizadas ) ) {
                            echo '<td data-label="Imagen">';
                            if ( count( $imagenes_personalizadas ) <= 2 ) {
                                // Solo una imagen: alineada a la derecha
                                echo '<div class="imagenes-personalizadas-grid" style="display: flex; justify-content: flex-end; max-height: 160px; overflow-y: auto;">';
                                foreach ( $imagenes_personalizadas as $img_url ) {
                                    echo '<div style="margin: 2px;"><img src="' . $img_url . '" alt="Imagen personalizada" class="img-personalizada-modal" data-img="' . $img_url . '" style="width: 70px; height: 70px; object-fit: cover; cursor:pointer;" /></div>';
                                }
                                echo '</div>';
                            } else {
                                // Varias imágenes: grid alineado a la derecha
                                echo '<div class="imagenes-personalizadas-grid" style="display: grid; grid-template-columns: repeat(3, 70px); gap: 5px; max-height: 160px; overflow-y: auto; justify-content: end;">';
                                foreach ( $imagenes_personalizadas as $img_url ) {
                                    echo '<div style="margin: 2px;"><img src="' . $img_url . '" alt="Imagen personalizada" class="img-personalizada-modal" data-img="' . $img_url . '" style="width: 70px; height: 70px; object-fit: cover; cursor:pointer;" /></div>';
                                }
                                echo '</div>';
                            }
                            echo '</td>';
                        } else {
                            // Imagen predeterminada si no hay imágenes personalizadas
                            echo '<td>' . $product->get_image( 'thumbnail' ) . '</td>';
                        }
                        
                        // Nombre del producto
                        echo '<td data-label="Producto"><a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $item->get_name() ) . '</a></td>';

                        // Precio
                        $total_pedido = $order->get_total();
                        echo '<td data-label="Precio">' . wc_price( $total_pedido ) . '</td>';

                        // Pedido y fecha
                        echo '<td data-label="Número de Pedido"><a href="' . esc_url( $order->get_view_order_url() ) . '">' . esc_html( $order->get_order_number() ) . '</a><br>' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . '</td>';

                        echo '</tr>';
                    }
                }
            }
        }

        echo '</tbody></table>';
        // Modal para mostrar la imagen grande
        ?>
        <div id="modal-imagen-personalizada" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); align-items:center; justify-content:center;">
            <img id="modal-img-personalizada" src="" alt="Imagen ampliada" style="max-width:90vw; max-height:90vh; border:5px solid #fff; border-radius:8px; box-shadow:0 0 20px #000;">
        </div>
        <?php
    } else {
        echo '<p>No se han encontrado productos adquiridos con los filtros aplicados.</p>';
    }
}

// Título del endpoint
add_filter( 'woocommerce_endpoint_productos-adquiridos_title', 'purchased_products_title' );
function purchased_products_title( $title ) {
    return 'Historial de compras';
}

// Guardar imágenes personalizadas en el pedido - actualizacion permite leer los arrays
add_action( 'woocommerce_checkout_create_order_line_item', 'guardar_imagenes_en_pedido', 10, 4 );
function guardar_imagenes_en_pedido( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['print_image_data'] ) && is_array( $values['print_image_data'] ) ) {
        foreach ( $values['print_image_data'] as $index => $image_data ) {
            if ( isset( $image_data['image_url'] ) ) {
                // Guarda cada imagen personalizada como metadato numerado del producto
                $item->add_meta_data( '_imagen_personalizada_' . ( $index + 1 ), esc_url_raw( $image_data['image_url'] ), false );
            }
        }
    }
}

// Asegurar que las reglas de reescritura se apliquen
register_activation_hook( __FILE__, 'activar_historial_compras' );
function activar_historial_compras() {
    add_purchased_products_endpoint();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'desactivar_historial_compras' );
function desactivar_historial_compras() {
    flush_rewrite_rules();
}

// Ocultar URL de imágenes personalizadas
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'ocultar_imagenes_personalizadas_backend', 10, 2 );
function ocultar_imagenes_personalizadas_backend( $formatted_meta, $item ) {
    foreach ( $formatted_meta as $key => $meta ) {
        if ( strpos( $meta->key, '_imagen_personalizada_' ) === 0 ) {
            unset( $formatted_meta[ $key ] );
        }
    }
    return $formatted_meta;
}

add_action('wp_enqueue_scripts', function() {
    if (is_account_page()) {
        wp_enqueue_style(
            'historial-compra',
            plugins_url('assets/css/historial-compra.css', __FILE__),
            [],
            '1.3'
        );
        wp_enqueue_script(
            'historial-compra-modal',
            plugins_url('assets/js/vista-modal.js', __FILE__),
            [],
            '1.0',
            true
        );
    }
});
