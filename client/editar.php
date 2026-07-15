<?php 
$pageTitle = "Modificar Ficha Corporativa";
include 'header.php';
// Configuración de rutas para la cabecera dinámica
$customLogoPath = "../logo.png";
$customHomePath = "../index.php"; 
$customAcPath = "../ac/index.php";

include '../main/layout_header.php'; 
?>

<link rel="stylesheet" href="../main/layout.css">

<style>
    .form-grid-complex { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
    .col-4 { grid-column: span 4; }
    .col-3 { grid-column: span 3; }
    .col-2 { grid-column: span 2; }
    .col-1 { grid-column: span 1; }
    .section-title { grid-column: span 4; margin-top: 1rem; padding-bottom: 0.25rem; border-bottom: 2px solid #e2e8f0; font-size: 1.1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
    @media (max-width: 768px) { .form-grid-complex > div { grid-column: span 4 !important; } }
</style>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">Modificar Datos del Cliente</h1>

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
    </div>        
    </div>

    <div class="card">
        <form id="edit-form" class="form-grid-complex">
            <input type="hidden" id="client-id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            
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

            <div class="actions col-4" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="ri-refresh-line"></i> Actualizar Ficha Completa</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const clientId = document.getElementById('client-id').value;

    if (!clientId) {
        alert("ID de cliente no proporcionado o inválido.");
        window.location.href = "index.php";
        return;
    }

    // 1. CARGAR DATOS ACTUALES DEL CLIENTE
    fetch(`../api/api.php?action=getClient&id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const client = data.data;
                document.getElementById('client-name').value = client.clientName || '';
                document.getElementById('client-rif').value = client.clientRif || '';
                document.getElementById('client-phone').value = client.clientPhone || '';
                document.getElementById('client-email').value = client.clientEmail || '';
                document.getElementById('client-website').value = client.clientWebsite || '';
                document.getElementById('client-address').value = client.clientAddress || '';
                document.getElementById('client-city').value = client.clientCity || '';
                document.getElementById('client-state-geo').value = client.clientStateGeo || '';
                document.getElementById('client-zip').value = client.clientZip || '';
                document.getElementById('client-country').value = client.clientCountry || '';
                document.getElementById('client-employees').value = client.clientEmployees || '';
                document.getElementById('client-income').value = client.clientIncome || '';
                document.getElementById('client-sector').value = client.clientSector || '';
                document.getElementById('client-service').value = client.clientService || '';
                document.getElementById('client-sector-desc').value = client.clientSectorDesc || '';
                document.getElementById('client-service-desc').value = client.clientServiceDesc || '';
                document.getElementById('client-instagram').value = client.clientInstagram || '';
                document.getElementById('client-linkedin').value = client.clientLinkedin || '';
                document.getElementById('client-status').value = client.clientStatus || 'Activo';
            } else {
                alert("Error al obtener los datos del cliente: " + (data.message || 'Desconocido'));
                window.location.href = "index.php";
            }
        })
        .catch(error => {
            console.error("Error cargando cliente:", error);
            alert("No se pudo conectar con el servidor.");
        });

    // 2. PROCESAR EL ENVÍO DEL FORMULARIO DE ACTUALIZACIÓN
    document.getElementById('edit-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const updatedData = {
            clientId: clientId,
            clientName: document.getElementById('client-name').value,
            clientRif: document.getElementById('client-rif').value,
            clientPhone: document.getElementById('client-phone').value,
            clientEmail: document.getElementById('client-email').value,
            clientWebsite: document.getElementById('client-website').value,
            clientAddress: document.getElementById('client-address').value,
            clientCity: document.getElementById('client-city').value,
            clientStateGeo: document.getElementById('client-state-geo').value,
            clientZip: document.getElementById('client-zip').value,
            clientCountry: document.getElementById('client-country').value,
            clientEmployees: document.getElementById('client-employees').value,
            clientIncome: document.getElementById('client-income').value,
            clientSector: document.getElementById('client-sector').value,
            clientService: document.getElementById('client-service').value,
            clientSectorDesc: document.getElementById('client-sector-desc').value,
            clientServiceDesc: document.getElementById('client-service-desc').value,
            clientInstagram: document.getElementById('client-instagram').value,
            clientLinkedin: document.getElementById('client-linkedin').value,
            clientStatus: document.getElementById('client-status').value
        };

        fetch('../api/api.php?action=updateClient', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updatedData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert("Ficha del cliente actualizada correctamente.");
                window.location.href = "index.php";
            } else {
                alert("Error al actualizar la ficha: " + (data.message || 'Desconocido'));
            }
        })
        .catch(error => {
            console.error("Error en la actualización:", error);
            alert("Ocurrió un error al intentar procesar la actualización.");
        });
    });
});
</script>

<?php include '../main/layout_footer.php';