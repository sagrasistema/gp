<?php 
// v/ac/index.php

// 1. Incluimos el encabezado común de tu sistema
include '../main/h.php'; 

// 2. Incluimos tu archivo de conexión (Este archivo crea la variable corporativa $pdo)
include '../main/config.php'; 

?>

<div class="container" style="margin-top: 30px;">
    <div class="row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="col s8">
            <h5 style="font-weight: bold; text-transform: uppercase; color: #1e88e5;">Aceptación y Continuidad</h5>
        </div>
        <div class="col s4 right-align">
            <a href="nuevo.php" class="btn-floating btn-large waves-effect waves-light blue tooltipped" data-position="left" data-tooltip="Nueva Evaluación">
                <i class="material-icons">add_circle</i>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col s12">
            <table class="striped highlight responsive-table white" style="border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background-color: #f5f5f5;">
                        <th style="padding-left: 20px; width: 10%;">ID AC</th>
                        <th style="width: 35%;">Cliente</th>
                        <th style="width: 25%;">Tipo de Evaluación</th>
                        <th style="width: 15%;">Fecha Creación</th>
                        <th class="center-align" style="width: 15%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        // Sentencia SQL limpia y optimizada para el motor relacional
                        $query = "SELECT 
                                    a.acId, 
                                    c.name AS clientName, 
                                    t.typeName, 
                                    a.created_at 
                                  FROM ac a
                                  INNER JOIN clientes c ON a.clientId = c.id
                                  INNER JOIN ac_types t ON a.typeId = t.typeId
                                  ORDER BY a.acId DESC";
                        
                        // Ejecución segura a través de la instancia PDO ($pdo)
                        $stmt = $pdo->query($query);
                        $evaluaciones = $stmt->fetchAll(PDO::FETCH_OBJ);
                        
                        // Verificamos si la consulta arrojó registros
                        if (!empty($evaluaciones)) {
                            foreach ($evaluaciones as $ac) {
                                // Mitigación XSS: Sanitización de datos persistidos en la base de datos antes de pintar en el DOM
                                $clientName = htmlspecialchars($ac->clientName, ENT_QUOTES, 'UTF-8');
                                $typeName   = htmlspecialchars($ac->typeName, ENT_QUOTES, 'UTF-8');
                                $fecha      = date('d/m/Y', strtotime($ac->created_at));

                                echo "<tr>";
                                echo "<td style='padding-left: 20px;'>#{$ac->acId}</td>";
                                echo "<td><strong>{$clientName}</strong></td>";
                                echo "<td><span class='chip blue lighten-5 blue-text' style='font-weight: 500;'>{$typeName}</span></td>";
                                echo "<td>{$fecha}</td>";
                                echo "<td class='center-align'>
                                        <a href='formularios.php?aid={$ac->acId}' class='btn-small blue-grey darken-1 waves-effect waves-light' style='border-radius: 4px;'>
                                            <i class='material-icons left'>assignment</i> Responder
                                        </a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            // Mensaje en caso de que la tabla esté vacía
                            echo "<tr>";
                            echo "<td colspan='5' class='center-align grey-text' style='padding: 40px;'>";
                            echo "<i class='material-icons large' style='opacity: 0.3;'>folder_open</i><br>";
                            echo "No se han encontrado evaluaciones de Aceptación y Continuidad iniciadas.<br>Haz clic en el botón '+' superior para registrar la primera.";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        // Manejo defensivo de errores en producción para ocultar trazas de base de datos
                        echo "<tr>";
                        echo "<td colspan='5' class='center-align red-text font-weight-bold' style='padding: 30px;'>";
                        echo "<i class='material-icons left'>error</i> Error interno: No se pudo conectar con el repositorio de datos de auditoría.";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// 3. Cierre común con scripts JavaScript y pie de página de tu sistema
include '../main/footer.php'; 
?>