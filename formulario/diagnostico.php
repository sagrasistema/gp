<?php
// diagnostico.php
$preguntas = [
    'p1' => [
        'titulo' => 'Órganos de Gobierno',
        'desc'   => '¿La empresa cuenta con un Consejo de Familia o Junta Directiva formal que se reúna periódicamente?'
    ],
    'p2' => [
        'titulo' => 'Protocolo Familiar',
        'desc'   => '¿Existen reglas claras y escritas que regulen el ingreso de familiares a trabajar en la empresa?'
    ],
    'p3' => [
        'titulo' => 'Plan de Sucesión',
        'desc'   => '¿Se ha definido un plan claro y estructurado para la sucesión del liderazgo y de la propiedad?'
    ],
    'p4' => [
        'titulo' => 'Separación Patrimonio / Empresa',
        'desc'   => '¿Están claramente delimitadas las finanzas de la familia respecto a los recursos de la empresa?'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Empresa Familiar</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            padding: 2rem 1rem;
            line-height: 1.5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
        }

        .header-card h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; }
        .header-card p { color: #94a3b8; font-size: 0.95rem; }

        .card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.4rem; color: #334155; }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus { border-color: var(--primary); }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        /* Escala Likert de 1 a 5 */
        .question-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin-bottom: 0.3rem; }
        .question-desc { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem; }

        .likert-scale {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
        }

        .likert-option { position: relative; }
        .likert-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }

        .likert-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 0.5rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .likert-option input[type="radio"]:checked + .likert-label {
            border-color: var(--primary);
            background: #eff6ff;
            color: var(--primary);
            transform: translateY(-2px);
        }

        .likert-sub { font-size: 0.7rem; font-weight: 400; margin-top: 0.2rem; color: #94a3b8; }

        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: var(--primary-hover); }
    </style>
</head>
<body>

<div class="container">
    <div class="header-card">
        <h1><i class="ri-building-2-line"></i> Diagnóstico de Empresa Familiar</h1>
        <p>Evalúa el nivel de institucionalización, gobernanza y madurez de tu organización en una escala del 1 (Muy bajo) al 5 (Excelente).</p>
    </div>

    <form id="formDiagnostico">
        <!-- Tarjeta: Información General -->
        <div class="card">
            <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:1rem; color:#1e293b;">Datos de la Empresa</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="nombre_empresa" class="form-control" required placeholder="Ej: Grupo Comercial S.A.">
                </div>
                <div class="form-group">
                    <label>Nombre del Contacto</label>
                    <input type="text" name="nombre_contacto" class="form-control" required placeholder="Ej: Juan Pérez">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="email_contacto" class="form-control" required placeholder="juan@empresa.com">
                </div>
                <div class="form-group">
                    <label>Sector / Industria</label>
                    <input type="text" name="sector" class="form-control" placeholder="Ej: Manufactura / Retail">
                </div>
            </div>
        </div>

        <!-- Preguntas -->
        <?php foreach ($preguntas as $id => $p): ?>
            <div class="card">
                <div class="question-title"><?= htmlspecialchars($p['titulo']) ?></div>
                <div class="question-desc"><?= htmlspecialchars($p['desc']) ?></div>

                <div class="likert-scale">
                    <?php 
                    $labels = [1 => 'Muy Bajo', 2 => 'Bajo', 3 => 'Medio', 4 => 'Alto', 5 => 'Excelente'];
                    for ($i = 1; $i <= 5; $i++): 
                    ?>
                        <div class="likert-option">
                            <input type="radio" id="<?= $id ?>_<?= $i ?>" name="preguntas[<?= $id ?>]" value="<?= $i ?>" required>
                            <label for="<?= $id ?>_<?= $i ?>" class="likert-label">
                                <span><?= $i ?></span>
                                <span class="likert-sub"><?= $labels[$i] ?></span>
                            </label>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <button type="submit" id="btnEnviar" class="btn-submit">
            <i class="ri-send-plane-fill"></i> Completar y Guardar Diagnóstico
        </button>
    </form>
</div>

<script>
document.getElementById('formDiagnostico').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line"></i> Guardando...';

    const formData = new FormData(this);

    fetch('procesar_diagnostico.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`¡Diagnóstico completado!\nPuntuación Total: ${data.puntuacion_total} / ${data.max_posible}`);
            this.reset();
        } else {
            alert('Atención: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Ocurrió un error al enviar las respuestas.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-send-plane-fill"></i> Completar y Guardar Diagnóstico';
    });
});
</script>

</body>
</html>