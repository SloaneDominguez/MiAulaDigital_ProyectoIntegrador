document.addEventListener('DOMContentLoaded', () => {
  const btnAvisos = document.getElementById('btnTabAvisos');
  const btnTareas = document.getElementById('btnTabTareas');

  const vistaAvisos = document.getElementById('vistaAvisos');
  const vistaTareas = document.getElementById('vistaTareas');

  const btnNuevo = document.getElementById('abrirModalAviso');

  if (!btnAvisos || !btnTareas) return;

  btnAvisos.addEventListener('click', () => {
    vistaAvisos.style.display = 'block';
    vistaTareas.style.display = 'none';

    btnNuevo.style.display = 'inline-flex';

    btnAvisos.classList.add('active');
    btnTareas.classList.remove('active');
  });

  btnTareas.addEventListener('click', () => {
    vistaAvisos.style.display = 'none';
    vistaTareas.style.display = 'block';

    btnNuevo.style.display = 'none';

    btnTareas.classList.add('active');
    btnAvisos.classList.remove('active');
  });
});
