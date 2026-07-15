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
    </style>
</head>
<body>
<!-- Se removió el .main-wrapper centrado para permitir layouts Full-Width nativos -->