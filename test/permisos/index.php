<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación activa de sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include '../main/config.php'; 

$usuarios = [];
$errorAlert = null;

try {
    $pdo = getPDOConnection();
    
    // Consulta optimizada seleccionando solo los campos requeridos
    $stmt = $pdo->prepare("
        SELECT id, nombre, email, rol, activo 
        FROM usuarios 
        ORDER BY id ASC
    ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error en index.php: " . $e->getMessage());
    $errorAlert = "Ocurrió un problema al cargar el listado de usuarios.";
}

$pageTitle = "Gestión de Usuarios y Permisos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Remixicon CDN para iconos -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a5f;
            --accent-color: #00bcd4;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
            --text-color: #1e293b;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-color);
            margin: 0;
            padding: 1.5rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            padding: 1.25rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .custom-table th, .custom-table td {
            padding: 0.6rem 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .custom-table th {
            background-color: var(--primary-color);
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .custom-table tr:hover {
            background-color: #f1f5f9;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            border-radius: 4px;
        }

        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background-color: #f1f5f9;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background-color: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-title">
        <i class="ri-user-settings-line" style="color: var(--accent-color);"></i>
        <h2>Administración de Usuarios y Permisos</h2>
    </div>

    <?php if ($errorAlert): ?>
        <div class="alert-error">
            <i class="ri-error-warning-line"></i> <?= htmlspecialchars($errorAlert, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo Electrónico</th>
                    <th>Rol</th>
                    <th>Estatus</th>
                    <th style="text-align: center;">Permisos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usuarios)): ?>
                    <?php foreach ($usuarios as $usr): ?>
                        <tr>
                            <td><?= (int)$usr['id'] ?></td>
                            <td><strong><?= htmlspecialchars($usr['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($usr['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($usr['rol'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (!empty($usr['activo'])): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="editar_permisos.php?usuario_id=<?= (int)$usr['id'] ?>" 
                                   class="btn-icon" 
                                   title="Editar Permisos de <?= htmlspecialchars($usr['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="ri-pencil-line"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 1.5rem;">
                            No se encontraron usuarios registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>