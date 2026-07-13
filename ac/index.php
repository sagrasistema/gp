<?php 
// v/ac/index.php

// 1. Definimos el título de la página antes del header para que se actualice correctamente
$pageTitle = "Aceptación y Continuidad";

// 2. Incluimos el encabezado común de tu sistema (Que está en main/h.php)
include '../main/h.php'; 

// 3. Incluimos tu archivo de conexión (Instancia $pdo)
include '../main/config.php'; 
?>

<div class="container">
    <header>
        <img src="../main/logo.png" alt="Logo Corporativo" class="brand-logo" style="cursor: pointer;" onclick="window.location.href='../index.php'">
        <h1>
            <i class="ri-shield-check-line"></i> Aceptación y Continuidad
        </h1>
        <div class="header-actions">
            <a href="../index.php" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Menú Principal</a>
            <a href="nuevo.php" class="btn btn-primary"><i class="ri-add-line"></i> Nueva Evaluación</a>
        </div>
    </header>

    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 12%;">ID AC</th>
                    <th style="width: 33%;">Cliente / Empresa</th>
                    <th style="width: 25%;">Tipo de Evaluación</th>
                    <th style="width: 15%;">Fecha Creación</th>
                    <th style="width: 15%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    // Consulta relacional usando la estructura de tu base de datos
                    $query = "SELECT 
                                a.acId, 
                                c.name AS clientName, 
                                t.typeName, 
                                a.created_at 
                              FROM ac a
                              INNER JOIN clientes c ON a.clientId = c.id
                              INNER JOIN ac_types t ON a.typeId = t.typeId
                              ORDER BY a.acId DESC";
                    
                    // Ejecución limpia mediante PDO
                    $stmt = $pdo->query($query);
                    $evaluaciones = $stmt->fetchAll(PDO::FETCH_OBJ);
                    
                    if (!empty($evaluaciones)) {
                        foreach ($evaluaciones as $ac) {
                            // Mitigación de XSS para renderizado seguro
                            $clientName = htmlspecialchars($ac->clientName, ENT_QUOTES, 'UTF-8');
                            $typeName   = htmlspecialchars($ac->typeName, ENT_QUOTES, 'UTF-8');
                            $fecha      = date('d/m/Y', strtotime($ac->created_at));

                            echo "<tr>";
                            echo "<td style='font-weight: 600; color: var(--text-muted);'>#{$ac->acId}</td>";
                            echo "<td><strong>{$clientName}</strong></td>";
                            echo "<td>{$typeName}</td>";
                            echo "<td>{$fecha}</td>";
                            echo "<td style='text-align: center;'>
                                    <div class=\"actions-cell\">
                                        <a href='formularios.php?aid={$ac->acId}' class='btn btn-secondary' style='padding: 0.4rem 0.8rem; font-size: 0.85rem; gap: 0.25rem;'>
                                            <i class='ri-file-list-3-line'></i> Responder
                                        </a>
                                    </div>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        // Mensaje amigable integrado al diseño de tu tabla si está vacía
                        echo "<tr>";
                        echo "<td colspan='5' style='text-align: center; color: var(--text-muted); padding: 3rem;'>";
                        echo "<i class='ri-folder-open-line' style='font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;'></i>";
                        echo "No se han encontrado evaluaciones de Aceptación y Continuidad iniciadas.<br>Haz clic en 'Nueva Evaluación' para registrar la primera.";
                        echo "</td>";
                        echo "</tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr>";
                    echo "<td colspan='5' style='text-align: center; color: var(--status-inactivo); font-weight: bold; padding: 2rem;'>";
                    echo "<i class='ri-error-warning-line'></i> Error al conectar con el módulo de datos.";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// 4. Incluimos el pie de página común de tu sistema
include '../main/footer.php'; 
?>