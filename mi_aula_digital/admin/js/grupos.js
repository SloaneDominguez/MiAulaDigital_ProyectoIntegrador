/* grupos.js */
document.addEventListener("DOMContentLoaded", function() {
    const groupSelector = document.querySelector('.group-selector');
    const tableRows = document.querySelectorAll('.custom-table tbody tr[data-grupo]');

    if (groupSelector) {
        groupSelector.addEventListener('change', function() {
            const grupoSeleccionado = this.value;

            tableRows.forEach(row => {
                const grupoFila = row.getAttribute('data-grupo');
                
                // Si se selecciona "todos" (por si lo añades en el futuro) o coincide el grupo
                if (grupoSeleccionado === "todos" || grupoFila === grupoSeleccionado) {
                    row.classList.remove('hidden-row');
                } else {
                    row.classList.add('hidden-row');
                }
            });
        });
        
        // Disparar el evento inicialmente para aplicar el filtro del primer valor por defecto
        groupSelector.dispatchEvent(new Event('change'));
    }
});