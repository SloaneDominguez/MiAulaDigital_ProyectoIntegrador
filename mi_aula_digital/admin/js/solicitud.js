const modalAprobar = document.getElementById('modalAprobar');
const modalRechazar = document.getElementById('modalRechazar');

const idSolicitudAprobar = document.getElementById('idSolicitudAprobar');
const idSolicitudRechazar = document.getElementById('idSolicitudRechazar');

document.querySelectorAll('.btnAprobar').forEach((boton) => {
  boton.addEventListener('click', () => {
    idSolicitudAprobar.value = boton.dataset.id;
    modalAprobar.style.display = 'flex';
  });
});

document.querySelectorAll('.btnRechazar').forEach((boton) => {
  boton.addEventListener('click', () => {
    idSolicitudRechazar.value = boton.dataset.id;
    modalRechazar.style.display = 'flex';
  });
});

document.getElementById('cancelarAprobar').addEventListener('click', () => {
  modalAprobar.style.display = 'none';
});

document.getElementById('cancelarRechazar').addEventListener('click', () => {
  modalRechazar.style.display = 'none';
});

document.getElementById('confirmarAprobar').addEventListener('click', () => {
  let datos = new FormData();

  datos.append('id', idSolicitudAprobar.value);

  fetch('process/aprobar_solicitud.php', {
    method: 'POST',
    body: datos,
  })
    .then((res) => res.text())
    .then((res) => {
      if (res == 'OK') {
        location.reload();
      } else {
        alert(res);
      }
    });
});

document.getElementById('confirmarRechazar').addEventListener('click', () => {
  let datos = new FormData();

  datos.append('id', idSolicitudRechazar.value);

  fetch('process/rechazar_solicitud.php', {
    method: 'POST',
    body: datos,
  })
    .then((res) => res.text())
    .then((res) => {
      if (res == 'OK') {
        location.reload();
      } else {
        alert(res);
      }
    });
});

window.onclick = function (e) {
  if (e.target == modalAprobar) {
    modalAprobar.style.display = 'none';
  }

  if (e.target == modalRechazar) {
    modalRechazar.style.display = 'none';
  }
};
