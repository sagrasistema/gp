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
        .table-actions-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem; /* Separación discreta con la tabla */
        }
    
        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.5rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        /* Efecto hover suave */
        .btn-export:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }
            /* Ajuste para el modo oscuro si lo tienes implementado */
        body.dark-mode .btn-export {
            background-color: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }
        body.dark-mode .btn-export:hover {
            background-color: #334155;
            color: #f8fafc;
        }
    </style>
</head>
<body>
<!-- Se removió el .main-wrapper centrado para permitir layouts Full-Width nativos -->