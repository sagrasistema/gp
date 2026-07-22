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

// 1. Cargar metadatos de la Prueba seleccionada
$stmtPrueba = $pdo->prepare("SELECT p.nombre, c.nombre AS catNombre FROM audit_pruebas p INNER JOIN audit_categorias c ON p.categoria_id = c.id WHERE p.id = :pId");
$stmtPrueba->execute([':pId' => $pruebaId]);
$metaPrueba = $stmtPrueba->fetch(PDO::FETCH_OBJ);

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
        if ($pdo->inTransaction()) $pdo->rollBack();
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
    <!-- Cabecera de Metadatos del Proyecto -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: #ffffff; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio Líder</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; padding-left: 0.5rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->nombre ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio de Calidad</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioCalidad ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; padding-left: 0.5rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Remisión</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Gerente Encargado</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>
    </div>
<!---------------------------------------------->

    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: #ffffff; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; font-size: 0.9rem;">
           
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
           
           
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; padding-left: 0.5rem; font-size: 0.9rem;">
            
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->nombre ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
           
          
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; padding-left: 0.5rem; font-size: 0.9rem;">
           
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Remisión</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            
          
        </div>
    </div>

<!---------------------------------------------->
    <div style="background: #ffffff; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 2rem;">
        <span style="font-size:0.8rem; font-weight:700; color:var(--accent); text-transform:uppercase;"><?= htmlspecialchars($metaPrueba->catNombre, ENT_QUOTES, 'UTF-8') ?></span>
        <h2 style="margin: 0.25rem 0 0 0; font-size: 1.3rem; color: #1e293b;"><?= htmlspecialchars($metaPrueba->nombre, ENT_QUOTES, 'UTF-8') ?></h2>
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
                        <strong>Actividad <?= $act->orden ?>:</strong> <?= $act->descripcion; // Renderizado HTML nativo seguro ?>
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:0.85rem; color:#475569; display:flex; align-items:center; gap:0.25rem;">
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

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>