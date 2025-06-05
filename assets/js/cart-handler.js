jQuery(document).ready(function ($) {
  let previousValue = 0;

  // Función para evitar validaciones excesivas mientras escribe
  function debounce(func, wait) {
    let timeout;
    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  // Función principal de validación
  function handleQuantityChange(input) {
    const rawValue = input.val();
    const max = parseInt(input.attr("max")) || 100;
    const min = parseInt(input.attr("min")) || 1;

    if (rawValue === "") return;

    let currentValue = parseInt(rawValue);

    if (isNaN(currentValue)) return;

    if (currentValue > max) {
      input.val(max);
      showNotification(
        "¡La cantidad máxima permitida es " + max + "!",
        "error"
      );
      currentValue = max;
    }

    if (currentValue < min) {
      input.val(min);
      showNotification("La cantidad no puede ser menor a " + min, "error");
      currentValue = min;
    }

    if (currentValue !== previousValue) {
      previousValue = currentValue;
      input.closest("form").find('button[name="update_cart"]').trigger("click");
    }
  }

  const debouncedQuantityChange = debounce(function (input) {
    handleQuantityChange(input);
  }, 500);

  // Detecta escritura manual
  $(document).on("input", "input.qty", function () {
    const input = $(this);
    const value = input.val();
    if (value === "" || isNaN(parseInt(value))) return;
    debouncedQuantityChange(input);
  });

  // Detecta al salir del campo
  $(document).on("blur", "input.qty", function () {
    handleQuantityChange($(this));
  });

  // Detecta tecla Enter
  $(document).on("keydown", "input.qty", function (e) {
    if (e.key === "Enter") {
      const val = $(this).val();
      const min = parseInt($(this).attr("min")) || 1;

      if (val === "" || isNaN(parseInt(val)) || parseInt(val) < min) {
        e.preventDefault();
        $(this).val(min);
        showNotification(
          "Por favor ingresa una cantidad válida antes de continuar",
          "error"
        );
      }
    }
  });

  // ✅ Detecta clics en los botones de cantidad (+/-)
  $(document)
    .on("mousedown", 'input.qty[type="number"]', function () {
      previousValue = parseInt($(this).val()) || 1;
    })
    .on("mouseup", 'input.qty[type="number"]', function () {
      setTimeout(() => handleQuantityChange($(this)), 1000);
    });

  // Detecta clics en los botones personalizados de Botiga (+ y -)
  $(document).on(
    "click",
    ".botiga-quantity-plus, .botiga-quantity-minus",
    function (e) {
      e.preventDefault(); // Evita que el enlace haga scroll a arriba
      const input = $(this).siblings("input.qty");

      // Espera a que se actualice el valor
      setTimeout(() => {
        handleQuantityChange(input);
      }, 1000); // Puedes ajustar el tiempo si es necesario
    }
  );

  // 🚫 Bloquea el checkout si hay cantidades vacías o inválidas
  $(document).on("click", "a.checkout-button", function (e) {
    let isValid = true;

    $("input.qty").each(function () {
      const val = $(this).val();
      const min = parseInt($(this).attr("min")) || 1;

      if (val === "" || isNaN(parseInt(val)) || parseInt(val) < min) {
        isValid = false;
        $(this).val(min);
        showNotification(
          "No puedes proceder al checkout con cantidades vacías o inválidas.",
          "error"
        );
        $(this).focus();
      }
    });

    if (!isValid) {
      e.preventDefault(); // Bloquea el enlace de checkout
    }
  });
});
