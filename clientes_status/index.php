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

include '../main/config.php';

// -------------------------------------------------------------------------
// 1. PROCESAR ACTUALIZACIÓN DE ESTADOS (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_status'])) {
    $clienteId = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $modulo    = filter_input(INPUT_POST, 'modulo', FILTER_SANITIZE_SPECIAL_CHARS); // 'ac', 'terminos', 'proyectos'
    $nuevoStatus = isset($_POST['statusId']) ? 1 : 0;

    if ($clienteId && in_array($modulo, ['ac', 'terminos', 'proyectos'], true)) {
        try {
            $pdo->beginTransaction();

            if ($modulo === 'ac') {
                $stmt = $pdo->prepare("UPDATE ac SET statusId = :status WHERE clientId = :cliente_id");
            } elseif ($modulo === 'terminos') {
                $stmt = $pdo->prepare("UPDATE terminos_condiciones SET statusId = :status WHERE cliente_id = :cliente_id");
            } elseif ($modulo === 'proyectos') {
                $stmt = $pdo->prepare("UPDATE proyectos SET statusId = :status WHERE cliente_id = :cliente_id");
            }

            $stmt->execute([
                ':status'     => $nuevoStatus,
                ':cliente_id' => $clienteId
            ]);

            $pdo->commit();
            header("Location: index.php?success=updated");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al actualizar statusId en módulo {$modulo}: " . $e->getMessage());
            $errorMessage = "Error al actualizar el estado en la base de datos.";
        }
    }
}

// -------------------------------------------------------------------------
// 2. CONSULTAR CLIENTES Y SUS ESTADOS EN LOS MÓDULOS
// -------------------------------------------------------------------------
try {
    $stmtClientes = $pdo->query("SELECT id, name, rif FROM clientes ORDER BY name ASC");
    $clientesList = $stmtClientes->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al consultar clientes: " . $e->getMessage());
    die("Error al cargar los datos del sistema.");
}

$pageTitle = "Control de Estados por Cliente";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php 
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'clientes_status'; 
include '../main/layout_header.php'; 
?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-user-settings-line"></i> Control y Estado por Cliente
        </h1>
        <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
            Gestión de visibilidad y estados (AC, Términos y Condiciones, Proyectos) por cada cliente registrado.
        </p>
    </div>

    <div class="table-actions-container">
        <a href="../index.php" class="btn btn-primary" data-tooltip="Volver al Inicio">
            <i class="ri-arrow-go-back-line"></i> Volver
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Estado actualizado correctamente.
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- CONTENEDOR DE CINTILLOS POR CLIENTE -->
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php if (empty($clientesList)): ?>
            <div style="padding: 3rem; text-align: center; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; color: #94a3b8;">
                No hay clientes registrados en el sistema.
            </div>
        <?php else: ?>
            <?php foreach ($clientesList as $cli): 
                // Consultar AC para este cliente
                $stmtAc = $pdo->prepare("SELECT acId, statusId FROM ac WHERE clientId = ?");
                $stmtAc->execute([$cli->id]);
                $acRecords = $stmtAc->fetchAll(PDO::FETCH_OBJ);

                // Consultar Términos para este cliente
                $stmtTerm = $pdo->prepare("SELECT id, statusId FROM terminos_condiciones WHERE cliente_id = ?");
                $stmtTerm->execute([$cli->id]);
                $termRecords = $stmtTerm->fetchAll(PDO::FETCH_OBJ);

                // Consultar Proyectos para este cliente
                $stmtProj = $pdo->prepare("SELECT id, statusId FROM proyectos WHERE cliente_id = ?");
                $stmtProj->execute([$cli->id]);
                $projRecords = $stmtProj->fetchAll(PDO::FETCH_OBJ);

                // Determinar estados lógicos (si están vacíos, se marca status 0 por defecto)
                $acEmpty   = empty($acRecords);
                $termEmpty = empty($termRecords);
                $projEmpty = empty($projRecords);

                // Tomamos el statusId general o del primer registro encontrado para representación del booleano
                $acStatus   = !$acEmpty ? (int)($acRecords[0]->statusId ?? 1) : 0;
                $termStatus = !$termEmpty ? (int)($termRecords[0]->statusId ?? 1) : 0;
                $projStatus = !$projEmpty ? (int)($projRecords[0]->statusId ?? 1) : 0;
            ?>
                <!-- CINTILLO ACORDIÓN NATIVO -->
                <details style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                    <summary style="padding: 1rem 1.25rem; background: #f8fafc; cursor: pointer; font-weight: 600; color: #1e293b; display: flex; justify-content: space-between; align-items: center; user-select: none;">
                        <span style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="ri-building-line" style="color: #2563eb;"></i> 
                            <?= htmlspecialchars($cli->name, ENT_QUOTES, 'UTF-8') ?>
                            <span style="font-size: 0.75rem; color: #64748b; font-weight: normal; margin-left: 0.5rem;">(RIF: <?= htmlspecialchars($cli->rif ?? 'N/D', ENT_QUOTES, 'UTF-8') ?>)</span>
                        </span>
                        <div style="display: flex; gap: 0.5rem; font-size: 0.75rem;">
                            <span style="padding: 0.2rem 0.5rem; border-radius: 10px; background: <?= !$acEmpty ? '#dcfce7' : '#f1f5f9' ?>; color: <?= !$acEmpty ? '#166534' : '#64748b' ?>;">
                                AC: <?= !$acEmpty ? ($acStatus ? 'Activo' : 'Oculto (0)') : 'Vacío' ?>
                            </span>
                            <span style="padding: 0.2rem 0.5rem; border-radius: 10px; background: <?= !$termEmpty ? '#dcfce7' : '#f1f5f9' ?>; color: <?= !$termEmpty ? '#166534' : '#64748b' ?>;">
                                Términos: <?= !$termEmpty ? ($termStatus ? 'Activo' : 'Oculto (0)') : 'Vacío' ?>
                            </span>
                            <span style="padding: 0.2rem 0.5rem; border-radius: 10px; background: <?= !$projEmpty ? '#dcfce7' : '#f1f5f9' ?>; color: <?= !$projEmpty ? '#166534' : '#64748b' ?>;">
                                Proyectos: <?= !$projEmpty ? ($projStatus ? 'Activo' : 'Oculto (0)') : 'Vacío' ?>
                            </span>
                        </div>
                    </summary>

                    <!-- CONTENIDO DESPLEGABLE -->
                    <div style="padding: 1.25rem; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                        
                        <!-- MÓDULO AC -->
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #0f172a;"><i class="ri-shield-check-line"></i> Aceptación y Continuidad (AC)</h4>
                            <?php if ($acEmpty): ?>
                                <p style="font-size: 0.8rem; color: #94a3b8; margin: 0 0 1rem 0;">No registra evaluaciones AC. (Status configurado en 0 por defecto).</p>
                            <?php else: ?>
                                <form method="POST" action="index.php" style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem;">
                                    <input type="hidden" name="action_update_status" value="1">
                                    <input type="hidden" name="cliente_id" value="<?= $cli->id ?>">
                                    <input type="hidden" name="modulo" value="ac">
                                    <label style="font-size: 0.85rem; color: #334155; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                                        <input type="checkbox" name="statusId" value="1" <?= $acStatus === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
                                        Activo (statusId = 1)
                                    </label>
                                    <span style="font-size: 0.75rem; color: #64748b;"><?= count($acRecords) ?> registro(s)</span>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- MÓDULO TERMINOS -->
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #0f172a;"><i class="ri-folders-line"></i> Términos y Condiciones</h4>
                            <?php if ($termEmpty): ?>
                                <p style="font-size: 0.8rem; color: #94a3b8; margin: 0 0 1rem 0;">No registra términos creados. (Status configurado en 0 por defecto).</p>
                            <?php else: ?>
                                <form method="POST" action="index.php" style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem;">
                                    <input type="hidden" name="action_update_status" value="1">
                                    <input type="hidden" name="cliente_id" value="<?= $cli->id ?>">
                                    <input type="hidden" name="modulo" value="terminos">
                                    <label style="font-size: 0.85rem; color: #334155; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                                        <input type="checkbox" name="statusId" value="1" <?= $termStatus === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
                                        Activo (statusId = 1)
                                    </label>
                                    <span style="font-size: 0.75rem; color: #64748b;"><?= count($termRecords) ?> registro(s)</span>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- MÓDULO PROYECTOS -->
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #0f172a;"><i class="ri-folder-open-line"></i> Proyectos de Auditoría</h4>
                            <?php if ($projEmpty): ?>
                                <p style="font-size: 0.8rem; color: #94a3b8; margin: 0 0 1rem 0;">No registra proyectos asociados. (Status configurado en 0 por defecto).</p>
                            <?php else: ?>
                                <form method="POST" action="index.php" style="display: flex; align-items: center; justify-content: space-name; justify-content: space-between; margin-top: 0.5rem;">
                                    <input type="hidden" name="action_update_status" value="1">
                                    <input type="hidden" name="cliente_id" value="<?= $cli->id ?>">
                                    <input type="hidden" name="modulo" value="proyectos">
                                    <label style="font-size: 0.85rem; color: #334155; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                                        <input type="checkbox" name="statusId" value="1" <?= $projStatus === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
                                        Activo (statusId = 1)
                                    </label>
                                    <span style="font-size: 0.75rem; color: #64748b;"><?= count($projRecords) ?> registro(s)</span>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>
                </details>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>