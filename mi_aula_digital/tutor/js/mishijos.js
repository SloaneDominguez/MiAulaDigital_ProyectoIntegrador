document.addEventListener('DOMContentLoaded', () => {
  const addOverlay = document.getElementById('addHijoOverlay');
  const detailOverlay = document.getElementById('detailHijoOverlay');
  const openAdd = document.getElementById('btnOpenAddModal');
  const closeAdd = document.getElementById('btnCloseAddModal');
  const closeDetail = document.getElementById('btnCloseDetailModal');
  const curpInput = document.getElementById('curpAlumno');

  const openModal = overlay => {
    if (!overlay) return;
    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };
  const closeModal = overlay => {
    if (!overlay) return;
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  openAdd?.addEventListener('click', () => {
    openModal(addOverlay);
    setTimeout(() => curpInput?.focus(), 50);
  });
  closeAdd?.addEventListener('click', () => closeModal(addOverlay));
  closeDetail?.addEventListener('click', () => closeModal(detailOverlay));

  [addOverlay, detailOverlay].forEach(overlay => {
    overlay?.addEventListener('click', event => {
      if (event.target === overlay) closeModal(overlay);
    });
  });

  curpInput?.addEventListener('input', () => {
    curpInput.value = curpInput.value.toUpperCase().replace(/\s+/g, '').slice(0, 18);
  });

  document.querySelectorAll('.btn-ver-hijo').forEach(button => {
    button.addEventListener('click', () => {
      document.getElementById('detailHijoNombre').textContent = button.dataset.nombre || 'Información del alumno';
      document.getElementById('detailHijoCurp').textContent = button.dataset.curp || '—';
      document.getElementById('detailHijoGrupo').textContent = button.dataset.grupo || '—';
      document.getElementById('detailHijoRegistro').textContent = button.dataset.registro || '—';
      document.getElementById('detailHijoCalificaciones').textContent = button.dataset.calificaciones || '0';
      document.getElementById('detailHijoIncidencias').textContent = button.dataset.incidencias || '0';
      openModal(detailOverlay);
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    closeModal(addOverlay);
    closeModal(detailOverlay);
  });
});
