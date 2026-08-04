// js/anuncios.js

document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-btn');
    const panels = document.querySelectorAll('.tab-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // 1. Desactivar todas las pestañas quitando la clase 'active'
            tabs.forEach(t => {
                t.classList.remove('active');
            });

            // 2. Ocultar todos los paneles de contenido de la vista
            panels.forEach(p => {
                p.style.display = 'none';
            });

            // 3. Activar la pestaña donde el usuario hizo clic
            tab.classList.add('active');
            
            // 4. Mostrar únicamente el panel correspondiente al data-target
            const targetId = tab.getAttribute('data-target');
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.style.display = 'grid';
            }
        });
    });
});
