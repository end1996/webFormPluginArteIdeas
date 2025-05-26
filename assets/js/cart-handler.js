jQuery(document).ready(function($) {
    // Variable para rastrear el valor anterior
    let previousValue = 0;

    // Función principal de validación
    function handleQuantityChange(input) {
        const max = parseInt(input.attr('max')) || 100;
        const currentValue = parseInt(input.val()) || 1;
        
        // Detectar si se está intentando superar el límite
        if (currentValue > max) {
            input.val(max); // Forzar el valor máximo
            showNotification("¡La cantidad máxima permitida es " + max + "!", "error");
            return false;
        }
        
        // Solo actualizar si el valor cambió realmente
        if (currentValue !== previousValue) {
            previousValue = currentValue;
            input.closest('form').find('button[name="update_cart"]').trigger('click');
        }
        
        return true;
    }

    // Evento para cambios manuales
    $(document).on('input change', 'input.qty', function() {
        handleQuantityChange($(this));
    });

    // Capturar clics en los botones nativos (+/-)
    $(document).on('mousedown', 'input.qty[type="number"]', function() {
        previousValue = parseInt($(this).val()) || 1;
    }).on('mouseup', 'input.qty[type="number"]', function() {
        setTimeout(() => handleQuantityChange($(this)), 10);
    });

    // Validación adicional al perder el foco
    $(document).on('blur', 'input.qty', function() {
        handleQuantityChange($(this));
    });
});