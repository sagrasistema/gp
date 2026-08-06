<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../main/config.php';

/** @var PDO $pdo */

$terminoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$terminoId || $terminoId <= 0) {
    http_response_code(400);
    die("Error: Registro de Términos y Condiciones no especificado.");
}

$currentUserId = (int)$_SESSION['user_id'];

// PROCESAR CAMBIO DE ESTADO (Solo usuarios 1 y 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statusId'])) {
    if ($currentUserId === 1 || $currentUserId === 2) {
        $newStatusId = (int)$_POST['statusId'];
        
        if (in_array($newStatusId, [1, 2], true)) {
            try {
                $stmtUpdateStatus = $pdo->prepare("
                    UPDATE terminos_condiciones 
                    SET statusId = :statusId, updated_at = NOW() 
                    WHERE id = :id
                ");
                $stmtUpdateStatus->execute([
                    ':statusId' => $newStatusId,
                    ':id'       => $terminoId
                ]);

                header("Location: responder-terminos.php?id={$terminoId}&success=updated");
                exit();
            } catch (PDOException $e) {
                error_log("Error al actualizar el estado de los términos: " . $e->getMessage());
                $errorMessage = "Error interno al intentar actualizar el estado.";
            }
        }
    } else {
        http_response_code(403);
        die("Acceso denegado: No tienes permisos para modificar el estado de este registro.");
    }
}

try {
    $stmtHeader = $pdo->prepare("
        SELECT tc.*, c.name AS clientName
        FROM terminos_condiciones tc
        INNER JOIN clientes c ON tc.cliente_id = c.id
        WHERE tc.id = :id
    ");
    $stmtHeader->execute([':id' => $terminoId]);
    $headerData = $stmtHeader->fetch(PDO::FETCH_OBJ);

    if (!$headerData) {
        http_response_code(404);
        die("Error: El registro de términos y condiciones no existe.");
    }

    $stmtItems = $pdo->prepare("
        SELECT * FROM terminos_condiciones_items 
        WHERE termino_id = :termino_id 
        ORDER BY orden ASC
    ");
    $stmtItems->execute([':termino_id' => $terminoId]);
    $itemsList = $stmtItems->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al cargar vista de respuesta: " . $e->getMessage());
    die("Error crítico de base de datos.");
}

$currentStatusId = (int)($headerData->statusId ?? 1);
$isClosed = ($currentStatusId === 2);
$pageTitle = "Responder Términos y Condiciones";
include '../main/h.php';

$mapaFormularios = [
    'carta_contratacion'  => 'formulario-carta-contratacion.php',
    'frecuencia'          => 'formulario-frecuencia.php',
    'roles_proyecto'      => 'formulario-roles-proyecto.php',
    'esquema_facturacion' => 'formulario-esquema-facturacion.php',
];
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
            <i class="ri-file-list-3-line" style="color: #2563eb;"></i> Responder Términos y Condiciones
        </h1>
    </div>

    <div class="table-actions-container">
        <a href="../terminos/index.php" class="btn btn-primary" data-tooltip="Volver al Listado">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
        <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> El estado del registro ha sido actualizado exitosamente.
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- CUADRO DE CIERRE Y ESTADO (DISEÑO SOLICITADO) -->
    <?php if ($currentUserId === 1 || $currentUserId === 2): ?>
        <form method="POST" action="responder-terminos.php?id=<?= $terminoId ?>">
            <div style="margin-top: 2rem; margin-bottom: 2rem; border: 1px solid <?= $isClosed ? '#fecaca' : '#cbd5e1' ?>; border-radius: 8px; background: <?= $isClosed ? '#fef2f2' : '#ffffff' ?>; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid <?= $isClosed ? '#fee2e2' : '#f1f5f9' ?>; padding-bottom: 0.75rem;">
                    <h3 style="margin: 0; font-size: 1.05rem; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; font-weight: 700;">
                        <i class="ri-shield-keyhole-line" style="color: <?= $isClosed ? '#dc2626' : '#0284c7' ?>; font-size: 1.25rem;"></i> Cierre y Estado del Registro
                    </h3>
                    
                    <?php if ($isClosed): ?>
                        <span style="background: #dc2626; color: #ffffff; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                            <i class="ri-lock-fill"></i> REGISTRO CERRADO
                        </span>
                    <?php else: ?>
                        <span style="background: #e0f2fe; color: #0369a1; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                            <i class="ri-time-line"></i> EN PROCESO
                        </span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <p style="margin: 0; font-size: 0.875rem; color: #475569; max-width: 600px;">
                        <?php if ($isClosed): ?>
                            Este registro ha sido finalizado y cerrado. Los campos se encuentran bloqueados para evitar alteraciones en la información.
                        <?php else: ?>
                            Selecciona <strong>"Cerrado"</strong> cuando hayas finalizado para bloquear la modificación de las respuestas.
                        <?php endif; ?>
                    </p>

                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <label for="statusId" style="font-weight: 700; color: #334155; font-size: 0.875rem; white-space: nowrap;">
                            Estado:
                        </label>
                        <select name="statusId" id="statusId" onchange="this.form.submit()" style="padding: 0.5rem 0.85rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background-color: #ffffff; color: #0f172a; cursor: pointer;">
                            <option value="1" <?= $currentStatusId === 1 ? 'selected' : '' ?>>En Proceso</option>
                            <option value="2" <?= $currentStatusId === 2 ? 'selected' : '' ?>>Cerrado</option>
                        </select>
                    </div>
                </div>

            </div>
        </form>
    <?php endif; ?>

    <!-- LISTADO DE LAS 4 ACTIVIDADES -->
    <div class="terminos-container" style="display: flex; flex-direction: column; gap: 0.6rem;">
        <?php foreach ($itemsList as $item): ?>
            <div class="termino-row" style="display: flex; align-items: center; background: #94a3b8; color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                
                <div style="padding: 0.85rem 1.25rem; background: rgba(0, 0, 0, 0.12); font-weight: 700; font-size: 1rem; min-width: 45px; text-align: center;">
                    <?= (int)$item->orden ?>
                </div>

                <div style="flex: 1; padding: 0.85rem 1.25rem; font-weight: 600; font-size: 0.95rem;">
                    <?= htmlspecialchars($item->item_nombre, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div style="padding: 0 1rem;">
                    <?php if ($item->estado === 'completado'): ?>
                        <span style="background: #16a34a; color: #fff; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 600;">Completado</span>
                    <?php else: ?>
                        <span style="background: rgba(255,255,255,0.2); color: #f8fafc; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 500;">Pendiente</span>
                    <?php endif; ?>
                </div>

                <?php 
                    $scriptDestino = $mapaFormularios[$item->item_key] ?? 'formulario-termino.php'; 
                ?>
                <a href="<?= htmlspecialchars($scriptDestino, ENT_QUOTES, 'UTF-8') ?>?terminoId=<?= $terminoId ?>&item=<?= htmlspecialchars($item->item_key, ENT_QUOTES, 'UTF-8') ?>" 
                title="<?= $isClosed ? 'Ver Registro' : 'Editar' ?>"
                style="display: flex; align-items: center; justify-content: center; padding: 0.85rem 1.25rem; background: rgba(0, 0, 0, 0.08); color: #ffffff; text-decoration: none;">
                    <?php if ($isClosed): ?>
                        <i class="ri-eye-fill" style="font-size: 1.1rem;"></i>
                    <?php else: ?>
                        <i class="ri-pencil-fill" style="font-size: 1.1rem;"></i>
                    <?php endif; ?>
                </a>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>