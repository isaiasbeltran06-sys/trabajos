<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php?error=2');
    exit;
}

// Verificar tiempo de sesión (opcional: expiración después de 2 horas)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
    session_destroy();
    header('Location: login.php?error=3');
    exit;
}

// Actualizar tiempo de sesión
$_SESSION['login_time'] = time();

// Información del doctor
$doctor_nombre = $_SESSION['doctor_nombre'];
$doctor_especialidad = $_SESSION['doctor_especialidad'];
$doctor_universidad = $_SESSION['doctor_universidad'];
$doctor_cedula = $_SESSION['doctor_cedula'];
$doctor_email = $_SESSION['doctor_email'];
$doctor_iniciales = $_SESSION['doctor_iniciales'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Médico - DIF Benito Juárez Tlaxcala</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ====== Variables CSS con colores DIF ====== */
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

        /* ====== Estilos generales ====== */
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

        /* ====== Navbar ====== */
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

        /* Estilo para la imagen del logo */
.logo-image {
            height: 55px;
            width: auto;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
}

        
        .logo { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
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

        /* ====== Contenido ====== */
        .main-content { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            padding: 2rem; 
            max-width: 1200px; 
            margin: 0 auto;
            width: 100%;
        }
        
        .welcome-section { 
            background: var(--white); 
            border-radius: 20px; 
            padding: 2.5rem; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            margin-bottom: 2rem; 
            width: 100%; 
            border: 2px solid var(--dif-verde-light);
        }
        
        .welcome-section h2 { 
            background: linear-gradient(135deg, var(--dif-morado), var(--dif-magenta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem; 
            font-size: 2.2rem; 
            font-weight: 700; 
            text-align: center;
        }
        
        .welcome-section p { 
            color: var(--text-light); 
            font-size: 1.2rem; 
            line-height: 1.6; 
            margin-bottom: 2rem; 
            max-width: 700px; 
            margin-left: auto; 
            margin-right: auto;
            text-align: center;
        }

        .user-welcome {
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 5px 15px rgba(118,61,147,0.3);
        }

        .user-welcome i {
            font-size: 3rem;
            color: var(--dif-turquesa);
        }

        .user-welcome div h3 {
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .doctor-details {
            background: var(--dif-turquesa-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 2px solid var(--dif-turquesa);
        }

        .doctor-details h4 {
            color: var(--dif-morado);
            margin-bottom: 1rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .doctor-details h4:before {
            content: '';
            display: block;
            width: 20px;
            height: 3px;
            background: linear-gradient(90deg, var(--dif-magenta), var(--dif-turquesa));
            border-radius: 2px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border-left: 4px solid var(--dif-verde);
        }

        .detail-label {
            font-weight: 600;
            color: var(--dif-morado);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: var(--text-color);
            font-size: 1rem;
        }

        /* ====== Botones ====== */
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
        
        .btn-primary:hover,
        .btn-primary:focus { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 20px rgba(118,61,147,0.5); 
            outline: none;
        }
        
        .btn-outline { 
            background-color: transparent; 
            border: 2px solid var(--dif-verde); 
            color: var(--dif-verde-dark); 
        }
        
        .btn-outline:hover,
        .btn-outline:focus { 
            background-color: var(--dif-verde-light); 
            transform: translateY(-3px); 
            outline: none;
        }

        /* ====== Footer ====== */
        .footer { 
            background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
            color: var(--white); 
            text-align: center; 
            padding: 1.5rem; 
            margin-top: auto; 
        }
        
        /* ====== Responsive ====== */
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
            
            .welcome-section {
                padding: 2rem;
            }
            
            .welcome-section h2 {
                font-size: 1.8rem;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .user-info .name {
                display: none;
            }

            .user-welcome {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .user-welcome i {
                font-size: 2.5rem;
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
            
            .welcome-section {
                padding: 1.5rem;
            }

            .main-content {
                padding: 1rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* ====== Utilidades ====== */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .flex-center {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        /* Colores de muestra para referencia */
        .color-sample {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 2px;
            vertical-align: middle;
        }
        
        .color-magenta { background-color: var(--dif-magenta); }
        .color-morado { background-color: var(--dif-morado); }
        .color-turquesa { background-color: var(--dif-turquesa); }
        .color-verde { background-color: var(--dif-verde); }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-section, .user-welcome {
            animation: fadeIn 0.8s ease-out;
        }
        
        /* Badge para mostrar colores DIF */
        .dif-colors-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 15px;
            font-size: 0.7rem;
            color: var(--text-light);
        }
    </style>
</head>
<body>
    <!-- ====== Navbar ====== -->
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
                <span>Gestión de Pacientes 
                    <span class="dif-colors-badge">
                        <span class="color-sample color-magenta"></span>
                        <span class="color-sample color-morado"></span>
                        <span class="color-sample color-turquesa"></span>
                        <span class="color-sample color-verde"></span>
                    </span>
                </span>
            </div>
        </div>
        
        <ul class="nav-menu" role="menubar">
            <li class="nav-item" role="none">
                <a href="dashboard.php" class="nav-link active" role="menuitem">
                    <i class="fas fa-home" aria-hidden="true"></i> 
                    <span>Inicio</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="pacientes.php" class="nav-link" role="menuitem" aria-current="page">
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

    <!-- ====== Contenido principal ====== -->
    <main class="main-content" role="main">
        <div class="user-welcome">
            <i class="fas fa-user-md"></i>
            <div>
                <h3>Bienvenido/a, <?php echo $doctor_nombre; ?></h3>
                <p>Especialidad: <?php echo $doctor_especialidad; ?> | Último acceso: <?php echo date('d/m/Y H:i'); ?></p>
            </div>
        </div>

        <div class="welcome-section">
            <h2>Portal Médico DIF Benito Juárez</h2>
            <p>Accede a la gestión de pacientes, agenda, recetas, informes clínicos y laboratorio. Todo en un solo lugar para los doctores del Sistema Municipal DIF.</p>
            
            <div class="doctor-details">
                <h4>Información Profesional</h4>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Nombre completo</span>
                        <span class="detail-value"><?php echo $doctor_nombre; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Cédula profesional</span>
                        <span class="detail-value"><?php echo $doctor_cedula; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Especialidad</span>
                        <span class="detail-value"><?php echo $doctor_especialidad; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Universidad</span>
                        <span class="detail-value"><?php echo $doctor_universidad; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="flex-center" style="margin-top: 2rem;">
                <a href="pacientes.php" class="btn btn-primary">
                    <i class="fas fa-notes-medical" aria-hidden="true"></i> 
                    Nueva consulta
                </a>
                <a href="pacientes.php?accion=registrar" class="btn btn-outline">
                    <i class="fas fa-user-plus" aria-hidden="true"></i> 
                    Registrar paciente
                </a>
                <a href="pacientes.php?accion=buscar" class="btn btn-outline">
                    <i class="fas fa-search" aria-hidden="true"></i> 
                    Buscar paciente
                </a>
                
            </div>
        </div>
        
        <?php
        // Mensaje de bienvenida según la hora
        date_default_timezone_set('America/Mexico_City');
        $hora_actual = date('H:i');
        $saludo = "";
        
        if ($hora_actual < "12:00") {
            $saludo = "¡Que tenga un buen día de trabajo!";
        } elseif ($hora_actual < "19:00") {
            $saludo = "¡Que tenga una buena tarde de consultas!";
        } else {
            $saludo = "¡Que tenga una buena noche!";
        }
        
        echo "<p style='color: var(--dif-morado); margin-top: 1rem; text-align: center;'><strong>$saludo</strong></p>";
        ?>
    </main>

    <!-- ====== Footer ====== -->
    <footer class="footer" role="contentinfo">
        <p>© <?php echo date('Y'); ?> DIF municipal Benito Juárez Tlaxcala</p>
    </footer>

    <!-- ====== Script ====== -->
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
        
        // Añadir animación suave a los elementos
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            const buttons = document.querySelectorAll('.btn');
            
            // Animación hover para botones
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Mostrar colores DIF en consola para referencia
            console.log('Colores DIF:');
            console.log('- Magenta: #D62985');
            console.log('- Morado: #763D93');
            console.log('- Turquesa: #2CB7BF');
            console.log('- Verde: #8FC744');
        });
    </script>
</body>
</html>