<?php 
$pageTitle = "Control de Clientes";
include 'header.php'; // Tu archivo de conexión / inicialización local de la carpeta client
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
// Configuración dinámica del Layout para la carpeta client/
$customLogoPath = '../main/logo.png'; // Ruta para llegar al logo original del sistema
$customHomePath = '../index.php';     // Ruta para volver al HUB principal
$customAcPath   = '../ac/index.php';  // Ruta para ir al módulo AC
$currentTab     = 'inicio';           // Podemos dejar 'inicio' o definir una pestaña para clientes si la creas luego

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
                    <th style="width: 25%;">Cliente / Empresa</th>
                    <th style="width: 20%;">Correo Electrónico</th>
                    <th style="width: 15%;">Teléfono</th>
                    <th style="width: 15%;">Sector</th>
                    <th style="width: 13%;">Estado</th>
                    <th style="width: 12%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody id="table-body">
                </tbody>
        </table>
    </div>
</div>

<?php 
// Renderiza el cierre del layout, barra lateral y los scripts de interacción móvil
include '../main/layout_footer.php'; 

// Renderiza los scripts del pie de página de clientes (donde seguramente cargas tu JS para rellenar la tabla)
include 'footer.php'; 
?>