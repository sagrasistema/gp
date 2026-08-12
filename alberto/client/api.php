<?php
declare(strict_types=1);

/**
 * API de Control de Clientes Corporativos
 * Estándar: PSR-12
 * Compatibilidad: PHP 8.x
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Forzar a PDO a devolver únicamente arreglos asociativos limpios y manejo de errores estricto
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
                $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                
                if ($id === false || $id === null) {
                    http_response_code(400);
                    echo json_encode(["error" => "El parámetro ID debe ser un número entero válido."], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND ver_id != 0 LIMIT 1");
                $stmt->execute([':id' => $id]);
                $client = $stmt->fetch();

                if ($client) {
                    http_response_code(200);
                    echo json_encode(sanitizeRow($client), JSON_UNESCAPED_UNICODE);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "El cliente no existe o se encuentra oculto."], JSON_UNESCAPED_UNICODE);
                }
                exit;
            }

            // CASO B: Listado general excluyendo registros ocultos (ver_id = 0)
            $stmt = $pdo->query("SELECT * FROM clientes WHERE ver_id != 2 ORDER BY id DESC");
            $results = $stmt->fetchAll();
            
            $cleanedResults = array_map('sanitizeRow', $results);
            http_response_code(200);
            echo json_encode($cleanedResults, JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Error crítico en API Clientes (GET): " . $e->getMessage());
            echo json_encode(["error" => "Ocurrió un error interno en el servidor."], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['name'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO clientes (name, rif, email, phone, address, city, state_geo, zip_code, website, instagram, linkedin, country, employees, income_level, sector, service, service_desc, sector_desc, status, ver_id) VALUES (:name, :rif, :email, :phone, :address, :city, :state_geo, :zip_code, :website, :instagram, :linkedin, :country, :employees, :income_level, :sector, :service, :service_desc, :sector_desc, :status, :ver_id)");
                
                $stmt->execute([
                    ':name'         => trim($data['name']),
                    ':rif'          => trim($data['rif'] ?? ''),
                    ':email'        => trim($data['email'] ?? ''),
                    ':phone'        => trim($data['phone'] ?? ''),
                    ':address'      => trim($data['address'] ?? ''),
                    ':city'         => trim($data['city'] ?? ''),
                    ':state_geo'    => trim($data['state_geo'] ?? ''),
                    ':zip_code'     => trim($data['zip_code'] ?? ''),
                    ':website'      => trim($data['website'] ?? ''),
                    ':instagram'    => trim($data['instagram'] ?? ''),
                    ':linkedin'     => trim($data['linkedin'] ?? ''),
                    ':country'      => trim($data['country'] ?? 'Venezuela'),
                    ':employees'    => trim($data['employees'] ?? ''),
                    ':income_level' => trim($data['income_level'] ?? ''),
                    ':sector'       => trim($data['sector'] ?? ''),
                    ':service'      => trim($data['service'] ?? ''),
                    ':service_desc' => trim($data['service_desc'] ?? ''),
                    ':sector_desc'  => trim($data['sector_desc'] ?? ''),
                    ':status'       => trim($data['status'] ?? 'Activo'),
                    ':ver_id'       => 1 // Inicializa como visible por defecto
                ]);
                
                http_response_code(201);
                echo json_encode(["status" => "success", "id" => $pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Error en API Clientes (POST): " . $e->getMessage());
                echo json_encode(["error" => "No se pudo crear el registro."], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "El nombre de la empresa es obligatorio."], JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                $stmt = $pdo->prepare("UPDATE clientes SET name = :name, rif = :rif, email = :email, phone = :phone, address = :address, city = :city, state_geo = :state_geo, zip_code = :zip_code, website = :website, instagram = :instagram, linkedin = :linkedin, country = :country, employees = :employees, income_level = :income_level, sector = :sector, service = :service, service_desc = :service_desc, sector_desc = :sector_desc, status = :status WHERE id = :id");
                
                $stmt->execute([
                    ':id'           => (int)$data['id'],
                    ':name'         => trim($data['name']),
                    ':rif'          => trim($data['rif'] ?? ''),
                    ':email'        => trim($data['email'] ?? ''),
                    ':phone'        => trim($data['phone'] ?? ''),
                    ':address'      => trim($data['address'] ?? ''),
                    ':city'         => trim($data['city'] ?? ''),
                    ':state_geo'    => trim($data['state_geo'] ?? ''),
                    ':zip_code'     => trim($data['zip_code'] ?? ''),
                    ':website'      => trim($data['website'] ?? ''),
                    ':instagram'    => trim($data['instagram'] ?? ''),
                    ':linkedin'     => trim($data['linkedin'] ?? ''),
                    ':country'      => trim($data['country'] ?? ''),
                    ':employees'    => trim($data['employees'] ?? ''),
                    ':income_level' => trim($data['income_level'] ?? ''),
                    ':sector'       => trim($data['sector'] ?? ''),
                    ':service'      => trim($data['service'] ?? ''),
                    ':service_desc' => trim($data['service_desc'] ?? ''),
                    ':sector_desc'  => trim($data['sector_desc'] ?? ''),
                    ':status'       => trim($data['status'] ?? 'Activo')
                ]);
                
                http_response_code(200);
                echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Error en API Clientes (PUT): " . $e->getMessage());
                echo json_encode(["error" => "Error al actualizar la ficha."], JSON_UNESCAPED_UNICODE);
            }
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                // Borrado lógico: Establece ver_id en 0 para ocultar el registro de las vistas principales
                $stmt = $pdo->prepare("UPDATE clientes SET ver_id = 0 WHERE id = :id");
                $stmt->execute([':id' => (int)$data['id']]);
                
                http_response_code(200);
                echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log("Error en API Clientes (DELETE): " . $e->getMessage());
                echo json_encode(["error" => "No se pudo ocultar el registro."], JSON_UNESCAPED_UNICODE);
            }
        }
        break;
}