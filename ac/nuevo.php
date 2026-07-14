<?php 
// v/ac/nuevo.php

// 1. Incluimos tu archivo de conexión primero (Inicializa la variable $pdo)
include '../main/config.php'; 

// =========================================================================
// LÓGICA DE PROCESAMIENTO Y GUARDADO (FUNCIONA COMO CONTROLADOR INTEGRADO)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = filter_input(INPUT_POST, 'clientId', FILTER_VALIDATE_INT);
    $typeId = filter_input(INPUT_POST, 'typeId', FILTER_VALIDATE_INT);
    $serviceId = filter_input(INPUT_POST, 'serviceId', FILTER_VALIDATE_INT);

    if (!$clientId || !$typeId || !$serviceId) {
        die("Error: Datos del formulario incompletos o inválidos.");
    }

    try {
        // Iniciar una transacción para asegurar la consistencia relacional de todas las tablas
        $pdo->beginTransaction();

        // A. Insertar la cabecera en la tabla principal `ac`
        $stmtInsertAC = $pdo->prepare("
            INSERT INTO ac (clientId, typeId, serviceId, riskScore, riskLevel) 
            VALUES (:clientId, :typeId, :serviceId, 0, 'Por evaluar')
        ");
        $stmtInsertAC->execute([
            ':clientId'  => $clientId,
            ':typeId'    => $typeId,
            ':serviceId' => $serviceId
        ]);
        $acId = $pdo->lastInsertId();

        // B. Inicializar respuestas vacías para las 30 preguntas maestras
        $questions = $pdo->query("SELECT questionId FROM ac_questions ORDER BY questionId ASC")->fetchAll(PDO::FETCH_COLUMN);
        
        $stmtInsertAnswer = $pdo->prepare("
            INSERT INTO ac_general_answers (acId, questionId, response, comment) 
            VALUES (:acId, :questionId, NULL, '')
        ");
        foreach ($questions as $qId) {
            $stmtInsertAnswer->execute([
                ':acId'       => $acId,
                ':questionId' => $qId
            ]);
        }

        // C. Inicializar respuestas en 'No Aplica' (0 puntos) para las 21 subpruebas asociadas a la Q28
        $tests = $pdo->query("SELECT testId FROM ac_q28_tests ORDER BY testId ASC")->fetchAll(PDO::FETCH_COLUMN);

        $stmtInsertTestAnswer = $pdo->prepare("
            INSERT INTO ac_q28_answers (acId, testId, riskValue, score) 
            VALUES (:acId, :testId, 'No Aplica', 0)
        ");
        foreach ($tests as $tId) {
            $stmtInsertTestAnswer->execute([
                ':acId'   => $acId,
                ':testId' => $tId
            ]);
        }

        // Confirmar la transacción de forma exitosa
        $pdo->commit();

        // Redirigir directamente al archivo interactivo para responder el cuestionario creado
        header("Location: responder.php?acId=" . $acId);
        exit;

    } catch (PDOException $e) {
        // En caso de fallar algo, revertimos todos los pasos anteriores para no dejar basura en la BD
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error en el sistema al iniciar la evaluación: " . $e->getMessage());
    }
}

// 2. Definimos el título de la página para que el header común lo asimile
$pageTitle = "Iniciar Aceptación y Continuidad";

// 3. Incluimos el encabezado común de tu sistema
include '../main/h.php'; 
?>

<div class="container" style="max-width: 700px; margin-top: 20px;">
    <header>
        <img src="../main/logo.png" alt="Logo Corporativo" class="brand-logo" style="cursor: pointer;" onclick="window.location.href='../index.php'">
        <h1>
            <i class="ri-shield-check-line"></i> Aceptación y Continuidad
        </h1>
        <a href="index.php" class="btn-back"><i class="ri-arrow-left-line"></i></a>
    </header>

    <div class="card">
        <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">
            Iniciar Nueva Evaluación
        </h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
            Selecciona el cliente corporativo, el tipo de evaluación y el servicio específico a generar.
        </p>

        <form action="nuevo.php" method="POST">
            
            <div class="form-group">
                <label for="clientId">Cliente / Empresa Activa</label>
                <select name="clientId" id="clientId" required>
                    <option value="" disabled selected>-- Selecciona un Cliente --</option>
                    <?php
                    try {
                        $stmtClients = $pdo->query("SELECT id, name FROM clientes WHERE status = 'Activo' ORDER BY name ASC");
                        $clients = $stmtClients->fetchAll(PDO::FETCH_OBJ);
                        
                        foreach ($clients as $client) {
                            $safeName = htmlspecialchars($client->name, ENT_QUOTES, 'UTF-8');
                            echo "<option value='{$client->id}'>{$safeName}</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value='' disabled>Error al cargar clientes</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label for="typeId">Tipo de Evaluación</label>
                <select name="typeId" id="typeId" required>
                    <option value="" disabled selected>-- Selecciona Tipo de Evaluación --</option>
                    <?php
                    try {
                        $stmtTypes = $pdo->query("SELECT typeId, typeName FROM ac_types ORDER BY typeId ASC");
                        $types = $stmtTypes->fetchAll(PDO::FETCH_OBJ);
                        
                        foreach ($types as $type) {
                            $safeTypeName = htmlspecialchars($type->typeName, ENT_QUOTES, 'UTF-8');
                            echo "<option value='{$type->typeId}'>{$safeTypeName}</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value='' disabled>Error al cargar tipos de evaluación</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label for="serviceId">Servicio a Prestar</label>
                <select name="serviceId" id="serviceId" required>
                    <option value="" disabled selected>-- Selecciona el Servicio --</option>
                    <?php
                    try {
                        $stmtServices = $pdo->query("SELECT serviceId, serviceName FROM ac_services ORDER BY serviceId ASC");
                        $services = $stmtServices->fetchAll(PDO::FETCH_OBJ);
                        
                        foreach ($services as $service) {
                            $safeServiceName = htmlspecialchars($service->serviceName, ENT_QUOTES, 'UTF-8');
                            echo "<option value='{$service->serviceId}'>{$safeServiceName}</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value='' disabled>Error al cargar el catálogo de servicios</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="actions" style="margin-top: 2.5rem;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-checkbox-circle-line"></i> Crear Evaluación
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
// 4. Incluimos el pie de página común de tu sistema
include '../main/footer.php'; 
?>