<?php
// v/proyectos/actividades.php
include '../main/config.php';

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);
$pruebaId = filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT);

if (!$proyectoId || !$pruebaId) {
    die("Error: Parámetros relacionales faltantes.");
}

// 1. Cargar Cabecera del Proyecto y Datos del Cliente
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.name AS clientName, 
            c.rif AS clientRif
        FROM proyectos p 
        INNER JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $proyectoId]);
    $projectData = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$projectData) {
        die("Error: El proyecto solicitado no existe.");
    }
} catch (PDOException $e) {
    error_log("Error crítico en cabecera de proyecto: " . $e->getMessage());
    die("Error crítico de base de datos al cargar el proyecto.");
}

// 2. Cargar metadatos de la Prueba seleccionada y su Estatus Actual en ejecución
try {
    $stmtPrueba = $pdo->prepare("
        SELECT p.nombre, p.norma, c.nombre AS catNombre 
        FROM audit_pruebas p 
        INNER JOIN audit_categorias c ON p.categoria_id = c.id 
        WHERE p.id = :pId
    ");
    $stmtPrueba->execute([':pId' => $pruebaId]);
    $metaPrueba = $stmtPrueba->fetch(PDO::FETCH_OBJ);

    if (! $metaPrueba) {
        die("Error: La prueba especificada no existe.");
    }

    // Obtener estatus actual de la prueba en este proyecto
    $stmtStatus = $pdo->prepare("
        SELECT estado FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :projId AND prueba_id = :prId
    ");
    $stmtStatus->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
    $estadoActualPrueba = $stmtStatus->fetchColumn() ?: 'en_proceso';

} catch (PDOException $e) {
    die("Error al cargar metadatos: " . $e->getMessage());
}

// 3. Procesar el guardado masivo de actividades + Estatus de la Prueba (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['actividades_data']) || isset($_POST['estado_prueba']))) {
    try {
        $pdo->beginTransaction();

        // A. Guardar/Actualizar Actividades
        if (isset($_POST['actividades_data']) && is_array($_POST['actividades_data'])) {
            $stmtSave = $pdo->prepare("
                INSERT INTO proyecto_actividades_ejecucion (proyecto_id, actividad_id, contenido_llenado, completado)
                VALUES (:proyecto_id, :actividad_id, :contenido, :completado)
                ON DUPLICATE KEY UPDATE contenido_llenado = :contenido_u, completado = :completado_u
            ");

            foreach ($_POST['actividades_data'] as $actId => $v) {
                $contenido = trim($v['contenido'] ?? '');
                $completado = isset($v['completado']) ? 1 : 0;

                $stmtSave->execute([
                    ':proyecto_id'  => $proyectoId,
                    ':actividad_id' => $actId,
                    ':contenido'    => $contenido !== '' ? $contenido : null,
                    ':completado'   => $completado,
                    ':contenido_u'  => $contenido !== '' ? $contenido : null,
                    ':completado_u' => $completado
                ]);
            }
        }

        // B. Guardar/Actualizar Estatus General de la Prueba
        $nuevoEstadoPrueba = trim($_POST['estado_prueba'] ?? 'en_proceso');
        $stmtTestSave = $pdo->prepare("
            INSERT INTO proyecto_pruebas_ejecucion (proyecto_id, prueba_id, estado)
            VALUES (:proyecto_id, :prueba_id, :estado)
            ON DUPLICATE KEY UPDATE estado = :estado_u
        ");
        $stmtTestSave->execute([
            ':proyecto_id' => $proyectoId,
            ':prueba_id'   => $pruebaId,
            ':estado'      => $nuevoEstadoPrueba,
            ':estado_u'    => $nuevoEstadoPrueba
        ]);

        $pdo->commit();
        header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error al procesar el guardado del formulario: " . $e->getMessage());
    }
}

// 4. Recuperar Catálogo de Actividades junto a su Estado de Llenado actual
$sqlActividades = "
    SELECT a.id, a.descripcion, a.orden, COALESCE(ae.contenido_llenado, '') AS respuesta, COALESCE(ae.completado, 0) AS is_ok
    FROM audit_actividades a
    LEFT JOIN proyecto_actividades_ejecucion ae ON ae.actividad_id = a.id AND ae.proyecto_id = :projId
    WHERE a.prueba_id = :prId ORDER BY a.orden ASC";
$stmtA = $pdo->prepare($sqlActividades);
$stmtA->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
$listaActividades = $stmtA->fetchAll(PDO::FETCH_OBJ);

$pageTitle = "Formulario de Actividades";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container">
    
    <div class="table-actions-container">
        <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;"><i class="ri-arrow-go-back-line"></i></a>
        <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;"><i class="ri-screenshot-2-line"></i></a>
        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;"><i class="ri-book-open-line"></i></a>
        <a href="nuevo.php" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;"><i class="ri-add-line"></i></a>
        <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-primary" data-tooltip="Cancelar (Atrás)"><i class="ri-close-circle-line"></i></a>
    </div>

    <!-- Cabecera de Metadatos del Proyecto -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: #ffffff; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Realizado por:</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Revisado</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Estatus</span>
            <strong style="color: #1e293b; font-size: 0.9rem; text-transform: capitalize;"><?= str_replace('_', ' ', $estadoActualPrueba) ?></strong>
        </div>
    </div>

    <!-- Cabecera de la Prueba con Botón de Norma -->
    <div style="background: #ffffff; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 2rem;">
        <span style="font-size:0.8rem; font-weight:700; color:var(--accent); text-transform:uppercase;"><?= htmlspecialchars($metaPrueba->catNombre, ENT_QUOTES, 'UTF-8') ?></span>
        <h2 style="margin: 0.25rem 0 0.75rem 0; font-size: 1.3rem; color: #1e293b;"><?= htmlspecialchars($metaPrueba->nombre, ENT_QUOTES, 'UTF-8') ?></h2>
        
        <button type="button" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.85rem;" onclick="openNormaModal()">
            <i class="ri-book-line"></i> Norma de Referencia
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px; margin-bottom:1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Respuestas de auditoría y estatus guardados con éxito.
        </div>
    <?php endif; ?>

    <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST">
        <?php foreach ($listaActividades as $act): ?>
            <div class="card-actividad" style="background:#ffffff; border: 1px solid var(--border-color); padding:1.5rem; border-radius:8px; margin-bottom:1.25rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:1rem; gap:1rem;">
                    <div style="font-size:1rem; color:#334155; line-height:1.4;">
                        <strong>Actividad <?= $act->orden ?>:</strong> <?= $act->descripcion; ?>
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:0.85rem; color:#475569; display:flex; align-items:center; gap:0.25rem; cursor:pointer;">
                            <input type="checkbox" name="actividades_data[<?= $act->id ?>][completado]" value="1" <?= $act->is_ok ? 'checked' : '' ?>> Realizado
                        </label>
                    </div>
                </div>
                <textarea name="actividades_data[<?= $act->id ?>][contenido]" placeholder="Escriba aquí los hallazgos, papeles de trabajo o evidencias analizadas..." rows="4" style="width:100%; padding:0.75rem; border-radius:6px; border:1px solid #cbd5e1; font-family:inherit; resize:vertical;"><?= htmlspecialchars($act->respuesta, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        <?php endforeach; ?>

        <!-- CAJA FINAL: Estatus General de la Prueba -->
        <div style="background: #ffffff; border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div>
                <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #1e293b;">Estatus General de la Prueba</h4>
                <p style="margin: 0; font-size: 0.85rem; color: #64748b;">Seleccione el estado en el que se encuentra esta prueba al guardar el formulario.</p>
            </div>
            <div>
                <select name="estado_prueba" class="status-select" style="padding: 0.6rem 1rem; border-radius: 8px; font-size: 0.9rem; border: 1px solid #cbd5e1; font-weight: 600; background: #f8fafc;">
                    <option value="en_proceso" <?= $estadoActualPrueba === 'en_proceso' ? 'selected' : '' ?>>⏳ En proceso</option>
                    <option value="completado" <?= $estadoActualPrueba === 'completado' ? 'selected' : '' ?>>✅ Completado</option>
                    <option value="por_corregir_lider" <?= $estadoActualPrueba === 'por_corregir_lider' ? 'selected' : '' ?>>⚠️ Por Corregir Lider</option>
                    <option value="por_corregir_riesgo" <?= $estadoActualPrueba === 'por_corregir_riesgo' ? 'selected' : '' ?>>🚨 Por Corregir Riesgo</option>
                    <option value="revisado" <?= $estadoActualPrueba === 'revisado' ? 'selected' : '' ?>>🔹 Revisado</option>
                    <option value="cerrado" <?= $estadoActualPrueba === 'cerrado' ? 'selected' : '' ?>>🔒 Cerrado</option>
                </select>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:1rem; margin: 2rem 0 4rem 0;">
            <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary">Volver al Panel</a>
            <button type="submit" class="btn btn-primary" style="padding:0.75rem 2.5rem;"><i class="ri-save-3-line"></i> Guardar Respuestas y Estatus</button>
        </div>
    </form>
</div>

<!-- Modal de la Norma -->
<div id="normaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#ffffff; padding:2rem; border-radius:12px; max-width:650px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
            <h3 style="margin:0; color:#1e293b; font-size:1.15rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="ri-book-2-line" style="color:var(--accent);"></i> Norma Aplicable
            </h3>
            <button type="button" onclick="closeNormaModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div style="color:#334155; line-height:1.6; max-height:400px; overflow-y:auto; font-size:0.95rem;">
            <?= !empty($metaPrueba->norma) ? nl2br(htmlspecialchars($metaPrueba->norma, ENT_QUOTES, 'UTF-8')) : '<em style="color:#64748b;">No hay una norma o marco regulatorio registrado para esta prueba.</em>' ?>
        </div>
        <div style="text-align:right; margin-top:1.5rem; border-top:1px solid #e2e8f0; padding-top:1rem;">
            <button type="button" class="btn btn-primary" onclick="closeNormaModal()" style="padding: 0.5rem 1.5rem;">Cerrar</button>
        </div>
    </div>
</div>

<script>
function openNormaModal() {
    document.getElementById('normaModal').style.display = 'flex';
}
function closeNormaModal() {
    document.getElementById('normaModal').style.display = 'none';
}
window.onclick = function(event) {
    let modal = document.getElementById('normaModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>