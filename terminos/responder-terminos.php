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

$currentUserId = (int)$sessionUserId = $_SESSION['user_id'];

// PROCESAR CAMBIO DE ESTADO A CERRADO (Solo usuarios 1 y 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_cerrar_termino'])) {
    if ($currentUserId === 1 || $currentUserId === 2) {
        try {
            $stmtCerrar = $pdo->prepare("
                UPDATE terminos_condiciones 
                SET statusId = 2, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmtCerrar->execute([':id' => $terminoId]);

            header("Location: responder-terminos.php?id={$terminoId}&success=cerrado");
            exit();
        } catch (PDOException $e) {
            error_log("Error al cerrar términos y condiciones: " . $e->getMessage());
            $errorMessage = "Error interno al intentar cerrar el registro.";
        }
    } else {
        http_response_code(403);
        die("Acceso denegado: No tienes permisos para cerrar este registro.");
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

$isCerrado = ((int)($headerData->statusId ?? 1) === 2);
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

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cerrado'): ?>
        <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-checkbox-circle-fill">Formatter</i> El registro ha sido cerrado exitosamente. Ningún usuario podrá editarlo.
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- PANEL DE ESTADO Y ZONA DE CIERRE (VISIBLE SOLO PARA ID 1 Y 2) -->
    <?php if ($currentUserId === 1 || $currentUserId === 2): ?>
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div>
                <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #0f172a;">
                    <i class="ri-lock-password-line" style="color: #2563eb;"></i> Panel de Control Administrativo (Cierre)
                </h3>
                <p style="margin: 0; font-size: 0.85rem; color: #64748b;">
                    Estado actual: <strong><?= $isCerrado ? 'CERRADO (Bloqueado)' : 'ABIERTO (Editable)' ?></strong>
                </p>
            </div>

            <div>
                <?php if (!$isCerrado): ?>
                    <form method="POST" action="responder-terminos.php?id=<?= $terminoId ?>" onsubmit="return confirm('¿Está seguro de cerrar este registro? Una vez cerrado, no se podrá editar ninguna información.');">
                        <input type="hidden" name="action_cerrar_termino" value="1">
                        <button type="submit" class="btn" style="background: #dc2626; color: #fff; padding: 0.6rem 1.2rem; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="ri-lock-fill"></i> Cerrar Términos
                        </button>
                    </form>
                <?php else: ?>
                    <span style="background: #fee2e2; color: #991b1b; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="ri-lock-line"></i> Registro Cerrado Definitivamente
                    </span>
                <?php endif; ?>
            </div>
        </div>
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
                title="<?= $isCerrado ? 'Ver Registro' : 'Editar' ?>"
                style="display: flex; align-items: center; justify-content: center; padding: 0.85rem 1.25rem; background: rgba(0, 0, 0, 0.08); color: #ffffff; text-decoration: none;">
                    <?php if ($isCerrado): ?>
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