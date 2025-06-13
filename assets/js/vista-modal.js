document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-imagen-personalizada');
    const modalImg = document.getElementById('modal-img-personalizada');
    document.querySelectorAll('.img-personalizada-modal').forEach(function(img) {
        img.addEventListener('click', function() {
            modalImg.src = this.dataset.img;
            modal.style.display = 'flex';
        });
    });
    modal.addEventListener('click', function(e) {
        // Cierra el modal si se hace click fuera de la imagen o en la imagen grande
        if (e.target === modal || e.target === modalImg) {
            modal.style.display = 'none';
            modalImg.src = '';
        }
    });
});
