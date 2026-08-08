<?php
// resultado.php - Módulo de Visualización de Resultados
declare(strict_types=1);
session_start();

if (!isset($_SESSION['diagnostico_resultado'])) {
    header('Location: diagnostico.php');
    exit;
}

$reporte = $_SESSION['diagnostico_resultado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados del Diagnóstico Corporativo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #311042 100%);
            --bg-pattern: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.035)' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 21h18M4 21V9l8-6 8 6v12M9 21v-6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v6M9 9h.01M15 9h.01M9 13h.01M15 13h.01'/%3E%3C/svg%3E");
            --card-glass: rgba(255, 255, 255, 0.95);
            --card-border: rgba(255, 255, 255, 0.2);
            --text-dark: #0f172a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-image: var(--bg-pattern), var(--bg-gradient);
            background-repeat: repeat, no-repeat;
            background-position: top left, center;
            background-size: 70px 70px, cover;
            background-attachment: fixed, fixed;
            color: var(--text-dark);
            min-height: 100vh;
            padding: 2rem 1rem 4rem 1rem;
        }

        .container { max-width: 900px; margin: 0 auto; }

        .header-hero {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem 2rem;
            text-align: center;
            color: #ffffff;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .header-hero h1 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .glass-card {
            background: var(--card-glass);
            border-radius: 20px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        /* Rejilla de Categorías Evaluadas */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .cat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.5rem;
            border-left: 6px solid #cbd5e1;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .cat-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.1rem;
            font-weight: 800;
        }

        .status-badge {
            padding: 0.35rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .recommendation-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.88rem;
            color: #334155;
            line-height: 1.5;
            border: 1px solid #e2e8f0;
        }

        /* Contenedor de Debilidades */
        .weakness-card {
            background: #ffffff;
            border-left: 6px solid #ef4444;
        }

        .weakness-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .weakness-item:last-child { border-bottom: none; }

        .score-pill {
            background: #fee2e2;
            color: #ef4444;
            font-weight: 800;
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .btn-return {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 14px;
            font-weight: 800;
            margin-top: 1rem;
            transition: transform 0.2s;
        }
        .btn-return:hover { transform: translateY(-2px); }

        @media (max-width: 640px) {
            .categories-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-hero">
        <span style="color:#cbd5e1; text-transform:uppercase; font-size:0.8rem; letter-spacing:1px; font-weight:700;">Reporte Diagnóstico</span>
        <h1><?= htmlspecialchars($reporte['empresa'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p style="color:#94a3b8; margin-top:0.4rem;">Puntuación Global: <strong><?= $reporte['puntuacion_total'] ?> / <?= $reporte['max_posible'] ?> Puntos</strong></p>
    </div>

    <!-- Rejilla de Categorías con Colores de Estado y Recomendaciones -->
    <h2 style="color:white; font-size:1.4rem; margin-bottom:1rem; font-weight:800;">Estado por Área Estratégica</h2>
    
    <div class="categories-grid">
        <?php foreach ($reporte['eval_categorias'] as $cat): ?>
            <div class="cat-card" style="border-left-color: <?= $cat['color'] ?>;">
                <div>
                    <div class="cat-header">
                        <div class="cat-title">
                            <i class="<?= $cat['icon'] ?>" style="color: <?= $cat['color'] ?>; font-size:1.4rem;"></i>
                            <span><?= htmlspecialchars($cat['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <span class="status-badge" style="background: <?= $cat['bg'] ?>; color: <?= $cat['color'] ?>;">
                            <?= $cat['estado'] ?>
                        </span>
                    </div>
                    <div style="font-size:0.9rem; color:#64748b; font-weight:600;">
                        Cumplimiento: <?= $cat['porcentaje'] ?>% (<?= $cat['puntuacion'] ?> / <?= $cat['maximo'] ?> pts)
                    </div>
                </div>

                <div class="recommendation-box">
                    <strong style="color:#0f172a; display:block; margin-bottom:0.3rem;"><i class="ri-lightbulb-line"></i> Recomendación:</strong>
                    <?= htmlspecialchars($cat['recomendacion'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<!-- NUEVO BLOQUE: Puntos Más Fortes / Pilares Estratégicos -->
    <div class="glass-card strength-card" style="border-left: 6px solid #10b981;">
        <h3 style="color:#10b981; font-size:1.2rem; font-weight:800; display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
            <i class="ri-shield-check-fill"></i> Principales Fortalezas Organizacionales
        </h3>

        <?php if (empty($reporte['preguntas_fuertes'])): ?>
            <p style="color:#64748b; font-size:0.9rem;">No se detectaron aspectos sobresalientes (puntuación de 4 o 5) en esta evaluación. Se recomienda enfocar esfuerzos en institucionalizar los procesos base.</p>
        <?php else: ?>
            <?php foreach ($reporte['preguntas_fuertes'] as $fort): ?>
                <div class="weakness-item"> <!-- Reutiliza estructura con clases específicas -->
                    <span class="score-pill" style="background:#d1fae5; color:#059669;">Puntaje: <?= $fort['score'] ?>/5</span>
                    <div>
                        <div style="font-weight:700; color:#0f172a; font-size:0.95rem; margin-bottom:0.25rem;">
                            [<?= $fort['categoria'] ?>] Ámbito Destacado: <?= strtoupper($fort['id']) ?>
                        </div>
                        <div style="font-size:0.88rem; color:#334155;">
                            <strong>Logro Consolidado:</strong> <?= htmlspecialchars($fort['fortaleza'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- Cuadro Específico: Preguntas con Mayor Debilidad -->
    <div class="glass-card weakness-card">
        <h3 style="color:#ef4444; font-size:1.2rem; font-weight:800; display:flex; align-items:center; gap:0.5rem; margin-bottom:1rem;">
            <i class="ri-alert-fill"></i> Puntos Críticos de Atención Inmediata
        </h3>

        <?php if (empty($reporte['preguntas_debiles'])): ?>
            <p style="color:#10b981; font-weight:600;"><i class="ri-checkbox-circle-fill"></i> No se registraron debilidades críticas en el diagnóstico. La organización mantiene un nivel satisfactorio en todos los ítems.</p>
        <?php else: ?>
            <?php foreach ($reporte['preguntas_debiles'] as $deb): ?>
                <div class="weakness-item">
                    <span class="score-pill">Puntaje: <?= $deb['score'] ?>/5</span>
                    <div>
                        <div style="font-weight:700; color:#0f172a; font-size:0.95rem; margin-bottom:0.25rem;">
                            [<?= $deb['categoria'] ?>] Pregunta de Control: <?= strtoupper($deb['id']) ?>
                        </div>
                        <div style="font-size:0.88rem; color:#475569;">
                            <strong>Acción Sugerida:</strong> <?= htmlspecialchars($deb['recomendacion'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="text-align:center; margin-top:2rem;">
        <a href="diagnostico.php" class="btn-return">
            <i class="ri-refresh-line"></i> Realizar Nueva Evaluación
        </a>
    </div>
</div>

</body>
</html>