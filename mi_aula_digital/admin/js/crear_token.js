function abrirModal() {
  document.getElementById('modalToken').style.display = 'block';
  cargarAlumnos();
}

function cerrarModal() {
  document.getElementById('modalToken').style.display = 'none';
}

function generarToken() {
  let token = Math.random().toString(36).substring(2, 12).toUpperCase();
  document.getElementById('tokenGenerado').value = token;
}

function cargarAlumnos() {
  fetch('process/listar_alumnos.php')
    .then((res) => res.json())
    .then((data) => {
      let select = document.getElementById('alumno');
      select.innerHTML = '';
      data.forEach((a) => {
        select.innerHTML += `
                    <option value="${a.ID_Alumno}">
                        ${a.Nombre} ${a.Primer_Apellido} (${a.CURP})
                    </option>`;
      });
    });
}

function guardarToken() {
  let id = document.getElementById('alumno').value;
  let token = document.getElementById('tokenGenerado').value;

  fetch('process/crear_token.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, token }),
  })
    .then((res) => res.json())
    .then((res) => {
      alert(res.msg);
      cerrarModal();
      location.reload();
    });
}

function exportarCSV() {
  document.getElementById('btnCSV').addEventListener('click', () => {
    const grupo = document.querySelector('select[name="grupo"]').value;

    if (grupo === '') {
      alert('Seleccione un grupo.');
      return;
    }

    window.location.href = 'process/exportar_tokens_csv.php?grupo=' + grupo;
  });
}

function exportarPDF() {
  window.open('process/exportar_tokens_pdf.php');
}
