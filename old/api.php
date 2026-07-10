<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    
    case 'GET':
        try {
            // Forzamos a que si la descripciÃ³n es NULL devuelva un texto vacÃ­o para evitar romper el JSON en JS
            $stmt = $pdo->query("SELECT id, name, IFNULL(description, '') as description, priority, status FROM actividades ORDER BY id DESC");
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;

    case 'POST':
        // Operaci¨®n: Crear una nueva actividad
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['name'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO actividades (name, description, priority, status) VALUES (:name, :description, :priority, :status)");
                $stmt->execute([
                    ':name'        => $data['name'],
                    ':description' => !empty($data['description']) ? $data['description'] : '',
                    ':priority'    => !empty($data['priority']) ? $data['priority'] : 'Media',
                    ':status'      => !empty($data['status']) ? $data['status'] : 'Pendiente'
                ]);
                echo json_encode(["status" => "success", "id" => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "El campo 'name' es requerido."]);
        }
        break;

    case 'PUT':
        // Operaci¨®n: Actualizar una actividad existente
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                $stmt = $pdo->prepare("UPDATE actividades SET name = :name, description = :description, priority = :priority, status = :status WHERE id = :id");
                $stmt->execute([
                    ':id'          => $data['id'],
                    ':name'        => $data['name'],
                    ':description' => isset($data['description']) ? $data['description'] : '',
                    ':priority'    => $data['priority'],
                    ':status'      => $data['status']
                ]);
                echo json_encode(["status" => "success"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID requerido."]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM actividades WHERE id = :id");
                $stmt->execute([':id' => $data['id']]);
                echo json_encode(["status" => "success"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
            }
        }
        break;
}