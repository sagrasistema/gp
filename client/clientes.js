let clients = [];

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('table-body')) {
        loadClients();
        document.getElementById('btn-export').addEventListener('click', exportToCSV);
    }

    const clientForm = document.getElementById('client-form');
    if (clientForm) clientForm.addEventListener('submit', createClient);

    const editForm = document.getElementById('edit-form');
    if (editForm) {
        loadClientData();
        editForm.addEventListener('submit', updateClient);
    }
});

// Obtener valores del formulario unificado
function getFormData() {
    return {
        name: document.getElementById('client-name').value,
        rif: document.getElementById('client-rif').value,
        phone: document.getElementById('client-phone').value,
        email: document.getElementById('client-email').value,
        address: document.getElementById('client-address').value,
        city: document.getElementById('client-city').value,
        state_geo: document.getElementById('client-state-geo').value,
        zip_code: document.getElementById('client-zip').value,
        website: document.getElementById('client-website').value,
        instagram: document.getElementById('client-instagram').value,
        linkedin: document.getElementById('client-linkedin').value,
        country: document.getElementById('client-country').value,
        employees: document.getElementById('client-employees').value,
        income_level: document.getElementById('client-income').value,
        sector: document.getElementById('client-sector').value,
        service: document.getElementById('client-service').value,
        service_desc: document.getElementById('client-service-desc').value,
        sector_desc: document.getElementById('client-sector-desc').value,
        status: document.getElementById('client-status').value
    };
}

// Llenar formulario con los datos recibidos del backend
function fillFormData(c) {
    document.getElementById('client-name').value = c.name || '';
    document.getElementById('client-rif').value = c.rif || '';
    document.getElementById('client-phone').value = c.phone || '';
    document.getElementById('client-email').value = c.email || '';
    document.getElementById('client-address').value = c.address || '';
    document.getElementById('client-city').value = c.city || '';
    document.getElementById('client-state-geo').value = c.state_geo || '';
    document.getElementById('client-zip').value = c.zip_code || '';
    document.getElementById('client-website').value = c.website || '';
    document.getElementById('client-instagram').value = c.instagram || '';
    document.getElementById('client-linkedin').value = c.linkedin || '';
    document.getElementById('client-country').value = c.country || '';
    document.getElementById('client-employees').value = c.employees || '';
    document.getElementById('client-income').value = c.income_level || '';
    document.getElementById('client-sector').value = c.sector || '';
    document.getElementById('client-service').value = c.service || '';
    document.getElementById('client-service-desc').value = c.service_desc || '';
    document.getElementById('client-sector-desc').value = c.sector_desc || '';
    document.getElementById('client-status').value = c.status || 'Activo';
}

// NUEVA/CORREGIDA: Carga el listado completo en el Index
async function loadClients() {
    try {
        const response = await fetch('api.php');
        if (!response.ok) {
            throw new Error(`Error de servidor (${response.status})`);
        }
        clients = await response.json();
        renderTable();
    } catch (error) {
        console.error("Error al solicitar el listado de clientes:", error);
        const tableBody = document.getElementById('table-body');
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red; padding:2rem;">Error al conectar con el servidor.</td></tr>`;
        }
    }
}

// Carga los datos de un único cliente para el formulario de edición (editar.php)
async function loadClientData() {
    const urlParams = new URLSearchParams(window.location.search);
    const clientId = urlParams.get('id');

    if (!clientId) {
        alert("No se especificó un ID de cliente válido.");
        window.location.href = 'index.php';
        return;
    }

    try {
        const response = await fetch(`api.php?id=${clientId}`);
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || `Error del servidor (Código ${response.status})`);
        }

        const client = await response.json();
        fillFormData(client);

    } catch (error) {
        alert("Error al cargar la ficha del cliente: " + error.message);
        console.error("Detalle del fallo:", error);
        window.location.href = 'index.php';
    }
}

function renderTable() {
    const tableBody = document.getElementById('table-body');
    tableBody.innerHTML = '';
    if (clients.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem;">No hay registros.</td></tr>`;
        return;
    }
    clients.forEach(c => {
        const tr = document.createElement('tr');
        const badgeStatus = c.status ? String(c.status).toLowerCase() : 'activo';
        tr.innerHTML = `
            <td><strong>${c.name}</strong><br><small style="color:#64748b;">RIF: ${c.rif || '-'}</small></td>
            <td>${c.email || '-'}</td>
            <td>${c.phone || '-'}</td>
            <td>${c.sector || '-'}</td>
            <td><span class="badge badge-${badgeStatus}">${c.status || 'Activo'}</span></td>
            <td>
                <div class="actions-cell">
                    <a href="editar.php?id=${c.id}" class="btn-icon btn-icon-edit"><i class="ri-edit-line"></i></a>
                    <button class="btn-icon btn-icon-delete" onclick="deleteClient(${c.id})"><i class="ri-delete-bin-line"></i></button>
                </div>
            </td>
        `;
        tableBody.appendChild(tr);
    });
}

async function createClient(e) {
    e.preventDefault();
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(getFormData())
        });
        if (response.ok) window.location.href = 'index.php';
    } catch (error) { console.error(error); }
}

async function updateClient(e) {
    e.preventDefault();
    const id = new URLSearchParams(window.location.search).get('id');
    const payload = { id, ...getFormData() };
    try {
        const response = await fetch('api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        if (response.ok) window.location.href = 'index.php';
    } catch (error) { console.error(error); }
}

async function deleteClient(id) {
    if (confirm('¿Eliminar cliente?')) {
        await fetch('api.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        loadClients();
    }
}

function exportToCSV() {
    if (clients.length === 0) return alert("No hay datos.");
    let csv = "ID,Empresa,RIF,Email,Telefono,Direccion,Ciudad,Estado,ZIP,Web,Instagram,Linkedin,Pais,Empleados,Ingresos,Sector,Servicio\n";
    clients.forEach(c => {
        csv += `${c.id},"${c.name}","${c.rif}","${c.email}","${c.phone}","${c.address}","${c.city}","${c.state_geo}","${c.zip_code}","${c.website}","${c.instagram}","${c.linkedin}","${c.country}","${c.employees}","${c.income_level}","${c.sector}","${c.service}"\n`;
    });
    const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `clientes_completo_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
}