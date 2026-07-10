<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    
    case 'GET':
        try {
            // CORRECCIÓN CRUCIAL: Si viene un ID en la URL (?id=1), devolvemos SOLO ese cliente como un objeto único
            if (!empty($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT id, name, IFNULL(email, '') as email, IFNULL(phone, '') as phone, status FROM clientes WHERE id = :id");
                $stmt->execute([':id' => $_GET['id']]);
                $client = $stmt->fetch();
                
                if ($client) {
                    echo json_encode($client);
                } else {
                    http_response_code(404);
                    echo json_encode(["error" => "Cliente no encontrado."]);
                }
            } else {
                // Si no viene ID, devolvemos la lista completa para la tabla principal
                $stmt = $pdo->query("SELECT id, name, IFNULL(email, '') as email, IFNULL(phone, '') as phone, status FROM clientes ORDER BY id DESC");
                echo json_encode($stmt->fetchAll());
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        break;

    case 'POST':
        // Operación: Crear un nuevo cliente con las columnas correctas
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['name'])) {
            try {
                $stmt = $pdo->prepare("INSERT INTO clientes (name, email, phone, status) VALUES (:name, :email, :phone, :status)");
                $stmt->execute([
                    ':name'   => $data['name'],
                    ':email'  => !empty($data['email']) ? $data['email'] : '',
                    ':phone'  => !empty($data['phone']) ? $data['phone'] : '',
                    ':status' => !empty($data['status']) ? $data['status'] : 'Activo'
                ]);
                echo json_encode(["status" => "success", "id" => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "El campo 'Nombre' es requerido."]);
        }
        break;

    case 'PUT':
        // Operación: Actualizar un cliente existente
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                $stmt = $pdo->prepare("UPDATE clientes SET name = :name, email = :email, phone = :phone, status = :status WHERE id = :id");
                $stmt->execute([
                    ':id'     => $data['id'],
                    ':name'   => $data['name'],
                    ':email'  => isset($data['email']) ? $data['email'] : '',
                    ':phone'  => isset($data['phone']) ? $data['phone'] : '',
                    ':status' => $data['status']
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
        // Operación: Eliminar un cliente
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
                $stmt->execute([':id' => $data['id']]);
                echo json_encode(["status" => "success"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => $e->getMessage()]);
            }
        }
        break;
}