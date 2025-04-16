// Variable global para seleccion de tamaños y almacenar múltiples imágenes
var selectedOptionSize;
let currentSelectedFrame = 'frame1'; // Valor por defecto para el marco
let uploadedImagesData = []; // Array para almacenar múltiples imágenes
let sizePicker;

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


document.addEventListener('DOMContentLoaded', function () {
  initializeSizePicker();
  initializeFramePicker();
  setupEventListeners();
  
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
  const sizeOptions = Object.keys(sizePrices).map(value => ({
    value: value,
    label: `${value.replace("X", "cm Ancho X ")}cm Alto => (S/.${sizePrices[value].toFixed(2)})`
  }));

  sizePicker = new iOSPicker({
    container: document.getElementById('size-picker'),
    items: sizeOptions,
    initialValue: '10X15',
    onChange: (value) => {
      // console.log('Size picker changed to:', value);
      updateTotalPrice();
    }
  });
}

function initializeFramePicker() {
  const framePicker = document.getElementById('frame-picker');
  if (!framePicker) return;

  const pickerItems = framePicker.querySelectorAll('.swiper-slide');
  pickerItems.forEach(item => {
    item.addEventListener('click', function () {
      if (uploadedImagesData.length === 0) {
        showNotification('Sube una imagen primero', 'error');
        return;
      }
      pickerItems.forEach(i => i.classList.remove('selected'));
      item.classList.add('selected');
      currentSelectedFrame = item.dataset.value;
    });
  });
}

function setupEventListeners() {
  document.querySelector('.quantity-field input').addEventListener('input', updateTotalPrice);
  document.getElementById("custom-size__width").addEventListener("input", handleCustomSize);
  document.getElementById("custom-size__height").addEventListener("input", handleCustomSize);
  document.querySelector('.add-to-cart-btn').addEventListener('click', addToCart);
}

function handleCustomSize() {
  if (document.querySelector('input[name="size-type"]:checked').value === 'custom') {
    updateTotalPrice();
  }
}

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
  const files = e.target.files;

  // Si hay archivos, cambiamos el estado visual
  if (files.length > 0) {
    uploadElements.classList.add('has-images');
  }

  for (let i = 0; i < files.length; i++) {
    const file = files[i];

    // Verificar que sea una imagen
    if (!file.type.match('image.*')) continue;

    // Generar un ID único para esta imagen
    const imageId = 'img_' + Date.now() + '_' + i;

    // Almacenar la imagen en la variable global con su ID
    uploadedImagesData.push({
      id: imageId,
      file: file
    });

    console.log("Imagen añadida:", imageId);

    const reader = new FileReader();

    reader.onload = function (event) {
      // Crear el elemento de imagen
      const imageItem = document.createElement('div');
      imageItem.className = 'image-item';
      imageItem.dataset.imageId = imageId; // Almacenar el ID para referencia

      const img = document.createElement('img');
      img.src = event.target.result;
      img.alt = 'Imagen subida';

      const removeBtn = document.createElement('button');
      removeBtn.className = 'remove-image-btn';
      removeBtn.textContent = 'X';
      removeBtn.addEventListener('click', function (e) {
        e.stopPropagation(); // Evitar que el clic se propague al área de upload

        // Eliminar la imagen del array global
        uploadedImagesData = uploadedImagesData.filter(img => img.id !== imageId);
        console.log("Imagen eliminada:", imageId);

        // Eliminar la vista previa de la imagen
        imageItem.remove();

        // Si no quedan imágenes, restauramos el estado visual
        if (imagesGrid.children.length === 0) {
          uploadElements.classList.remove('has-images');
        }

        // Actualizar el contador de imágenes
        updateImageCounter();

        // Actualizar el precio total
        updateTotalPrice();
      });

      imageItem.appendChild(img);
      imageItem.appendChild(removeBtn);
      imagesGrid.appendChild(imageItem);

      // Actualizar el contador de imágenes
      updateImageCounter();

      // Actualizar el precio total
      updateTotalPrice();
    };

    reader.readAsDataURL(file);
  }

  // Resetear el input para permitir subir el mismo archivo otra vez
  fileInput.value = '';
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
  const minWidth = widthCm * PIXELS_PER_CM;
  const minHeight = heightCm * PIXELS_PER_CM;
  return { minWidth, minHeight };
}

// Función para analizar los píxeles de la imagen
function analyzeImagePixels(img, imageSize, onSuccess, onError) {
  const { minWidth, minHeight } = getMinimumPixelsForImage(imageSize);

  if (img.naturalWidth === 0 || img.naturalHeight === 0) {
    console.log("⚠️ La imagen aún no se ha cargado completamente.");
    onError("La imagen no se cargó correctamente.");
    return;
  }

  // Validar dimensiones mínimas dinámicamente
  if (img.naturalWidth < minWidth || img.naturalHeight < minHeight) {
    console.log(`❌ La imagen no cumple con las dimensiones mínimas: ${img.naturalWidth}x${img.naturalHeight}`);
    onError(`Tu imagen debe tener al menos ${minWidth}x${minHeight} píxeles para un tamaño de ${imageSize.replace("X", "x")} cm.`);
    return;
  }

  console.log(`✅ Imagen válida: ${img.naturalWidth}x${img.naturalHeight}`);
  onSuccess();
}

// Modificar el flujo de subida de imágenes para incluir la validación dinámica
function handleFiles(e) {
  const files = e.target.files;

  if (files.length > 0) {
    uploadElements.classList.add('has-images');
  }

  for (let i = 0; i < files.length; i++) {
    const file = files[i];

    // Verificar que sea una imagen
    if (!file.type.match('image.*')) continue;

    const reader = new FileReader();

    reader.onload = function (event) {
      const img = new Image();
      img.src = event.target.result;

      img.onload = function () {
        // Obtener el tamaño de la imagen seleccionado en el picker
        const selectedSize = sizePicker.getValue(); // Ejemplo: "10X15"

        // Validar la calidad de la imagen según el tamaño seleccionado
        analyzeImagePixels(
          img,
          selectedSize,
          () => {
            // Si la imagen es válida, agregarla al array y mostrarla
            const imageId = 'img_' + Date.now() + '_' + i;

            uploadedImagesData.push({
              id: imageId,
              file: file,
            });

            console.log("Imagen añadida:", imageId);

            const imageItem = document.createElement('div');
            imageItem.className = 'image-item';
            imageItem.dataset.imageId = imageId;

            const imgElement = document.createElement('img');
            imgElement.src = event.target.result;
            imgElement.alt = 'Imagen subida';

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-image-btn';
            removeBtn.textContent = 'X';
            removeBtn.addEventListener('click', function (e) {
              e.stopPropagation();

              uploadedImagesData = uploadedImagesData.filter(img => img.id !== imageId);
              console.log("Imagen eliminada:", imageId);

              imageItem.remove();

              if (imagesGrid.children.length === 0) {
                uploadElements.classList.remove('has-images');
              }

              updateImageCounter();
              updateTotalPrice();
            });

            imageItem.appendChild(imgElement);
            imageItem.appendChild(removeBtn);
            imagesGrid.appendChild(imageItem);

            updateImageCounter();
            updateTotalPrice();
          },
          (errorMessage) => {
            // Si la imagen no es válida, mostrar un mensaje de error
            showNotification(errorMessage, 'error');
          }
        );
      };
    };

    reader.readAsDataURL(file);
  }

  fileInput.value = '';
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
  }

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
    setTimeout(() => document.body.removeChild(notification), 300);
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
  const selectedSize = sizePicker.getValue();
  
  let pricePerUnit = 0;

  if (selectedSize) {
    pricePerUnit = sizePrices[selectedSize] || 0;
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

//SCROLL DE CANTIDAD
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