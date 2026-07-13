<?php 
// v/ac/nuevo.php

// 1. Incluimos tu archivo de conexión primero (Inicializa la variable $pdo)
include '../main/config.php'; 

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

        <form action="../../c/ac.php?action=create" method="POST">
            
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