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
        $tests = $pdo->query("SELECT testId FROM ac_q28_tests ORDER BY testNumber ASC")->fetchAll(PDO::FETCH_COLUMN);

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
        header("Location: index.php");
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

// 3. Incluimos el encabezado común de tu sistema (Sesiones / Metas)
include '../main/h.php'; 
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
// Configuración dinámica del Layout para que mapee las rutas desde la subcarpeta ac/
$customLogoPath = '../main/logo.png'; 
$customHomePath = '../index.php';     
$customAcPath   = 'index.php';  
$currentTab     = 'aceptacion'; // Mantiene activa la opción "Aceptación" en el menú lateral

include '../main/layout_header.php'; 
?>

<div class="view-container">
    
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-shield-check-line"></i> Aceptación y Continuidad
        </h1>
        
    </div>

    <div class="table-actions-container">
        <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
            <i class="ri-arrow-go-back-line"></i> 
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
            <i class="ri-screenshot-2-line"></i>
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
            <i class="ri-book-open-line"></i> 
        </a>

        <a href="nuevo.php" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
            <i class="ri-add-line"></i>
        </a>

        <a href="../index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>


    <div class="card" style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);">
        <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">
            Iniciar Nueva Evaluación
        </h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">
            Selecciona el cliente corporativo, el tipo de evaluación y el servicio específico a generar.
        </p>

        <form action="nuevo.php" method="POST">
            
            <div class="form-group">
                <label for="clientId" style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Cliente / Empresa Activa</label>
                <select name="clientId" id="clientId" required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.95rem; color: var(--text-main);">
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
                <label for="typeId" style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Tipo de Evaluación</label>
                <select name="typeId" id="typeId" required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.95rem; color: var(--text-main);">
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
                <label for="serviceId" style="display: block; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Servicio a Prestar</label>
                <select name="serviceId" id="serviceId" required style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.95rem; color: var(--text-main);">
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

            <div class="actions" style="margin-top: 2.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-checkbox-circle-line"></i> Crear Evaluación
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
// Renderiza el cierre estructural del bloque flex layout y scripts móviles
include '../main/layout_footer.php'; 

// 4. Incluimos el pie de página nativo común de tu sistema
include '../main/footer.php'; 
?>