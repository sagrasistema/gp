<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Control de Clientes'; ?></title>
    
    <?php
    // Detectamos dinámicamente la ruta base hacia la carpeta principal 'main'
    $baseDir = (strpos($_SERVER['REQUEST_URI'], '/ac/') !== false) ? '../main/' : '';
    ?>

    <link rel="shortcut icon" href="<?php echo $baseDir; ?>favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?php echo $baseDir; ?>favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --border-color: #e2e8f0;
            --status-activo: #10b981;
            --status-inactivo: #ef4444;
        }
        
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Segoe UI', system-ui, sans-serif; 
        }
        
        body { 
            background-color: var(--bg-primary); 
            color: var(--text-main); 
            min-height: 100vh;
            background-image: url('<?php echo $baseDir; ?>mosaico.svg'); 
            background-repeat: repeat; 
        }
        
        .container { width: 100%; max-width: 1200px; margin: 0 auto; }
        .container-form { max-width: 600px; } 
        
        .btn { padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; border: none; text-decoration: none; font-size: 0.95rem; transition: background 0.2s ease; }
        .btn-primary { background-color: var(--accent); color: white; }
        .btn-primary:hover { background-color: var(--accent-hover); }
        .btn-secondary { background-color: #f1f5f9; color: var(--text-muted); border: 1px solid var(--border-color); }
        .btn-secondary:hover { background-color: #e2e8f0; color: var(--text-main); }
        
        .card { background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 2rem; }
        .table-container { background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        
        table.custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem; }
        table.custom-table th { background-color: #f8fafc; padding: 1rem; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-color); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        table.custom-table td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        table.custom-table tr:hover td { background-color: #f8fafc; }
        /* --- CONTENEDOR PRINCIPAL FORZADO A LA DERECHA --- */
        .table-actions-container {
            display: flex !important;
            justify-content: flex-end !important;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            width: 100%; /* Asegura que ocupe todo el ancho para poder alinearse a la derecha */
        }

        /* --- CONFIGURACIÓN DE LOS TOOLTIPS --- */

        /* Posicionamiento relativo individual para cada botón */
        .table-actions-container a {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* El globo del Tooltip */
        .table-actions-container a::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 125%; /* Lo ubica justo arriba del botón */
            left: 50%;
            transform: translateX(-50%) translateY(5px);
            background-color: #1e293b; /* Fondo oscuro elegante */
            color: #ffffff;
            padding: 0.4rem 0.7rem;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 99;
            pointer-events: none; /* Evita interferir con los clics */
        }

        /* La pequeña flecha del Tooltip */
        .table-actions-container a::before {
            content: "";
            position: absolute;
            bottom: 110%;
            left: 50%;
            transform: translateX(-50%) translateY(5px);
            border-width: 6px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent; /* Flecha apuntando abajo */
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 99;
            pointer-events: none;
        }

        /* Acción Hover: Muestra el tooltip con un efecto de deslizamiento hacia arriba */
        .table-actions-container a:hover::after,
        .table-actions-container a:hover::before {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        /* Ajuste para que los botones deshabilitados tengan el cursor correcto y sí muestren el tooltip */
        .btn-control-disabled {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.55rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background-color: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed; /* Muestra el icono de prohibido */
            text-decoration: none;
        }

        /* Modo oscuro para el botón deshabilitado */
        body.dark-mode .btn-control-disabled {
            background-color: #1e293b;
            border-color: #334155;
            color: #64748b;
        }
            /* Estilos internos específicos del Formulario y Acordeones */
    .view-container-form { width: 100%; max-width: 1000px; margin: 0 auto; }
    
    .meta-summary { background: #fff; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color, #e2e8f0); margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 2rem; }
    .meta-item { font-size: 0.9rem; color: var(--text-muted, #64748b); }
    .meta-item strong { color: var(--text-main, #0f172a); display: block; font-size: 1.05rem; }
    
    /* CUADRÍCULA DE ACTIVIDADES (PROGRESO) */
    .activities-grid-card { background: #fff; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem; }
    .activities-grid-card h3 { font-size: 0.95rem; font-weight: 700; margin-top: 0; margin-bottom: 0.75rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
    .activities-grid { display: grid; grid-template-columns: repeat(15, 1fr); gap: 0.5rem; }
    .activity-box { display: flex; align-items: center; justify-content: center; height: 35px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; text-decoration: none; border: 1px solid #cbd5e1; transition: all 0.2s ease-in-out; cursor: pointer; }
    
    /* Estados de la Cuadrícula */
    .activity-box.pending { background: #f1f5f9; color: #64748b; border-color: #cbd5e1; }
    .activity-box.completed { background: #10b981; color: #ffffff; border-color: #059669; }
    .activity-box:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }

    .accordion-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 0.5rem; overflow: hidden; }
    .accordion-header { background: #fff; padding: 1rem 1.25rem; font-size: 0.95rem; font-weight: 600; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; transition: background 0.2s; border-left: 4px solid var(--accent, #0284c7); }
    .accordion-header:hover { background: #f8fafc; }
    .accordion-header i { font-size: 1.2rem; color: #64748b; transition: transform 0.2s; }
    
    .accordion-item.active .accordion-header { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
    .accordion-item.active .accordion-header i { transform: rotate(180deg); }
    .accordion-content { display: none; padding: 1.25rem; background: #fafafa; }
    .accordion-item.active .accordion-content { display: block; }

    .question-row { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 1.25rem; margin-bottom: 0.75rem; scroll-margin-top: 80px; }
    .question-text { font-size: 0.95rem; font-weight: 500; color: #1e293b; margin-bottom: 1rem; line-height: 1.4; }
    .question-inputs { display: grid; grid-template-columns: 180px 1fr; gap: 1.5rem; align-items: center; }
    
    .radio-group { display: flex; gap: 1.25rem; }
    .radio-label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer; font-weight: 600; color: #475569; }
    .radio-label input { width: 17px; height: 17px; accent-color: var(--accent, #0284c7); }
    
    .comment-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0.5rem 0.75rem; font-size: 0.88rem; outline: none; transition: border-color 0.2s; }
    .comment-input:focus { border-color: var(--accent, #0284c7); }

    .subtest-table { width: 100%; border-collapse: collapse; margin-top: 1.25rem; font-size: 0.88rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; }
    .subtest-table th { background: #f8fafc; text-align: left; padding: 0.75rem; font-size: 0.8rem; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
    .subtest-table td { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .subtest-table select { padding: 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 0.85rem; width: 100%; max-width: 180px; background: #fff; outline: none; }
    .subtest-table select:focus { border-color: var(--accent, #0284c7); }
    
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }

    .badge-risk { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.95rem; font-weight: 700; transition: all 0.3s ease; }
    .badge-risk.risk-bajo { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .badge-risk.risk-moderado { background-color: #fefce8; color: #854d0e; border: 1px solid #fef08a; }
    .badge-risk.risk-moderado-alto { background-color: #fff7ed; color: #9a3412; border: 1px solid #ffedd5; }
    .badge-risk.risk-alto { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

    @media (max-width: 768px) {
        .meta-summary {font-size: 5em; flex-direction: column; gap: 1rem !important; }
        .meta-item:last-child { align-items: flex-start !important; text-align: left !important; margin-left: 0 !important; }
        .question-inputs { grid-template-columns: 1fr; gap: 1rem; }
        .activities-grid { grid-template-columns: repeat(6, 1fr); }
    }

    </style>
</head>
<body>
<!-- Se removió el .main-wrapper centrado para permitir layouts Full-Width nativos -->