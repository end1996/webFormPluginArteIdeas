// Variable global para seleccion de tamaños y almacenar múltiples imágenes
var selectedOptionSize;
let currentSelectedFrame = 'frame1'; // Valor por defecto para el marco
let uploadedImagesData = []; // Array para almacenar múltiples imágenes
let sizePicker;
let selectedSize;
let currentSizePrices;

const sizePrices = {
  "10X15": 5.00,
  "13X18": 7.00,
  "15X21": 8.00,
  "20X25": 15.00,
  "20X30": 18.00,
  "25X38": 25.00,
  "30X45": 35.00,
  "30X60": 45.00,
};

// Precios para cantidades mayores o iguales a 4
const bulkSizePrices = {
  "10X15": 3.00,
  "13X18": 5.00,
  "15X21": 6.00,
  "20X25": 13.00,
  "20X30": 15.00,
  "25X38": 20.00,
  "30X45": 30.00,
  "30X60": 40.00,
};

// Precios para cantidades mayores o iguales a 12
const bulkSizePrices12 = {
  "10X15": 2.50,
  "13X18": 4.00,
  "15X21": 5.00,
  "20X25": 10.00,
  "20X30": 12.00,
  "25X38": 18.00,
  "30X45": 25.00,
  "30X60": 35.00,
};

document.addEventListener('DOMContentLoaded', function () {
  initializeSizePicker();
  initializeFramePicker();
  setupEventListeners();
  setupCommentLengthChecker();

  // Initialize image counter
  const counterElement = document.createElement('div');
  counterElement.id = 'images-counter';
  counterElement.className = 'images-counter';
  counterElement.textContent = '0';
  counterElement.style.display = 'none'; // Initially hidden

  // Insert counter after upload area
  const uploadAreaParent = document.getElementById('upload-area').parentElement;
  if (uploadAreaParent) {
    uploadAreaParent.insertBefore(counterElement, document.getElementById('upload-area').nextSibling);
  }

  updateTotalPrice();
});

function initializeSizePicker() {
  const quantityInput = document.querySelector('.quantity-field input');
  const sizePickerContainer = document.getElementById('size-picker');

  function getCurrentSizePrices(quantity) {
    return quantity >= 12 ? bulkSizePrices12 : (quantity >= 4 ? bulkSizePrices : sizePrices);
  }

  function updateSizeOptions(quantity) {
    currentSizePrices = getCurrentSizePrices(quantity);
    const sizeOptions = Object.keys(currentSizePrices).map(value => ({
      value: value,
      label: `${value.replace("X", "cm Ancho X ")}cm Alto => (S/.${currentSizePrices[value].toFixed(2)})`
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
      }
    });
  }

  // Inicializar con cantidad 1
  updateSizeOptions(1);

  // Escuchar cambios en cantidad
  quantityInput.addEventListener('input', function () {
    const quantity = parseInt(this.value) || 1;
    console.log(`Cantidad ingresada: ${quantity}`);
    updateSizeOptions(quantity);
  });
}


function initializeFramePicker() {
  const framePicker = document.getElementById('frame-picker');
  if (!framePicker) return;

  const pickerItems = framePicker.querySelectorAll('.swiper-slide');
  pickerItems.forEach(item => {
    item.addEventListener('click', function () {
      // Verificar si hay imágenes subidas antes de permitir la selección del marco
      // if (uploadedImagesData.length === 0) {
      //   showNotification('Sube una imagen primero', 'error');
      //   return;
      // }
      pickerItems.forEach(i => i.classList.remove('selected'));
      item.classList.add('selected');
      currentSelectedFrame = item.dataset.value;
    });
  });
}

function setupCommentLengthChecker() {
  const commentArea = document.querySelector(".comment-area");

  commentArea.addEventListener('input', function () {
    const maxLength = 255;
    const currentLength = this.value.length;

    if (currentLength >= maxLength) {
      showNotification('Has alcanzado el límite máximo de 255 caracteres en los comentarios.', 'error');
    }
  });
}

function setupEventListeners() {
  const quantityInput = document.querySelector('.quantity-field input');


  quantityInput.addEventListener('focus', function () {
    if (this.value === '1') {
      this.value = '';
    }
  });

  quantityInput.addEventListener('input', function () {
    let value = parseInt(this.value);

    // Si es mayor a 100, vuelve al mismo valor
    if (value > 100) {
      this.value = 100;
      showNotification('La cantidad no puede ser mayor a 100 unidades', 'error');
    }

    updateTotalPrice();
  });

  quantityInput.addEventListener('blur', function () {
    let value = parseInt(this.value);

    if (isNaN(value) || value < 1) {
      this.value = 1;

    }
  });

  document.getElementById("custom-size__width").addEventListener("input", handleCustomSize);
  document.getElementById("custom-size__height").addEventListener("input", handleCustomSize);
  document.querySelector('.add-to-cart-btn').addEventListener('click', addToCart);
}

function handleCustomSize() {
  const widthInput = document.getElementById("custom-size__width");
  const heightInput = document.getElementById("custom-size__height");

  // ANCHO 
  widthInput.addEventListener('focus', function () {
    if (this.value === '10') this.value = '';
  });

  widthInput.addEventListener('input', function () {
    let value = parseInt(this.value);
    if (value > 60) {
      this.value = 60;
      showNotification("El ancho no puede ser mayor a 60 cm.", 'error');
    }
    updateTotalPrice();
  });

  widthInput.addEventListener('blur', function () {
    let value = parseInt(this.value);
    if (isNaN(value) || value < 10) {
      this.value = 10;
      showNotification("El ancho no puede ser menor a 10 cm.", 'error');
    }
  });

  // ALTO 
  heightInput.addEventListener('focus', function () {
    if (this.value === '15') this.value = '';
  });

  heightInput.addEventListener('input', function () {
    let value = parseInt(this.value);
    if (value > 100) {
      this.value = 100;
      showNotification("El alto no puede ser mayor a 100 cm.", 'error');
    }
    updateTotalPrice();
  });

  heightInput.addEventListener('blur', function () {
    let value = parseInt(this.value);
    if (isNaN(value) || value < 15) {
      this.value = 15;
      showNotification("El alto no puede ser menor a 15 cm.", 'error');
    }
  });

  document.querySelector('.add-to-cart-btn').addEventListener('click', addToCart);
}

// Función para alternar entre opciones de tamaño estándar y personalizado
function toggleSizeOptions(type) {
  if (type === 'standard') {
    document.getElementById('standard-sizes').style.display = 'block';
    document.getElementById('custom-size').style.display = 'none';
    this.selectedOptionSize = 'standard';
  } else {
    document.getElementById('standard-sizes').style.display = 'none';
    document.getElementById('custom-size').style.display = 'flex';
    this.selectedOptionSize = 'custom';
  }
}

// Función para inicializar el visor de imágenes (Viewer.js) para las imágenes del Swiper
function initializeViewerForSwiper() {
  const slides = document.querySelectorAll('.swiper-slide');

  slides.forEach((slide) => {
    const img = slide.querySelector('img'); // Busca la imagen dentro del slide
    const btn = slide.querySelector('.zoom-icon-btn'); //Busca el botón con clase .zoom-icon-btn dentro del slide

    if (btn && img) {
      let viewerInstance = null;
      // Agrega un evento de clic al botón de zoom
      btn.addEventListener('click', function (event) {
        event.stopPropagation();

        // Evita crear múltiples viewers
        if (viewerInstance) {
          viewerInstance.destroy();
        }

        // Crea un contenedor temporal solo para zoom
        const clone = img.cloneNode(true);
        const tempContainer = document.createElement('div');
        tempContainer.style.display = 'none';
        tempContainer.appendChild(clone);
        document.body.appendChild(tempContainer);

        viewerInstance = new Viewer(clone, {
          inline: false,
          navbar: false,
          title: false,
          toolbar: {
            zoomIn: { // Habilitar zoom in
              show: true,
              title: 'Zoom In',
              size: 'large'
            },
            zoomOut: { // Habilitar zoom out
              show: true,
              title: 'Zoom Out',
              size: 'large'
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
          }
        });
        // Muestra el visor
        viewerInstance.show();
      });
    }
  });
}

function toggleFrameOptions(show) {
  document.getElementById('frame-ios-picker-wrapper').style.display = show ? 'block' : 'none';

  if (show) {
    if (!window.mySwiper) {
      window.mySwiper = new Swiper('.mySwiper', {
        effect: 'coverflow',
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

const uploadArea = document.getElementById('upload-area');
const uploadElements = document.getElementById('upload-elements');
const fileInput = document.getElementById('file-input');
const imagesGrid = document.getElementById('images-grid');

// Función para activar el input de archivo
function triggerFileInput() {
  fileInput.click();
}

// Click en el área de subida
uploadArea.addEventListener('click', function (e) {
  // Verificar si el clic fue en un botón de eliminar o en una imagen
  if (e.target.classList.contains('remove-image-btn') ||
    e.target.closest('.remove-image-btn') ||
    e.target.tagName === 'IMG') {
    return;
  }

  triggerFileInput();
});

// Manejo de archivos seleccionados
fileInput.addEventListener('change', handleFiles);

// Manejo de arrastrar y soltar
uploadArea.addEventListener('dragover', (e) => {
  e.preventDefault();
  uploadArea.style.backgroundColor = '#f1f1f1';
});

uploadArea.addEventListener('dragleave', (e) => {
  e.preventDefault();
  uploadArea.style.backgroundColor = '#f8f9fa';
});

uploadArea.addEventListener('drop', (e) => {
  e.preventDefault();
  uploadArea.style.backgroundColor = '#f8f9fa';

  if (e.dataTransfer.files.length > 0) {
    handleFiles({ target: { files: e.dataTransfer.files } });
  }
});

// Función para manejar los archivos - Modificada para soportar múltiples imágenes
function handleFiles(e) {
  const files = e.target.files; // Obtiene los archivos seleccionados
  const isCustomSize = selectedOptionSize === 'custom'; // Verifica si el tamaño es personalizado
  selectedSize = sizePicker.getValue(); // Obtiene el tamaño seleccionado

  // Si es personalizado, obtiene los valores actuales de ancho y alto
  let customWidth, customHeight;
  if (isCustomSize) {
    customWidth = parseInt(document.getElementById('custom-size__width').value);
    customHeight = parseInt(document.getElementById('custom-size__height').value);

    // Si los valores personalizados no son válidos, muestra una advertencia y detiene el proceso
    if (isNaN(customWidth) || isNaN(customHeight)) {
      showNotification("Por favor, ingresa un ancho y alto válidos para el tamaño personalizado.", "error");
      return;
    }
  }

  const lowQualityIndexes = []; // Array para almacenar índices de imágenes de baja calidad

  if (files.length > 0) {
    uploadElements.classList.add('has-images'); // Añadir clase para mostrar que hay imágenes
  }

  let pending = files.length; // Contador de archivos pendientes de procesar

  for (let i = 0; i < files.length; i++) {
    const file = files[i];

    // Verificar si el archivo es una imagen
    if (!file.type.match('image.*')) {
      pending--; // Decrementar el contador de pendientes si no es una imagen
      continue;
    }

    const reader = new FileReader(); // Crear un nueva instancia FileReader para cada archivo

    reader.onload = function (event) {
      const img = new Image();
      img.src = event.target.result; // Asignar la URL de la imagen al objeto Image

      img.onload = function () {
        const { minWidth, minHeight } = getMinimumPixelsForImage(selectedSize); // Calcular los píxeles mínimos por imagen
        const isLowQuality = img.naturalWidth < minWidth || img.naturalHeight < minHeight; // Verificar si la imagen es de baja calidad

        const imageId = 'img_' + Date.now() + '_' + i; // Generar un ID único para la imagen

        // Almacenar los datos de la imagen en el array global
        uploadedImagesData.push({
          id: imageId,
          file: file,
          lowQuality: isLowQuality,
        });

        // Crear el elemento de imagen en el DOM
        const imageItem = document.createElement('div');
        imageItem.className = 'image-item';
        imageItem.dataset.imageId = imageId; // Asignar el ID al elemento

        if (isLowQuality) {
          imageItem.classList.add('low-quality');  // Añadir clase para baja calidad
          lowQualityIndexes.push(i + 1); // Guarda el índice (basado en 1) de la imagen Posición humana
        }

        // Crear el elemento de imagen y botón de eliminar
        const imgElement = document.createElement('img');
        imgElement.src = event.target.result;
        imgElement.alt = 'Imagen subida';

        // Crea el botón de eliminar imagen
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-image-btn';
        removeBtn.textContent = 'X';
        removeBtn.addEventListener('click', function (e) {
          e.stopPropagation(); // Evitar que el evento de clic se propague al contenedor
          uploadedImagesData = uploadedImagesData.filter(img => img.id !== imageId); // Eliminar la imagen del array
          imageItem.remove(); // Eliminar el elemento del DOM
          if (imagesGrid.children.length === 0) {
            uploadElements.classList.remove('has-images'); // Remover clase si no hay imágenes
          }
          updateImageCounter(); // Actualizar el contador de imágenes
          updateTotalPrice(); // Actualizar el precio total
        });

        // Crear la etiqueta de advertencia de baja calidad
        const warningLabel = document.createElement('div');
        warningLabel.className = 'quality-warning';
        warningLabel.textContent = '⚠️ Resolución baja';
        if (!isLowQuality) warningLabel.style.display = 'none'; // Ocultar si no es baja calidad

        // Añadir los elementos al contenedor de imágenes
        imageItem.appendChild(imgElement);
        imageItem.appendChild(warningLabel);
        imageItem.appendChild(removeBtn);
        imagesGrid.appendChild(imageItem);

        updateImageCounter(); // Actualizar el contador de imágenes
        updateTotalPrice(); // Actualizar el precio total

        pending--; // Decrementar el contador de pendientes

        // Si todas las imágenes han sido procesadas, mostrar advertencia si hay imágenes de baja calidad
        // if (pending === 0 && lowQualityIndexes.length > 0) {
        //   const imagesStr = lowQualityIndexes.join(', '); // Crear una cadena con los índices de las imágenes
        //   const plural = lowQualityIndexes.length > 1; // Determina si es plural
        //   const message = plural
        //     ? `Las imágenes ${imagesStr} no tienen resolución suficiente para una impresión óptima en ${selectedSize.replace("X", "x")} cm.`
        //     : `La imagen ${imagesStr} no tiene resolución suficiente para una impresión óptima en ${selectedSize.replace("X", "x")} cm.`;

        //   showNotification(message, 'error'); // Mostrar advertencia al usuario
        // }
      };
    };

    reader.readAsDataURL(file); // Leer el archivo como URL de datos
  }

  fileInput.value = ''; // Resetea el input para permitir subir el mismo archivo otra vez
}

// Función para actualizar el contador de imágenes
function updateImageCounter() {
  const counter = document.getElementById('images-counter');
  if (counter) {
    counter.textContent = uploadedImagesData.length;
  } else {
    // Si no existe el contador, lo creamos
    const counterElement = document.createElement('div');
    counterElement.id = 'images-counter';
    counterElement.className = 'images-counter';
    counterElement.textContent = uploadedImagesData.length;

    // Insertamos el contador cerca del área de subida
    const uploadAreaParent = uploadArea.parentElement;
    uploadAreaParent.insertBefore(counterElement, uploadArea.nextSibling);
  }

  // Mostrar u ocultar contador según tengamos imágenes
  if (counter) {
    counter.style.display = uploadedImagesData.length > 0 ? 'block' : 'none';
  }
}

// Factor de conversión de cm a píxeles (ajusta según la calidad requerida)
const PIXELS_PER_CM = 30;

// Función para calcular los requisitos mínimos de píxeles según el tamaño de la imagen
function getMinimumPixelsForImage(size) {
  const [widthCm, heightCm] = size.split('X').map(Number); // Convierte "10X15" a [10, 15]
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
    const imgElement = imagesGrid.querySelector(`[data-image-id="${imageData.id}"] img`);
    const warningLabel = imagesGrid.querySelector(`[data-image-id="${imageData.id}"] .quality-warning`);

    if (imgElement) {
      const tempImg = new Image();
      tempImg.src = imgElement.src;

      tempImg.onload = () => {
        const isLowQuality = tempImg.naturalWidth < minWidth || tempImg.naturalHeight < minHeight;

        // Actualiza el estado de calidad en el array global
        imageData.lowQuality = isLowQuality;

        // Modifica clases visuales y etiqueta
        if (isLowQuality) {
          lowQualityIndexes.push(index + 1); // Índice basado en 1
          warningLabel.style.display = 'block'; // Mostrar advertencia
        } else {
          warningLabel.style.display = 'none'; // Ocultar advertencia
        }
      };
    }
  });

  // Mostrar notificación si hay imágenes de baja calidad
  // if (lowQualityIndexes.length > 0) {
  //   const imagesStr = lowQualityIndexes.join(', ');
  //   const plural = lowQualityIndexes.length > 1;
  //   const message = plural
  //     ? `Las imágenes ${imagesStr} no tienen resolución suficiente para una impresión óptima en ${customWidth}x${customHeight} cm.`
  //     : `La imagen ${imagesStr} no tiene resolución suficiente para una impresión óptima en ${customWidth}x${customHeight} cm.`;

  //   showNotification(message, 'error');
  // }
}

// Modificar el evento de entrada para tamaños personalizados
document.getElementById("custom-size__width").addEventListener("input", function () {
  const customWidth = parseInt(this.value);
  const customHeight = parseInt(document.getElementById("custom-size__height").value);

  if (!isNaN(customWidth) && !isNaN(customHeight)) {
    validateImagesWithCustomSize(customWidth, customHeight);
  }
});

document.getElementById("custom-size__height").addEventListener("input", function () {
  const customWidth = parseInt(document.getElementById("custom-size__width").value);
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
    return `La imagen "${fileName}" no tiene suficiente calidad para imprimir en ${imageSize.replace("X", "x")} cm. Debe tener al menos ${minWidth}x${minHeight} píxeles.`;
  }

  return null; // No hay error
}

// Functión para revalidar imágenes con el nuevo tamaño seleccionado
function revalidateImagesWithNewSize(newSize) {
  const { minWidth, minHeight } = getMinimumPixelsForImage(newSize); // Obtener los nuevos requisitos mínimos
  const lowQualityIndexes = []; // Array para almacenar índices de imágenes de baja calidad

  // Recorre las imágenes en el orden en que aparecen en el grid
  const imageItems = Array.from(imagesGrid.querySelectorAll('.image-item'));

  imageItems.forEach((item, index) => {
    const imageId = item.dataset.imageId; // Obtener el ID de la imagen
    const data = uploadedImagesData.find(img => img.id === imageId); // Buscar en el array de datos
    const imgElement = item.querySelector('img'); // Obtener el elemento de imagen
    const warningLabel = item.querySelector('.quality-warning'); // Obtener la etiqueta de advertencia

    const tempImg = new Image(); // Crear un nuevo objeto Image para cargar la imagen
    tempImg.src = imgElement.src; // Asignar la URL de la imagen al objeto Image

    tempImg.onload = () => {
      const isLowQuality = tempImg.naturalWidth < minWidth || tempImg.naturalHeight < minHeight;

      // Actualiza el estado de calidad en el array global
      data.lowQuality = isLowQuality;

      // Modifica clases visuales y etiqueta
      if (isLowQuality) {
        item.classList.add('low-quality');
        if (warningLabel) warningLabel.style.display = 'block'; // Mostrar advertencia
        lowQualityIndexes.push(index + 1); // Índice basado en el orden del grid
      } else {
        item.classList.remove('low-quality');
        if (warningLabel) warningLabel.style.display = 'none'; // Ocultar advertencia
      }

      // Si es la última imagen y hay imágenes de baja calidad, muestra una notificación
      // if (index === imageItems.length - 1 && lowQualityIndexes.length > 0) {
      //   const plural = lowQualityIndexes.length > 1; // Determina si es plural
      //   const imagesStr = lowQualityIndexes.join(', '); // Crear una cadena con los índices de las imágenes
      //   const message = plural
      //     ? `Las imágenes ${imagesStr} no tienen resolución suficiente para una impresión óptima en ${newSize.replace("X", "x")} cm.`
      //     : `La imagen ${imagesStr} no tiene resolución suficiente para una impresión óptima en ${newSize.replace("X", "x")} cm.`;

      //   showNotification(message, 'error'); // Mostrar advertencia al usuario
      // }
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

  // Verificar si hay imágenes subidas
  if (uploadedImagesData.length === 0) {
    showNotification('Por favor, sube al menos una imagen primero.', 'error');
    console.log("⚠️ No hay imágenes subidas.");
    return;
  }

  // Obtener los valores del formulario
  const quantity = document.querySelector('.quantity-field input').value;
  const isCustomSize = selectedOptionSize == 'custom';

  let size, customWidth, customHeight;
  if (isCustomSize) {
    customWidth = document.getElementById('custom-size__width').value;
    customHeight = document.getElementById('custom-size__height').value;
    if (!customWidth || !customHeight) {
      showNotification('Ingresa las medidas personalizadas', 'error');
      return;
    }
    size = `${customWidth}x${customHeight}`;
  } else {
    size = sizePicker.getValue();
    pricePerUnit = getSelectedSizePrice(); // Obtener el precio del tamaño seleccionado
  }

  // const totalPrice = pricePerUnit * quantity * uploadedImagesData.length; // Calcular el precio total
  // console.log(`💰 Precio total: S/. ${totalPrice.toFixed(2)}`);

  const comments = document.querySelector('.comment-area').value;
  const withFrame = document.querySelector('input[name="frame"]:checked').value !== 'sin-marco';
  let frame = 'sin-marco';

  if (withFrame) {
    const selectedFrameElement = document.querySelector('#frame-picker .swiper-slide.selected');
    frame = selectedFrameElement ? currentSelectedFrame : 'frame1';
  }

  // Verificar si `ajax_object` está definido antes de usarlo
  if (typeof ajax_object === 'undefined') {
    console.error("❌ ERROR: ajax_object no está definido. Verifica que el script de WordPress está cargando correctamente.");
    showNotification("Error de configuración. Contacte con el administrador.", "error");
    return;
  }

  // Mostrar indicador de carga
  showLoadingIndicator();

  // Variables para seguimiento del proceso
  let processedImages = 0;
  let successfulProcesses = 0;
  let failedProcesses = 0;
  let cartUrl = '';

  // Función para procesar una imagen específica
  const processImage = async (index) => {
    if (index >= uploadedImagesData.length) {
      // Hemos terminado de procesar todas las imágenes
      finishProcessing();
      return;
    }

    const imageData = uploadedImagesData[index];
    console.log(`📦 Procesando imagen ${index + 1}/${uploadedImagesData.length}...`);

    try {
      // Preparar los datos para enviar
      const formData = new FormData();
      formData.append('action', 'process_print_image');
      formData.append('nonce', ajax_object.nonce);
      formData.append('image_data', imageData.file);
      formData.append('quantity', quantity);
      formData.append('size', size);
      formData.append('price', pricePerUnit); // Precio unitario
      formData.append('is_custom_size', isCustomSize);
      formData.append('custom_width', customWidth || 0);
      formData.append('custom_height', customHeight || 0);
      formData.append('comments', comments);
      formData.append('frame', frame);
      formData.append('image_index', index); // Añadir el índice para seguimiento

      // Enviar al servidor
      const response = await fetch(ajax_object.ajax_url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });

      const data = await response.json();
      processedImages++;

      if (data.success) {
        successfulProcesses++;
        if (!cartUrl && data.data && data.data.cart_url) {
          cartUrl = data.data.cart_url;
        }
        console.log(`✅ Imagen ${index + 1} procesada correctamente`);
      } else {
        failedProcesses++;
        console.error(`❌ Error al procesar la imagen ${index + 1}:`, data.data);
      }

      // Actualizar el indicador de carga con el progreso
      updateLoadingStatus(processedImages, uploadedImagesData.length);

      // Procesar la siguiente imagen
      await processImage(index + 1);

    } catch (error) {
      processedImages++;
      failedProcesses++;
      console.error(`❌ Error al procesar la imagen ${index + 1}:`, error);

      // Continuar con la siguiente imagen a pesar del error
      await processImage(index + 1);
    }
  };

  // Función para mostrar el estado de carga actualizado
  const updateLoadingStatus = (processed, total) => {
    const loadingOverlay = document.querySelector('.loading-overlay p');
    if (loadingOverlay) {
      loadingOverlay.textContent = `Procesando imagen ${processed}/${total}...`;
    }
  };

  // Función para finalizar el proceso
  const finishProcessing = () => {
    hideLoadingIndicator();

    console.log(`✅ Procesadas ${processedImages}/${uploadedImagesData.length} imágenes. Éxitos: ${successfulProcesses}, Fallos: ${failedProcesses}`);

    if (successfulProcesses > 0) {
      if (failedProcesses > 0) {
        showNotification(`Se añadieron ${successfulProcesses} imágenes al carrito, pero ${failedProcesses} no pudieron procesarse.`, 'warning');
      } else {
        showNotification(`¡${successfulProcesses} ${successfulProcesses > 1 ? 'imágenes añadidas' : 'imagen añadida'} al carrito!`, 'success');
      }

      // Redirigir al carrito después de un breve retraso
      if (cartUrl) {
        setTimeout(() => {
          window.location.href = cartUrl;
        }, 1500);
      }

      // Limpiar las imágenes procesadas exitosamente
      if (successfulProcesses === uploadedImagesData.length) {
        // Todas las imágenes se procesaron con éxito, limpiar todo
        uploadedImagesData = [];
        imagesGrid.innerHTML = '';
        uploadElements.classList.remove('has-images');
        updateImageCounter();
      }
    } else if (failedProcesses > 0) {
      showNotification('Error al procesar las imágenes. Por favor intenta de nuevo.', 'error');
    }
  };

  // Iniciar el procesamiento secuencial comenzando por la primera imagen
  processImage(0);
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);

  // Agregar el mensaje al log de la consola
  console.log(`[${type.toUpperCase()}] ${message}`);

  setTimeout(() => notification.classList.add('show'), 10);
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => document.body.removeChild(notification), 500);
  }, 3000);
}

// Función para mostrar indicador de carga mejorado
function showLoadingIndicator() {
  const loadingOverlay = document.createElement('div');
  loadingOverlay.className = 'loading-overlay';
  loadingOverlay.innerHTML = `
    <div class="loading-spinner"></div>
    <p>Procesando imagen 1/${uploadedImagesData.length}...</p>
  `;
  document.body.appendChild(loadingOverlay);
  setTimeout(() => loadingOverlay.classList.add('show'), 10);
}

// Función para ocultar indicador de carga
function hideLoadingIndicator() {
  const loadingOverlay = document.querySelector('.loading-overlay');
  if (loadingOverlay) {
    loadingOverlay.classList.remove('show');

    setTimeout(() => {
      if (document.body.contains(loadingOverlay)) {
        document.body.removeChild(loadingOverlay);
      }
    }, 300);
  }
}

// Función para calcular el precio total (Actualizada para múltiples imágenes)
function updateTotalPrice() {
  if (uploadedImagesData.length === 0) {
    document.getElementById("total-price-button").innerText = `S/. 0.00`;
    return;
  }

  const quantity = parseInt(document.querySelector('.quantity-field input').value) || 1;

  // Use the sizePicker instance to get the value instead of DOM selection
  selectedSize = sizePicker.getValue();

  let pricePerUnit = 0;

  if (selectedSize) {
    pricePerUnit = currentSizePrices[selectedSize] || 0;
  }

  // Calculate the total price considering all images
  const totalPrice = pricePerUnit * quantity * uploadedImagesData.length;

  // Round
  const roundedPrice = totalPrice.toFixed(2);

  // Update total price text on button
  document.getElementById("total-price-button").innerText = `S/. ${roundedPrice}`;
}

// Exponer la función al ámbito global para evitar problemas con `onclick`
window.addToCart = addToCart;