/**
 * Gestor de Clientes Corporativos
 */
document.addEventListener('DOMContentLoaded', () => {
    const editForm = document.getElementById('form-editar-cliente');

    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Mapeo seguro de todos los campos del formulario
            const payload = {
                id: document.getElementById('cliente_id').value,
                name: document.getElementById('name').value,
                rif: document.getElementById('rif').value,
                persona: document.getElementById('persona').value,
                cargo: document.getElementById('cargo').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                address: document.getElementById('address').value,
                city: document.getElementById('city').value,
                state_geo: document.getElementById('state_geo').value,
                zip_code: document.getElementById('zip_code').value,
                country: document.getElementById('country').value,
                status: document.getElementById('status').value,
                website: document.getElementById('website').value,
                instagram: document.getElementById('instagram').value,
                linkedin: document.getElementById('linkedin').value,
                sector: document.getElementById('sector').value,
                service: document.getElementById('service').value,
                employees: document.getElementById('employees').value,
                income_level: document.getElementById('income_level').value,
                service_desc: document.getElementById('service_desc').value,
                sector_desc: document.getElementById('sector_desc').value
            };

            try {
                const response = await fetch('api.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok) {
                    alert(result.message || 'Cliente actualizado exitosamente.');
                    window.location.href = 'clientes.php';
                } else {
                    alert('Error: ' + (result.error || 'No se pudo guardar la información.'));
                }
            } catch (error) {
                console.error('Error enviando datos:', error);
                alert('Ocurrió un error de conexión con la API.');
            }
        });
    }
});