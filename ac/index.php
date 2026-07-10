<?php 
// v/ac/index.php

// 1. Incluimos el encabezado común de tu sistema
include '../main/header.php'; 

// 2. Incluimos tu archivo de conexión (Ajusta la ruta exacta si tu archivo se llama diferente, por ejemplo: connection.php)
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
                    // Consulta relacional: Trae los datos de 'ac' uniendo el nombre del cliente y el tipo de proceso
                    $query = "SELECT 
                                a.acId, 
                                c.name AS clientName, 
                                t.typeName, 
                                a.created_at 
                              FROM ac a
                              INNER JOIN clientes c ON a.clientId = c.id
                              INNER JOIN ac_types t ON a.typeId = t.typeId
                              ORDER BY a.acId DESC";
                    
                    // Ejecutamos la consulta usando tu variable global '$connection'
                    $_ac = mysqli_query($connection, $query);
                    
                    // Verificamos si la consulta tiene registros creados
                    if ($_ac && mysqli_num_rows($_ac) > 0) {
                        while ($ac = $_ac->fetch_object()) {
                            echo "<tr>";
                            echo "<td style='padding-left: 20px;'>#{$ac->acId}</td>";
                            echo "<td><strong>{$ac->clientName}</strong></td>";
                            echo "<td><span class='chip blue lighten-5 blue-text' style='font-weight: 500;'>{$ac->typeName}</span></td>";
                            echo "<td>" . date('d/m/Y', strtotime($ac->created_at)) . "</td>";
                            echo "<td class='center-align'>
                                    <a href='formularios.php?aid={$ac->acId}' class='btn-small blue-grey darken-1 waves-effect waves-light' style='border-radius: 4px;'>
                                        <i class='material-icons left'>assignment</i> Responder
                                    </a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        // Mensaje amigable en caso de que la tabla de AC esté vacía
                        echo "<tr>";
                        echo "<td colspan='5' class='center-align grey-text' style='padding: 40px;'>";
                        echo "<i class='material-icons large style='opacity: 0.3;'>folder_open</i><br>";
                        echo "No se han encontrado evaluaciones de Aceptación y Continuidad iniciadas.<br>Haz clic en el botón '+' superior para registrar la primera.";
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