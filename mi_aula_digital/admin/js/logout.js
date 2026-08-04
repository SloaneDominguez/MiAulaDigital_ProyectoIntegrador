document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.getElementById('openLogoutModal');
  const closeBtn = document.getElementById('closeLogoutModal');
  const modal = document.getElementById('logoutModal');

  if (openBtn) {
    openBtn.addEventListener('click', () => {
      modal.classList.add('active');
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      modal.classList.remove('active');
    });
  }

  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('active');
    }
  });
});
