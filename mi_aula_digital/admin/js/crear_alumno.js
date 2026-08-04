document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalAlumno');
  const abrir = document.getElementById('abrirModalAlumno');
  const cerrar = document.getElementById('cerrarModalAlumno');
  const form = document.getElementById('formAlumno');

  const idAlumno = document.getElementById('id_alumno');
  const titulo = document.getElementById('tituloModalAlumno');

  const modalEliminar = document.getElementById('modalEliminarAlumno');
  const cerrarEliminar = document.getElementById('cerrarEliminarAlumno');
  const formEliminar = document.getElementById('formEliminarAlumno');
  const idEliminar = document.getElementById('idEliminarAlumno');

  // ===============================
  // ABRIR MODAL NUEVO
  // ===============================

  if (abrir) {
    abrir.addEventListener('click', () => {
      form.reset();

      idAlumno.value = '';

      titulo.textContent = 'Registrar alumno';

      modal.style.display = 'flex';
    });
  }

  // ===============================
  // CERRAR MODAL REGISTRO
  // ===============================

  if (cerrar) {
    cerrar.addEventListener('click', () => {
      modal.style.display = 'none';
    });
  }

  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
  }

  // ===============================
  // GUARDAR / EDITAR
  // ===============================

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const datos = new FormData(form);

      fetch('process/crear_alumno.php', {
        method: 'POST',
        body: datos,
      })
        .then((r) => r.text())

        .then((res) => {
          alert(res);

          location.reload();
        })

        .catch((err) => {
          console.error(err);

          alert('Error al guardar alumno');
        });
    });
  }

  // ===============================
  // EDITAR ALUMNO
  // ===============================

  document.querySelectorAll('.editarAlumno').forEach((btn) => {
    btn.addEventListener('click', () => {
      const fila = btn.closest('tr');

      const datos = fila.querySelectorAll('td');

      const nombreCompleto = datos[0].textContent.trim();

      const curp = datos[1].textContent.trim();

      idAlumno.value = btn.dataset.id;

      let partes = nombreCompleto.split(' ');

      form.nombre.value = partes[0] || '';

      form.primer_apellido.value = partes[1] || '';

      form.segundo_apellido.value = partes.slice(2).join(' ') || '';

      form.curp.value = curp;

      const grupoTexto = datos[2].innerText.trim();

      for (let opcion of form.id_grupo.options) {
        if (opcion.text.includes(grupoTexto)) {
          opcion.selected = true;

          break;
        }
      }

      titulo.textContent = 'Editar alumno';

      modal.style.display = 'flex';
    });
  });

  // ===============================
  // ABRIR ELIMINAR
  // ===============================

  document.querySelectorAll('.eliminarAlumno').forEach((btn) => {
    btn.addEventListener('click', () => {
      idEliminar.value = btn.dataset.id;

      modalEliminar.style.display = 'flex';
    });
  });

  // ===============================
  // CERRAR ELIMINAR
  // ===============================

  if (cerrarEliminar) {
    cerrarEliminar.addEventListener('click', () => {
      modalEliminar.style.display = 'none';
    });
  }

  // ===============================
  // ELIMINAR ALUMNO
  // ===============================

  if (formEliminar) {
    formEliminar.addEventListener('submit', (e) => {
      e.preventDefault();

      const datos = new FormData(formEliminar);

      datos.append('accion', 'eliminar');

      fetch('process/crear_alumno.php', {
        method: 'POST',
        body: datos,
      })
        .then((r) => r.text())

        .then((res) => {
          alert(res);

          location.reload();
        })

        .catch((err) => {
          console.error(err);

          alert('Error al eliminar alumno');
        });
    });
  }
});

// ===============================
// SOLO LETRAS
// ===============================

function soloLetras(input) {
  input.value = input.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]/g, '');
}

// ===============================
// VALIDAR CURP
// ===============================

function validarCURP(input) {
  input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');

  if (input.value.length > 18) {
    input.value = input.value.substring(0, 18);
  }
}
