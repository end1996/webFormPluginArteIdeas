let priceParam = 0;
let priceBase = 'base';
let marcoSeleccionado = 0; // 0 es el precio del marco sin seleccionar
priceDimensionsBaseMain = null;


// EVENTOS DE CAMBIO EN LA CANTIDAD ----------------------------------------------------------------------------------------------------------

function cambioCantidad(){

    let cantidad = parseInt(document.getElementById('iptCantidad').value)

    if ( cantidad > 4 && cantidad <=12 ) {
        getDimensions(0.12)
    }
    else if ( cantidad > 12 && cantidad <=100 ){
        getDimensions(0.0855)
    }
    else if ( cantidad > 100 ){
        getDimensions(0.020)
    }
    else {
        getDimensions(0.20)
    }

}

// EVENTOS DE CAMBIO EN LA CANTIDAD ----------------------------------------------------------------------------------------------------------

// EVENTO SELECCION DE MARCO PRECIO ----------------------------------------------------------------------------------------------------------

function calcularPrecioMarco(price) {

    marcoSeleccionado = price;
    window.marcoSeleccionado = marcoSeleccionado; // Enviar el precio del marco seleccionado a main.js

    let precioDimensiones = parseFloat(priceDimensionsBaseMain);

    let precioTotal = (precioDimensiones + marcoSeleccionado).toFixed(2);

    document.getElementById('total-price-button').innerText = `S/. ${precioTotal}`;
}

// EVENTO SELECCION DE MARCO PRECIO ----------------------------------------------------------------------------------------------------------

document.getElementById("size-picker").addEventListener("click", function (e) {
    if (e.target.classList.contains("carousel-item")) {
        if ( priceDimensionsBaseMain != null && marcoSeleccionado != null ){

            let priced = parseFloat( priceDimensionsBaseMain );
            let pricem = parseFloat( marcoSeleccionado );

            console.log("precio dimension: " + priced + "\nprecio marco: " + pricem)

            let btnTotalPrice = ( pricem + priced ).toFixed(2);

            console.log("precio total: "+ btnTotalPrice)

            document.getElementById('total-price-button').innerText = `S/. ${ btnTotalPrice }`;
        }
    }
});
  
  
// EVENTO REINICIAR PRECIOS ----------------------------------------------------------------------------------------------------------

function reiniciarPrecioMarco() {
    priceBase = 'base';
    priceParam = 0;
}

// EVENTO REINICIAR PRECIOS ----------------------------------------------------------------------------------------------------------

// EVENTO VOLVER AL PRECIO ORIGINAL AL SELECCIONAR SIN MARCOS ----------------------------------------------------------------------------

// ... existing code ...
function calcularPrecioSinMarco() {
    // Asegurarse de que priceDimensionsBaseMain tenga un valor válido
    if (priceDimensionsBaseMain === null) {
        document.getElementById('total-price-button').innerHTML = 'S/. 0.00';
    } else {
        document.getElementById('total-price-button').innerHTML = 'S/. ' + priceDimensionsBaseMain;
    }

    // Remover la selección de todos los marcos
    document.querySelectorAll('.swiper-slide.selected').forEach(el => {
        el.classList.remove('selected');
    });

    // Reiniciar el precio del marco
    marcoSeleccionado = 0;
    window.marcoSeleccionado = 0; // Asegurarse de que también se actualice en el ámbito global
}
// ... existing code ...

// EVENTO VOLVER AL PRECIO ORIGINAL AL SELECCIONAR SIN MARCOS ----------------------------------------------------------------------------