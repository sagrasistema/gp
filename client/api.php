<?php

declare(strict_types=1);

/**
 * RESTful API Controller - Gestión de Clientes Corporativos
 * Estándar: PSR-12 / PHP 8.x Strict Types
 */

header('Content-Type: application/json; charset=utf-8');

// Cargar la conexión unificada de la base de datos
require_once 'config.php';

/** @var PDO $pdo */

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Helper para sanitizar las entradas del cliente de manera segura.
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
            $value = trim((string)$input[$key]);
            $clean[$key] = $value !== '' ? $value : null;
        } else {
            $clean[$key] = null;
        }
    }

    return $clean;
}

try {
    switch ($method) {
        
        // ------------------------------------------------------------------
        // GET: Consultar todos los clientes o uno en específico por ID
        // ------------------------------------------------------------------
        case 'GET':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

            if ($id) {
                $stmt = $pdo->prepare("
                    SELECT 
                        id, name, rif, persona, cargo, email, phone, address, 
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
                    echo json_encode(['error' => 'El cliente solicitado no existe.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                echo json_encode($cliente, JSON_UNESCAPED_UNICODE);
            } else {
                $stmt = $pdo->query("
                    SELECT 
                        id, name, rif, persona, cargo, email, phone, address, 
                        city, state_geo, zip_code, website, instagram, linkedin, 
                        country, employees, income_level, sector, service, 
                        service_desc, sector_desc, status 
                    FROM clientes 
                    ORDER BY id DESC
                ");
                echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
            }
            break;

        // ------------------------------------------------------------------
        // POST: Crear nuevo registro
        // ------------------------------------------------------------------
        case 'POST':
            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $data = sanitizeInput($rawInput);

            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'El campo Nombre o Razón Social es obligatorio.'], JSON_UNESCAPED_UNICODE);
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
            echo json_encode([
                'message' => 'Cliente registrado exitosamente.', 
                'id' => (int)$pdo->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ------------------------------------------------------------------
        // PUT: Actualización completa de la ficha corporativa
        // ------------------------------------------------------------------
        case 'PUT':
            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = filter_var($rawInput['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Identificador (ID) de cliente no válido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $data = sanitizeInput($rawInput);

            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'El campo Nombre o Razón Social es obligatorio.'], JSON_UNESCAPED_UNICODE);
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

            echo json_encode(['message' => 'Ficha de cliente actualizada exitosamente.'], JSON_UNESCAPED_UNICODE);
            break;

        // ------------------------------------------------------------------
        // DELETE: Eliminar cliente por ID
        // ------------------------------------------------------------------
        case 'DELETE':
            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $id = filter_var($rawInput['id'] ?? null, FILTER_VALIDATE_INT);

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Identificador (ID) inválido para eliminación.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(['message' => 'Registro eliminado correctamente.'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método HTTP no soportado.'], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (PDOException $e) {
    error_log("Error PDO en api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno procesando la solicitud.'], JSON_UNESCAPED_UNICODE);
}