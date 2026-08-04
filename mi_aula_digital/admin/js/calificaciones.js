document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalCalificacion');
  const abrir = document.getElementById('abrirModalCalificacion');
  const cerrar = document.getElementById('cerrarModalCalificacion');
  const form = document.getElementById('formCalificacion');

  const accion = document.getElementById('accion');
  const idCalificacion = document.getElementById('id_calificacion');

  const alumno = document.getElementById('id_alumno');
  const campo = document.getElementById('id_campo');
  const bimestre = document.getElementById('bimestre_modal');
  const nota = document.getElementById('calificacion');

  const titulo = document.getElementById('tituloModalCalificacion');

  const btnEliminar = document.getElementById('btnEliminarCalificacion');
  const btnGuardar = document.getElementById('btnGuardarCalificacion');

  const modalEliminar = document.getElementById('modalEliminarCalificacion');
  const confirmarEliminar = document.getElementById(
    'confirmarEliminarCalificacion'
  );
  const cancelarEliminar = document.getElementById(
    'cancelarEliminarCalificacion'
  );

  function configurarModal(tipo) {
    if (tipo === 'guardar') {
      titulo.innerHTML = 'Registrar calificación';

      btnGuardar.style.display = 'inline-block';
      btnGuardar.innerHTML = 'Guardar';

      btnEliminar.style.display = 'none';
    }

    if (tipo === 'editar') {
      titulo.innerHTML = 'Editar calificación';

      btnGuardar.style.display = 'inline-block';
      btnGuardar.innerHTML = 'Actualizar';

      btnEliminar.style.display = 'none';
    }

    if (tipo === 'eliminar') {
      titulo.innerHTML = 'Eliminar calificación';

      btnGuardar.style.display = 'none';

      btnEliminar.style.display = 'inline-block';
    }
  }

  function cargarCampos(calificaciones) {
    campo.innerHTML = `<option value="">Seleccionar campo</option>`;

    calificaciones.forEach((c) => {
      campo.innerHTML += `

            <option
            value="${c.ID_Campo}"
            data-id="${c.ID_Calificacion}"
            data-nota="${c.Calificacion_Numerica}">

            ${c.Nombre_Campo}

            </option>

            `;
    });
  }

  /*==============================
    NUEVA CALIFICACION
    ==============================*/

  abrir.onclick = () => {
    form.reset();

    accion.value = 'guardar';

    idCalificacion.value = '';

    configurarModal('guardar');

    modal.style.display = 'flex';
  };

  cerrar.onclick = () => {
    modal.style.display = 'none';
  };

  modal.onclick = (e) => {
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  };

  /*==============================
    CAMBIO DE CAMPO
    ==============================*/

  campo.addEventListener('change', () => {
    let opcion = campo.options[campo.selectedIndex];

    if (!opcion.dataset.id) {
      idCalificacion.value = '';

      nota.value = '';

      return;
    }

    idCalificacion.value = opcion.dataset.id;

    nota.value = opcion.dataset.nota;
  });

  /*==============================
    EDITAR
    ==============================*/

  document.querySelectorAll('.editarCalificacion').forEach((btn) => {
    btn.onclick = () => {
      let datos = new FormData();

      datos.append('accion', 'buscar');

      datos.append('id_alumno', btn.dataset.alumno);

      datos.append(
        'bimestre',
        document.querySelector('[name="bimestre"]').value
      );

      fetch('process/guardar_calificacion.php', {
        method: 'POST',

        body: datos,
      })
        .then((r) => r.json())

        .then((calificaciones) => {
          if (calificaciones.length === 0) {
            alert(
              'Este alumno no tiene calificaciones registradas en este bimestre.'
            );

            return;
          }

          alumno.value = btn.dataset.alumno;

          bimestre.value = document.querySelector('[name="bimestre"]').value;

          cargarCampos(calificaciones);

          accion.value = 'editar';

          configurarModal('editar');

          idCalificacion.value = '';

          nota.value = '';

          modal.style.display = 'flex';
        });
    };
  });

  /*==============================
    ELIMINAR
    ==============================*/

  document.querySelectorAll('.eliminarCalificacion').forEach((btn) => {
    btn.onclick = () => {
      let datos = new FormData();

      datos.append('accion', 'buscar');

      datos.append('id_alumno', btn.dataset.alumno);

      datos.append(
        'bimestre',
        document.querySelector('[name="bimestre"]').value
      );

      fetch('process/guardar_calificacion.php', {
        method: 'POST',

        body: datos,
      })
        .then((r) => r.json())

        .then((calificaciones) => {
          if (calificaciones.length === 0) {
            alert('Este alumno no tiene calificaciones para eliminar.');

            return;
          }

          alumno.value = btn.dataset.alumno;

          bimestre.value = document.querySelector('[name="bimestre"]').value;

          cargarCampos(calificaciones);

          accion.value = 'eliminar';

          configurarModal('eliminar');

          idCalificacion.value = '';

          nota.value = '';

          modal.style.display = 'flex';
        });
    };
  });

  /*==============================
    GUARDAR / EDITAR
    ==============================*/

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    if (accion.value === 'editar' && idCalificacion.value === '') {
      alert('Selecciona un campo con calificación para editar.');

      return;
    }

    if (accion.value === 'guardar' && nota.value === '') {
      alert('Ingresa una calificación.');

      return;
    }

    let datos = new FormData(form);

    fetch('process/guardar_calificacion.php', {
      method: 'POST',

      body: datos,
    })
      .then((r) => r.text())

      .then((res) => {
        alert(res);

        location.reload();
      })

      .catch(() => {
        alert('Error al guardar.');
      });
  });

  /*==============================
    ELIMINAR DESDE MODAL
    ==============================*/

  btnEliminar.onclick = () => {
    if (idCalificacion.value === '') {
      alert('Selecciona una calificación para eliminar.');

      return;
    }

    modalEliminar.style.display = 'flex';
  };

  cancelarEliminar.onclick = () => {
    modalEliminar.style.display = 'none';
  };

  modalEliminar.onclick = (e) => {
    if (e.target === modalEliminar) {
      modalEliminar.style.display = 'none';
    }
  };

  confirmarEliminar.onclick = () => {
    let datos = new FormData(form);

    datos.set('accion', 'eliminar');

    fetch('process/guardar_calificacion.php', {
      method: 'POST',

      body: datos,
    })
      .then((r) => r.text())

      .then((res) => {
        alert(res);

        location.reload();
      })

      .catch(() => {
        alert('Error al eliminar la calificación.');
      });
  };
});
