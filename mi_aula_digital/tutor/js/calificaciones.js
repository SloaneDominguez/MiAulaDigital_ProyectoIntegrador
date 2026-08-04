// js/calificaciones.js

function toggleDropdown() {
    const dropdown = document.getElementById('downloadDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
}

// Cerrar el menú de forma segura si se hace clic en cualquier otra parte de la pantalla
window.addEventListener('click', function(event) {
    const dropdown = document.getElementById('downloadDropdown');
    const btn = document.getElementById('btnDownloadToggle');
    
    // Si el clic no fue en el botón ni en sus elementos internos (como el ícono)[cite: 12]
    if (dropdown && btn && !btn.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});