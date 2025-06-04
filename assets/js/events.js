let priceParam = 0;
let priceBase = 'base';
let marcoSeleccionado = 0; // 0 es el precio del marco sin seleccionar
priceDimensionsBaseMain = null;

// EVENTO SELECCION DE MARCO PRECIO ----------------------------------------------------------------------------------------------------------

function calcularPrecioMarco(price) {
    marcoSeleccionado = price;
    window.marcoSeleccionado = marcoSeleccionado; // Enviar el precio del marco seleccionado a main.js
    
    // Actualizar el precio total
    updateTotalPriceWithFrame();
}

// FUNCIÓN PARA ACTUALIZAR PRECIO TOTAL INCLUYENDO MARCO ----------------------------------------------------------------------------------------------------------

function updateTotalPriceWithFrame() {
    const quantity = parseInt(document.querySelector(".quantity-field input").value) || 1;
    
    let precioDimensiones = parseFloat(priceDimensionsBaseMain) || 0;
    let precioMarco = parseFloat(marcoSeleccionado) || 0;
    
    // El precio total es: (precio de dimensiones + precio de marco por unidad) * cantidad
    let precioTotal = ((precioDimensiones / quantity) + precioMarco) * quantity;
    
    document.getElementById('total-price-button').innerText = `S/. ${precioTotal.toFixed(2)}`;
}

// EVENTO SELECCION DE MARCO PRECIO ----------------------------------------------------------------------------------------------------------

document.getElementById("size-picker").addEventListener("click", function (e) {
    if (e.target.classList.contains("carousel-item")) {
        // Usar la nueva función que incluye el marco
        updateTotalPriceWithFrame();
    }
});
  
// EVENTO REINICIAR PRECIOS ----------------------------------------------------------------------------------------------------------

function reiniciarPrecioMarco() {
    priceBase = 'base';
    priceParam = 0;
}

// EVENTO REINICIAR PRECIOS ----------------------------------------------------------------------------------------------------------

// EVENTO VOLVER AL PRECIO ORIGINAL AL SELECCIONAR SIN MARCOS ----------------------------------------------------------------------------

function calcularPrecioSinMarco() {
    // Remover la selección de todos los marcos
    document.querySelectorAll('.swiper-slide.selected').forEach(el => {
        el.classList.remove('selected');
    });

    // Reiniciar el precio del marco
    marcoSeleccionado = 0;
    window.marcoSeleccionado = 0; // Asegurarse de que también se actualice en el ámbito global
    
    // Actualizar el precio total sin marco
    updateTotalPriceWithFrame();
}

// EVENTO VOLVER AL PRECIO ORIGINAL AL SELECCIONAR SIN MARCOS ----------------------------------------------------------------------------