<?php
// diagnostico.php - Interfaz Avanzada de Diagnóstico para Empresa Familiar
// Compatibilidad: PHP 7.4+ / PHP 8.x | Estándar PSR-12

$categorias = [
    'GOBERNANZA' => [
        'titulo' => 'Gobernanza y Órganos de Dirección',
        'icon'   => 'ri-government-line',
        'color'  => '#6366f1',
        'preguntas' => [
            'p1' => ['titulo' => 'Consejo de Familia', 'desc' => '¿La empresa cuenta con un Consejo de Familia formalizado que se reúna periódicamente?'],
            'p2' => ['titulo' => 'Junta Directiva', 'desc' => '¿Existe una Junta Directiva o Consejo de Administración con miembros independientes?'],
            'p3' => ['titulo' => 'Definición de Roles', 'desc' => '¿Están formalmente diferenciados los roles de accionista, consejero y directivo?'],
            'p4' => ['titulo' => 'Rendición de Cuentas', 'desc' => '¿Se presentan estados financieros auditados e informes de gestión a la familia/socios?'],
            'p5' => ['titulo' => 'Toma de Decisiones', 'desc' => '¿Existen límites claros de autoridad para decisiones de inversión y endeudamiento?']
        ]
    ],
    'PROTOCOLO' => [
        'titulo' => 'Protocolo y Normativa Familiar',
        'icon'   => 'ri-file-paper-2-line',
        'color'  => '#ec4899',
        'preguntas' => [
            'p6'  => ['titulo' => 'Protocolo Familiar Firmado', 'desc' => '¿Se cuenta con un Protocolo Familiar firmado y vinculante por todos los miembros?'],
            'p7'  => ['titulo' => 'Política de Empleo', 'desc' => '¿Existen requisitos claros de perfil, experiencia y titulación para familiares que deseen trabajar en la empresa?'],
            'p8'  => ['titulo' => 'Política Remunerativa', 'desc' => '¿Los sueldos de los familiares están alineados estrictamente con los valores de mercado?'],
            'p9'  => ['titulo' => 'Resolución de Conflictos', 'desc' => '¿Existen mecanismos estipulados de mediación o arbitraje para desacuerdos familiares?'],
            'p10' => ['titulo' => 'Política de Dividendos', 'desc' => '¿Se encuentra claramente establecida la política de reparto o reinversión de utilidades?']
        ]
    ],
    'SUCESIÓN' => [
        'titulo' => 'Sucesión y Continuidad',
        'icon'   => 'ri-git-merge-line',
        'color'  => '#8b5cf6',
        'preguntas' => [
            'p11' => ['titulo' => 'Plan de Sucesión Directiva', 'desc' => '¿Existe un plan escrito y aprobado para la sucesión del Director General / CEO?'],
            'p12' => ['titulo' => 'Capacitación de Sucesores', 'desc' => '¿Se ejecuta un programa estructurado para la formación de la siguiente generación?'],
            'p13' => ['titulo' => 'Transferencia de Propiedad', 'desc' => '¿Está definida la estrategia fiscal y legal para la transmisión de las acciones?'],
            'p14' => ['titulo' => 'Plan de Emergencia', 'desc' => '¿Existe un protocolo en caso de incapacidad imprevista o fallecimiento del líder actual?'],
            'p15' => ['titulo' => 'Liderazgo Futuro', 'desc' => '¿La siguiente generación muestra compromiso explícito y vocación para dar continuidad al negocio?']
        ]
    ],
    'FINANZAS' => [
        'titulo' => 'Estrategia y Gestión Financiera',
        'icon'   => 'ri-bank-card-line',
        'color'  => '#06b6d4',
        'preguntas' => [
            'p16' => ['titulo' => 'Separación Patrimonial', 'desc' => '¿Están 100% delimitadas las finanzas personales de la familia frente a los activos de la empresa?'],
            'p17' => ['titulo' => 'Planificación Estratégica', 'desc' => '¿La empresa cuenta con un plan estratégico formal a 3-5 años con indicadores KPIs?'],
            'p18' => ['titulo' => 'Presupuestos y Control', 'desc' => '¿Se elabora y supervisa mensualmente un presupuesto operativo y de flujo de caja?'],
            'p19' => ['titulo' => 'Estructura de Capital', 'desc' => '¿Existe una política prudente de endeudamiento y reinversión para sostener el crecimiento?'],
            'p20' => ['titulo' => 'Evaluación de Riesgos', 'desc' => '¿Se realizan auditorías periódicas de riesgos operativos, legales y de mercado?']
        ]
    ]
];

$totalPreguntas = 0;
foreach ($categorias as $cat) {
    $totalPreguntas += count($cat['preguntas']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Madurez Corporativa</title>

    <!-- Fuentes e Íconos Remotos Cargados Prioritariamente -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            /* Mantenemos exactamente la paleta de colores del body */
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #311042 100%);
            
            /* Mosaico vectorizado vectorizado en UTF-8:
            Combina una estructura de empresa/hogar en trazo ultra sutil (opacidad 3.5%) */
            --bg-pattern: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.035)' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 21h18M4 21V9l8-6 8 6v12M9 21v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6M9 9h.01M15 9h.01M9 13h.01M15 13h.01'/%3E%3C/svg%3E");

            --card-glass: rgba(255, 255, 255, 0.95);
            --card-border: rgba(255, 255, 255, 0.2);
            --text-dark: #0f172a;
            --text-muted: #64748b;
            
            /* Colores Escala Likert */
            --lvl-1: #ef4444;
            --lvl-2: #f97316;
            --lvl-3: #eab308;
            --lvl-4: #84cc16;
            --lvl-5: #10b981;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            
            /* Capa 1: Mosaico SVG repetido en bucle | Capa 2: Degradado lineal base */
            background-image: var(--bg-pattern), var(--bg-gradient);
            background-repeat: repeat, no-repeat;
            background-position: top left, center;
            background-size: 70px 70px, cover;
            background-attachment: fixed, fixed;
            
            color: var(--text-dark);
            min-height: 100vh;
            padding: 2rem 1rem 4rem 1rem;
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

        .container { max-width: 900px; margin: 0 auto; }

        /* Hero Header con Glassmorphism */
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
            font-size: 2.2rem;
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

        /* Separadores de Categoría */
        .category-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 1.25rem 1.5rem;
            color: #ffffff;
            margin: 2.5rem 0 1.5rem 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .category-header i { font-size: 2rem; }
        .category-header h2 { font-size: 1.35rem; font-weight: 800; }

        /* Tarjetas de Pregunta */
        .glass-card {
            background: var(--card-glass);
            border-radius: 20px;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .glass-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .question-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
        .question-desc { font-size: 0.92rem; color: #475569; margin: 0.5rem 0 1.25rem 0; line-height: 1.5; }

        /* Rejilla Escala Likert Escritorio */
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

        .likert-number { font-size: 1.25rem; font-weight: 800; color: #334155; }
        .likert-text { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-top: 0.2rem; }

        .likert-tile:hover {
            transform: translateY(-2px);
            border-color: #cbd5e1;
        }

        /* Colores dinámicos al seleccionar */
        .opt-1 input:checked + .likert-tile { border-color: var(--lvl-1); background: rgba(239, 68, 68, 0.1); }
        .opt-1 input:checked + .likert-tile .likert-number, .opt-1 input:checked + .likert-tile .likert-text { color: var(--lvl-1); }

        .opt-2 input:checked + .likert-tile { border-color: var(--lvl-2); background: rgba(249, 115, 22, 0.1); }
        .opt-2 input:checked + .likert-tile .likert-number, .opt-2 input:checked + .likert-tile .likert-text { color: var(--lvl-2); }

        .opt-3 input:checked + .likert-tile { border-color: var(--lvl-3); background: rgba(234, 179, 8, 0.15); }
        .opt-3 input:checked + .likert-tile .likert-number, .opt-3 input:checked + .likert-tile .likert-text { color: #b45309; }

        .opt-4 input:checked + .likert-tile { border-color: var(--lvl-4); background: rgba(132, 204, 22, 0.15); }
        .opt-4 input:checked + .likert-tile .likert-number, .opt-4 input:checked + .likert-tile .likert-text { color: #4d7c0f; }

        .opt-5 input:checked + .likert-tile { border-color: var(--lvl-5); background: rgba(16, 185, 129, 0.15); }
        .opt-5 input:checked + .likert-tile .likert-number, .opt-5 input:checked + .likert-tile .likert-text { color: var(--lvl-5); }

        /* Inputs Generales */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .form-group label { font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; }
        .form-control {
            padding: 0.85rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            outline: none;
            background: #f8fafc;
            transition: all 0.25s ease;
        }
        .form-control:focus { border-color: #6366f1; background: #ffffff; }

        /* Botón de Envio Gradiente */
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
            margin-top: 2rem;
            box-shadow: 0 12px 25px rgba(236, 72, 153, 0.35);
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .btn-gradient:hover { background-position: right center; transform: translateY(-2px); }

        /* Adaptabilidad Móvil (Responsive Touch Fix) */
        @media (max-width: 640px) {
            body { padding: 1rem 0.5rem; }
            .header-hero { padding: 2rem 1rem; }
            .header-hero h1 { font-size: 1.6rem; }
            
            /* Reorganización de escala Likert en móviles para mostrar número y texto legible */
            .likert-container {
                grid-template-columns: repeat(5, 1fr);
                gap: 0.3rem;
            }
            .likert-tile {
                padding: 0.6rem 0.1rem;
                border-radius: 10px;
            }
            .likert-number { font-size: 1rem; }
            .likert-text {
                display: block; /* Mantiene la visibilidad del texto */
                font-size: 0.55rem;
                letter-spacing: -0.2px;
            }
        }
    </style>
</head>
<body>

<div class="progress-bar-container">
    <div class="progress-bar-fill" id="progressBar"></div>
</div>

<div class="container">
    <div class="header-hero">
        <div class="badge">
            <i class="ri-sparkling-fill"></i> Evaluación de Madurez
        </div>
        <h1>Diagnóstico de Empresa Familiar</h1>
        <p>Evaluación de 20 puntos de control distribuidos en 4 áreas operativas estratégicas.</p>
    </div>

    <form id="formDiagnostico">
        <!-- Información de Empresa -->
        <div class="glass-card">
            <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:1.25rem; color:#1e293b; display:flex; align-items:center; gap:0.5rem;">
                <i class="ri-building-4-fill" style="color: #6366f1;"></i> Datos de la Organización
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Organización *</label>
                    <input type="text" name="nombre_empresa" class="form-control" required placeholder="Ej: Corporación Vanguardia">
                </div>
                <div class="form-group">
                    <label>Contacto *</label>
                    <input type="text" name="nombre_contacto" class="form-control" required placeholder="Ej: Alejandro Silva">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email_contacto" class="form-control" required placeholder="ejemplo@empresa.com">
                </div>
                <div class="form-group">
                    <label>Sector</label>
                    <input type="text" name="sector" class="form-control" placeholder="Ej: Tecnología, Retail">
                </div>
            </div>
        </div>

        <!-- Renderizado Dinámico de Categorías -->
        <?php foreach ($categorias as $catKey => $catData): ?>
            <div class="category-header">
                <i class="<?= $catData['icon'] ?>" style="color: <?= $catData['color'] ?>;"></i>
                <h2><?= htmlspecialchars($catData['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
            </div>

            <?php foreach ($catData['preguntas'] as $pId => $pData): ?>
                <div class="glass-card">
                    <div class="question-title"><?= htmlspecialchars($pData['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="question-desc"><?= htmlspecialchars($pData['desc'], ENT_QUOTES, 'UTF-8') ?></div>

                    <div class="likert-container">
                        <?php 
                        $escalas = [1 => 'Muy Bajo', 2 => 'Bajo', 3 => 'Medio', 4 => 'Alto', 5 => 'Excelente'];
                        foreach ($escalas as $val => $txt): 
                        ?>
                            <div class="likert-option opt-<?= $val ?>">
                                <input type="radio" id="<?= $pId ?>_<?= $val ?>" name="preguntas[<?= $pId ?>]" value="<?= $val ?>" required class="radio-preg">
                                <label for="<?= $pId ?>_<?= $val ?>" class="likert-tile">
                                    <span class="likert-number"><?= $val ?></span>
                                    <span class="likert-text"><?= $txt ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <button type="submit" id="btnEnviar" class="btn-gradient">
            <i class="ri-send-plane-fill"></i> Finalizar y Guardar Diagnóstico
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formDiagnostico');
    const progressBar = document.getElementById('progressBar');
    const totalPreguntas = <?= $totalPreguntas ?>;
    const btnEnviar = document.getElementById('btnEnviar');

    form.addEventListener('change', () => {
        const respondidas = new Set();
        document.querySelectorAll('.radio-preg:checked').forEach(radio => respondidas.add(radio.name));
        const porcentaje = Math.round((respondidas.size / totalPreguntas) * 100);
        progressBar.style.width = `${porcentaje}%`;
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Guardando diagnóstico...';

        fetch('procesar_diagnostico.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(`¡Diagnóstico Registrado Exitosamente!\n\nPuntuación Total: ${data.puntuacion_total} / ${data.max_posible} puntos.`);
                form.reset();
                progressBar.style.width = '0%';
            } else {
                alert('Atención: ' + data.message);
            }
        })
        .catch(() => alert('Error crítico al procesar la solicitud en el servidor.'))
        .finally(() => {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = '<i class="ri-send-plane-fill"></i> Finalizar y Guardar Diagnóstico';
        });
    });
});
</script>

</body>
</html>