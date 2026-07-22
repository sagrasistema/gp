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
        SELECT p.*, c.name AS clientName, c.rif AS clientRif
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

// 2. Cargar metadatos de la Prueba y su Estatus Actual
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

    $stmtStatus = $pdo->prepare("
        SELECT estado FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :projId AND prueba_id = :prId
    ");
    $stmtStatus->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
    $estadoActualPrueba = $stmtStatus->fetchColumn() ?: 'en_proceso';

} catch (PDOException $e) {
    die("Error al cargar metadatos: " . $e->getMessage());
}

// 3. Procesamiento POST (Guardado de actividades, estatus y detalles de indicadores)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? 'save_all';

    try {
        $pdo->beginTransaction();

        if ($action === 'add_indicador_detalle') {
            // Guardar un punto de control / detalle para un indicador específico
            $tipoInd = filter_input(INPUT_POST, 'tipo_indicador', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $rubro = trim($_POST['rubro'] ?? '');
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $recomendacion = trim($_POST['recomendacion'] ?? '');

            if (in_array($tipoInd, ['CI', 'CG', 'SC', 'AA']) && !empty($titulo)) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO proyecto_indicador_detalles (proyecto_id, prueba_id, tipo_indicador, rubro, titulo, descripcion, recomendacion)
                    VALUES (:proj, :pr, :tipo, :rubro, :titulo, :desc, :rec)
                ");
                $stmtIns->execute([
                    ':proj' => $proyectoId, ':pr' => $pruebaId, ':tipo' => $tipoInd,
                    ':rubro' => $rubro, ':titulo' => $titulo, ':desc' => $descripcion, ':rec' => $recomendacion
                ]);
            }
        } elseif ($action === 'delete_indicador_detalle') {
            // Eliminar un detalle de indicador
            $detalleId = filter_input(INPUT_POST, 'detalle_id', FILTER_VALIDATE_INT);
            if ($detalleId) {
                $stmtDel = $pdo->prepare("DELETE FROM proyecto_indicador_detalles WHERE id = :id AND proyecto_id = :proj AND prueba_id = :pr");
                $stmtDel->execute([':id' => $detalleId, ':proj' => $proyectoId, ':pr' => $pruebaId]);
            }
        } else {
            // Guardado General (Actividades + Estatus de Prueba)
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

            // Actualizar Estatus General y evaluar booleanos automáticos de indicadores según existencia de registros
            $nuevoEstadoPrueba = trim($_POST['estado_prueba'] ?? 'en_proceso');
            
            // Verificamos si existen registros para cada indicador para mantener sincronizado el resumen
            $hasCI = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CI'")->fetchColumn() > 0 ? 1 : 0;
            $hasCG = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CG'")->fetchColumn() > 0 ? 1 : 0;
            $hasSC = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='SC'")->fetchColumn() > 0 ? 1 : 0;
            $hasAA = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='AA'")->fetchColumn() > 0 ? 1 : 0;

            $stmtTestSave = $pdo->prepare("
                INSERT INTO proyecto_pruebas_ejecucion 
                (proyecto_id, prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado)
                VALUES (:proyecto_id, :prueba_id, :ci, :cg, :sc, :aa, :estado)
                ON DUPLICATE KEY UPDATE 
                    indicador_ci = :ci_u, indicador_cg = :cg_u, indicador_sc = :sc_u, indicador_aa = :aa_u, estado = :estado_u
            ");
            $stmtTestSave->execute([
                ':proyecto_id' => $proyectoId, ':prueba_id' => $pruebaId,
                ':ci' => $hasCI, ':cg' => $hasCG, ':sc' => $hasSC, ':aa' => $hasAA, ':estado' => $nuevoEstadoPrueba,
                ':ci_u' => $hasCI, ':cg_u' => $hasCG, ':sc_u' => $hasSC, ':aa_u' => $hasAA, ':estado_u' => $nuevoEstadoPrueba
            ]);
        }

        $pdo->commit();
        header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error al procesar la operación: " . $e->getMessage());
    }
}

// 4. Recuperar Catálogo de Actividades y Detalles de los Indicadores
$sqlActividades = "
    SELECT a.id, a.descripcion, a.orden, COALESCE(ae.contenido_llenado, '') AS respuesta, COALESCE(ae.completado, 0) AS is_ok
    FROM audit_actividades a
    LEFT JOIN proyecto_actividades_ejecucion ae ON ae.actividad_id = a.id AND ae.proyecto_id = :projId
    WHERE a.prueba_id = :prId ORDER BY a.orden ASC";
$stmtA = $pdo->prepare($sqlActividades);
$stmtA->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
$listaActividades = $stmtA->fetchAll(PDO::FETCH_OBJ);

// Cargar detalles de indicadores agrupados por tipo
$stmtIndDetalles = $pdo->prepare("SELECT * FROM proyecto_indicador_detalles WHERE proyecto_id = :proj AND prueba_id = :pr ORDER BY id DESC");
$stmtIndDetalles->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
$allDetalles = $stmtIndDetalles->fetchAll(PDO::FETCH_OBJ);

$detallesPorTipo = ['CI' => [], 'CG' => [], 'SC' => [], 'AA' => []];
foreach ($allDetalles as $det) {
    $detallesPorTipo[$det->tipo_indicador][] = $det;
}

$pageTitle = "Formulario de Actividades y Hallazgos";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container">
    
    <div class="table-actions-container">
        <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-primary" data-tooltip="Cancelar (Atrás)"><i class="ri-close-circle-line"></i> Volver al Panel</a>
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

    <!-- Cabecera de la Prueba -->
    <div style="background: #1e293b; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <span style="font-size: 0.75rem; font-weight: 700; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.25rem;">
            <?= htmlspecialchars($metaPrueba->catNombre, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <h2 style="margin: 0 0 1rem 0; font-size: 1.35rem; color: #ffffff; font-weight: 700; line-height: 1.4;">
            <?= htmlspecialchars($metaPrueba->nombre, ENT_QUOTES, 'UTF-8') ?>
        </h2>
        <button type="button" onclick="openNormaModal()" style="background: #0284c7; color: #ffffff; border: none; font-size: 0.85rem; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="ri-book-line"></i> Norma de Referencia
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px; margin-bottom:1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Operación ejecutada con éxito.
        </div>
    <?php endif; ?>

    <!-- FORMULARIO PRINCIPAL DE ACTIVIDADES -->
    <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST">
        <input type="hidden" name="action_type" value="save_all">
        
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

        <!-- SECCIÓN DE ACORDEONES POR CADA INDICADOR (CI, CG, SC, AA) -->
        <div style="margin: 2.5rem 0 1.5rem 0;">
            <h3 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin-bottom: 1rem;">Indicadores y Puntos de Control</h3>
            
            <?php 
            $indicadoresMeta = [
                'CI' => ['nombre' => 'Debilidades de Control Interno (CI)', 'color' => '#16a34a'],
                'CG' => ['nombre' => 'Carta de Gerencia (CG)', 'color' => '#2563eb'],
                'SC' => ['nombre' => 'Situaciones Críticas (SC)', 'color' => '#dc2626'],
                'AA' => ['nombre' => 'Asuntos de Auditoría (AA)', 'color' => '#9333ea']
            ];

            foreach ($indicadoresMeta as $key => $meta):
                $items = $detallesPorTipo[$key] ?? [];
            ?>
                <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: #ffffff;">
                    <!-- Cabecera del Acordeon -->
                    <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid <?= $meta['color'] ?>;">
                        <span style="color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="ri-file-list-3-line" style="color: <?= $meta['color'] ?>;"></i> <?= $meta['nombre'] ?> 
                            <span style="font-size: 0.75rem; background: #e2e8f0; color: #334155; padding: 0.15rem 0.5rem; border-radius: 9999px;"><?= count($items) ?> registros</span>
                        </span>
                        <i class="ri-arrow-down-s-line"></i>
                    </div>

                    <!-- Contenido del Acordeon -->
                    <div class="accordion-content" style="display: none; padding: 1.25rem; background: #ffffff;">
                        <div style="margin-bottom: 1rem;">
                            <button type="button" class="btn btn-primary" onclick="openIndicatorModal('<?= $key ?>')" style="padding: 0.4rem 0.85rem; font-size: 0.85rem; background: <?= $meta['color'] ?>; border-color: <?= $meta['color'] ?>;">
                                <i class="ri-add-line"></i> Punto de control
                            </button>
                        </div>

                        <!-- Tabla de Registros del Indicador -->
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                                        <th style="padding: 0.75rem;">Rubro</th>
                                        <th style="padding: 0.75rem;">Título</th>
                                        <th style="padding: 0.75rem;">Descripción</th>
                                        <th style="padding: 0.75rem;">Recomendación del Asunto</th>
                                        <th style="padding: 0.75rem; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 0.75rem; font-weight: 600; color: #334155;"><?= htmlspecialchars($item->rubro ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td style="padding: 0.75rem; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($item->titulo, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td style="padding: 0.75rem; color: #475569;"><?= nl2br(htmlspecialchars($item->descripcion, ENT_QUOTES, 'UTF-8')) ?></td>
                                                <td style="padding: 0.75rem; color: #475569;"><?= nl2br(htmlspecialchars($item->recomendacion ?? '-', ENT_QUOTES, 'UTF-8')) ?></td>
                                                <td style="padding: 0.75rem; text-align: center;">
                                                    <button type="submit" form="deleteForm_<?= $item->id ?>" class="btn" style="background: #fee2e2; color: #dc2626; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;" title="Eliminar">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="padding: 1rem; text-align: center; color: #64748b; font-style: italic;">No hay registros agregados en este indicador.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CAJA DE ESTATUS GENERAL DE LA PRUEBA -->
        <div style="background: #ffffff; border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div>
                <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #1e293b;">Estatus General de la Prueba</h4>
                <p style="margin: 0; font-size: 0.85rem; color: #64748b;">El estatus se actualizará al guardar todo el formulario.</p>
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
            <button type="submit" class="btn btn-primary" style="padding:0.75rem 2.5rem;"><i class="ri-save-3-line"></i> Guardar Todo</button>
        </div>
    </form>

    <!-- Formularios ocultos individuales para eliminar registros de indicadores -->
    <?php foreach ($allDetalles as $det): ?>
        <form id="deleteForm_<?= $det->id ?>" action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST" style="display:none;">
            <input type="hidden" name="action_type" value="delete_indicador_detalle">
            <input type="hidden" name="detalle_id" value="<?= $det->id ?>">
        </form>
    <?php endforeach; ?>
</div>

<!-- MODAL PARA AGREGAR PUNTO DE CONTROL DE INDICADOR -->
<div id="indicatorModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1100; align-items:center; justify-content:center;">
    <div style="background:#ffffff; padding:2rem; border-radius:12px; max-width:650px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
            <h3 id="modalIndicatorTitle" style="margin:0; color:#1e293b; font-size:1.15rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="ri-add-box-line" style="color:var(--accent);"></i> Nuevo Punto de Control
            </h3>
            <button type="button" onclick="closeIndicatorModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST">
            <input type="hidden" name="action_type" value="add_indicador_detalle">
            <input type="hidden" id="modalTipoIndicador" name="tipo_indicador" value="">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Rubro</label>
                <input type="text" name="rubro" placeholder="Ej. Activo Corriente, Cuentas por Cobrar..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Título del Asunto / Hallazgo *</label>
                <input type="text" name="titulo" required placeholder="Título resumido..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Descripción *</label>
                <textarea name="descripcion" required rows="3" placeholder="Descripción detallada de la debilidad o hallazgo..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Recomendación del Asunto</label>
                <textarea name="recomendacion" rows="3" placeholder="Recomendación sugerida..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="text-align:right; border-top:1px solid #e2e8f0; padding-top:1rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeIndicatorModal()" style="padding: 0.5rem 1.25rem;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Guardar Registro</button>
            </div>
        </form>
    </div>
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
            <?= !empty($metaPrueba->norma) ? nl2br(htmlspecialchars($metaPrueba->norma, ENT_QUOTES, 'UTF-8')) : '<em style="color:#64748b;">No hay una norma registrada.</em>' ?>
        </div>
        <div style="text-align:right; margin-top:1.5rem; border-top:1px solid #e2e8f0; padding-top:1rem;">
            <button type="button" class="btn btn-primary" onclick="closeNormaModal()" style="padding: 0.5rem 1.5rem;">Cerrar</button>
        </div>
    </div>
</div>

<script>
function openIndicatorModal(tipo) {
    document.getElementById('modalTipoIndicador').value = tipo;
    document.getElementById('indicatorModal').style.display = 'flex';
}
function closeIndicatorModal() {
    document.getElementById('indicatorModal').style.display = 'none';
}
function openNormaModal() {
    document.getElementById('normaModal').style.display = 'flex';
}
function closeNormaModal() {
    document.getElementById('normaModal').style.display = 'none';
}
window.onclick = function(event) {
    let modalNorma = document.getElementById('normaModal');
    let modalInd = document.getElementById('indicatorModal');
    if (event.target === modalNorma) modalNorma.style.display = 'none';
    if (event.target === modalInd) modalInd.style.display = 'none';
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>