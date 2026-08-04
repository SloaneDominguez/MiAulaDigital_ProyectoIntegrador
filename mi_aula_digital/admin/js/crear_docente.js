document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalDocente');
  const abrir = document.getElementById('abrirModalDocente');
  const cerrar = document.getElementById('cerrarModalDocente');
  const form = document.getElementById('formDocente');
  const btnGuardar = document.getElementById('btnGuardarDocente');

  const modalEliminar = document.getElementById('modalEliminarDocente');
  const cancelarEliminar = document.getElementById('cancelarEliminarDocente');
  const confirmarEliminar = document.getElementById('confirmarEliminarDocente');
  const idEliminar = document.getElementById('idEliminarDocente');

  let editando = false;

  // ==========================================
  // ABRIR MODAL CREAR
  // ==========================================

  if (abrir) {
    abrir.addEventListener('click', () => {
      form.reset();

      document.getElementById('id_docente').value = '';

      document.getElementById('contrasena').placeholder = '';

      editando = false;

      btnGuardar.textContent = 'Guardar docente';

      modal.style.display = 'flex';
    });
  }

  // ==========================================
  // CERRAR MODAL DOCENTE
  // ==========================================

  function cerrarModal() {
    modal.style.display = 'none';

    form.reset();

    document.getElementById('id_docente').value = '';

    document.getElementById('contrasena').placeholder = '';

    editando = false;
  }

  if (cerrar) {
    cerrar.addEventListener('click', cerrarModal);
  }

  // ==========================================
  // CERRAR MODALES CLICK AFUERA
  // ==========================================

  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      cerrarModal();
    }

    if (e.target === modalEliminar) {
      modalEliminar.style.display = 'none';
    }
  });

  // ==========================================
  // GUARDAR / EDITAR
  // ==========================================

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    let datos = new FormData(form);

    datos.append('accion', editando ? 'editar' : 'crear');

    // VALIDACIONES

    if (datos.get('nombre').trim() === '') {
      alert('Ingrese el nombre');

      return;
    }

    if (datos.get('primer_apellido').trim() === '') {
      alert('Ingrese el primer apellido');

      return;
    }

    if (datos.get('usuario').trim() === '') {
      alert('Ingrese el usuario');

      return;
    }

    if (datos.get('correo').trim() === '') {
      alert('Ingrese el correo');

      return;
    }

    if (!editando && datos.get('contrasena').length < 6) {
      alert('La contraseña debe tener mínimo 6 caracteres');

      return;
    }

    if (datos.get('id_grupo') === '') {
      alert('Seleccione un grupo');

      return;
    }

    btnGuardar.disabled = true;

    btnGuardar.textContent = 'Guardando...';

    try {
      let respuesta = await fetch('process/crear_docente.php', {
        method: 'POST',
        body: datos,
      });

      let resultado = await respuesta.text();

      alert(resultado);

      if (resultado.includes('correctamente')) {
        cerrarModal();

        location.reload();
      }
    } catch (error) {
      console.error(error);

      alert('Error al procesar la información');
    }

    btnGuardar.disabled = false;

    btnGuardar.textContent = editando
      ? 'Actualizar docente'
      : 'Guardar docente';
  });

  // ==========================================
  // EDITAR DOCENTE
  // ==========================================

  document.addEventListener('click', async (e) => {
    const botonEditar = e.target.closest('.editarDocente');

    if (!botonEditar) {
      return;
    }

    let id = botonEditar.dataset.id;

    let datos = new FormData();

    datos.append('accion', 'obtener');

    datos.append('id_docente', id);

    try {
      let respuesta = await fetch('process/crear_docente.php', {
        method: 'POST',
        body: datos,
      });

      let docente = await respuesta.json();

      document.getElementById('id_docente').value = docente.ID_Docente;

      document.getElementById('nombre').value = docente.Nombre;

      document.getElementById('primer_apellido').value =
        docente.Primer_Apellido;

      document.getElementById('segundo_apellido').value =
        docente.Segundo_Apellido || '';

      document.getElementById('usuario').value = docente.Usuario;

      document.getElementById('correo').value = docente.Correo;

      document.getElementById('id_grupo').value = docente.ID_Grupo_Asignado;

      const password = document.getElementById('contrasena');

      password.value = '';

      password.placeholder = 'Dejar vacío para conservar contraseña';

      editando = true;

      btnGuardar.textContent = 'Actualizar docente';

      modal.style.display = 'flex';
    } catch (error) {
      console.error(error);

      alert('Error al cargar docente');
    }
  });

  // ==========================================
  // ABRIR MODAL ELIMINAR
  // ==========================================

  document.addEventListener('click', (e) => {
    const botonEliminar = e.target.closest('.eliminarDocente');

    if (!botonEliminar) {
      return;
    }

    idEliminar.value = botonEliminar.dataset.id;

    modalEliminar.style.display = 'flex';
  });

  // ==========================================
  // CANCELAR ELIMINAR
  // ==========================================

  if (cancelarEliminar) {
    cancelarEliminar.addEventListener('click', () => {
      modalEliminar.style.display = 'none';
    });
  }

  // ==========================================
  // CONFIRMAR ELIMINAR
  // ==========================================

  if (confirmarEliminar) {
    confirmarEliminar.addEventListener('click', async () => {
      let datos = new FormData();

      datos.append('accion', 'eliminar');

      datos.append('id_docente', idEliminar.value);

      try {
        let respuesta = await fetch('process/crear_docente.php', {
          method: 'POST',
          body: datos,
        });

        let resultado = await respuesta.text();

        alert(resultado);

        if (resultado.includes('correctamente')) {
          location.reload();
        }
      } catch (error) {
        console.error(error);

        alert('Error al eliminar docente');
      }
    });
  }
});

// ==========================================
// SOLO LETRAS
// ==========================================

function soloLetras(input) {
  input.value = input.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]/g, '');
}

// ==========================================
// VALIDAR USUARIO
// ==========================================

function validarUsuario(input) {
  input.value = input.value.replace(/[^A-Za-z0-9_]/g, '');
}

// ==========================================
// VALIDAR CONTRASEÑA
// ==========================================

function validarPassword(input) {
  let regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}$/;

  if (input.value === '') {
    input.setCustomValidity('');

    return;
  }

  if (!regex.test(input.value)) {
    input.setCustomValidity(
      'La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número'
    );
  } else {
    input.setCustomValidity('');
  }
}
