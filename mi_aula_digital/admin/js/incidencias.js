document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalIncidencia');
  const abrir = document.getElementById('abrirModalIncidencia');
  const cerrar = document.getElementById('cerrarModalIncidencia');

  const form = document.getElementById('formIncidencia');

  const grupo = document.getElementById('id_grupo');
  const alumno = document.getElementById('id_alumno');

  const titulo = document.getElementById('tituloModalIncidencia');
  const btnGuardar = document.getElementById('btnGuardarIncidencia');
  const idIncidencia = document.getElementById('id_incidencia');

  let modoEditar = false;

  //==========================================
  // ABRIR NUEVA INCIDENCIA
  //==========================================

  abrir.addEventListener('click', () => {
    modoEditar = false;

    form.reset();

    alumno.innerHTML = "<option value=''>Selecciona alumno</option>";

    idIncidencia.value = '';

    titulo.textContent = 'Registrar incidencia';

    btnGuardar.textContent = 'Guardar';

    modal.style.display = 'flex';
  });

  //==========================================
  // CERRAR
  //==========================================

  cerrar.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });

  //==========================================
  // CARGAR ALUMNOS
  //==========================================

  function cargarAlumnos(idGrupo, alumnoSeleccionado = null) {
    alumno.innerHTML = '<option>Cargando...</option>';

    return fetch(
      `process/crear_incidencias.php?action=get_alumnos&id_grupo=${idGrupo}`
    )
      .then((r) => r.json())

      .then((data) => {
        alumno.innerHTML = "<option value=''>Selecciona alumno</option>";

        data.forEach((a) => {
          alumno.innerHTML += `
                        <option value="${a.ID_Alumno}">
                            ${a.Nombre}
                        </option>
                    `;
        });

        if (alumnoSeleccionado) alumno.value = alumnoSeleccionado;
      });
  }

  grupo.addEventListener('change', () => {
    if (!grupo.value) {
      alumno.innerHTML = "<option value=''>Selecciona alumno</option>";

      return;
    }

    cargarAlumnos(grupo.value);
  });

  //==========================================
  // EDITAR
  //==========================================

  document.querySelectorAll('.editarIncidencia').forEach((btn) => {
    btn.addEventListener('click', () => {
      fetch(
        `process/crear_incidencias.php?action=get&id=${btn.dataset.id}`
      )
        .then((r) => r.json())

        .then(async (data) => {
          modoEditar = true;

          titulo.textContent = 'Editar incidencia';

          btnGuardar.textContent = 'Actualizar';

          idIncidencia.value = data.ID_Incidencia;

          grupo.value = data.ID_Grupo;

          await cargarAlumnos(data.ID_Grupo, data.ID_Alumno);

          form.tipo_incidencia.value = data.Tipo_Incidencia;

          form.descripcion.value = data.Descripcion;

          form.fecha.value = data.Fecha;

          modal.style.display = 'flex';
        });
    });
  });

  //==========================================
  // GUARDAR / ACTUALIZAR
  //==========================================

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    const datos = new FormData(form);

    const accion = modoEditar ? 'update' : 'insert';

    fetch(
      `process/crear_incidencias.php?action=${accion}`,
      {
        method: 'POST',

        body: datos,
      }
    )
      .then((r) => r.text())

      .then((res) => {
        alert(res);

        location.reload();
      });
  });

  //==========================================
  // ELIMINAR
  //==========================================

  const modalEliminar = document.getElementById('modalEliminarIncidencia');
  const idEliminar = document.getElementById('idEliminarIncidencia');
  const confirmarEliminar = document.getElementById(
    'confirmarEliminarIncidencia'
  );
  const cancelarEliminar = document.getElementById(
    'cancelarEliminarIncidencia'
  );

  document.querySelectorAll('.eliminarIncidencia').forEach((btn) => {
    btn.addEventListener('click', () => {
      idEliminar.value = btn.dataset.id;

      modalEliminar.style.display = 'flex';
    });
  });

  cancelarEliminar.addEventListener('click', () => {
    modalEliminar.style.display = 'none';

    idEliminar.value = '';
  });

  modalEliminar.addEventListener('click', (e) => {
    if (e.target === modalEliminar) {
      modalEliminar.style.display = 'none';

      idEliminar.value = '';
    }
  });

  confirmarEliminar.addEventListener('click', () => {
    const datos = new FormData();

    datos.append('id', idEliminar.value);

    fetch(
      'process/crear_incidencias.php?action=delete',
      {
        method: 'POST',

        body: datos,
      }
    )
      .then((r) => r.text())

      .then((res) => {
        modalEliminar.style.display = 'none';

        mostrarAlerta(res, 'success');

        setTimeout(() => {
          location.reload();
        }, 1000);
      })

      .catch(() => {
        mostrarAlerta('Error al eliminar la incidencia.', 'error');
      });
  });
});
