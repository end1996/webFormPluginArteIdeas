<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
       
    </style>
</head>

<body>
    <div id="print-image-form" class="print-image-form">
        <div class="upload-area" draggable="true" id="upload-area">
            <div class="upload-area-elements" id="upload-elements">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#e76f51" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <polyline points="21 15 16 10 5 21" />
                </svg>
                <p class="desktop-upload-text">Arrastra tus imágenes aquí o haz clic para subir</p>
                <p class="mobile-upload-text">Sube tus imágenes aquí</p>
                <input type="file" id="file-input" multiple accept="image/*" style="display: none;" />
            </div>

            <div class="images-grid" id="images-grid">
                <!-- Las imágenes subidas aparecerán aquí -->
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                <label>Cantidad</label>
                <div class="quantity-field">
                    <input type="number" value="1" min="1" max="1000" step="1">
                </div>
            </div>
        </div>

        <!-- Sección de tamaños  -->
        <div class="section">
            <div class="section-title">
                <label>Tamaño:</label>
                <div class="radio-group" id="size-options">
                    <label class="radio-label">
                        <input type="radio" name="size-type" class="radio-input" checked
                            onclick="toggleSizeOptions('standard')">
                        Estándar
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="size-type" class="radio-input" onclick="toggleSizeOptions('custom')">
                        Personalizado
                    </label>
                </div>
            </div>
            <div id="standard-sizes" class="ios-picker-container" style="display: block;">
                <!-- Placeholder que se mostrará mientras carga el iOS picker -->
                <div class="picker-placeholder placeholder-glow" id="size-picker-placeholder" style="display: none;">
                    <div class="placeholder placeholder-100"></div>
                    <div class="placeholder placeholder-100"></div>
                    <div class="placeholder placeholder-100"></div>
                </div>
                <div id="size-picker">
                    <!-- Contenedor para el iOSPicker -->
                </div>
            </div>

            <div id="custom-size" class="custom-size" style="display: none;">
                <input id="custom-size__width" type="number" placeholder="Ancho (cm)" min="10" max="60" step="1">
                <input id="custom-size__height" type="number" placeholder="Alto (cm)" min="15" max="100" step="1">
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">
            Indicaciones de impresión
        </div>

        <textarea class="comment-area" placeholder=" Escribe aquí tus comentarios..." maxlength="255"
            required></textarea>
    </div>

    <div class="section">
        <div class="section-title">
            <div class="radio-group">
                <input id='noFrame' type="radio" name="frame" class="radio-input" checked
                    onclick="toggleFrameOptions(false); calcularPrecioSinMarco()">
                <label class="radio-label">Sin marco</label>
                <input type="radio" name="frame" class="radio-input" onclick="toggleFrameOptions(true)">
                <label class="radio-label">Con marco</label>
            </div>
        </div>
    </div>
    <div id="frame-ios-picker-wrapper" class="frames container" style="display: none">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper" id="frame-picker">
            </div>
        </div>
    </div>
    <div class="price-estimate-section" id="price-estimate" style="display: none;">
        <div class="section-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            Precio estimado
        </div>
        <div class="price-display">
            <span id="estimated-price">-</span>
        </div>
    </div>
    <button class="add-to-cart-btn">Agregar al carro <span id="total-price-button">S/. 0.00</span></button>
</body>
<script type="module">
    window.siteUrl = '<?php echo home_url() ?>';
    console.log(window.siteUrl);

    getFrames('<?php echo plugin_dir_url(__FILE__); ?>');
</script>

</html>