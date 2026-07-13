<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Control de Clientes'; ?></title>
    
    <?php
    // Detectamos dinámicamente la ruta base hacia la carpeta principal 'main'
    // Esto evita que se rompan los estilos al entrar en subcarpetas como 'ac'
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
            padding: 2rem 1rem; 
            /* Se usa la ruta corregida para el fondo */
            background-image: url('<?php echo $baseDir; ?>mosaico.svg'); 
            background-repeat: repeat; 
        }
        
        /* Regla flexible para no romper layouts basados en Materialize o Bootstrap */
        .main-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .brand-topbar {
            width: 100%;
            max-width: 1200px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: flex-start;
        }

        .brand-logo {
            height: 60px;
            width: auto;
            object-fit: contain;
        }
                 
        .container { width: 100%; max-width: 1200px; margin: 0 auto; }
        .container-form { max-width: 600px; } 
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        h1 { font-size: 1.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        h1 i { color: var(--accent); }
        .btn-back { color: var(--text-muted); text-decoration: none; font-size: 1.5rem; display: inline-flex; align-items: center; }
        .btn-back:hover { color: var(--text-main); }
        .header-actions { display: flex; gap: 0.75rem; }
        
        .btn { padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; border: none; text-decoration: none; font-size: 0.95rem; }
        .btn-primary { background-color: var(--accent); color: white; }
        .btn-primary:hover { background-color: var(--accent-hover); }
        .btn-success { background-color: #10b981; color: white; }
        .btn-success:hover { background-color: #059669; }
        .btn-secondary { background-color: #f1f5f9; color: var(--text-muted); }
        .btn-secondary:hover { background-color: #e2e8f0; color: var(--text-main); }
        
        .card { background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 2rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.5rem; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        .form-group input, .form-group select { padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; width: 100%; }
        .form-group input:focus, .form-group select:focus { border-color: var(--accent); }
        
        .actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; }
        .table-container { background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        
        /* Estilos base para tablas limpias */
        table.custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem; }
        table.custom-table th { background-color: #f8fafc; padding: 1rem; font-weight: 600; color: var(--text-muted); border-bottom: 1px solid var(--border-color); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        table.custom-table td { padding: 1rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        table.custom-table tr:last-child td { border-bottom: none; }
        table.custom-table tr:hover td { background-color: #f8fafc; }
        
        /* Corrección de la comilla en badge */
        .badge { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
        .badge-activo { background-color: #d1fae5; color: var(--status-activo); }
        .badge-inactivo { background-color: #fee2e2; color: var(--status-inactivo); }
        .actions-cell { display: flex; gap: 0.5rem; justify-content: center; }
        .btn-icon { background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 6px; border-radius: 4px; font-size: 1.1rem; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-icon-edit:hover { color: var(--accent); background-color: #e0f2fe; }
        .btn-icon-delete:hover { color: var(--status-inactivo); background-color: #fee2e2; }
    </style>
</head>
<body>
<div class="main-wrapper">