
<form action="guardar_actividad.php" method="POST" enctype="multipart/form-data" class="form-actividad">
    <input type="hidden" name="actividad_id" value="123">

    <div class="form-group" style="margin-bottom: 1rem;">
        <label for="archivo_csv" style="font-weight: 600; font-size: 0.85rem;">Anexar datos (Archivo CSV/Excel):</label>
        <input type="file" name="archivo_csv" id="archivo_csv" accept=".csv" class="form-control" style="padding: 0.4rem; font-size: 0.8rem;">
        <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 0.25rem;">
            * El archivo debe estar guardado como <strong>CSV (delimitado por punto y coma ";")</strong> desde Excel.
        </small>
    </div>

    <button type="submit" name="guardar_actividad" class="btn btn-primary">
        <i class="ri-save-line"></i> Guardar Actividad e Importar Datos
    </button>
</form>