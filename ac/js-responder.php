
<script>
// Guardar el estado inicial cargado de la base de datos
const backendProgress = <?= json_encode($qNumberToIdMap) ?>;

function toggleAccordion(headerElement) {
    const item = headerElement.parentElement;
    if (item.classList.contains('active')) {
        item.classList.remove('active');
    } else {
        document.querySelectorAll('.accordion-item').forEach(el => el.classList.remove('active'));
        item.classList.add('active');
    }
}

// Navegación con scroll inteligente y apertura automática de acordeón
function scrollToQuestion(qNum, event) {
    if(event) event.preventDefault();
    const targetElement = document.getElementById(`question-${qNum}`);
    if (targetElement) {
        // Encontrar si el elemento está dentro de un acordeón colapsado y abrirlo
        const accordionItem = targetElement.closest('.accordion-item');
        if (accordionItem && !accordionItem.classList.contains('active')) {
            const header = accordionItem.querySelector('.accordion-header');
            toggleAccordion(header);
        }
        
        // Scroll suave al contenedor de la pregunta
        setTimeout(() => {
            targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    }
}

function updateProgressGrid() {
    // 1. Cargar estados iniciales desde PHP
    Object.keys(backendProgress).forEach(qNum => {
        const box = document.getElementById(`grid-box-${qNum}`);
        if(box) {
            if(backendProgress[qNum].completed) {
                box.classList.remove('pending');
                box.classList.add('completed');
            } else {
                box.classList.remove('completed');
                box.classList.add('pending');
            }
        }
    });

    // 2. Escuchar cambios dinámicos en los radios para actualizar la UI sin guardar
    document.querySelectorAll('.q-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const qNum = this.getAttribute('data-qnum');
            const box = document.getElementById(`grid-box-${qNum}`);
            if (box && this.checked) {
                box.classList.remove('pending');
                box.classList.add('completed');
            }
        });
    });
}

function calculateLiveRisk() {
    const selects = document.querySelectorAll('.q28-select');
    let score = 0;
    
    const pointsMap = {
        'No Aplica': 0,
        'Bajo': 1,
        'Bajo-Moderado': 2,
        'Moderado': 3,
        'Moderado-Alto': 4,
        'Alto': 5
    };

    selects.forEach(select => {
        score += pointsMap[select.value] || 0;
    });

    let level = 'Bajo';
    let cssClass = 'risk-bajo';
    let iconClass = 'ri-checkbox-circle-line';

    if (score <= 25) {
        level = 'Bajo'; cssClass = 'risk-bajo'; iconClass = 'ri-checkbox-circle-line';
    } else if (score <= 55) {
        level = 'Moderado'; cssClass = 'risk-moderado'; iconClass = 'ri-alert-line';
    } else if (score <= 85) {
        level = 'Moderado-Alto'; cssClass = 'risk-moderado-alto'; iconClass = 'ri-error-warning-line';
    } else {
        level = 'Alto'; cssClass = 'risk-alto'; iconClass = 'ri-close-circle-line';
    }

    const badge = document.getElementById('live-risk-badge');
    if (badge) {
        badge.className = `badge-risk ${cssClass}`;
        badge.innerHTML = `<i class="${iconClass}"></i> ${score} Pts (${level})`;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    calculateLiveRisk();
    updateProgressGrid();
});
document.addEventListener("DOMContentLoaded", () => {
    const totalActividades = 30;
    const barraFill = document.getElementById("progress-bar-fill");
    const porcentajeTexto = document.getElementById("progress-percentage-text");
    const rejillaActividades = document.querySelector(".activities-grid");

    if (!barraFill || !porcentajeTexto || !rejillaActividades) {
        console.warn("No se encontraron los elementos de la barra de progreso o la rejilla de actividades.");
        return;
    }

    /**
     * Calcula cuántas actividades están completadas y actualiza la barra.
     * Evaluamos que NO tengan la clase 'pending'. Si manejas otra clase como 'completed'
     * o 'success', el script se adapta perfectamente.
     */
    function actualizarProgreso() {
        // Contamos las cajas que no están pendientes
        const cajasCompletadas = rejillaActividades.querySelectorAll(".activity-box:not(.pending)").length;
        
        // Calculamos el porcentaje real
        const porcentaje = Math.round((cajasCompletadas / totalActividades) * 100);

        // Limitamos entre 0 y 100 por seguridad visual
        const porcentajeFinal = Math.min(Math.max(porcentaje, 0), 100);

        // Aplicamos el cambio visual con la transición suave que ya tiene tu CSS inline
        barraFill.style.width = `${porcentajeFinal}%`;
        porcentajeTexto.textContent = `${porcentajeFinal}%`;
    }

    // 1. Ejecutar al cargar la página (para capturar lo que PHP ya marcó como completado desde la BD)
    actualizarProgreso();

    // 2. Crear un observador (MutationObserver) para detectar en tiempo real
    // cuando cambias dinámicamente las clases (de 'pending' a completado) mediante JS
    const observador = new MutationObserver((mutationsList) => {
        for (const mutation of mutationsList) {
            if (mutation.type === "attributes" && mutation.attributeName === "class") {
                actualizarProgreso();
            }
        }
    });

    // Observar cambios de clase en cada uno de los 30 bloques
    const cajas = rejillaActividades.querySelectorAll(".activity-box");
    cajas.forEach(caja => {
        observador.observe(caja, { attributes: true });
    });
});
</script>
