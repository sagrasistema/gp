<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// v/proyectos/index.php
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
                    
                    <th style="width: 35%;">Cliente / Empresa</th>
                    <th style="width: 25%;">Proyecto / Alcance</th>
                    <th style="width: 15%;">Fecha Inicio</th>
                    <th style="width: 15%; text-align: center;">Acciones</th>
                    <th style="width: 10%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    // Consulta filtrando por ver_id para preservar el statusId comercial/operativo (ej. proyectos cerrados en 2)
                    $query = "SELECT p.id AS proyectoId, c.name AS clientName, p.nombre AS proyectoNombre, p.fecha_inicio, p.statusId, p.ver_id 
                              FROM proyectos p
                              INNER JOIN clientes c ON p.cliente_id = c.id
                              WHERE p.ver_id != 2
                              ORDER BY p.id DESC";
                              
                    $stmt = $pdo->query($query);
                    $proyectos = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
                    if (!empty($proyectos)) {
                        foreach ($proyectos as $proj) {
                            $clientName = htmlspecialchars($proj->clientName, ENT_QUOTES, 'UTF-8');
                            $projName   = htmlspecialchars($proj->proyectoNombre, ENT_QUOTES, 'UTF-8');
                            $fecha      = date('d/m/Y', strtotime($proj->fecha_inicio));

                            echo "<tr>";
                            

                            echo "<td><strong>{$clientName}</strong></td>";
                            echo "<td>{$projName}</td>";
                            echo "<td>{$fecha}</td>";
                            echo "<td style='text-align: center; white-space: nowrap;'>";
                            
                            // Botón de Pruebas / Gestionar (responder.php)
                            echo "<a href='responder.php?proyectoId={$proj->proyectoId}' class='btn btn-secondary' style='padding: 0.4rem 0.8rem; background: #08855b; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: 600; data-tooltip='Gestionar Pruebas'>";
                            echo "<i class='ri-pencil-fill'></i> Consultar";
                            echo "</a>";

                            // Botón de Asignación de Equipo (proyecto_equipo.php)
                            //echo "<a href='proyecto_equipo.php?id={$proj->proyectoId}' class='btn btn-secondary' style='padding: 0.4rem 0.6rem; font-size: 0.8rem; background-color: rgba(0, 188, 212, 0.1); color: var(--accent-cian, #00bcd4); border: 1px solid var(--accent-cian, #00bcd4);' data-tooltip='Asignar Equipo'>";
                            //echo "<i class='ri-team-line'></i>";
                            //echo "</a>";

                            echo "</td>";
                             
                            $statusId  = (int)($proj->statusId ?? 1);
                            $isClosed  = ($statusId === 2);

                            $iconClass = $isClosed ? 'ri-lock-fill' : 'ri-lock-unlock-line';
                            $iconColor = $isClosed ? '#0f172a' : '#16a34a'; // Negro para cerrado, Verde para en proceso
                            $tooltip   = $isClosed ? 'Cerrado' : 'En proceso';
                        
                          // Indicador de Estado / Tooltip con Ícono
                            echo '<td style="text-align: center; vertical-align: middle;">';
                            echo '    <span title="' . htmlspecialchars($tooltip ?? '', ENT_QUOTES, 'UTF-8') . '" style="cursor: help; display: inline-flex; align-items: center;">';
                            echo '        <i class="' . htmlspecialchars($iconClass ?? '', ENT_QUOTES, 'UTF-8') . '" style="font-size: 1.25rem; color: ' . htmlspecialchars($iconColor ?? '', ENT_QUOTES, 'UTF-8') . ';"></i>';
                            echo '    </span>';
                            echo '</td>';
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; color: #64748b; padding: 3rem;'>No se han encontrado proyectos de auditoría registrados.</td></tr>";
                    }
                } catch (PDOException $e) {
                    error_log("Error al listar proyectos en index.php: " . $e->getMessage());
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