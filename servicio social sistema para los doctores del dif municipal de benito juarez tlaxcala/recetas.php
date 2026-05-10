<?php
session_start();
// Verificar sesión
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php?error=2');
    exit;
}

// Información del doctor
$doctor_nombre = $_SESSION['doctor_nombre'] ?? '';
$doctor_especialidad = $_SESSION['doctor_especialidad'] ?? '';
$doctor_cedula = $_SESSION['doctor_cedula'] ?? '';
$doctor_universidad = $_SESSION['doctor_universidad'] ?? '';

// Calcular iniciales del doctor
$doctor_iniciales = 'DR';
if (!empty($doctor_nombre)) {
    $nombres = explode(' ', trim($doctor_nombre));
    if (count($nombres) >= 2) {
        $inicial1 = substr($nombres[0], 0, 1);
        $inicial2 = substr($nombres[1], 0, 1);
        $doctor_iniciales = strtoupper($inicial1 . $inicial2);
    } elseif (!empty($nombres[0])) {
        $doctor_iniciales = strtoupper(substr($nombres[0], 0, 2));
    }
}

// Función para convertir a mayúsculas de forma segura
function safe_strtoupper($string) {
    if (is_null($string)) {
        return '';
    }
    return strtoupper((string)$string);
}

// Incluir configuración de base de datos
require_once 'config/database.php';

// Crear conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

$accion = $_GET['accion'] ?? 'listar';
$paciente_id = $_GET['paciente_id'] ?? null;
$receta_id = $_GET['id'] ?? null;

// Obtener información del paciente si hay paciente_id
$paciente = null;
if ($paciente_id) {
    $stmt = $conn->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$paciente_id]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Guardar receta con múltiples medicamentos
if ($accion == 'guardar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Guardar receta principal
        $sql_receta = "INSERT INTO recetas (paciente_id, fecha, medico_nombre, peso, talla, ta, temp, fc, fr, glucosa, sat_o2, alergias, indicaciones) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql_receta);
        $stmt->execute([
            $_POST['paciente_id'],
            $_POST['fecha'],
            $_SESSION['doctor_nombre'],
            $_POST['peso'] ?? null,
            $_POST['talla'] ?? null,
            $_POST['ta'] ?? null,
            $_POST['temp'] ?? null,
            $_POST['fc'] ?? null,
            $_POST['fr'] ?? null,
            $_POST['glucosa'] ?? null,
            $_POST['sat_o2'] ?? null,
            $_POST['alergias'] ?? null,
            $_POST['indicaciones'] ?? null
        ]);
        
        $receta_id = $conn->lastInsertId();
        
        // Guardar múltiples medicamentos
        $medicamentos = $_POST['medicamento'] ?? [];
        $dosis = $_POST['dosis'] ?? [];
        $frecuencias = $_POST['frecuencia'] ?? [];
        $duraciones = $_POST['duracion'] ?? [];
        
        $sql_medicamento = "INSERT INTO receta_medicamentos (receta_id, medicamento, dosis, frecuencia, duracion) 
                           VALUES (?, ?, ?, ?, ?)";
        
        $stmt_med = $conn->prepare($sql_medicamento);
        
        for ($i = 0; $i < count($medicamentos); $i++) {
            if (!empty($medicamentos[$i])) {
                $stmt_med->execute([
                    $receta_id,
                    $medicamentos[$i],
                    $dosis[$i] ?? null,
                    $frecuencias[$i] ?? null,
                    $duraciones[$i] ?? null
                ]);
            }
        }
        
        header("Location: recetas.php?paciente_id=" . $_POST['paciente_id'] . "&success=1");
        exit;
        
    } catch (PDOException $e) {
        $error = "Error al guardar la receta: " . $e->getMessage();
    }
}

// Obtener receta específica para imprimir
$receta_imprimir = null;
$medicamentos_imprimir = [];
if ($accion == 'imprimir' && $receta_id) {
    // Obtener receta principal
    $stmt = $conn->prepare("SELECT r.*, p.nombre as paciente_nombre, p.edad as paciente_edad, p.genero as paciente_genero, 
                           p.direccion as paciente_direccion, p.telefono as paciente_telefono
                           FROM recetas r 
                           INNER JOIN pacientes p ON r.paciente_id = p.id 
                           WHERE r.id = ?");
    $stmt->execute([$receta_id]);
    $receta_imprimir = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener medicamentos de la receta
    if ($receta_imprimir) {
        $stmt_med = $conn->prepare("SELECT * FROM receta_medicamentos WHERE receta_id = ? ORDER BY id");
        $stmt_med->execute([$receta_id]);
        $medicamentos_imprimir = $stmt_med->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Listar recetas del paciente
$recetas = [];
if ($paciente_id) {
    $stmt = $conn->prepare("SELECT r.*, 
                           (SELECT COUNT(*) FROM receta_medicamentos rm WHERE rm.receta_id = r.id) as num_medicamentos
                           FROM recetas r 
                           WHERE r.paciente_id = ? 
                           ORDER BY r.fecha DESC");
    $stmt->execute([$paciente_id]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Si la acción es imprimir, mostrar solo la receta
if ($accion == 'imprimir' && $receta_imprimir) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receta Médica - DIF Benito Juárez</title>
        <style>
            /* ====== CONFIGURACIÓN DE IMPRESIÓN OPTIMIZADA ====== */
            @page {
                margin: 0.2cm !important;
                size: A4 portrait;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            body { 
                font-family: 'Arial', sans-serif !important; 
                margin: 0 !important; 
                padding: 0 !important;
                background: white !important;
                font-size: 10pt !important;
                line-height: 1.2 !important;
                color: #000 !important;
                width: 21cm !important;
                height: 29.7cm !important;
            }
            
            .prescription-container {
                width: 100% !important;
                min-height: 29.7cm !important;
                max-height: 29.7cm !important;
                margin: 0 auto !important;
                background: white !important;
                position: relative !important;
                page-break-inside: avoid !important;
                page-break-after: auto !important;
                padding: 0.3cm !important;
                overflow: hidden !important;
            }
            
            /* ====== LOGO DIF - ESPACIO OPTIMIZADO ====== */
            .header-container {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 0.15cm !important;
                padding-bottom: 0.1cm !important;
                border-bottom: 1px solid #000 !important;
                min-height: 1.4cm !important;
                max-height: 1.4cm !important;
            }
            
            .logo-section {
                flex: 0 0 auto;
                text-align: left;
                margin-right: 0.2cm;
            }
            
            .logo-dif {
                height: 1.2cm !important;
                width: auto !important;
                max-width: 3cm !important;
                object-fit: contain;
            }
            
            .header-text {
                flex: 1;
                text-align: center;
                padding: 0 0.3cm;
            }
            
            .municipio {
                font-size: 11pt !important;
                font-weight: bold !important;
                text-transform: uppercase;
                color: #763D93 !important;
                margin-bottom: 0.05cm !important;
                line-height: 1.1 !important;
            }
            
            .admin-periodo {
                font-size: 7pt !important;
                color: #666 !important;
                margin-bottom: 0.05cm !important;
            }
            
            .institucion {
                font-size: 9pt !important;
                font-weight: bold !important;
                color: #D62985 !important;
                text-transform: uppercase;
                margin: 0.05cm 0 !important;
            }
            
            .sistema-medico {
                font-size: 8pt !important;
                font-weight: bold !important;
                color: #2CB7BF !important;
                margin: 0.03cm 0 !important;
            }
            
            .prescription-number {
                flex: 0 0 auto;
                font-size: 7pt !important;
                color: #666 !important;
                background: #f5f5f5;
                padding: 0.1cm 0.2cm;
                border-radius: 0.15cm;
                border: 0.5px solid #ccc;
                text-align: center;
                min-width: 2.5cm;
            }
            
            /* ====== INFORMACIÓN DEL MÉDICO - COMPACTADO ====== */
            .doctor-info {
                text-align: center;
                margin: 0.15cm 0 0.2cm 0 !important;
                padding: 0.15cm;
                border-bottom: 0.5px solid #000 !important;
                font-size: 8.5pt !important;
                line-height: 1.1 !important;
            }
            
            .doctor-name {
                font-weight: bold !important;
                color: #763D93 !important;
                margin-bottom: 0.05cm !important;
            }
            
            .doctor-title {
                color: #D62985 !important;
                margin-bottom: 0.03cm !important;
                font-size: 8pt !important;
            }
            
            .doctor-cedula {
                font-style: italic;
                color: #666 !important;
                font-size: 7pt !important;
            }
            
            /* ====== CONTENEDOR PRINCIPAL - OPTIMIZADO ====== */
            .main-content {
                display: flex;
                justify-content: space-between;
                margin-bottom: 0.3cm !important;
                gap: 0.2cm !important;
                min-height: 3cm !important;
                max-height: 3cm !important;
            }
            
            /* ====== DATOS DEL PACIENTE - COMPACTADO ====== */
            .patient-section {
                flex: 1;
                padding: 0.15cm !important;
                border: 1px solid #763D93 !important;
                border-radius: 0.15cm;
                background: #f9f5ff !important;
            }
            
            .section-title {
                font-size: 8.5pt !important;
                font-weight: bold !important;
                color: #763D93 !important;
                text-align: center;
                margin-bottom: 0.1cm !important;
                padding-bottom: 0.05cm;
                border-bottom: 0.5px solid #D62985 !important;
            }
            
            .patient-data {
                font-size: 8pt !important;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.05cm;
            }
            
            .data-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 0.05cm !important;
                padding-bottom: 0.03cm;
            }
            
            .data-label {
                font-weight: bold !important;
                min-width: 0.8cm !important;
                color: #D62985 !important;
                font-size: 7.5pt !important;
            }
            
            .data-value {
                flex: 1;
                text-align: left;
                color: #333 !important;
                font-size: 8pt !important;
            }
            
            /* ====== SIGNOS VITALES - MÁS COMPACTO ====== */
            .vital-signs-section {
                width: 45% !important;
                padding: 0.15cm !important;
                border: 1px solid #2CB7BF !important;
                border-radius: 0.15cm;
                background: #f0f9fa !important;
            }
            
            .vital-signs-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 0.05cm !important;
            }
            
            .vital-sign-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.05cm 0.1cm;
                background: white;
                border-radius: 0.08cm;
                border: 0.5px solid #ddd;
                font-size: 7.5pt !important;
                min-height: 0.4cm;
            }
            
            .vital-label {
                font-weight: bold !important;
                color: #763D93 !important;
                font-size: 7pt !important;
            }
            
            .vital-value {
                font-weight: bold !important;
                color: #2CB7BF !important;
                min-width: 1cm;
                text-align: right;
                font-size: 7.5pt !important;
            }
            
            /* ====== SECCIÓN DE ALERGIAS - COMPACTADA ====== */
            .allergy-item {
                grid-column: span 2;
                background: #fff8e1 !important;
                border: 0.5px solid #FF9800 !important;
                border-radius: 0.08cm;
                padding: 0.05cm 0.1cm;
                margin-top: 0.03cm;
                font-size: 7.5pt !important;
            }
            
            .allergy-label {
                color: #D32F2F !important;
                font-weight: bold !important;
                font-size: 7pt !important;
            }
            
            .allergy-value {
                color: #D32F2F !important;
                font-weight: bold !important;
                font-style: italic;
                font-size: 7.5pt !important;
            }
            
            /* ====== RECETA MÉDICA - ESPACIO MÁXIMO OPTIMIZADO ====== */
            .prescription-section {
                margin: 0.15cm 0 !important;
                padding: 0.2cm !important;
                border: 1px solid #763D93 !important;
                border-radius: 0.15cm;
                background: white !important;
                min-height: 12cm !important;
                max-height: 12cm !important;
                overflow: hidden;
            }
            
            .prescription-title {
                font-size: 10pt !important;
                font-weight: bold !important;
                text-align: center;
                text-transform: uppercase;
                color: #D62985 !important;
                margin-bottom: 0.15cm !important;
                padding-bottom: 0.05cm;
                border-bottom: 1px solid #2CB7BF !important;
            }
            
            .medicamentos-container {
                max-height: 9.5cm !important;
                min-height: 9.5cm !important;
                overflow-y: auto;
                padding-right: 0.1cm;
                margin-bottom: 0.15cm;
            }
            
            /* Scrollbar más fino */
            .medicamentos-container::-webkit-scrollbar {
                width: 2px;
            }
            
            .medicamentos-container::-webkit-scrollbar-track {
                background: #f1f1f1;
            }
            
            .medicamentos-container::-webkit-scrollbar-thumb {
                background: #763D93;
                border-radius: 1px;
            }
            
            /* ====== MEDICAMENTOS - DISEÑO COMPACTO ====== */
            .medicamento-item {
                margin-bottom: 0.1cm !important;
                padding: 0.1cm !important;
                border-left: 2px solid #8FC744 !important;
                background: linear-gradient(to right, rgba(143,199,68,0.03), white) !important;
                border-radius: 0 0.08cm 0.08cm 0 !important;
                page-break-inside: avoid !important;
                min-height: 0.8cm !important;
            }
            
            .medicamento-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 0.03cm !important;
            }
            
            .medicamento-nombre {
                font-size: 8.5pt !important;
                font-weight: bold !important;
                color: #763D93 !important;
                text-transform: uppercase;
                flex: 1;
            }
            
            .medicamento-numero {
                font-size: 7pt !important;
                color: #2CB7BF !important;
                font-weight: bold !important;
                margin-right: 0.2cm;
                min-width: 0.4cm;
            }
            
            .medicamento-detalles {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 0.1cm !important;
                margin-top: 0.05cm !important;
                padding-left: 0.3cm;
            }
            
            .detalle-item {
                display: flex;
                flex-direction: column;
            }
            
            .detalle-label {
                font-weight: bold !important;
                color: #D62985 !important;
                font-size: 6.5pt !important;
                text-transform: uppercase;
                margin-bottom: 0.02cm;
            }
            
            .detalle-value {
                color: #333 !important;
                font-size: 7.5pt !important;
                line-height: 1.1;
            }
            
            /* ====== INDICACIONES GENERALES - COMPACTADAS ====== */
            .indicaciones-section {
                margin-top: 0.15cm !important;
                padding: 0.1cm !important;
                border: 0.5px solid #8FC744 !important;
                border-radius: 0.1cm;
                background: #f8ffef !important;
                min-height: 0.8cm !important;
                max-height: 0.8cm !important;
                overflow: hidden;
            }
            
            .indicaciones-title {
                font-size: 8pt !important;
                font-weight: bold !important;
                color: #8FC744 !important;
                margin-bottom: 0.03cm !important;
                display: inline;
            }
            
            .indicaciones-content {
                font-size: 7.5pt !important;
                line-height: 1.1 !important;
                color: #333 !important;
                display: inline;
            }
            
            /* ====== FIRMAS Y SELLOS - COMPACTADOS ====== */
            .signature-section {
                margin-top: 0.2cm !important;
                padding-top: 0.15cm !important;
                border-top: 0.5px solid #763D93 !important;
                min-height: 1.8cm !important;
                max-height: 1.8cm !important;
            }
            
            .signature-container {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                height: 1.5cm;
            }
            
            .doctor-signature {
                text-align: center;
                flex: 1;
            }
            
            .signature-line {
                display: inline-block;
                border-top: 0.5px solid #000;
                width: 4cm !important;
                margin-top: 0.2cm !important;
            }
            
            .signature-text {
                font-size: 7.5pt !important;
                margin-top: 0.08cm !important;
                color: #763D93 !important;
                font-weight: bold !important;
                line-height: 1 !important;
            }
            
            .stamp-area {
                text-align: center;
                padding: 0.05cm;
                flex: 0 0 auto;
            }
            
            .stamp {
                width: 1.2cm !important;
                height: 1.2cm !important;
                border: 0.5px solid #D62985 !important;
                border-radius: 50%;
                margin: 0 auto;
                position: relative;
                background: white;
            }
            
            .stamp-text {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 6pt !important;
                font-weight: bold !important;
                color: #D62985 !important;
                text-align: center;
                line-height: 1;
                width: 90%;
            }
            
            /* ====== INFORMACIÓN DE CONTACTO - COMPACTADA ====== */
            .contact-info {
                text-align: center;
                margin-top: 0.15cm !important;
                padding: 0.1cm !important;
                border-top: 0.5px solid #2CB7BF !important;
                font-size: 6.5pt !important;
                color: #333 !important;
                line-height: 1.1 !important;
                min-height: 0.8cm !important;
                background: #f0f9fa !important;
            }
            
            .contact-row {
                margin-bottom: 0.02cm !important;
            }
            
            .contact-highlight {
                font-weight: bold !important;
                color: #763D93 !important;
                font-size: 7pt !important;
                text-transform: uppercase;
                margin-bottom: 0.03cm !important;
                display: block;
            }
            
            /* ====== BOTONES PARA VISTA PREVIA ====== */
            .print-controls {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
                display: flex;
                gap: 10px;
            }
            
            .print-btn {
                padding: 8px 16px;
                background: linear-gradient(135deg, #763D93 0%, #D62985 100%);
                color: white;
                border: none;
                border-radius: 15px;
                cursor: pointer;
                font-weight: bold;
                font-size: 10pt;
                box-shadow: 0 4px 15px rgba(118,61,147,0.4);
                transition: all 0.3s;
            }
            
            .print-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(118,61,147,0.5);
            }
            
            .close-btn {
                padding: 8px 16px;
                background: linear-gradient(135deg, #2CB7BF 0%, #8FC744 100%);
                color: white;
                border: none;
                border-radius: 15px;
                cursor: pointer;
                font-size: 10pt;
                box-shadow: 0 4px 15px rgba(44,183,191,0.4);
                transition: all 0.3s;
            }
            
            .close-btn:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(44,183,191,0.5);
            }
            
            /* ====== ESTILOS PARA IMPRESIÓN ====== */
            @media print {
                body { 
                    margin: 0 !important; 
                    padding: 0 !important; 
                    background: white !important;
                    width: 21cm !important;
                    height: 29.7cm !important;
                }
                
                .prescription-container { 
                    margin: 0 !important; 
                    box-shadow: none !important; 
                    padding: 0.3cm !important;
                    border: none !important;
                }
                
                .no-print { 
                    display: none !important; 
                }
                
                /* Ocultar scrollbar en impresión */
                .medicamentos-container {
                    overflow-y: visible !important;
                    max-height: none !important;
                    padding-right: 0 !important;
                }
                
                /* Ajustes para mejor impresión en blanco y negro */
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                
                /* Colores para impresión */
                .municipio, 
                .institucion, 
                .sistema-medico,
                .doctor-name,
                .doctor-title,
                .section-title,
                .data-label,
                .vital-label,
                .vital-value,
                .medicamento-nombre,
                .detalle-label,
                .indicaciones-title,
                .signature-text,
                .stamp-text,
                .contact-highlight,
                .prescription-title,
                .allergy-label,
                .allergy-value {
                    color: #000 !important;
                }
                
                .allergy-item {
                    background: #f5f5f5 !important;
                    border-color: #000 !important;
                }
                
                .medicamento-item {
                    border-left-color: #666 !important;
                    background: #f9f9f9 !important;
                }
                
                /* Ajustes específicos de impresión */
                @page {
                    margin: 0.2cm !important;
                }
            }
            
            /* ====== MEDIA QUERIES PARA VISTA PREVIA ====== */
            @media screen {
                .prescription-container {
                    box-shadow: 0 0 15px rgba(0,0,0,0.08);
                    margin: 0.5cm auto;
                }
            }
            
            /* ====== ESTILOS PARA CUANDO HAY MUCHOS MEDICAMENTOS ====== */
            .compact-mode .medicamento-detalles {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            
            .compact-mode .medicamento-item {
                min-height: 0.7cm !important;
                padding: 0.08cm !important;
            }
            
            .compact-mode .medicamento-nombre {
                font-size: 8pt !important;
            }
            
            /* ====== ESTILOS PARA DATOS VACÍOS ====== */
            .empty-data {
                color: #888 !important;
                font-style: italic;
                font-size: 7.5pt !important;
            }
        </style>
    </head>
    <body>
        <div class="print-controls no-print">
            <button onclick="window.print()" class="print-btn">🖨️ Imprimir</button>
            <button onclick="window.close()" class="close-btn">✖️ Cerrar</button>
        </div>
        
        <div class="prescription-container">
            <!-- Encabezado con logo -->
            <div class="header-container">
                <div class="logo-section">
                    <?php
                    // Verificar si existe el logo DIF
                    $logo_path = 'img/logoDif.png';
                    $logo_exists = file_exists($logo_path);
                    ?>
                    
                    <?php if($logo_exists): ?>
                        <img src="<?php echo $logo_path; ?>" alt="Logo DIF Benito Juárez" class="logo-dif">
                    <?php else: ?>
                        <div style="width: 2.5cm; height: 1.2cm; background: #f0f0f0; border: 0.5px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #666; font-size: 7pt; text-align: center; border-radius: 0.1cm;">
                            DIF<br>BJ
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="header-text">
                    <div class="municipio">BENITO JUÁREZ</div>
                    <div class="admin-periodo">Administración 2024-2027</div>
                    <div class="institucion">SISTEMA MUNICIPAL DIF</div>
                    <div class="sistema-medico">SERVICIOS MÉDICOS</div>
                </div>
                
                <div class="prescription-number">
                    Receta: <?php echo str_pad($receta_id, 6, '0', STR_PAD_LEFT); ?>
                </div>
            </div>
            
            <!-- Información del Médico -->
            <div class="doctor-info">
    <div class="doctor-name"> <?php echo safe_strtoupper($_SESSION['doctor_nombre'] ?? ''); ?></div>
    <div class="doctor-title"><?php echo safe_strtoupper($_SESSION['doctor_especialidad'] ?? 'MÉDICO CIRUJANO'); ?></div>
    <div class="doctor-cedula">
        <?php if(!empty($_SESSION['doctor_cedula'])): ?>
            CÉDULA: <?php echo $_SESSION['doctor_cedula']; ?> | 
        <?php endif; ?>
        <?php if(!empty($_SESSION['doctor_universidad'])): ?>
            <?php echo strtoupper($_SESSION['doctor_universidad']); ?>
        <?php else: ?>
            UPAEP
        <?php endif; ?>
    </div>
</div>
            
            <!-- Contenido Principal -->
            <div class="main-content">
                <!-- Datos del Paciente -->
                <div class="patient-section">
                    <div class="section-title">DATOS DEL PACIENTE</div>
                    <div class="patient-data">
                        <div class="data-row">
                            <span class="data-label">Nombre:</span>
                            <span class="data-value"><?php echo safe_strtoupper($receta_imprimir['paciente_nombre'] ?? ''); ?></span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Edad:</span>
                            <span class="data-value"><?php echo $receta_imprimir['paciente_edad'] ?? ''; ?> años</span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Género:</span>
                            <span class="data-value"><?php echo safe_strtoupper($receta_imprimir['paciente_genero'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="data-row">
                            <span class="data-label">Fecha:</span>
                            <span class="data-value"><?php echo date('d/m/Y', strtotime($receta_imprimir['fecha'] ?? '')); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Signos Vitales con Alergias -->
                <div class="vital-signs-section">
                    <div class="section-title">SIGNOS VITALES</div>
                    <div class="vital-signs-grid">
                        <div class="vital-sign-item">
                            <span class="vital-label">PESO</span>
                            <span class="vital-value"><?php echo $receta_imprimir['peso'] ? $receta_imprimir['peso'] . ' kg' : '-'; ?></span>
                        </div>
                        <div class="vital-sign-item">
                            <span class="vital-label">TALLA</span>
                            <span class="vital-value"><?php echo $receta_imprimir['talla'] ? $receta_imprimir['talla'] . ' cm' : '-'; ?></span>
                        </div>
                        <div class="vital-sign-item">
                            <span class="vital-label">T/A</span>
                            <span class="vital-value"><?php echo $receta_imprimir['ta'] ?: '-'; ?></span>
                        </div>
                        <div class="vital-sign-item">
                            <span class="vital-label">TEMP.</span>
                            <span class="vital-value"><?php echo $receta_imprimir['temp'] ? $receta_imprimir['temp'] . ' °C' : '-'; ?></span>
                        </div>
                        <div class="vital-sign-item">
                            <span class="vital-label">F.C.</span>
                            <span class="vital-value"><?php echo $receta_imprimir['fc'] ? $receta_imprimir['fc'] . ' lpm' : '-'; ?></span>
                        </div>
                        <div class="vital-sign-item">
                            <span class="vital-label">F.R.</span>
                            <span class="vital-value"><?php echo $receta_imprimir['fr'] ? $receta_imprimir['fr'] . ' rpm' : '-'; ?></span>
                        </div>
                        <div class="vital-sign-item">
                            <span class="vital-label">GLUCOSA</span>
                            <span class="vital-value"><?php echo $receta_imprimir['glucosa'] ? $receta_imprimir['glucosa'] . ' mg/dL' : '-'; ?></span>
                        </div>
                        <div class="vital-sign-item">
                            <span class="vital-label">SAT O₂</span>
                            <span class="vital-value"><?php echo $receta_imprimir['sat_o2'] ? $receta_imprimir['sat_o2'] . ' %' : '-'; ?></span>
                        </div>
                        <!-- Campo de Alergias -->
                        <div class="vital-sign-item allergy-item">
                            <span class="vital-label allergy-label">ALERGIAS:</span>
                            <span class="vital-value allergy-value">
                                <?php echo $receta_imprimir['alergias'] ? htmlspecialchars($receta_imprimir['alergias']) : 'NO REGISTRADAS'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Receta Médica (Espacio optimizado para más medicamentos) -->
            <div class="prescription-section">
                <div class="prescription-title">PRESCRIPCIÓN MÉDICA</div>
                
                <div class="medicamentos-container" id="medicamentosContainer">
                    <?php if(!empty($medicamentos_imprimir)): ?>
                        <?php foreach($medicamentos_imprimir as $index => $medicamento): ?>
                            <div class="medicamento-item">
                                <div class="medicamento-header">
                                    <span class="medicamento-numero"><?php echo ($index + 1); ?>.</span>
                                    <div class="medicamento-nombre"><?php echo safe_strtoupper($medicamento['medicamento'] ?? ''); ?></div>
                                </div>
                                
                                <?php if(!empty($medicamento['dosis']) || !empty($medicamento['frecuencia']) || !empty($medicamento['duracion'])): ?>
                                <div class="medicamento-detalles">
                                    <?php if(!empty($medicamento['dosis'])): ?>
                                        <div class="detalle-item">
                                            <span class="detalle-label">Dosis</span>
                                            <span class="detalle-value"><?php echo $medicamento['dosis']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($medicamento['frecuencia'])): ?>
                                        <div class="detalle-item">
                                            <span class="detalle-label">Frecuencia</span>
                                            <span class="detalle-value"><?php echo $medicamento['frecuencia']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($medicamento['duracion'])): ?>
                                        <div class="detalle-item">
                                            <span class="detalle-label">Duración</span>
                                            <span class="detalle-value"><?php echo $medicamento['duracion']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="medicamento-item">
                            <div class="medicamento-header">
                                <span class="medicamento-numero">1.</span>
                                <div class="medicamento-nombre">NO SE PRESCRIBEN MEDICAMENTOS</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if(!empty($receta_imprimir['indicaciones'])): ?>
                    <div class="indicaciones-section">
                        <span class="indicaciones-title">INDICACIONES:</span>
                        <span class="indicaciones-content"><?php echo $receta_imprimir['indicaciones']; ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Firmas y Sellos -->
            <div class="signature-section">
    <div class="signature-container">
        <div class="doctor-signature">
            <div class="signature-line"></div>
            <div class="signature-text">
                <?php echo safe_strtoupper($_SESSION['doctor_nombre'] ?? ''); ?><br>
                <em><?php echo htmlspecialchars($_SESSION['doctor_especialidad'] ?? 'Médico Tratante'); ?></em>
                <?php if(!empty($_SESSION['doctor_cedula'])): ?>
                    <br><small>Cédula: <?php echo htmlspecialchars($_SESSION['doctor_cedula']); ?></small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stamp-area">
            <div class="stamp">
                <div class="stamp-text">
                    <?php if(!empty($_SESSION['doctor_universidad'])): ?>
                        <?php echo strtoupper(substr($_SESSION['doctor_universidad'], 0, 12)); ?><br>
                    <?php endif; ?>
                    SELLO<br>
                    Y<br>
                    FIRMA
                </div>
            </div>
        </div>
    </div>
</div>
            
            <!-- INFORMACIÓN DE CONTACTO -->
            <div class="contact-info">
                <div class="contact-row">
                    <span class="contact-highlight">SISTEMA MUNICIPAL DIF CONSULTORIO 01</span>
                </div>
                <div class="contact-row">AV. EJÉRCITO NACIONAL S/N • BENITO JUÁREZ TLAXCALA</div>
                <div class="contact-row">TEL. 748 766 2331 • "Por una comunidad saludable y unida"</div>
            </div>
        </div>
        
        <script>
            // Imprimir automáticamente al cargar la página
            window.onload = function() {
                // Activar modo compacto si hay muchos medicamentos
                const medicamentos = document.querySelectorAll('.medicamento-item');
                if (medicamentos.length > 8) {
                    document.getElementById('medicamentosContainer').classList.add('compact-mode');
                }
                
                // Esperar un momento para que todo cargue
                setTimeout(function() {
                    window.print();
                }, 800);
            };
            
            // Después de imprimir, ofrecer cerrar
            window.onafterprint = function() {
                if (confirm('¿Desea cerrar esta ventana?')) {
                    window.close();
                }
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetas Médicas - DIF Benito Juárez</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ====== VARIABLES CSS DIF ====== */
        :root {
            /* Colores oficiales del logo DIF */
            --dif-magenta: #D62985;    /* Rosa Fucsia */
            --dif-morado: #763D93;     /* Violeta */
            --dif-turquesa: #2CB7BF;   /* Turquesa/Aqua */
            --dif-verde: #8FC744;      /* Verde Limón */
            
            /* Colores derivados para mejor contraste */
            --dif-magenta-light: #F8E0EF;
            --dif-morado-light: #F0E6F6;
            --dif-turquesa-light: #E6F7F8;
            --dif-verde-light: #F0F8E6;
            
            --dif-magenta-dark: #B02070;
            --dif-morado-dark: #5D3074;
            --dif-turquesa-dark: #24959C;
            --dif-verde-dark: #72A538;
            
            --text-color: #333;
            --text-light: #718096;
            --bg-color: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0,0,0,0.15);
            --border-radius: 10px;
        }

        /* ====== ESTILOS GENERALES DIF ====== */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        body { 
            background: linear-gradient(135deg, var(--dif-turquesa-light) 0%, var(--dif-verde-light) 100%); 
            color: var(--text-color); 
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
        }

        /* ====== NAVBAR DIF ====== */
        .navbar { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            box-shadow: var(--shadow); 
            padding: 1rem 3rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }

        .logo { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        
        .logo-image {
            height: 55px;
            width: auto;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .logo-text { 
            display: flex; 
            flex-direction: column; 
        }
        
        .logo-text h1 { 
            color: var(--white); 
            font-size: 28px; 
            font-weight: 700; 
            letter-spacing: 0.5px; 
        }
        
        .logo-text span { 
            color: rgba(255,255,255,0.85); 
            font-size: 14px; 
        }

        .nav-menu { 
            display: flex; 
            list-style: none; 
            gap: 5px; 
        }
        
        .nav-item { 
            position: relative; 
        }
        
        .nav-link { 
            text-decoration: none; 
            color: rgba(255,255,255,0.9); 
            font-weight: 500; 
            padding: 16px 22px; 
            transition: all 0.3s; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            border-radius: var(--border-radius); 
            font-size: 16px; 
        }
        
        .nav-link:hover, 
        .nav-link:focus { 
            background: rgba(255,255,255,0.15); 
            color: var(--white); 
            transform: translateY(-2px); 
            outline: none;
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: var(--white);
            border-left: 4px solid var(--dif-turquesa);
        }
        
        .nav-link i { 
            font-size: 20px; 
            width: 24px; 
            text-align: center; 
        }

        .user-profile { 
            position: relative; 
            display: flex; 
            align-items: center; 
            cursor: pointer; 
            gap: 15px; 
            padding: 10px 16px; 
            border-radius: 12px; 
            transition: all 0.3s; 
        }
        
        .user-profile:hover { 
            background: rgba(255,255,255,0.15); 
        }
        
        .user-avatar { 
            width: 50px; 
            height: 50px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, var(--dif-turquesa), var(--dif-verde)); 
            color: var(--white); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold; 
            font-size: 18px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
        }
        
        .user-info { 
            display: flex; 
            flex-direction: column; 
        }
        
        .user-info .name { 
            color: var(--white); 
            font-weight: 600; 
            font-size: 16px; 
        }
        
        .user-info .role { 
            color: rgba(255,255,255,0.85); 
            font-size: 13px; 
        }
        
        .user-dropdown { 
            position: absolute; 
            top: 100%; 
            right: 0; 
            background: var(--white); 
            border-radius: 12px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.2); 
            display: none; 
            flex-direction: column; 
            width: 200px; 
            overflow: hidden; 
            z-index: 1001;
            border: 2px solid var(--dif-magenta-light);
        }
        
        .user-dropdown a { 
            padding: 12px 18px; 
            text-decoration: none; 
            color: var(--text-color); 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            transition: all 0.3s; 
        }
        
        .user-dropdown a:hover,
        .user-dropdown a:focus { 
            background: var(--dif-turquesa-light); 
            color: var(--dif-morado); 
            outline: none;
        }

        /* ====== CONTENIDO PRINCIPAL ====== */
        .main-content { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
        }
        
        .container { 
            max-width: 1200px; 
            margin: 2rem auto; 
            background: var(--white); 
            padding: 2rem; 
            border-radius: 15px; 
            box-shadow: var(--shadow);
            border: 2px solid var(--dif-magenta-light);
            flex: 1;
        }
        
        h2, h3, h4 { 
            color: var(--dif-morado); 
            margin-bottom: 1rem; 
        }
        
        /* ====== BOTONES DIF ====== */
        .btn { 
            padding: 12px 24px; 
            border-radius: var(--border-radius); 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            font-weight: 600; 
            transition: all 0.3s; 
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: white; 
            box-shadow: 0 4px 15px rgba(118,61,147,0.3); 
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(118,61,147,0.4); 
            background: linear-gradient(135deg, var(--dif-morado-dark) 0%, var(--dif-magenta-dark) 100%);
        }
        
        .btn-outline { 
            background: transparent; 
            border: 2px solid var(--dif-verde); 
            color: var(--dif-verde-dark); 
        }
        
        .btn-outline:hover { 
            background: var(--dif-verde-light); 
            transform: translateY(-2px);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            border: none; 
        }
        
        .btn-success { 
            background: linear-gradient(135deg, var(--dif-verde) 0%, var(--dif-verde-dark) 100%); 
            color: white; 
            border: none; 
        }
        
        /* ====== FORMULARIOS DIF ====== */
        .form-section { 
            background: linear-gradient(135deg, var(--dif-turquesa-light) 0%, var(--dif-verde-light) 100%); 
            padding: 1.5rem; 
            margin: 1rem 0; 
            border-radius: var(--border-radius); 
            border-left: 4px solid var(--dif-magenta);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .form-row { 
            display: flex; 
            gap: 1rem; 
            margin-bottom: 1rem; 
        }
        
        .form-group { 
            flex: 1; 
        }
        
        form input, form select, form textarea { 
            padding: 12px 15px; 
            width: 100%; 
            margin: 8px 0; 
            border-radius: 8px; 
            border: 1px solid #ccc; 
            box-sizing: border-box; 
            font-size: 14px;
            transition: all 0.3s;
        }
        
        form input:focus, form select:focus, form textarea:focus { 
            outline: none;
            border-color: var(--dif-turquesa);
            box-shadow: 0 0 0 3px rgba(44,183,191,0.1);
        }
        
        form label { 
            font-weight: bold; 
            display: block; 
            margin-bottom: 5px; 
            color: var(--dif-morado);
        }
        
        textarea { 
            resize: vertical; 
            min-height: 100px; 
        }
        
        /* ====== MEDICAMENTOS DIF ====== */
        .medicamento-fila {
            background: var(--white);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            border: 2px solid var(--dif-turquesa-light);
            position: relative;
            transition: all 0.3s;
        }
        
        .medicamento-fila:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            border-color: var(--dif-turquesa);
        }
        
        .medicamento-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .medicamento-numero {
            background: linear-gradient(135deg, var(--dif-magenta) 0%, var(--dif-morado) 100%);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(118,61,147,0.3);
        }
        
        .btn-eliminar-med {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-eliminar-med:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220,53,69,0.3);
        }
        
        .btn-agregar-med {
            background: linear-gradient(135deg, var(--dif-verde) 0%, var(--dif-verde-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 12px 24px;
            cursor: pointer;
            font-weight: bold;
            margin: 10px 0;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-agregar-med:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(143,199,68,0.3);
        }
        
        /* ====== TABLAS DIF ====== */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            border-radius: var(--border-radius); 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            margin-top: 1rem;
            border: 2px solid var(--dif-magenta-light);
        }
        
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #eee;
        }
        
        th { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: #fff; 
            font-weight: 600; 
            font-size: 14px;
        }
        
        tr:hover { 
            background: var(--dif-turquesa-light); 
        }
        
        /* ====== MENSAJES DIF ====== */
        .error { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
            color: #721c24; 
            padding: 15px; 
            border-radius: var(--border-radius); 
            margin: 10px 0; 
            border-left: 4px solid #dc3545;
        }
        
        .success { 
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
            color: #155724; 
            padding: 15px; 
            border-radius: var(--border-radius); 
            margin: 10px 0; 
            border-left: 4px solid var(--dif-verde);
        }
        
        /* ====== VISTA PREVIA DIF ====== */
        .preview-container {
            width: 100%;
            background: white;
            padding: 20px;
            border: 2px solid var(--dif-morado);
            border-radius: var(--border-radius);
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, rgba(118,61,147,0.02) 0%, rgba(214,41,133,0.02) 100%);
        }
        
        .preview-header {
            text-align: center;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--dif-morado);
        }
        
        .preview-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--dif-magenta);
            margin-bottom: 5px;
        }
        
        .preview-subtitle {
            font-size: 14px;
            color: var(--dif-morado);
        }
        
        .preview-content {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .preview-patient {
            flex: 1;
            padding: 15px;
            border: 2px solid var(--dif-morado);
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(118,61,147,0.05) 0%, rgba(118,61,147,0.1) 100%);
        }
        
        .preview-vitals {
            width: 40%;
            padding: 15px;
            border: 2px solid var(--dif-turquesa);
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(44,183,191,0.05) 0%, rgba(44,183,191,0.1) 100%);
        }
        
        .preview-medicamentos {
            margin-top: 20px;
        }
        
        .preview-medicamento {
            padding: 12px 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--dif-verde);
            background: linear-gradient(to right, var(--dif-verde-light), white);
            border-radius: 0 8px 8px 0;
        }
        
        /* ====== BADGES DIF ====== */
        .badge {
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        /* ====== FOOTER DIF ====== */
        .footer { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: var(--white); 
            text-align: center; 
            padding: 1.5rem; 
            margin-top: auto; 
        }
        
        /* ====== RESPONSIVE ====== */
        @media print {
            .no-print, .btn, .navbar, .footer { display: none !important; }
            .container { 
                box-shadow: none; 
                margin: 0; 
                padding: 0; 
                border: none;
            }
            body { 
                background: white; 
                padding: 0;
            }
        }
        
        @media (max-width: 1024px) {
            .navbar {
                padding: 1rem;
                flex-wrap: wrap;
            }
            
            .nav-menu {
                order: 3;
                width: 100%;
                justify-content: center;
                margin-top: 1rem;
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 768px) {
            .logo-text h1 {
                font-size: 22px;
            }
            
            .nav-link {
                padding: 12px 16px;
                font-size: 14px;
            }
            
            .container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .preview-content {
                flex-direction: column;
            }
            
            .preview-vitals {
                width: 100%;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .form-row {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .logo-text span {
                display: none;
            }
            
            .nav-link span {
                display: none;
            }
            
            .nav-link {
                padding: 10px;
            }
            
            .user-info .name {
                display: none;
            }

            .medicamento-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .btn-eliminar-med {
                align-self: flex-end;
            }
        }
        
        /* ====== UTILIDADES ====== */
        .small-text {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-2 {
            margin-top: 2rem;
        }
        
        .mb-2 {
            margin-bottom: 2rem;
        }
        
        /* ====== PALETA DE COLORES VISIBLE ====== */
        .dif-colors-sample {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .color-sample {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        /* ====== ANIMACIONES ====== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .container {
            animation: fadeIn 0.8s ease-out;
        }
        
        /* ====== BADGE PARA MOSTRAR COLORES DIF ====== */
        .dif-colors-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 15px;
            font-size: 0.7rem;
            color: var(--text-light);
        }
        
        .color-sample-mini {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- ====== NAVBAR ====== -->
    <nav class="navbar" role="navigation" aria-label="Navegación principal">
        <div class="logo">
            <?php
            // Verificar si existe el logo
            $logo_path = 'img/logoDif.png';
            $logo_exists = file_exists($logo_path);
            ?>
            
            <?php if($logo_exists): ?>
                <!-- Mostrar logo real si existe -->
                <img src="<?php echo $logo_path; ?>" alt="Logo DIF Benito Juárez" class="logo-image">
            <?php else: ?>
                <!-- Mostrar logo placeholder si no existe la imagen -->
                <div class="logo-icon" aria-hidden="true" style="width: 55px; height: 55px; background: var(--white); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    <i class="fas fa-hands-helping" style="font-size: 28px; background: linear-gradient(135deg, var(--dif-morado), var(--dif-magenta)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"></i>
                </div>
            <?php endif; ?>
            
            <div class="logo-text">
                <h1>DIF Benito Juárez</h1>
                <span>Portal Médico 
                    <span class="dif-colors-badge">
                        <span class="color-sample-mini" style="background: var(--dif-magenta);"></span>
                        <span class="color-sample-mini" style="background: var(--dif-morado);"></span>
                        <span class="color-sample-mini" style="background: var(--dif-turquesa);"></span>
                        <span class="color-sample-mini" style="background: var(--dif-verde);"></span>
                    </span>
                </span>
            </div>
        </div>
            
        <ul class="nav-menu" role="menubar">
            <li class="nav-item" role="none">
                <a href="dashboard.php" class="nav-link" role="menuitem">
                    <i class="fas fa-home" aria-hidden="true"></i> 
                    <span>Inicio</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="pacientes.php" class="nav-link" role="menuitem">
                    <i class="fas fa-user-injured" aria-hidden="true"></i> 
                    <span>Pacientes</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="recetas.php" class="nav-link active" role="menuitem" aria-current="page">
                    <i class="fas fa-file-prescription" aria-hidden="true"></i> 
                    <span>Recetas</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="informes.php" class="nav-link" role="menuitem">
                    <i class="fas fa-file-medical-alt" aria-hidden="true"></i> 
                    <span>Informes</span>
                </a>
            </li>
        </ul>
        
        <div class="user-profile" id="userProfile" tabindex="0" aria-haspopup="true" aria-expanded="false">
            <div class="user-avatar" aria-label="Iniciales del doctor">
                <?php echo htmlspecialchars($doctor_iniciales); ?>
            </div>
            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($doctor_nombre); ?></div>
                <div class="role"><?php echo htmlspecialchars($doctor_especialidad); ?></div>
            </div>
            
            <div class="user-dropdown" id="userDropdown" role="menu">
                <a href="perfil.php" role="menuitem">
                    <i class="fas fa-id-badge" aria-hidden="true"></i> 
                    Mi perfil
                </a>
                <a href="configuracion.php" role="menuitem">
                    <i class="fas fa-cog" aria-hidden="true"></i> 
                    Configuración
                </a>
                <a href="logout.php" role="menuitem">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i> 
                    Cerrar sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- ====== CONTENIDO PRINCIPAL ====== -->
    <main class="main-content" role="main">
        <div class="container">
            <?php if(isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="success">✓ Receta guardada correctamente</div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>
                    <i class="fas fa-file-prescription" style="color: var(--dif-magenta); margin-right: 10px;"></i>
                    <?php if($paciente): ?>
                        Recetas Médicas - <?php echo htmlspecialchars($paciente['nombre'] ?? ''); ?>
                    <?php else: ?>
                        Recetas Médicas
                    <?php endif; ?>
                </h2>
                <div style="display: flex; gap: 10px;">
                    <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-home"></i> Inicio</a>
                    <a href="pacientes.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Pacientes</a>
                </div>
            </div>

            <?php if($accion == 'nueva' && $paciente): ?>
                <!-- Paleta de colores DIF -->
                <div class="dif-colors-sample no-print">
                    <div class="color-sample" style="background: var(--dif-magenta);" title="Magenta DIF"></div>
                    <div class="color-sample" style="background: var(--dif-morado);" title="Morado DIF"></div>
                    <div class="color-sample" style="background: var(--dif-turquesa);" title="Turquesa DIF"></div>
                    <div class="color-sample" style="background: var(--dif-verde);" title="Verde DIF"></div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-plus-circle" style="color: var(--dif-magenta);"></i> Nueva Receta Médica</h3>
                    <form method="post" action="recetas.php?accion=guardar" id="recetaForm">
                        <input type="hidden" name="paciente_id" value="<?php echo htmlspecialchars($paciente['id'] ?? ''); ?>">
                        <input type="hidden" name="fecha" value="<?php echo date('Y-m-d'); ?>">
                        
                        <!-- Sección de Datos del Paciente -->
                        <div class="form-row">
                            <div class="form-group">
                                <h4 style="color: var(--dif-morado);"><i class="fas fa-user"></i> Datos del Paciente</h4>
                                <div style="background: var(--dif-morado-light); padding: 15px; border-radius: 8px; margin-top: 10px; border: 1px solid var(--dif-morado);">
                                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($paciente['nombre'] ?? ''); ?></p>
                                    <p><strong>Edad:</strong> <?php echo htmlspecialchars($paciente['edad'] ?? ''); ?> años</p>
                                    <p><strong>Género:</strong> <?php echo htmlspecialchars($paciente['genero'] ?? ''); ?></p>
                                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y'); ?></p>
                                </div>
                            </div>
                            <div class="form-group">
                                <h4 style="color: var(--dif-turquesa);"><i class="fas fa-heartbeat"></i> Signos Vitales</h4>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                                    <div>
                                        <label><i class="fas fa-weight"></i> Peso (kg):</label>
                                        <input type="text" name="peso" id="peso" placeholder="Ej: 70" autocomplete="off">
                                    </div>
                                    <div>
                                        <label><i class="fas fa-ruler-vertical"></i> Talla (cm):</label>
                                        <input type="text" name="talla" id="talla" placeholder="Ej: 170" autocomplete="off">
                                    </div>
                                    <div>
                                        <label><i class="fas fa-tachometer-alt"></i> T/A:</label>
                                        <input type="text" name="ta" id="ta" placeholder="Ej: 120/80" autocomplete="off">
                                    </div>
                                    <div>
                                        <label><i class="fas fa-thermometer-half"></i> Temp (°C):</label>
                                        <input type="text" name="temp" id="temp" placeholder="Ej: 36.5" autocomplete="off">
                                    </div>
                                    <div>
                                        <label><i class="fas fa-heart"></i> F.C. (lpm):</label>
                                        <input type="text" name="fc" id="fc" placeholder="Ej: 80" autocomplete="off">
                                    </div>
                                    <div>
                                        <label><i class="fas fa-lungs"></i> F.R. (rpm):</label>
                                        <input type="text" name="fr" id="fr" placeholder="Ej: 16" autocomplete="off">
                                    </div>
                                    <div>
                                        <label><i class="fas fa-vial"></i> Glucosa (mg/dL):</label>
                                        <input type="text" name="glucosa" id="glucosa" placeholder="Ej: 90" autocomplete="off">
                                    </div>
                                    <div>
                                        <label><i class="fas fa-wind"></i> Sat O₂ (%):</label>
                                        <input type="text" name="sat_o2" id="sat_o2" placeholder="Ej: 98" autocomplete="off">
                                    </div>
                                    <!-- Campo de Alergias -->
                                    <div style="grid-column: span 2;">
                                        <label><i class="fas fa-allergies"></i> Alergias:</label>
                                        <input type="text" name="alergias" id="alergias" 
                                               placeholder="Ej: Penicilina, Ibuprofeno, etc. (Dejar en blanco si no hay)" 
                                               autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sección de Medicamentos -->
                        <h4 style="color: var(--dif-verde); border-bottom: 2px solid var(--dif-verde-light); padding-bottom: 5px;">
                            <i class="fas fa-pills"></i> Medicamentos
                        </h4>
                        <div id="medicamentos-container">
                            <!-- Primera fila de medicamento -->
                            <div class="medicamento-fila" data-index="0">
                                <div class="medicamento-header">
                                    <div class="medicamento-numero">1</div>
                                    <button type="button" class="btn-eliminar-med" onclick="eliminarMedicamento(this)">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="fas fa-capsules"></i> Medicamento:</label>
                                        <input type="text" name="medicamento[]" class="medicamento-input" required 
                                               placeholder="Nombre del medicamento" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-prescription-bottle-alt"></i> Dosis:</label>
                                        <input type="text" name="dosis[]" class="dosis-input" 
                                               placeholder="Ej: 500mg cada 12 horas" autocomplete="off">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label><i class="fas fa-clock"></i> Frecuencia:</label>
                                        <input type="text" name="frecuencia[]" class="frecuencia-input" 
                                               placeholder="Ej: Cada 8 horas" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-calendar-alt"></i> Duración:</label>
                                        <input type="text" name="duracion[]" class="duracion-input" 
                                               placeholder="Ej: 7 días" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn-agregar-med" onclick="agregarMedicamento()">
                            <i class="fas fa-plus-circle"></i> Agregar otro medicamento
                        </button>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label><i class="fas fa-sticky-note"></i> Indicaciones generales:</label>
                            <textarea name="indicaciones" id="indicaciones" 
                                      placeholder="Instrucciones adicionales para el paciente..." 
                                      style="min-height: 120px;"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Receta
                            </button>
                            <button type="button" class="btn btn-outline" onclick="window.history.back()">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Vista previa mejorada -->
                <div class="preview-container no-print">
                    <div class="preview-header">
                        <div class="preview-title">VISTA PREVIA DE RECETA MÉDICA</div>
                        <div class="preview-subtitle">DIF Municipal Benito Juárez</div>
                    </div>
                    
                    <div class="preview-content">
                        <div class="preview-patient">
                            <h5 style="color: var(--dif-morado); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-user"></i> DATOS DEL PACIENTE
                            </h5>
                            <p><strong>Nombre:</strong> <span id="prev-nombre"><?php echo htmlspecialchars($paciente['nombre'] ?? ''); ?></span></p>
                            <p><strong>Edad:</strong> <span id="prev-edad"><?php echo htmlspecialchars($paciente['edad'] ?? ''); ?> años</span></p>
                            <p><strong>Fecha:</strong> <span id="prev-fecha"><?php echo date('d/m/Y'); ?></span></p>
                        </div>
                        
                        <div class="preview-vitals">
                            <h5 style="color: var(--dif-turquesa); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-heartbeat"></i> SIGNOS VITALES
                            </h5>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 5px;">
                                <div><strong>Peso:</strong> <span id="prev-peso">N/A</span></div>
                                <div><strong>Talla:</strong> <span id="prev-talla">N/A</span></div>
                                <div><strong>T/A:</strong> <span id="prev-ta">N/A</span></div>
                                <div><strong>Temp:</strong> <span id="prev-temp">N/A</span></div>
                                <div><strong>F.C.:</strong> <span id="prev-fc">N/A</span></div>
                                <div><strong>F.R.:</strong> <span id="prev-fr">N/A</span></div>
                                <div><strong>Glucosa:</strong> <span id="prev-glucosa">N/A</span></div>
                                <div><strong>Sat O₂:</strong> <span id="prev-sat_o2">N/A</span></div>
                                <!-- Vista previa de Alergias -->
                                <div style="grid-column: span 2; margin-top: 5px; padding-top: 5px; border-top: 1px dashed var(--dif-morado-light);">
                                    <strong>Alergias:</strong> <span id="prev-alergias">No registradas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="preview-medicamentos">
                        <h5 style="color: var(--dif-verde); margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-pills"></i> MEDICAMENTOS PRESCRITOS
                        </h5>
                        <div id="vista-previa-receta">
                            <p><em style="color: var(--text-light);">Complete el formulario para ver la vista previa de los medicamentos...</em></p>
                        </div>
                        
                        <div id="vista-previa-indicaciones" style="margin-top: 15px; padding: 15px; background: var(--dif-verde-light); border-radius: 8px; display: none;">
                            <strong style="color: var(--dif-verde-dark);">Indicaciones generales:</strong> 
                            <span id="prev-indicaciones" style="display: block; margin-top: 5px;"></span>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <small class="small-text">
                            <i class="fas fa-info-circle" style="color: var(--dif-turquesa);"></i>
                            Esta es una vista previa. Para ver el formato completo de impresión, guarde primero la receta.
                        </small>
                    </div>
                </div>

                <script>
                    let medicamentoCounter = 1;
                    
                    function agregarMedicamento() {
                        medicamentoCounter++;
                        const container = document.getElementById('medicamentos-container');
                        const nuevoMedicamento = document.createElement('div');
                        nuevoMedicamento.className = 'medicamento-fila';
                        nuevoMedicamento.setAttribute('data-index', medicamentoCounter - 1);
                        
                        nuevoMedicamento.innerHTML = `
                            <div class="medicamento-header">
                                <div class="medicamento-numero">${medicamentoCounter}</div>
                                <button type="button" class="btn-eliminar-med" onclick="eliminarMedicamento(this)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-capsules"></i> Medicamento:</label>
                                    <input type="text" name="medicamento[]" class="medicamento-input" required 
                                           placeholder="Nombre del medicamento" autocomplete="off">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-prescription-bottle-alt"></i> Dosis:</label>
                                    <input type="text" name="dosis[]" class="dosis-input" 
                                           placeholder="Ej: 500mg cada 12 horas" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-clock"></i> Frecuencia:</label>
                                    <input type="text" name="frecuencia[]" class="frecuencia-input" 
                                           placeholder="Ej: Cada 8 horas" autocomplete="off">
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-calendar-alt"></i> Duración:</label>
                                    <input type="text" name="duracion[]" class="duracion-input" 
                                           placeholder="Ej: 7 días" autocomplete="off">
                                </div>
                            </div>
                        `;
                        
                        container.appendChild(nuevoMedicamento);
                        
                        // Agregar listeners a los nuevos inputs
                        nuevoMedicamento.querySelectorAll('input').forEach(input => {
                            input.addEventListener('input', actualizarVistaPrevia);
                        });
                        
                        actualizarVistaPrevia();
                        actualizarNumerosMedicamentos();
                    }
                    
                    function eliminarMedicamento(button) {
                        const fila = button.closest('.medicamento-fila');
                        const index = parseInt(fila.getAttribute('data-index'));
                        
                        // No permitir eliminar el primer medicamento
                        if (index === 0) {
                            alert('Debe haber al menos un medicamento en la receta.');
                            return;
                        }
                        
                        fila.remove();
                        medicamentoCounter--;
                        actualizarNumerosMedicamentos();
                        actualizarVistaPrevia();
                    }
                    
                    function actualizarNumerosMedicamentos() {
                        const filas = document.querySelectorAll('.medicamento-fila');
                        filas.forEach((fila, index) => {
                            fila.setAttribute('data-index', index);
                            fila.querySelector('.medicamento-numero').textContent = index + 1;
                        });
                    }
                    
                    function actualizarVistaPrevia() {
                        const form = document.getElementById('recetaForm');
                        
                        // Actualizar signos vitales
                        document.getElementById('prev-peso').textContent = form.peso.value ? form.peso.value + ' kg' : 'N/A';
                        document.getElementById('prev-talla').textContent = form.talla.value ? form.talla.value + ' cm' : 'N/A';
                        document.getElementById('prev-ta').textContent = form.ta.value || 'N/A';
                        document.getElementById('prev-temp').textContent = form.temp.value ? form.temp.value + ' °C' : 'N/A';
                        document.getElementById('prev-fc').textContent = form.fc.value ? form.fc.value + ' lpm' : 'N/A';
                        document.getElementById('prev-fr').textContent = form.fr.value ? form.fr.value + ' rpm' : 'N/A';
                        document.getElementById('prev-glucosa').textContent = form.glucosa.value ? form.glucosa.value + ' mg/dL' : 'N/A';
                        document.getElementById('prev-sat_o2').textContent = form.sat_o2.value ? form.sat_o2.value + ' %' : 'N/A';
                        
                        // Actualizar alergias
                        const alergiasValue = form.alergias.value.trim();
                        document.getElementById('prev-alergias').textContent = alergiasValue || 'No registradas';
                        document.getElementById('prev-alergias').style.color = alergiasValue ? '#D32F2F' : 'var(--text-light)';
                        document.getElementById('prev-alergias').style.fontWeight = alergiasValue ? 'bold' : 'normal';
                        
                        // Actualizar medicamentos
                        const medicamentos = form.querySelectorAll('.medicamento-input');
                        const dosis = form.querySelectorAll('.dosis-input');
                        const frecuencias = form.querySelectorAll('.frecuencia-input');
                        const duraciones = form.querySelectorAll('.duracion-input');
                        
                        let html = '';
                        let hasMedicamentos = false;
                        
                        medicamentos.forEach((med, index) => {
                            if (med.value.trim()) {
                                hasMedicamentos = true;
                                html += `<div class="preview-medicamento">
                                    <div style="font-weight: bold; color: var(--dif-morado); margin-bottom: 5px;">
                                        <i class="fas fa-prescription"></i> ${index + 1}. ${med.value}
                                    </div>`;
                                
                                if (dosis[index] && dosis[index].value.trim()) {
                                    html += `<div><strong>Dosis:</strong> ${dosis[index].value}</div>`;
                                }
                                
                                if (frecuencias[index] && frecuencias[index].value.trim()) {
                                    html += `<div><strong>Frecuencia:</strong> ${frecuencias[index].value}</div>`;
                                }
                                
                                if (duraciones[index] && duraciones[index].value.trim()) {
                                    html += `<div><strong>Duración:</strong> ${duraciones[index].value}</div>`;
                                }
                                
                                html += `</div>`;
                            }
                        });
                        
                        const vistaPrevia = document.getElementById('vista-previa-receta');
                        if (hasMedicamentos) {
                            vistaPrevia.innerHTML = html;
                        } else {
                            vistaPrevia.innerHTML = '<p><em style="color: var(--text-light);">Complete el formulario para ver la vista previa de los medicamentos...</em></p>';
                        }
                        
                        // Actualizar indicaciones
                        const indicaciones = form.indicaciones.value.trim();
                        const indicacionesDiv = document.getElementById('vista-previa-indicaciones');
                        const indicacionesSpan = document.getElementById('prev-indicaciones');
                        
                        if (indicaciones) {
                            indicacionesSpan.textContent = indicaciones;
                            indicacionesDiv.style.display = 'block';
                        } else {
                            indicacionesDiv.style.display = 'none';
                        }
                    }
                    
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.getElementById('recetaForm');
                        
                        // Escuchar cambios en todos los campos
                        form.querySelectorAll('input, textarea').forEach(field => {
                            field.addEventListener('input', actualizarVistaPrevia);
                        });
                        
                        // Inicializar vista previa
                        actualizarVistaPrevia();
                        
                        // Validación básica del formulario
                        form.addEventListener('submit', function(e) {
                            const medicamentos = form.querySelectorAll('.medicamento-input');
                            let hasAtLeastOne = false;
                            
                            medicamentos.forEach(med => {
                                if (med.value.trim()) {
                                    hasAtLeastOne = true;
                                }
                            });
                            
                            if (!hasAtLeastOne) {
                                e.preventDefault();
                                alert('Debe ingresar al menos un medicamento en la receta.');
                                return false;
                            }
                            
                            // Mostrar mensaje de confirmación
                            if (!confirm('¿Está seguro de guardar esta receta médica?')) {
                                e.preventDefault();
                                return false;
                            }
                            
                            return true;
                        });
                    });
                </script>

            <?php elseif($paciente_id && count($recetas) > 0): ?>
                <h3><i class="fas fa-history" style="color: var(--dif-morado);"></i> Historial de Recetas</h3>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar"></i> Fecha</th>
                            <th><i class="fas fa-pills"></i> Medicamentos</th>
                            <th><i class="fas fa-heartbeat"></i> Signos Vitales</th>
                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recetas as $receta): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($receta['fecha'])); ?></strong><br>
                                <small class="small-text"><?php echo date('H:i', strtotime($receta['fecha'])); ?></small>
                            </td>
                            <td>
                                <span class="badge">
                                    <i class="fas fa-pills"></i> <?php echo htmlspecialchars($receta['num_medicamentos'] ?? 0); ?> medicamento(s)
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <?php if($receta['peso']): ?>
                                        <span style="color: var(--dif-morado);"><i class="fas fa-weight"></i> P: <?php echo htmlspecialchars($receta['peso']); ?>kg</span>
                                    <?php endif; ?>
                                    <?php if($receta['ta']): ?>
                                        <span style="color: var(--dif-turquesa);"> | <i class="fas fa-tachometer-alt"></i> TA: <?php echo htmlspecialchars($receta['ta']); ?></span>
                                    <?php endif; ?>
                                    <?php if($receta['temp']): ?>
                                        <span style="color: var(--dif-magenta);"> | <i class="fas fa-thermometer-half"></i> T: <?php echo htmlspecialchars($receta['temp']); ?>°C</span>
                                    <?php endif; ?>
                                    <?php if($receta['alergias']): ?>
                                        <br><span style="color: #D32F2F; font-weight: bold;"><i class="fas fa-allergies"></i> Alergias: <?php echo htmlspecialchars($receta['alergias']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <button onclick="imprimirReceta(<?php echo $receta['id']; ?>)" class="btn btn-primary" style="padding: 8px 15px; font-size: 13px;">
                                    <i class="fas fa-print"></i> Imprimir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="text-center mt-2">
                    <a href="recetas.php?accion=nueva&paciente_id=<?php echo htmlspecialchars($paciente_id); ?>" class="btn btn-success">
                        <i class="fas fa-file-prescription"></i> Nueva Receta
                    </a>
                </div>

            <?php elseif($paciente_id): ?>
                <div class="text-center" style="padding: 3rem;">
                    <div style="font-size: 4rem; color: var(--dif-morado); margin-bottom: 1rem;">
                        <i class="fas fa-file-prescription"></i>
                    </div>
                    <h3 style="color: var(--dif-morado);">No hay recetas registradas</h3>
                    <p style="color: var(--text-light); margin-bottom: 2rem;">Este paciente no tiene recetas médicas registradas.</p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="recetas.php?accion=nueva&paciente_id=<?php echo htmlspecialchars($paciente_id); ?>" class="btn btn-primary">
                            <i class="fas fa-file-prescription"></i> Crear Primera Receta
                        </a>
                        <a href="pacientes.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Volver a Pacientes
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 3rem;">
                    <div style="font-size: 4rem; color: var(--dif-turquesa); margin-bottom: 1rem;">
                        <i class="fas fa-file-prescription"></i>
                    </div>
                    <h3 style="color: var(--dif-morado);">Seleccione un paciente</h3>
                    <p style="color: var(--text-light); margin-bottom: 2rem;">Para gestionar recetas médicas, primero seleccione un paciente.</p>
                    <a href="pacientes.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> Ver Lista de Pacientes
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ====== FOOTER ====== -->
    <footer class="footer" role="contentinfo">
        <p>© <?php echo date('Y'); ?> DIF municipal Benito Juárez Tlaxcala</p>
    </footer>

    <script>
    function imprimirReceta(recetaId) {
        // Abrir ventana para imprimir receta específica
        window.open('recetas.php?accion=imprimir&id=' + recetaId, '_blank');
    }
    
    // Toggle menú perfil de usuario
    document.getElementById('userProfile').addEventListener('click', function () {
        const dropdown = document.getElementById('userDropdown');
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        
        dropdown.style.display = isExpanded ? 'none' : 'flex';
        this.setAttribute('aria-expanded', !isExpanded);
    });
    
    // Manejo de teclado para el menú desplegable
    document.getElementById('userProfile').addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
    
    // Cerrar menú si se hace clic fuera
    document.addEventListener('click', function (e) {
        const userProfile = document.getElementById('userProfile');
        const userDropdown = document.getElementById('userDropdown');
        
        if (!userProfile.contains(e.target)) {
            userDropdown.style.display = 'none';
            userProfile.setAttribute('aria-expanded', 'false');
        }
    });
    
    // Cerrar menú con tecla Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const userProfile = document.getElementById('userProfile');
            const userDropdown = document.getElementById('userDropdown');
            
            userDropdown.style.display = 'none';
            userProfile.setAttribute('aria-expanded', 'false');
        }
    });
    </script>
</body>
</html>