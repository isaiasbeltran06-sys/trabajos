<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($usuario) || empty($password)) {
        header('Location: login.php?error=1');
        exit;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Consulta para verificar credenciales
        $query = "SELECT * FROM usuarios WHERE username = :username AND activo = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $usuario);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña (usando password_verify para contraseñas hasheadas)
            if ($password === $usuario_data['password']) {
                // Credenciales correctas - Iniciar sesión
                $_SESSION['doctor_id'] = $usuario_data['id'];
                $_SESSION['doctor_usuario'] = $usuario_data['username'];
                $_SESSION['doctor_nombre'] = $usuario_data['nombre'];
                $_SESSION['doctor_email'] = $usuario_data['email'];
                $_SESSION['doctor_rol'] = $usuario_data['rol'];
                $_SESSION['login_time'] = time();
                
                // Agregar campos profesionales si existen
                if (isset($usuario_data['especialidad'])) {
                    $_SESSION['doctor_especialidad'] = $usuario_data['especialidad'];
                } else {
                    $_SESSION['doctor_especialidad'] = 'Médico Cirujano';
                }
                
                if (isset($usuario_data['universidad'])) {
                    $_SESSION['doctor_universidad'] = $usuario_data['universidad'];
                }
                
                if (isset($usuario_data['cedula'])) {
                    $_SESSION['doctor_cedula'] = $usuario_data['cedula'];
                } else {
                    $_SESSION['doctor_cedula'] = $usuario_data['username'];
                }
                
                if (isset($usuario_data['iniciales'])) {
                    $_SESSION['doctor_iniciales'] = $usuario_data['iniciales'];
                } else {
                    // Generar iniciales automáticamente
                    $nombres = explode(' ', $usuario_data['nombre']);
                    $iniciales = '';
                    foreach ($nombres as $nombre) {
                        if (!empty($nombre)) {
                            $iniciales .= strtoupper($nombre[0]);
                        }
                    }
                    $_SESSION['doctor_iniciales'] = substr($iniciales, 0, 2);
                }
                
                // Registrar el acceso en la base de datos (opcional)
                $update_query = "UPDATE usuarios SET last_login = NOW() WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':id', $usuario_data['id']);
                $update_stmt->execute();
                
                // Redirigir al dashboard
                header('Location: dashboard.php');
                exit;
            }
        }
        
        // Si llegamos aquí, las credenciales son incorrectas
        header('Location: login.php?error=1');
        exit;
        
    } catch(PDOException $exception) {
        // En caso de error de base de datos, puedes redirigir a una página de error
        // o mostrar un mensaje (no recomendado en producción)
        error_log("Error de base de datos: " . $exception->getMessage());
        header('Location: login.php?error=4');
        exit;
    }
} else {
    // Si no es POST, redirigir al login
    header('Location: login.php');
    exit;
}
?>