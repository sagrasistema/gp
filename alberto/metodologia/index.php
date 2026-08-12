<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// v/metodologia/index.php
include '../main/h.php'; 
include '../main/config.php'; 
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'metodologia'; 

include '../main/layout_header.php';
?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-settings-4-line"></i> Configuración de Metodología de Auditoría
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

        <a href="nuevo.php" class="btn btn-primary" data-tooltip="Crear Servicio">
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
                    <th style="width: 10%;">ID</th>
                    <th style="width: 60%;">Servicio de Auditoría</th>
                    <th style="width: 15%; text-align: center;">Categorías</th>
                    <th style="width: 15%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    // Consulta con relacion correcta a serviceId e Id
                    $query = "SELECT s.Id, s.serviceName,
                              COUNT(c.id) AS total_categorias
                              FROM audit_servicios s
                              LEFT JOIN audit_categorias c ON c.serviceId = s.Id
                              GROUP BY s.Id, s.serviceName
                              ORDER BY s.Id ASC";
                              
                    $stmt = $pdo->query($query);
                    $servicios = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
                    if (!empty($servicios)) {
                        foreach ($servicios as $serv) {
                            $servicioId     = (int) $serv->Id;
                            $nombreServicio = htmlspecialchars($serv->serviceName ?? '', ENT_QUOTES, 'UTF-8');
                            $totalCats      = (int) ($serv->total_categorias ?? 0);

                            echo "<tr>";
                            echo "<td><strong>#{$servicioId}</strong></td>";
                            echo "<td><strong>{$nombreServicio}</strong></td>";
                            echo "<td style='text-align: center;'><span class='badge' style='background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-weight: 600;'>{$totalCats} Registradas</span></td>";
                            echo "<td style='text-align: center; white-space: nowrap;'>";
                            
                            // Redirección con el parámetro serviceId
                            echo "<a href='servicio_etapas.php?serviceId={$servicioId}' class='btn btn-secondary' style='padding: 0.4rem 0.8rem; background: #08855b; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: 600;' data-tooltip='Configurar Metodología'>";
                            echo "<i class='ri-pencil-fill'></i> Configurar";
                            echo "</a>";

                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align: center; color: #64748b; padding: 3rem;'>No se han encontrado servicios de auditoría configurados.</td></tr>";
                    }
                } catch (PDOException $e) {
                    error_log("Error al listar servicios de metodología: " . $e->getMessage());
                    echo "<tr><td colspan='4' style='text-align: center; color: red; padding: 2rem;'>Error al cargar los servicios metodológicos desde el servidor.</td></tr>";
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