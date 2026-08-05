<?php
// v/proyectos/actividades.php
declare(strict_types=1);

include '../main/config.php';

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);
$pruebaId = filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT);

if (!$proyectoId || !$pruebaId) {
    die("Error: Parámetros relacionales faltantes.");
}

/**
 * Convierte un número en formato venezolano (ej. "5.000.000,00") a un float estándar de PHP.
 */
function parseVenezuelanNumber(?string $value): float {
    if ($value === null || trim($value) === '') {
        return 0.00;
    }
    $clean = str_replace(['.', ' '], ['', ''], $value);
    $clean = str_replace(',', '.', $clean);
    
    return filter_var($clean, FILTER_VALIDATE_FLOAT) !== false ? (float)$clean : 0.00;
}

// 1. Cargar Cabecera del Proyecto y Datos del Cliente
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS clientName, c.rif AS clientRif
        FROM proyectos p 
        INNER JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $proyectoId]);
    $projectData = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$projectData) {
        die("Error: El proyecto solicitado no existe.");
    }
} catch (PDOException $e) {
    error_log("Error crítico en cabecera de proyecto: " . $e->getMessage());
    die("Error crítico de base de datos al cargar el proyecto.");
}

// 2. Cargar metadatos de la Prueba y su Estatus Actual

// 2. Cargar metadatos de la Prueba y su Modelo desde la BD
try {
    $stmtPrueba = $pdo->prepare("
        SELECT p.nombre, p.norma, p.modelo, c.nombre AS catNombre 
        FROM audit_pruebas p 
        INNER JOIN audit_categorias c ON p.categoria_id = c.id 
        WHERE p.id = :pId
    ");
    $stmtPrueba->execute([':pId' => $pruebaId]);
    $metaPrueba = $stmtPrueba->fetch(PDO::FETCH_OBJ);

    if (!$metaPrueba) {
        die("Error: La prueba especificada no existe.");
    }

    // DISCRIMINADOR DE MODELO: Extraemos el modelo directamente del resultado de la BD
    // (Nota: asegúrate de que la columna en tu tabla audit_pruebas se llame 'modelo')
    $modeloPrueba = $metaPrueba->modelo ?? null;

    $stmtStatus = $pdo->prepare("
        SELECT estado FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :projId AND prueba_id = :prId
    ");
    $stmtStatus->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
    $estadoActualPrueba = $stmtStatus->fetchColumn() ?: 'en_proceso';

} catch (PDOException $e) {
    die("Error al cargar metadatos: " . $e->getMessage());
}



// 3. Procesamiento POST (Guardado de actividades, estatus, materialidad, matriz de riesgos e indicadores)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? 'save_all';

    try {
        $pdo->beginTransaction();
        ##
        // Interceptar la petición POST del modal de la Prueba 11
        // Procesamiento específico para la Prueba 16 (Materialidad)
        if ((int)$pruebaId === 16 && isset($_POST['materialidad']) && is_array($_POST['materialidad'])) {
            $mat = $_POST['materialidad'];
            
            $ben_m   = parseVenezuelanNumber($mat['beneficios_monto'] ?? null);
            $tram_p  = parseVenezuelanNumber($mat['tramo_porc'] ?? null);
            $tram_m  = parseVenezuelanNumber($mat['tramo_monto'] ?? null);
            $imp_ini = parseVenezuelanNumber($mat['importancia_inicial_monto'] ?? null);
            $rec_p   = parseVenezuelanNumber($mat['recorte_porc'] ?? null);
            $rec_m   = parseVenezuelanNumber($mat['recorte_monto'] ?? null);
            $imp_aju = parseVenezuelanNumber($mat['importancia_ajustada_monto'] ?? null);
            $min_p   = parseVenezuelanNumber($mat['minimis_porc'] ?? null);
            $min_m   = parseVenezuelanNumber($mat['minimis_monto'] ?? null);
            $min_s   = parseVenezuelanNumber($mat['minimis_secundario_monto'] ?? null);

            $stmtMatSave = $pdo->prepare("
                INSERT INTO proyecto_materialidad 
                (proyecto_id, prueba_id, beneficios_monto, tramo_porc, tramo_monto, importancia_inicial_monto, recorte_porc, recorte_monto, importancia_ajustada_monto, minimis_porc, minimis_monto, minimis_secundario_monto)
                VALUES (:proj, :pr, :ben_m, :tram_p, :tram_m, :imp_ini, :rec_p, :rec_m, :imp_aju, :min_p, :min_m, :min_s)
                ON DUPLICATE KEY UPDATE 
                    beneficios_monto = :ben_m_u, 
                    tramo_porc = :tram_p_u, 
                    tramo_monto = :tram_m_u, 
                    importancia_inicial_monto = :imp_ini_u, 
                    recorte_porc = :rec_p_u, 
                    recorte_monto = :rec_m_u, 
                    importancia_ajustada_monto = :imp_aju_u, 
                    minimis_porc = :min_p_u, 
                    minimis_monto = :min_m_u, 
                    minimis_secundario_monto = :min_s_u
            ");

            $dataMat = [
                ':proj'     => $proyectoId,
                ':pr'       => $pruebaId,
                ':ben_m'    => $ben_m,
                ':tram_p'   => $tram_p,
                ':tram_m'   => $tram_m,
                ':imp_ini'  => $imp_ini,
                ':rec_p'    => $rec_p,
                ':rec_m'    => $rec_m,
                ':imp_aju'  => $imp_aju,
                ':min_p'    => $min_p,
                ':min_m'    => $min_m,
                ':min_s'    => $min_s,
                ':ben_m_u'  => $ben_m,
                ':tram_p_u' => $tram_p,
                ':tram_m_u' => $tram_m,
                ':imp_ini_u'=> $imp_ini,
                ':rec_p_u'  => $rec_p,
                ':rec_m_u'  => $rec_m,
                ':imp_aju_u'=> $imp_aju,
                ':min_p_u'  => $min_p,
                ':min_m_u'  => $min_m,
                ':min_s_u'  => $min_s,
            ];

            $stmtMatSave->execute($dataMat);
        }

        // NUEVO: Procesamiento específico para la Pregunta 23 (Matriz de Riesgos / Formulario de 8 campos)
        #if ((int)$pruebaId === 23 && isset($_POST['matriz_riesgos']) && is_array($_POST['matriz_riesgos'])) {
         #   $mr = $_POST['matriz_riesgos'];

        // Cargar los datos de materialidad para la vista si es la prueba 16
        $materialidadData = null;
        if ((int)$pruebaId === 16) {
            $stmtMatGet = $pdo->prepare("SELECT * FROM proyecto_materialidad WHERE proyecto_id = :proj AND prueba_id = :pr");
            $stmtMatGet->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
            $materialidadData = $stmtMatGet->fetch(PDO::FETCH_OBJ);
        }

        // NUEVO: Cargar los datos de la Matriz de Riesgos para la vista si es la prueba 23


        if ($action === 'add_indicador_detalle') {
            $tipoInd = filter_input(INPUT_POST, 'tipo_indicador', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $rubro = trim($_POST['rubro'] ?? '');
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $recomendacion = trim($_POST['recomendacion'] ?? '');

            if (in_array($tipoInd, ['CI', 'CG', 'SC', 'AA']) && !empty($titulo)) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO proyecto_indicador_detalles (proyecto_id, prueba_id, tipo_indicador, rubro, titulo, descripcion, recomendacion)
                    VALUES (:proj, :pr, :tipo, :rubro, :titulo, :desc, :rec)
                ");
                $stmtIns->execute([
                    ':proj' => $proyectoId, ':pr' => $pruebaId, ':tipo' => $tipoInd,
                    ':rubro' => $rubro, ':titulo' => $titulo, ':desc' => $descripcion, ':rec' => $recomendacion
                ]);
            }
        } elseif ($action === 'delete_indicador_detalle') {
            $detalleId = filter_input(INPUT_POST, 'detalle_id', FILTER_VALIDATE_INT);
            if ($detalleId) {
                $stmtDel = $pdo->prepare("DELETE FROM proyecto_indicador_detalles WHERE id = :id AND proyecto_id = :proj AND prueba_id = :pr");
                $stmtDel->execute([':id' => $detalleId, ':proj' => $proyectoId, ':pr' => $pruebaId]);
            }
        } elseif ($action === 'save_modelo_6') {
            // =========================================================================
            // INICIO PROCESAMIENTO MODELO 6
            // =========================================================================
            
            // 1. Sanitización de Textos
            $partida = trim($_POST['partida_estado_financiero'] ?? '');
            $fecha   = trim($_POST['fecha_periodo_prueba'] ?? '');
            $exp     = trim($_POST['desarrollar_expectativa'] ?? '');
            $def     = trim($_POST['definicion_diferencia_umbral'] ?? '');
            $det     = trim($_POST['determinacion_diferencias'] ?? '');
            $eval    = trim($_POST['evaluacion_resultados'] ?? '');

            // 2. Uso de tu función parseVenezuelanNumber para parseo numérico seguro
            $impGen  = parseVenezuelanNumber($_POST['importancia_relativa_general'] ?? null);
            $impPlan = parseVenezuelanNumber($_POST['importancia_relativa_planificacion'] ?? null);
            $sud     = parseVenezuelanNumber($_POST['nivel_registro_sud'] ?? null);

            // 3. Aserciones (Booleanos)
            $aser_c  = isset($_POST['aser_c']) ? 1 : 0;
            $aser_a  = isset($_POST['aser_a']) ? 1 : 0;
            $aser_eo = isset($_POST['aser_eo']) ? 1 : 0;
            $aser_co = isset($_POST['aser_co']) ? 1 : 0;
            $aser_ro = isset($_POST['aser_ro']) ? 1 : 0;
            $aser_va = isset($_POST['aser_va']) ? 1 : 0;
            $aser_pd = isset($_POST['aser_pd']) ? 1 : 0;

            // 4. Upsert (Insertar o Actualizar)
            $stmtM6Save = $pdo->prepare("
                INSERT INTO audit_modelo_6_detalles (
                    proyecto_id, prueba_id, partida_estado_financiero, fecha_periodo_prueba,
                    importancia_relativa_general, importancia_relativa_planificacion, nivel_registro_sud,
                    aser_c, aser_a, aser_eo, aser_co, aser_ro, aser_va, aser_pd,
                    desarrollar_expectativa, definicion_diferencia_umbral, determinacion_diferencias, evaluacion_resultados
                ) VALUES (
                    :proj, :pr, :partida, :fecha,
                    :imp_gen, :imp_plan, :sud,
                    :aser_c, :aser_a, :aser_eo, :aser_co, :aser_ro, :aser_va, :aser_pd,
                    :exp, :def, :det, :eval
                ) ON DUPLICATE KEY UPDATE 
                    partida_estado_financiero = :partida_u, fecha_periodo_prueba = :fecha_u,
                    importancia_relativa_general = :imp_gen_u, importancia_relativa_planificacion = :imp_plan_u, nivel_registro_sud = :sud_u,
                    aser_c = :aser_c_u, aser_a = :aser_a_u, aser_eo = :aser_eo_u, aser_co = :aser_co_u, aser_ro = :aser_ro_u, aser_va = :aser_va_u, aser_pd = :aser_pd_u,
                    desarrollar_expectativa = :exp_u, definicion_diferencia_umbral = :def_u, determinacion_diferencias = :det_u, evaluacion_resultados = :eval_u
            ");

            $stmtM6Save->execute([
                ':proj' => $proyectoId, ':pr' => $pruebaId, ':partida' => $partida, ':fecha' => $fecha,
                ':imp_gen' => $impGen, ':imp_plan' => $impPlan, ':sud' => $sud,
                ':aser_c' => $aser_c, ':aser_a' => $aser_a, ':aser_eo' => $aser_eo, ':aser_co' => $aser_co, ':aser_ro' => $aser_ro, ':aser_va' => $aser_va, ':aser_pd' => $aser_pd,
                ':exp' => $exp, ':def' => $def, ':det' => $det, ':eval' => $eval,
                // Parámetros duplicados para el UPDATE seguro
                ':partida_u' => $partida, ':fecha_u' => $fecha,
                ':imp_gen_u' => $impGen, ':imp_plan_u' => $impPlan, ':sud_u' => $sud,
                ':aser_c_u' => $aser_c, ':aser_a_u' => $aser_a, ':aser_eo_u' => $aser_eo, ':aser_co_u' => $aser_co, ':aser_ro_u' => $aser_ro, ':aser_va_u' => $aser_va, ':aser_pd_u' => $aser_pd,
                ':exp_u' => $exp, ':def_u' => $def, ':det_u' => $det, ':eval_u' => $eval
            ]);
            
            // =========================================================================
            // FIN PROCESAMIENTO MODELO 6
            // =========================================================================
        } elseif ($action === 'save_modelo_5') {
            // =========================================================================
            // INICIO PROCESAMIENTO MODELO 5
            // =========================================================================
            
            // 1. Textos Generales
            $partida = trim($_POST['partida_estado_financiero'] ?? '');
            $fecha_1 = trim($_POST['fecha_periodo_prueba'] ?? '');
            
            $pob_inf = trim($_POST['poblacion_informe'] ?? '');
            $pob_fec = trim($_POST['poblacion_fecha'] ?? '');
            $pob_n   = (int)($_POST['poblacion_n_partidas'] ?? 0);
            
            // 2. Textos Enriquecidos (Se permite HTML si usas editores WYSIWYG, pero filtramos scripts si es posible o usamos htmlspecialchars al imprimir)
            $proc_realizado = trim($_POST['procedimiento_realizado'] ?? '');
            $doc_excepcion  = trim($_POST['documentar_excepciones'] ?? '');
            $def_error      = trim($_POST['definicion_error'] ?? '');
            $doc_resultado  = trim($_POST['documentar_resultado'] ?? '');
            $eval_result    = trim($_POST['evaluacion_resultados'] ?? '');

            // 3. Montos Financieros (Usando tu función parseVenezuelanNumber)
            $impGen  = parseVenezuelanNumber($_POST['importancia_relativa_general'] ?? null);
            $impPlan = parseVenezuelanNumber($_POST['importancia_relativa_planificacion'] ?? null);
            $sud     = parseVenezuelanNumber($_POST['nivel_registro_sud'] ?? null);
            $pob_val = parseVenezuelanNumber($_POST['poblacion_valor_total'] ?? null);

            // 4. Aserciones (Booleanos)
            $aser_c  = isset($_POST['aser_c']) ? 1 : 0;
            $aser_a  = isset($_POST['aser_a']) ? 1 : 0;
            $aser_eo = isset($_POST['aser_eo']) ? 1 : 0;
            $aser_co = isset($_POST['aser_co']) ? 1 : 0;
            $aser_ro = isset($_POST['aser_ro']) ? 1 : 0;
            $aser_va = isset($_POST['aser_va']) ? 1 : 0;
            $aser_pd = isset($_POST['aser_pd']) ? 1 : 0;

            // 5. Tabla de Selección
            $cob_n = (int)($_POST['cobertura_n'] ?? 0);       $cob_m = parseVenezuelanNumber($_POST['cobertura_monto'] ?? null);       $cob_p = parseVenezuelanNumber($_POST['cobertura_porcentaje'] ?? null);
            $rie_n = (int)($_POST['riesgo_n'] ?? 0);          $rie_m = parseVenezuelanNumber($_POST['riesgo_monto'] ?? null);          $rie_p = parseVenezuelanNumber($_POST['riesgo_porcentaje'] ?? null);
            $imp_n = (int)($_POST['impredecibles_n'] ?? 0);   $imp_m = parseVenezuelanNumber($_POST['impredecibles_monto'] ?? null);   $imp_p = parseVenezuelanNumber($_POST['impredecibles_porcentaje'] ?? null);
            $pro_n = (int)($_POST['probado_n'] ?? 0);         $pro_m = parseVenezuelanNumber($_POST['probado_monto'] ?? null);         $pro_p = parseVenezuelanNumber($_POST['probado_porcentaje'] ?? null);
            $nop_n = (int)($_POST['no_probado_n'] ?? 0);      $nop_m = parseVenezuelanNumber($_POST['no_probado_monto'] ?? null);      $nop_p = parseVenezuelanNumber($_POST['no_probado_porcentaje'] ?? null);

            // 6. Ejecutar Upsert
            $stmtM5Save = $pdo->prepare("
                INSERT INTO audit_modelo_5_detalles (
                    proyecto_id, prueba_id, partida_estado_financiero, fecha_periodo_prueba, importancia_relativa_general, importancia_relativa_planificacion, nivel_registro_sud,
                    aser_c, aser_a, aser_eo, aser_co, aser_ro, aser_va, aser_pd,
                    poblacion_informe, poblacion_fecha, poblacion_valor_total, poblacion_n_partidas, procedimiento_realizado, documentar_excepciones,
                    definicion_error,
                    cobertura_n, cobertura_monto, cobertura_porcentaje, riesgo_n, riesgo_monto, riesgo_porcentaje, impredecibles_n, impredecibles_monto, impredecibles_porcentaje, probado_n, probado_monto, probado_porcentaje, no_probado_n, no_probado_monto, no_probado_porcentaje,
                    documentar_resultado, evaluacion_resultados
                ) VALUES (
                    :proj, :pr, :p1, :p2, :p3, :p4, :p5,
                    :a1, :a2, :a3, :a4, :a5, :a6, :a7,
                    :po1, :po2, :po3, :po4, :po5, :po6,
                    :de1,
                    :t1n, :t1m, :t1p, :t2n, :t2m, :t2p, :t3n, :t3m, :t3p, :t4n, :t4m, :t4p, :t5n, :t5m, :t5p,
                    :dr1, :dr2
                ) ON DUPLICATE KEY UPDATE 
                    partida_estado_financiero = VALUES(partida_estado_financiero), fecha_periodo_prueba = VALUES(fecha_periodo_prueba), importancia_relativa_general = VALUES(importancia_relativa_general), importancia_relativa_planificacion = VALUES(importancia_relativa_planificacion), nivel_registro_sud = VALUES(nivel_registro_sud),
                    aser_c = VALUES(aser_c), aser_a = VALUES(aser_a), aser_eo = VALUES(aser_eo), aser_co = VALUES(aser_co), aser_ro = VALUES(aser_ro), aser_va = VALUES(aser_va), aser_pd = VALUES(aser_pd),
                    poblacion_informe = VALUES(poblacion_informe), poblacion_fecha = VALUES(poblacion_fecha), poblacion_valor_total = VALUES(poblacion_valor_total), poblacion_n_partidas = VALUES(poblacion_n_partidas), procedimiento_realizado = VALUES(procedimiento_realizado), documentar_excepciones = VALUES(documentar_excepciones),
                    definicion_error = VALUES(definicion_error),
                    cobertura_n = VALUES(cobertura_n), cobertura_monto = VALUES(cobertura_monto), cobertura_porcentaje = VALUES(cobertura_porcentaje), riesgo_n = VALUES(riesgo_n), riesgo_monto = VALUES(riesgo_monto), riesgo_porcentaje = VALUES(riesgo_porcentaje), impredecibles_n = VALUES(impredecibles_n), impredecibles_monto = VALUES(impredecibles_monto), impredecibles_porcentaje = VALUES(impredecibles_porcentaje), probado_n = VALUES(probado_n), probado_monto = VALUES(probado_monto), probado_porcentaje = VALUES(probado_porcentaje), no_probado_n = VALUES(no_probado_n), no_probado_monto = VALUES(no_probado_monto), no_probado_porcentaje = VALUES(no_probado_porcentaje),
                    documentar_resultado = VALUES(documentar_resultado), evaluacion_resultados = VALUES(evaluacion_resultados)
            ");

            // Bind param masivo (usando array indexado para simplificar lectura)
            $stmtM5Save->execute([
                ':proj' => $proyectoId, ':pr' => $pruebaId, ':p1' => $partida, ':p2' => $fecha_1, ':p3' => $impGen, ':p4' => $impPlan, ':p5' => $sud,
                ':a1' => $aser_c, ':a2' => $aser_a, ':a3' => $aser_eo, ':a4' => $aser_co, ':a5' => $aser_ro, ':a6' => $aser_va, ':a7' => $aser_pd,
                ':po1' => $pob_inf, ':po2' => $pob_fec, ':po3' => $pob_val, ':po4' => $pob_n, ':po5' => $proc_realizado, ':po6' => $doc_excepcion,
                ':de1' => $def_error,
                ':t1n' => $cob_n, ':t1m' => $cob_m, ':t1p' => $cob_p, ':t2n' => $rie_n, ':t2m' => $rie_m, ':t2p' => $rie_p, ':t3n' => $imp_n, ':t3m' => $imp_m, ':t3p' => $imp_p, ':t4n' => $pro_n, ':t4m' => $pro_m, ':t4p' => $pro_p, ':t5n' => $nop_n, ':t5m' => $nop_m, ':t5p' => $nop_p,
                ':dr1' => $doc_resultado, ':dr2' => $eval_result
            ]);
            
            // =========================================================================
            // FIN PROCESAMIENTO MODELO 5
            // ====================================================================
        } else {
            // Guardado General (Actividades + Estatus de Prueba)
           // Guardado General (Actividades + Archivos Adjuntos + Estatus de Prueba)
            if (isset($_POST['actividades_data']) && is_array($_POST['actividades_data'])) {
                $stmtSave = $pdo->prepare("
                    INSERT INTO proyecto_actividades_ejecucion 
                        (proyecto_id, actividad_id, contenido_llenado, completado, archivo_nombre, archivo_ruta, archivo_peso)
                    VALUES 
                        (:proyecto_id, :actividad_id, :contenido, :completado, :archivo_nombre, :archivo_ruta, :archivo_peso)
                    ON DUPLICATE KEY UPDATE 
                        contenido_llenado = :contenido_u, 
                        completado = :completado_u,
                        archivo_nombre = COALESCE(:archivo_nombre_u, archivo_nombre),
                        archivo_ruta = COALESCE(:archivo_ruta_u, archivo_ruta),
                        archivo_peso = COALESCE(:archivo_peso_u, archivo_peso)
                ");

                $uploadDir = '../uploads/proyectos/actividades/';
                $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'zip', 'rar'];
                $maxFileSize = 10 * 1024 * 1024; // Límite de 10 MB

                foreach ($_POST['actividades_data'] as $actId => $v) {
                    $actIdInt = (int)$actId;
                    $contenido = trim($v['contenido'] ?? '');
                    $completado = isset($v['completado']) ? 1 : 0;

                    $archivoNombreOriginal = null;
                    $archivoRutaGuardar   = null;
                    $archivoPeso           = null;

                    // Procesar archivo adjunto especifico para esta actividad
                    $fileKey = "actividad_archivo_{$actIdInt}";
                    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES[$fileKey];

                        if ($file['size'] > $maxFileSize) {
                            throw new Exception("El archivo adjunto para la actividad ID {$actIdInt} excede el límite permitido de 10MB.");
                        }

                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, $allowedExtensions, true)) {
                            throw new Exception("Tipo de archivo no permitido en la actividad ID {$actIdInt}. Extensiones válidas: " . implode(', ', $allowedExtensions));
                        }

                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $safeFileName = bin2hex(random_bytes(16)) . '.' . $ext;
                        $destinationPath = $uploadDir . $safeFileName;
                        $archivoRutaRelativa = 'uploads/proyectos/actividades/' . $safeFileName;

                        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
                            throw new Exception("Error al guardar el archivo adjunto en el servidor para la actividad ID {$actIdInt}.");
                        }

                        $archivoNombreOriginal = $file['name'];
                        $archivoRutaGuardar   = $archivoRutaRelativa;
                        $archivoPeso           = (int)$file['size'];
                    }

                    $stmtSave->execute([
                        ':proyecto_id'      => $proyectoId,
                        ':actividad_id'     => $actIdInt,
                        ':contenido'        => $contenido !== '' ? $contenido : null,
                        ':completado'       => $completado,
                        ':archivo_nombre'   => $archivoNombreOriginal,
                        ':archivo_ruta'     => $archivoRutaGuardar,
                        ':archivo_peso'     => $archivoPeso,
                        ':contenido_u'      => $contenido !== '' ? $contenido : null,
                        ':completado_u'     => $completado,
                        ':archivo_nombre_u' => $archivoNombreOriginal,
                        ':archivo_ruta_u'   => $archivoRutaGuardar,
                        ':archivo_peso_u'   => $archivoPeso
                    ]);
                }
            }
        }

        $nuevoEstadoPrueba = trim($_POST['estado_prueba'] ?? $estadoActualPrueba);

        // VALIDACIÓN DE NEGOCIO: Si intenta colocar el estado como 'completado', verificar que todas las actividades estén finalizadas
        if ($nuevoEstadoPrueba === 'completado') {
            $stmtCheckAct = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM audit_actividades WHERE prueba_id = :prId1) AS total_actividades,
                    (SELECT COUNT(*) FROM proyecto_actividades_ejecucion ae 
                     INNER JOIN audit_actividades a ON ae.actividad_id = a.id 
                     WHERE ae.proyecto_id = :projId1 AND a.prueba_id = :prId2 AND ae.completado = 1) AS completadas
            ");
            $stmtCheckAct->execute([
                ':prId1'   => $pruebaId,
                ':projId1' => $proyectoId,
                ':prId2'   => $pruebaId
            ]);
            $resAct = $stmtCheckAct->fetch(PDO::FETCH_OBJ);

            if ($resAct && (int)$resAct->total_actividades > 0 && (int)$resAct->completadas < (int)$resAct->total_actividades) {
                throw new Exception("Acción no permitida: No se puede cambiar el estado a 'Completado' porque existen actividades pendientes de finalizar.");
            }
        }

        // SINCRONIZACIÓN AUTOMÁTICA: Verificar existencia de registros en detalles para cada indicador
        $hasCI = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CI'")->fetchColumn() > 0 ? 1 : 0;
        $hasCG = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CG'")->fetchColumn() > 0 ? 1 : 0;
        $hasSC = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='SC'")->fetchColumn() > 0 ? 1 : 0;
        $hasAA = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='AA'")->fetchColumn() > 0 ? 1 : 0;
        $obsLider = trim($_POST['observacion_socio_lider'] ?? '');
        $obsCalidad = trim($_POST['observacion_socio_calidad'] ?? '');

        $stmtTestSave = $pdo->prepare("
            INSERT INTO proyecto_pruebas_ejecucion 
            (proyecto_id, prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado, observacion_socio_lider, observacion_socio_calidad)
            VALUES (:proyecto_id, :prueba_id, :ci, :cg, :sc, :aa, :estado, :obs_lider, :obs_calidad)
            ON DUPLICATE KEY UPDATE 
                indicador_ci = :ci_u, indicador_cg = :cg_u, indicador_sc = :sc_u, indicador_aa = :aa_u, 
                estado = :estado_u, observacion_socio_lider = :obs_lider_u, observacion_socio_calidad = :obs_calidad_u
        ");
        $stmtTestSave->execute([
            ':proyecto_id' => $proyectoId, ':prueba_id' => $pruebaId,
            ':ci' => $hasCI, ':cg' => $hasCG, ':sc' => $hasSC, ':aa' => $hasAA, ':estado' => $nuevoEstadoPrueba,
            ':obs_lider' => $obsLider, ':obs_calidad' => $obsCalidad,
            ':ci_u' => $hasCI, ':cg_u' => $hasCG, ':sc_u' => $hasSC, ':aa_u' => $hasAA, 
            ':estado_u' => $nuevoEstadoPrueba, ':obs_lider_u' => $obsLider, ':obs_calidad_u' => $obsCalidad
        ]);

        $pdo->commit();
        header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMsg = urlencode($e->getMessage());
        header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&error={$errorMsg}");
        exit;
    }
}

// 4. Recuperar Catálogo de Actividades y Detalles de los Indicadores
// 4. Recuperar Catálogo de Actividades y Detalles de los Indicadores
$sqlActividades = "
    SELECT 
        a.id, 
        a.descripcion, 
        a.orden, 
        COALESCE(ae.contenido_llenado, '') AS respuesta, 
        COALESCE(ae.completado, 0) AS is_ok,
        ae.archivo_nombre,
        ae.archivo_ruta,
        ae.archivo_peso
    FROM audit_actividades a
    LEFT JOIN proyecto_actividades_ejecucion ae 
        ON ae.actividad_id = a.id AND ae.proyecto_id = :projId
    WHERE a.prueba_id = :prId 
    ORDER BY a.orden ASC";

$stmtA = $pdo->prepare($sqlActividades);
$stmtA->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
$listaActividades = $stmtA->fetchAll(PDO::FETCH_OBJ);

// Cargar detalles de indicadores agrupados por tipo
$stmtIndDetalles = $pdo->prepare("SELECT * FROM proyecto_indicador_detalles WHERE proyecto_id = :proj AND prueba_id = :pr ORDER BY id DESC");
$stmtIndDetalles->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
$allDetalles = $stmtIndDetalles->fetchAll(PDO::FETCH_OBJ);

$detallesPorTipo = ['CI' => [], 'CG' => [], 'SC' => [], 'AA' => []];
foreach ($allDetalles as $det) {
    $detallesPorTipo[$det->tipo_indicador][] = $det;
}

$stmtStatus = $pdo->prepare("
    SELECT estado, observacion_socio_lider, observacion_socio_calidad 
    FROM proyecto_pruebas_ejecucion 
    WHERE proyecto_id = :projId AND prueba_id = :prId
");
$stmtStatus->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
$datosEjecucion = $stmtStatus->fetch(PDO::FETCH_OBJ);

$estadoActualPrueba = $datosEjecucion->estado ?? 'en_proceso';
$obsSocioLider = $datosEjecucion->observacion_socio_lider ?? '';
$obsSocioCalidad = $datosEjecucion->observacion_socio_calidad ?? '';

// Cargar todos los riesgos registrados para la Prueba 23 en este proyecto
$listaRiesgosProyecto = [];
if ((int)$pruebaId === 23) {
    try {
        $stmtMrGet = $pdo->prepare("SELECT * FROM prueba_23_riesgos WHERE proyecto_id = :proj ORDER BY id DESC");
        $stmtMrGet->execute([':proj' => $proyectoId]);
        $listaRiesgosProyecto = $stmtMrGet->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        error_log("Error al recuperar prueba_23_riesgos: " . $e->getMessage());
    }
}
// =========================================================================
// INICIO LECTURA DATOS MODELO 6 (Precarga de Formulario)
// =========================================================================
$formData = [
    'partida_estado_financiero' => '', 'fecha_periodo_prueba' => '',
    'importancia_relativa_general' => '', 'importancia_relativa_planificacion' => '', 'nivel_registro_sud' => '',
    'aser_c' => 0, 'aser_a' => 0, 'aser_eo' => 0, 'aser_co' => 0, 'aser_ro' => 0, 'aser_va' => 0, 'aser_pd' => 0,
    'desarrollar_expectativa' => '', 'definicion_diferencia_umbral' => '', 'determinacion_diferencias' => '', 'evaluacion_resultados' => ''
];

// Asumiendo que el discriminator es $modeloPrueba === '6', ajústalo a tu BD si es necesario
if (isset($modeloPrueba) && (string)$modeloPrueba === '6') { 
    try {
        $stmtM6Get = $pdo->prepare("SELECT * FROM audit_modelo_6_detalles WHERE proyecto_id = :proj AND prueba_id = :pr LIMIT 1");
        $stmtM6Get->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
        $rowM6 = $stmtM6Get->fetch(PDO::FETCH_ASSOC);
        
        if ($rowM6) {
            // Formatear números al estándar venezolano para la vista si fueron guardados como FLOAT
            $rowM6['importancia_relativa_general'] = number_format((float)$rowM6['importancia_relativa_general'], 2, ',', '.');
            $rowM6['importancia_relativa_planificacion'] = number_format((float)$rowM6['importancia_relativa_planificacion'], 2, ',', '.');
            $rowM6['nivel_registro_sud'] = number_format((float)$rowM6['nivel_registro_sud'], 2, ',', '.');
            
            // Combinar con los defaults para evitar undefined offsets
            $formData = array_merge($formData, $rowM6);
        }
    } catch (PDOException $e) {
        error_log("Error al recuperar Modelo 6: " . $e->getMessage());
    }
}
// =========================================================================
// FIN LECTURA DATOS MODELO 6
// =========================================================================
// =========================================================================
// INICIO LECTURA DATOS MODELO 5 (Precarga de Formulario)
// =========================================================================
if (isset($modeloPrueba) && (string)$modeloPrueba === '5') { 
    $formData5 = [];
    try {
        $stmtM5Get = $pdo->prepare("SELECT * FROM audit_modelo_5_detalles WHERE proyecto_id = :proj AND prueba_id = :pr LIMIT 1");
        $stmtM5Get->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
        $rowM5 = $stmtM5Get->fetch(PDO::FETCH_ASSOC);
        
        if ($rowM5) {
            // Arrays con los campos numéricos a formatear a estándar venezolano
            $monedaFields = [
                'importancia_relativa_general', 'importancia_relativa_planificacion', 'nivel_registro_sud', 'poblacion_valor_total',
                'cobertura_monto', 'riesgo_monto', 'impredecibles_monto', 'probado_monto', 'no_probado_monto',
                'cobertura_porcentaje', 'riesgo_porcentaje', 'impredecibles_porcentaje', 'probado_porcentaje', 'no_probado_porcentaje'
            ];
            
            foreach ($monedaFields as $field) {
                if (isset($rowM5[$field]) && $rowM5[$field] !== null) {
                    $rowM5[$field] = number_format((float)$rowM5[$field], 2, ',', '.');
                }
            }
            $formData5 = $rowM5;
        }
    } catch (PDOException $e) {
        error_log("Error al recuperar Modelo 5: " . $e->getMessage());
    }
}
$pageTitle = "Formulario de Actividades y Hallazgos";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<?php 
$errorMensaje = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!empty($errorMensaje)): 
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    mostrarModalAlertaPersonalizado("<?php echo htmlspecialchars(urldecode($errorMensaje), ENT_QUOTES, 'UTF-8'); ?>");
});

function mostrarModalAlertaPersonalizado(mensaje) {
    let modalExistente = document.getElementById('modal-alerta-actividades');
    if (modalExistente) modalExistente.remove();

    const modalHtml = `
        <div id="modal-alerta-actividades" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
            <div style="background: #ffffff; padding: 2rem; border-radius: 10px; max-width: 400px; width: 90%; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2.5rem; color: #f59e0b; margin-bottom: 1rem;"><i class="ri-alert-line"></i></div>
                <h3 style="margin: 0 0 0.5rem 0; color: #1e293b; font-size: 1.25rem;">Acción No Permitida</h3>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">
                    Debe realizar todas las actividades de esta prueba para poder completarla 
                </p>
                <button type="button" onclick="document.getElementById('modal-alerta-actividades').remove()" style="background: #2563eb; color: white; border: none; padding: 0.65rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    Entendido
                </button>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}
</script>
<?php endif; ?>