<?php
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