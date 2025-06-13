// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Crear un elemento <style>
    const style = document.createElement('style');
    style.type = 'text/css';
    
    // Definir los estilos CSS
    style.innerHTML = `
        .elementor-menu-cart__subtotal {
            padding-bottom: var(--product-divider-gap, 10px) !important;
            font-weight: 400 !important;
            padding-top: 0 !important;
        }
        
        .elementor-menu-cart__footer-buttons {
            padding-top: 10px !important;
            padding-bottom: 10px !important;
        }
    `;
    
    // Añadir al <head> del documento
    document.head.appendChild(style);
});