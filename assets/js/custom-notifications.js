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
