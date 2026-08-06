<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

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

            // Insertar cabecera incluyendo statusId y ver_id inicial por defecto en 1
            $stmtMaster = $pdo->prepare("
                INSERT INTO terminos_condiciones (cliente_id, servicio, estado, statusId, ver_id) 
                VALUES (:cliente_id, :servicio, 'pendiente', 1, 1)
            ");
            $stmtMaster->execute([
                ':cliente_id' => $clienteId,
                ':servicio'   => $servicio
            ]);
            $terminoId = (int)$pdo->lastInsertId();

            // Las 4 actividades requeridas por defecto
            $defaultItems = [
                1 => ['key' => 'carta_contratacion', 'nombre' => 'Carta de Contratación'],
                2 => ['key' => 'frecuencia',          'nombre' => 'Frecuencia'],
                3 => ['key' => 'roles_proyecto',      'nombre' => 'Roles de proyecto'],
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
    // Listado de registros filtrando aquellos cuyo ver_id sea diferente de 0
    $stmtList = $pdo->prepare("
        SELECT tc.*, c.name AS clientName, c.rif AS clientRif
        FROM terminos_condiciones tc
        INNER JOIN clientes c ON tc.cliente_id = c.id
        WHERE tc.ver_id != 0
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
<?php 
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'terminos'; 
include '../main/layout_header.php'; 
?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-folders-line"></i> Términos y Condiciones
        </h1>
        <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
            Gestión y respuesta de cartas de contratación, frecuencia, roles y esquemas de facturación.
        </p>
    </div>
        
    <div class="table-actions-container">
        <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
            <i class="ri-arrow-go-back-line"></i> 
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
            <i class="ri-screenshot-2-line"></i>
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
            <i class="ri-book-open-line"></i> 
        </a>
        
        <button onclick="openModal()" class="btn btn-primary" data-tooltip="Crear Registro">
            <i class="ri-add-line"></i> 
        </button>
        
        <a href="../index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
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
                    <!--<th style="padding: 0.85rem 1rem;">#</th>-->
                    <th style="padding: 0.85rem 1rem;">Cliente</th>
                    <th style="padding: 0.85rem 1rem;">Servicio</th>
                    <th style="padding: 0.85rem 1rem;">Estado</th>
                    <th style="padding: 0.85rem 1rem;">Fecha Creación</th>
                    <th style="padding: 0.85rem 1rem; text-align: center;">Acciones</th>
                    <th style="width: 10%;">Estado</th>
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
                            <?php 
                            $statusId   = (int)($row->statusId ?? 1);
                            $isClosed   = ($statusId === 2);

                            $iconClass  = $isClosed ? 'ri-lock-fill' : 'ri-lock-unlock-line';
                            $iconColor  = $isClosed ? '#0f172a' : '#16a34a'; // Negro para cerrado, Verde para en proceso
                            $tooltip    = $isClosed ? 'Cerrado' : 'En proceso';
                            ?>
                            <td style='text-align: center; vertical-align: middle;'>
                                    <span title='<?= echo $tooltip;?>' style='cursor: help; display: inline-flex; align-items: center;'>
                                        <i class='<?=echo $iconClass;?>' style='font-size: 1.25rem; color: <? echo $iconColor;?>;'></i>
                                    </span>
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