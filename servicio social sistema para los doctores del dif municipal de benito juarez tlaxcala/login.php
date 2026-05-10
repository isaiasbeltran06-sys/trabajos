<?php
session_start();
require_once 'config/database.php';

// Configuración para sistema local
$sistema_local = true;

// Procesar registro de nuevo doctor
if (isset($_POST['registrar_doctor'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $nombre = trim($_POST['nombre']);
        $cedula = trim($_POST['cedula']);
        $especialidad = trim($_POST['especialidad']);
        $universidad = trim($_POST['universidad']);
        $email = trim($_POST['email']);
        $password = $_POST['password_temp'];
        $confirm_password = $_POST['password_confirm'];
        
        // Validaciones
        if (empty($nombre) || empty($cedula) || empty($password)) {
            $error_registro = "Todos los campos son obligatorios";
        } elseif (!preg_match('/^\d+$/', $cedula)) {
            $error_registro = "La cédula debe contener solo números";
        } elseif ($password !== $confirm_password) {
            $error_registro = "Las contraseñas no coinciden";
        } elseif (strlen($password) < 4) {
            $error_registro = "La contraseña debe tener al menos 4 caracteres";
        } else {
            // Verificar si la cédula ya existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$cedula]);
            
            if ($stmt->rowCount() > 0) {
                $error_registro = "Ya existe un médico con esta cédula";
            } else {
                // Generar iniciales del nombre
                $iniciales = '';
                $partes = explode(' ', $nombre);
                foreach ($partes as $parte) {
                    if (!empty($parte)) {
                        $iniciales .= strtoupper(substr($parte, 0, 1));
                    }
                }
                
                // Insertar nuevo doctor
                $sql = "INSERT INTO usuarios (nombre, username, password, especialidad, universidad, cedula, email, iniciales, activo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $nombre,
                    $cedula,
                    $password,
                    $especialidad,
                    $universidad,
                    $cedula,
                    $email,
                    $iniciales
                ]);
                
                $doctor_id = $conn->lastInsertId();
                
                // MENSAJE MEJORADO - MÁS CORTO Y ESTÉTICO
                $success_registro = '
                    <div style="text-align: center; padding: 10px;">
                        <div style="margin-bottom: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #8FC744 0%, #72A538 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-md" style="color: white; font-size: 20px;"></i>
                            </div>
                        </div>
                        <h4 style="color: #72A538; margin-bottom: 20px; font-weight: 600;">
                            <i class="fas fa-check-circle"></i> Médico Registrado
                        </h4>
                        <div style="background: linear-gradient(135deg, rgba(143,199,68,0.05), white); border: 1px solid #F0F8E6; border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                            <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px; align-items: center;">
                                <span style="color: #763D93; font-weight: 600;">Nombre:</span>
                                <span style="text-align: right; color: #5D3074;">'.$nombre.'</span>
                                
                                <span style="color: #2CB7BF; font-weight: 600;">Cédula:</span>
                                <span style="text-align: right; color: #24959C;">'.$cedula.'</span>
                                
                                <span style="color: #D62985; font-weight: 600;">Contraseña:</span>
                                <span style="text-align: right; color: #D62985; font-weight: bold; font-family: monospace; letter-spacing: 1px;">'.$password.'</span>
                            </div>
                        </div>
                        <div style="font-size: 13px; color: #24959C; background: #E6F7F8; padding: 8px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-lock"></i>
                            <span>Información de acceso generada</span>
                        </div>
                    </div>
                ';
            }
        }
    } catch (PDOException $e) {
        $error_registro = "Error al registrar: " . $e->getMessage();
    }
}

// Procesar login normal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['registrar_doctor'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
        $stmt->execute([$username]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && $password === $usuario['password']) {
            // Crear sesión
            $_SESSION['doctor_id'] = $usuario['id'];
            $_SESSION['doctor_nombre'] = $usuario['nombre'];
            $_SESSION['doctor_especialidad'] = $usuario['especialidad'];
            $_SESSION['doctor_universidad'] = $usuario['universidad'];
            $_SESSION['doctor_cedula'] = $usuario['cedula'];
            $_SESSION['doctor_email'] = $usuario['email'];
            $_SESSION['doctor_iniciales'] = $usuario['iniciales'];
            
            $_SESSION['login_time'] = time();
            
            // Actualizar último login
            $update_stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $update_stmt->execute([$usuario['id']]);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "❌ Credenciales incorrectas";
        }
    } catch (PDOException $e) {
        $error = "Error de conexión: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Médico DIF Benito Juárez</title>
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
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .logo-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        /* Estilo para cuando hay imagen de logo */
        .logo-image {
            width: 200px;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        /* Logo placeholder con colores DIF */
        .dif-logo-placeholder {
            width: 200px;
            height: 200px;
            background: linear-gradient(135deg, 
                var(--dif-magenta) 0%, 
                var(--dif-morado) 25%, 
                var(--dif-turquesa) 50%, 
                var(--dif-verde) 75%, 
                var(--dif-magenta) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: gradientRotate 10s linear infinite;
        }
        
        @keyframes gradientRotate {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }
        
        .dif-logo-inner {
            width: 180px;
            height: 180px;
            background: white;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
        }
        
        .dif-logo-inner .dif-text {
            font-size: 36px;
            font-weight: 900;
            background: linear-gradient(135deg, 
                var(--dif-magenta) 0%, 
                var(--dif-morado) 33%, 
                var(--dif-turquesa) 66%, 
                var(--dif-verde) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .dif-logo-inner .municipio-text {
            font-size: 14px;
            font-weight: 700;
            color: var(--dif-morado);
            line-height: 1.2;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .dif-logo-inner .admin-text {
            font-size: 10px;
            font-weight: 600;
            color: var(--dif-turquesa);
            line-height: 1.3;
        }
        
        .logo-text {
            text-align: center;
        }
        
        .logo-text h1 {
            background: linear-gradient(135deg, var(--dif-morado), var(--dif-magenta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .logo-text .system-name {
            color: var(--dif-verde);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .logo-text p {
            color: #666;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .form-container {
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.95rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dif-morado);
            font-size: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: var(--light-gray);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--dif-morado);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(118,61,147,0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--dif-morado), var(--dif-magenta));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(118,61,147,0.4);
            background: linear-gradient(135deg, var(--dif-magenta), var(--dif-morado));
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn-action {
            width: 100%;
            padding: 14px;
            border: 2px solid var(--dif-turquesa);
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-register {
            background: var(--white);
            color: var(--dif-turquesa);
        }
        
        .btn-register:hover {
            background: var(--dif-turquesa);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44,183,191,0.3);
        }
        
        .alert {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid;
            line-height: 1.5;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .alert-success {
            background: #fff !important;
            border: 2px solid #d4edda !important;
            color: #155724 !important;
            padding: 20px !important;
        }
        
        .alert-success h3, .alert-success h4 {
            margin-top: 0;
            color: #28a745;
        }
        
        .alert-success .fas {
            margin-right: 8px;
        }
        
        .footer-info {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e1e5e9;
            font-size: 0.9rem;
            color: #666;
        }
        
        .footer-info strong {
            color: var(--dif-morado);
        }
        
        .system-info {
            background: var(--dif-turquesa-light);
            border: 2px solid var(--dif-turquesa);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: var(--dif-turquesa-dark);
        }
        
        .system-info i {
            color: var(--dif-turquesa);
            margin-right: 8px;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
            }
            
            .dif-logo-placeholder {
                width: 150px;
                height: 150px;
            }
            
            .dif-logo-inner {
                width: 130px;
                height: 130px;
            }
            
            .dif-logo-inner .dif-text {
                font-size: 28px;
            }
            
            .dif-logo-inner .municipio-text {
                font-size: 11px;
            }
            
            .dif-logo-inner .admin-text {
                font-size: 8px;
            }
            
            .logo-text h1 {
                font-size: 1.5rem;
            }
            
            .logo-text .system-name {
                font-size: 1rem;
            }
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            color: var(--dif-morado);
            font-size: 1.5rem;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            padding: 5px;
        }
        
        .close-modal:hover {
            color: var(--dif-morado);
        }
        
        .password-info {
            background: var(--dif-magenta-light);
            border: 2px solid var(--dif-magenta);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--dif-magenta-dark);
        }
        
        .password-info i {
            color: var(--dif-magenta);
            margin-right: 8px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cancel {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        .btn-cancel:hover {
            background: #e9ecef;
        }
        
        /* Estilo para logos en el modal */
        .modal-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            padding: 15px;
            background: linear-gradient(135deg, var(--dif-verde-light), white);
            border-radius: 10px;
            border: 2px solid var(--dif-verde);
        }
        
        .modal-logo .dif-logo-small {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--dif-magenta), var(--dif-morado));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            border: 3px solid var(--dif-turquesa);
        }
        
        .modal-logo .dif-logo-small i {
            color: white;
            font-size: 24px;
        }
        
        .modal-logo-text h4 {
            color: var(--dif-morado);
            margin: 0;
            font-size: 1.2rem;
        }
        
        .modal-logo-text p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        
        /* Efectos especiales */
        .pulse-effect {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(118,61,147,0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(118,61,147,0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(118,61,147,0);
            }
        }
        
        /* Icono de corazón con colores DIF */
        .heart-icon {
            background: linear-gradient(135deg, 
                var(--dif-magenta) 0%, 
                var(--dif-morado) 33%, 
                var(--dif-turquesa) 66%, 
                var(--dif-verde) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: heartbeat 1.5s infinite;
        }
        
        @keyframes heartbeat {
            0% { transform: scale(1); }
            5% { transform: scale(1.1); }
            10% { transform: scale(1); }
            15% { transform: scale(1.1); }
            20% { transform: scale(1); }
        }
        
        /* Color coding para campos */
        .color-sample {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .color-magenta { background-color: var(--dif-magenta); }
        .color-morado { background-color: var(--dif-morado); }
        .color-turquesa { background-color: var(--dif-turquesa); }
        .color-verde { background-color: var(--dif-verde); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-header">
            <div class="logo-container">
                <?php
                // Verificar si existe el logo en la carpeta
                $logo_path = 'img/logoDif.png';
                $logo_exists = file_exists($logo_path);
                ?>
                
                <?php if($logo_exists): ?>
                    <!-- Mostrar logo real si existe -->
                    <img src="<?php echo $logo_path; ?>" alt="Logo DIF Benito Juárez" class="logo-image">
                <?php else: ?>
                    <!-- Mostrar logo placeholder con colores DIF -->
                    <div class="dif-logo-placeholder pulse-effect">
                        <div class="dif-logo-inner">
                            <div class="dif-text">DIF</div>
                            <div class="municipio-text">BENITO JUÁREZ</div>
                            <div class="admin-text">Administración Municipal<br>2024 - 2027</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="logo-text">
                <h1>SISTEMA MUNICIPAL DIF</h1>
                <div class="system-name">
                    <i class="fas fa-heartbeat heart-icon"></i> 
                    SISTEMA MÉDICO
                </div>
                <p>Benito Juárez, Tlaxcala</p>
            </div>
        </div>
        
        <div class="system-info">
            <i class="fas fa-info-circle"></i>
            <strong>Sistema Local</strong> - Todas las operaciones se realizan directamente en esta estación de trabajo.
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if(isset($success_registro)): ?>
            <div class="alert alert-success">
                <?php echo $success_registro; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username"><i class="fas fa-id-card"></i> Cédula Profesional</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user-md input-icon"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required 
                               placeholder="Ingrese su número de cédula"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               maxlength="20">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-key"></i> Contraseña</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Ingrese su contraseña">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </button>
            </form>
        </div>
        
        <div class="action-buttons">
            <button type="button" class="btn-action btn-register" id="registerDoctorBtn">
                <i class="fas fa-user-plus"></i>
                Registrar Nuevo Doctor
            </button>
        </div>
        
        <div class="footer-info">
            <p>© <?php echo date('Y'); ?> Sistema Municipal DIF Benito Juárez</p>
            <p><strong>Sistema Local</strong> - Versión 1.0</p>
            <p style="margin-top: 10px; font-size: 0.8rem; color: var(--dif-morado);">
                "Trabajando por el bienestar de nuestra comunidad"
            </p>
            <p style="margin-top: 5px; font-size: 0.75rem; color: #888;">
                Administración Municipal 2024-2027
            </p>
        </div>
    </div>
    
    <!-- Modal para Registrar Nuevo Doctor -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Registrar Nuevo Médico</h3>
                <button class="close-modal" id="closeRegisterModal">&times;</button>
            </div>
            
            <!-- Logo del DIF en el modal -->
            <div class="modal-logo">
                <div class="dif-logo-small">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <div class="modal-logo-text">
                    <h4>DIF Benito Juárez</h4>
                    <p>Sistema de Registro Médico</p>
                </div>
            </div>
            
            <?php if(isset($error_registro)): ?>
                <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($error_registro); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="password-info">
                <i class="fas fa-info-circle"></i>
                <strong>Para sistema local:</strong> La contraseña se guardará y mostrará en pantalla. Debe entregársela al médico.
            </div>
            
            <form method="POST" id="registerForm">
                <input type="hidden" name="registrar_doctor" value="1">
                
                <div class="form-group">
                    <label>Nombre Completo *</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Dr. Juan Pérez González" 
                           value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Cédula Profesional *</label>
                    <input type="text" id="cedula" name="cedula" required placeholder="12345678" 
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                           value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Especialidad *</label>
                    <input type="text" id="especialidad" name="especialidad" required placeholder="Medicina General"
                           value="<?php echo isset($_POST['especialidad']) ? htmlspecialchars($_POST['especialidad']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Universidad *</label>
                    <input type="text" id="universidad" name="universidad" required placeholder="Universidad Nacional"
                           value="<?php echo isset($_POST['universidad']) ? htmlspecialchars($_POST['universidad']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="doctor@ejemplo.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Contraseña *</label>
                    <input type="password" id="password_temp" name="password_temp" required placeholder="Mínimo 4 caracteres">
                </div>
                
                <div class="form-group">
                    <label>Confirmar Contraseña *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repita la contraseña">
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" id="cancelRegister">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-login">
                        <i class="fas fa-user-plus"></i>
                        Registrar Médico
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
            });
            
            // Modal handling
            const registerModal = document.getElementById('registerModal');
            const registerBtn = document.getElementById('registerDoctorBtn');
            const closeRegisterModal = document.getElementById('closeRegisterModal');
            const cancelRegister = document.getElementById('cancelRegister');
            
            // Check if we need to show modal based on PHP errors
            <?php if(isset($error_registro) || isset($success_registro)): ?>
                registerModal.style.display = 'flex';
            <?php endif; ?>
            
            // Open modal
            registerBtn.addEventListener('click', function() {
                registerModal.style.display = 'flex';
                // Clear any previous errors/success messages
                document.getElementById('registerForm').reset();
            });
            
            // Close modal
            function closeModal() {
                registerModal.style.display = 'none';
            }
            
            closeRegisterModal.addEventListener('click', closeModal);
            cancelRegister.addEventListener('click', closeModal);
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === registerModal) {
                    closeModal();
                }
            });
            
            // Form validation for registration
            const registerForm = document.getElementById('registerForm');
            registerForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password_temp').value;
                const confirmPassword = document.getElementById('password_confirm').value;
                
                if (password !== confirmPassword) {
                    alert('❌ Las contraseñas no coinciden');
                    e.preventDefault();
                    return;
                }
                
                if (password.length < 4) {
                    alert('❌ La contraseña debe tener al menos 4 caracteres');
                    e.preventDefault();
                    return;
                }
            });
            
            // Auto-focus on username field
            document.getElementById('username').focus();
            
            // Enter key to submit login form
            document.getElementById('loginForm').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.querySelector('.btn-login').click();
                }
            });
            
            // Prevent form resubmission on refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Show password strength indicator for registration
            const passwordField = document.getElementById('password_temp');
            const confirmField = document.getElementById('password_confirm');
            
            function validatePassword() {
                const password = passwordField.value;
                const confirm = confirmField.value;
                
                if (password && confirm) {
                    if (password !== confirm) {
                        confirmField.style.borderColor = '#dc3545';
                        confirmField.style.boxShadow = '0 0 0 3px rgba(220,53,69,0.1)';
                    } else {
                        confirmField.style.borderColor = '#28a745';
                        confirmField.style.boxShadow = '0 0 0 3px rgba(40,167,69,0.1)';
                    }
                }
            }
            
            passwordField.addEventListener('input', validatePassword);
            confirmField.addEventListener('input', validatePassword);
            
            // Efecto de latido para el icono del corazón
            const heartIcon = document.querySelector('.heart-icon');
            setInterval(() => {
                heartIcon.style.animation = 'none';
                setTimeout(() => {
                    heartIcon.style.animation = 'heartbeat 1.5s infinite';
                }, 10);
            }, 3000);
            
            // Efecto de gradiente rotativo para el logo placeholder
            const logoPlaceholder = document.querySelector('.dif-logo-placeholder');
            if (logoPlaceholder) {
                logoPlaceholder.style.backgroundSize = '400% 400%';
            }
        });
    </script>
</body>
</html>