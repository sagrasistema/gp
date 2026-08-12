<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etapas y Categorías</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container-fluid py-4 px-4">
    <!-- Selector de Versión y Servicio -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body d-flex justify-content-between align-items-center bg-white rounded">
            <div class="d-flex align-items-center gap-3">
                <span class="fw-bold">Servicio:</span>
                <select class="form-select w-auto" onchange="location.href='?serviceId='+this.value">
                    <!-- Iterar Servicios -->
                    <option value="1" selected>Auditoría Interna</option>
                </select>
                <span class="badge bg-primary fs-6">Versión Activa: v<?= $currentVersion ?></span>
            </div>
            <form method="POST" action="action_handler.php" onsubmit="return confirm('¿Crear nueva versión? Clonará la estructura actual.')">
                <input type="hidden" name="action" value="create_version">
                <input type="hidden" name="serviceId" value="<?= $serviceId ?>">
                <button type="submit" class="btn btn-warning fw-bold">🚀 Generar Nueva Versión (v<?= $currentVersion + 1 ?>)</button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Columna Etapas -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span>📌 Etapas (Fases)</span>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#etapaModal">+ Nueva</button>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <!-- Loop Etapas -->
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>1. Planeación</strong>
                                <br><small class="text-muted">Fase de levantamiento</small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary">Editar</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Columna Categorías -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <span>🗂️ Categorías de la Etapa</span>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#catModal">+ Nueva Categoría</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover m-0">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Etapa Padre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Gobierno Corporativo</td>
                                <td><span class="badge bg-secondary">Planeación</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary">Editar</button>
                                    <a href="pruebas.php?catId=1" class="btn btn-sm btn-outline-info">Ver Pruebas &rarr;</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>