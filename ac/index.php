<?php 
// v/ac/index.php
$pageTitle = "Aceptación y Continuidad";
include '../main/h.php'; 
include '../main/config.php'; 
?>

<header class="main-navbar">
    <div class="navbar-left">
        <button id="toggle-sidebar-btn" class="btn-toggle"><i class="ri-menu-line"></i></button>
        
        <div class="navbar-logo" onclick="window.location.href='../index.php'">
            <span class="logo-box">S</span>
            <div class="logo-text">
                <strong>SAGRA</strong>
                <span>GESTIÓN</span>
            </div>
        </div>
    </div>
    <div class="navbar-right">
        <i class="ri-user-line user-avatar"></i>
        <span class="user-name-text">Juan Manuel Godoy</span>
    </div>
</header>

<div class="app-body">
    
    <aside class="main-sidebar">
        <nav class="sidebar-menu">
            <a href="../index.php" class="menu-item">
                <i class="ri-home-4-line"></i> <span>Inicio</span>
            </a>
            <a href="index.php" class="menu-item active">
                <i class="ri-shield-check-line"></i> <span>Aceptación</span>
            </a>
            <a href="#" class="menu-item style-disabled">
                <i class="ri-customer-service-2-line"></i> <span>Soporte</span>
            </a>
        </nav>
    </aside>

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
                            $evaluaciones =$stmt->fetchAll(PDO::FETCH_OBJ);
                            
                            if (!empty($evaluaciones)) {
                                foreach ($evaluaciones as$ac) {
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

<style>
    /* Reset de márgenes del body para que todo pegue a los bordes */
    body {
        margin: 0;
        padding: 0 !important;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* 1. NAVBAR SUPERIOR COMPLETO */
    .main-navbar {
        height: 60px;
        width: 100%;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.5rem;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
    }
    .navbar-left { display: flex; align-items: center; gap: 1.5rem; }
    .btn-toggle { background: none; border: none; font-size: 1.4rem; color: #475569; cursor: pointer; display: flex; align-items: center; }
    
    /* Logo corporativo en Navbar */
    .navbar-logo { display