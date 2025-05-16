// Variable global para seleccion de tamaños y almacenar múltiples imágenes
var selectedOptionSize;
let currentSelectedFrame = "frame1"; // Valor por defecto para el marco
let uploadedImagesData = []; // Array para almacenar múltiples imágenes
let sizePicker;
let selectedSize;
let currentSizePrices;
let selectedSizeId;
let quantity_condition_text = [1, 4, 12, 100]

let precios_por_cantidad = {}; 
let dimension_precio_data = {};

let priceDimensionsBaseMain;
let cantidad_index;
let variation_id = 0;

document.addEventListener("DOMContentLoaded", function () {
  getDimensions();
  setupEventListeners();
  setupCommentLengthChecker();

  updateTotalPrice();
});

function initializeSizePicker() {
  const quantityInput = document.querySelector(".quantity-field input");
  const sizePickerContainer = document.getElementById("size-picker");

  function getCurrentSizePrices(quantity) {
    let sizePriceDynamic;
    // PRECIOS POR CANTIDAD DINAMICO

    cantidad_index = quantity;

    console.log(precios_por_cantidad)
    console.log(dimension_precio_data);

    for( let pQuantity in precios_por_cantidad ){
      
      let parsedQuantity = parseInt(pQuantity);

      let isGreater = parsedQuantity === 1 ? parsedQuantity <= quantity : parsedQuantity < quantity;

      if ( isGreater ) {
        sizePriceDynamic = precios_por_cantidad[pQuantity];
      }

    }

    return sizePriceDynamic;
  }

  function updateSizeOptions(quantity) {
    currentSizePrices = getCurrentSizePrices(quantity);
    const sizeOptions = Object.keys(currentSizePrices).map((value) => ({
      value: value,
      label: `${value.replace(
        "X",
        "cm Ancho X "
      )}cm Alto => (S/.${currentSizePrices[value]})`,
    }));

    // Guardar el valor actual antes de destruir
    const previousValue = selectedSize;

    // Si ya existe, destruir el picker
    if (sizePicker) {
      sizePicker.destroy();
    }

    // Crear el picker con el valor anterior si existe, si no, usar el inicial
    sizePicker = new iOSPicker({
      container: sizePickerContainer,
      items: sizeOptions,
      initialValue: previousValue,
      onChange: (value) => {
        selectedSize = value; // Guardar nuevo valor seleccionado
        revalidateImagesWithNewSize(value);
        updateTotalPrice();
        reiniciarPrecioMarco();
      },
    });
  }

  // Inicializar con cantidad 1
  updateSizeOptions(1);

  // Escuchar cambios en cantidad
  quantityInput.addEventListener("input", function () {
    const quantity = parseInt(this.value) || 1;
    console.log(`Cantidad ingresada: ${quantity}`);
    updateSizeOptions(quantity);
  });
}

function initializeFramePicker() {
  const framePicker = document.getElementById("frame-picker");
  if (!framePicker) return;

  const pickerItems = framePicker.querySelectorAll(".swiper-slide");
  pickerItems.forEach((item) => {
    item.addEventListener("click", function () {
      pickerItems.forEach((i) => i.classList.remove("selected"));
      item.classList.add("selected");
      currentSelectedFrame = item.dataset.value;
    });
  });
}

function setupCommentLengthChecker() {
  const commentArea = document.querySelector(".comment-area");

  commentArea.addEventListener("input", function () {
    const maxLength = 255;
    const currentLength = this.value.length;

    if (currentLength >= maxLength) {
      showNotification(
        "Has alcanzado el límite máximo de 255 caracteres en los comentarios.",
        "error"
      );
    }
  });
}

function setupEventListeners() {
  const quantityInput = document.querySelector(".quantity-field input");

  quantityInput.addEventListener("focus", function () {
    if (this.value === "1") {
      this.value = "";
    }
  });

  quantityInput.addEventListener("input", function () {
    let value = parseInt(this.value);

    // Si es mayor a 1000, vuelve al mismo valor
    if (value > 1000) {
      this.value = 1000;
      showNotification(
        "La cantidad no puede ser mayor a 1000 unidades",
        "error"
      );
    }

    updateTotalPrice();
    reiniciarPrecioMarco();
  });

  quantityInput.addEventListener("blur", function () {
    let value = parseInt(this.value);

    if (isNaN(value) || value < 1) {
      this.value = 1;
    }
  });

  document
    .getElementById("custom-size__width")
    .addEventListener("input", handleCustomSize);
  document
    .getElementById("custom-size__height")
    .addEventListener("input", handleCustomSize);
  document
    .querySelector(".add-to-cart-btn")
    .addEventListener("click", addToCart);
}

function handleCustomSize() {
  const widthInput = document.getElementById("custom-size__width");
  const heightInput = document.getElementById("custom-size__height");

  // ANCHO
  widthInput.addEventListener("focus", function () {
    if (this.value === "10") this.value = "";
  });

  widthInput.addEventListener("input", function () {
    let value = parseInt(this.value);
    if (value > 60) {
      this.value = 60;
      showNotification("El ancho no puede ser mayor a 60 cm.", "error");
    }
    updateTotalPrice();
    reiniciarPrecioMarco();
  });

  widthInput.addEventListener("blur", function () {
    let value = parseInt(this.value);
    if (isNaN(value) || value < 10) {
      this.value = 10;
      showNotification("El ancho no puede ser menor a 10 cm.", "error");
    }
    updateTotalPrice();
    reiniciarPrecioMarco();
  });

  // ALTO
  heightInput.addEventListener("focus", function () {
    if (this.value === "15") this.value = "";
  });

  heightInput.addEventListener("input", function () {
    let value = parseInt(this.value);
    if (value > 100) {
      this.value = 100;
      showNotification("El alto no puede ser mayor a 100 cm.", "error");
    }
    updateTotalPrice();
    reiniciarPrecioMarco();
  });

  heightInput.addEventListener("blur", function () {
    let value = parseInt(this.value);
    if (isNaN(value) || value < 15) {
      this.value = 15;
      showNotification("El alto no puede ser menor a 15 cm.", "error");
    }
    updateTotalPrice();
    reiniciarPrecioMarco();
  });

  document
    .querySelector(".add-to-cart-btn")
    .addEventListener("click", addToCart);
}

// Función para alternar entre opciones de tamaño estándar y personalizado
function toggleSizeOptions(type) {
  if (type === "standard") {
    document.getElementById("standard-sizes").style.display = "block";
    document.getElementById("custom-size").style.display = "none";
    this.selectedOptionSize = "standard";
    updateTotalPrice(); 
    reiniciarPrecioMarco();
  } else {
    document.getElementById("standard-sizes").style.display = "none";
    document.getElementById("custom-size").style.display = "flex";
    this.selectedOptionSize = "custom";

    // Entrada de tamaño personalizado
    const widthInput = document.getElementById("custom-size__width");
    const heightInput = document.getElementById("custom-size__height");
    if (widthInput) widthInput.value = '';
    if (heightInput) heightInput.value = '';
    
    // Inicializa el precio en 0.00
    document.getElementById("total-price-button").innerText = `S/. 0.00`;
  }
}

// Función para inicializar el visor de imágenes (Viewer.js) para las imágenes del Swiper
function initializeViewerForSwiper() {
  const slides = document.querySelectorAll(".swiper-slide");

  slides.forEach((slide) => {
    const img = slide.querySelector("img"); // Busca la imagen dentro del slide
    const btn = slide.querySelector(".zoom-icon-btn"); // Busca el botón con clase .zoom-icon-btn dentro del slide

    if (btn && img) {
      let viewerInstance = null;

      // Elimina cualquier listener previo antes de agregar uno nuevo
      btn.removeEventListener("click", btn._viewerClickHandler);

      // Define el handler como una propiedad del botón para evitar duplicados
      btn._viewerClickHandler = function (event) {
        event.stopPropagation();

        // Evita crear múltiples viewers
        if (viewerInstance) {
          viewerInstance.destroy();
        }

        // Crea un contenedor temporal solo para zoom
        const clone = img.cloneNode(true);
        const tempContainer = document.createElement("div");
        tempContainer.style.display = "none";
        tempContainer.appendChild(clone);
        document.body.appendChild(tempContainer);

        viewerInstance = new Viewer(clone, {
          inline: false,
          navbar: false,
          title: false,
          toolbar: {
            zoomIn: {
              show: true,
              title: "Zoom In",
              size: "large",
            },
            zoomOut: {
              show: true,
              title: "Zoom Out",
              size: "large",
            },
            oneToOne: false,
            reset: false,
            prev: false,
            play: false,
            next: false,
            rotateLeft: false,
            rotateRight: false,
            flipHorizontal: false,
            flipVertical: false,
          },
          viewed() {
            viewerInstance.zoomTo(1);
          },
          hidden() {
            viewerInstance.destroy();
            tempContainer.remove();
            viewerInstance = null;
          },
        });

        // Muestra el visor
        viewerInstance.show();
      };

      // Agrega el listener actualizado
      btn.addEventListener("click", btn._viewerClickHandler);
    }
  });
}

function toggleFrameOptions(show) {
  document.getElementById("frame-ios-picker-wrapper").style.display = show
    ? "block"
    : "none";

  if (show) {
    if (!window.mySwiper) {
      window.mySwiper = new Swiper(".mySwiper", {
        effect: "coverflow",
        grabCursor: true,
        centeredSlides: true,
        slidesPerView: "2",
        coverflowEffect: {
          rotate: 20,
          strech: 0,
          depth: 150,
          modifier: 3,
          slideShadows: true,
        },
        loop: true,
      });
    }
  }

  // Inicializa Viewer.js para las imágenes del Swiper
  initializeViewerForSwiper();
}

// Variables globales para almacenar las imágenes
// uploadedImagesData está declarado al inicio del archivo como un array vacío

const uploadArea = document.getElementById("upload-area");
const uploadElements = document.getElementById("upload-elements");
const fileInput = document.getElementById("file-input");
const imagesGrid = document.getElementById("images-grid");

// Función para activar el input de archivo
function triggerFileInput() {
  fileInput.click();
}

// Click en el área de subida
uploadArea.addEventListener("click", function (e) {
  // Verificar si el clic fue en un botón de eliminar o en una imagen
  if (
    e.target.classList.contains("remove-image-btn") ||
    e.target.closest(".remove-image-btn") ||
    e.target.tagName === "IMG"
  ) {
    return;
  }

  triggerFileInput();
});

// Manejo de archivos seleccionados
fileInput.addEventListener("change", handleFiles);

// Manejo de arrastrar y soltar
uploadArea.addEventListener("dragover", (e) => {
  e.preventDefault();
  uploadArea.style.backgroundColor = "#f1f1f1";
});

uploadArea.addEventListener("dragleave", (e) => {
  e.preventDefault();
  uploadArea.style.backgroundColor = "#f8f9fa";
});

uploadArea.addEventListener("drop", (e) => {
  e.preventDefault();
  uploadArea.style.backgroundColor = "#f8f9fa";

  if (e.dataTransfer.files.length > 0) {
    handleFiles({ target: { files: e.dataTransfer.files } });
  }
});

// Función para manejar los archivos - Modificada para soportar múltiples imágenes
function handleFiles(e) {
  const files = e.target.files; // Obtiene los archivos seleccionados
  const isCustomSize = selectedOptionSize === "custom"; // Verifica si el tamaño es personalizado
  selectedSize = sizePicker.getValue(); // Obtiene el tamaño seleccionado

  // Si es personalizado, obtiene los valores actuales de ancho y alto
  let customWidth, customHeight;
  if (isCustomSize) {
    customWidth = parseInt(document.getElementById("custom-size__width").value);
    customHeight = parseInt(
      document.getElementById("custom-size__height").value
    );

    // Si los valores personalizados no son válidos, muestra una advertencia y detiene el proceso
    if (isNaN(customWidth) || isNaN(customHeight)) {
      showNotification(
        "Por favor, ingresa un ancho y alto válidos para el tamaño personalizado.",
        "error"
      );
      return;
    }
  }

  const lowQualityIndexes = []; // Array para almacenar índices de imágenes de baja calidad

  if (files.length > 0) {
    uploadElements.classList.add("has-images"); // Añadir clase para mostrar que hay imágenes
  }

  let pending = files.length; // Contador de archivos pendientes de procesar

  for (let i = 0; i < files.length; i++) {
    const file = files[i];

    // Verificar si el archivo es una imagen
    if (!file.type.match("image.*")) {
      pending--; // Decrementar el contador de pendientes si no es una imagen
      continue;
    }

    const reader = new FileReader(); // Crear un nueva instancia FileReader para cada archivo

    reader.onload = function (event) {
      const img = new Image();
      img.src = event.target.result; // Asignar la URL de la imagen al objeto Image

      img.onload = function () {
        const { minWidth, minHeight } = getMinimumPixelsForImage(selectedSize); // Calcular los píxeles mínimos por imagen
        const isLowQuality =
          img.naturalWidth < minWidth || img.naturalHeight < minHeight; // Verificar si la imagen es de baja calidad

        const imageId = "img_" + Date.now() + "_" + i; // Generar un ID único para la imagen

        // Almacenar los datos de la imagen en el array global
        uploadedImagesData.push({
          id: imageId,
          file: file,
          lowQuality: isLowQuality,
        });

        // Crear el elemento de imagen en el DOM
        const imageItem = document.createElement("div");
        imageItem.className = "image-item";
        imageItem.dataset.imageId = imageId; // Asignar el ID al elemento

        if (isLowQuality) {
          imageItem.classList.add("low-quality"); // Añadir clase para baja calidad
          lowQualityIndexes.push(i + 1); // Guarda el índice (basado en 1) de la imagen Posición humana
        }

        // Crear el elemento de imagen y botón de eliminar
        const imgElement = document.createElement("img");
        imgElement.src = event.target.result;
        imgElement.alt = "Imagen subida";

        // Crea el botón de eliminar imagen
        const removeBtn = document.createElement("button");
        removeBtn.className = "remove-image-btn";
        removeBtn.textContent = "X";
        removeBtn.addEventListener("click", function (e) {
          e.stopPropagation(); // Evitar que el evento de clic se propague al contenedor
          uploadedImagesData = uploadedImagesData.filter(
            (img) => img.id !== imageId
          ); // Eliminar la imagen del array
          imageItem.remove(); // Eliminar el elemento del DOM
          if (imagesGrid.children.length === 0) {
            uploadElements.classList.remove("has-images"); // Remover clase si no hay imágenes
          }
          updateTotalPrice(); // Actualizar el precio total
          reiniciarPrecioMarco();
        });

        // Crear la etiqueta de advertencia de baja calidad
        const warningLabel = document.createElement("div");
        warningLabel.className = "quality-warning";
        warningLabel.textContent = "⚠️ Resolución baja";
        if (!isLowQuality) warningLabel.style.display = "none"; // Ocultar si no es baja calidad

        // Añadir los elementos al contenedor de imágenes
        imageItem.appendChild(imgElement);
        imageItem.appendChild(warningLabel);
        imageItem.appendChild(removeBtn);
        imagesGrid.appendChild(imageItem);

        updateTotalPrice(); // Actualizar el precio total
        reiniciarPrecioMarco();

        pending--; // Decrementar el contador de pendientes
      };
    };

    reader.readAsDataURL(file); // Leer el archivo como URL de datos
  }

  fileInput.value = ""; // Resetea el input para permitir subir el mismo archivo otra vez
}

// Factor de conversión de cm a píxeles (ajusta según la calidad requerida)
const PIXELS_PER_CM = 30;

// Función para calcular los requisitos mínimos de píxeles según el tamaño de la imagen
function getMinimumPixelsForImage(size) {
  const [widthCm, heightCm] = size.split("X").map(Number); // Convierte "10X15" a [10, 15]
  const minWidth = widthCm * PIXELS_PER_CM; // Calcula el ancho mínimo en píxeles
  const minHeight = heightCm * PIXELS_PER_CM; // Calcula la altura mínima en píxeles
  return { minWidth, minHeight }; // Devuelve un objeto con los valores mínimos
}

// Nueva función para validar imágenes con tamaños personalizados
function validateImagesWithCustomSize(customWidth, customHeight) {
  const minWidth = customWidth * PIXELS_PER_CM; // Ancho mínimo en píxeles
  console.log(`Ancho mínimo: ${minWidth}`);
  const minHeight = customHeight * PIXELS_PER_CM; // Alto mínimo en píxeles
  console.log(`Alto mínimo: ${minHeight}`);
  const lowQualityIndexes = []; // Índices de imágenes de baja calidad

  // Recorre las imágenes subidas
  uploadedImagesData.forEach((imageData, index) => {
    const imgElement = imagesGrid.querySelector(
      `[data-image-id="${imageData.id}"] img`
    );
    const warningLabel = imagesGrid.querySelector(
      `[data-image-id="${imageData.id}"] .quality-warning`
    );

    if (imgElement) {
      const tempImg = new Image();
      tempImg.src = imgElement.src;

      tempImg.onload = () => {
        const isLowQuality =
          tempImg.naturalWidth < minWidth || tempImg.naturalHeight < minHeight;

        // Actualiza el estado de calidad en el array global
        imageData.lowQuality = isLowQuality;

        // Modifica clases visuales y etiqueta
        if (isLowQuality) {
          lowQualityIndexes.push(index + 1); // Índice basado en 1
          warningLabel.style.display = "block"; // Mostrar advertencia
        } else {
          warningLabel.style.display = "none"; // Ocultar advertencia
        }
      };
    }
  });
}

// Modificar el evento de entrada para tamaños personalizados
document
  .getElementById("custom-size__width")
  .addEventListener("input", function () {
    const customWidth = parseInt(this.value);
    const customHeight = parseInt(
      document.getElementById("custom-size__height").value
    );

    if (!isNaN(customWidth) && !isNaN(customHeight)) {
      validateImagesWithCustomSize(customWidth, customHeight);
    }
  });

document
  .getElementById("custom-size__height")
  .addEventListener("input", function () {
    const customWidth = parseInt(
      document.getElementById("custom-size__width").value
    );
    const customHeight = parseInt(this.value);

    if (!isNaN(customWidth) && !isNaN(customHeight)) {
      validateImagesWithCustomSize(customWidth, customHeight);
    }
  });

// Modificado para incluir el nombre del archivo
function analyzeImagePixels(img, imageSize, fileName) {
  const { minWidth, minHeight } = getMinimumPixelsForImage(imageSize); // Obtener los requisitos mínimos de píxeles

  // Verificar si la imagen se ha cargado correctamente
  if (img.naturalWidth === 0 || img.naturalHeight === 0) {
    return `La imagen "${fileName}" no se cargó correctamente.`;
  }

  // Verificar si la imagen cumple con los requisitos mínimos
  if (img.naturalWidth < minWidth || img.naturalHeight < minHeight) {
    return `La imagen "${fileName}" no tiene suficiente calidad para imprimir en ${imageSize.replace(
      "X",
      "x"
    )} cm. Debe tener al menos ${minWidth}x${minHeight} píxeles.`;
  }

  return null; // No hay error
}

// Functión para revalidar imágenes con el nuevo tamaño seleccionado
function revalidateImagesWithNewSize(newSize) {
  const { minWidth, minHeight } = getMinimumPixelsForImage(newSize); // Obtener los nuevos requisitos mínimos
  const lowQualityIndexes = []; // Array para almacenar índices de imágenes de baja calidad

  // Recorre las imágenes en el orden en que aparecen en el grid
  const imageItems = Array.from(imagesGrid.querySelectorAll(".image-item"));

  imageItems.forEach((item, index) => {
    const imageId = item.dataset.imageId; // Obtener el ID de la imagen
    const data = uploadedImagesData.find((img) => img.id === imageId); // Buscar en el array de datos
    const imgElement = item.querySelector("img"); // Obtener el elemento de imagen
    const warningLabel = item.querySelector(".quality-warning"); // Obtener la etiqueta de advertencia

    const tempImg = new Image(); // Crear un nuevo objeto Image para cargar la imagen
    tempImg.src = imgElement.src; // Asignar la URL de la imagen al objeto Image

    tempImg.onload = () => {
      const isLowQuality =
        tempImg.naturalWidth < minWidth || tempImg.naturalHeight < minHeight;

      // Actualiza el estado de calidad en el array global
      data.lowQuality = isLowQuality;

      // Modifica clases visuales y etiqueta
      if (isLowQuality) {
        item.classList.add("low-quality");
        if (warningLabel) warningLabel.style.display = "block"; // Mostrar advertencia
        lowQualityIndexes.push(index + 1); // Índice basado en el orden del grid
      } else {
        item.classList.remove("low-quality");
        if (warningLabel) warningLabel.style.display = "none"; // Ocultar advertencia
      }
    };
  });
}

function getSelectedSizePrice() {
  if (initializeSizePicker) {
    console.log("🚀 initializeSizePicker() fue llamada correctamente");
  }
  selectedSize = sizePicker.getValue(); // Obtener el tamaño seleccionado
  return currentSizePrices[selectedSize] || 0; // Retornar el precio correspondiente
}

// Función para añadir al carrito - Modificada para procesar múltiples imágenes de forma secuencial
function addToCart() {
  console.log("🚀 addToCart() fue llamada correctamente");

  if (uploadedImagesData.length === 0) {
    showNotification("Por favor, sube al menos una imagen primero.", "error");
    console.log("⚠️ No hay imágenes subidas.");
    return;
  }

  const quantity = document.querySelector(".quantity-field input").value;
  const isCustomSize = selectedOptionSize == "custom";

  let size, customWidth, customHeight, pricePerUnit;
  if (isCustomSize) {
    customWidth = document.getElementById("custom-size__width").value;
    customHeight = document.getElementById("custom-size__height").value;
    if (!customWidth || !customHeight) {
      showNotification("Ingresa las medidas personalizadas", "error");
      return;
    }
    size = `${customWidth}x${customHeight}`;
    pricePerUnit = parseInt(customHeight) * parseInt(customWidth) * 0.03;
    pricePerUnit = Math.round(pricePerUnit);
  } else {
    size = sizePicker.getValue();
    pricePerUnit = getSelectedSizePrice();
  }
  console.log("🚀 pricePerUnit", pricePerUnit);

  // Validar que el campo de comentarios no esté vacío
  const comments = document.querySelector(".comment-area").value.trim();
  if (!comments) {
    showNotification("Por favor, escribe un comentario.", "error");
    document.querySelector(".comment-area").focus();
    return;
  }
  const withFrame =
    document.querySelector('input[name="frame"]:checked').value !== "sin-marco";
  let frame = "sin-marco";
  let frame_price = 0;

  if (withFrame) {
    const selectedFrameElement = document.querySelector(
      "#frame-picker .swiper-slide.selected"
    );
    frame = selectedFrameElement ? currentSelectedFrame : "Sin marco";
    // marcoSeleccionado viene de events.js y tiene el precio del marco seleccionado
    frame_price = window.marcoSeleccionado !== "undefined" ? parseFloat(window.marcoSeleccionado) : 0;
  }

  if (typeof ajax_object === "undefined") {
    console.error(
      "❌ ERROR: ajax_object no está definido. Verifica que el script de WordPress está cargando correctamente."
    );
    showNotification(
      "Error de configuración. Contacte con el administrador.",
      "error"
    );
    return;
  }

  showLoadingIndicator();

  // --- NUEVO: Enviar todas las imágenes en un solo FormData ---
  const formData = new FormData();
  formData.append("action", "process_print_image");
  formData.append("nonce", ajax_object.nonce);
  formData.append("quantity", quantity);
  formData.append("size", size);
  formData.append("variation_id", variation_id);
  formData.append("price", pricePerUnit);
  formData.append("is_custom_size", isCustomSize);
  formData.append("custom_width", customWidth || 0);
  formData.append("custom_height", customHeight || 0);
  formData.append("comments", comments);
  formData.append("frame", frame);
  formData.append("frame_price", frame_price);

  alert(
    formData.get("action") + "\n" +
    formData.get("nonce") + "\n" +
    formData.get("quantity") + "\n" +
    formData.get("size") + "\n" +
    formData.get("variation_id") + "\n" +
    formData.get("price") + "\n" +
    formData.get("is_custom_size") + "\n" +
    formData.get("custom_width") + "\n" +
    formData.get("custom_height") + "\n" +
    formData.get("comments") + "\n" +
    formData.get("frame") + "\n"
  );

  // Agregar todas las imágenes como image_data[]
  uploadedImagesData.forEach((imageData, idx) => {
    formData.append("image_data[]", imageData.file, imageData.file.name);
  });

  fetch(ajax_object.ajax_url, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => response.json())
    .then((data) => {
      hideLoadingIndicator();
      if (data.success) {
        showNotification("¡Imágenes añadidas al carrito!", "success");
        
        // Actualizar el fragmento del carrito
        if (typeof wc_cart_fragments_params !== 'undefined') {
          jQuery(document.body).trigger('wc_fragment_refresh');
        }
     
         // Mostrar automáticamente la barra lateral del carrito de Elementor
       setTimeout(function() {
        // Buscar y hacer clic en el botón que abre el carrito lateral
        const cartToggleBtn = document.querySelector('.elementor-menu-cart__toggle_button');
        if (cartToggleBtn) {
          cartToggleBtn.click();
        } else {
          // Alternativa: usar el evento personalizado de Elementor para abrir el carrito lateral
          jQuery(document.body).trigger('wc_fragments_refreshed');
          jQuery(document.body).trigger('elementor/popup/show');
        }
      }, 2500); // Pequeño retraso para asegurar que los fragmentos se actualizaron


        // Limpiar todos los campos del formulario
        resetForm();
      } else {
        showNotification(
          data.data || "Error al procesar las imágenes.",
          "error"
        );
      }
    })
    .catch((error) => {
      hideLoadingIndicator();
      showNotification(
        "Error al procesar las imágenes. Por favor intenta de nuevo.",
        "error"
      );
      console.error(error);
    });
}

// Función para resetear el formulario
function resetForm() {
  // Limpiar imágenes
  uploadedImagesData = [];
  imagesGrid.innerHTML = "";
  uploadElements.classList.remove("has-images");

  // Resetear cantidad
  const quantityInput = document.querySelector(".quantity-field input");
  if (quantityInput) {
    quantityInput.value = "1";
  }

  // Resetear comentarios
  const commentArea = document.querySelector(".comment-area");
  if (commentArea) {
    commentArea.value = "";
  }

  // Resetear tamaño personalizado
  const customWidthInput = document.getElementById("custom-size__width");
  const customHeightInput = document.getElementById("custom-size__height");
  if (customWidthInput) customWidthInput.value = "10";
  if (customHeightInput) customHeightInput.value = "15";

  // Resetear selección de marco
  const frameInputs = document.querySelectorAll('input[name="frame"]');
  if (frameInputs.length > 0) {
    frameInputs[0].checked = true; // Seleccionar el primer radio button
  }

  // Resetear el picker de tamaño
  if (sizePicker) {
    sizePicker.destroy();
    initializeSizePicker();
  }

  // Resetear el precio total
  document.getElementById("total-price-button").innerText = "S/. 0.00";

  // Resetear la selección de tipo de tamaño
  if (selectedOptionSize === "custom") {
    toggleSizeOptions("standard");
  }
}

// Función para mostrar notificaciones
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);

  // Agregar el mensaje al log de la consola
  console.log(`[${type.toUpperCase()}] ${message}`);

  setTimeout(() => notification.classList.add("show"), 10);
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => document.body.removeChild(notification), 600);
  }, 2000);
}

// Función para mostrar indicador de carga mejorado
function showLoadingIndicator() {
  const loadingOverlay = document.createElement("div");
  loadingOverlay.className = "loading-overlay";
  loadingOverlay.innerHTML = `
    <div class="loading-spinner"></div>
    <p>Procesando imagen 1/${uploadedImagesData.length}...</p>
  `;
  document.body.appendChild(loadingOverlay);
  setTimeout(() => loadingOverlay.classList.add("show"), 10);
}

// Función para ocultar indicador de carga
function hideLoadingIndicator() {
  const loadingOverlay = document.querySelector(".loading-overlay");
  if (loadingOverlay) {
    loadingOverlay.classList.remove("show");

    setTimeout(() => {
      if (document.body.contains(loadingOverlay)) {
        document.body.removeChild(loadingOverlay);
      }
    }, 300);
  }
}

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
      priceDimensionsBaseMain = totalPrice.toFixed(2);

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
    priceDimensionsBaseMain = totalPrice.toFixed(2);

    // Update total price text on button
    document.getElementById(
      "total-price-button"
    ).innerText = `S/. ${totalPrice.toFixed(2)}`;
  }
}

// Exponer la función al ámbito global para evitar problemas con `onclick`
window.addToCart = addToCart;
