document.addEventListener("DOMContentLoaded", () => {
    const openBtn = document.getElementById("openLogoutModal");
    const closeBtn = document.getElementById("closeLogoutModal");
    const overlay = document.getElementById("logoutOverlay");
    const modal = document.getElementById("logoutModal");

    // Evento para abrir el modal al presionar Cerrar sesión
    if (openBtn && overlay && modal) {
        openBtn.addEventListener("click", (e) => {
            e.preventDefault();
            overlay.style.display = "flex";
            setTimeout(() => {
                overlay.classList.add("active");
                modal.classList.add("active");
            }, 10);
        });
    }

    // Función reutilizable para ocultar el modal
    const closeModal = () => {
        if (overlay && modal) {
            overlay.classList.remove("active");
            modal.classList.remove("active");
            setTimeout(() => {
                overlay.style.display = "none";
            }, 250);
        }
    };

    // Cerrar con botón Cancelar
    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    
    // Cerrar si se hace clic fuera del recuadro blanco
    if (overlay) {
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) closeModal();
        });
    }
});

// EVENTOS PARA EL MODAL DE NUEVA INCIDENCIA (Añadir a js/app.js)
const btnIncidencia = document.getElementById("btnNuevaIncidencia");
if (btnIncidencia) {
    btnIncidencia.addEventListener("click", () => {
        // Tu lógica equivalente para abrir ventanas modales de formularios
        alert("Desplegar formulario de Incidencias");
    });
}