<script>
function openIndicatorModal(tipo) {
    document.getElementById('modalTipoIndicador').value = tipo;
    document.getElementById('indicatorModal').style.display = 'flex';
}
function closeIndicatorModal() {
    document.getElementById('indicatorModal').style.display = 'none';
}
function openNormaModal() {
    document.getElementById('normaModal').style.display = 'flex';
}
function closeNormaModal() {
    document.getElementById('normaModal').style.display = 'none';
}
window.onclick = function(event) {
    let modalNorma = document.getElementById('normaModal');
    let modalInd = document.getElementById('indicatorModal');
    if (event.target === modalNorma) modalNorma.style.display = 'none';
    if (event.target === modalInd) modalInd.style.display = 'none';
}

// Función estándar para controlar los acordeones de la vista
function toggleAccordion(header) {
    const content = header.nextElementSibling;
    const icon = header.querySelector('.ri-arrow-down-s-line, i[class*="ri-arrow"]');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        if (icon) {
            icon.style.transform = 'rotate(180deg)';
            icon.style.transition = 'transform 0.2s ease';
        }
    } else {
        content.style.display = 'none';
        if (icon) {
            icon.style.transform = 'rotate(0deg)';
        }
    }
}

function openIndicatorModal(tipo) {
    document.getElementById('modalTipoIndicador').value = tipo;
    document.getElementById('indicatorModal').style.display = 'flex';
}

function closeIndicatorModal() {
    document.getElementById('indicatorModal').style.display = 'none';
}

function openNormaModal() {
    document.getElementById('normaModal').style.display = 'flex';
}

function closeNormaModal() {
    document.getElementById('normaModal').style.display = 'none';
}

window.onclick = function(event) {
    let modalNorma = document.getElementById('normaModal');
    let modalInd = document.getElementById('indicatorModal');
    if (event.target === modalNorma) modalNorma.style.display = 'none';
    if (event.target === modalInd) modalInd.style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function () {
    const selectEstado = document.getElementById('estado_prueba_selector'); // Ajusta el ID según tu HTML
    
    if (selectEstado) {
        selectEstado.addEventListener('change', function(e) {
            if (this.value === 'completado') {
                // Verificar si existen actividades pendientes en el DOM (ej. checkboxes o estados de actividades)
                const actividadesPendientes = document.querySelectorAll('.actividad-item:not(.completada), input.check-actividad:not(:checked)');
                
                if (actividadesPendientes.length > 0) {
                    e.preventDefault();
                    // Revertir temporalmente el selector al estado anterior
                    this.value = this.dataset.estadoAnterior || 'en_proceso';
                    
                    // Mostrar Modal de Advertencia
                    mostrarModalAlertaActividades();
                }
            } else {
                // Guardar el estado válido actual por si se rechaza el cambio
                this.dataset.estadoAnterior = this.value;
            }
        });
    }
});

function mostrarModalAlertaActividades() {
    // Si ya existe un modal previo, lo removemos
    let modalExistente = document.getElementById('modal-alerta-actividades');
    if (modalExistente) modalExistente.remove();

    const modalHtml = `
        <div id="modal-alerta-actividades" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
            <div style="background: #ffffff; padding: 2rem; border-radius: 10px; max-width: 400px; width: 90%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2.5rem; color: #f59e0b; margin-bottom: 1rem;"><i class="ri-alert-line"></i></div>
                <h3 style="margin: 0 0 0.5rem 0; color: #1e293b; font-size: 1.25rem;">Acción No Permitida</h3>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">
                    No es posible cambiar el estado a <strong>Completado</strong>. Debes finalizar y marcar todas las actividades correspondientes de la prueba antes de continuar.
                </p>
                <button type="button" onclick="document.getElementById('modal-alerta-actividades').remove()" style="background: #2563eb; color: white; border: none; padding: 0.65rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    Entendido
                </button>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}
document.addEventListener('DOMContentLoaded', function () {
    // Seleccionar todos los textareas de las actividades
    const textareas = document.querySelectorAll('.activity-textarea');

    textareas.forEach(textarea => {
        // Encontrar el contenedor o la fila padre de esta actividad específica
        const row = textarea.closest('.activity-row') || textarea.parentElement;
        const checkboxContainer = row.querySelector('.activity-checkbox-container');
        const checkbox = checkboxContainer ? checkboxContainer.querySelector('input[type="checkbox"]') : null;

        function toggleCheckboxVisibility() {
            if (!checkboxContainer) return;

            if (textarea.value.trim() === '') {
                // Si está vacío: ocultar el checkbox y desmarcarlo
                checkboxContainer.style.display = 'none';
                if (checkbox) checkbox.checked = false;
            } else {
                // Si tiene texto: mostrar el checkbox
                checkboxContainer.style.display = 'inline-block'; // o 'flex' según tu diseño
            }
        }

        // Ejecutar al cargar la página (por si ya tienen datos guardados)
        toggleCheckboxVisibility();

        // Escuchar cada vez que el usuario escriba o borre en el textarea
        textarea.addEventListener('input', toggleCheckboxVisibility);
    });
});
</script>



<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>