/* calificaciones.js */
document.addEventListener("DOMContentLoaded", () => {
    const modalAlta = document.getElementById('modalCalificacion');
    const modalVisor = document.getElementById('modalVisor');

    const btnAbrirModal = document.getElementById('btnAbrirModal');
    const btnXModal = document.getElementById('btnXModal');
    const btnAbrirVisor = document.getElementById('btnAbrirVisor');
    const btnCerrarVisor = document.getElementById('btnCerrarVisor');

    const botonesBimestre = document.querySelectorAll('.btn-bimestre');
    const selectGrupoFiltro = document.querySelector('.select-grupo-filter');

    // Elementos dinámicos del modal
    const modalTituloCalif = document.getElementById('modalTituloCalif');
    const inputAccionCalif = document.getElementById('inputAccionCalif');
    const inputIdCalificacion = document.getElementById('inputIdCalificacion');
    const contenedorGrupoRegistro = document.getElementById('contenedorGrupoRegistro');
    const contenedorAlumnoRegistro = document.getElementById('contenedorAlumnoRegistro');
    const contenedorAlumnoTexto = document.getElementById('contenedorAlumnoTexto');
    const inputAlumnoTextoFijo = document.getElementById('inputAlumnoTextoFijo');
    const modalSelectGrupo = document.getElementById('modal_select_grupo');
    const modalSelectAlumno = document.getElementById('modal_select_alumno');
    const modalSelectCampo = document.getElementById('modal_select_campo');
    const modalSelectBimestre = document.getElementById('modal_select_bimestre');
    const modalInputCalificacion = document.getElementById('modal_input_calificacion');
    const btnSubmitCalifText = document.getElementById('btnSubmitCalifText');
    const formCalificacionModal = document.getElementById('formCalificacionModal');

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

    // FILTRADO INTERACTIVO DE LA TABLA PRINCIPAL
    function aplicarFiltros() {
        const bimestreActivo = document.querySelector('.btn-bimestre.active').getAttribute('data-bimestre');
        const grupoSeleccionado = selectGrupoFiltro ? selectGrupoFiltro.value : '';

        const filas = document.querySelectorAll('.fila-calificacion');
        filas.forEach(fila => {
            const filaBimestre = fila.getAttribute('data-bimestre');
            const filaGrupo = fila.getAttribute('data-grupo-tabla');

            const coincideBimestre = (filaBimestre === bimestreActivo.toString());
            const coincideGrupo = (grupoSeleccionado === '' || filaGrupo === grupoSeleccionado);

            if (coincideBimestre && coincideGrupo) {
                fila.style.display = "";
            } else {
                fila.style.display = "none";
            }
        });
    }

    // Inicializar filtros de la vista de la tabla
    aplicarFiltros();

    botonesBimestre.forEach(btn => {
        btn.addEventListener('click', () => {
            botonesBimestre.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            aplicarFiltros();
        });
    });

    if (selectGrupoFiltro) {
        selectGrupoFiltro.addEventListener('change', aplicarFiltros);
    }

    // CONTROL DE MODALES (ABRIR PARA REGISTRAR)
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', () => {
            modalTituloCalif.textContent = "Agregar calificación";
            inputAccionCalif.value = "registrar";
            inputIdCalificacion.value = "";
            btnSubmitCalifText.textContent = "Guardar calificación";

            // Mostrar selects de registro y ocultar campo de texto fijo
            contenedorGrupoRegistro.style.display = "block";
            contenedorAlumnoRegistro.style.display = "block";
            contenedorAlumnoTexto.style.display = "none";
            modalSelectGrupo.setAttribute('required', 'required');
            modalSelectAlumno.setAttribute('required', 'required');

            if (formCalificacionModal) formCalificacionModal.reset();
            modalSelectAlumno.disabled = true;
            modalSelectAlumno.innerHTML = '<option value="">Selecciona primero un grupo</option>';

            modalAlta.classList.add('active');
        });
    }

    if (btnXModal) btnXModal.addEventListener('click', () => modalAlta.classList.remove('active'));
    
    if (btnAbrirVisor) {
        btnAbrirVisor.addEventListener('click', () => {
            generarReporteBimestreActual();
            modalVisor.classList.add('active');
        });
    }
    if (btnCerrarVisor) btnCerrarVisor.addEventListener('click', () => modalVisor.classList.remove('active'));

    window.addEventListener('click', e => {
        if (e.target === modalAlta) modalAlta.classList.remove('active');
        if (e.target === modalVisor) modalVisor.classList.remove('active');
    });

    // CONTROL DEL MODAL (ABRIR PARA EDITAR)
    document.querySelectorAll('.btn-editar-calif').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombreAlumno = this.getAttribute('data-alumno-nombre');
            const idCampo = this.getAttribute('data-campo');
            const bimestre = this.getAttribute('data-bimestre');
            const calificacion = this.getAttribute('data-calificacion');

            modalTituloCalif.textContent = "Editar calificación";
            inputAccionCalif.value = "editar";
            inputIdCalificacion.value = id;
            btnSubmitCalifText.textContent = "Guardar cambios";

            // Ocultar selects de grupo/alumno nuevos y mostrar el texto estático con el nombre del alumno
            contenedorGrupoRegistro.style.display = "none";
            contenedorAlumnoRegistro.style.display = "none";
            modalSelectGrupo.removeAttribute('required');
            modalSelectAlumno.removeAttribute('required');

            contenedorAlumnoTexto.style.display = "block";
            inputAlumnoTextoFijo.value = nombreAlumno;

            // Rellenar valores actuales
            modalSelectCampo.value = idCampo;
            modalSelectBimestre.value = bimestre;
            modalInputCalificacion.value = calificacion;

            modalAlta.classList.add('active');
        });
    });

    // SELECTOR DE GRUPO DINÁMICO EN EL MODAL (PARA REGISTRO)
    if (modalSelectGrupo && modalSelectAlumno) {
        const listaCompletaAlumnos = Array.from(modalSelectAlumno.options);

        modalSelectGrupo.addEventListener('change', function() {
            const grupoId = this.value;
            modalSelectAlumno.innerHTML = '';

            if (grupoId === '') {
                modalSelectAlumno.disabled = true;
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Selecciona primero un grupo';
                modalSelectAlumno.appendChild(opt);
            } else {
                modalSelectAlumno.disabled = false;
                
                const optDef = document.createElement('option');
                optDef.value = '';
                optDef.textContent = 'Seleccionar alumno';
                modalSelectAlumno.appendChild(optDef);

                listaCompletaAlumnos.forEach(option => {
                    if (option.getAttribute('data-grupo') === grupoId) {
                        modalSelectAlumno.appendChild(option.cloneNode(true));
                    }
                });
            }
        });
    }

    // RENDERIZADOR DEL RESUMEN GRUPAL
    function generarReporteBimestreActual() {
        const bimestreActivo = document.querySelector('.btn-bimestre.active').getAttribute('data-bimestre');
        const cuerpoTabla = document.getElementById('visorReporteCuerpo');
        const txtBim = document.getElementById('visorBimestreTxt');
        
        if (txtBim) txtBim.textContent = bimestreActivo;
        cuerpoTabla.innerHTML = "";

        if (typeof todasLasCalificaciones === 'undefined' || todasLasCalificaciones.length === 0) {
            cuerpoTabla.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">No hay registros.</td></tr>`;
            return;
        }

        const notasBimestre = todasLasCalificaciones.filter(n => parseInt(n.bimestre) === parseInt(bimestreActivo));
        const alumnosMap = {};

        notasBimestre.forEach(n => {
            const nombre = `${n.nombre} ${n.primer_apellido} ${n.segundo_apellido}`;
            if (!alumnosMap[nombre]) {
                alumnosMap[nombre] = {
                    grupo: `${n.grado}${n.grupo}`,
                    campos: [],
                    suma: 0
                };
            }
            const val = parseFloat(n.calificacion_numerica);
            const valFormateado = val % 1 === 0 ? val.toFixed(0) : val.toFixed(1);
            
            alumnosMap[nombre].campos.push(`${n.nombre_campo}: <span class="nota-bold">${valFormateado}</span>`);
            alumnosMap[nombre].suma += val;
        });

        Object.keys(alumnosMap).forEach(nombre => {
            const data = alumnosMap[nombre];
            const promedio = (data.suma / data.campos.length).toFixed(1);
            const listadoCampos = data.campos.join(' &nbsp;&nbsp; ');

            let fila = `
                <tr>
                    <td><span class="badge-grupo-dark">${data.grupo}</span></td>
                    <td class="student-name-modal">${nombre}</td>
                    <td class="materias-list-modal">${listadoCampos}</td>
                    <td class="promedio-modal">${promedio}</td>
                </tr>
            `;
            cuerpoTabla.innerHTML += fila;
        });
    }
});