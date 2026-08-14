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

// v/usuarios/index.php
include '../main/h.php'; 
include '../main/config.php'; 
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'permiso'; 

include '../main/layout_header.php';
?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-user-settings-line"></i> Control de Usuarios y Permisos
        </h1>
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

        <a href="nuevo.php" class="btn btn-primary" data-tooltip="Crear Usuario">
            <i class="ri-add-line"></i>
        </a>

        <a href="../index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>

    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Nombre Completo</th>
                    <th style="width: 30%;">Correo Electrónico</th>
                    <th style="width: 15%;">Rol</th>
                    <th style="width: 15%; text-align: center;">Acciones</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    // Consulta de usuarios utilizando la conexión $pdo heredada de config.php
                    $query = "SELECT id, nombre_completo, username, rol
                              FROM usuarios 
                              ORDER BY id ASC";
                              
                    $stmt = $pdo->query($query);
                    $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
                    if (!empty($usuarios)) {
                        foreach ($usuarios as $usr) {
                            $nombre = htmlspecialchars($usr->nombre_completo ?? '', ENT_QUOTES, 'UTF-8');
                            $email  = htmlspecialchars($usr->username ?? '', ENT_QUOTES, 'UTF-8');
                            $rol    = htmlspecialchars($usr->rol ?? 'Usuario', ENT_QUOTES, 'UTF-8');

                            echo "<tr>";
                            echo "<td><strong>{$nombre}</strong></td>";
                            echo "<td>{$email}</td>";
                            echo "<td>{$rol}</td>";
                            echo "<td style='text-align: center; white-space: nowrap;'>";
                            
                            // Botón para Editar Permisos por Usuario (editar_permisos.php)
                            echo "<a href='editar_permisos.php?usuario_id={$usr->id}' class='btn btn-secondary' style='padding: 0.4rem 0.8rem; background: #08855b; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: 600;' data-tooltip='Gestionar Permisos'>";
                            echo "<i class='ri-pencil-fill'></i> Permisos";
                            echo "</a>";

                            echo "</td>";
                             
                            
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; color: #64748b; padding: 3rem;'>No se han encontrado usuarios registrados.</td></tr>";
                    }
                } catch (PDOException $e) {
                    error_log("Error al listar usuarios en index.php: " . $e->getMessage());
                    echo "<tr><td colspan='5' style='text-align: center; color: red; padding: 2rem;'>Error al cargar el listado de usuarios desde el servidor.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>