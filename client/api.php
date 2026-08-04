<?php

declare(strict_types=1);

/**
 * API de Control de Clientes Corporativos
 * Estándar: PSR-12
 * Compatibilidad: PHP 8.0+
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require_once 'config.php';

// Asegurar que PDO está correctamente configurado
/** @var PDO $pdo */
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Limpia y normaliza los valores del registro
 */
function sanitizeRow(?array $row): array
{
    if (!$row) {
        return [];
    }

    return array_map(function ($value) {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }, $row);
}

switch ($method) {
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

                if ($id === false || $id === null || $id <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'El parámetro ID debe ser un número entero válido.']);
                    exit;
                }

                $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => $id]);
                $client = $stmt->fetch();

                if ($client) {
                    http_response_code(200);
                    echo json_encode(sanitizeRow($client));
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'El cliente solicitado no existe.']);
                }
                exit;
            }

            $stmt = $pdo->query('SELECT * FROM clientes ORDER BY id DESC');
            $results = $stmt->fetchAll();

            $cleanedResults = array_map('sanitizeRow', $results);
            http_response_code(200);
            echo json_encode($cleanedResults);
        } catch (PDOException $e) {
            error_log('Error crítico en GET API Clientes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al consultar la base de datos.']);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data) || empty(trim((string) ($data['name'] ?? '')))) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre de la empresa es obligatorio.']);
            exit;
        }

        try {
            // CORREGIDO: Inclusión de los 21 campos con sus 21 placeholders alineados
            $sql = 'INSERT INTO clientes (
                name, rif, email, persona, cargo, phone, address, city, state_geo, 
                zip_code, website, instagram, linkedin, country, employees, 
                income_level, sector, service, service_desc, sector_desc, status
            ) VALUES (
                :name, :rif, :email, :persona, :cargo, :phone, :address, :city, :state_geo, 
                :zip_code, :website, :instagram, :linkedin, :country, :employees, 
                :income_level, :sector, :service, :service_desc, :sector_desc, :status
            )';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'         => trim((string) ($data['name'] ?? '')),
                ':rif'          => trim((string) ($data['rif'] ?? '')),
                ':email'        => trim((string) ($data['email'] ?? '')),
                ':persona'      => trim((string) ($data['persona'] ?? '')),
                ':cargo'        => trim((string) ($data['cargo'] ?? '')), // CORREGIDO: Mapeo correcto
                ':phone'        => trim((string) ($data['phone'] ?? '')),
                ':address'      => trim((string) ($data['address'] ?? '')),
                ':city'         => trim((string) ($data['city'] ?? '')),
                ':state_geo'    => trim((string) ($data['state_geo'] ?? '')),
                ':zip_code'     => trim((string) ($data['zip_code'] ?? '')),
                ':website'      => trim((string) ($data['website'] ?? '')),
                ':instagram'    => trim((string) ($data['instagram'] ?? '')),
                ':linkedin'     => trim((string) ($data['linkedin'] ?? '')),
                ':country'      => trim((string) ($data['country'] ?? 'Venezuela')),
                ':employees'    => trim((string) ($data['employees'] ?? '')),
                ':income_level' => trim((string) ($data['income_level'] ?? '')),
                ':sector'       => trim((string) ($data['sector'] ?? '')),
                ':service'      => trim((string) ($data['service'] ?? '')),
                ':service_desc' => trim((string) ($data['service_desc'] ?? '')),
                ':sector_desc'  => trim((string) ($data['sector_desc'] ?? '')),
                ':status'       => trim((string) ($data['status'] ?? 'Activo')),
            ]);

            http_response_code(201);
            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            error_log('Error crítico en POST API Clientes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo crear el registro en la base de datos.']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Se requiere un ID de cliente válido para actualizar.']);
            exit;
        }

        if (empty(trim((string) ($data['name'] ?? '')))) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre de la empresa no puede estar vacío.']);
            exit;
        }

        try {
            $sql = 'UPDATE clientes SET 
                name = :name, 
                rif = :rif, 
                email = :email, 
                persona = :persona, 
                cargo = :cargo, 
                phone = :phone, 
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
            WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'           => $id,
                ':name'         => trim((string) ($data['name'] ?? '')),
                ':rif'          => trim((string) ($data['rif'] ?? '')),
                ':email'        => trim((string) ($data['email'] ?? '')),
                ':persona'      => trim((string) ($data['persona'] ?? '')),
                ':cargo'        => trim((string) ($data['cargo'] ?? '')), // CORREGIDO: Mapeo correcto
                ':phone'        => trim((string) ($data['phone'] ?? '')),
                ':address'      => trim((string) ($data['address'] ?? '')),
                ':city'         => trim((string) ($data['city'] ?? '')),
                ':state_geo'    => trim((string) ($data['state_geo'] ?? '')),
                ':zip_code'     => trim((string) ($data['zip_code'] ?? '')),
                ':website'      => trim((string) ($data['website'] ?? '')),
                ':instagram'    => trim((string) ($data['instagram'] ?? '')),
                ':linkedin'     => trim((string) ($data['linkedin'] ?? '')),
                ':country'      => trim((string) ($data['country'] ?? '')),
                ':employees'    => trim((string) ($data['employees'] ?? '')),
                ':income_level' => trim((string) ($data['income_level'] ?? '')),
                ':sector'       => trim((string) ($data['sector'] ?? '')),
                ':service'      => trim((string) ($data['service'] ?? '')),
                ':service_desc' => trim((string) ($data['service_desc'] ?? '')),
                ':sector_desc'  => trim((string) ($data['sector_desc'] ?? '')),
                ':status'       => trim((string) ($data['status'] ?? 'Activo')),
            ]);

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Ficha actualizada correctamente.']);
        } catch (PDOException $e) {
            error_log('Error crítico en PUT API Clientes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error interno al actualizar la ficha.']);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Se requiere un ID válido para eliminar el registro.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = :id');
            $stmt->execute([':id' => $id]);

            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Registro eliminado correctamente.']);
        } catch (PDOException $e) {
            error_log('Error crítico en DELETE API Clientes: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo eliminar el registro.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método HTTP no permitido.']);
        break;
}