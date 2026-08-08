<?php
// procesar_diagnostico.php - Controlador de Evaluación y Reglas de Negocio
// Estándar PSR-12 | Compatibilidad PHP 8.x

declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

// Matriz de Reglas de Negocio y Recomendaciones
$reglasCategorias = [
    'GOBERNANZA' => [
        'titulo' => 'Gobernanza y Órganos de Dirección',
        'icon'   => 'ri-government-line',
        'color'  => '#6366f1',
        'recom_critica' => 'Priorice la constitución del Consejo de Familia y delimite las decisiones operativas de las estratégicas.',
        'recom_media'   => 'Formalice la frecuencia de las reuniones de la Junta Directiva e incluya la figura de consejeros independientes.',
        'recom_excelente' => 'El esquema de gobernanza es sólido. Mantenga la auditoría externa y la rendición de cuentas continua.'
    ],
    'PROTOCOLO' => [
        'titulo' => 'Protocolo y Normativa Familiar',
        'icon'   => 'ri-file-paper-2-line',
        'color'  => '#ec4899',
        'recom_critica' => 'Es urgente redactar y firmar un Protocolo Familiar que reglamente el ingreso y remuneración de familiares.',
        'recom_media'   => 'Ajuste la política salarial a valores de mercado y establezca un comité de mediación para conflictos.',
        'recom_excelente' => 'Existe un marco normativo normado eficaz. Revíselo quinquenalmente para adaptarlo a nuevas generaciones.'
    ],
    'SUCESIÓN' => [
        'titulo' => 'Sucesión y Continuidad',
        'icon'   => 'ri-git-merge-line',
        'color'  => '#8b5cf6',
        'recom_critica' => 'Estructure de inmediato un plan de emergencia y un esquema de formación directiva para la siguiente generación.',
        'recom_media'   => 'Defina la hoja de ruta fiscal/legal para la transferencia accionaria y formalice el plan de sucesión del CEO.',
        'recom_excelente' => 'El plan de continuidad está blindado. Asegure la ejecución del programa de liderazgo futuro.'
    ],
    'FINANZAS' => [
        'titulo' => 'Estrategia y Gestión Financiera',
        'icon'   => 'ri-bank-card-line',
        'color'  => '#06b6d4',
        'recom_critica' => 'Separe inmediatamente las finanzas personales de las empresariales e implemente presupuestos mensuales.',
        'recom_media'   => 'Fortalezca el control de flujo de caja y estipule políticas formales para el endeudamiento e inversión.',
        'recom_excelente' => 'Excelente disciplina financiera. Continúe con las auditorías de riesgo operativo y legal.'
    ]
];

$recomendacionesPreguntas = [
    'p1'  => 'Forme el Consejo de Familia para institucionalizar la toma de decisiones.',
    'p2'  => 'Incorpore un consejero independiente para aportar objetividad en la junta.',
    'p3'  => 'Aclare formalmente en el organigrama las funciones de accionista vs. directivo.',
    'p4'  => 'Audite estados financieros anualmente con una firma externa.',
    'p5'  => 'Establezca una tabla de montos máximos autorizados para inversiones.',
    'p6'  => 'Inicie la fase de redacción del Protocolo Familiar con consenso de los socios.',
    'p7'  => 'Exija titulación y experiencia laboral previa fuera de la empresa a familiares.',
    'p8'  => 'Realice un estudio de mercado salarial para los cargos ocupados por parientes.',
    'p9'  => 'Cláusule mecanismos de mediación o arbitraje en el acuerdo de socios.',
    'p10' => 'Defina una fórmula fija para la distribución periódica de dividendos.',
    'p11' => 'Redacte formalmente el perfil de reemplazo para el Director General.',
    'p12' => 'Diseñe un plan de carrera interno para preparar a los potenciales sucesores.',
    'p13' => 'Consulte un asesor tributario para proyectar el traspaso accionario generacional.',
    'p14' => 'Redacte el protocolo de actuación ante ausencia imprevista del líder principal.',
    'p15' => 'Fomente talleres de alineación vocacional con los jóvenes de la familia.',
    'p16' => 'Cierre inmediatamente el uso de fondos de la empresa para gastos personales.',
    'p17' => 'Elabore un Plan Estratégico a 3 años fijando metas numéricas (KPIs).',
    'p18' => 'Implemente un comité de presupuesto para revisar desviaciones cada mes.',
    'p19' => 'Fije una métrica máxima de apalancamiento bancario aceptable.',
    'p20' => 'Contrate una auditoría de matriz de riesgos legales y operativos.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Sanitización de entradas corporativas
    $empresa  = filter_var($_POST['nombre_empresa'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $contacto = filter_var($_POST['nombre_contacto'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email    = filter_var($_POST['email_contacto'] ?? '', FILTER_VALIDATE_EMAIL);
    $sector   = filter_var($_POST['sector'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $answers  = $_POST['preguntas'] ?? [];

    if (!$empresa || !$contacto || !$email || empty($answers) || !is_array($answers)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, complete todos los campos obligatorios.']);
        exit;
    }

    // Procesamiento de puntuación y evaluación de negocio
    $evaluacionCategorias = [];
    $preguntasDebiles     = [];
    $puntuacionTotal      = 0;

    $mapaPreguntas = [
        'p1'=>'GOBERNANZA','p2'=>'GOBERNANZA','p3'=>'GOBERNANZA','p4'=>'GOBERNANZA','p5'=>'GOBERNANZA',
        'p6'=>'PROTOCOLO','p7'=>'PROTOCOLO','p8'=>'PROTOCOLO','p9'=>'PROTOCOLO','p10'=>'PROTOCOLO',
        'p11'=>'SUCESIÓN','p12'=>'SUCESIÓN','p13'=>'SUCESIÓN','p14'=>'SUCESIÓN','p15'=>'SUCESIÓN',
        'p16'=>'FINANZAS','p17'=>'FINANZAS','p18'=>'FINANZAS','p19'=>'FINANZAS','p20'=>'FINANZAS'
    ];

    $conteoCat = ['GOBERNANZA' => 0, 'PROTOCOLO' => 0, 'SUCESIÓN' => 0, 'FINANZAS' => 0];

    foreach ($answers as $pId => $val) {
        $score = (int)$val;
        $puntuacionTotal += $score;
        $cat = $mapaPreguntas[$pId] ?? null;

        if ($cat) {
            $conteoCat[$cat] += $score;
        }

        // Evaluar Preguntas con Mayor Debilidad (Puntajes 1 o 2)
        if ($score <= 2) {
            $preguntasDebiles[] = [
                'id'            => $pId,
                'score'         => $score,
                'categoria'     => $cat,
                'recomendacion' => $recomendacionesPreguntas[$pId] ?? 'Atender esta área con prioridad.'
            ];
        }
    }

    // Clasificación semafórica por categoría (Máximo 25 pts por categoría)
    foreach ($conteoCat as $catKey => $puntos) {
        $maxCat = 25;
        $porcentaje = ($puntos / $maxCat) * 100;

        if ($porcentaje < 50) {
            $estado = 'CRÍTICO';
            $color = '#ef4444'; // Rojo
            $bg = 'rgba(239, 68, 68, 0.15)';
            $recom = $reglasCategorias[$catKey]['recom_critica'];
        } elseif ($porcentaje < 80) {
            $estado = 'EN RIESGO';
            $color = '#f59e0b'; // Amarillo / Naranja
            $bg = 'rgba(245, 158, 11, 0.15)';
            $recom = $reglasCategorias[$catKey]['recom_media'];
        } else {
            $estado = 'SÓLIDO';
            $color = '#10b981'; // Verde
            $bg = 'rgba(16, 185, 129, 0.15)';
            $recom = $reglasCategorias[$catKey]['recom_excelente'];
        }

        $evaluacionCategorias[$catKey] = [
            'titulo'        => $reglasCategorias[$catKey]['titulo'],
            'icon'          => $reglasCategorias[$catKey]['icon'],
            'puntuacion'    => $puntos,
            'maximo'        => $maxCat,
            'porcentaje'    => round($porcentaje),
            'estado'        => $estado,
            'color'         => $color,
            'bg'            => $bg,
            'recomendacion' => $recom
        ];
    }

    // Persistencia BD mediante PDO Transaccional (Ajuste según su DB)
    /*
    $pdo = new PDO("mysql:host=localhost;dbname=audit_db;charset=utf8mb4", "user", "pass", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->prepare("INSERT INTO evaluaciones (empresa, contacto, email, total_puntos) VALUES (?, ?, ?, ?)");
    $stmt->execute([$empresa, $contacto, $email, $puntuacionTotal]);
    */

    // Almacenar reporte en Sesión para la vista de resultados
    $_SESSION['diagnostico_resultado'] = [
        'empresa'            => $empresa,
        'contacto'           => $contacto,
        'email'              => $email,
        'puntuacion_total'   => $puntuacionTotal,
        'max_posible'        => 100,
        'eval_categorias'    => $evaluacionCategorias,
        'preguntas_debiles'  => $preguntasDebiles,
        'fecha'              => date('Y-m-d H:i:s')
    ];

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    error_log("Error Diagnóstico: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de procesamiento en el servidor.']);
}