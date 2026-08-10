<?php
// diagnostico.php - Interfaz de Diagnóstico para Empresa Familiar
// Compatibilidad: PHP 7.4+ / PHP 8.x

$preguntas = [
    'p1' => [
        'categoria' => 'GOBERNANZA',
        'titulo'    => 'Órganos de Gobierno y Dirección',
        'desc'      => '¿La empresa cuenta con un Consejo de Familia o Junta Directiva formal que se reúna periódicamente para la toma de decisiones estratégicas?',
        'icon'      => 'ri-government-line',
        'color'     => '#6366f1'
    ],
    'p2' => [
        'categoria' => 'PROTOCOLO',
        'titulo'    => 'Protocolo y Normativa Familiar',
        'desc'      => '¿Existen reglas claras, consensuadas y escritas que regulen el ingreso, remuneración y desempeño de familiares en la empresa?',
        'icon'      => 'ri-file-paper-2-line',
        'color'     => '#ec4899'
    ],
    'p3' => [
        'categoria' => 'SUCESIÓN',
        'titulo'    => 'Plan de Sucesión y Continuidad',
        'desc'      => '¿Se ha definido un plan estructurado y capacitado para la sucesión del liderazgo ejecutivo y la transferencia de la propiedad?',
        'icon'      => 'ri-git-merge-line',
        'color'     => '#8b5cf6'
    ],
    'p4' => [
        'categoria' => 'FINANZAS',
        'titulo'    => 'Separación Patrimonio - Empresa',
        'desc'      => '¿Están claramente delimitadas las finanzas personales de la familia respecto a los activos, tesorería y gastos de la empresa?',
        'icon'      => 'ri-bank-card-line',
        'color'     => '#06b6d4'
    ],
    'p5' => [
        'categoria' => 'ESTRATEGIA',
        'titulo'    => 'Visión Compartida de Futuro',
        'desc'      => '¿Coinciden los miembros de la familia sobre el rumbo, metas de expansión y estrategia de negocio a largo plazo?',
        'icon'      => 'ri-compass-3-line',
        'color'     => '#10b981'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Madurez - Empresa Familiar</title>
    <!-- Tipografía Google Fonts & Remix Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #311042 100%);
            --card-glass: rgba(255, 255, 255, 0.95);
            --card-border: rgba(255, 255, 255, 0.2);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            
            /* Colores escala Likert */
            --lvl-1: #ef4444; /* Rojo */
            --lvl-2: #f97316; /* Naranja */
            --lvl-3: #eab308; /* Amarillo */
            --lvl-4: #84cc16; /* Verde Lima */
            --lvl-5: #10b981; /* Verde Esmeralda */
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            background-attachment: fixed;
            color: var(--text-dark);
            min-height: 100vh;
            padding: 2rem 1rem 4rem 1rem;
            position: relative;
        }

        /* Barra de Progreso Superior */
        .progress-bar-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1000;
        }
        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #3b82f6, #ec4899, #10b981);
            transition: width 0.4s ease;
            box-shadow: 0 0 12px rgba(236, 72, 153, 0.8);
        }

        .container {
            max-width: 850px;
            margin: 0 auto;
        }

        /* Hero Header */
        .header-hero {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            color: #ffffff;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .header-hero .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.3), rgba(236, 72, 153, 0.3));
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .header-hero h1 {
            font-size: 2.3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        .header-hero p {
            color: #94a3b8;
            font-size: 1rem;
            max-width: 650px;
            margin: 0 auto;
        }

        /* Tarjetas de Formulario */
        .glass-card {
            background: var(--card-glass);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        /* Inputs de Datos */
        .section-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 500;
            background: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            border-color: #6366f1;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        /* Preguntas Escala Likert */
        .question-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .question-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .question-category {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #64748b;
        }

        .question-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .question-desc {
            font-size: 0.92rem;
            color: #475569;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        /* Diseños de Botones de Escala (1-5) */
        .likert-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.6rem;
        }

        .likert-option { position: relative; }
        .likert-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }

        .likert-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 0.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            cursor: pointer;
            background: #f8fafc;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .likert-number {
            font-size: 1.25rem;
            font-weight: 800;
            color: #334155;
            transition: color 0.2s;
        }

        .likert-text {
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            margin-top: 0.2rem;
            text-transform: uppercase;
        }

        /* Hover & Checked Colores Personalizados */
        .likert-tile:hover {
            transform: translateY(-3px);
            border-color: #cbd5e1;
            box-shadow: 0 6px 12px rgba(0,0,0,0.05);
        }

        /* Nivel 1 */
        .opt-1 input:checked + .likert-tile {
            border-color: var(--lvl-1);
            background: rgba(239, 68, 68, 0.1);
        }
        .opt-1 input:checked + .likert-tile .likert-number,
        .opt-1 input:checked + .likert-tile .likert-text { color: var(--lvl-1); }

        /* Nivel 2 */
        .opt-2 input:checked + .likert-tile {
            border-color: var(--lvl-2);
            background: rgba(249, 115, 22, 0.1);
        }
        .opt-2 input:checked + .likert-tile .likert-number,
        .opt-2 input:checked + .likert-tile .likert-text { color: var(--lvl-2); }

        /* Nivel 3 */
        .opt-3 input:checked + .likert-tile {
            border-color: var(--lvl-3);
            background: rgba(234, 179, 8, 0.15);
        }
        .opt-3 input:checked + .likert-tile .likert-number,
        .opt-3 input:checked + .likert-tile .likert-text { color: #b45309; }

        /* Nivel 4 */
        .opt-4 input:checked + .likert-tile {
            border-color: var(--lvl-4);
            background: rgba(132, 204, 22, 0.15);
        }
        .opt-4 input:checked + .likert-tile .likert-number,
        .opt-4 input:checked + .likert-tile .likert-text { color: #4d7c0f; }

        /* Nivel 5 */
        .opt-5 input:checked + .likert-tile {
            border-color: var(--lvl-5);
            background: rgba(16, 185, 129, 0.15);
        }
        .opt-5 input:checked + .likert-tile .likert-number,
        .opt-5 input:checked + .likert-tile .likert-text { color: var(--lvl-5); }

        /* Botón de Envío Gradiente Neon */
        .btn-submit-container {
            margin-top: 2rem;
        }

        .btn-gradient {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #ec4899 50%, #10b981 100%);
            background-size: 200% 200%;
            color: #ffffff;
            border: none;
            padding: 1.25rem;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 12px 25px rgba(236, 72, 153, 0.35);
            transition: all 0.4s ease;
        }

        .btn-gradient:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 18px 35px rgba(236, 72, 153, 0.5);
        }

        .btn-gradient:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Responsividad */
        @media (max-width: 640px) {
            body { padding: 1rem 0.5rem; }
            .header-hero { padding: 2rem 1rem; }
            .header-hero h1 { font-size: 1.6rem; }
            .glass-card { padding: 1.25rem; }
            .likert-text { font-size: 0.6rem; }
            .likert-tile { padding: 0.6rem 0.1rem; }
        }
    </style>
</head>
<body>

<!-- Barra de Progreso Superior -->
<div class="progress-bar-container">
    <div class="progress-bar-fill" id="progressBar"></div>
</div>

<div class="container">

    <!-- Encabezado Hero -->
    <div class="header-hero">
        <div class="badge">
            <i class="ri-sparkling-fill"></i> Evaluación de Madurez
        </div>
        <h1>Diagnóstico de Empresa Familiar</h1>
        <p>Identifica los fortalezas y oportunidades de mejora en la gobernanza, sucesión y gestión de tu organización en pocos minutos.</p>
    </div>

    <form id="formDiagnostico">

        <!-- Seccion: Información de la Empresa -->
        <div class="glass-card">
            <div class="section-title">
                <i class="ri-building-4-fill" style="color: #6366f1;"></i> Datos de la Organización
            </div>

            <div class="form-grid" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label>Nombre de la Empresa *</label>
                    <input type="text" name="nombre_empresa" class="form-control" required placeholder="Ej: Corporación Vanguardia">
                </div>
                <div class="form-group">
                    <label>Nombre del Contacto *</label>
                    <input type="text" name="nombre_contacto" class="form-control" required placeholder="Ej: ALberto Valera">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Correo Electrónico *</label>
                    <input type="email" name="email_contacto" class="form-control" required placeholder="ejemplo@empresa.com">
                </div>
                <div class="form-group">
                    <label>Sector / Industria</label>
                    <input type="text" name="sector" class="form-control" placeholder="Ej: Tecnología, Retail, Alimentos">
                </div>
            </div>
        </div>

        <!-- Preguntas Evaluación -->
        <?php foreach ($preguntas as $id => $p): ?>
            <div class="glass-card">
                <div class="question-header">
                    <div class="question-icon" style="background: <?= $p['color'] ?>;">
                        <i class="<?= $p['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="question-category"><?= htmlspecialchars($p['categoria'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="question-title"><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <div class="question-desc">
                    <?= htmlspecialchars($p['desc'], ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="likert-container">
                    <?php 
                    $niveles = [
                        1 => 'M. Bajo',
                        2 => 'Bajo',
                        3 => 'Medio',
                        4 => 'Alto',
                        5 => 'Excelente'
                    ];
                    foreach ($niveles as $val => $texto): 
                    ?>
                        <div class="likert-option opt-<?= $val ?>">
                            <input type="radio" id="<?= $id ?>_<?= $val ?>" name="preguntas[<?= $id ?>]" value="<?= $val ?>" required class="radio-preg">
                            <label for="<?= $id ?>_<?= $val ?>" class="likert-tile">
                                <span class="likert-number"><?= $val ?></span>
                                <span class="likert-text"><?= $texto ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Acciones -->
        <div class="btn-submit-container">
            <button type="submit" id="btnEnviar" class="btn-gradient">
                <i class="ri-send-plane-fill"></i> Finalizar y Guardar Diagnóstico
            </button>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formDiagnostico');
    const progressBar = document.getElementById('progressBar');
    const totalPreguntas = <?= count($preguntas) ?>;
    const btnEnviar = document.getElementById('btnEnviar');

    // Actualizar barra de progreso dinámica
    form.addEventListener('change', () => {
        const respondidas = new Set();
        document.querySelectorAll('.radio-preg:checked').forEach(radio => {
            respondidas.add(radio.name);
        });

        const porcentaje = Math.round((respondidas.size / totalPreguntas) * 100);
        progressBar.style.width = `${porcentaje}%`;
    });

    // Envío asíncrono con Fetch
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Guardando diagnóstico...';

        const formData = new FormData(this);

        fetch('procesar_diagnostico.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(`¡Diagnóstico Registrado Exitosamente!\n\nPuntuación Total: ${data.puntuacion_total} / ${data.max_posible} puntos.`);
                form.reset();
                progressBar.style.width = '0%';
            } else {
                alert('Atención: ' + (data.message || 'No se pudo procesar la solicitud.'));
            }
        })
        .catch(error => {
            console.error('Error en la petición AJAX:', error);
            alert('Ocurrió un error de conexión al intentar enviar el formulario.');
        })
        .finally(() => {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = '<i class="ri-send-plane-fill"></i> Finalizar y Guardar Diagnóstico';
        });
    });
});
</script>

</body>
</html>