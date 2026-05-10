<?php
session_start();
require_once '../config/database.php';

// Verificar si es administrador
if (!isset($_SESSION['doctor_rol']) || $_SESSION['doctor_rol'] !== 'admin') {
    header('Location: ../login.php?error=2');
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula']);
    $nombre = trim($_POST['nombre']);
    $especialidad = trim($_POST['especialidad']);
    $universidad = trim($_POST['universidad']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verificar si la cédula ya existe
        $check_query = "SELECT id FROM usuarios WHERE username = :cedula";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':cedula', $cedula);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $mensaje = "Error: La cédula profesional ya está registrada.";
        } else {
            // Insertar nuevo doctor
            $insert_query = "INSERT INTO usuarios (username, password, nombre, email, rol, activo, especialidad, universidad, cedula, iniciales, created_at) 
                            VALUES (:username, :password, :nombre, :email, 'medico', 1, :especialidad, :universidad, :cedula, :iniciales, NOW())";
            
            $insert_stmt = $db->prepare($insert_query);
            
            // Generar iniciales
            $nombres = explode(' ', $nombre);
            $iniciales = '';
            foreach ($nombres as $nombre_part) {
                if (!empty($nombre_part)) {
                    $iniciales .= strtoupper($nombre_part[0]);
                }
            }
            $iniciales = substr($iniciales, 0, 2);
            
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_stmt->bindParam(':username', $cedula);
            $insert_stmt->bindParam(':password', $password_hash);
            $insert_stmt->bindParam(':nombre', $nombre);
            $insert_stmt->bindParam(':email', $email);
            $insert_stmt->bindParam(':especialidad', $especialidad);
            $insert_stmt->bindParam(':universidad', $universidad);
            $insert_stmt->bindParam(':cedula', $cedula);
            $insert_stmt->bindParam(':iniciales', $iniciales);
            
            if ($insert_stmt->execute()) {
                $mensaje = "Doctor agregado exitosamente. Cédula: $cedula";
            } else {
                $mensaje = "Error al agregar el doctor.";
            }
        }
    } catch(PDOException $exception) {
        $mensaje = "Error de base de datos: " . $exception->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Doctor - DIF Benito Juárez</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #2a5bd7; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .mensaje { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Agregar Nuevo Doctor</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo strpos($mensaje, 'Error') === false ? 'exito' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Cédula Profesional:</label>
                <input type="text" name="cedula" required>
            </div>
            
            <div class="form-group">
                <label>Nombre Completo:</label>
                <input type="text" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label>Especialidad:</label>
                <input type="text" name="especialidad" value="Lic. Médico Cirujano" required>
            </div>
            
            <div class="form-group">
                <label>Universidad:</label>
                <input type="text" name="universidad" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Contraseña Temporal:</label>
                <input type="text" name="password" value="medico123" required>
            </div>
            
            <button type="submit">Agregar Doctor</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="../dashboard.php">← Volver al Dashboard</a>
        </p>
    </div>
</body>
</html>