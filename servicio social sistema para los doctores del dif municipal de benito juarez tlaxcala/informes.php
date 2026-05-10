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
$doctor_universidad = $_SESSION['doctor_universidad'] ?? '';
$doctor_cedula = $_SESSION['doctor_cedula'] ?? '';
$doctor_email = $_SESSION['doctor_email'] ?? '';

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

// Función helper para HTML seguro
function safe_html($string) {
    return htmlspecialchars((string)($string ?? ''));
}

// Función para formatear fecha
function safe_date($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

// Incluir configuración de base de datos
require_once 'config/database.php';

// Crear conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

$accion = $_GET['accion'] ?? 'listar';

// Obtener estadísticas para el dashboard de informes
$estadisticas = [];
try {
    // Total de pacientes
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pacientes");
    $estadisticas['total_pacientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total de consultas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM consultas");
    $estadisticas['total_consultas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total de recetas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM recetas");
    $estadisticas['total_recetas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Consultas por mes (últimos 6 meses)
    $stmt = $conn->query("
        SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, COUNT(*) as total 
        FROM consultas 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha, '%Y-%m') 
        ORDER BY mes ASC
    ");
    $estadisticas['consultas_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pacientes por mes (últimos 6 meses)
    $stmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total 
        FROM pacientes 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
        ORDER BY mes ASC
    ");
    $estadisticas['pacientes_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recetas por mes (últimos 6 meses)
    $stmt = $conn->query("
        SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, COUNT(*) as total 
        FROM recetas 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha, '%Y-%m') 
        ORDER BY mes ASC
    ");
    $estadisticas['recetas_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Distribución por género
    $stmt = $conn->query("
        SELECT genero, COUNT(*) as total 
        FROM pacientes 
        WHERE genero IS NOT NULL AND genero != ''
        GROUP BY genero
    ");
    $estadisticas['genero_distribucion'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_estadisticas = "Error al obtener estadísticas: " . $e->getMessage();
}

// Generar informe específico
if ($accion == 'generar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_informe = $_POST['tipo_informe'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    
    try {
        switch($tipo_informe) {
            case 'consultas_general':
                $sql = "SELECT c.*, p.nombre as paciente_nombre 
                        FROM consultas c 
                        LEFT JOIN pacientes p ON c.paciente_id = p.id 
                        WHERE c.fecha BETWEEN ? AND ? 
                        ORDER BY c.fecha DESC, c.hora DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$fecha_inicio, $fecha_fin]);
                $datos_informe = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $titulo_informe = "Informe de Consultas Generales";
                break;
                
            case 'pacientes_registrados':
                $sql = "SELECT nombre, edad, genero, curp, telefono, direccion, created_at 
                        FROM pacientes 
                        WHERE created_at BETWEEN ? AND ? 
                        ORDER BY created_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59']);
                $datos_informe = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $titulo_informe = "Informe de Pacientes Registrados";
                break;
                
            case 'recetas_emitidas':
                // Primero obtenemos las recetas
                $sql = "SELECT r.*, p.nombre as paciente_nombre, p.edad as paciente_edad,
                               p.genero as paciente_genero
                        FROM recetas r 
                        LEFT JOIN pacientes p ON r.paciente_id = p.id 
                        WHERE r.fecha BETWEEN ? AND ? 
                        ORDER BY r.fecha DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$fecha_inicio, $fecha_fin]);
                $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Luego para cada receta obtenemos sus medicamentos
                $datos_informe = [];
                foreach($recetas as $receta) {
                    $sql_medicamentos = "SELECT medicamento, dosis, frecuencia, duracion 
                                        FROM receta_medicamentos 
                                        WHERE receta_id = ? 
                                        ORDER BY id";
                    $stmt_med = $conn->prepare($sql_medicamentos);
                    $stmt_med->execute([$receta['id']]);
                    $medicamentos = $stmt_med->fetchAll(PDO::FETCH_ASSOC);
                    
                    $receta['medicamentos'] = $medicamentos;
                    $datos_informe[] = $receta;
                }
                $titulo_informe = "Informe de Recetas Emitidas";
                break;
                
            case 'estadisticas_generales':
                // Estadísticas generales del período
                $sql_pacientes = "SELECT COUNT(*) as total FROM pacientes WHERE created_at BETWEEN ? AND ?";
                $sql_consultas = "SELECT COUNT(*) as total FROM consultas WHERE fecha BETWEEN ? AND ?";
                $sql_recetas = "SELECT COUNT(*) as total FROM recetas WHERE fecha BETWEEN ? AND ?";
                
                $stmt = $conn->prepare($sql_pacientes);
                $stmt->execute([$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59']);
                $total_pacientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                
                $stmt = $conn->prepare($sql_consultas);
                $stmt->execute([$fecha_inicio, $fecha_fin]);
                $total_consultas = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                
                $stmt = $conn->prepare($sql_recetas);
                $stmt->execute([$fecha_inicio, $fecha_fin]);
                $total_recetas = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                
                $datos_informe = [
                    'total_pacientes' => $total_pacientes,
                    'total_consultas' => $total_consultas,
                    'total_recetas' => $total_recetas,
                    'periodo' => safe_date($fecha_inicio) . ' - ' . safe_date($fecha_fin)
                ];
                $titulo_informe = "Estadísticas Generales";
                break;
        }
        
    } catch (PDOException $e) {
        $error = "Error al generar el informe: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes y Reportes - DIF Benito Juárez</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        h2, h3 { 
            color: var(--dif-morado); 
            margin-bottom: 1.5rem; 
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
            font-size: 14px;
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
            border: 2px solid var(--dif-turquesa); 
            color: var(--dif-turquesa-dark); 
        }
        
        .btn-outline:hover { 
            background: var(--dif-turquesa-light); 
            transform: translateY(-2px);
        }
        
        .btn-success { 
            background: linear-gradient(135deg, var(--dif-verde) 0%, var(--dif-verde-dark) 100%); 
            color: white; 
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
            font-family: inherit;
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
            min-height: 120px; 
        }
        
        /* ====== TARJETAS DE ESTADÍSTICAS DIF ====== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--white) 0%, var(--dif-turquesa-light) 100%);
            padding: 1.2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            border-top: 4px solid var(--dif-magenta);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dif-morado);
            margin: 0.4rem 0;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .stat-icon {
            font-size: 2rem;
            color: var(--dif-turquesa);
            margin-bottom: 0.4rem;
        }
        
        /* ====== GRÁFICOS DIF - MÁS PEQUEÑOS ====== */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.2rem;
            margin: 2rem 0;
        }
        
        .chart-container {
            background: var(--white);
            padding: 1.2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 2px solid var(--dif-magenta-light);
            min-height: 180px;
        }
        
        .chart-title {
            text-align: center;
            margin-bottom: 0.8rem;
            color: var(--dif-morado);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .chart-wrapper {
            height: 150px;
            position: relative;
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
            padding: 10px 12px; 
            text-align: left; 
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }
        
        th { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: #fff; 
            font-weight: 600; 
            font-size: 0.85rem;
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
        
        /* ====== CAJAS DE INFORMACIÓN DIF ====== */
        .info-box {
            background: linear-gradient(135deg, var(--dif-turquesa-light) 0%, var(--dif-verde-light) 100%);
            border-left: 4px solid var(--dif-morado);
            padding: 1.2rem;
            margin: 1.5rem 0;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-box h4 {
            color: var(--dif-morado);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }
        
        .info-box ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .info-box li {
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        
        /* ====== ACCIONES DE REPORTES DIF ====== */
        .report-actions {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }
        
        /* ====== ESTILOS ESPECÍFICOS PARA RECETAS ====== */
        .receta-info {
            background: linear-gradient(135deg, var(--dif-magenta-light) 0%, var(--dif-morado-light) 100%);
            border-left: 4px solid var(--dif-verde);
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: var(--border-radius);
        }
        
        .receta-medicamento {
            background: linear-gradient(to right, var(--dif-verde-light), var(--white));
            border-left: 3px solid var(--dif-turquesa);
            padding: 0.8rem;
            margin: 0.3rem 0;
            border-radius: 0 8px 8px 0;
            font-size: 0.9rem;
        }
        
        /* ====== FOOTER DIF ====== */
        .footer { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: var(--white); 
            text-align: center; 
            padding: 1.5rem; 
            margin-top: auto; 
        }
        
        /* ====== ESTILOS PARA IMPRESIÓN ====== */
        @media print {
            /* 1. Ocultar elementos que NO deben imprimirse */
            .no-print,
            .report-actions,
            .navbar,
            .sidebar,
            .footer,
            .user-profile,
            .nav-menu,
            .btn,
            .form-section > h3,
            .form-section > div:first-child,
            .stats-grid:not(.printable-stats),
            .charts-grid,
            .info-box:not(.printable-info),
            .dif-colors-sample,
            .print-controls,
            .user-dropdown,
            .logo,
            .user-avatar,
            .user-info,
            .nav-link,
            .logo-text span,
            .dif-colors-badge {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* 2. Resetear el body para impresión */
            body {
                background: white !important;
                color: #333 !important;
                font-size: 12pt !important;
                line-height: 1.4 !important;
                padding: 0 !important;
                margin: 0 !important;
                visibility: visible !important;
                font-family: 'Arial', sans-serif !important;
            }
            
            /* 3. Contenedor principal de impresión */
            .printable-content {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                background: white !important;
                display: block !important;
                visibility: visible !important;
            }
            
            /* 4. Estilos específicos del encabezado de impresión CON COLORES DIF */
            .print-header {
                display: flex !important;
                align-items: center !important;
                margin-bottom: 20px !important;
                padding-bottom: 15px !important;
                border-bottom: 3px solid #763D93 !important;
                page-break-after: avoid !important;
                visibility: visible !important;
            }
            
            .print-logo-section {
                flex: 0 0 auto !important;
                margin-right: 15px !important;
                padding: 5px !important;
            }
            
            .print-logo {
                max-height: 60px !important;
                max-width: 80px !important;
                object-fit: contain !important;
            }
            
            .print-logo-placeholder {
                border: 2px solid #763D93 !important;
                padding: 8px !important;
                text-align: center !important;
                min-width: 80px !important;
                background: #F0E6F6 !important;
                border-radius: 6px !important;
                font-size: 10px !important;
                font-weight: bold !important;
                color: #763D93 !important;
            }
            
            .print-header-text {
                flex: 1 !important;
            }
            
            .print-header-text h1 {
                color: #763D93 !important;
                font-size: 16pt !important;
                margin: 0 0 5px 0 !important;
                font-weight: bold !important;
                border-bottom: 1px solid #D62985 !important;
                padding-bottom: 3px !important;
            }
            
            .print-header-text h2 {
                color: #D62985 !important;
                font-size: 14pt !important;
                margin: 0 0 10px 0 !important;
                font-weight: bold !important;
            }
            
            .print-header-info {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 4px 15px !important;
                font-size: 9pt !important;
                background: #E6F7F8 !important;
                padding: 8px !important;
                border-radius: 5px !important;
                border-left: 4px solid #2CB7BF !important;
            }
            
            .print-header-info p {
                margin: 2px 0 !important;
                color: #333 !important;
            }
            
            .print-header-info strong {
                color: #763D93 !important;
            }
            
            /* 5. Estilos para tablas de datos CON COLORES DIF */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 15px 0 20px 0 !important;
                page-break-inside: auto !important;
                border: 2px solid #763D93 !important;
                font-size: 10pt !important;
                table-layout: fixed !important;
            }
            
            thead {
                display: table-header-group !important;
            }
            
            th {
                background: linear-gradient(135deg, #763D93 0%, #D62985 100%) !important;
                color: white !important;
                font-weight: bold !important;
                border: 1px solid #763D93 !important;
                padding: 8px !important;
                text-align: left !important;
                page-break-inside: avoid !important;
                font-size: 10pt !important;
            }
            
            td {
                border: 1px solid #ddd !important;
                padding: 6px !important;
                vertical-align: top !important;
                page-break-inside: avoid !important;
                font-size: 9pt !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                word-wrap: break-word !important;
            }
            
            /* Filas alternadas con colores DIF */
            tr:nth-child(even) {
                background-color: #E6F7F8 !important; /* Turquesa claro */
            }
            
            tr:nth-child(odd) {
                background-color: #FFF !important;
            }
            
            /* 6. Estilos MEJORADOS para medicamentos en recetas */
            .medicamentos-container {
                display: flex !important;
                flex-direction: column !important;
                gap: 4px !important;
            }
            
            .receta-detalle {
                background: #F8E0EF !important; /* Magenta claro */
                border: 1px solid #D62985 !important;
                margin: 0 !important;
                padding: 6px !important;
                font-size: 8.5pt !important;
                page-break-inside: avoid !important;
                border-left: 3px solid #8FC744 !important; /* Verde */
                border-radius: 0 4px 4px 0 !important;
                break-inside: avoid !important;
            }
            
            .medicamento-nombre {
                font-weight: bold !important;
                color: #763D93 !important;
                margin-bottom: 2px !important;
                font-size: 9pt !important;
            }
            
            .medicamento-detalle {
                font-size: 8pt !important;
                color: #444 !important;
                margin-left: 10px !important;
            }
            
            /* Mejorar visualización de múltiples medicamentos */
            .receta-row td {
                padding: 4px !important;
            }
            
            /* 7. Clases para colores DIF en texto */
            .dif-magenta {
                color: #D62985 !important;
            }
            
            .dif-morado {
                color: #763D93 !important;
            }
            
            .dif-turquesa {
                color: #2CB7BF !important;
            }
            
            .dif-verde {
                color: #8FC744 !important;
            }
            
            /* 8. Pie de página con colores DIF */
            .print-footer {
                display: block !important;
                text-align: center !important;
                margin-top: 2rem !important;
                padding-top: 1rem !important;
                border-top: 2px solid #2CB7BF !important;
                font-size: 8pt !important;
                page-break-before: avoid !important;
                color: #763D93 !important;
            }
            
            /* 9. Márgenes de página */
            @page {
                margin: 1.5cm !important;
                size: auto !important;
            }
            
            @page :first {
                margin-top: 2cm !important;
            }
            
            /* 10. Numeración de páginas con color DIF */
            @page {
                @bottom-center {
                    content: "Página " counter(page) " de " counter(pages);
                    font-size: 8pt;
                    font-family: Arial, sans-serif;
                    color: #2CB7BF;
                }
            }
            
            /* 11. Estilos para CURP y otros campos especiales */
            .curp-field {
                font-family: 'Courier New', monospace !important;
                font-size: 8.5pt !important;
                font-weight: bold !important;
                letter-spacing: 0.5px !important;
            }
            
            .no-curp {
                color: #999 !important;
                font-style: italic !important;
                font-size: 8.5pt !important;
            }
            
            /* 12. Ajustar para múltiples medicamentos - evita cortes */
            .receta-detalle, 
            .medicamento-nombre,
            .medicamento-detalle {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* 13. Tablas más compactas para recetas */
            table tr.receta-row {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            table tr.receta-row td:nth-child(5) { /* Columna de medicamentos */
                min-width: 120px !important;
                max-width: 180px !important;
            }
            
            /* 14. Ocultar cualquier botón restante */
            button, .btn, input[type="button"], input[type="submit"] {
                display: none !important;
            }
            
            /* 15. Ajustar imágenes para impresión */
            img {
                max-width: 100% !important;
                height: auto !important;
                page-break-inside: avoid !important;
            }
            
            /* 16. Estilos para enlaces en impresión */
            a {
                color: #000 !important;
                text-decoration: none !important;
            }
            
            /* 17. Evitar cortes de página dentro de elementos importantes */
            h1, h2, h3, h4 {
                page-break-after: avoid !important;
            }
            
            /* 18. Limitar tamaño de celdas con texto largo */
            td {
                max-width: 150px !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: normal !important;
            }
            
            /* Específico para direcciones */
            td:nth-child(6) { /* Columna de dirección */
                max-width: 120px !important;
                font-size: 7.5pt !important;
            }
            
            /* 19. Mostrar estadísticas generales en impresión */
            .printable-stats {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 15px !important;
                margin: 20px 0 !important;
                visibility: visible !important;
            }
            
            .printable-stat {
                display: block !important;
                visibility: visible !important;
                background: #E6F7F8 !important;
                border: 1px solid #2CB7BF !important;
                padding: 15px !important;
                text-align: center !important;
                border-radius: 5px !important;
            }
            
            .printable-stat .stat-number {
                font-size: 24pt !important;
                color: #763D93 !important;
                font-weight: bold !important;
                margin: 5px 0 !important;
            }
            
            .printable-stat .stat-label {
                font-size: 10pt !important;
                color: #333 !important;
            }
            
            .printable-info {
                display: block !important;
                background: #F0F8E6 !important;
                border: 1px solid #8FC744 !important;
                padding: 15px !important;
                margin: 20px 0 !important;
                border-radius: 5px !important;
                visibility: visible !important;
            }
            
            /* 20. Asegurar que todo el contenido dentro de printable-content sea visible */
            .printable-content * {
                visibility: visible !important;
            }
        }
        
        /* ====== RESPONSIVE ====== */
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
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .btn {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            /* Ajustes para tablas en móviles */
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .chart-wrapper {
                height: 140px;
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
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .report-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .report-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .chart-wrapper {
                height: 130px;
            }
        }
        
        /* ====== UTILIDADES ====== */
        .text-center {
            text-align: center;
        }
        
        .mt-2 {
            margin-top: 2rem;
        }
        
        .mb-2 {
            margin-bottom: 2rem;
        }
        
        .small-text {
            font-size: 12px;
            color: var(--text-light);
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
        
        /* ====== ESTILOS ESPECÍFICOS PARA CURP ====== */
        .curp-field {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .no-curp {
            color: #999;
            font-style: italic;
            font-size: 0.9em;
        }
        
        /* ====== PARA TRUNCAR TEXTO LARGO ====== */
        .truncated-address {
            max-width: 200px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
        }
        
        @media print {
            .truncated-address {
                max-width: 120px !important;
                font-size: 7.5pt !important;
            }
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
                <a href="recetas.php" class="nav-link" role="menuitem">
                    <i class="fas fa-file-prescription" aria-hidden="true"></i> 
                    <span>Recetas</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="informes.php" class="nav-link active" role="menuitem" aria-current="page">
                    <i class="fas fa-file-medical-alt" aria-hidden="true"></i> 
                    <span>Informes</span>
                </a>
            </li>
        </ul>
        
        <div class="user-profile" id="userProfile" tabindex="0" aria-haspopup="true" aria-expanded="false">
            <div class="user-avatar" aria-label="Iniciales del doctor">
                <?php echo safe_html($doctor_iniciales); ?>
            </div>
            <div class="user-info">
                <div class="name"><?php echo safe_html($doctor_nombre); ?></div>
                <div class="role"><?php echo safe_html($doctor_especialidad); ?></div>
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
                <div class="error"><?php echo safe_html($error); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="success">✓ Informe generado y guardado correctamente</div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;" class="no-print">
                <h2><i class="fas fa-chart-bar" style="color: var(--dif-magenta); margin-right: 10px;"></i> Informes y Reportes</h2>
                <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver al Inicio</a>
            </div>

            <?php if($accion == 'listar'): ?>
                <!-- Estadísticas Generales -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="stat-number"><?php echo $estadisticas['total_pacientes']; ?></div>
                        <div class="stat-label">Pacientes Registrados</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <div class="stat-number"><?php echo $estadisticas['total_consultas']; ?></div>
                        <div class="stat-label">Consultas Realizadas</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-prescription"></i>
                        </div>
                        <div class="stat-number"><?php echo $estadisticas['total_recetas']; ?></div>
                        <div class="stat-label">Recetas Emitidas</div>
                    </div>
                </div>

                <!-- Gráficos de Actividad -->
                <div class="charts-grid">
                    <!-- Gráfico de Consultas -->
                    <?php if(!empty($estadisticas['consultas_mes'])): ?>
                    <div class="chart-container">
                        <div class="chart-title"><i class="fas fa-stethoscope"></i> Consultas por Mes</div>
                        <div class="chart-wrapper">
                            <canvas id="consultasChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Gráfico de Pacientes -->
                    <?php if(!empty($estadisticas['pacientes_mes'])): ?>
                    <div class="chart-container">
                        <div class="chart-title"><i class="fas fa-users"></i> Pacientes Registrados por Mes</div>
                        <div class="chart-wrapper">
                            <canvas id="pacientesChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Gráfico de Recetas -->
                    <?php if(!empty($estadisticas['recetas_mes'])): ?>
                    <div class="chart-container">
                        <div class="chart-title"><i class="fas fa-prescription-bottle-alt"></i> Recetas Emitidas por Mes</div>
                        <div class="chart-wrapper">
                            <canvas id="recetasChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Distribución por Género -->
                    <?php if(!empty($estadisticas['genero_distribucion'])): ?>
                    <div class="chart-container">
                        <div class="chart-title"><i class="fas fa-venus-mars"></i> Distribución por Género</div>
                        <div class="chart-wrapper">
                            <canvas id="generoChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Generador de Informes -->
                <div class="form-section">
                    <h3><i class="fas fa-plus-circle" style="color: var(--dif-magenta);"></i> Generar Nuevo Informe</h3>
                    <form method="post" action="informes.php?accion=generar">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-file-alt"></i> Tipo de Informe:</label>
                                <select name="tipo_informe" required>
                                    <option value="">Seleccionar tipo de informe</option>
                                    <option value="consultas_general">Consultas Generales</option>
                                    <option value="pacientes_registrados">Pacientes Registrados</option>
                                    <option value="recetas_emitidas">Recetas Emitidas</option>
                                    <option value="estadisticas_generales">Estadísticas Generales</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Fecha de inicio:</label>
                                <input type="date" name="fecha_inicio" required value="<?php echo date('Y-m-01'); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> Fecha de fin:</label>
                                <input type="date" name="fecha_fin" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Generar Informe
                        </button>
                    </form>
                </div>

                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Tipos de Informes Disponibles</h4>
                    <ul>
                        <li><strong>Consultas Generales:</strong> Listado detallado de todas las consultas en el período seleccionado</li>
                        <li><strong>Pacientes Registrados:</strong> Lista de pacientes registrados en el período seleccionado (incluye CURP)</li>
                        <li><strong>Recetas Emitidas:</strong> Reporte de todas las recetas médicas emitidas</li>
                        <li><strong>Estadísticas Generales:</strong> Resumen numérico de actividad médica</li>
                    </ul>
                </div>

                <!-- Scripts para los gráficos -->
                <script>
                // Colores DIF para gráficos
                const difColors = {
                    magenta: 'rgba(214, 41, 133, 0.8)',
                    morado: 'rgba(118, 61, 147, 0.8)',
                    turquesa: 'rgba(44, 183, 191, 0.8)',
                    verde: 'rgba(143, 199, 68, 0.8)',
                    magentaSolid: 'rgba(214, 41, 133, 1)',
                    moradoSolid: 'rgba(118, 61, 147, 1)',
                    turquesaSolid: 'rgba(44, 183, 191, 1)',
                    verdeSolid: 'rgba(143, 199, 68, 1)',
                    magentaLight: 'rgba(214, 41, 133, 0.3)',
                    turquesaLight: 'rgba(44, 183, 191, 0.3)',
                    verdeLight: 'rgba(143, 199, 68, 0.3)'
                };

                // Configuración común para gráficos pequeños
                const smallChartOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 10
                                }
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                },
                                maxRotation: 45
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0,0,0,0.7)',
                            titleFont: {
                                size: 11
                            },
                            bodyFont: {
                                size: 11
                            }
                        }
                    }
                };

                // Función para formatear meses
                function formatearMes(fechaStr) {
                    const [year, month] = fechaStr.split('-');
                    const fecha = new Date(year, month - 1);
                    const mes = fecha.toLocaleDateString('es-ES', { month: 'short' });
                    const año = fecha.getFullYear().toString().substr(-2);
                    return `${mes} '${año}`;
                }

                // Gráfico de Consultas
                <?php if(!empty($estadisticas['consultas_mes'])): ?>
                const consultasCtx = document.getElementById('consultasChart').getContext('2d');
                const consultasData = <?php echo json_encode($estadisticas['consultas_mes']); ?>;
                
                const consultasMeses = consultasData.map(item => formatearMes(item.mes));
                const consultasTotals = consultasData.map(item => parseInt(item.total));
                
                new Chart(consultasCtx, {
                    type: 'bar',
                    data: {
                        labels: consultasMeses,
                        datasets: [{
                            label: 'Consultas',
                            data: consultasTotals,
                            backgroundColor: difColors.morado,
                            borderColor: difColors.moradoSolid,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        ...smallChartOptions,
                        scales: {
                            ...smallChartOptions.scales,
                            y: {
                                ...smallChartOptions.scales.y,
                                suggestedMax: Math.max(...consultasTotals) + 2
                            }
                        }
                    }
                });
                <?php endif; ?>

                // Gráfico de Pacientes
                <?php if(!empty($estadisticas['pacientes_mes'])): ?>
                const pacientesCtx = document.getElementById('pacientesChart').getContext('2d');
                const pacientesData = <?php echo json_encode($estadisticas['pacientes_mes']); ?>;
                
                const pacientesMeses = pacientesData.map(item => formatearMes(item.mes));
                const pacientesTotals = pacientesData.map(item => parseInt(item.total));
                
                new Chart(pacientesCtx, {
                    type: 'line',
                    data: {
                        labels: pacientesMeses,
                        datasets: [{
                            label: 'Pacientes',
                            data: pacientesTotals,
                            backgroundColor: difColors.turquesaLight,
                            borderColor: difColors.turquesaSolid,
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        ...smallChartOptions,
                        scales: {
                            ...smallChartOptions.scales,
                            y: {
                                ...smallChartOptions.scales.y,
                                suggestedMax: Math.max(...pacientesTotals) + 1
                            }
                        }
                    }
                });
                <?php endif; ?>

                // Gráfico de Recetas
                <?php if(!empty($estadisticas['recetas_mes'])): ?>
                const recetasCtx = document.getElementById('recetasChart').getContext('2d');
                const recetasData = <?php echo json_encode($estadisticas['recetas_mes']); ?>;
                
                const recetasMeses = recetasData.map(item => formatearMes(item.mes));
                const recetasTotals = recetasData.map(item => parseInt(item.total));
                
                new Chart(recetasCtx, {
                    type: 'bar',
                    data: {
                        labels: recetasMeses,
                        datasets: [{
                            label: 'Recetas',
                            data: recetasTotals,
                            backgroundColor: difColors.verde,
                            borderColor: difColors.verdeSolid,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        ...smallChartOptions,
                        scales: {
                            ...smallChartOptions.scales,
                            y: {
                                ...smallChartOptions.scales.y,
                                suggestedMax: Math.max(...recetasTotals) + 1
                            }
                        }
                    }
                });
                <?php endif; ?>

                // Gráfico de Género
                <?php if(!empty($estadisticas['genero_distribucion'])): ?>
                const generoCtx = document.getElementById('generoChart').getContext('2d');
                const generoData = <?php echo json_encode($estadisticas['genero_distribucion']); ?>;
                
                const generoLabels = generoData.map(item => item.genero);
                const generoTotals = generoData.map(item => parseInt(item.total));
                
                new Chart(generoCtx, {
                    type: 'doughnut',
                    data: {
                        labels: generoLabels,
                        datasets: [{
                            data: generoTotals,
                            backgroundColor: [difColors.magenta, difColors.turquesa, difColors.morado, difColors.verde],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                });
                <?php endif; ?>
                </script>

            <?php elseif($accion == 'generar' && isset($datos_informe)): ?>
                <!-- Contenido imprimible -->
                <div class="printable-content">
                    <!-- Encabezado para impresión con colores DIF -->
                    <div class="print-header">
                        <div class="print-logo-section">
                            <?php
                            $logo_path = 'img/logoDif.png';
                            $logo_exists = file_exists($logo_path);
                            ?>
                            
                            <?php if($logo_exists): ?>
                                <img src="<?php echo $logo_path; ?>" 
                                     alt="Logo DIF Benito Juárez" 
                                     class="print-logo"
                                     style="width: 70px; height: auto; object-fit: contain;">
                            <?php else: ?>
                                <div class="print-logo-placeholder" 
                                     style="border: 2px solid #763D93; 
                                            padding: 8px; 
                                            text-align: center;
                                            background: #F0E6F6;
                                            border-radius: 8px;
                                            min-width: 100px;">
                                    <strong style="color: #763D93;">DIF BENITO JUÁREZ</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="print-header-text">
                            <h1 style="color: #763D93; margin-bottom: 5px; font-size: 18px; border-bottom: 2px solid #D62985; padding-bottom: 3px;">
                                SISTEMA MUNICIPAL DIF
                            </h1>
                            <h2 style="color: #D62985; margin-bottom: 8px; font-size: 16px;">
                                <?php echo safe_html($titulo_informe); ?>
                            </h2>
                            <div class="print-header-info" 
                                 style="background: #E6F7F8; 
                                        padding: 10px; 
                                        border-radius: 8px;
                                        border-left: 4px solid #2CB7BF;">
                                <p style="color: #333; margin: 3px 0;">
                                    <strong style="color: #763D93;">Período:</strong> 
                                    <span style="color: #2CB7BF; font-weight: bold;">
                                        <?php echo safe_date($fecha_inicio); ?> al <?php echo safe_date($fecha_fin); ?>
                                    </span>
                                </p>
                                <p style="color: #333; margin: 3px 0;">
                                    <strong style="color: #763D93;">Generado por:</strong> 
                                    <span style="color: #D62985;">
                                        <?php echo safe_html($_SESSION['doctor_nombre'] ?? ''); ?>
                                    </span>
                                </p>
                                <p style="color: #333; margin: 3px 0;">
                                    <strong style="color: #763D93;">Fecha de generación:</strong> 
                                    <?php echo date('d/m/Y H:i'); ?>
                                </p>
                                <p style="color: #333; margin: 3px 0;">
                                    <strong style="color: #763D93;">Total de registros:</strong> 
                                    <span style="color: #8FC744; font-weight: bold;">
                                        <?php echo count($datos_informe); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acciones (NO se imprimirán) -->
                    <div class="report-actions no-print">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Imprimir Informe
                        </button>
                        <a href="informes.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <?php if(isset($tipo_informe) && $tipo_informe != 'estadisticas_generales'): ?>
                        <button onclick="exportToExcel()" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($tipo_informe == 'estadisticas_generales'): ?>
                        <!-- Estadísticas generales - visible en impresión -->
                        <div class="stats-grid printable-stats" style="margin-top: 20px;">
                            <div class="stat-card printable-stat">
                                <div class="stat-number"><?php echo $datos_informe['total_pacientes']; ?></div>
                                <div class="stat-label">Pacientes Registrados</div>
                            </div>
                            <div class="stat-card printable-stat">
                                <div class="stat-number"><?php echo $datos_informe['total_consultas']; ?></div>
                                <div class="stat-label">Consultas Realizadas</div>
                            </div>
                            <div class="stat-card printable-stat">
                                <div class="stat-number"><?php echo $datos_informe['total_recetas']; ?></div>
                                <div class="stat-label">Recetas Emitidas</div>
                            </div>
                        </div>
                        
                        <div class="info-box printable-info">
                            <h4><i class="fas fa-chart-pie"></i> Resumen del Período</h4>
                            <p>En el período comprendido entre <?php echo safe_html($datos_informe['periodo']); ?>, se registró la siguiente actividad médica:</p>
                            <ul>
                                <li><?php echo $datos_informe['total_pacientes']; ?> pacientes fueron registrados en el sistema</li>
                                <li>Se realizaron <?php echo $datos_informe['total_consultas']; ?> consultas médicas</li>
                                <li>Se emitieron <?php echo $datos_informe['total_recetas']; ?> recetas médicas</li>
                            </ul>
                        </div>
                        
                    <?php else: ?>
                        <?php if(!empty($datos_informe)): ?>
                            <?php if($tipo_informe == 'recetas_emitidas'): ?>
                                <!-- Tabla mejorada para recetas -->
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Paciente</th>
                                            <th>Edad/Género</th>
                                            <th style="width: 100px;">Alergias</th>
                                            <th style="width: 200px;">Medicamentos</th>
                                            <th>Indicaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($datos_informe as $fila): ?>
                                        <tr class="receta-row">
                                            <td><?php echo safe_date($fila['fecha'] ?? ''); ?></td>
                                            <td><strong style="color: #763D93;"><?php echo safe_html($fila['paciente_nombre'] ?? ''); ?></strong></td>
                                            <td>
                                                <span style="color: #2CB7BF; font-weight: bold;">
                                                    <?php echo safe_html($fila['paciente_edad'] ?? ''); ?> años
                                                </span>
                                                <br>
                                                <small style="color: #D62985;">
                                                    <?php echo safe_html($fila['paciente_genero'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if(!empty($fila['alergias'])): ?>
                                                    <div style="background: #F8E0EF; padding: 3px 6px; border-radius: 3px; font-size: 8.5pt;">
                                                        <?php echo safe_html($fila['alergias']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: #8FC744; font-style: italic;">Ninguna</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($fila['medicamentos'])): ?>
                                                    <div class="medicamentos-container">
                                                        <?php foreach($fila['medicamentos'] as $index => $medicamento): ?>
                                                            <div class="receta-detalle" style="page-break-inside: avoid;">
                                                                <div class="medicamento-nombre" style="color: #763D93;">
                                                                    <span style="color: #D62985;">•</span> 
                                                                    <?php echo safe_html($medicamento['medicamento'] ?? ''); ?>
                                                                </div>
                                                                <?php if(!empty($medicamento['dosis'])): ?>
                                                                    <div class="medicamento-detalle" style="color: #2CB7BF;">
                                                                        <strong>Dosis:</strong> <?php echo safe_html($medicamento['dosis']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if(!empty($medicamento['frecuencia'])): ?>
                                                                    <div class="medicamento-detalle" style="color: #8FC744;">
                                                                        <strong>Frecuencia:</strong> <?php echo safe_html($medicamento['frecuencia']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if(!empty($medicamento['duracion'])): ?>
                                                                    <div class="medicamento-detalle" style="color: #763D93;">
                                                                        <strong>Duración:</strong> <?php echo safe_html($medicamento['duracion']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: #999; font-style: italic;">Sin medicamentos</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($fila['indicaciones'])): ?>
                                                    <small style="color: #444; font-size: 8.5pt;">
                                                        <?php echo safe_html($fila['indicaciones']); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span style="color: #999; font-size: 8.5pt;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif($tipo_informe == 'consultas_general'): ?>
                                <!-- Tabla para consultas generales -->
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Paciente</th>
                                            <th>Motivo</th>
                                            <th>Diagnóstico</th>
                                            <th>Médico</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($datos_informe as $fila): ?>
                                        <tr>
                                            <td><?php echo safe_date($fila['fecha'] ?? ''); ?></td>
                                            <td><strong style="color: #763D93;"><?php echo safe_html($fila['paciente_nombre'] ?? ''); ?></strong></td>
                                            <td><?php echo safe_html($fila['motivo_consulta'] ?? ''); ?></td>
                                            <td>
                                                <?php if(!empty($fila['diagnostico'])): ?>
                                                    <div style="background: #E6F7F8; padding: 3px 6px; border-radius: 3px; font-size: 8.5pt;">
                                                        <?php echo safe_html($fila['diagnostico']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: #999;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="color: #2CB7BF; font-weight: bold;">
                                                    <?php echo safe_html($fila['medico_nombre'] ?? ''); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif($tipo_informe == 'pacientes_registrados'): ?>
                                <!-- Tabla para pacientes registrados con direcciones truncadas -->
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Edad</th>
                                            <th>Género</th>
                                            <th>CURP</th>
                                            <th>Teléfono</th>
                                            <th>Dirección</th>
                                            <th>Fecha Registro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($datos_informe as $fila): ?>
                                        <tr>
                                            <td><strong style="color: #763D93;"><?php echo safe_html($fila['nombre'] ?? ''); ?></strong></td>
                                            <td>
                                                <span style="color: #2CB7BF; font-weight: bold;">
                                                    <?php echo safe_html($fila['edad'] ?? ''); ?> años
                                                </span>
                                            </td>
                                            <td>
                                                <span style="color: #D62985;">
                                                    <?php echo safe_html($fila['genero'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td class="curp-field">
                                                <?php if(!empty($fila['curp'])): ?>
                                                    <strong style="color: #8FC744;"><?php echo safe_html($fila['curp']); ?></strong>
                                                <?php else: ?>
                                                    <span class="no-curp">No registrada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($fila['telefono'])): ?>
                                                    <span style="color: #2CB7BF;"><?php echo safe_html($fila['telefono']); ?></span>
                                                <?php else: ?>
                                                    <span style="color: #999;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($fila['direccion'])): 
                                                    $direccion = safe_html($fila['direccion']);
                                                    // Truncar dirección si es muy larga
                                                    if(strlen($direccion) > 50) {
                                                        $direccion_corta = substr($direccion, 0, 50) . '...';
                                                    } else {
                                                        $direccion_corta = $direccion;
                                                    }
                                                ?>
                                                    <span class="truncated-address" title="<?php echo safe_html($fila['direccion']); ?>">
                                                        <?php echo $direccion_corta; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="no-curp">No registrada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="color: #763D93; font-size: 8.5pt;">
                                                    <?php echo safe_date($fila['created_at'] ?? ''); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <div style="margin-top: 2rem; text-align: center; padding: 15px; background: #F0F8E6; border-radius: 8px; border-left: 4px solid #8FC744;">
                                <p style="color: #763D93; margin: 0; font-weight: bold;">
                                    <i class="fas fa-clipboard-check"></i> Total de registros: 
                                    <span style="color: #D62985;"><?php echo count($datos_informe); ?></span>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="info-box">
                                <p><i class="fas fa-exclamation-triangle" style="color: #D62985;"></i> No se encontraron registros para el período seleccionado.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Pie de página para impresión con colores DIF -->
                    <div class="print-footer">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 10px; border-top: 2px solid #2CB7BF;">
                            <div style="text-align: left; font-size: 8pt; color: #763D93;">
                                <strong>Sello y firma del médico:</strong>
                                <div style="height: 40px; margin-top: 5px; border-bottom: 1px solid #ccc;"></div>
                            </div>
                            <div style="text-align: center; font-size: 8pt;">
                                <p style="color: #D62985; margin: 0; font-weight: bold;">Sistema Médico DIF Benito Juárez</p>
                                <p style="color: #666; margin: 2px 0;">Documento confidencial - Uso médico exclusivo</p>
                                <p style="color: #2CB7BF; margin: 0;">www.difbenitojuarez.mx</p>
                            </div>
                            <div style="text-align: right; font-size: 8pt; color: #8FC744;">
                                <p style="margin: 0;">Generado: <?php echo date('d/m/Y H:i'); ?></p>
                                <p style="margin: 2px 0;">Página <span class="page-number">1</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                function exportToExcel() {
                    // Crear un HTML mejor formateado para Excel
                    let html = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <style>
                                body { font-family: Arial, sans-serif; font-size: 11pt; }
                                h1 { color: #763D93; text-align: center; }
                                h2 { color: #D62985; }
                                table { border-collapse: collapse; width: 100%; margin-top: 20px; table-layout: fixed; }
                                th { background-color: #763D93; color: white; font-weight: bold; padding: 8px; text-align: left; }
                                td { border: 1px solid #ddd; padding: 8px; vertical-align: top; max-width: 150px; overflow: hidden; text-overflow: ellipsis; }
                                tr:nth-child(even) { background-color: #f2f2f2; }
                                .header-info { margin-bottom: 20px; padding: 10px; background-color: #E6F7F8; border-left: 4px solid #2CB7BF; }
                                .receta-med { background-color: #F0F8E6; border-left: 3px solid #8FC744; padding: 5px; margin: 2px 0; }
                                .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 9pt; color: #666; text-align: center; }
                                .medicamento-item { margin: 3px 0; padding: 3px; }
                                .truncated { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                            </style>
                        </head>
                        <body>
                            <h1>DIF Benito Juárez</h1>
                            <h2><?php echo safe_html($titulo_informe); ?></h2>
                            <div class="header-info">
                                <p><strong>Período:</strong> <?php echo safe_date($fecha_inicio); ?> al <?php echo safe_date($fecha_fin); ?></p>
                                <p><strong>Generado por:</strong> <?php echo safe_html($_SESSION['doctor_nombre'] ?? ''); ?></p>
                                <p><strong>Fecha de generación:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                                <p><strong>Total de registros:</strong> <?php echo count($datos_informe); ?></p>
                            </div>
                    `;
                    
                    <?php if($tipo_informe == 'estadisticas_generales'): ?>
                        html += `
                            <table>
                                <tr><th>Estadística</th><th>Valor</th></tr>
                                <tr><td>Pacientes Registrados</td><td><?php echo $datos_informe['total_pacientes']; ?></td></tr>
                                <tr><td>Consultas Realizadas</td><td><?php echo $datos_informe['total_consultas']; ?></td></tr>
                                <tr><td>Recetas Emitidas</td><td><?php echo $datos_informe['total_recetas']; ?></td></tr>
                            </table>
                        `;
                    <?php else: ?>
                        <?php if(!empty($datos_informe)): ?>
                            html += '<table>';
                            // Encabezados
                            html += '<tr>';
                            <?php if($tipo_informe == 'consultas_general'): ?>
                                html += '<th>Fecha</th><th>Paciente</th><th>Motivo</th><th>Diagnóstico</th><th>Médico</th>';
                            <?php elseif($tipo_informe == 'pacientes_registrados'): ?>
                                html += '<th>Nombre</th><th>Edad</th><th>Género</th><th>CURP</th><th>Teléfono</th><th>Dirección</th><th>Fecha Registro</th>';
                            <?php elseif($tipo_informe == 'recetas_emitidas'): ?>
                                html += '<th>Fecha</th><th>Paciente</th><th>Edad/Género</th><th>Alergias</th><th>Medicamentos</th><th>Indicaciones</th>';
                            <?php endif; ?>
                            html += '</tr>';
                            
                            // Datos
                            <?php foreach($datos_informe as $fila): ?>
                                html += '<tr>';
                                <?php if($tipo_informe == 'consultas_general'): ?>
                                    html += '<td><?php echo safe_date($fila['fecha'] ?? ''); ?></td>';
                                    html += '<td><?php echo safe_html($fila['paciente_nombre'] ?? ''); ?></td>';
                                    html += '<td><?php echo safe_html($fila['motivo_consulta'] ?? ''); ?></td>';
                                    html += '<td><?php echo !empty($fila['diagnostico']) ? safe_html($fila['diagnostico']) : 'N/A'; ?></td>';
                                    html += '<td><?php echo safe_html($fila['medico_nombre'] ?? ''); ?></td>';
                                <?php elseif($tipo_informe == 'pacientes_registrados'): ?>
                                    html += '<td><?php echo safe_html($fila['nombre'] ?? ''); ?></td>';
                                    html += '<td><?php echo safe_html($fila['edad'] ?? ''); ?> años</td>';
                                    html += '<td><?php echo safe_html($fila['genero'] ?? ''); ?></td>';
                                    html += '<td><?php echo !empty($fila['curp']) ? safe_html($fila['curp']) : 'No registrada'; ?></td>';
                                    html += '<td><?php echo !empty($fila['telefono']) ? safe_html($fila['telefono']) : 'N/A'; ?></td>';
                                    <?php
                                    // Truncar dirección para Excel también
                                    if(!empty($fila['direccion'])) {
                                        $direccion_excel = safe_html($fila['direccion']);
                                        if(strlen($direccion_excel) > 50) {
                                            $direccion_excel = substr($direccion_excel, 0, 50) . '...';
                                        }
                                    } else {
                                        $direccion_excel = 'No registrada';
                                    }
                                    ?>
                                    html += '<td class="truncated"><?php echo $direccion_excel; ?></td>';
                                    html += '<td><?php echo safe_date($fila['created_at'] ?? ''); ?></td>';
                                <?php elseif($tipo_informe == 'recetas_emitidas'): ?>
                                    html += '<td><?php echo safe_date($fila['fecha'] ?? ''); ?></td>';
                                    html += '<td><?php echo safe_html($fila['paciente_nombre'] ?? ''); ?></td>';
                                    html += '<td><?php echo safe_html($fila['paciente_edad'] ?? ''); ?> años / <?php echo safe_html($fila['paciente_genero'] ?? ''); ?></td>';
                                    html += '<td><?php echo !empty($fila['alergias']) ? safe_html($fila['alergias']) : 'Ninguna'; ?></td>';
                                    html += '<td>';
                                    <?php if(!empty($fila['medicamentos'])): ?>
                                        <?php foreach($fila['medicamentos'] as $medicamento): ?>
                                            html += '<div class="medicamento-item">';
                                            html += '<strong>• <?php echo safe_html($medicamento['medicamento'] ?? ''); ?></strong>';
                                            <?php if(!empty($medicamento['dosis'])): ?>
                                                html += '<br>Dosis: <?php echo safe_html($medicamento['dosis']); ?>';
                                            <?php endif; ?>
                                            <?php if(!empty($medicamento['frecuencia'])): ?>
                                                html += '<br>Frecuencia: <?php echo safe_html($medicamento['frecuencia']); ?>';
                                            <?php endif; ?>
                                            <?php if(!empty($medicamento['duracion'])): ?>
                                                html += '<br>Duración: <?php echo safe_html($medicamento['duracion']); ?>';
                                            <?php endif; ?>
                                            html += '</div>';
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        html += 'Sin medicamentos';
                                    <?php endif; ?>
                                    html += '</td>';
                                    html += '<td><?php echo !empty($fila['indicaciones']) ? safe_html($fila['indicaciones']) : 'N/A'; ?></td>';
                                <?php endif; ?>
                                html += '</tr>';
                            <?php endforeach; ?>
                            html += '</table>';
                        <?php else: ?>
                            html += '<p>No se encontraron registros para el período seleccionado.</p>';
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    html += `
                            <div class="footer">
                                <p>Informe generado automáticamente por el Sistema Médico DIF Benito Juárez</p>
                                <p>Documento confidencial - Uso médico exclusivo</p>
                            </div>
                        </body>
                        </html>
                    `;
                    
                    // Crear blob y descargar
                    let blob = new Blob([html], {type: 'application/vnd.ms-excel'});
                    let url = URL.createObjectURL(blob);
                    let link = document.createElement('a');
                    let fileName = 'informe_<?php echo $tipo_informe ?? "general"; ?>_<?php echo date('Y-m-d'); ?>.xls';
                    link.download = fileName;
                    link.href = url;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
                
                // Numeración de páginas en pie de página
                document.addEventListener('DOMContentLoaded', function() {
                    // Actualizar números de página en pie de página
                    const pageNumbers = document.querySelectorAll('.page-number');
                    if (pageNumbers.length > 0) {
                        // Esto se ejecutará al imprimir
                        window.addEventListener('beforeprint', function() {
                            const totalPages = Math.ceil(document.querySelector('.printable-content').scrollHeight / window.innerHeight);
                            pageNumbers.forEach(span => {
                                span.textContent = `1 de ${totalPages}`;
                            });
                        });
                    }
                });
                </script>

            <?php else: ?>
                <div class="info-box">
                    <h3><i class="fas fa-exclamation-triangle"></i> No se pudo generar el informe</h3>
                    <p>Ocurrió un error al procesar su solicitud. Por favor, intente nuevamente.</p>
                    <a href="informes.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Volver a Informes</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- ====== FOOTER ====== -->
    <footer class="footer" role="contentinfo">
        <p>© <?php echo date('Y'); ?> DIF municipal Benito Juárez Tlaxcala</p>
    </footer>

    <script>
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
    
    // Configurar Chart.js para usar colores DIF
    Chart.defaults.color = '#333';
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.font.size = 11;
    </script>
</body>
</html>