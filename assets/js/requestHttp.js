// PETICIONES HTTP --------------------------------------------------------------------------------------------------------

// DIMENSIONES | PRECIOS ---------------------------------------
let customDimensionObj = null; // Variable global para la dimensión personalizada

const getDimensions = async () => {
  try {
    showSizePickerPlaceholder();

    const responseDimensions = await fetch(
      window.siteUrl + "/wp-json/api/v1/dimensiones"
    );
    const responsePrice = await fetch(
      window.siteUrl + "/wp-json/api/v1/dimensiones_precio_base"
    );

    if (!responseDimensions.ok || !responsePrice.ok)
      throw new Error(`Error HTTP`);

    const dimensionsData = await responseDimensions.json();
    const priceData = await responsePrice.json();

    // Obtener dimensión personalizada
    customDimensionObj = dimensionsData.custom_dimensions_data.find(
      (d) => d.is_custom
    );

    // Filtrar dimensiones predefinidas
    const dimensionesFiltradas = dimensionsData.custom_dimensions_data.filter(
      (d) => !d.is_custom
    );

    // Ordenar dimensiones por width y height
    dimensionesFiltradas.sort((a, b) => {
      if (a.width === b.width) {
        return a.height - b.height;
      }
      return a.width - b.width;
    });

    // Crear mapa de precios base para dimensiones predefinidas
    const dimensionPrecioBase = {};
    priceData.price_data.forEach((precio) => {
      const dimension = dimensionesFiltradas.find(
        (d) => d.id === precio.dimension_id
      );
      if (dimension) {
        const key = `${dimension.width}X${dimension.height}`;
        dimensionPrecioBase[key] = {
          price: precio.price,
          variation_id: precio.variation_id,
        };
      }
    });

    // Agregar dimensión personalizada al mapa si tiene custom_variation_id válido
    if (
      customDimensionObj &&
      customDimensionObj.custom_variation_id !== null &&
      customDimensionObj.custom_variation_id !== "0"
    ) {
      const precioCustom = priceData.price_data.find(
        (p) => p.variation_id === customDimensionObj.custom_variation_id
      );

      if (precioCustom) {
        dimensionPrecioBase["custom"] = {
          price: precioCustom.price,
          variation_id: precioCustom.variation_id,
        };
      }
    }

    // Guardar mapa globalmente
    window.dimensionPrecioBase = dimensionPrecioBase;

    // Inicializar picker
    await initializeSizePicker();

    hideSizePickerPlaceholder();
  } catch (error) {
    console.error("Error al obtener dimensiones:", error);
    hideSizePickerPlaceholder();
  }
};


// MARCOS --------------------------------------------
const getFrames = async (url) => {
  try {
    // Opcional: también puedes agregar placeholder para los marcos si lo necesitas
    // showFramePickerPlaceholder(); // Si implementas placeholder para marcos

    //if ( urlGeneral == null || siteUrl == undefined ) { return; }

    const response = await fetch(window.siteUrl + "/wp-json/api/v1/marcos");

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }

    let html = "";
    const framesData = await response.json();
    const frames = framesData.frames_data;
    let i = 1;

    frames.forEach((marco) => {
      html += `
                <div class="swiper-slide" data-value="frame${i}" onclick="calcularPrecioMarco(${marco.price})">
                    <img src="${url}assets/frames/${marco.frame_img}" alt="${marco.frame_type}">
                    <button class="zoom-icon-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path fill="#f0710a"
                                d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM184 296c0 13.3 10.7 24 24 24s24-10.7 24-24l0-64 64 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-64 0 0-64c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 64-64 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l64 0 0 64z" />
                        </svg>
                    </button>
                    <p class="price">S/. ${marco.price}</p>
                </div>
            `;
      i++;
    });

    document.getElementById("frame-picker").innerHTML = html;

    await initializeFramePicker();

    // hideFramePickerPlaceholder(); // Si implementas placeholder para marcos
  } catch (error) {
    console.error("Error al obtener los marcos:", error);
    // hideFramePickerPlaceholder(); // En caso de error
  }
};

// PETICIONES HTTP --------------------------------------------------------------------------------------------------------
