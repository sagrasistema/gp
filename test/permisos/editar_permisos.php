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

// Inclusiones estándar del layout del sistema
include '../main/h.php'; 
include '../main/config.php'; 

// Sanitizar y validar ID de usuario objetivo
$usuarioIdTarget = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT) ?? 0;

if ($usuarioIdTarget <= 0) {
    header('Location: index.php');
    exit;
}

$mensajeExito = null;
$mensajeError = null;

// -------------------------------------------------------------------------
// 2. PROCESAMIENTO DE FORMULARIO (GUARDAR PERMISOS)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permisosPost = $_POST['permisos'] ?? [];

    try {
        $pdo->beginTransaction();

        // Obtener todos los módulos activos para iteración segura
        $stmtModulos = $pdo->query("SELECT id FROM modulos WHERE activo = 1");
        $modulosActivos = $stmtModulos->fetchAll(PDO::FETCH_COLUMN);

        // Consulta UPSERT: inserta o actualiza si ya existe el par (usuario_id, modulo_id)
        $sqlUpsert = "
            INSERT INTO usuario_permisos 
                (usuario_id, modulo_id, puede_ver, puede_crear, puede_editar, puede_eliminar)
            VALUES 
                (:usuario_id, :modulo_id, :ver, :crear, :editar, :eliminar)
            ON DUPLICATE KEY UPDATE
                puede_ver = VALUES(puede_ver),
                puede_crear = VALUES(puede_crear),
                puede_editar = VALUES(puede_editar),
                puede_eliminar = VALUES(puede_eliminar)
        ";

        $stmtSave = $pdo->prepare($sqlUpsert);

        foreach ($modulosActivos as $mId) {
            $mId = (int)$mId;

            // Extraer valor (1 si fue marcado en el POST, 0 si no)
            $ver      = isset($permisosPost[$mId]['ver']) ? 1 : 0;
            $crear    = isset($permisosPost[$mId]['crear']) ? 1 : 0;
            $editar   = isset($permisosPost[$mId]['editar']) ? 1 : 0;
            $eliminar = isset($permisosPost[$mId]['eliminar']) ? 1 : 0;

            $stmtSave->execute([
                ':usuario_id' => $usuarioIdTarget,
                ':modulo_id'  => $mId,
                ':ver'        => $ver,
                ':crear'      => $crear,
                ':editar'     => $editar,
                ':eliminar'   => $eliminar
            ]);
        }

        $pdo->commit();
        $mensajeExito = "Los permisos han sido actualizados correctamente.";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al guardar permisos para el usuario {$usuarioIdTarget}: " . $e->getMessage());
        $mensajeError = "Ocurrió un error al procesar la actualización en el servidor.";
    }
}

// -------------------------------------------------------------------------
// 3. CONSULTA DE DATOS PARA LA VISTA
// -------------------------------------------------------------------------
$usuarioTarget = null;
$matrizPermisos = [];

try {
    // A. Obtener datos del usuario
    $stmtUser = $pdo->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE id = :id LIMIT 1");
    $stmtUser->execute([':id' => $usuarioIdTarget]);
    $usuarioTarget = $stmtUser->fetch(PDO::FETCH_OBJ);

    if (!$usuarioTarget) {
        header('Location: index.php');
        exit;
    }

    // B. Obtener módulos y permisos asignados mediante LEFT JOIN
    $sqlPermisos = "
        SELECT 
            m.id AS modulo_id,
            m.nombre AS modulo_nombre,
            m.descripcion AS modulo_descripcion,
            COALESCE(up.puede_ver, 0) AS puede_ver,
            COALESCE(up.puede_crear, 0) AS puede_crear,
            COALESCE(up.puede_editar, 0) AS puede_editar,
            COALESCE(up.puede_eliminar, 0) AS puede_eliminar
        FROM modulos m
        LEFT JOIN usuario_permisos up 
            ON m.id = up.modulo_id AND up.usuario_id = :usuario_id
        WHERE m.activo = 1
        ORDER BY m.id ASC
    ";

    $stmtPerms = $pdo->prepare($sqlPermisos);
    $stmtPerms->execute([':usuario_id' => $usuarioIdTarget]);
    $matrizPermisos = $stmtPerms->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al cargar la matriz de permisos: " . $e->getMessage());
    $mensajeError = "Error de comunicación con la base de datos.";
}

// Variables globales para layout_header.php
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'usuarios'; 

?>

<link rel="stylesheet" href="../main/layout.css">
<style>
    /* Estilos para casillas de verificación de permisos */
    .permission-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #08855b;
    }
    
    .user-info-card {
        background: #ffffff;
        border: 1px solid var(--border-color, #cbd5e1);
        border-radius: 6px;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: flex;
        gap: 1.5rem;
        align-items: center;
        font-size: 0.85rem;
    }

    .alert-banner {
        padding: 0.6rem 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-danger { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
</style>

<?php include '../main/layout_header.php'; ?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-shield-keyhole-line"></i> Asignación de Permisos por Módulo
        </h1>
    </div>

    <!-- Acciones / Controles Superiores -->
    <div class="table-actions-container">
        <a href="index.php" class="btn btn-primary" data-tooltip="Volver a Usuarios">
            <i class="ri-arrow-go-back-line"></i>
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
            <i class="ri-book-open-line"></i>
        </a>

        <a href="index.php" class="btn btn-primary" data-tooltip="Cancelar">
            <i class="ri-close-circle-line"></i>
        </a>
    </div>

    <?php if ($mensajeExito): ?>
        <div class="alert-banner alert-success">
            <i class="ri-checkbox-circle-line"></i> <?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($mensajeError): ?>
        <div class="alert-banner alert-danger">
            <i class="ri-error-warning-line"></i> <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Ficha Informativa del Usuario Objetivo -->
    <div class="user-info-card">
        <div>
            <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Usuario:</span>
            <strong style="color: #1e293b; margin-left: 0.25rem;"><?= htmlspecialchars($usuarioTarget->nombre ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Correo:</span>
            <span style="color: #1e293b; margin-left: 0.25rem;"><?= htmlspecialchars($usuarioTarget->email ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div>
            <span style="color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Rol Actual:</span>
            <span class="badge" style="background: #e2e8f0; color: #334155; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600; font-size: 0.75rem;">
                <?= htmlspecialchars($usuarioTarget->rol ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>

    <!-- Formulario de Matriz de Permisos -->
    <form method="POST" action="editar_permisos.php?usuario_id=<?= $usuarioIdTarget ?>">
        <div class="table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 40%;">Módulo / Sección</th>
                        <th style="width: 15%; text-align: center;">Ver</th>
                        <th style="width: 15%; text-align: center;">Crear</th>
                        <th style="width: 15%; text-align: center;">Editar</th>
                        <th style="width: 15%; text-align: center;">Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($matrizPermisos)): ?>
                        <?php foreach ($matrizPermisos as $m): ?>
                            <?php $mId = (int)$m->modulo_id; ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($m->modulo_nombre, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($m->modulo_descripcion)): ?>
                                        <br><small style="color: #64748b; font-size: 0.75rem;"><?= htmlspecialchars($m->modulo_descripcion, ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Permiso Ver -->
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="checkbox" 
                                           name="permisos[<?= $mId ?>][ver]" 
                                           value="1" 
                                           class="permission-checkbox"
                                           <?= (int)$m->puede_ver === 1 ? 'checked' : '' ?>>
                                </td>

                                <!-- Permiso Crear -->
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="checkbox" 
                                           name="permisos[<?= $mId ?>][crear]" 
                                           value="1" 
                                           class="permission-checkbox"
                                           <?= (int)$m->puede_crear === 1 ? 'checked' : '' ?>>
                                </td>

                                <!-- Permiso Editar -->
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="checkbox" 
                                           name="permisos[<?= $mId ?>][editar]" 
                                           value="1" 
                                           class="permission-checkbox"
                                           <?= (int)$m->puede_editar === 1 ? 'checked' : '' ?>>
                                </td>

                                <!-- Permiso Eliminar -->
                                <td style="text-align: center; vertical-align: middle;">
                                    <input type="checkbox" 
                                           name="permisos[<?= $mId ?>][eliminar]" 
                                           value="1" 
                                           class="permission-checkbox"
                                           <?= (int)$m->puede_eliminar === 1 ? 'checked' : '' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #64748b; padding: 2rem;">
                                No se encontraron módulos activos registrados en la base de datos.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Botón para Guardar Cambios -->
        <div style="margin-top: 1rem; text-align: right;">
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem; background: #08855b; border-color: #08855b; font-weight: 600; cursor: pointer;">
                <i class="ri-save-3-line"></i> Guardar Permisos
            </button>
        </div>
    </form>
</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>