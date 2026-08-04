<?php

declare(strict_types=1);

// v/terminos/formulario-roles-proyecto.php
require_once '../main/config.php';

/** @var PDO $pdo */

// -------------------------------------------------------------------------
// 1. SANITIZACIÓN Y VALIDACIÓN DE PARÁMETROS ENTRANTES
// -------------------------------------------------------------------------
$terminoId = filter_input(INPUT_GET, 'terminoId', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'termino_id', FILTER_VALIDATE_INT);

if (!$terminoId || $terminoId <= 0) {
    http_response_code(400);
    die("Error: Identificador de Términos y Condiciones no especificado o inválido.");
}

$itemKey = 'roles_proyecto';

// -------------------------------------------------------------------------
// 2. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_roles'])) {
    $liderUsuarioIdInput   = filter_input(INPUT_POST, 'lider_usuario_id', FILTER_VALIDATE_INT);
    $auditorUsuarioIdInput = filter_input(INPUT_POST, 'auditor_usuario_id', FILTER_VALIDATE_INT);
    $contactoClienteInput  = filter_input(INPUT_POST, 'contacto_cliente', FILTER_DEFAULT);
    $observacionesInput    = filter_input(INPUT_POST, 'observaciones', FILTER_DEFAULT);

    // Sanitización de arreglo de integrantes del equipo (IDs de usuarios)
    $rawIntegrantes = $_POST['integrantes_ids'] ?? [];
    $integrantesIds = [];
    if (is_array($rawIntegrantes)) {
        $integrantesIds = array_values(
            array_filter(
                array_map('intval', $rawIntegrantes), 
                static fn(int $id): bool => $id > 0
            )
        );
    }

    $contactoCliente = is_string($contactoClienteInput) ? trim($contactoClienteInput) : '';
    $observaciones   = is_string($observacionesInput) ? trim($observacionesInput) : '';

    // Validaciones de Lógica de Negocio
    if (!$liderUsuarioIdInput || $liderUsuarioIdInput <= 0) {
        $errorMessage = "Debe seleccionar un Líder / Gerente de Proyecto válido.";
    } elseif (!$auditorUsuarioIdInput || $auditorUsuarioIdInput <= 0) {
        $errorMessage = "Debe seleccionar un Auditor Principal válido.";
    } else {
        try {
            $pdo->beginTransaction();

            // Guardar estructurado los IDs referenciales
            $payloadJson = json_encode([
                'lider_usuario_id'   => $liderUsuarioIdInput,
                'auditor_usuario_id' => $auditorUsuarioIdInput,
                'contacto_cliente'   => $contactoCliente,
                'integrantes_ids'    => $integrantesIds,
                'observaciones'      => $observaciones,
                'updated_at'         => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            // Actualizar el ítem 'roles_proyecto'
            $stmtUpdateItem = $pdo->prepare("
                UPDATE terminos_condiciones_items 
                SET datos_json = :datos_json,
                    estado = 'completado'
                WHERE termino_id = :termino_id AND item_key = :item_key
            ");
            $stmtUpdateItem->execute([
                ':datos_json' => $payloadJson,
                ':termino_id'  => $terminoId,
                ':item_key'    => $itemKey
            ]);

            // Reevaluar estado global de la cabecera
            $stmtCheckPending = $pdo->prepare("
                SELECT COUNT(*) 
                FROM terminos_condiciones_items 
                WHERE termino_id = :termino_id AND estado != 'completado'
            ");
            $stmtCheckPending->execute([':termino_id' => $terminoId]);
            $pendingCount = (int)$stmtCheckPending->fetchColumn();

            $nuevoEstadoGlobal = ($pendingCount === 0) ? 'completado' : 'en_proceso';

            $stmtUpdateMaster = $pdo->prepare("
                UPDATE terminos_condiciones 
                SET estado = :estado 
                WHERE id = :id
            ");
            $stmtUpdateMaster->execute([
                ':estado' => $nuevoEstadoGlobal,
                ':id'     => $terminoId
            ]);

            $pdo->commit();

            header("Location: responder-terminos.php?id={$terminoId}&success=roles_saved");
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al guardar roles del proyecto: " . $e->getMessage());
            $errorMessage = "Ocurrió un error interno al guardar la asignación de roles.";
        }
    }
}

// -------------------------------------------------------------------------
// 3. CARGAR DATOS EXISTENTES Y CATÁLOGO DE USUARIOS
// -------------------------------------------------------------------------
try {
    // Cargar datos de la cabecera
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
        die("Error: El registro de Términos y Condiciones no existe.");
    }

    // Consulta ajustada estrictamente a las columnas del SQL Dump
    $stmtUsuarios = $pdo->query("
        SELECT id, username, nombre_completo, rol 
        FROM usuarios 
        ORDER BY nombre_completo ASC
    ");
    $usuariosSistema = $stmtUsuarios->fetchAll(PDO::FETCH_OBJ);

    // Cargar el ítem configurado actual
    $stmtItem = $pdo->prepare("
        SELECT * 
        FROM terminos_condiciones_items 
        WHERE termino_id = :termino_id AND item_key = :item_key
    ");
    $stmtItem->execute([
        ':termino_id' => $terminoId,
        ':item_key'   => $itemKey
    ]);
    $itemData = $stmtItem->fetch(PDO::FETCH_OBJ);

    $savedData = [];
    if ($itemData && !empty($itemData->datos_json)) {
        $savedData = json_decode($itemData->datos_json, true) ?: [];
    }

} catch (PDOException $e) {
    error_log("Error al cargar módulo de roles: " . $e->getMessage());
    die("Error crítico al consultar la base de datos.");
}

$liderUsuarioIdVal   = (int)($savedData['lider_usuario_id'] ?? 0);
$auditorUsuarioIdVal = (int)($savedData['auditor_usuario_id'] ?? 0);
$contactoClienteVal  = (string)($savedData['contacto_cliente'] ?? '');
$integrantesIdsVal   = (array)($savedData['integrantes_ids'] ?? []);
$observacionesVal   = (string)($savedData['observaciones'] ?? '');

$pageTitle = "Asignación de Roles - Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container" style="padding: 2rem; max-width: 900px; margin: 0 auto;">

    <!-- CABECERA DE NAVEGACIÓN -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-team-line" style="color: #2563eb;"></i> Actividad 3: Roles y Responsabilidades
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($headerData->clientName, ENT_QUOTES, 'UTF-8') ?></strong> | 
                Servicio: <strong><?= htmlspecialchars($headerData->servicio, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; text-decoration: none; background: #e2e8f0; color: #334155; border-radius: 6px; font-weight: 600;">
            <i class="ri-arrow-left-line"></i> Volver a Actividades
        </a>
    </div>

    <?php if (isset($errorMessage)): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO CON SELECTORES DE USUARIOS -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <form method="POST" action="formulario-roles-proyecto.php?terminoId=<?= $terminoId ?>">
            <input type="hidden" name="action_save_roles" value="1">
            <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

            <!-- DESPLEGABLES LÍDER Y AUDITOR -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <!-- LÍDER DEL PROYECTO -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Líder / Gerente de Proyecto *
                    </label>
                    <select name="lider_usuario_id" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                        <option value="">-- Seleccionar Usuario --</option>
                        <?php foreach ($usuariosSistema as $usr): ?>
                            <option value="<?= $usr->id ?>" <?= $usr->id === $liderUsuarioIdVal ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usr->nombre_completo, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($usr->username, ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- AUDITOR PRINCIPAL -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Auditor Principal / Encargado *
                    </label>
                    <select name="auditor_usuario_id" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                        <option value="">-- Seleccionar Usuario --</option>
                        <?php foreach ($usuariosSistema as $usr): ?>
                            <option value="<?= $usr->id ?>" <?= $usr->id === $auditorUsuarioIdVal ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usr->nombre_completo, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($usr->username, ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- CONTACTO CLIENTE E INTEGRANTES ADICIONALES -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <!-- CONTACTO DEL CLIENTE (TEXTO) -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Contacto Principal por el Cliente
                    </label>
                    <input type="text" name="contacto_cliente" value="<?= htmlspecialchars($contactoClienteVal, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre de la persona contacto en la empresa cliente" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                </div>

                <!-- INTEGRANTES DEL EQUIPO (SELECCIÓN MÚLTIPLE) -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Otros Integrantes del Equipo Auditor
                    </label>
                    <select name="integrantes_ids[]" multiple style="width: 100%; height: 110px; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                        <?php foreach ($usuariosSistema as $usr): ?>
                            <?php 
                                $isSelected = in_array((int)$usr->id, $integrantesIdsVal, true) ? 'selected' : '';
                            ?>
                            <option value="<?= $usr->id ?>" <?= $isSelected ?>>
                                <?= htmlspecialchars($usr->nombre_completo, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($usr->username, ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display: block; font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Mantenga presionada la tecla Ctrl (o Cmd en Mac) para seleccionar varios.</small>
                </div>

            </div>

            <!-- OBSERVACIONES -->
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Observaciones o Notas sobre el Equipo
                </label>
                <textarea name="observaciones" rows="3" placeholder="Detalle particularidades sobre disponibilidad, firmas autorizadas o modalidades de reunión..." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($observacionesVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- BOTONES -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
                <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; background: #e2e8f0; color: #334155; border-radius: 6px; text-decoration: none; font-weight: 600;">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="ri-save-line"></i> Guardar Roles
                </button>
            </div>

        </form>
    </div>

</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>