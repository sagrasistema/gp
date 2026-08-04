<?php
declare(strict_types=1);

/**
 * Módulo de Gestión de Clientes - Vista para Modificar Ficha Corporativa
 * PHP Version 8.x
 */

// 1. Validar y sanitizar el parámetro 'id' antes de renderizar cualquier salida
$clienteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$clienteId || $clienteId <= 0) {
    // Redirección limpia al index si el ID no es válido
    header('Location: index.php?error=invalid_id');
    exit;
}

$pageTitle = "Modificar Ficha Corporativa";
include 'header.php'; 
?>
<link rel="stylesheet" href="../main/layout.css">
<?php
// Configuración dinámica del Layout para la carpeta client/
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'inicio'; 

include '../main/layout_header.php'; 
?>

<style>
    .form-grid-complex { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
    .col-4 { grid-column: span 4; }
    .col-3 { grid-column: span 3; }
    .col-2 { grid-column: span 2; }
    .col-1 { grid-column: span 1; }
    .section-title { grid-column: span 4; margin-top: 1rem; padding-bottom: 0.25rem; border-bottom: 2px solid #e2e8f0; font-size: 1.1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
    @media (max-width: 768px) { .form-grid-complex > div { grid-column: span 4 !important; } }
    
    body.dark-mode .section-title {
        border-bottom-color: #334155;
        color: #f8fafc;
    }

    .table-actions-container {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center;
        gap: 0.5rem;
        width: auto;
    }

    .table-actions-container a, 
    .table-actions-container button {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
    }

    .table-actions-container a::after,
    .table-actions-container button::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        background-color: #1e293b;
        color: #ffffff;
        padding: 0.4rem 0.7rem;
        border-radius: 5px;
        font-size: 0.75rem;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 99;
        pointer-events: none;
    }

    .table-actions-container a::before,
    .table-actions-container button::before {
        content: "";
        position: absolute;
        bottom: 110%;
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        border-width: 6px;
        border-style: solid;
        border-color: #1e293b transparent transparent transparent;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 99;
        pointer-events: none;
    }

    .table-actions-container a:hover::after,
    .table-actions-container a:hover::before,
    .table-actions-container button:hover::after,
    .table-actions-container button:hover::before {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    .btn-control-disabled {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.55rem 1rem;
        font-size: 0.85rem;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        background-color: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
        text-decoration: none;
        height: 38px;
        width: 42px;
    }

    .table-actions-container .btn-primary {
        height: 38px;
        width: 42px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    body.dark-mode .btn-control-disabled {
        background-color: #1e293b;
        border-color: #334155;
        color: #64748b;
    }
</style>

<div class="view-container">
    
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-user-add-line"></i> Modificar Ficha de Cliente
        </h1>

        <div class="table-actions-container">
            <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
                <i class="ri-arrow-go-back-line"></i> 
            </a>

            <a href="export_cliente_word.php?id=<?= htmlspecialchars((string)$clienteId, ENT_QUOTES, 'UTF-8') ?>" 
               class="btn btn-primary" 
               data-tooltip="Exportar Ficha a Word" 
               target="_blank">
                <i class="ri-file-word-2-line"></i>
            </a>

            <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
                <i class="ri-screenshot-2-line"></i>
            </a>

            <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
                <i class="ri-book-open-line"></i> 
            </a>

            <button type="button" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
                <i class="ri-add-line"></i>
            </button>

            <a href="index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
                <i class="ri-close-circle-line"></i> 
            </a>
        </div>
    </div>

    <div class="card">
        <!-- El formulario se maneja asíncronamente mediante clients.js -->
        <form id="edit-form" class="form-grid-complex" onsubmit="return false;">
            
            <!-- Campo oculto para asegurar la disponibilidad del ID en JS/DOM -->
            <input type="hidden" id="client-id" value="<?= htmlspecialchars((string)$clienteId, ENT_QUOTES, 'UTF-8') ?>">

            <div class="section-title"><i class="ri-building-line"></i> Datos de la Empresa</div>
            
            <div class="form-group col-2">
                <label for="client-name">Nombre o Razón Social *</label>
                <input type="text" id="client-name" name="name" required>
            </div>
            <div class="form-group col-1">
                <label for="client-rif">Número ID Fiscal (R.I.F)</label>
                <input type="text" id="client-rif" name="rif">
            </div>
            <div class="form-group col-2">
                <label for="client-persona">Persona contacto</label>
                <input type="text" id="client-persona" name="persona">
            </div>
            <div class="form-group col-1">
                <label for="client-cargo">Cargo</label>
                <input type="text" id="client-cargo" name="cargo">
            </div>
            <div class="form-group col-1">
                <label for="client-phone">Teléfono</label>
                <input type="text" id="client-phone" name="phone">
            </div>
            <div class="form-group col-2">
                <label for="client-email">Correo Electrónico</label>
                <input type="email" id="client-email" name="email">
            </div>
            <div class="form-group col-2">
                <label for="client-website">Página Web</label>
                <input type="url" id="client-website" name="website">
            </div>

            <div class="section-title"><i class="ri-map-pin-line"></i> Ubicación Fiscal</div>

            <div class="form-group col-4">
                <label for="client-address">Dirección Fiscal</label>
                <input type="text" id="client-address" name="address">
            </div>
            <div class="form-group col-1">
                <label for="client-city">Ciudad</label>
                <input type="text" id="client-city" name="city">
            </div>
            <div class="form-group col-1">
                <label for="client-state-geo">Estado</label>
                <input type="text" id="client-state-geo" name="state_geo">
            </div>
            <div class="form-group col-1">
                <label for="client-zip">Código Postal</label>
                <input type="text" id="client-zip" name="zip_code">
            </div>
            <div class="form-group col-1">
                <label for="client-country">País</label>
                <input type="text" id="client-country" name="country">
            </div>

            <div class="section-title"><i class="ri-briefcase-line"></i> Segmentación Comercial</div>

            <div class="form-group col-1">
                <label for="client-employees">Nro de Trabajadores</label>
                <input type="text" id="client-employees" name="employees">
            </div>
            <div class="form-group col-1">
                <label for="client-income">Nivel de Ingreso en $</label>
                <input type="text" id="client-income" name="income_level">
            </div>
            <div class="form-group col-1">
                <label for="client-sector">Sector al que Pertenece</label>
                <input type="text" id="client-sector" name="sector">
            </div>
            <div class="form-group col-1">
                <label for="client-service">Servicio Prestado</label>
                <input type="text" id="client-service" name="service">
            </div>
            <div class="form-group col-2">
                <label for="client-sector-desc">Descripción del Sector</label>
                <input type="text" id="client-sector-desc" name="sector_desc">
            </div>
            <div class="form-group col-2">
                <label for="client-service-desc">Descripción del Servicio</label>
                <input type="text" id="client-service-desc" name="service_desc">
            </div>

            <div class="section-title"><i class="ri-global-line"></i> Redes Sociales y Sistema</div>

            <div class="form-group col-1">
                <label for="client-instagram">Instagram</label>
                <input type="text" id="client-instagram" name="instagram">
            </div>
            <div class="form-group col-2">
                <label for="client-linkedin">Linkedin</label>
                <input type="text" id="client-linkedin" name="linkedin">
            </div>
            <div class="form-group col-1">
                <label for="client-status">Estado del Cliente (Sistema)</label>
                <select id="client-status" name="status">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>

            <div class="actions col-4">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-refresh-line"></i> Actualizar Ficha Completa
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Inclusión explícita del script del controlador frontend 

<?php 
// Cierre del layout y footers
include '../main/layout_footer.php'; 
include 'footer.php'; 
?>