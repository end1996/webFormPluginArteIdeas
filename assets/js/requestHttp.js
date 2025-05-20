// PETICIONES HTTP --------------------------------------------------------------------------------------------------------

// DIMENSIONES | PRECIOS ---------------------------------------
let customDimensionObj = null; // Variable global para la dimensión personalizada
const getDimensions = async () => {
    try {

        //if ( siteUrl == null || siteUrl == undefined ) { return; }

        const responseDimensions = await fetch(window.siteUrl+'/wp-json/api/v1/dimensiones');
        const responsePrice = await fetch(window.siteUrl+'/wp-json/api/v1/dimensiones_precio');

        if (!responseDimensions.ok || !responsePrice.ok ) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const dimensionsData = await responseDimensions.json();
        const priceData = await responsePrice.json();
        const dimensiones = dimensionsData.custom_dimensions_data;
        const precios = priceData.price_data;

        // Guardar la dimensión personalizada y filtrar del array
        customDimensionObj = dimensiones.find(d => d.is_custom);
        const dimensionesFiltradas = dimensiones.filter(d => !d.is_custom);

        dimensionesFiltradas.forEach(dimension => {
            const key = `${dimension.width}X${dimension.height}`;

            precios.forEach(precio => {
                if (precio.dimension_id == dimension.id) {
                    if (!precios_por_cantidad[precio.quantity] ) precios_por_cantidad[precio.quantity] = {};
                    if (!dimension_precio_data[precio.quantity] ) dimension_precio_data[precio.quantity] = {};

                    precios_por_cantidad[precio.quantity][key] = precio.price;
                    dimension_precio_data[precio.quantity][key] = precio.variation_id;
                }
            });
        });
        
        initializeSizePicker();
        console.log("Picker inicializado");
        

    } catch (error) {
        console.error("Error al obtener dimensiones:", error);
    }
}

// MARCOS --------------------------------------------
const getFrames = async ( url ) => {
    try{

        //if ( urlGeneral == null || siteUrl == undefined ) { return; }

        const response = await fetch(window.siteUrl+'/wp-json/api/v1/marcos');

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        let html = '';
        const framesData = await response.json();
        const frames = framesData.frames_data;
        let i = 1;

        frames.forEach(marco => {

            html+= `
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
            `
            i++;
            
        });

        document.getElementById('frame-picker').innerHTML = html;

        initializeFramePicker();
        console.log("Picker de marcos inicializado");
    } catch (error) {
        console.error("Error al obtener los marcos:", error);
    }
}
// PETICIONES HTTP --------------------------------------------------------------------------------------------------------