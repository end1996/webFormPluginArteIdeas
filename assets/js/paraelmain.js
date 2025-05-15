let priceDimensionsBaseMain; // Declara esta variable en el main

//Y EN LA ULTIMA FUNCION AGREGA SUS INSTANCIAS
// Función para calcular el precio total
function updateTotalPrice() {
  if (uploadedImagesData.length === 0) {
    document.getElementById("total-price-button").innerText = `S/. 0.00`;
    return;
  }

  const quantity =
    parseInt(document.querySelector(".quantity-field input").value) || 1;
  let pricePerUnit = 0;

  if (selectedOptionSize === "custom") {
    const width =
      parseInt(document.getElementById("custom-size__width").value) || 0;
    const height =
      parseInt(document.getElementById("custom-size__height").value) || 0;

    if (width > 0 && height > 0) {
      // Primero calculamos el precio según la fórmula
      pricePerUnit = Math.round(height * width * 0.03);

      const totalPrice = pricePerUnit * quantity;
      priceDimensionsBaseMain = totalPrice.toFixed(2); // ------------> ESTO DE AQUI :V

      document.getElementById(
        "total-price-button"  
      ).innerText = `S/. ${totalPrice.toFixed(2)}`;
      return;
    } else {
      document.getElementById("total-price-button").innerText = `S/. 0.00`;
      return;
    }
  } else {
    //Si se utilice los pickers
    selectedSize = sizePicker.getValue();

    if (selectedSize) {
      pricePerUnit = currentSizePrices[selectedSize] || 0;
    }

    const totalPrice = pricePerUnit * quantity;

    priceDimensionsBaseMain = totalPrice.toFixed(2); // ------------> ESTO DE AQUI :V

    // Update total price text on button
    document.getElementById(
      "total-price-button"
    ).innerText = `S/. ${totalPrice.toFixed(2)}`;
  }
}