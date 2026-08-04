/* js/anuncios.js */
document.addEventListener("DOMContentLoaded", () => {
    const modalAnuncio = document.getElementById('modalAnuncio');
    const btnAbrirModal = document.getElementById('btnAbrirModal');
    const btnCancelarModal = document.getElementById('btnCancelarModal');
    const formAnuncio = document.getElementById('formAnuncio');

    // Elementos dinámicos del modal
    const modalTituloAnuncio = document.getElementById('modalTituloAnuncio');
    const inputAccionAnuncio = document.getElementById('inputAccionAnuncio');
    const inputIdPublicacion = document.getElementById('inputIdPublicacion');
    const inputTitulo = document.getElementById('titulo');
    const textareaContenido = document.getElementById('contenido');
    const selectGrupo = document.getElementById('grupo');
    const selectTipoAviso = document.getElementById('tipo_aviso');
    const btnSubmitAnuncioText = document.getElementById('btnSubmitAnuncioText');

    // =======================================================
    // OCULTAR MENSAJE DE ALERTA AUTOMÁTICAMENTE (4 SEGUNDOS)
    // =======================================================
    const alertaMensaje = document.getElementById('alerta-mensaje');
    if (alertaMensaje) {
        setTimeout(() => {
            alertaMensaje.style.transition = "opacity 0.5s ease";
            alertaMensaje.style.opacity = "0";
            setTimeout(() => alertaMensaje.remove(), 500);
        }, 4000);
    }

    // =======================================================
    // CONTROL DEL MODAL (ABRIR PARA CREAR NUEVO)
    // =======================================================
    const abrirModalCrear = () => {
        if (modalAnuncio) {
            modalTituloAnuncio.textContent = "Nueva Publicación";
            inputAccionAnuncio.value = "crear";
            inputIdPublicacion.value = "";
            btnSubmitAnuncioText.textContent = "Publicar anuncio";
            if (formAnuncio) formAnuncio.reset();
            modalAnuncio.classList.add('active');
        }
    };

    const cerrarModal = () => {
        if (modalAnuncio) {
            modalAnuncio.classList.remove('active');
            if (formAnuncio) formAnuncio.reset();
        }
    };

    // Listeners de Apertura y Cierre General
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', abrirModalCrear);
    }

    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', cerrarModal);
    }

    // Cerrar si se hace click fuera del modal
    window.addEventListener('click', (e) => {
        if (e.target === modalAnuncio) {
            cerrarModal();
        }
    });

    // =======================================================
    // CONTROL DEL MODAL (ABRIR PARA EDITAR)
    // =======================================================
    document.querySelectorAll('.btn-editar-anuncio').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const titulo = this.getAttribute('data-titulo');
            const contenido = this.getAttribute('data-contenido');
            const grupo = this.getAttribute('data-grupo');
            const tipo = this.getAttribute('data-tipo');

            modalTituloAnuncio.textContent = "Editar Publicación";
            inputAccionAnuncio.value = "editar";
            inputIdPublicacion.value = id;

            // Rellenar campos con los valores actuales
            inputTitulo.value = titulo;
            textareaContenido.value = contenido;
            selectGrupo.value = grupo ? grupo : "";
            selectTipoAviso.value = tipo ? tipo : "";

            btnSubmitAnuncioText.textContent = "Guardar cambios";
            modalAnuncio.classList.add('active');
        });
    });
});