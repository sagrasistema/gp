<?php

include '../config/config.php'; 
include '../controllers/MetodologiaController.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Servicios</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🛠️ Servicios Metodológicos</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="clearModal()">+ Nuevo Servicio</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Servicio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicios as $s): ?>
                    <tr>
                        <td><?= (int)$s['serviceId'] ?></td>
                        <td><strong><?= htmlspecialchars($s['serviceName']) ?></strong></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary me-1" onclick='editService(<?= json_encode($s) ?>)'>Editar</button>
                            <a href="etapas_categorias.php?serviceId=<?= $s['serviceId'] ?>" class="btn btn-sm btn-info text-white">Configurar Estructura &rarr;</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="serviceModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="action_handler.php" class="modal-content">
      <input type="hidden" name="action" value="save_service">
      <input type="hidden" name="serviceId" id="serviceId">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Nuevo Servicio</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nombre del Servicio</label>
          <input type="text" name="serviceName" id="serviceName" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editService(data) {
    document.getElementById('serviceId').value = data.serviceId;
    document.getElementById('serviceName').value = data.serviceName;
    document.getElementById('modalTitle').innerText = 'Editar Servicio';
    new bootstrap.Modal(document.getElementById('serviceModal')).show();
}
function clearModal() {
    document.getElementById('serviceId').value = '';
    document.getElementById('serviceName').value = '';
    document.getElementById('modalTitle').innerText = 'Nuevo Servicio';
}
</script>
</body>
</html>