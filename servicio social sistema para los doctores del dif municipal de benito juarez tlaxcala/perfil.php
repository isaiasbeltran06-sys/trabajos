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

// Información del doctor desde la sesión
$doctor_id = $_SESSION['doctor_id'];
$doctor_nombre = $_SESSION['doctor_nombre'];
$doctor_especialidad = $_SESSION['doctor_especialidad'];
$doctor_universidad = $_SESSION['doctor_universidad'];
$doctor_cedula = $_SESSION['doctor_cedula'];
$doctor_email = $_SESSION['doctor_email'];
$doctor_iniciales = $_SESSION['doctor_iniciales'];

// Inicializar variable
$usuario = null;

try {
    // Obtener datos actualizados de la base de datos
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado en la base de datos");
    }
    
} catch (PDOException $e) {
    $error = "Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Actualizar perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_perfil'])) {
    try {
        $nombre = trim($_POST['nombre']);
        $especialidad = trim($_POST['especialidad']);
        $universidad = trim($_POST['universidad']);
        $email = trim($_POST['email']);
        
        // Preparar la consulta SQL con los campos que sí existen
        $sql = "UPDATE usuarios SET nombre = ?, especialidad = ?, universidad = ?, email = ?";
        $params = [$nombre, $especialidad, $universidad, $email];
        
        // Verificar si la columna 'cedula' existe antes de incluirla
        $check_cedula = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'cedula'");
        if ($check_cedula->rowCount() > 0) {
            $cedula = trim($_POST['cedula'] ?? '');
            $sql .= ", cedula = ?";
            $params[] = $cedula;
        }
        
        // Verificar si la columna 'telefono' existe
        $check_telefono = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'telefono'");
        if ($check_telefono->rowCount() > 0) {
            $telefono = trim($_POST['telefono'] ?? '');
            $sql .= ", telefono = ?";
            $params[] = $telefono;
        }
        
        // Verificar si la columna 'direccion' existe
        $check_direccion = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'direccion'");
        if ($check_direccion->rowCount() > 0) {
            $direccion = trim($_POST['direccion'] ?? '');
            $sql .= ", direccion = ?";
            $params[] = $direccion;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $doctor_id;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Actualizar sesión
        $_SESSION['doctor_nombre'] = $nombre;
        $_SESSION['doctor_especialidad'] = $especialidad;
        $_SESSION['doctor_universidad'] = $universidad;
        $_SESSION['doctor_email'] = $email;
        
        // Actualizar cédula si existe
        if ($check_cedula->rowCount() > 0) {
            $_SESSION['doctor_cedula'] = $cedula;
        }
        
        // ACTUALIZAR LAS VARIABLES LOCALES TAMBIÉN
        $doctor_nombre = $nombre;
        $doctor_especialidad = $especialidad;
        $doctor_universidad = $universidad;
        $doctor_email = $email;
        if ($check_cedula->rowCount() > 0) {
            $doctor_cedula = $cedula;
        }
        
        // Recalcular iniciales con el nuevo nombre
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
        $_SESSION['doctor_iniciales'] = $doctor_iniciales;
        
        $mensaje_exito = "Perfil actualizado correctamente";
        
        // Recargar datos
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error = "Error al actualizar perfil: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Si no se pudo cargar el usuario de la BD, usar datos de sesión con valores predeterminados seguros
if (!$usuario) {
    $usuario = [
        'nombre' => $doctor_nombre,
        'especialidad' => $doctor_especialidad,
        'universidad' => $doctor_universidad,
        'email' => $doctor_email
    ];
    
    // Agregar solo los campos que puedan existir
    if (isset($_SESSION['doctor_cedula'])) {
        $usuario['cedula'] = $_SESSION['doctor_cedula'];
    }
}

// Asegurarnos de que todos los campos necesarios existan en el array $usuario
$campos_necesarios = ['nombre', 'especialidad', 'universidad', 'email', 'cedula', 'telefono', 'direccion'];
foreach ($campos_necesarios as $campo) {
    if (!isset($usuario[$campo])) {
        $usuario[$campo] = '';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - DIF Benito Juárez</title>
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--dif-turquesa);
            box-shadow: 0 0 0 3px rgba(44,183,191,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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
        }
        
        /* ====== UTILIDADES ====== */
        .required::after {
            content: " *";
            color: var(--dif-magenta);
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
                <a href="perfil.php" role="menuitem" aria-current="page">
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
                <h3><?php echo htmlspecialchars($doctor_nombre); ?></h3>
                <p><?php echo htmlspecialchars($doctor_especialidad); ?></p>
                <p><small><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($doctor_universidad); ?></small></p>
                <?php if(!empty($doctor_cedula)): ?>
                    <p><small><i class="fas fa-id-card"></i> Cédula: <?php echo htmlspecialchars($doctor_cedula); ?></small></p>
                <?php endif; ?>
            </div>
            
            <ul class="nav-vertical">
                <li><a href="perfil.php" class="active"><i class="fas fa-user"></i> Información Personal</a></li>
                <li><a href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a></li>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Volver al Inicio</a></li>
            </ul>
        </div>

        <div class="content-area">
            <div class="content-header">
                <h2><i class="fas fa-user-circle"></i> Información Personal</h2>
                <p>Actualiza tu información profesional y de contacto</p>
            </div>

            <?php if(isset($mensaje_exito)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre" class="required"><i class="fas fa-user"></i> Nombre completo</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="especialidad" class="required"><i class="fas fa-stethoscope"></i> Especialidad</label>
                        <input type="text" id="especialidad" name="especialidad" value="<?php echo htmlspecialchars($usuario['especialidad'] ?? ''); ?>" required>
                    </div>

                    <?php
                    // Verificar si la columna 'cedula' existe
                    $check_cedula = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'cedula'");
                    if ($check_cedula->rowCount() > 0):
                    ?>
                    <div class="form-group">
                        <label for="cedula" class="required"><i class="fas fa-id-card"></i> Cédula profesional</label>
                        <input type="text" id="cedula" name="cedula" value="<?php echo htmlspecialchars($usuario['cedula'] ?? ''); ?>" required>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="universidad" class="required"><i class="fas fa-graduation-cap"></i> Universidad</label>
                        <input type="text" id="universidad" name="universidad" value="<?php echo htmlspecialchars($usuario['universidad'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="required"><i class="fas fa-envelope"></i> Correo electrónico</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                    </div>

                    <?php
                    // Verificar si la columna 'telefono' existe
                    $check_telefono = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'telefono'");
                    if ($check_telefono->rowCount() > 0):
                    ?>
                    <div class="form-group">
                        <label for="telefono"><i class="fas fa-phone"></i> Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>

                    <?php
                    // Verificar si la columna 'direccion' existe
                    $check_direccion = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'direccion'");
                    if ($check_direccion->rowCount() > 0):
                    ?>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="direccion"><i class="fas fa-map-marker-alt"></i> Dirección</label>
                        <textarea id="direccion" name="direccion"><?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar perfil
                </button>
            </form>
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
    </script>
</body>
</html>