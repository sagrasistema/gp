<?php 
// v/ac/index.php
$pageTitle = "Aceptación y Continuidad";
include '../main/h.php'; 
include '../main/config.php'; 
?>

<!-- CONTENEDOR MAESTRO FULL-WIDTH -->
<div class="system-layout">
    
    <!-- BARRA LATERAL IZQUIERDA (Sidebar Completamente Pegado) -->
    <aside class="main-sidebar">
        <div class="sidebar-brand">
            <!-- Título de la app simulando el logo de tu captura original -->
            <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center; color: #fff;">
                <span style="background: #0284c7; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 900; font-size: 1.1rem;">S</span>
                <div style="text-align: left; line-height: 1;">
                    <strong style="font-size: 1.1rem; letter-spacing: 0.05em;">SAGRA</strong>
                    <span style="display: block; font-size: 0.6rem; color: #94a3b8;">GESTIÓN DE PROYECTOS</span>
                </div>
            </div>
        </div>
        <nav class="sidebar-menu">
            <a href="../index.php" class="menu-item">
                <i class="ri-home-4-line"></i> <span>Inicio</span>
            </a>
            <a href="index.php" class="menu-item active">
                <i class="ri-shield-check-line"></i> <span>Aceptación y Cont.</span>
            </a>
            <a href="#" class="menu-item style-disabled">
                <i class="ri-customer-service-2-line"></i> <span>Soporte IT</span>
            </a>
        </nav>
    </aside>

    <!-- CONTENEDOR DERECHO (Navbar Superior + Contenido Variable) -->
    <div class="content-wrapper">
        
        <!-- NAVBAR SUPERIOR (Completamente Pegado al Borde de Arriba) -->
        <header class="main-navbar">
            <div class="navbar-left">
                <button id="toggle-sidebar-btn" class="btn-toggle"><i class="ri-menu-line"></i></button>
                <span class="navbar-title">Módulo de Auditoría</span>
            </div>
            <div class="navbar-right">
                <i class="ri-user-line user-avatar"></i>
                <span class="user-name-text">Juan Manuel Godoy</span>
            </div>
        </header>

        <!-- CUERPO DEL CONTENIDO CON PADDING INTERNO -->
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

                <!-- TABLA ADMINISTRATIVA RESPONSIVA -->
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
                                        echo "<!-- Se eliminó strong duplicado para conservar estilos limpios --><td><strong>{$clientName}</strong></td>";
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
</div>

<!-- SISTEMA DE DISEÑO ESTRUCTURAL Y RESPONSIVO -->
<style>
    .system-layout {
        display: flex;
        min-height: 100vh;
        width: 100vw;
        overflow-x: hidden;
    }

    /* Estilos del Sidebar Edge-to-Edge */
    .main-sidebar {
        width: 260px;
        background: #2c3e50;
        color: #fff;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        z-index: 100;
        transition: transform 0.3s ease;
    }
    .sidebar-brand {
        padding: 1.25rem 1.5rem;
        background: #1e2b37;
        border-bottom: 1px solid #34495e;
        height: 60px;
        display: flex;
        align-items: center;
    }
    .sidebar-menu {
        padding: 0.5rem 0;
        display: flex;
        flex-direction: column;
    }
    .menu-item {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.85rem 1.5rem;
        color: #edf2f7;
        text-decoration: none;
        font-size: 0.95rem;
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
    }
    .menu-item:hover {
        background: #34495e;
        color: #fff;
    }
    .menu-item.active {
        background: #1a252f;
        color: #fff;
        font-weight: 600;
        border-left-color: #3498db;
    }
    .menu-item.style-disabled {
        opacity: 0.5;
    }

    /* Estilos del Content Wrapper y Navbar Edge-to-Edge */
    .content-wrapper {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .main-navbar {
        height: 60px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        position: sticky;
        top: 0;
        z-index: 90;
    }
    .navbar-left { display: flex; align-items: center; gap: 1.25rem; }
    .navbar-right { display: flex; align-items: center; gap: 0.75rem; color: #475569; font-size: 0.95rem; }
    .btn-toggle { background: none; border: none; font-size: 1.4rem; color: #475569; cursor: pointer; display: flex; align-items: center; }
    .navbar-title { font-weight: 600; color: #1e293b; font-size: 1.05rem; }
    .user-avatar { background: #f1f5f9; padding: 0.4rem; border-radius: 50%; font-size: 1.2rem; }

    /* Área de Trabajo Interna */
    .main-content {
        padding: 2rem;
        flex-grow: 1;
        width: 100%;
    }
    .view-container {
        max-width: 1300px;
        margin: 0 auto;
        width: 100%;
    }
    .view-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        gap: 1rem;
    }
    .page-main-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    /* MEDIA QUERIES - RESPONSIVE COMPLETO PARA CELULARES */
    @media (max-width: 992px) {
        .main-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            transform: translateX(-100%); /* Oculto por defecto a la izquierda */
        }
        .system-layout.sidebar-open .main-sidebar {
            transform: translateX(0); /* Desliza hacia la derecha al abrir */
        }
        .main-navbar { padding: 0 1rem; }
        .main-content { padding: 1rem; }
    }

    @media (max-width: 768px) {
        .user-name-text { display: none; } /* Remueve texto largo en mobile */
        .view-header { flex-direction: column; align-items: flex-start; }
        .header-actions { width: 100%; display: flex; justify-content: space-between; }
        .header-actions .btn { flex-grow: 1; justify-content: center; }
    }
</style>

<!-- CONTROL INTERACTIVO DEL MENU MOBILE -->
<script>
    document.getElementById('toggle-sidebar-btn').addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.system-layout').classList.toggle('sidebar-open');
    });

    // Cierra el menú al hacer clic fuera en dispositivos móviles
    document.addEventListener('click', function(e) {
        const layout = document.querySelector('.system-layout');
        const sidebar = document.querySelector('.main-sidebar');
        if (layout.classList.contains('sidebar-open') && !sidebar.contains(e.target)) {
            layout.classList.remove('sidebar-open');
        }
    });
</script>

<?php include '../main/footer.php'; ?>