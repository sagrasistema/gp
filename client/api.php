<?php
/**
 * API de Control de Clientes Corporativos
 * Estándar: PSR-12
 * Compatibilidad: PHP 7.4 | 8.x
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Forzar a PDO a devolver únicamente arreglos asociativos limpios
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/**
 * Sanitiza y limpia valores nulos de la Base de Datos para evitar conflictos en JS
 */
function sanitizeRow(?array $row): array 
{
    if (!$row) return [];
    return array_map(function ($value) {
        return $value === null ? '' : htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
    }, $row);
}

switch ($method) {
    case 'GET':
        try {
            // CASO A: Solicitar la ficha de un cliente específico (?id=X)
            if (isset($_GET['id'])) {
                // Validación estricta del tipo de dato del ID
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                
                if ($id === false || $id === null) {
                    http_response_code(400); // Bad Request
                    echo json_encode(["error" => "El parámetro ID debe ser un número entero válido."]);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $id]);
                $client = $stmt->fetch();

                if ($client) {
                    http_response_code(200);
                    echo json_encode(sanitizeRow($client));
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(["error" => "El cliente con ID asignado no existe."]);
                }
                exit;
            }

            // CASO B: Listado general de la tabla principal
            $stmt = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
            $results = $stmt->fetchAll();
            
            $cleanedResults = array_map('sanitizeRow', $results);
            http_response_code(200);
            echo json_encode($cleanedResults);

        } catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            error_log("Error crítico en API Clientes: " . $e->getMessage());
            echo json_encode(["error" => "Ocurrió un error interno en el servidor."]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['name'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO clientes (name, rif, email, phone, address, city, state_geo, zip_code, website, instagram, linkedin, country, employees, income_level, sector, service, service_desc, sector_desc, status) VALUES (:name, :rif, :email, :phone, :address, :city, :state_geo, :zip_code, :website, :instagram, :linkedin, :country, :employees, :income_level, :sector, :service, :service_desc, :sector_desc, :status)");
                
                $stmt->execute([
                    ':name'         => $data['name'],
                    ':rif'          => $data['rif'] ?? '',
                    ':email'        => $data['email'] ?? '',
                    ':phone'        => $data['phone'] ?? '',
                    ':address'      => $data['address'] ?? '',
                    ':city'         => $data['city'] ?? '',
                    ':state_geo'    => $data['state_geo'] ?? '',
                    ':zip_code'     => $data['zip_code'] ?? '',
                    ':website'      => $data['website'] ?? '',
                    ':instagram'    => $data['instagram'] ?? '',
                    ':linkedin'     => $data['linkedin'] ?? '',
                    ':country'      => $data['country'] ?? 'Venezuela',
                    ':employees'    => $data['employees'] ?? '',
                    ':income_level' => $data['income_level'] ?? '',
                    ':sector'       => $data['sector'] ?? '',
                    ':service'      => $data['service'] ?? '',
                    ':service_desc' => $data['service_desc'] ?? '',
                    ':sector_desc'  => $data['sector_desc'] ?? '',
                    ':status'       => $data['status'] ?? 'Activo'
                ]);
                http_response_code(201); // Created
                echo json_encode(["status" => "success", "id" => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "No se pudo crear el registro."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "El nombre de la empresa es obligatorio."]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                $stmt = $pdo->prepare("UPDATE clientes SET name = :name, rif = :rif, email = :email, phone = :phone, address = :address, city = :city, state_geo = :state_geo, zip_code = :zip_code, website = :website, instagram = :instagram, linkedin = :linkedin, country = :country, employees = :employees, income_level = :income_level, sector = :sector, service = :service, service_desc = :service_desc, sector_desc = :sector_desc, status = :status WHERE id = :id");
                
                $stmt->execute([
                    ':id'           => $data['id'],
                    ':name'         => $data['name'],
                    ':rif'          => $data['rif'] ?? '',
                    ':email'        => $data['email'] ?? '',
                    ':phone'        => $data['phone'] ?? '',
                    ':address'      => $data['address'] ?? '',
                    ':city'         => $data['city'] ?? '',
                    ':state_geo'    => $data['state_geo'] ?? '',
                    ':zip_code'     => $data['zip_code'] ?? '',
                    ':website'      => $data['website'] ?? '',
                    ':instagram'    => $data['instagram'] ?? '',
                    ':linkedin'     => $data['linkedin'] ?? '',
                    ':country'      => $data['country'] ?? '',
                    ':employees'    => $data['employees'] ?? '',
                    ':income_level' => $data['income_level'] ?? '',
                    ':sector'       => $data['sector'] ?? '',
                    ':service'      => $data['service'] ?? '',
                    ':service_desc' => $data['service_desc'] ?? '',
                    ':sector_desc'  => $data['sector_desc'] ?? '',
                    ':status'       => $data['status']
                ]);
                http_response_code(200);
                echo json_encode(["status" => "success"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "Error al actualizar la ficha."]);
            }
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
                $stmt->execute([':id' => $data['id']]);
                http_response_code(200);
                echo json_encode(["status" => "success"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "No se pudo eliminar el registro."]);
            }
        }
        break;
}