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
            background-image: url('client/mosaico.svg'); 
            background-repeat: repeat;
            background-size: 60px 60px;
            color: var(--text-main); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 1. NAVBAR SUPERIOR COMPLETO (Color corporativo oscuro) */
        .main-navbar {
            height: 60px;
            width: 100%;
            background: #2c3e50;
            border-bottom: 1px solid #1e2b37;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        
        .navbar-left { display: flex; align-items: center; gap: 1.25rem; }
        .navbar-logo-container { display: flex; align-items: center; height: 40px; }
        
        /* Logo intacto en sus colores originales */
        .main-system-logo { 
            height: 36px; 
            width: auto; 
            object-fit: contain; 
            cursor: pointer;
        }

        .navbar-title { 
            font-weight: 600; 
            color: #94a3b8; 
            font-size: 0.95rem;
            border-left: 1px solid #34495e;
            padding-left: 1.25rem;
        }
        
        .navbar-right { display: flex; align-items: center; gap: 1rem; color: #edf2f7; font-size: 0.9rem; }
        .user-name-text { font-weight: 500; }
        .user-avatar { background: #34495e; color: #fff; padding: 0.45rem; border-radius: 50%; font-size: 1.1rem; }

        .btn-toggle { 
            background: none; 
            border: none; 
            font-size: 1.4rem; 
            color: #cbd5e1; 
            cursor: pointer; 
            display: flex; 
            align-items: center;
            padding: 0.2rem;
            transition: color 0.2s;
        }
        .btn-toggle:hover { color: #fff; }

        /* CONTENEDOR DE LA APLICACIÓN BAJO EL NAVBAR */
        .app-body {
            margin-top: 60px;
            display: flex;
            min-height: calc(100vh - 60px);
            width: 100%;
        }

        /* 2. SIDEBAR COMPACTO REINCORPORADO (Ancho: 90px) */
        .main-sidebar {
            width: 90px;
            background: #2c3e50;
            color: #fff;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            z-index: 900;
            border-right: 1px solid #1e2b37;
        }
        .sidebar-menu { padding: 0.75rem 0; display: flex; flex-direction: column; gap: 0.25rem; }
        
        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 0.4rem;
            padding: 1rem 0.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s ease, background-color 0.2s ease;
            border-left: 3px solid transparent;
        }
        .menu-item i { font-size: 1.4rem; }
        .menu-item span { font-size: 0.75rem; font-weight: 500; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 100%; }
        
        /* Hover con fondo azul claro corporativo y letras/icono blancos */
        .menu-item:hover { 
            background: #3498db; 
            color: #fff; 
        }
        
        /* En el index principal, "Inicio" es la pestaña activa */
        .menu-item.active { background: #1a252f; color: #fff; font-weight: 600; border-left-color: #3498db; }
        .menu-item.style-disabled { opacity: 0.4; pointer-events: none; }

        /* 3. ÁREA CENTRAL DE TRABAJO (Desplazada 90px a la derecha por la barra lateral) */
        .main-content {
            flex-grow: 1;
            margin-left: 90px;
            padding: 3rem 2rem;
            width: calc(100% - 90px);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container { 
            width: 100%; 
            max-width: 1100px; 
        }

        /* Encabezado */
        .view-header {
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .view-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .view-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 0.25rem;
        }

        /* Grid de Módulos */
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

        /* Módulos Deshabilitados */
        .module-card.disabled {
            cursor: not-allowed;
            opacity: 0.75;
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

        /* Etiqueta Próximamente */
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

        /* COMPORTAMIENTO RESPONSIVO */
        @media (max-width: 768px) {
            .main-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .app-body.sidebar-open .main-sidebar {
                transform: translateX(0);
            }
            .main-content { 
                margin-left: 0; 
                width: 100%; 
                padding: 2rem 1rem; 
            }
            .navbar-title, .user-name-text { display: none; }
            .view-header { text-align: center; margin-bottom: 2rem; }
        }
    </style>
</head>
<body>

<header class="main-navbar">
    <div class="navbar-left">
        <div class="navbar-logo-container">
            <img src="client/logo.png" alt="SAGRA" class="main-system-logo">
        </div>
        <span class="navbar-title">SAGRAGP VERSION 2.0</span>
    </div>
    
    <div class="navbar-right">
        <span class="user-name-text">Juan Manuel Godoy</span>
        <i class="ri-user-line user-avatar"></i>
        <button id="toggle-sidebar-btn" class="btn-toggle"><i class="ri-menu-line"></i></button>
    </div>
</header>

<div class="app-body">
    
    <aside class="main-sidebar">
        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item active">
                <i class="ri-home-4-line"></i>
                <span>Inicio</span>
            </a>
            <a href="ac/index.php" class="menu-item">
                <i class="ri-shield-check-line"></i>
                <span>Aceptación</span>
            </a>
            <a href="#" class="menu-item style-disabled">
                <i class="ri-customer-service-2-line"></i>
                <span>Soporte IT</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="container">
            
            <div class="view-header">
                <h1>Panel de Administración Global</h1>
                <p>Selecciona el módulo del ecosistema al que deseas ingresar</p>
            </div>

            <div class="modules-grid">
                
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

            </div>
        </div>
    </main>
</div>

<script>
    document.getElementById('toggle-sidebar-btn').addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.app-body').classList.toggle('sidebar-open');
    });

    document.addEventListener('click', function(e) {
        const body = document.querySelector('.app-body');
        const sidebar = document.querySelector('.main-sidebar');
        if (body.classList.contains('sidebar-open') && !sidebar.contains(e.target)) {
            body.classList.remove('sidebar-open');
        }
    });
</script>

</body>
</html>