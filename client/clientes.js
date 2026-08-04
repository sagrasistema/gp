/**
 * Sistema de Gestión de Clientes Corporativos - Controller de Cliente
 * Estándar: ES6+ Robusto
 */

let clients = [];

document.addEventListener('DOMContentLoaded', () => {
    // Inicialización de la vista de listado
    if (document.getElementById('table-body')) {
        loadClients();
        const btnExport = document.getElementById('btn-export');
        if (btnExport) {
            btnExport.addEventListener('click', exportToCSV);
        }
    }

    // Formulario de creación
    const clientForm = document.getElementById('client-form');
    if (clientForm) {
        clientForm.addEventListener('submit', createClient);
    }

    // Formulario de edición
    const editForm = document.getElementById('edit-form');
    if (editForm) {
        loadClientData();
        editForm.addEventListener('submit', updateClient);
    }
});

/**
 * Obtiene el valor de un input de manera segura para evitar TypeErrors si el ID no existe
 */
function getValue(id) {
    const el = document.getElementById(id);
    return el ? el.value.trim() : '';
}

/**
 * Asigna un valor a un campo del DOM y decodifica entidades HTML devueltas por el API
 */
function setValue(id, value) {
    const el = document.getElementById(id);
    if (el) {
        const txt = document.createElement('textarea');
        txt.innerHTML = value || '';
        el.value = txt.value;
    }
}

/**
 * Extrae y empaqueta la payload del formulario activo de forma segura
 */
function getFormData() {
    return {
        name: getValue('client-name'),
        rif: getValue('client-rif'),
        persona: getValue('client-persona'),
        cargo: getValue('client-cargo'),
        phone: getValue('client-phone'),
        email: getValue('client-email'),
        address: getValue('client-address'),
        city: getValue('client-city'),
        state_geo: getValue('client-state-geo'),
        zip_code: getValue('client-zip'),
        website: getValue('client-website'),
        instagram: getValue('client-instagram'),
        linkedin: getValue('client-linkedin'),
        country: getValue('client-country'),
        employees: getValue('client-employees'),
        income_level: getValue('client-income'),
        sector: getValue('client-sector'),
        service: getValue('client-service'),
        service_desc: getValue('client-service-desc'),
        sector_desc: getValue('client-sector-desc'),
        status: getValue('client-status') || 'Activo'
    };
}

/**
 * Pobla los inputs del formulario de edición de forma defensiva
 */
function fillFormData(c) {
    setValue('client-name', c.name);
    setValue('client-rif', c.rif);
    setValue('client-persona', c.persona);
    setValue('client-cargo', c.cargo);
    setValue('client-phone', c.phone);
    setValue('client-email', c.email);
    setValue('client-address', c.address);
    setValue('client-city', c.city);
    setValue('client-state-geo', c.state_geo);
    setValue('client-zip', c.zip_code);
    setValue('client-website', c.website);
    setValue('client-instagram', c.instagram);
    setValue('client-linkedin', c.linkedin);
    setValue('client-country', c.country);
    setValue('client-employees', c.employees);
    setValue('client-income', c.income_level);
    setValue('client-sector', c.sector);
    setValue('client-service', c.service);
    setValue('client-service-desc', c.service_desc);
    setValue('client-sector-desc', c.sector_desc);
    setValue('client-status', c.status || 'Activo');
}

/**
 * Carga el listado completo de clientes
 */
async function loadClients() {
    try {
        const response = await fetch('api.php');
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `Error HTTP ${response.status}`);
        }

        clients = await response.json();
        renderTable();
    } catch (error) {
        console.error("Error al cargar clientes:", error);
        const tableBody = document.getElementById('table-body');
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#ef4444; padding:2rem;">
                Error al conectar con el servidor: ${error.message}
            </td></tr>`;
        }
    }
}

/**
 * Carga los datos de un único cliente para su actualización
 */
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
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || `Error del servidor (${response.status})`);
        }

        fillFormData(result);
    } catch (error) {
        alert("Error al cargar la ficha del cliente: " + error.message);
        console.error("Detalle del fallo:", error);
        window.location.href = 'index.php';
    }
}

/**
 * Renderiza dinámicamente el listado de clientes en la tabla HTML
 */
function renderTable() {
    const tableBody = document.getElementById('table-body');
    if (!tableBody) return;

    tableBody.innerHTML = '';
    if (clients.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem;">No hay registros cargados.</td></tr>`;
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

/**
 * Procesa la creación de un nuevo registro (POST)
 */
async function createClient(e) {
    e.preventDefault();
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(getFormData())
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || `Error en el servidor (${response.status})`);
        }

        window.location.href = 'index.php';
    } catch (error) {
        alert("Error al guardar el nuevo registro: " + error.message);
        console.error("Detalle en createClient:", error);
    }
}

/**
 * Procesa la actualización de la ficha existente (PUT)
 */
async function updateClient(e) {
    e.preventDefault();
    const id = new URLSearchParams(window.location.search).get('id');

    if (!id) {
        alert("Falta el identificador (ID) del cliente en la URL.");
        return;
    }

    const payload = { id, ...getFormData() };

    try {
        const response = await fetch('api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || `Error en el servidor (${response.status})`);
        }

        window.location.href = 'index.php';
    } catch (error) {
        alert("Error al actualizar la ficha: " + error.message);
        console.error("Detalle en updateClient:", error);
    }
}

/**
 * Elimina un registro de la base de datos (DELETE)
 */
async function deleteClient(id) {
    if (!confirm('¿Está seguro de eliminar esta ficha corporativa?')) return;

    try {
        const response = await fetch('api.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || `Error al eliminar (${response.status})`);
        }

        loadClients();
    } catch (error) {
        alert("No se pudo eliminar el cliente: " + error.message);
        console.error("Detalle en deleteClient:", error);
    }
}

/**
 * Genera y descarga el archivo CSV con la data cargada
 */
function exportToCSV() {
    if (clients.length === 0) return alert("No hay información registrada para exportar.");
    
    let csv = "ID,Empresa,RIF,Persona Contacto,Cargo,Email,Telefono,Direccion,Ciudad,Estado,ZIP,Web,Instagram,Linkedin,Pais,Empleados,Ingresos,Sector,Servicio\n";
    
    clients.forEach(c => {
        csv += `${c.id},"${c.name}","${c.rif}","${c.persona || ''}","${c.cargo || ''}","${c.email}","${c.phone}","${c.address}","${c.city}","${c.state_geo}","${c.zip_code}","${c.website}","${c.instagram}","${c.linkedin}","${c.country}","${c.employees}","${c.income_level}","${c.sector}","${c.service}"\n`;
    });

    const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `clientes_export_${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
}