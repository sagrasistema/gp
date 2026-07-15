<?php 
$pageTitle = "Modificar Ficha Corporativa";
include 'header.php'; ?>
<link rel="stylesheet" href="../main/layout.css">
<?php
// Configuración dinámica del Layout para la carpeta client/ (Estándar unificado)
$customLogoPath = '../main/logo.png'; // Ruta para llegar al logo original del sistema
$customHomePath = '../index.php';     // Ruta para volver al HUB principal
$customAcPath   = '../ac/index.php';  // Ruta para ir al módulo AC
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
        /* Modo oscuro adaptado para títulos de sección del formulario */
    body.dark-mode .section-title {
        border-bottom-color: #334155;
        color: #f8fafc;
    }

    /* --- CONTENEDOR PRINCIPAL DE BOTONES DE CONTROL (FORZADO A LA DERECHA) --- */
    .table-actions-container {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center;
        gap: 0.5rem;
        width: auto;
    }

    /* Posicionamiento relativo individual para los tooltips */
    .table-actions-container a, 
    .table-actions-container button {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
    }

    /* El globo del Tooltip */
    .table-actions-container a::after,
    .table-actions-container button::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 125%; /* Lo ubica justo arriba del botón */
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        background-color: #1e293b; /* Fondo oscuro elegante */
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
        pointer-events: none; /* Evita interferir con los clics */
    }

    /* La pequeña flecha del Tooltip */
    .table-actions-container a::before,
    .table-actions-container button::before {
        content: "";
        position: absolute;
        bottom: 110%;
        left: 50%;
        transform: translateX(-50%) translateY(5px);
        border-width: 6px;
        border-style: solid;
        border-color: #1e293b transparent transparent transparent; /* Flecha apuntando abajo */
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 99;
        pointer-events: none;
    }

    /* Acción Hover: Muestra el tooltip con un efecto de deslizamiento hacia arriba */
    .table-actions-container a:hover::after,
    .table-actions-container a:hover::before,
    .table-actions-container button:hover::after,
    .table-actions-container button:hover::before {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    /* Ajuste para que los botones deshabilitados tengan el cursor correcto y sí muestren el tooltip */
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
        cursor: not-allowed; /* Muestra el icono de prohibido */
        text-decoration: none;
        height: 38px;
        width: 42px; /* Caja cuadrada idéntica */
    }

    /* Botón primary normalizado para coincidir en tamaño */
    .table-actions-container .btn-primary {
        height: 38px;
        width: 42px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    /* Modo oscuro para el botón deshabilitado */
    body.dark-mode .btn-control-disabled {
        background-color: #1e293b;
        border-color: #334155;
        color: #64748b;
    }
</style>

<div class="container" style="max-width: 1100px;">
    <header>
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

            <button href="#" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
                <i class="ri-add-line"></i>
            </button>

            <a href="index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
                <i class="ri-close-circle-line"></i> 
            </a>
        </div>
    </header>

    <div class="card">
        <form id="edit-form" class="form-grid-complex">
            
            <div class="section-title"><i class="ri-building-line"></i> Datos de la Empresa</div>
            
            <div class="form-group col-2">
                <label>Nombre o Razón Social *</label>
                <input type="text" id="client-name" required>
            </div>
            <div class="form-group col-1">
                <label>Número ID Fiscal (R.I.F)</label>
                <input type="text" id="client-rif">
            </div>
            <div class="form-group col-1">
                <label>Teléfono</label>
                <input type="text" id="client-phone">
            </div>
            <div class="form-group col-2">
                <label>Correo Electrónico</label>
                <input type="email" id="client-email">
            </div>
            <div class="form-group col-2">
                <label>Página Web</label>
                <input type="url" id="client-website">
            </div>

            <div class="section-title"><i class="ri-map-pin-line"></i> Ubicación Fiscal</div>

            <div class="form-group col-4">
                <label>Dirección Fiscal</label>
                <input type="text" id="client-address">
            </div>
            <div class="form-group col-1">
                <label>Ciudad</label>
                <input type="text" id="client-city">
            </div>
            <div class="form-group col-1">
                <label>Estado</label>
                <input type="text" id="client-state-geo">
            </div>
            <div class="form-group col-1">
                <label>Código Postal</label>
                <input type="text" id="client-zip">
            </div>
            <div class="form-group col-1">
                <label>País</label>
                <input type="text" id="client-country">
            </div>

            <div class="section-title"><i class="ri-briefcase-line"></i> Segmentación Comercial</div>

            <div class="form-group col-1">
                <label>Nro de Trabajadores</label>
                <input type="text" id="client-employees">
            </div>
            <div class="form-group col-1">
                <label>Nivel de Ingreso en $</label>
                <input type="text" id="client-income">
            </div>
            <div class="form-group col-1">
                <label>Sector al que Pertenece</label>
                <input type="text" id="client-sector">
            </div>
            <div class="form-group col-1">
                <label>Servicio Prestado</label>
                <input type="text" id="client-service">
            </div>
            <div class="form-group col-2">
                <label>Descripción del Sector</label>
                <input type="text" id="client-sector-desc">
            </div>
            <div class="form-group col-2">
                <label>Descripción del Servicio</label>
                <input type="text" id="client-service-desc">
            </div>

            <div class="section-title"><i class="ri-global-line"></i> Redes Sociales y Sistema</div>

            <div class="form-group col-1">
                <label>Instagram</label>
                <input type="text" id="client-instagram">
            </div>
            <div class="form-group col-2">
                <label>Linkedin</label>
                <input type="text" id="client-linkedin">
            </div>
            <div class="form-group col-1">
                <label>Estado del Cliente (Sistema)</label>
                <select id="client-status">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>

            <div class="actions col-4">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="ri-refresh-line"></i> Actualizar Ficha Completa</button>
            </div>
        </form>
    </div>
</div>

<?php 
// Renderiza el cierre del layout, barra lateral y los scripts de interacción móvil
include '../main/layout_footer.php'; 
include 'footer.php'; ?>