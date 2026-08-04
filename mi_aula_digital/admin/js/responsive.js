document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('mobileMenuToggle');
  const sidebar = document.querySelector('.sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const icon = toggle?.querySelector('i');

  const closeMenu = () => {
    sidebar?.classList.remove('is-open');
    backdrop?.classList.remove('is-visible');
    document.body.classList.remove('menu-open');
    toggle?.setAttribute('aria-expanded', 'false');
    toggle?.setAttribute('aria-label', 'Abrir menú');
    icon?.classList.remove('fa-xmark');
    icon?.classList.add('fa-bars');
  };

  const openMenu = () => {
    sidebar?.classList.add('is-open');
    backdrop?.classList.add('is-visible');
    document.body.classList.add('menu-open');
    toggle?.setAttribute('aria-expanded', 'true');
    toggle?.setAttribute('aria-label', 'Cerrar menú');
    icon?.classList.remove('fa-bars');
    icon?.classList.add('fa-xmark');
  };

  toggle?.addEventListener('click', () => {
    sidebar?.classList.contains('is-open') ? closeMenu() : openMenu();
  });
  backdrop?.addEventListener('click', closeMenu);
  sidebar?.querySelectorAll('a.menu-item').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) closeMenu();
    });
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeMenu();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeMenu();
  });

  // Convierte automáticamente las tablas normales en tarjetas legibles en celular.
  document.querySelectorAll('table').forEach(table => {
    if (table.classList.contains('no-responsive-cards')) return;
    const headers = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
    if (!headers.length) return;
    table.classList.add('responsive-card-table');
    table.querySelectorAll('tbody tr').forEach(row => {
      [...row.children].forEach((cell, index) => {
        if (cell.tagName === 'TD' && !cell.hasAttribute('colspan')) {
          cell.dataset.label = headers[index] || `Campo ${index + 1}`;
        }
      });
    });
  });
});
