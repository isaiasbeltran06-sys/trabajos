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

$paciente_id = $_GET['paciente_id'] ?? null;
$accion = $_GET['accion'] ?? 'ver';

// Obtener información del paciente
$paciente = null;
if ($paciente_id) {
    $stmt = $conn->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$paciente_id]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener historial de consultas del paciente
$consultas = [];
if ($paciente_id) {
    $stmt = $conn->prepare("SELECT * FROM consultas WHERE paciente_id = ? ORDER BY fecha DESC, hora DESC");
    $stmt->execute([$paciente_id]);
    $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener historial de recetas del paciente
$recetas = [];
if ($paciente_id) {
    $stmt = $conn->prepare("SELECT * FROM recetas WHERE paciente_id = ? ORDER BY fecha DESC");
    $stmt->execute([$paciente_id]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Médico - DIF Benito Juárez</title>
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
            background: #fff; 
            padding: 2rem; 
            border-radius: 15px; 
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
        
        .historial-section {
            margin: 2rem 0;
        }
        
        .historial-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .consulta-card, .receta-card {
            border: 1px solid var(--dif-magenta-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: var(--white);
            transition: all 0.3s;
            border-left: 4px solid var(--dif-turquesa);
        }
        
        .consulta-card:hover, .receta-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
            border-left-color: var(--dif-magenta);
        }
        
        .consulta-header, .receta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--dif-turquesa-light);
        }
        
        .consulta-fecha, .receta-fecha {
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
        
        .receta-medicamento {
            font-weight: bold;
            color: var(--dif-morado);
            margin-bottom: 0.5rem;
        }
        
        .receta-detalles {
            color: var(--text-light);
            font-size: 0.9rem;
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
        
        .actions-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .badge-consultas {
            background: var(--dif-morado-light);
            color: var(--dif-morado-dark);
            border: 1px solid var(--dif-morado);
        }
        
        .badge-recetas {
            background: var(--dif-verde-light);
            color: var(--dif-verde-dark);
            border: 1px solid var(--dif-verde);
        }
        
        .stats-summary {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            background: var(--dif-turquesa-light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 2px solid var(--dif-magenta-light);
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dif-morado);
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .historial-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .actions-bar {
                flex-direction: column;
            }
            
            .stats-summary {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stat-item {
                min-width: 100%;
            }
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .container {
            animation: fadeIn 0.8s ease-out;
        }
        
        /* Colores para las estadísticas */
        .stat-consultas .stat-number { color: var(--dif-morado); }
        .stat-recetas .stat-number { color: var(--dif-verde); }
        .stat-primera .stat-number { color: var(--dif-magenta); }
    </style>
</head>
<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2><i class="fas fa-history" style="color: var(--dif-magenta); margin-right: 10px;"></i> Historial Médico del Paciente</h2>
        <a href="pacientes.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver a Pacientes</a>
    </div>

    <?php if($paciente): ?>
        <!-- Información del Paciente -->
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

        <!-- Barra de Acciones -->
        <div class="actions-bar">
            <a href="consultas.php?accion=nueva&paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-primary">
                <i class="fas fa-file-medical"></i> Nueva Consulta
            </a>
            <a href="recetas.php?accion=nueva&paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-warning">
                <i class="fas fa-file-prescription"></i> Nueva Receta
            </a>
            <a href="pacientes.php?accion=ver&id=<?php echo $paciente['id']; ?>" class="btn btn-success">
                <i class="fas fa-user"></i> Ver Ficha Completa
            </a>
        </div>

        <!-- Resumen de Historial -->
        <div class="stats-summary">
            <div class="stat-item stat-consultas">
                <div class="stat-number"><?php echo count($consultas); ?></div>
                <div class="stat-label">Consultas Realizadas</div>
            </div>
            <div class="stat-item stat-recetas">
                <div class="stat-number"><?php echo count($recetas); ?></div>
                <div class="stat-label">Recetas Emitidas</div>
            </div>
            <div class="stat-item stat-primera">
                <div class="stat-number">
                    <?php 
                    $primera_consulta = !empty($consultas) ? min(array_column($consultas, 'fecha')) : 'N/A';
                    echo $primera_consulta != 'N/A' ? date('d/m/Y', strtotime($primera_consulta)) : 'N/A';
                    ?>
                </div>
                <div class="stat-label">Primera Consulta</div>
            </div>
        </div>

        <!-- Historial en Grid -->
        <div class="historial-grid">
            <!-- Columna de Consultas -->
            <div class="historial-section">
                <h3>
                    <i class="fas fa-file-medical" style="color: var(--dif-morado);"></i> 
                    Historial de Consultas
                    <span class="badge badge-consultas"><?php echo count($consultas); ?></span>
                </h3>
                
                <?php if(!empty($consultas)): ?>
                    <?php foreach($consultas as $consulta): ?>
                        <div class="consulta-card">
                            <div class="consulta-header">
                                <div class="consulta-fecha">
                                    <i class="fas fa-calendar-alt" style="color: var(--dif-magenta);"></i>
                                    <?php echo date('d/m/Y', strtotime($consulta['fecha'])); ?>
                                </div>
                                <div>
                                    <a href="consultas.php?accion=ver&id=<?php echo $consulta['id']; ?>&paciente_id=<?php echo $paciente['id']; ?>" 
                                       class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </div>
                            </div>
                            
                            <div class="consulta-motivo">
                                <strong>Motivo:</strong> <?php echo $consulta['motivo_consulta']; ?>
                            </div>
                            
                            <?php if($consulta['diagnostico']): ?>
                                <div class="consulta-diagnostico">
                                    <strong>Diagnóstico:</strong>
                                    <p><?php echo nl2br($consulta['diagnostico']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if($consulta['tratamiento']): ?>
                                <div>
                                    <strong>Tratamiento:</strong>
                                    <p><?php echo nl2br($consulta['tratamiento']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--dif-magenta-light); font-size: 0.9rem; color: var(--text-light);">
                                <strong>Médico:</strong> <?php echo $consulta['medico_nombre']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-medical"></i>
                        <h4>No hay consultas registradas</h4>
                        <p>Este paciente no tiene consultas médicas en su historial.</p>
                        <a href="consultas.php?accion=nueva&paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar Primera Consulta
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Columna de Recetas -->
            <div class="historial-section">
                <h3>
                    <i class="fas fa-file-prescription" style="color: var(--dif-verde);"></i> 
                    Historial de Recetas
                    <span class="badge badge-recetas"><?php echo count($recetas); ?></span>
                </h3>
                
                <?php if(!empty($recetas)): ?>
                    <?php foreach($recetas as $receta): ?>
                        <div class="receta-card">
                            <div class="receta-header">
                                <div class="receta-fecha">
                                    <i class="fas fa-calendar-alt" style="color: var(--dif-magenta);"></i>
                                    <?php echo date('d/m/Y', strtotime($receta['fecha'])); ?>
                                </div>
                                <div>
                                    <button onclick="imprimirReceta(<?php echo $receta['id']; ?>)" 
                                            class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fas fa-print"></i> Imprimir
                                    </button>
                                </div>
                            </div>
                            
                            <div class="receta-medicamento">
                                <?php echo $receta['medicamento']; ?>
                            </div>
                            
                            <div class="receta-detalles">
                                <?php if($receta['dosis']): ?>
                                    <div><strong>Dosis:</strong> <?php echo $receta['dosis']; ?></div>
                                <?php endif; ?>
                                
                                <?php if($receta['frecuencia']): ?>
                                    <div><strong>Frecuencia:</strong> <?php echo $receta['frecuencia']; ?></div>
                                <?php endif; ?>
                                
                                <?php if($receta['duracion']): ?>
                                    <div><strong>Duración:</strong> <?php echo $receta['duracion']; ?></div>
                                <?php endif; ?>
                                
                                <?php if($receta['indicaciones']): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <strong>Indicaciones:</strong>
                                        <p><?php echo nl2br($receta['indicaciones']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--dif-magenta-light); font-size: 0.9rem; color: var(--text-light);">
                                <strong>Médico:</strong> <?php echo $receta['medico_nombre'] ?? $_SESSION['doctor_nombre']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-prescription"></i>
                        <h4>No hay recetas registradas</h4>
                        <p>Este paciente no tiene recetas médicas en su historial.</p>
                        <a href="recetas.php?accion=nueva&paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-plus"></i> Emitir Primera Receta
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-injured" style="color: var(--dif-magenta);"></i>
            <h3>Seleccione un paciente</h3>
            <p>Para ver el historial médico, primero seleccione un paciente.</p>
            <a href="pacientes.php" class="btn btn-primary">
                <i class="fas fa-list"></i> Ver Lista de Pacientes
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function imprimirReceta(recetaId) {
    window.open('recetas.php?accion=imprimir&id=' + recetaId, '_blank');
}
</script>
</body>
</html>