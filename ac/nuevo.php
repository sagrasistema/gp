<?php 
// v/ac/nuevo.php
include '../main/header.php'; 
?>

<div class="container" style="margin-top: 30px;">
    <div class="row">
        <div class="col s12 m8 offset-m2">
            <div class="card white" style="border-radius: 8px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <span class="card-title" style="font-weight: bold; color: #1e88e5;">Iniciar Aceptación y Continuidad</span>
                <p class="grey-text">Selecciona un cliente y el tipo de formulario a generar.</p>
                <br>

                <form action="../../c/ac.php?action=create" method="POST">
                    
                    <div class="input-field col s12" style="margin-bottom: 25px;">
                        <select name="clientId" required class="browser-default" style="border: 1px solid #ccc; border-radius: 4px; padding: 10px;">
                            <option value="" disabled selected>-- Selecciona un Cliente --</option>
                            <?php
                            $_clients = mysqli_query($connection, "SELECT id, name FROM clientes WHERE status = 'Activo' ORDER BY name ASC");
                            while ($client = $_clients->fetch_object()) {
                                echo "<option value='{$client->id}'>{$client->name}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-field col s12" style="margin-bottom: 30px;">
                        <select name="typeId" required class="browser-default" style="border: 1px solid #ccc; border-radius: 4px; padding: 10px;">
                            <option value="" disabled selected>-- Selecciona Tipo de Evaluación --</option>
                            <?php
                            $_types = mysqli_query($connection, "SELECT typeId, typeName FROM ac_types ORDER BY typeId ASC");
                            while ($type = $_types->fetch_object()) {
                                echo "<option value='{$type->typeId}'>{$type->typeName}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row" style="margin-top: 20px;">
                        <div class="col s12 right-align">
                            <a href="index.php" class="btn btn-secondary grey lighten-1 black-text waves-effect" style="margin-right: 10px;">Cancelar</a>
                            <button type="submit" class="btn blue waves-effect waves-light">
                                <i class="material-icons left">check_circle</i> Crear Evaluación
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php 
include '../main/footer.php'; 
?>