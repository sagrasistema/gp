<?php

declare(strict_types=1);

// v/terminos/index.php
include '../main/config.php';

// -------------------------------------------------------------------------
// 1. PROCESAR CREACIÓN DE TÉRMINOS Y CONDICIONES (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_termino'])) {
    $clienteId = filter_input(INPUT_POST, 'cliente_id', FILTER_VALIDATE_INT);
    $servicio  = filter_input(INPUT_POST, 'servicio', FILTER_SANITIZE_SPECIAL_CHARS);
    $servicio  = $servicio ? trim($servicio) : '';

    if (!$clienteId || empty($servicio)) {
        $errorMessage = "Debe seleccionar un cliente y especificar el servicio.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insertar cabecera de Términos y Condiciones
            $stmtMaster = $pdo->prepare("
                INSERT INTO terminos_condiciones (cliente_id, servicio, estado) 
                VALUES (:cliente_id, :servicio, 'pendiente')
            ");
            $stmtMaster->execute([
                ':cliente_id' => $clienteId,
                ':servicio'   => $servicio
            ]);
            $terminoId = (int)$pdo->lastInsertId();

            // Las 4 actividades requeridas por defecto
            $defaultItems = [
                1 => ['key' => 'carta_contratacion', 'nombre' => 'Carta de Contratación'],
                2 => ['key' => 'frecuencia',           'nombre' => 'Frecuencia'],
                3 => ['key' => 'roles_proyecto',       'nombre' => 'Roles de proyecto'],
                4 => ['key' => 'esquema_facturacion',  'nombre' => 'Esquema de facturación']
            ];

            // Inicializar las 4 actividades
            $stmtItem = $pdo->prepare("
                INSERT INTO terminos_condiciones_items (termino_id, item_key, item_nombre, orden, estado) 
                VALUES (:termino_id, :item_key, :item_nombre, :orden, 'pendiente')
            ");

            foreach ($defaultItems as $orden => $item) {
                $stmtItem->execute([
                    ':termino_id'  => $terminoId,
                    ':item_key'    => $item['key'],
                    ':item_nombre' => $item['nombre'],
                    ':orden'       => $orden
                ]);
            }

            $pdo->commit();

            header("Location: index.php?success=created");
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al crear Términos y Condiciones: " . $e->getMessage());
            $errorMessage = "Ocurrió un error al registrar en la base de datos.";
        }
    }
}

// -------------------------------------------------------------------------
// 2. CONSULTAR DATOS PARA LA VISTA
// -------------------------------------------------------------------------
try {
    // Listado de registros creados
    $stmtList = $pdo->prepare("
        SELECT tc.*, c.name AS clientName, c.rif AS clientRif
        FROM terminos_condiciones tc
        INNER JOIN clientes c ON tc.cliente_id = c.id
        ORDER BY tc.id DESC
    ");
    $stmtList->execute();
    $records = $stmtList->fetchAll(PDO::FETCH_OBJ);

    // Listado de clientes activos para el Modal/Formulario
    $stmtClientes = $pdo->prepare("SELECT id, name FROM clientes ORDER BY name ASC");
    $stmtClientes->execute();
    $clientesList = $stmtClientes->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al consultar módulo de Términos y Condiciones: " . $e->getMessage());
    die("Error al cargar los datos del módulo.");
}

$pageTitle = "Módulo de Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container" style="padding: 2rem; max-width: 1200px; margin: 0 auto;">

    <!-- CABECERA DE LA VISTA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-file-text-line" style="color: #2563eb;"></i> Términos y Condiciones
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Gestión y respuesta de cartas de contratación, frecuencia, roles y esquemas de facturación.
            </p>
        </div>
        <button onclick="openModal()" class="btn btn-primary" style="padding: 0.6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
            <i class="ri-add-line"></i> Crear Términos y Condiciones
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Términos y Condiciones creados e inicializados correctamente.
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- TABLA DE REGISTROS -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 700;">
                    <th style="padding: 0.85rem 1rem;">#</th>
                    <th style="padding: 0.85rem 1rem;">Cliente</th>
                    <th style="padding: 0.85rem 1rem;">Servicio</th>
                    <th style="padding: 0.85rem 1rem;">Estado</th>
                    <th style="padding: 0.85rem 1rem;">Fecha Creación</th>
                    <th style="padding: 0.85rem 1rem; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6" style="padding: 2rem; text-align: center; color: #94a3b8;">
                            No existen términos y condiciones registrados. Haga clic en <strong>Crear Términos y Condiciones</strong> para iniciar uno.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $index => $row): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.85rem 1rem; font-weight: 600; color: #64748b;"><?= $index + 1 ?></td>
                            <td style="padding: 0.85rem 1rem; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($row->clientName, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 0.85rem 1rem; color: #334155;"><?= htmlspecialchars($row->servicio, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding: 0.85rem 1rem;">
                                <?php if ($row->estado === 'completado'): ?>
                                    <span style="background: #dcfce7; color: #15803d; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 600;">Completado</span>
                                <?php elseif ($row->estado === 'en_proceso'): ?>
                                    <span style="background: #fef3c7; color: #b45309; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 600;">En Proceso</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #64748b; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 600;">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.85rem 1rem; color: #64748b; font-size: 0.85rem;">
                                <?= date('d/m/Y H:i', strtotime($row->created_at)) ?>
                            </td>
                            <td style="padding: 0.85rem 1rem; text-align: center;">
                                <a href="responder-terminos.php?id=<?= $row->id ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; background: #0f172a; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: 600;">
                                    <i class="ri-reply-fill"></i> Responder
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA CREAR NUEVO REGISTRO -->
<div id="modalCreate" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div style="background: #ffffff; width: 100%; max-width: 500px; border-radius: 8px; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #0f172a;">Crear Términos y Condiciones</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="index.php">
            <input type="hidden" name="action_create_termino" value="1">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem;">Cliente *</label>
                <select name="cliente_id" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                    <option value="">-- Seleccione un Cliente --</option>
                    <?php foreach ($clientesList as $cli): ?>
                        <option value="<?= $cli->id ?>"><?= htmlspecialchars($cli->name, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem;">Servicio *</label>
                <input type="text" name="servicio" placeholder="Ej. Auditoría de Estados Financieros 2026" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary" style="padding: 0.5rem 1rem; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; cursor: pointer;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Guardar y Crear</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalCreate').style.display = 'flex';
}
function closeModal() {
    document.getElementById('modalCreate').style.display = 'none';
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>