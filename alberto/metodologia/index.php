<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Carga de configuración y conexión PDO
require_once '../main/config.php';

$mensaje = '';
$error = '';

// Procesar creación de un nuevo servicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'crear_servicio') {
    $nombreServicio = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombreServicio)) {
        $error = 'El nombre del servicio es obligatorio.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare("
                INSERT INTO audit_servicios (nombre, descripcion, estatus) 
                VALUES (:nombre, :descripcion, 1)
            ");
            $stmtIns->execute([
                ':nombre' => $nombreServicio,
                ':descripcion' => $descripcion
            ]);
            $servicioId = (int)$pdo->lastInsertId();

            // Garantizar la inserción de las 4 etapas fijas por servicio
            $etapasEstandar = [
                1 => 'Planificación',
                2 => 'Estrategia',
                3 => 'Ejecución',
                4 => 'Conclusión'
            ];

            $stmtEtapa = $pdo->prepare("
                INSERT INTO audit_etapas (id_enum, servicio_id, nombre) 
                VALUES (:id_enum, :servicio_id, :nombre)
            ");

            foreach ($etapasEstandar as $orden => $nombreEtapa) {
                $stmtEtapa->execute([
                    ':id_enum' => $orden,
                    ':servicio_id' => $servicioId,
                    ':nombre' => $nombreEtapa
                ]);
            }

            $pdo->commit();
            $mensaje = 'Servicio configurado con éxito junto a sus 4 etapas estándar.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error al crear servicio: " . $e->getMessage());
            $error = 'Ocurrió un error al intentar registrar el servicio.';
        }
    }
}

// Consultar servicios
try {
    $stmtServicios = $pdo->query("SELECT * FROM audit_servicios ORDER BY id ASC");
    $servicios = $stmtServicios->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al consultar servicios: " . $e->getMessage());
    $servicios = [];
}

// Incluir el Header / Navbar de la plantilla del sistema si existe
if (file_exists('../v/header.php')) {
    require_once '../v/header.php';
} elseif (file_exists('../includes/header.php')) {
    require_once '../includes/header.php';
} else {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Metodología - Servicios de Auditoría</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
</head>
<body>
<?php } ?>

<!-- CONTENIDO PRINCIPAL DE LA VISTA METODOLOGÍA -->
<div class="main-content" style="padding: 20px;">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h2 style="margin: 0; color: #1e3a5f;">Metodología de Servicios</h2>
                <p class="text-muted" style="color: #6c757d; margin: 0;">Gestión de servicios, etapas, categorías, pruebas y actividades</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openModalServicio()" style="padding: 10px 18px; background: #1e3a5f; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                <i class="ri-add-line"></i> + Crear Nuevo Servicio
            </button>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success" style="padding: 12px; background: #d1fae5; color: #065f46; border-radius: 6px; margin-bottom: 20px;">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 6px; margin-bottom: 20px;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Grilla de Servicios -->
        <div class="row" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
            <?php foreach ($servicios as $serv): ?>
                <div class="card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3 style="margin: 0 0 10px 0; color: #1e293b; font-size: 1.2rem;"><?= htmlspecialchars($serv['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Activo</span>
                        </div>
                        <p style="color: #64748b; font-size: 0.875rem; margin-bottom: 15px;">
                            <?= htmlspecialchars($serv['descripcion'] ?: 'Sin descripción asignada.', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div style="text-align: right; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 10px;">
                        <a href="servicio_etapas.php?servicioId=<?= (int)$serv['id'] ?>" class="btn btn-outline-primary" style="background: transparent; color: #0284c7; border: 1px solid #0284c7; padding: 6px 14px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: inline-block;">
                            Configurar Metodología &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal para Crear Servicio -->
<div id="modalServicio" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; padding: 25px; border-radius: 10px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.25rem;">Crear Nuevo Servicio</h3>
        <form action="index.php" method="POST">
            <input type="hidden" name="action_type" value="crear_servicio">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Nombre del Servicio *</label>
                <input type="text" name="nombre" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px; color: #334155;">Descripción</label>
                <textarea name="descripcion" rows="3" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"></textarea>
            </div>

            <div style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModalServicio()" style="padding: 8px 16px; background: #e2e8f0; color: #475569; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancelar</button>
                <button type="submit" style="padding: 8px 18px; background: #1e3a5f; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Guardar Servicio</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalServicio() { document.getElementById('modalServicio').style.display = 'flex'; }
function closeModalServicio() { document.getElementById('modalServicio').style.display = 'none'; }
</script>

<?php 
if (file_exists('../main/footer.php')) {
    require_once '../main/footer.php';
} elseif (file_exists('../main/footer.php')) {
    require_once '../main/footer.php';
} else {
    echo '</body></html>';
}
?>