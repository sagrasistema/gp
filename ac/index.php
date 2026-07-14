<?php 
// v/ac/index.php
$pageTitle = "Aceptación y Continuidad";
include '../main/h.php'; 
include '../main/config.php'; 
?>

<!-- 1. NAVBAR SUPERIOR COMPLETO (Identidad unificada oscura) -->
<header class="main-navbar">
    <div class="navbar-left">
        <!-- Logo real del sistema en sus colores originales -->
        <div class="navbar-logo-container">
            <img src="../main/logo.png" alt="SAGRA" class="main-system-logo" onclick="window.location.href='../index.php'">
        </div>
        
        <span class="navbar-title">Módulo de Auditoría</span>
    </div>
    
    <!-- Lado derecho organizado: Nombre -> Avatar -> Icono Menú -->
    <div class="navbar-right">
        <span class="user-name-text">Juan Manuel Godoy</span>
        <i class="ri-user-line user-avatar"></i>
        <!-- El botón del menú se ubica ahora del lado derecho del usuario -->
        <button id="toggle-sidebar-btn" class="btn-toggle"><i class="ri-menu-line"></i></button>
    </div>
</header>

<!-- Contenedor del cuerpo bajo el Navbar -->
<div class="app-body">
    
    <!-- 2. SIDEBAR COMPACTO VERTICAL (Ancho: 90px) -->
    <aside class="main-sidebar">
        <nav class="sidebar-menu">
            <a href="../index.php" class="menu-item">
                <i class="ri-home-4-line"></i>
                <span>Inicio</span>
            </a>
            <a href="index.php" class="menu-item active">
                <i class="ri-shield-check-line"></i>
                <span>Aceptación</span>
            </a>
            <a href="#" class="menu-item style-disabled">
                <i class="ri-customer-service-2-line"></i>
                <span>Soporte IT</span>
            </a>
        </nav>
    </aside>

    <!-- 3. ÁREA DE TRABAJO PRINCIPAL MAXIMIZADA -->
    <main class="main-content">
        <div class="view-container">
            <div class="view-header">
                <h1 class="page-main-title">
                    <i class="ri-shield-check-line"></i> Aceptación y Continuidad
                </h1>
                <div class="header-actions">
                    <a href="../index.php" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Menú</a>
                    <a href="nuevo.php" class="btn btn-primary"><i class="ri-add-line"></i> Nueva Evaluación</a>
                </div>
            </div>

            <!-- TABLA ADMINISTRATIVA SEGURA CON PDO -->
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">ID AC</th>
                            <th style="width: 40%;">Cliente / Empresa</th>
                            <th style="width: 25%;">Tipo de Evaluación</th>
                            <th style="width: 15%;">Fecha Creación</th>
                            <th style="width: 10%; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $query = "SELECT a.acId, c.name AS clientName, t.typeName, a.created_at 
                                      FROM ac a
                                      INNER JOIN clientes c ON a.clientId = c.id
                                      INNER JOIN ac_types t ON a.typeId = t.typeId
                                      ORDER BY a.acId DESC";
                            $stmt = $pdo->query($query);
                            $evaluaciones = $stmt->fetchAll(PDO::FETCH_OBJ);
                            
                            if (!empty($evaluaciones)) {
                                foreach ($evaluaciones as $ac) {
                                    $clientName = htmlspecialchars($ac->clientName, ENT_QUOTES, 'UTF-8');
                                    $typeName   = htmlspecialchars($ac->typeName, ENT_QUOTES, 'UTF-8');
                                    $fecha      = date('d/m/Y', strtotime($ac->created_at));

                                    echo "<tr>";
                                    echo "<td style='font-weight: 600; color: #64748b;'>#{$ac->acId}</td>";
                                    echo "<td><strong>{$clientName}</strong></td>";
                                    echo "<td>{$typeName}</td>";
                                    echo "<td>{$fecha}</td>";
                                    echo "<td style='text-align: center;'>
                                            <a href='responder.php?acId={$ac->acId}' class='btn btn-secondary' style='padding: 0.4rem 0.8rem; font-size: 0.85rem;'>
                                                <i class='ri-file-list-3-line'></i> Responder
                                            </a>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' style='text-align: center; color: #64748b; padding: 3rem;'>No se han encontrado evaluaciones.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='5' style='text-align: center; color: red; padding: 2rem;'>Error al cargar los datos del servidor.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- CAPA DE ESTILOS CSS CORREGIDA -->
<style>
    body {
        margin: 0 !important;
        padding: 0 !important;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* 1. NAVBAR SUPERIOR UNIFICADO */
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
    
    /* Contenedor e Imagen del logo en su estado original sin filtros */
    .navbar-logo-container { display: flex; align-items: center; height: 40px; }
    .main-system-logo { 
        height: 36px; 
        width: auto; 
        object-fit: contain; 
        cursor: pointer;
        /* Removido el filtro invertido para respetar los colores reales */
    }

    .navbar-title { 
        font-weight: 600; 
        color: #94a3b8; 
        font-size: 0.95rem;
        border-left: 1px solid #34495e;
        padding-left: 1.25rem;
    }
    
    /* Lado derecho estructurado */
    .navbar-right { display: flex; align-items: center; gap: 1rem; color: #edf2f7; font-size: 0.9rem; }
    .user-name-text { font-weight: 500; }
    .user-avatar { background: #34495e; color: #fff; padding: 0.45rem; border-radius: 50%; font-size: 1.1rem; }
    
    /* Estilo del botón de menú a la derecha */
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

    /* CUERPO DE LA APP */
    .app-body {
        display: flex;
        margin-top: 60px;
        min-height: calc(100vh - 60px);
        width: 100%;
    }

    /* 2. SIDEBAR COMPACTO (90px) */
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
    
    /* Orientación vertical (Icono arriba, texto abajo) */
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
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    .menu-item i { font-size: 1.4rem; }
    .menu-item span { font-size: 0.75rem; font-weight: 500; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 100%; }
    
    .menu-item:hover { background: #34495e; color: #fff; }
    .menu-item.active { background: #1a252f; color: #fff; font-weight: 600; border-left-color: #3498db; }
    .menu-item.style-disabled { opacity: 0.4; }

    /* 3. CONTENEDOR DE TRABAJO AMPLIO */
    .main-content {
        flex-grow: 1;
        margin-left: 90px;
        padding: 2rem;
        width: calc(100% - 90px);
        min-height: 100%;
    }
    .view-container { max-width: 1400px; margin: 0 auto; width: 100%; }
    .view-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; }
    .page-main-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; }

    /* COMPORTAMIENTO PARA DISPOSITIVOS MÓVILES */
    @media (max-width: 768px) {
        .main-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .app-body.sidebar-open .main-sidebar {
            transform: translateX(0);
        }
        .main-content { margin-left: 0; width: 100%; padding: 1rem; }
        .navbar-title, .user-name-text { display: none; }
        .view-header { flex-direction: column; align-items: flex-start; }
        .header-actions { width: 100%; display: flex; gap: 0.5rem; }
        .header-actions .btn { flex-grow: 1; justify-content: center; }
    }
</style>

<!-- LÓGICA INTERACTIVA DEL MENU -->
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

<?php include '../main/footer.php'; ?>