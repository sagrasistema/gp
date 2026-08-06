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
// 1. PROCESAR ACTUALIZACIÓN DE VISIBILIDAD (VER_ID) (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_ver'])) {
    $clienteId  = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $modulo     = filter_input(INPUT_POST, 'modulo', FILTER_SANITIZE_SPECIAL_CHARS);
    $nuevoVerId = isset($_POST['ver_id']) ? 1 : 0;

    // Se agrega 'clientes' a los módulos permitidos
    if ($clienteId && in_array($modulo, ['ac', 'terminos', 'proyectos', 'clientes'], true)) {
        try {
            $pdo->beginTransaction();

            switch ($modulo) {
                case 'ac':
                    $stmt = $pdo->prepare("UPDATE ac SET ver_id = :ver WHERE clientId = :cliente_id");
                    break;
                case 'terminos':
                    $stmt = $pdo->prepare("UPDATE terminos_condiciones SET ver_id = :ver WHERE cliente_id = :cliente_id");
                    break;
                case 'proyectos':
                    $stmt = $pdo->prepare("UPDATE proyectos SET ver_id = :ver WHERE cliente_id = :cliente_id");
                    break;
                case 'clientes':
                    $stmt = $pdo->prepare("UPDATE clientes SET ver_id = :ver WHERE id = :cliente_id");
                    break;
            }

            $stmt->execute([
                ':ver'        => $nuevoVerId,
                ':cliente_id' => $clienteId
            ]);

            $pdo->commit();
            header("Location: index.php?success=updated");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al actualizar ver_id en módulo {$modulo}: " . $e->getMessage());
            $errorMessage = "Error al actualizar la visibilidad en la base de datos.";
        }
    }
}

// -------------------------------------------------------------------------
// 2. CONSULTAR CLIENTES Y SU CONFIGURACIÓN
// -------------------------------------------------------------------------
try {
    // Nota: Seleccionamos ver_id del cliente también
    $stmtClientes = $pdo->query("SELECT id, name, rif, ver_id FROM clientes ORDER BY name ASC");
    $clientesList = $stmtClientes->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al consultar clientes: " . $e->getMessage());
    die("Error al cargar los datos del sistema.");
}

$pageTitle = "Control de Visibilidad por Cliente";
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
            <i class="ri-user-settings-line"></i> Control de Visibilidad (Ver ID) por Cliente
        </h1>
        <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
            Administre los interruptores de control visual (`ver_id`).
        </p>
    </div>

    <div class="table-actions-container">
        <a href="../index.php" class="btn btn-primary"><i class="ri-arrow-go-back-line"></i> Volver</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Visibilidad actualizada correctamente.
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($clientesList as $cli): 
            // Consultas de sub-módulos para el contador y estado
            $stmtAc = $pdo->prepare("SELECT ver_id FROM ac WHERE clientId = ?");
            $stmtAc->execute([$cli->id]);
            $acRecords = $stmtAc->fetchAll(PDO::FETCH_OBJ);

            $stmtTerm = $pdo->prepare("SELECT ver_id FROM terminos_condiciones WHERE cliente_id = ?");
            $stmtTerm->execute([$cli->id]);
            $termRecords = $stmtTerm->fetchAll(PDO::FETCH_OBJ);

            $stmtProj = $pdo->prepare("SELECT ver_id FROM proyectos WHERE cliente_id = ?");
            $stmtProj->execute([$cli->id]);
            $projRecords = $stmtProj->fetchAll(PDO::FETCH_OBJ);

            $acEmpty   = empty($acRecords);
            $termEmpty = empty($termRecords);
            $projEmpty = empty($projRecords);
        ?>
            <details style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <summary style="padding: 1rem 1.25rem; background: #f8fafc; cursor: pointer; font-weight: 600; display: flex; justify-content: space-between;">
                    <span><?= htmlspecialchars($cli->name, ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-size: 0.8rem; font-weight: normal;">RIF: <?= htmlspecialchars($cli->rif ?? 'N/D') ?></span>
                </summary>

                <div style="padding: 1.25rem; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                    
                    <!-- 1. MÓDULO CLIENTE (NUEVO) -->
                    <div style="background: #eff6ff; padding: 1rem; border-radius: 6px; border: 1px solid #bfdbfe;">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem;"><i class="ri-building-line"></i> Perfil Cliente</h4>
                        <form method="POST" action="index.php">
                            <input type="hidden" name="action_update_ver" value="1">
                            <input type="hidden" name="cliente_id" value="<?= $cli->id ?>">
                            <input type="hidden" name="modulo" value="clientes">
                            <label style="font-size: 0.85rem; cursor: pointer;">
                                <input type="checkbox" name="ver_id" value="1" <?= (int)$cli->ver_id === 1 ? 'checked' : '' ?> onchange="this.form.submit()">
                                Visible (ver_id = 1)
                            </label>
                        </form>
                    </div>

                    <!-- 2. MÓDULO AC -->
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">Aceptación (AC)</h4>
                        <?php if ($acEmpty): ?> <p style="font-size: 0.75rem; color: #94a3b8;">Sin datos.</p> <?php else: ?>
                            <form method="POST" action="index.php">
                                <input type="hidden" name="action_update_ver" value="1">
                                <input type="hidden" name="cliente_id" value="<?= $cli->id ?>">
                                <input type="hidden" name="modulo" value="ac">
                                <input type="checkbox" name="ver_id" value="1" <?= (int)$acRecords[0]->ver_id === 1 ? 'checked' : '' ?> onchange="this.form.submit()"> Visible
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- 3. MÓDULO TÉRMINOS -->
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">Términos</h4>
                        <?php if ($termEmpty): ?> <p style="font-size: 0.75rem; color: #94a3b8;">Sin datos.</p> <?php else: ?>
                            <form method="POST" action="index.php">
                                <input type="hidden" name="action_update_ver" value="1">
                                <input type="hidden" name="cliente_id" value="<?= $cli->id ?>">
                                <input type="hidden" name="modulo" value="terminos">
                                <input type="checkbox" name="ver_id" value="1" <?= (int)$termRecords[0]->ver_id === 1 ? 'checked' : '' ?> onchange="this.form.submit()"> Visible
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- 4. MÓDULO PROYECTOS -->
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                        <h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">Proyectos</h4>
                        <?php if ($projEmpty): ?> <p style="font-size: 0.75rem; color: #94a3b8;">Sin datos.</p> <?php else: ?>
                            <form method="POST" action="index.php">
                                <input type="hidden" name="action_update_ver" value="1">
                                <input type="hidden" name="cliente_id" value="<?= $cli->id ?>">
                                <input type="hidden" name="modulo" value="proyectos">
                                <input type="checkbox" name="ver_id" value="1" <?= (int)$projRecords[0]->ver_id === 1 ? 'checked' : '' ?> onchange="this.form.submit()"> Visible
                            </form>
                        <?php endif; ?>
                    </div>

                </div>
            </details>
        <?php endforeach; ?>
    </div>
</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>