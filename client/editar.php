<?php

declare(strict_types=1);

require_once 'config.php';

/** @var PDO $pdo */

// 1. Validar y sanitizar el parámetro ID obtenido por GET
$clienteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$clienteId) {
    header('Location: clientes.php?error=id_invalido');
    exit;
}

// 2. Obtener los datos del cliente desde la BD antes de renderizar el HTML
try {
    $stmt = $pdo->prepare("
        SELECT 
            id, name, rif, persona, cargo, phone, email, address, 
            city, state_geo, zip_code, website, instagram, linkedin, 
            country, employees, income_level, sector, service, 
            service_desc, sector_desc, status 
        FROM clientes 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        header('Location: clientes.php?error=no_encontrado');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error al cargar cliente en editar.php: " . $e->getMessage());
    die("Ocurrió un error interno al recuperar la información del cliente.");
}

// Helper para imprimir valores escapados de forma segura
function e(?string $val): string {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

require_once 'header.php';
?>

<div class="container mt-4">
    <h2>Editar Ficha de Cliente Corporativo</h2>
    <hr>

    <form id="form-editar-cliente" novalidate>
        <!-- Campo Oculto con el ID del Cliente -->
        <input type="hidden" id="cliente_id" name="id" value="<?= e((string)$cliente['id']) ?>">

        <div class="row g-3">
            <!-- 1. Identificación y Datos Legales -->
            <div class="col-md-6">
                <label for="name" class="form-label">Nombre / Razón Social *</label>
                <input type="text" class="form-class form-control" id="name" name="name" value="<?= e($cliente['name']) ?>" required>
            </div>

            <div class="col-md-6">
                <label for="rif" class="form-label">RIF / Identificación</label>
                <input type="text" class="form-control" id="rif" name="rif" value="<?= e($cliente['rif']) ?>">
            </div>

            <div class="col-md-6">
                <label for="persona" class="form-label">Persona de Contacto</label>
                <input type="text" class="form-control" id="persona" name="persona" value="<?= e($cliente['persona']) ?>">
            </div>

            <div class="col-md-6">
                <label for="cargo" class="form-label">Cargo del Contacto</label>
                <input type="text" class="form-control" id="cargo" name="cargo" value="<?= e($cliente['cargo']) ?>">
            </div>

            <!-- 2. Contacto y Ubicación -->
            <div class="col-md-6">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($cliente['email']) ?>">
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?= e($cliente['phone']) ?>">
            </div>

            <div class="col-md-12">
                <label for="address" class="form-label">Dirección Fiscal</label>
                <input type="text" class="form-control" id="address" name="address" value="<?= e($cliente['address']) ?>">
            </div>

            <div class="col-md-4">
                <label for="city" class="form-label">Ciudad</label>
                <input type="text" class="form-control" id="city" name="city" value="<?= e($cliente['city']) ?>">
            </div>

            <div class="col-md-4">
                <label for="state_geo" class="form-label">Estado / Región</label>
                <input type="text" class="form-control" id="state_geo" name="state_geo" value="<?= e($cliente['state_geo']) ?>">
            </div>

            <div class="col-md-4">
                <label for="zip_code" class="form-label">Código Postal</label>
                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?= e($cliente['zip_code']) ?>">
            </div>

            <div class="col-md-6">
                <label for="country" class="form-label">País</label>
                <input type="text" class="form-control" id="country" name="country" value="<?= e($cliente['country'] ?: 'Venezuela') ?>">
            </div>

            <div class="col-md-6">
                <label for="status" class="form-label">Estatus</label>
                <select class="form-select" id="status" name="status">
                    <option value="Activo" <?= $cliente['status'] === 'Activo' ? 'selected' : '' ?>>Activo</option>
                    <option value="Inactivo" <?= $cliente['status'] === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>

            <!-- 3. Redes y Presencia Digital -->
            <div class="col-md-4">
                <label for="website" class="form-label">Sitio Web</label>
                <input type="text" class="form-control" id="website" name="website" value="<?= e($cliente['website']) ?>">
            </div>

            <div class="col-md-4">
                <label for="instagram" class="form-label">Instagram</label>
                <input type="text" class="form-control" id="instagram" name="instagram" value="<?= e($cliente['instagram']) ?>">
            </div>

            <div class="col-md-4">
                <label for="linkedin" class="form-label">LinkedIn</label>
                <input type="text" class="form-control" id="linkedin" name="linkedin" value="<?= e($cliente['linkedin']) ?>">
            </div>

            <!-- 4. Perfil Comercial -->
            <div class="col-md-3">
                <label for="sector" class="form-label">Sector</label>
                <input type="text" class="form-control" id="sector" name="sector" value="<?= e($cliente['sector']) ?>">
            </div>

            <div class="col-md-3">
                <label for="service" class="form-label">Servicio Contratado</label>
                <input type="text" class="form-control" id="service" name="service" value="<?= e($cliente['service']) ?>">
            </div>

            <div class="col-md-3">
                <label for="employees" class="form-label">Empleados</label>
                <input type="text" class="form-control" id="employees" name="employees" value="<?= e($cliente['employees']) ?>">
            </div>

            <div class="col-md-3">
                <label for="income_level" class="form-label">Nivel de Ingresos</label>
                <input type="text" class="form-control" id="income_level" name="income_level" value="<?= e($cliente['income_level']) ?>">
            </div>

            <!-- 5. Descripciones -->
            <div class="col-md-6">
                <label for="service_desc" class="form-label">Descripción del Servicio</label>
                <textarea class="form-control" id="service_desc" name="service_desc" rows="3"><?= e($cliente['service_desc']) ?></textarea>
            </div>

            <div class="col-md-6">
                <label for="sector_desc" class="form-label">Descripción del Sector</label>
                <textarea class="form-control" id="sector_desc" name="sector_desc" rows="3"><?= e($cliente['sector_desc']) ?></textarea>
            </div>
        </div>

        <div class="mt-4 mb-5">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="clientes.php" class="btn btn-secondary">Cancelar</a>
            <a href="export_cliente_word.php?id=<?= e((string)$cliente['id']) ?>" class="btn btn-outline-success float-end">Exportar a Word</a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>