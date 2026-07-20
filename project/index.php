<?php
// v/proyectos/index.php
$pageTitle = "Gestión de Proyectos de Auditoría";
include '../main/h.php'; 
include '../main/config.php'; 
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'proyectos'; 

include '../main/layout_header.php';
?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-folders-line"></i> Control de Proyectos de Auditoría
        </h1>
    </div>
    <div class="table-actions-container">
        <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
            <i class="ri-arrow-go-back-line"></i> 
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
            <i class="ri-screenshot-2-line"></i>
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
            <i class="ri-book-open-line"></i> 
        </a>

        <a href="nuevo.php" class="btn btn-primary" data-tooltip="Crear Registro">
            <i class="ri-add-line"></i>
        </a>

        <a href="../index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>


    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 10%;">ID Proj</th>
                    <th style="width: 40%;">Cliente / Empresa</th>
                    <th style="width: 25%;">Proyecto / Alcance</th>
                    <th style="width: 15%;">Fecha Inicio</th>
                    <th style="width: 10%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $query = "SELECT p.id AS proyectoId, c.name AS clientName, p.nombre AS proyectoNombre, p.fecha_inicio 
                              FROM proyectos p
                              INNER JOIN clientes c ON p.cliente_id = c.id
                              ORDER BY p.id DESC";
                    $stmt = $pdo->query($query);
                    $proyectos = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
                    if (!empty($proyectos)) {
                        foreach ($proyectos as $proj) {
                            $clientName = htmlspecialchars($proj->clientName, ENT_QUOTES, 'UTF-8');
                            $projName   = htmlspecialchars($proj->proyectoNombre, ENT_QUOTES, 'UTF-8');
                            $fecha      = date('d/m/Y', strtotime($proj->fecha_inicio));

                            echo "<tr>";
                            echo "<td style='font-weight: 600; color: #64748b;'>#{$proj->proyectoId}</td>";
                            echo "<td><strong>{$clientName}</strong></td>";
                            echo "<td>{$projName}</td>";
                            echo "<td>{$fecha}</td>";
                            echo "<td style='text-align: center;'>
                                    <a href='responder.php?proyectoId={$proj->proyectoId}' class='btn btn-secondary' style='padding: 0.4rem 0.8rem; font-size: 0.85rem;'>
                                        <i class='ri-folder-open-line'></i> Gestionar
                                    </a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; color: #64748b; padding: 3rem;'>No se han encontrado proyectos de auditoría registrados.</td></tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='5' style='text-align: center; color: red; padding: 2rem;'>Error al cargar los proyectos desde el servidor.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>