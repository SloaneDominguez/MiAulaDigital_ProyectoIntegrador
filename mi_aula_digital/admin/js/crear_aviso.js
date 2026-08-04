document.addEventListener('DOMContentLoaded', () => {
  //=========================================
  // ELEMENTOS
  //=========================================

  const modal = document.getElementById('modalAviso');
  const abrir = document.getElementById('abrirModalAviso');
  const cerrar = document.getElementById('cerrarModalAviso');
  const form = document.getElementById('formAviso');

  const btnGuardar = document.getElementById('btnGuardarAviso');
  const tituloModal = document.getElementById('tituloModalAviso');
  const idPublicacion = document.getElementById('id_publicacion');

  const inputTitulo = document.querySelector('[name="titulo"]');
  const inputContenido = document.querySelector('[name="contenido"]');
  const selectTipo = document.querySelector('[name="tipo_aviso"]');
  const selectGrupo = document.querySelector('[name="id_grupo"]');

  if (!modal || !abrir || !cerrar || !form) return;

  let accion = 'crear';

  //=========================================
  // ABRIR MODAL NUEVO
  //=========================================

  abrir.addEventListener('click', () => {
    form.reset();

    idPublicacion.value = '';

    accion = 'crear';

    tituloModal.textContent = 'Nuevo aviso institucional';

    btnGuardar.textContent = 'Publicar aviso';

    modal.style.display = 'flex';
  });

  //=========================================
  // CERRAR MODAL
  //=========================================

  function cerrarModal() {
    modal.style.display = 'none';

    form.reset();

    idPublicacion.value = '';

    accion = 'crear';

    tituloModal.textContent = 'Nuevo aviso institucional';

    btnGuardar.textContent = 'Publicar aviso';
  }

  cerrar.addEventListener('click', cerrarModal);

  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      cerrarModal();
    }
  });

  //=========================================
  // EDITAR AVISO
  //=========================================

  document.querySelectorAll('.editarAviso').forEach((btn) => {
    btn.addEventListener('click', () => {
      accion = 'editar';

      idPublicacion.value = btn.dataset.id;

      inputTitulo.value = btn.dataset.titulo;

      inputContenido.value = btn.dataset.contenido;

      selectTipo.value = btn.dataset.tipo;

      // Seleccionar grupo correcto

      if (btn.dataset.grupo) {
        selectGrupo.value = btn.dataset.grupo;
      } else {
        selectGrupo.value = '';
      }

      tituloModal.textContent = 'Editar aviso institucional';

      btnGuardar.textContent = 'Guardar cambios';

      modal.style.display = 'flex';
    });
  });

  //=========================================
  // GUARDAR / EDITAR
  //=========================================

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const datos = new FormData(form);

    datos.append('accion', accion);

    if (inputTitulo.value.trim() === '') {
      alert('Ingrese un título.');

      return;
    }

    if (inputContenido.value.trim() === '') {
      alert('Ingrese el contenido.');

      return;
    }

    btnGuardar.disabled = true;

    btnGuardar.textContent =
      accion === 'editar' ? 'Guardando...' : 'Publicando...';

    try {
      const respuesta = await fetch('process/crear_aviso.php', {
        method: 'POST',
        body: datos,
      });

      const json = await respuesta.json();

      if (json.status === 'ok') {
        alert(json.msg);

        cerrarModal();

        location.reload();
      } else {
        alert(json.msg);
      }
    } catch (error) {
      console.error(error);

      alert('Error al guardar el aviso.');
    }

    btnGuardar.disabled = false;
  });

  //=========================================
  // MODAL ELIMINAR AVISO
  //=========================================

  const modalEliminar = document.getElementById('modalEliminarAviso');
  const confirmarEliminar = document.getElementById('confirmarEliminarAviso');
  const cancelarEliminar = document.getElementById('cancelarEliminarAviso');

  let idEliminar = null;

  document.querySelectorAll('.eliminarAviso').forEach((btn) => {
    btn.addEventListener('click', () => {
      idEliminar = btn.dataset.id;

      modalEliminar.style.display = 'flex';
    });
  });

  // Cancelar eliminación

  cancelarEliminar.addEventListener('click', () => {
    modalEliminar.style.display = 'none';

    idEliminar = null;
  });

  // Confirmar eliminación

  confirmarEliminar.addEventListener('click', () => {
    if (!idEliminar) return;

    const datos = new FormData();

    datos.append('accion', 'eliminar');

    datos.append('id_publicacion', idEliminar);

    fetch('process/crear_aviso.php', {
      method: 'POST',
      body: datos,
    })
      .then((res) => res.json())

      .then((json) => {
        if (json.status === 'ok') {
          alert(json.msg);

          location.reload();
        } else {
          alert(json.msg);
        }
      })

      .catch((error) => {
        console.error(error);

        alert('Error al eliminar el aviso.');
      });
  });
});
