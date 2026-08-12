<?php 
// Procesar cambio de estado a Cerrado (1) o En Proceso (2)
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$projectId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statusId']) && $projectId) {
    if ($currentUserId === 1 || $currentUserId === 2) {
        $newStatusId = (int)$_POST['statusId'];
        if (in_array($newStatusId, [1, 2], true)) {
            $stmtUpdateStatus = $pdo->prepare("UPDATE proyectos SET statusId = :statusId, updated_at = NOW() WHERE id = :id");
            $stmtUpdateStatus->execute([':statusId' => $newStatusId, ':id' => $projectId]);
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $projectId);
            exit;
        }
    }
}

// Obtener estado actual del proyecto
$stmtStatus = $pdo->prepare("SELECT statusId FROM proyectos WHERE id = :id");
$stmtStatus->execute([':id' => $projectId]);
$currentStatusId = (int)($stmtStatus->fetchColumn() ?: 1);
$isClosed = ($currentStatusId === 2);?>

<?php if ($currentUserId === 1 || $currentUserId === 2): ?>
    <form method="POST" action="">
        <div style="margin-top: 2rem; border: 1px solid <?= $isClosed ? '#fecaca' : '#cbd5e1' ?>; border-radius: 8px; background: <?= $isClosed ? '#fef2f2' : '#ffffff' ?>; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid <?= $isClosed ? '#fee2e2' : '#f1f5f9' ?>; padding-bottom: 0.75rem;">
                <h3 style="margin: 0; font-size: 1.05rem; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; font-weight: 700;">
                    <i class="ri-shield-keyhole-line" style="color: <?= $isClosed ? '#dc2626' : '#0284c7' ?>; font-size: 1.25rem;"></i> Cierre y Estado del Proyecto
                </h3>
                <?php if ($isClosed): ?>
                    <span style="background: #dc2626; color: #ffffff; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                        <i class="ri-lock-fill"></i> PROYECTO CERRADO
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
                        Este proyecto ha sido finalizado y cerrado. Los campos se encuentran bloqueados para evitar alteraciones en la información.
                    <?php else: ?>
                        Selecciona <strong>"Cerrado"</strong> cuando hayas finalizado para bloquear la modificación de los registros.
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