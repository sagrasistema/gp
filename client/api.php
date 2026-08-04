<?php
declare(strict_types=1);

/**
 * RESTful API Controller - Gestión de Clientes Corporativos
 * Estándar: PSR-12 / PHP 8.x Strict Types
 */

header('Content-Type: application/json; charset=utf-8');

// Configuración de la base de datos (Ajustar credenciales según entorno)
define('DB_HOST', 'localhost');
define('DB_NAME', 'sagracom_alberto_1');
define('DB_USER', 'sagracom_alberto_t');
define('DB_PASS', 'sagragp2705');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error crítico de conexión a la base de datos.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Función helper para sanitizar y extraer datos de entrada de forma segura
 */
function sanitizeInput(array $input): array
{
    $clean = [];
    $allowedKeys = [
        'name', 'rif', 'persona', 'cargo', 'phone', 'email', 'address',
        'city', 'state_geo', 'zip_code', 'website', 'instagram', 'linkedin',
        'country', 'employees', 'income_level', 'sector', 'service',
        'service_desc', 'sector_desc', 'status'
    ];

    foreach ($allowedKeys as $key) {
        if (array_key_exists($key, $input) && $input[$key] !== null) {
            $clean[$key] = trim((string)$input[$key]);
        } else {
            $clean[$key] = null;
        }
    }

    return $clean;
}

try {
    switch ($method) {
        
        // ------------------------------------------------------------------
        // GET: Obtener todos los registros o un registro por ID
        // ------------------------------------------------------------------
        case 'GET':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

            if ($id) {
                // Consulta explícita que garantiza el retorno de persona y cargo
                $stmt = $pdo->prepare("
                    SELECT 
                        id, name, rif, persona, cargo, phone, email, address, 
                        city, state_geo, zip_code, website, instagram, linkedin, 
                        country, employees, income_level, sector, service, 
                        service_desc, sector_desc, status 
                    FROM clientes 
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $id]);
                $cliente = $stmt->fetch();

                if (!$cliente) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Registro no encontrado.']);
                    exit;
                }

                echo json_encode($cliente, JSON_UNESCAPED_UNICODE);
            } else {
                $stmt = $pdo->query("
                    SELECT 
                        id, name, rif, persona, cargo, phone, email, address, 
                        city, state_geo, zip_code, website, instagram, linkedin, 
                        country, employees, income_level, sector, service, 
                        service_desc, sector_desc, status 
                    FROM clientes 
                    ORDER BY id DESC
                ");
                $clientes = $stmt->fetchAll();
                echo json_encode($clientes, JSON_UNESCAPED_UNICODE);
            }
            break;

        // ------------------------------------------------------------------
        // POST: Crear nuevo cliente
        // ------------------------------------------------------------------
        case 'POST':
            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $data = sanitizeInput($rawInput);

            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'El campo Nombre/Razón Social es obligatorio.']);
                exit;
            }

            $sql = "INSERT INTO clientes (
                        name, rif, persona, cargo, phone, email, address, city, 
                        state_geo, zip_code, website, instagram, linkedin, country, 
                        employees, income_level, sector, service, service_desc, 
                        sector_desc, status
                    ) VALUES (
                        :name, :rif, :persona, :cargo, :phone, :email, :address, :city, 
                        :state_geo, :zip_code, :website, :instagram, :linkedin, :country, 
                        :employees, :income_level, :sector, :service, :service_desc, 
                        :sector_desc, :status
                    )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'         => $data['name'],
                ':rif'          => $data['rif'],
                ':persona'      => $data['persona'],
                ':cargo'        => $data['cargo'],
                ':phone'        => $data['phone'],
                ':email'        => $data['email'],
                ':address'      => $data['address'],
                ':city'         => $data['city'],
                ':state_geo'    => $data['state_geo'],
                ':zip_code'     => $data['zip_code'],
                ':website'      => $data['website'],
                ':instagram'    => $data['instagram'],
                ':linkedin'     => $data['linkedin'],
                ':country'      => $data['country'],
                ':employees'    => $data['employees'],
                ':income_level' => $data['income_level'],
                ':sector'       => $data['sector'],
                ':service'      => $data['service'],
                ':service_desc' => $data['service_desc'],
                ':sector_desc'  => $data['sector_desc'],
                ':status'       => $data['status'] ?? 'Activo'
            ]);

            http_response_code(201);
            echo json_encode(['message' => 'Cliente registrado exitosamente.', 'id' => $pdo->lastInsertId()]);
            break;

        // ------------------------------------------------------------------
        // PUT: Actualizar registro existente
        // ------------------------------------------------------------------
        case 'PUT':
            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = filter_var($rawInput['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID inválido o no suministrado para actualización.']);
                exit;
            }

            $data = sanitizeInput($rawInput);

            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'El campo Nombre/Razón Social es obligatorio.']);
                exit;
            }

            $sql = "UPDATE clientes SET 
                        name = :name,
                        rif = :rif,
                        persona = :persona,
                        cargo = :cargo,
                        phone = :phone,
                        email = :email,
                        address = :address,
                        city = :city,
                        state_geo = :state_geo,
                        zip_code = :zip_code,
                        website = :website,
                        instagram = :instagram,
                        linkedin = :linkedin,
                        country = :country,
                        employees = :employees,
                        income_level = :income_level,
                        sector = :sector,
                        service = :service,
                        service_desc = :service_desc,
                        sector_desc = :sector_desc,
                        status = :status
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'           => $id,
                ':name'         => $data['name'],
                ':rif'          => $data['rif'],
                ':persona'      => $data['persona'],
                ':cargo'        => $data['cargo'],
                ':phone'        => $data['phone'],
                ':email'        => $data['email'],
                ':address'      => $data['address'],
                ':city'         => $data['city'],
                ':state_geo'    => $data['state_geo'],
                ':zip_code'     => $data['zip_code'],
                ':website'      => $data['website'],
                ':instagram'    => $data['instagram'],
                ':linkedin'     => $data['linkedin'],
                ':country'      => $data['country'],
                ':employees'    => $data['employees'],
                ':income_level' => $data['income_level'],
                ':sector'       => $data['sector'],
                ':service'      => $data['service'],
                ':service_desc' => $data['service_desc'],
                ':sector_desc'  => $data['sector_desc'],
                ':status'       => $data['status'] ?? 'Activo'
            ]);

            echo json_encode(['message' => 'Ficha del cliente actualizada exitosamente.']);
            break;

        // ------------------------------------------------------------------
        // DELETE: Eliminar cliente
        // ------------------------------------------------------------------
        case 'DELETE':
            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = filter_var($rawInput['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID inválido para eliminación.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(['message' => 'Cliente eliminado correctamente.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método HTTP no permitido.']);
            break;
    }

} catch (PDOException $e) {
    http_response_code(500);
    // En producción se debe registrar $e->getMessage() en un archivo log interno
    echo json_encode(['error' => 'Error de ejecución en base de datos: ' . $e->getMessage()]);
}