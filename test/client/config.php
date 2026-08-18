<?php
$host = 'localhost';
$db_name = 'sagracom_alberto_1';
$username = 'sagracom_alberto_t';
$password = 'sagragp2705';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
    exit;
}

/**
 * Obtiene los permisos de un usuario para un módulo específico en la tabla 'usuario_permisos'.
 * 
 * @param PDO $pdo Instancia de conexión PDO a la base de datos.
 * @param int $userId ID del usuario obtenido desde la sesión.
 * @param int $moduloId ID del módulo a consultar (ej. 1).
 * @return array Matriz asociativa con las banderas de permisos procesadas como booleanos.
 */
function obtenerPermisosModulo(PDO $pdo, int $userId, int $moduloId = 1): array
{
    // Estructura por defecto en caso de no existir registro
    $permisosPredeterminados = [
        'puede_acceder' => false,
        'puede_ver'     => false,
        'puede_crear'   => false,
        'puede_editar'  => false,
        'puede_eliminar' => false,
    ];

    if ($userId <= 0) {
        return $permisosPredeterminados;
    }

    try {
        $sql = "SELECT puede_acceder, puede_ver, puede_crear, puede_editar, puede_eliminar 
                FROM usuario_permisos 
                WHERE usuario_id = :usuario_id 
                  AND modulo_id = :modulo_id 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $userId,
            ':modulo_id'  => $moduloId,
        ]);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            return $permisosPredeterminados;
        }

        // Cast explicito a bool para garantizar la compatibilidad con PHP 8.x
        return [
            'puede_acceder'  => (bool)$registro['puede_acceder'],
            'puede_ver'      => (bool)$registro['puede_ver'],
            'puede_crear'    => (bool)$registro['puede_crear'],
            'puede_editar'   => (bool)$registro['puede_editar'],
            'puede_eliminar' => (bool)$registro['puede_eliminar'],
        ];

    } catch (PDOException $e) {
        // Registrar error internamente sin exponérselo al usuario
        error_log("Error al consultar permisos de usuario [ID: {$userId}, Modulo: {$moduloId}]: " . $e->getMessage());
        return $permisosPredeterminados;
    }
}
// 1. Obtener y sanitizar el ID del usuario desde la sesión activa
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$moduloId = 1; // Módulo deseado
$moduloId4 = 4; // Módulo deseado
$moduloId5 = 5; // Módulo deseado
$moduloId6 = 6; // Módulo deseado
$moduloId7 = 7; // Módulo deseado

// proyecto
$permisosModulo1 = obtenerPermisosModulo($pdo, $userId, $moduloId);
// Cliente
$permisosModulo4 = obtenerPermisosModulo($pdo, $userId, $moduloId4);
// aceptacion y continuidad
$permisosModulo5 = obtenerPermisosModulo($pdo, $userId, $moduloId5);
// terminos y condiciones
$permisosModulo6 = obtenerPermisosModulo($pdo, $userId, $moduloId6);
// permisos 
$permisosModulo7 = obtenerPermisosModulo($pdo, $userId, $moduloId7);