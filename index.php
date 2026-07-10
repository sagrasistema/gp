<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal - Sistema de Gestión</title>
    
    <link rel="shortcut icon" href="client/favicon.ico" type="image/x-icon">
    <link rel="icon" href="client/favicon.ico" type="image/x-icon">
    
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --border-color: #e2e8f0;
        }

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Segoe UI', system-ui, sans-serif; 
        }

        body { 
            background-color: var(--bg-primary); 
            background-image: url('client/mosaico.svg'); /* Reutiliza tu mosaico */
            background-repeat: repeat;
            background-size: 60px 60px;
            color: var(--text-main); 
            padding: 3rem 2rem; 
            display: flex; 
            justify-content: center; 
            align-items: center;
            min-height: 100vh;
        }

        .container { 
            width: 100%; 
            max-width: 1100px; 
        }

        /* Encabezado Principal */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .logo-title-wrapper {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .header-logo {
            height: 55px;
            width: auto;
            object-fit: contain;
        }

        header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
        }

        header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 0.25rem;
        }

        /* Grid de Módulos (Estilo de la imagen de referencia) */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2rem;
        }

        /* Tarjeta de Módulo */
        .module-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 10px 15px -3px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Efectos Hover */
        .module-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 20px 25px -5px rgba(2, 132, 199, 0.1), 0 10px 10px -5px rgba(2, 132, 199, 0.04);
        }

        /* Contenedor del Icono */
        .icon-box {
            width: 70px;
            height: 70px;
            background-color: #f0f9ff;
            color: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .module-card:hover .icon-box {
            background-color: var(--accent);
            color: #ffffff;
        }

        .module-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-main);
        }

        .module-card p {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Estilos Especiales para los Módulos No Desarrollados */
        .module-card.disabled {
            cursor: not-allowed;
            opacity: 0.85;
        }

        .module-card.disabled:hover {
            transform: none;
            border-color: var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .module-card.disabled .icon-box {
            background-color: #f1f5f9;
            color: #94a3b8;
        }

        .module-card.disabled:hover .icon-box {
            background-color: #f1f5f9;
            color: #94a3b8;
        }

        /* Etiqueta de Próximamente */
        .badge-coming-soon {
            position: absolute;
            top: 12px;
            right: 12px;
            background-color: #f1f5f9;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="logo-title-wrapper">
            <img src="client/logo.png" alt="Logo" class="header-logo">
            <div>
                <h1>Panel de Administración Global</h1>
                <p>Selecciona el módulo del ecosistema al que deseas ingresar</p>
            </div>
        </div>
    </header>

    <main class="modules-grid">
        
        <a href="client/index.php" class="module-card">
            <div class="icon-box">
                <i class="ri-team-line"></i>
            </div>
            <h2>Clientes</h2>
            <p>Control, registro y fichas corporativas de clientes de la firma.</p>
        </a>

        
        <a href="ac/index.php" class="module-card">
            
            <div class="icon-box">
                <i class="ri-shield-check-line"></i>
            </div>
            <h2>Aceptación y Continuidad</h2>
            <p>Evaluación de riesgos, políticas internas y aprobación regulatoria.</p>
        </a>
        

        <div class="module-card disabled">
            <span class="badge-coming-soon">Próximamente</span>
            <div class="icon-box">
                <i class="ri-file-list-3-line"></i>
            </div>
            <h2>Términos y Condiciones</h2>
            <p>Gestión de contratos, cláusulas legales y acuerdos de nivel de servicio.</p>
        </div>

        <div class="module-card disabled">
            <span class="badge-coming-soon">Próximamente</span>
            <div class="icon-box">
                <i class="ri-folders-line"></i>
            </div>
            <h2>Proyecto</h2>
            <p>Planificación de flujos de trabajo, entregables y asignación de tareas.</p>
        </div>

    </main>
</div>

</body>
</html>