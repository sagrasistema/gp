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
include 'header.php'; 
include 'config.php'; // Archivo de conexión / inicialización local de la carpeta client
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

            <!-- Botón de exportación requerido por el script JS -->
            <button id="btn-export" class="btn btn-secondary" data-tooltip="Exportar CSV">
                <i class="ri-file-excel-line"></i>
            </button>
           
            <?php     // 3. Uso directo en verificacioness
                if ($permisosModulo5['puede_crear'] == 1) {?>                   
                    <a href="nuevo.php" class="btn btn-primary" data-tooltip="Crear Registro">
                        <i class="ri-add-line"></i>
                    </a>
            <?php } else {?>
                <a href="#" class="btn-control-disabled" data-tooltip="Crear Registro">
                        <i class="ri-add-line"></i>
                    </a>
            <?php } ?>    

            <a href="../index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
                <i class="ri-close-circle-line"></i> 
            </a>
        </div>

    </div>

    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Cliente / Empresa</th>
                    <th style="width: 20%;">Correo Electrónico</th>
                    <th style="width: 15%;">Teléfono</th>
                    <th style="width: 15%;">Sector</th>
                    <th style="width: 13%;">Estado</th>
                    <th style="width: 12%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <!-- Los datos se cargan de forma asíncrona mediante JavaScript desde api.php -->
            </tbody>
        </table>
    </div>
</div>

<?php 
// Renderiza el cierre del layout, barra lateral y los scripts de interacción móvil
include '../main/layout_footer.php'; 

// Renderiza los scripts del pie de página de clientes (incluye el JS analizado previamente)
include 'footer.php'; 
?>