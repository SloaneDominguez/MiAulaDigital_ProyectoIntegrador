document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalToken');

  // =========================
  // MODALES EDITAR / ELIMINAR
  // =========================

  const modalEditar = document.getElementById('modalEditarToken');
  const modalEliminar = document.getElementById('modalEliminarToken');

  const formEditar = document.getElementById('formEditarToken');

  const btnCerrarEditar = document.getElementById('cerrarEditarToken');

  const btnCancelarEliminar = document.getElementById('cancelarEliminarToken');

  const btnConfirmarEliminar = document.getElementById(
    'confirmarEliminarToken'
  );

  window.abrirModal = function () {
    modal.style.display = 'flex';
    cargarGrupos();
  };

  window.cerrarModal = function () {
    modal.style.display = 'none';
  };

  window.generarToken = function () {
    document.getElementById('tokenBase').value = Math.random()
      .toString(36)
      .substring(2, 10)
      .toUpperCase();
  };

  window.cargarGrupos = function () {
    fetch('process/listar_grupos.php')
      .then((r) => r.json())

      .then((data) => {
        const select = document.getElementById('grupoSeleccionado');

        select.innerHTML = '<option value="">Selecciona grupo</option>';

        data.forEach((g) => {
          select.innerHTML += `
            <option value="${g.ID_Grupo}">
              ${g.Grado}° ${g.Grupo}
            </option>
          `;
        });
      });
  };

  window.guardarToken = function () {
    let grupo = document.getElementById('grupoSeleccionado').value;

    let expiracion =
      document.getElementById('expiracionToken')?.value || '1 year';

    if (!grupo) {
      alert('Selecciona un grupo');

      return;
    }

    fetch('process/crear_token.php', {
      method: 'POST',

      headers: {
        'Content-Type': 'application/json',
      },

      body: JSON.stringify({
        grupo,
        expiracion,
      }),
    })
      .then((r) => r.json())

      .then((res) => {
        alert(res.msg);

        cerrarModal();

        location.reload();
      });
  };

  // ==========================================
  // EDITAR TOKEN
  // ==========================================

  document.querySelectorAll('.editarToken').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;

      fetch(
        'process/editar_token.php?accion=obtener&id=' +
          id
      )
        .then((r) => r.json())

        .then((token) => {
          document.getElementById('editarIdToken').value = token.ID_Token;

          document.getElementById('editarAlumno').value = token.Alumno;

          document.getElementById('editarToken').value = token.Token_String;

          modalEditar.style.display = 'flex';
        });
    });
  });

  // cerrar

  btnCerrarEditar.addEventListener('click', () => {
    modalEditar.style.display = 'none';
  });

  // generar nuevo token

  document
    .getElementById('btnGenerarNuevoToken')
    .addEventListener('click', () => {
      const id = document.getElementById('editarIdToken').value;

      const expiracion =
        document.getElementById('editarExpiracion').value || '1 year';

      fetch('process/editar_token.php', {
        method: 'POST',

        headers: {
          'Content-Type': 'application/json',
        },

        body: JSON.stringify({
          accion: 'editar',

          id: id,

          expiracion: expiracion,
        }),
      })
        .then((r) => r.json())

        .then((res) => {
          if (res.success) {
            document.getElementById('editarToken').value = res.token;

            alert(res.msg);
          } else {
            alert(res.msg);
          }
        });
    });

  // guardar edición

  formEditar.addEventListener('submit', (e) => {
    e.preventDefault();

    fetch('process/editar_token.php', {
      method: 'POST',

      headers: {
        'Content-Type': 'application/json',
      },

      body: JSON.stringify({
        accion: 'editar',

        id: document.getElementById('editarIdToken').value,

        token: document.getElementById('editarToken').value,

        expiracion: document.getElementById('editarExpiracion').value,
      }),
    })
      .then((r) => r.json())

      .then((res) => {
        alert(res.msg);

        modalEditar.style.display = 'none';

        location.reload();
      });
  });

  // ==========================================
  // ELIMINAR TOKEN
  // ==========================================

  document.querySelectorAll('.eliminarToken').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.getElementById('eliminarIdToken').value = btn.dataset.id;

      modalEliminar.style.display = 'flex';
    });
  });

  btnCancelarEliminar.addEventListener('click', () => {
    modalEliminar.style.display = 'none';
  });

  btnConfirmarEliminar.addEventListener('click', () => {
    fetch('process/editar_token.php', {
      method: 'POST',

      headers: {
        'Content-Type': 'application/json',
      },

      body: JSON.stringify({
        accion: 'eliminar',
        id: document.getElementById('eliminarIdToken').value,
      }),
    })
      .then((r) => r.json())

      .then((res) => {
        alert(res.msg);

        modalEliminar.style.display = 'none';

        location.reload();
      });
  });

  // =========================
  // EXPORTAR CSV
  // =========================

  const btnCSV = document.getElementById('btnCSV');

  if (btnCSV) {
    btnCSV.addEventListener('click', () => {
      const grupo = document.querySelector('select[name="grupo"]').value;

      if (grupo === '') {
        alert('Seleccione un grupo para exportar.');

        return;
      }

      window.location.href =
        'process/exportar_tokens_csv.php?grupo=' + grupo;
    });
  }

  // =========================
  // EXPORTAR PDF
  // =========================

  const btnPDF = document.getElementById('btnPDF');

  if (btnPDF) {
    btnPDF.addEventListener('click', () => {
      const grupo = document.querySelector('select[name="grupo"]').value;

      if (grupo === '') {
        alert('Seleccione un grupo para exportar.');

        return;
      }

      window.open(
        'process/exportar_tokens_pdf.php?grupo=' + grupo,
        '_blank'
      );
    });
  }
});

document.getElementById('btnExcel')?.addEventListener('click', () => {
  const grupo = document.querySelector('select[name="grupo"]').value;

  if (!grupo) {
    alert('Selecciona un grupo para generar el Excel');
    return;
  }

  window.open(
    'process/exportar_excel.php?grupo=' + grupo,
    '_blank'
  );
});
