<?php
session_start();
// Verificar sesión
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php?error=2');
    exit;
}

// Incluir configuración de base de datos
require_once 'config/database.php';

// Crear conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

$accion = $_GET['accion'] ?? 'listar';
$paciente_id = $_GET['paciente_id'] ?? null;

// Obtener información del paciente si hay paciente_id
$paciente = null;
if ($paciente_id) {
    $stmt = $conn->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$paciente_id]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Guardar consulta
if ($accion == 'guardar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "INSERT INTO consultas (paciente_id, fecha, hora, motivo_consulta, sintomas, diagnostico, tratamiento, observaciones, medico_nombre, medico_cedula) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['paciente_id'],
            $_POST['fecha'],
            $_POST['hora'],
            $_POST['motivo_consulta'],
            $_POST['sintomas'],
            $_POST['diagnostico'],
            $_POST['tratamiento'],
            $_POST['observaciones'],
            $_SESSION['doctor_nombre'],
            $_SESSION['doctor_cedula']
        ]);
        
        header("Location: consultas.php?paciente_id=" . $_POST['paciente_id'] . "&success=1");
        exit;
        
    } catch (PDOException $e) {
        $error = "Error al guardar la consulta: " . $e->getMessage();
    }
}

// Actualizar consulta
if ($accion == 'actualizar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "UPDATE consultas SET fecha=?, hora=?, motivo_consulta=?, sintomas=?, diagnostico=?, tratamiento=?, observaciones=? WHERE id=?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['fecha'],
            $_POST['hora'],
            $_POST['motivo_consulta'],
            $_POST['sintomas'],
            $_POST['diagnostico'],
            $_POST['tratamiento'],
            $_POST['observaciones'],
            $_POST['id']
        ]);
        
        header("Location: consultas.php?paciente_id=" . $_POST['paciente_id'] . "&success=2");
        exit;
        
    } catch (PDOException $e) {
        $error = "Error al actualizar la consulta: " . $e->getMessage();
    }
}

// Borrar consulta
if ($accion == 'borrar' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM consultas WHERE id=?");
        $stmt->execute([$_GET['id']]);
        header("Location: consultas.php?paciente_id=" . $_GET['paciente_id'] . "&success=3");
        exit;
    } catch (PDOException $e) {
        $error = "Error al borrar la consulta: " . $e->getMessage();
    }
}

// Listar consultas del paciente
$consultas = [];
if ($paciente_id) {
    $stmt = $conn->prepare("SELECT * FROM consultas WHERE paciente_id = ? ORDER BY fecha DESC, hora DESC");
    $stmt->execute([$paciente_id]);
    $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener consulta específica para editar
$consulta_actual = null;
if (($accion == 'editar' || $accion == 'ver') && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM consultas WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $consulta_actual = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas Médicas - DIF Benito Juárez</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, var(--dif-turquesa-light) 0%, var(--dif-verde-light) 100%); 
            margin: 0; 
            padding: 0; 
            min-height: 100vh;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 2rem auto; 
            background: var(--white); 
            padding: 2rem; 
            border-radius: var(--border-radius); 
            box-shadow: var(--shadow);
            border: 2px solid var(--dif-magenta-light);
        }
        
        h2, h3 { 
            color: var(--dif-morado); 
            margin-bottom: 1.5rem; 
        }
        
        .btn { 
            padding: 10px 20px; 
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
            box-shadow: 0 4px 15px rgba(143,199,68,0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(143,199,68,0.4);
        }
        
        .btn-warning { 
            background: linear-gradient(135deg, var(--dif-turquesa) 0%, var(--dif-turquesa-dark) 100%); 
            color: white; 
            box-shadow: 0 4px 15px rgba(44,183,191,0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44,183,191,0.4);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            box-shadow: 0 4px 15px rgba(220,53,69,0.3); 
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220,53,69,0.4);
        }
        
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
        
        .form-group-full {
            width: 100%;
        }
        
        form input, form select, form textarea { 
            padding: 10px; 
            width: 100%; 
            margin: 8px 0; 
            border-radius: 8px; 
            border: 1px solid #ccc; 
            box-sizing: border-box; 
            font-family: inherit;
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
        
        .info-paciente {
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            border-left: 4px solid var(--dif-turquesa);
        }
        
        .info-paciente h3 {
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .consulta-card {
            border: 1px solid var(--dif-magenta-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: var(--white);
            transition: all 0.3s;
            border-left: 4px solid var(--dif-turquesa);
        }
        
        .consulta-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
            border-left-color: var(--dif-magenta);
        }
        
        .consulta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--dif-turquesa-light);
        }
        
        .consulta-fecha {
            font-weight: bold;
            color: var(--dif-morado);
        }
        
        .consulta-motivo {
            font-style: italic;
            color: var(--text-light);
            margin-bottom: 1rem;
        }
        
        .consulta-diagnostico {
            background: var(--dif-turquesa-light);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 1rem 0;
            border-left: 3px solid var(--dif-turquesa);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
            background: var(--dif-turquesa-light);
            border-radius: var(--border-radius);
            border: 2px dashed var(--dif-turquesa);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--dif-turquesa);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--dif-magenta-light);
        }
        
        .section-header h3 {
            margin-bottom: 0;
        }
        
        .consulta-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .info-item {
            background: var(--dif-magenta-light);
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--dif-magenta);
        }
        
        .info-item strong {
            color: var(--dif-morado);
            display: block;
            margin-bottom: 0.5rem;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .container {
            animation: fadeIn 0.8s ease-out;
        }
        
        @media print {
            .no-print, .btn { 
                display: none !important; 
            }
            .container { 
                box-shadow: none !important; 
                margin: 0 !important; 
                padding: 0 !important; 
                border: none !important;
                background: white !important;
            }
            body { 
                background: white !important; 
                font-size: 10pt !important;
            }
            h2, h3 {
                color: #000 !important;
            }
            .consulta-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                margin: 1rem 0 !important;
            }
            .consulta-diagnostico {
                background: #f0f0f0 !important;
                border-left: 2px solid #000 !important;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .consulta-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .consulta-header > div {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .consulta-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <?php if(isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(isset($_GET['success'])): ?>
        <div class="success">
            <?php 
            switch($_GET['success']) {
                case '1': echo "✓ Consulta guardada correctamente"; break;
                case '2': echo "✓ Consulta actualizada correctamente"; break;
                case '3': echo "✓ Consulta eliminada correctamente"; break;
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="section-header">
        <h2>
            <?php if($paciente): ?>
                <i class="fas fa-file-medical" style="color: var(--dif-magenta); margin-right: 10px;"></i> 
                Consultas Médicas - <?php echo $paciente['nombre']; ?>
            <?php else: ?>
                <i class="fas fa-file-medical" style="color: var(--dif-magenta); margin-right: 10px;"></i> 
                Consultas Médicas
            <?php endif; ?>
        </h2>
        <a href="pacientes.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver a Pacientes</a>
    </div>

    <?php if($paciente): ?>
        <div class="info-paciente">
            <h3><i class="fas fa-user-injured"></i> <?php echo $paciente['nombre']; ?></h3>
            <p>
                <strong>Edad:</strong> <?php echo $paciente['edad']; ?> años | 
                <strong>Género:</strong> <?php echo $paciente['genero']; ?> | 
                <strong>Teléfono:</strong> <?php echo $paciente['telefono'] ?? 'N/A'; ?>
            </p>
            <?php if(!empty($paciente['curp'])): ?>
                <p><strong>CURP:</strong> <?php echo $paciente['curp']; ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if($accion == 'nueva' && $paciente): ?>
        <div class="form-section">
            <h3><i class="fas fa-plus-circle" style="color: var(--dif-magenta);"></i> Nueva Consulta Médica</h3>
            <form method="post" action="consultas.php?accion=guardar">
                <input type="hidden" name="paciente_id" value="<?php echo $paciente['id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Fecha de consulta:</label>
                        <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Hora de consulta:</label>
                        <input type="time" name="hora" value="<?php echo date('H:i'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-stethoscope"></i> Motivo de consulta:</label>
                    <textarea name="motivo_consulta" placeholder="Describa el motivo principal de la consulta..." required><?php echo $consulta_actual['motivo_consulta'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-heartbeat"></i> Síntomas:</label>
                    <textarea name="sintomas" placeholder="Describa los síntomas que presenta el paciente..."><?php echo $consulta_actual['sintomas'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-diagnoses"></i> Diagnóstico:</label>
                    <textarea name="diagnostico" placeholder="Escriba el diagnóstico..."><?php echo $consulta_actual['diagnostico'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-capsules"></i> Tratamiento:</label>
                    <textarea name="tratamiento" placeholder="Describa el tratamiento indicado..."><?php echo $consulta_actual['tratamiento'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-clipboard"></i> Observaciones:</label>
                    <textarea name="observaciones" placeholder="Observaciones adicionales..."><?php echo $consulta_actual['observaciones'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Consulta</button>
                    <a href="consultas.php?paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>

    <?php elseif($accion == 'editar' && $consulta_actual): ?>
        <div class="form-section">
            <h3><i class="fas fa-edit" style="color: var(--dif-magenta);"></i> Editar Consulta Médica</h3>
            <form method="post" action="consultas.php?accion=actualizar">
                <input type="hidden" name="id" value="<?php echo $consulta_actual['id']; ?>">
                <input type="hidden" name="paciente_id" value="<?php echo $consulta_actual['paciente_id']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Fecha de consulta:</label>
                        <input type="date" name="fecha" value="<?php echo $consulta_actual['fecha']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Hora de consulta:</label>
                        <input type="time" name="hora" value="<?php echo $consulta_actual['hora']; ?>" required>
                    </div>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-stethoscope"></i> Motivo de consulta:</label>
                    <textarea name="motivo_consulta" placeholder="Describa el motivo principal de la consulta..." required><?php echo $consulta_actual['motivo_consulta']; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-heartbeat"></i> Síntomas:</label>
                    <textarea name="sintomas" placeholder="Describa los síntomas que presenta el paciente..."><?php echo $consulta_actual['sintomas']; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-diagnoses"></i> Diagnóstico:</label>
                    <textarea name="diagnostico" placeholder="Escriba el diagnóstico..."><?php echo $consulta_actual['diagnostico']; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-capsules"></i> Tratamiento:</label>
                    <textarea name="tratamiento" placeholder="Describa el tratamiento indicado..."><?php echo $consulta_actual['tratamiento']; ?></textarea>
                </div>
                
                <div class="form-group-full">
                    <label><i class="fas fa-clipboard"></i> Observaciones:</label>
                    <textarea name="observaciones" placeholder="Observaciones adicionales..."><?php echo $consulta_actual['observaciones']; ?></textarea>
                </div>
                
                <div class="form-row">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar Consulta</button>
                    <a href="consultas.php?paciente_id=<?php echo $consulta_actual['paciente_id']; ?>" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>

    <?php elseif($accion == 'ver' && $consulta_actual): ?>
        <div class="consulta-card">
            <div class="consulta-header">
                <div class="consulta-fecha">
                    <i class="fas fa-calendar-alt" style="color: var(--dif-magenta);"></i>
                    <?php echo date('d/m/Y', strtotime($consulta_actual['fecha'])); ?> 
                    a las 
                    <?php echo date('H:i', strtotime($consulta_actual['hora'])); ?>
                </div>
                <div class="no-print">
                    <a href="consultas.php?paciente_id=<?php echo $consulta_actual['paciente_id']; ?>" class="btn btn-outline">Volver</a>
                    <button onclick="window.print()" class="btn btn-warning"><i class="fas fa-print"></i> Imprimir</button>
                </div>
            </div>
            
            <div class="consulta-motivo">
                <strong>Motivo de consulta:</strong> <?php echo $consulta_actual['motivo_consulta']; ?>
            </div>
            
            <div class="consulta-info-grid">
                <?php if($consulta_actual['sintomas']): ?>
                    <div class="info-item">
                        <strong>Síntomas:</strong>
                        <p><?php echo nl2br($consulta_actual['sintomas']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if($consulta_actual['diagnostico']): ?>
                    <div class="consulta-diagnostico">
                        <strong>Diagnóstico:</strong>
                        <p><?php echo nl2br($consulta_actual['diagnostico']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if($consulta_actual['tratamiento']): ?>
                    <div class="info-item">
                        <strong>Tratamiento:</strong>
                        <p><?php echo nl2br($consulta_actual['tratamiento']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if($consulta_actual['observaciones']): ?>
                    <div class="info-item">
                        <strong>Observaciones:</strong>
                        <p><?php echo nl2br($consulta_actual['observaciones']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--dif-magenta-light); background: var(--dif-morado-light); padding: 1rem; border-radius: var(--border-radius);">
                <p><strong><i class="fas fa-user-md"></i> Médico:</strong> <?php echo $consulta_actual['medico_nombre']; ?></p>
                <p><strong><i class="fas fa-id-card"></i> Cédula profesional:</strong> <?php echo $consulta_actual['medico_cedula']; ?></p>
            </div>
        </div>

    <?php elseif($paciente_id): ?>
        <div class="section-header">
            <h3><i class="fas fa-history" style="color: var(--dif-morado);"></i> Historial de Consultas</h3>
            <a href="consultas.php?accion=nueva&paciente_id=<?php echo $paciente_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Consulta
            </a>
        </div>

        <?php if(count($consultas) > 0): ?>
            <?php foreach($consultas as $consulta): ?>
                <div class="consulta-card">
                    <div class="consulta-header">
                        <div class="consulta-fecha">
                            <i class="fas fa-calendar-alt" style="color: var(--dif-magenta);"></i>
                            <?php echo date('d/m/Y', strtotime($consulta['fecha'])); ?> 
                            a las 
                            <?php echo date('H:i', strtotime($consulta['hora'])); ?>
                        </div>
                        <div>
                            <a href="consultas.php?accion=ver&id=<?php echo $consulta['id']; ?>&paciente_id=<?php echo $paciente_id; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="consultas.php?accion=editar&id=<?php echo $consulta['id']; ?>&paciente_id=<?php echo $paciente_id; ?>" class="btn btn-outline">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="consultas.php?accion=borrar&id=<?php echo $consulta['id']; ?>&paciente_id=<?php echo $paciente_id; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('¿Está seguro de que desea eliminar esta consulta?');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </div>
                    </div>
                    
                    <div class="consulta-motivo">
                        <strong>Motivo:</strong> <?php echo $consulta['motivo_consulta']; ?>
                    </div>
                    
                    <?php if($consulta['diagnostico']): ?>
                        <div>
                            <strong>Diagnóstico:</strong> 
                            <?php echo strlen($consulta['diagnostico']) > 100 ? substr($consulta['diagnostico'], 0, 100) . '...' : $consulta['diagnostico']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--dif-magenta-light); font-size: 0.9rem; color: var(--text-light);">
                        <strong>Médico:</strong> <?php echo $consulta['medico_nombre']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-file-medical" style="color: var(--dif-turquesa);"></i>
                <h3>No hay consultas registradas</h3>
                <p>Este paciente no tiene consultas médicas registradas.</p>
                <a href="consultas.php?accion=nueva&paciente_id=<?php echo $paciente_id; ?>" class="btn btn-primary">
                    <i class="fas fa-file-medical"></i> Registrar Primera Consulta
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-file-medical" style="color: var(--dif-magenta);"></i>
            <h3>Seleccione un paciente</h3>
            <p>Para gestionar consultas médicas, primero seleccione un paciente.</p>
            <a href="pacientes.php" class="btn btn-primary">
                <i class="fas fa-list"></i> Ver Lista de Pacientes
            </a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>