document.addEventListener('DOMContentLoaded', () => {
  //=========================================
  // MODALES
  //=========================================

  const modalCrear = document.getElementById('modalCrearGrupo');
  const modalEditar = document.getElementById('modalEditarGrupo');
  const modalEliminar = document.getElementById('modalEliminarGrupo');

  const formCrear = document.getElementById('formCrearGrupo');
  const formEditar = document.getElementById('formEditarGrupo');
  const formEliminar = document.getElementById('formEliminarGrupo');

  //=========================================
  // BOTONES
  //=========================================

  document.getElementById('btnNuevoGrupo').onclick = () => {
    formCrear.reset();
    modalCrear.style.display = 'flex';
  };

  document.getElementById('cerrarCrearGrupo').onclick = () =>
    cerrarModal(modalCrear);
  document.getElementById('cerrarEditarGrupo').onclick = () =>
    cerrarModal(modalEditar);
  document.getElementById('cerrarEliminarGrupo').onclick = () =>
    cerrarModal(modalEliminar);

  //=========================================
  // CERRAR MODAL
  //=========================================

  function cerrarModal(modal) {
    modal.style.display = 'none';
  }

  window.onclick = (e) => {
    if (e.target.classList.contains('modal')) {
      cerrarModal(e.target);
    }
  };

  //=========================================
  // EDITAR
  //=========================================

  document.querySelectorAll('.btnEditarGrupo').forEach((btn) => {
    btn.onclick = () => {
      document.getElementById('editarIdGrupo').value = btn.dataset.id;

      document.getElementById('editarGrado').value = btn.dataset.grado;

      document.getElementById('editarGrupo').value = btn.dataset.grupo;

      document.getElementById('editarDocente').value =
        btn.dataset.docente ?? '';

      modalEditar.style.display = 'flex';
    };
  });

  //=========================================
  // ELIMINAR
  //=========================================

  document.querySelectorAll('.btnEliminarGrupo').forEach((btn) => {
    btn.onclick = () => {
      document.getElementById('eliminarIdGrupo').value = btn.dataset.id;

      document.getElementById('nombreGrupoEliminar').textContent =
        btn.dataset.nombre;

      modalEliminar.style.display = 'flex';
    };
  });

  //=========================================
  // ENVIAR FORMULARIOS
  //=========================================

  async function guardar(formulario, modal) {
    const datos = new FormData(formulario);

    try {
      const respuesta = await fetch('process/guardar_asignacion.php', {
        method: 'POST',
        body: datos,
      });

      const texto = await respuesta.text();

      if (texto.toLowerCase().includes('correctamente')) {
        mostrarAlerta(texto, 'success');

        cerrarModal(modal);

        setTimeout(() => {
          location.reload();
        }, 1200);
      } else {
        mostrarAlerta(texto, 'error');
      }
    } catch (e) {
      console.error(e);

      mostrarAlerta('Ocurrió un error inesperado.', 'error');
    }
  }

  //=========================================
  // CREAR
  //=========================================

  formCrear.addEventListener('submit', (e) => {
    e.preventDefault();

    guardar(formCrear, modalCrear);
  });

  //=========================================
  // EDITAR
  //=========================================

  formEditar.addEventListener('submit', (e) => {
    e.preventDefault();

    guardar(formEditar, modalEditar);
  });

  //=========================================
  // ELIMINAR
  //=========================================

  formEliminar.addEventListener('submit', (e) => {
    e.preventDefault();

    guardar(formEliminar, modalEliminar);
  });
});

