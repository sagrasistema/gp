<?php
declare(strict_types=1);

// 1. Incluir la conexión a la base de datos (ajusta el nombre del archivo de conexión si es diferente, ej: conexion.php, db.php)
include '../main/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener identificadores desde la URL de forma estricta
        $proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);
        $pruebaId = filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT);

        if (!$proyectoId || $pruebaId !== 11) {
            throw new Exception("Parámetros de identificación de proyecto o prueba no válidos.");
        }

        $actionType = trim($_POST['action_type'] ?? '');

        // 2. ACCIÓN: AGREGAR PARTIDA ANALÍTICA
        if ($actionType === 'add_analitica_item') {
            $tipo = trim($_POST['tipo'] ?? '');
            $tipoRubro = trim($_POST['tipo_rubro'] ?? '');
            
            // Normalización de montos flotantes (manejo seguro de comas o puntos decimales)
            $rawActual = str_replace(',', '.', trim((string)($_POST['saldo_actual'] ?? '0')));
            $rawAnterior = str_replace(',', '.', trim((string)($_POST['saldo_anterior'] ?? '0')));
            
            $saldoActual = is_numeric($rawActual) ? (float)$rawActual : 0.00;
            $saldoAnterior = is_numeric($rawAnterior) ? (float)$rawAnterior : 0.00;
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validaciones de negocio
            $tiposPermitidos = ['activo', 'pasivo', 'patrimonio'];
            if (!in_array($tipo, $tiposPermitidos, true)) {
                throw new Exception("El tipo de cuenta seleccionado no es válido.");
            }

            if (empty($tipoRubro)) {
                throw new Exception("El campo rubro o descripción es obligatorio.");
            }

            // Inserción segura mediante PDO (Sentencias Preparadas)
            $sql = "INSERT INTO proyecto_revision_analitica 
                    (proyecto_id, prueba_id, tipo, tipo_rubro, saldo_actual, saldo_anterior, observaciones, created_at) 
                    VALUES (:proj, :pr, :tipo, :rubro, :actual, :anterior, :obs, NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':proj'     => $proyectoId,
                ':pr'       => $pruebaId,
                ':tipo'     => $tipo,
                ':rubro'    => htmlspecialchars($tipoRubro, ENT_QUOTES, 'UTF-8'),
                ':actual'   => $saldoActual,
                ':anterior' => $saldoAnterior,
                ':obs'      => !empty($observaciones) ? htmlspecialchars($observaciones, ENT_QUOTES, 'UTF-8') : null
            ]);

            // Redirección exitosa (Patrón PRG)
            header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
            exit;
        }

        // 3. ACCIÓN: ELIMINAR PARTIDA ANALÍTICA
        if ($actionType === 'delete_analitica_item') {
            $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
            
            if (!$itemId) {
                throw new Exception("El identificador del registro a eliminar no es válido.");
            }

            $sql = "DELETE FROM proyecto_revision_analitica WHERE id = :id AND proyecto_id = :proj AND prueba_id = :pr";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id'   => $itemId, 
                ':proj' => $proyectoId, 
                ':pr'   => $pruebaId
            ]);

            header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
            exit;
        }

    } catch (Throwable $e) {
        // Registro interno de errores y redirección controlada con mensaje de error
        error_log("Error en procesador de analítica (Prueba 11): " . $e->getMessage());
        $errorMsg = urlencode($e->getMessage());
        $pId = $proyectoId ?? 0;
        header("Location: actividades.php?proyectoId={$pId}&pruebaId=11&error={$errorMsg}");
        exit;
    }
}