let priceParam = 0;
let priceBase = 'base';
let marcoSeleccionado = null;
priceDimensionsBaseMain = null;

// EVENTO SELECCION DE MARCO PRECIO ----------------------------------------------------------------------------------------------------------

function calcularPrecioMarco(price) {

    console.log(priceDimensionsBaseMain)
    marcoSeleccionado = price;
    // Exponer el precio del marco seleccionado a la variable global marcoSeleccionado
    window.marcoSeleccionado = marcoSeleccionado;

    //alert(marcoSeleccionado)

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

function calcularPrecioSinMarco() {
    document.getElementById('total-price-button').innerHTML = 'S/. ' + priceDimensionsBaseMain;

    document.querySelectorAll('.swiper-slide.selected').forEach(el => {
        el.classList.remove('selected');
    });

    marcoSeleccionado = 0;
}

// EVENTO VOLVER AL PRECIO ORIGINAL AL SELECCIONAR SIN MARCOS ----------------------------------------------------------------------------