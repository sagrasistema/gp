<?php
declare(strict_types=1);

// 1. Iniciar sesión obligatoriamente antes de procesar lógica o incluir layouts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = "Control de Clientes";
include 'header.php'; // Archivo de conexión / inicialización local de la carpeta client
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
// Configuración dinámica del Layout para la carpeta client/
$customLogoPath = '../main/logo.png'; 
$customHomePath = '../index.php';     
$customAcPath   = '../ac/index.php';  
$currentTab     = 'clientes';         

include '../main/layout_header.php'; 
?>

<div class="view-container">
    
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-team-line"></i> Control de Clientes
        </h1>

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

            <a href="nuevo.php" class="btn btn-primary" data-tooltip="Crear Registro">
                <i class="ri-add-line"></i>
            </a>

            <a href="../index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
                <i class="ri-close-circle-line"></i> 
            </a>
        </div>
    </div>

    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 30%;">Cliente / Empresa</th>
                    <th style="width: 25%;">Correo Electrónico</th>
                    <th style="width: 15%;">Teléfono</th>
                    <th style="width: 20%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    // Consulta con filtro estricto de visibilidad por ver_id
                    $query = "SELECT id, name, email, phone, created_at, ver_id 
                              FROM clientes 
                              WHERE ver_id != 0 
                              ORDER BY id DESC";
                              
                    $stmt = $pdo->query($query);
                    $clientes = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
                    if (!empty($clientes)) {
                        foreach ($clientes as $cli) {
                            $id    = (int)$cli->id;
                            $name  = htmlspecialchars($cli->name, ENT_QUOTES, 'UTF-8');
                            $email = htmlspecialchars($cli->email ?? 'N/A', ENT_QUOTES, 'UTF-8');
                            $phone = htmlspecialchars($cli->phone ?? 'N/A', ENT_QUOTES, 'UTF-8');

                            echo "<tr>";
                            echo "<td style='font-weight: 600; color: #64748b;'>#{$id}</td>";
                            echo "<td><strong>{$name}</strong></td>";
                            echo "<td>{$email}</td>";
                            echo "<td>{$phone}</td>";
                            echo "<td style='text-align: center; white-space: nowrap;'>";
                            
                            // Botón Editar / Gestionar Cliente
                            echo "<a href='editar.php?id={$id}' class='btn btn-secondary' style='padding: 0.4rem 0.6rem; font-size: 0.8rem; margin-right: 4px;' data-tooltip='Editar Cliente'>";
                            echo "<i class='ri-edit-line'></i>";
                            echo "</a>";

                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align: center; color: #64748b; padding: 3rem;'>No se han encontrado clientes registrados.</td></tr>";
                    }
                } catch (PDOException $e) {
                    error_log("Error al listar clientes en client/index.php: " . $e->getMessage());
                    echo "<tr><td colspan='5' style='text-align: center; color: red; padding: 2rem;'>Error al cargar los clientes desde el servidor.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Renderiza el cierre del layout, barra lateral y los scripts de interacción móvil
include '../main/layout_footer.php'; 

// Renderiza los scripts del pie de página de clientes
include 'footer.php'; 
?>