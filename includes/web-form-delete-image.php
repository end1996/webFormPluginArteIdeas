<?php
/**
 * WebForm Delete Image
 * 
 * Description: Elimina automaticamente imagenes subidas hace mas de 30 dias
 * @version 2.2
 * @author Piero Jimenez
 */

 // DISPARADORES Y EVENTOS
register_activation_hook(__FILE__, 'lsf_activar');
register_deactivation_hook(__FILE__, 'lsf_desactivar');
add_action('lsf_evento_diario', 'lsf_borrar_imagenes_antiguas');

// FUNCION PARA CREAR EL EVENTO DE BORRADO DIARIO
function lsf_activar() {
    if (!wp_next_scheduled('lsf_evento_diario')) {
        wp_schedule_event(time(), 'daily', 'lsf_evento_diario');
    }
}

// ELIMINA EL EVENTO AL DESACTIVAR EL PLUGIN
function lsf_desactivar() {
    wp_clear_scheduled_hook('lsf_evento_diario');
}

// FUNCION PARA ELIMINAR LAS IMAGENES PASADO 1 MES = 30 DIAS
function lsf_borrar_imagenes_antiguas() {
    $uploads_dir = WP_CONTENT_DIR . '/uploads/';
    $dias = 30;
    $ahora = time();

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploads_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $archivo) {
        if (!is_file($archivo)) continue;

        $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;

        $modificado = filemtime($archivo);
        if (($ahora - $modificado) > $dias * DAY_IN_SECONDS) {
            $url_relativa = str_replace(WP_CONTENT_DIR, '', $archivo);
            $url_completa = content_url($url_relativa);
            $attachment = attachment_url_to_postid($url_completa);

            if ($attachment) continue;

            unlink($archivo);
        }
    }
}