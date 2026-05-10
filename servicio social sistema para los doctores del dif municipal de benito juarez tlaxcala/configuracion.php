<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php?error=2');
    exit;
}

require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Información del doctor
$doctor_id = $_SESSION['doctor_id'];
$doctor_nombre = $_SESSION['doctor_nombre'];
$doctor_especialidad = $_SESSION['doctor_especialidad'];
$doctor_cedula = $_SESSION['doctor_cedula'];
$doctor_iniciales = $_SESSION['doctor_iniciales'];

// Cambiar contraseña
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_password'])) {
    try {
        $password_actual = $_POST['password_actual'];
        $nuevo_password = $_POST['nuevo_password'];
        $confirmar_password = $_POST['confirmar_password'];
        
        // Verificar contraseña actual
        $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($password_actual !== $usuario['password']) { // Comparación directa
            $error_password = "La contraseña actual es incorrecta";
        } elseif ($nuevo_password !== $confirmar_password) {
            $error_password = "Las nuevas contraseñas no coinciden";
        } elseif (strlen($nuevo_password) < 4) {
            $error_password = "La nueva contraseña debe tener al menos 4 caracteres";
        } else {
            // Actualizar contraseña
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$nuevo_password, $doctor_id]);
            
            $mensaje_exito_password = "Contraseña actualizada correctamente";
        }
        
    } catch (PDOException $e) {
        $error_password = "Error al cambiar contraseña: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - DIF Benito Juárez</title>
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
        
        .logo-icon { 
            width: 55px; 
            height: 55px; 
            background: var(--white); 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
        }
        
        .logo-icon i { 
            font-size: 28px; 
            background: linear-gradient(135deg, var(--dif-morado), var(--dif-magenta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
        
        .nav-link:hover { 
            background: rgba(255,255,255,0.15); 
            color: var(--white); 
            transform: translateY(-2px); 
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
        
        .user-dropdown a:hover { 
            background: var(--dif-turquesa-light); 
            color: var(--dif-morado); 
        }

         /* Estilos para el badge de colores DIF */
        .dif-colors-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-left: 8px;
        }

        .color-sample-mini {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* ====== CONTENIDO PRINCIPAL ====== */
        .main-content { 
            flex: 1; 
            display: flex; 
            padding: 2rem; 
            max-width: 1200px; 
            margin: 0 auto;
            width: 100%;
            gap: 2rem;
        }

        .sidebar {
            width: 300px;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            height: fit-content;
            border: 2px solid var(--dif-magenta-light);
        }

        .profile-card {
            text-align: center;
            padding: 2rem 1rem;
            border-bottom: 2px solid var(--dif-turquesa-light);
            margin-bottom: 1rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 25px rgba(118,61,147,0.3);
            border: 4px solid var(--dif-turquesa);
        }

        .profile-card h3 {
            color: var(--dif-morado);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .profile-card p {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .profile-card small {
            color: var(--dif-turquesa);
            font-weight: 600;
        }

        .nav-vertical {
            list-style: none;
            margin-top: 2rem;
        }

        .nav-vertical li {
            margin-bottom: 0.5rem;
        }

        .nav-vertical a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: var(--text-color);
            border-radius: var(--border-radius);
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-vertical a:hover,
        .nav-vertical a.active {
            background: var(--dif-turquesa-light);
            color: var(--dif-morado);
            border-left: 3px solid var(--dif-magenta);
        }

        .nav-vertical a i {
            width: 20px;
            text-align: center;
            color: var(--dif-morado);
        }

        .content-area {
            flex: 1;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2.5rem;
            border: 2px solid var(--dif-magenta-light);
        }

        .content-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--dif-turquesa-light);
        }

        .content-header h2 {
            color: var(--dif-morado);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-header h2 i {
            color: var(--dif-magenta);
        }

        .content-header p {
            color: var(--text-light);
        }

        .config-section {
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--dif-turquesa-light);
        }

        .config-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .config-section h3 {
            color: var(--dif-morado);
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .config-section h3 i {
            color: var(--dif-magenta);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dif-morado);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--dif-turquesa);
            width: 16px;
        }

        .form-group input {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--dif-turquesa);
            box-shadow: 0 0 0 3px rgba(44,183,191,0.1);
        }

        /* ====== BOTONES DIF ====== */
        .btn {
            padding: 14px 28px; 
            border-radius: var(--border-radius); 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s; 
            border: none; 
            font-size: 16px; 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            text-decoration: none; 
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: white; 
            box-shadow: 0 4px 15px rgba(118,61,147,0.4); 
        }
        
        .btn-primary:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 20px rgba(118,61,147,0.5); 
            background: linear-gradient(135deg, var(--dif-morado-dark) 0%, var(--dif-magenta-dark) 100%);
        }

        /* ====== MENSAJES DIF ====== */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
            color: #155724;
            border-left: 4px solid var(--dif-verde);
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .info-box {
            background: linear-gradient(135deg, var(--dif-turquesa-light) 0%, var(--dif-verde-light) 100%);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--dif-morado);
            margin-top: 1.5rem;
        }

        .info-box h4 {
            color: var(--dif-morado);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-box ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--text-color);
        }

        .info-box li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        /* ====== FOOTER DIF ====== */
        .footer { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: var(--white); 
            text-align: center; 
            padding: 1.5rem; 
            margin-top: auto; 
        }

        /* ====== ANIMACIONES ====== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sidebar, .content-area {
            animation: fadeIn 0.8s ease-out;
        }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
                padding: 1rem;
            }
            
            .sidebar {
                width: 100%;
            }
            
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
            
            .nav-link span {
                display: none;
            }
            
            .user-info .name {
                display: none;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .logo-text span {
                display: none;
            }
            
            .nav-link {
                padding: 10px;
            }
            
            .content-area {
                padding: 1.5rem;
            }
            
            .config-section {
                padding-bottom: 1.5rem;
                margin-bottom: 2rem;
            }
        }
        
        /* ====== UTILIDADES ====== */
        .required::after {
            content: " *";
            color: var(--dif-magenta);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .password-strength.strong {
            color: var(--dif-verde);
        }
        
        .password-strength.weak {
            color: #dc3545;
        }
         /* Estilo para el link activo en el dropdown */
        .user-dropdown a[aria-current="page"] {
            background: var(--dif-turquesa-light);
            color: var(--dif-morado);
            font-weight: 600;
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
                <div class="logo-icon" aria-hidden="true">
                    <i class="fas fa-hands-helping"></i>
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
                <a href="informes.php" class="nav-link" role="menuitem">
                    <i class="fas fa-file-medical-alt" aria-hidden="true"></i> 
                    <span>Informes</span>
                </a>
            </li>
        </ul>
        
        <div class="user-profile" id="userProfile" tabindex="0" aria-haspopup="true" aria-expanded="false">
    <div class="user-avatar" aria-label="Iniciales del doctor">
        <?php echo $doctor_iniciales; ?>
    </div>
    <div class="user-info">
        <div class="name"><?php echo htmlspecialchars((string) $doctor_nombre); ?></div>
        <div class="role"><?php echo htmlspecialchars((string) $doctor_especialidad); ?></div>
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
        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar"><?php echo $doctor_iniciales; ?></div>
                <h3><?php echo $doctor_nombre; ?></h3>
                <p><?php echo $doctor_especialidad; ?></p>
                <p><small><i class="fas fa-id-card"></i> Cédula: <?php echo $doctor_cedula; ?></small></p>
            </div>
            
            <ul class="nav-vertical">
                <li><a href="perfil.php"><i class="fas fa-user"></i> Información Personal</a></li>
                <li><a href="configuracion.php" class="active"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Volver al Dashboard</a></li>
            </ul>
        </div>

        <div class="content-area">
            <div class="content-header">
                <h2><i class="fas fa-cog"></i> Configuración</h2>
                <p>Gestiona la seguridad de tu cuenta</p>
            </div>

            <!-- Sección de Seguridad -->
            <div class="config-section">
                <h3><i class="fas fa-shield-alt"></i> Seguridad</h3>

                <?php if(isset($mensaje_exito_password)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $mensaje_exito_password; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($error_password)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_password; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password_actual" class="required"><i class="fas fa-key"></i> Contraseña actual</label>
                            <input type="password" id="password_actual" name="password_actual" required>
                        </div>

                        <div class="form-group">
                            <label for="nuevo_password" class="required"><i class="fas fa-lock"></i> Nueva contraseña</label>
                            <input type="password" id="nuevo_password" name="nuevo_password" required minlength="4">
                            <div class="password-strength">Mínimo 4 caracteres</div>
                        </div>

                        <div class="form-group">
                            <label for="confirmar_password" class="required"><i class="fas fa-lock"></i> Confirmar nueva contraseña</label>
                            <input type="password" id="confirmar_password" name="confirmar_password" required minlength="4">
                        </div>
                    </div>

                    <button type="submit" name="cambiar_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Cambiar contraseña
                    </button>
                </form>
            </div>

            <!-- Información de Seguridad -->
            <div class="config-section">
                <h3><i class="fas fa-info-circle"></i> Información de Seguridad</h3>
                
                <div class="info-box">
                    <h4><i class="fas fa-shield-alt"></i> Recomendaciones de seguridad</h4>
                    
                    <ul>
                        <li>Usa una contraseña que no hayas usado en otros servicios</li>
                        <li>La contraseña debe tener al menos 6 caracteres</li>
                        <li>No compartas tu contraseña con nadie</li>
                        <li>Cierra sesión cuando termines de usar el sistema</li>
                        <li>Evita usar información personal en tu contraseña</li>
                        <li>Actualiza tu contraseña periódicamente</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <!-- ====== FOOTER ====== */
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

        // Validación de contraseñas
        const form = document.querySelector('form[name="cambiar_password"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const nuevoPassword = document.getElementById('nuevo_password').value;
                const confirmarPassword = document.getElementById('confirmar_password').value;
                
                if (nuevoPassword !== confirmarPassword) {
                    e.preventDefault();
                    alert('❌ Las contraseñas no coinciden. Por favor, verifica que ambas contraseñas sean iguales.');
                    return false;
                }
                
                if (nuevoPassword.length < 4) {
                    e.preventDefault();
                    alert('❌ La contraseña debe tener al menos 4 caracteres.');
                    return false;
                }
                
                return true;
            });
        }
        
        // Mostrar fortaleza de la contraseña
        const nuevoPasswordInput = document.getElementById('nuevo_password');
        if (nuevoPasswordInput) {
            nuevoPasswordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthIndicator = this.nextElementSibling;
                
                if (password.length >= 6) {
                    strengthIndicator.textContent = 'Contraseña fuerte ✓';
                    strengthIndicator.className = 'password-strength strong';
                } else if (password.length >= 4) {
                    strengthIndicator.textContent = 'Contraseña aceptable';
                    strengthIndicator.className = 'password-strength';
                } else {
                    strengthIndicator.textContent = 'Mínimo 4 caracteres';
                    strengthIndicator.className = 'password-strength weak';
                }
            });
        }
    </script>
</body>
</html>