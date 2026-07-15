<?php 
// v/ac/index.php
$pageTitle = "Aceptación y Continuidad";
include '../main/h.php'; // Tu cabecera PHP normal de base de datos / sesiones
include '../main/config.php'; 
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
// Configuración para que el componente apunte a las carpetas correctas desde v/ac/
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = 'index.php';
$currentTab     = 'aceptacion'; // Marca "Aceptación" activo en el sidebar

include '../main/layout_header.php';
?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-shield-check-line"></i> Aceptación y Continuidad
        </h1>

    </div>
    <div class="table-actions-container">
        <a href="#" class="btn-control-disabled">
            <i class="ri-arrow-go-back-line"></i> Atrás
        </a>

        <a href="#" class="btn-control-disabled">
            <i class="ri-screenshot-2-line"></i> Captura
        </a>

        <a href="#" class="btn-control-disabled">
            <i class="ri-book-open-line"></i> Instrucciones
        </a>

        <a href="nuevo.php" class="btn-control-blue">
            <i class="ri-add-box-line"></i> Crear Registro
        </a>

        <a href="../index.php" class="btn-control-blue">
            <i class="ri-close-circle-line"></i> Cancelar (Atrás)
        </a>
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

<script>
function exportTable(type) {
    console.log("Exportando tabla como: " + type);
    // Aquí puedes agregar la lógica de DataTables, jsPDF o tu sistema personalizado de exportación.
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>