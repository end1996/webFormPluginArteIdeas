document.addEventListener('DOMContentLoaded', function() {
    // Selecciona TODOS los enlaces que contengan 'my-account' en el menú de Elementor
    const accountLinks = document.querySelectorAll('.elementor-nav-menu a[href*="my-account"]');
    
    accountLinks.forEach(link => {
        // Cambia URL y texto según el estado de login
        if (document.body.classList.contains('logged-in')) {
            link.href = 'https://webformwp.reflexoperu.com.pe/my-account/';
            link.textContent = 'Mi cuenta';
        } else {
            link.href = 'https://webformwp.reflexoperu.com.pe/login/';
            link.textContent = 'Iniciar sesión';
        }
    });
});