/* incidencias.js */
document.addEventListener("DOMContentLoaded", function() {
    const modalInc = document.getElementById('modalIncidencia');
    const btnAbrirInc = document.getElementById('btnAbrirModalInc');
    const btnCerrarX = document.getElementById('btnCerrarX');

    const selectGrupo = document.getElementById('select_grupo_modal');
    const selectAlumno = document.getElementById('select_alumno_modal');

    // Elementos dinámicos del modal para alternar Registro / Edición
    const modalTitulo = document.getElementById('modalTitulo');
    const inputAccionModal = document.getElementById('inputAccionModal');
    const inputIdIncidencia = document.getElementById('inputIdIncidencia');
    const selectTipoIncidencia = document.getElementById('selectTipoIncidencia');
    const textareaDescripcion = document.getElementById('textareaDescripcion');
    const btnSubmitModalText = document.getElementById('btnSubmitModalText');
    
    const grupoContenedorSelects = document.getElementById('grupoContenedorSelects');
    const alumnoContenedorSelects = document.getElementById('alumnoContenedorSelects');
    const alumnoContenedorTexto = document.getElementById('alumnoContenedorTexto');
    const inputAlumnoTextoFijo = document.getElementById('inputAlumnoTextoFijo');
    
    const campoEstadoModal = document.getElementById('campoEstadoModal');
    const selectEstadoIncidencia = document.getElementById('selectEstadoIncidencia');

    // =======================================================
    // OCULTAR MENSAJE DE ALERTA AUTOMÁTICAMENTE (A LOS 4 SEGUNDOS)
    // =======================================================
    const alertaMensaje = document.getElementById('alerta-mensaje');
    if (alertaMensaje) {
        setTimeout(() => {
            alertaMensaje.style.transition = "opacity 0.5s ease";
            alertaMensaje.style.opacity = "0";
            setTimeout(() => alertaMensaje.remove(), 500); // Lo elimina del DOM tras el desvanecimiento
        }, 4000); // 4000 milisegundos = 4 segundos
    }

    // =======================================================
    // CONTROL DEL MODAL (ABRIR PARA REGISTRAR)
    // =======================================================
    if (btnAbrirInc) {
        btnAbrirInc.addEventListener('click', () => {
            modalTitulo.textContent = "Nueva incidencia";
            inputAccionModal.value = "registrar";
            inputIdIncidencia.value = "";
            
            // Limpiar campos
            selectGrupo.value = "";
            selectAlumno.innerHTML = '<option value="">Alumno</option>';
            selectAlumno.disabled = true;
            selectTipoIncidencia.value = "";
            textareaDescripcion.value = "";
            
            // Mostrar selectores de creación y ocultar campos exclusivos de edición
            grupoContenedorSelects.style.display = "block";
            alumnoContenedorSelects.style.display = "block";
            alumnoContenedorTexto.style.display = "none";
            campoEstadoModal.style.display = "none";
            
            selectGrupo.setAttribute('required', 'required');
            selectAlumno.setAttribute('required', 'required');

            btnSubmitModalText.textContent = "Registrar incidencia";
            modalInc.classList.add('active');
        });
    }

    // =======================================================
    // CONTROL DEL MODAL (ABRIR PARA EDITAR)
    // =======================================================
    document.querySelectorAll('.btn-editar-inc').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const tipo = this.getAttribute('data-tipo');
            const descripcion = this.getAttribute('data-descripcion');
            const estado = this.getAttribute('data-estado');
            const nombreAlumno = this.getAttribute('data-alumno');

            modalTitulo.textContent = "Editar incidencia";
            inputAccionModal.value = "editar";
            inputIdIncidencia.value = id;

            // Rellenar datos
            selectTipoIncidencia.value = tipo;
            textareaDescripcion.value = descripcion;
            selectEstadoIncidencia.value = estado;
            inputAlumnoTextoFijo.value = nombreAlumno;

            // Ocultar selects de creación y mostrar texto fijo del alumno y estado
            grupoContenedorSelects.style.display = "none";
            alumnoContenedorSelects.style.display = "none";
            selectGrupo.removeAttribute('required');
            selectAlumno.removeAttribute('required');

            alumnoContenedorTexto.style.display = "block";
            campoEstadoModal.style.display = "block";

            btnSubmitModalText.textContent = "Guardar cambios";
            modalInc.classList.add('active');
        });
    });

    // Cerrar modal con la 'X' o haciendo clic fuera
    if (btnCerrarX) btnCerrarX.addEventListener('click', () => modalInc.classList.remove('active'));
    window.addEventListener('click', e => {
        if (e.target === modalInc) modalInc.classList.remove('active');
    });

    // =======================================================
    // LÓGICA DE ENCADENAMIENTO DINÁMICO (FILTRAR ALUMNOS)
    // =======================================================
    if (selectGrupo && selectAlumno) {
        selectGrupo.addEventListener('change', function() {
            const idGrupoSeleccionado = this.value;
            
            selectAlumno.innerHTML = '<option value="">Alumno</option>';
            
            if (idGrupoSeleccionado === "") {
                selectAlumno.disabled = true;
                return;
            }

            if (typeof alumnosDisponibles !== 'undefined') {
                const alumnosFiltrados = alumnosDisponibles.filter(al => parseInt(al.id_grupo) === parseInt(idGrupoSeleccionado));

                alumnosFiltrados.forEach(al => {
                    const nombreCompleto = `${al.primer_apellido} ${al.segundo_apellido} ${al.nombre}`;
                    const option = document.createElement('option');
                    option.value = al.id_alumno;
                    option.textContent = nombreCompleto;
                    selectAlumno.appendChild(option);
                });

                selectAlumno.disabled = false;
            }
        });
    }
});