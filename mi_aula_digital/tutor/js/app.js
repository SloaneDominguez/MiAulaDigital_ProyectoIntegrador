document.addEventListener('DOMContentLoaded', function () {
    
    // --- CONTROL DEL MODAL DE CERRAR SESIÓN ---
    const openModalBtn = document.getElementById('openLogoutModal');
    const closeModalBtn = document.getElementById('closeLogoutModal');
    const logoutOverlay = document.getElementById('logoutOverlay');

    if (openModalBtn && logoutOverlay) {
        openModalBtn.addEventListener('click', function (e) {
            e.preventDefault();
            logoutOverlay.style.display = 'flex';
        });
    }

    if (closeModalBtn && logoutOverlay) {
        closeModalBtn.addEventListener('click', function () {
            logoutOverlay.style.display = 'none';
        });
    }

    if (logoutOverlay) {
        logoutOverlay.addEventListener('click', function (e) {
            if (e.target === logoutOverlay) {
                logoutOverlay.style.display = 'none';
            }
        });
    }

    // --- REDIRECCIÓN AUTOMÁTICA EN TARJETAS MÉTRICAS ---
    const metricCards = document.querySelectorAll('.metric-row-card');
    metricCards.forEach(card => {
        card.addEventListener('click', function () {
            const targetView = this.getAttribute('data-view');
            if (targetView) {
                window.location.href = `dashboard.php?view=${targetView}`;
            }
        });
    });
});