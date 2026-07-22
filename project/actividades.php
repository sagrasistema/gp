<?php
// v/proyectos/actividades.php
include '../main/config.php';

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);
$pruebaId = filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT);

if (!$proyectoId || !$pruebaId) {
    die("Error: Parámetros relacionales faltantes.");
}

// 1. Cargar metadatos de la Prueba seleccionada (incluyendo la norma)
try {
    $stmtPrueba = $pdo->prepare("
        SELECT p.nombre, p.norma, c.nombre AS catNombre 
        FROM audit_pruebas p 
        INNER JOIN audit_categorias c ON p.categoria_id = c.id 
        WHERE p.id = :pId
    ");
    $stmtPrueba->execute([':pId' => $pruebaId]);
    $metaPrueba = $stmtPrueba->fetch(PDO::FETCH_OBJ);

    if (!$metaPrueba) {
        die("Error: La prueba especificada no existe.");
    }
} catch (PDOException $e) {
    die("Error al cargar metadatos: " . $e->getMessage());
}

// 2. Procesar el guardado masivo del texto de las actividades (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actividades_data'])) {
    try {
        $pdo->beginTransaction();
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

// 3. Recuperar Catálogo de Actividades junto a su Estado de Llenado actual
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
            <i class="ri-checkbox-circle-fill"></i> Respuestas de auditoría guardadas con éxito.
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

        <div style="display:flex; justify-content:flex-end; gap:1rem; margin: 2rem 0 4rem 0;">
            <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary">Volver al Panel</a>
            <button type="submit" class="btn btn-primary" style="padding:0.75rem 2.5rem;"><i class="ri-save-3-line"></i> Guardar Respuestas</button>
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