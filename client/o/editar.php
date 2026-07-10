<?php 
$pageTitle = "Modificar Ficha Corporativa";
include 'header.php'; 
?>

<style>
    .form-grid-complex { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
    .col-4 { grid-column: span 4; }
    .col-3 { grid-column: span 3; }
    .col-2 { grid-column: span 2; }
    .col-1 { grid-column: span 1; }
    .section-title { grid-column: span 4; margin-top: 1rem; padding-bottom: 0.25rem; border-bottom: 2px solid #e2e8f0; font-size: 1.1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
    @media (max-width: 768px) { .form-grid-complex > div { grid-column: span 4 !important; } }
</style>

<div class="container" style="max-width: 1100px;">
    <header>
        <a href="index.php" class="btn-back"><i class="ri-arrow-left-line"></i></a>
        <h1>Modificar Datos del Cliente</h1>
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

<?php include 'footer.php'; ?>