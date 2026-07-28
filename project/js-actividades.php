<script>
// ==========================================
// 1. GESTIÓN DE MODALES (Indicadores, Normas y Partidas Analíticas / Prueba 11)
// ==========================================
function openIndicatorModal(tipo) {
    const input = document.getElementById('modalTipoIndicador');
    const modal = document.getElementById('indicatorModal');
    if (input) input.value = tipo;
    if (modal) modal.style.display = 'flex';
}

function closeIndicatorModal() {
    const modal = document.getElementById('indicatorModal');
    if (modal) modal.style.display = 'none';
}

function openNormaModal() {
    const modal = document.getElementById('normaModal');
    if (modal) modal.style.display = 'flex';
}

function closeNormaModal() {
    const modal = document.getElementById('normaModal');
    if (modal) modal.style.display = 'none';
}

// Funciones específicas requeridas para los botones de Prueba 11 (Activo, Pasivo, Patrimonio)

// Cierre global de modales al hacer clic fuera de su contenido
window.onclick = function(event) {
    const modalNorma = document.getElementById('normaModal');
    const modalInd = document.getElementById('indicatorModal');
    const modalAnalitica = document.getElementById('analiticaModal');

    if (event.target === modalNorma) modalNorma.style.display = 'none';
    if (event.target === modalInd) modalInd.style.display = 'none';
    if (event.target === modalAnalitica) modalAnalitica.style.display = 'none';
};

// ==========================================
// 2. CONTROL DE ACORDEONES
// ==========================================
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

// ==========================================
// 3. VALIDACIÓN DE ESTADO Y ACTIVIDADES PENDIENTES
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
    const selectEstado = document.getElementById('estado_prueba_selector');
    
    if (selectEstado) {
        selectEstado.dataset.estadoAnterior = selectEstado.value;

        selectEstado.addEventListener('change', function(e) {
            if (this.value === 'completado') {
                const actividadesPendientes = document.querySelectorAll('.actividad-item:not(.completada), input.check-actividad:not(:checked)');
                
                if (actividadesPendientes.length > 0) {
                    e.preventDefault();
                    this.value = this.dataset.estadoAnterior || 'en_proceso';
                    mostrarModalAlertaActividades();
                }
            } else {
                this.dataset.estadoAnterior = this.value;
            }
        });
    }
});

function mostrarModalAlertaActividades() {
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

// ==========================================
// 4. GUARDAR RIESGO 23 (AJAX)
// ==========================================

const btnGuardarRiesgo23 = document.getElementById('btnGuardarRiesgo23');
if (btnGuardarRiesgo23) {
    btnGuardarRiesgo23.addEventListener('click', function(e) {
        e.preventDefault();
        
        const form = document.getElementById('formRiesgo23');
        if (!form) return;
        
        const formData = new FormData(form);

        fetch('guardar-riesgo-23.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const toast = document.createElement('div');
                toast.style.cssText = "position: fixed; bottom: 20px; right: 20px; background-color: #1e3a5f; color: #ffffff; padding: 12px 20px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); z-index: 10000; font-family: inherit; font-size: 14px; font-weight: 600;";
                toast.innerHTML = '<i class="ri-check-line" style="margin-right: 8px;"></i> ¡Riesgo guardado con éxito!';
                document.body.appendChild(toast);

                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                alert('Error al guardar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error inesperado al procesar la solicitud.');
        });
    });
}

</script>


<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>