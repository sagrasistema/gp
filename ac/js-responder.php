
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

// Evaluación basada en la escala simétrica de 105 puntos (21 preguntas)
    if (score <= 21) {
        level = 'Bajo';
        cssClass = 'risk-bajo';
        iconClass = 'ri-checkbox-circle-line'; // Círculo de check verde (Seguro)
    } else if (score <= 42) {
        level = 'Bajo Moderado';
        cssClass = 'risk-bajo-mod';
        iconClass = 'ri-information-line';     // Icono de información azul/celeste (Atención)
    } else if (score <= 63) {
        level = 'Moderado';
        cssClass = 'risk-mod';
        iconClass = 'ri-alert-line';           // Triángulo de advertencia amarillo (Prevención)
    } else if (score <= 84) {
        level = 'Moderado Alto';
        cssClass = 'risk-mod-alto';
        iconClass = 'ri-error-warning-line';   // Advertencia naranja con signo de exclamación
    } else {
        level = 'Alto';
        cssClass = 'risk-alto';
        iconClass = 'ri-close-circle-line';    // Círculo de error rojo (Peligro crítico)
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

// Monitorizar en tiempo real las respuestas de la pregunta 28
function checkQ28RealTimeProgress() {
    const selects = document.querySelectorAll('.q28-select');
    let answeredCount = 0;

    selects.forEach(select => {
        const val = select.value;
        const selectedOption = select.options[select.selectedIndex];
        
        // Evaluamos si la opción seleccionada es válida (mayor a 0 / no vacía)
        let isValidAnswer = false;

        if (val && val !== '' && val !== '0' && val !== 'No Aplica') {
            // Si tus opciones tienen un atributo 'data-score', validamos que sea mayor a 0
            if (selectedOption && selectedOption.hasAttribute('data-score')) {
                const score = parseInt(selectedOption.getAttribute('data-score'), 10);
                if (score > 0) {
                    isValidAnswer = true;
                }
            } else {
                // Si no usan 'data-score', el simple hecho de no ser vacío, '0' ni 'No Aplica' la hace válida
                isValidAnswer = true;
            }
        }

        if (isValidAnswer) {
            answeredCount++;
        }
    });

    const box28 = document.getElementById('grid-box-28');
    if (box28) {
        // Hacemos la validación dinámica comparando con el total de selects existentes (normalmente 21)
        if (answeredCount >= selects.length && selects.length > 0) {
            box28.classList.remove('pending');
            box28.classList.add('completed');
        } else {
            box28.classList.remove('completed');
            box28.classList.add('pending');
        }
    }

    // Opcional: Actualizar la barra de progreso general si existe la función
    if (typeof updateProgressBar === 'function') {
        updateProgressBar();
    }
}

// Escuchar cambios en cualquiera de los selectores de la Q28
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('.q28-select');
    selects.forEach(select => {
        select.addEventListener('change', checkQ28RealTimeProgress);
    });
    
    // Ejecutar una vez al cargar la página para sincronizar el estado inicial
    checkQ28RealTimeProgress();
});
</script>
