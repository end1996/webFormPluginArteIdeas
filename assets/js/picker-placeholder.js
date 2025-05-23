// Funciones helper para el placeholder (asegúrate de que estén disponibles)
function showSizePickerPlaceholder() {
    const placeholder = document.getElementById('size-picker-placeholder');
    const picker = document.getElementById('size-picker');
    if (placeholder && picker) {
        placeholder.style.display = 'block';
        picker.style.display = 'none';
    }
}

function hideSizePickerPlaceholder() {
    const placeholder = document.getElementById('size-picker-placeholder');
    const picker = document.getElementById('size-picker');
    if (placeholder && picker) {
        placeholder.style.display = 'none';
        picker.style.display = 'block';
    }
}