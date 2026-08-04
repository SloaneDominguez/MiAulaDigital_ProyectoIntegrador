/* js/anuncios.js */
document.addEventListener("DOMContentLoaded", () => {
    const modalAnuncio = document.getElementById('modalAnuncio');
    const btnAbrirModal = document.getElementById('btnAbrirModal');
    const btnCancelarModal = document.getElementById('btnCancelarModal');
    const formAnuncio = document.getElementById('formAnuncio');

    // Funciones de control del Modal
    const abrirModal = () => {
        if (modalAnuncio) modalAnuncio.classList.add('active');
    };

    const cerrarModal = () => {
        if (modalAnuncio) {
            modalAnuncio.classList.remove('active');
            if (formAnuncio) formAnuncio.reset(); // Limpia los inputs al cerrar
        }
    };

    // Listeners de Eventos
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModal);
    }

    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', cerrarModal);
    }

    // Cerrar si se hace click fuera de la caja blanca del modal
    window.addEventListener('click', (e) => {
        if (e.target === modalAnuncio) {
            cerrarModal();
        }
    });
});