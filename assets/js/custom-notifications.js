// Función para mostrar notificaciones
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `notification ${type}`;
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => notification.classList.add("show"), 10);
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => document.body.removeChild(notification), 600);
  }, 2000);
}

// Función para mostrar indicador de carga dinámico
function showLoadingIndicator(totalImages) {
  const loadingOverlay = document.createElement("div");
  loadingOverlay.className = "loading-overlay";
  loadingOverlay.innerHTML = `
    <div class="loading-spinner"></div>
    <p>Procesando imagen 0/${totalImages}...</p>
  `;
  document.body.appendChild(loadingOverlay);
  setTimeout(() => loadingOverlay.classList.add("show"), 10);

  // Retornamos una función para actualizar el progreso
  return {
    updateProgress: (current) => {
      const progressText = loadingOverlay.querySelector("p");
      if (progressText) {
        progressText.textContent = `Procesando imagen ${current}/${totalImages}...`;
      }
    },
    remove: () => {
      loadingOverlay.classList.remove("show");
      setTimeout(() => loadingOverlay.remove(), 500); // Animación de fade out
    }
  };
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

// Alerta para descuentos aplicados con Yaycommerce
function showCustomAlert(message) {
  const alertOverlay = document.getElementById("custom-alert");
  const alertMessage = document.getElementById("custom-alert-message");
  const alertClose = document.getElementById("custom-alert-close");

  alertMessage.textContent = message;
  alertOverlay.classList.remove("hidden");

  alertClose.onclick = () => {
    alertOverlay.classList.add("hidden");
  };
}
