<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$paciente_id = $_GET['id'] ?? 0;

// Obtener TODOS los datos del paciente
$sql = "SELECT 
    id, nombre, edad, genero, fecha_nacimiento, direccion, localidad, municipio, estado,
    telefono, curp, escolaridad, ocupacion, religion,
    antecedentes_familiares, tabaquismo, alcoholismo, adicciones, zoonosis,
    inmunizaciones, hacinamiento, vivienda, habitos_higienico_dieteticos,
    enfermedades_infecciosas, hospitalizaciones, quirurgicos, transfusiones,
    enfermedades_cronicas, traumaticos, alergias, enfermedades_infancia,
    menarca, ciclos, ivsa, nps, g, p, a, c, fum, fpp, fup, fuc, ful, mpf,
    papanicolaou, mastografia, vph, padecimiento_actual, medicacion_actual,
    interrogatorio_nervioso, interrogatorio_digestivo, interrogatorio_respiratorio,
    interrogatorio_urinario, interrogatorio_circulatorio, interrogatorio_genital,
    interrogatorio_musculo_esqueletico, interrogatorio_piel_anexos,
    interrogatorio_organos_sentidos, peso, talla, imc, cc, ca, ta, fc, fr,
    temp, sat02, dxtx, exploracion_fisica, impresion_diagnostica, plan,
    medico_nombre, medico_cedula
    FROM pacientes WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$paciente_id]);
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    die("Paciente no encontrado");
}

// Datos del médico actual
$doctor_nombre = $_SESSION['doctor_nombre'];
$doctor_cedula = $_SESSION['doctor_cedula'];
$doctor_especialidad = $_SESSION['doctor_especialidad'];
$doctor_universidad = $_SESSION['doctor_universidad'] ?? '';

// Verificar si existe el logo
$logo_path = 'img/logoDif.png';
$logo_exists = file_exists($logo_path);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Clínico - <?php echo $paciente['nombre']; ?></title>
    <style>
        /* ESTILOS PARA IMPRESIÓN */
        @page {
            margin: 0.5cm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: white;
            color: black;
            font-size: 11pt;
            line-height: 1.3;
            padding: 0.5cm;
        }
        
        /* ENCABEZADO CON LOGO DIF */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #763D93; /* Morado DIF */
        }
        
        .logo-section {
            flex: 0 0 auto;
        }
        
        .logo-dif {
            height: 80px;
            width: auto;
            max-width: 180px;
        }
        
        .logo-placeholder {
            width: 180px;
            height: 80px;
            background: linear-gradient(135deg, #D62985 0%, #763D93 25%, #2CB7BF 50%, #8FC744 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }
        
        .header-text {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }
        
        .municipio {
            font-size: 16pt;
            font-weight: bold;
            color: #763D93; /* Morado DIF */
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .admin-periodo {
            font-size: 9pt;
            color: #666;
            margin-bottom: 5px;
        }
        
        .institucion {
            font-size: 14pt;
            font-weight: bold;
            color: #D62985; /* Magenta DIF */
            text-transform: uppercase;
            margin: 5px 0;
        }
        
        .sistema-medico {
            font-size: 12pt;
            font-weight: bold;
            color: #2CB7BF; /* Turquesa DIF */
            margin: 3px 0;
        }
        
        /* TÍTULO PRINCIPAL */
        .titulo-principal {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            border: 2px solid #763D93;
            background: linear-gradient(135deg, rgba(118,61,147,0.1) 0%, rgba(214,41,133,0.1) 100%);
            border-radius: 8px;
        }
        
        .titulo-principal h1 {
            font-size: 18pt;
            color: #D62985; /* Magenta DIF */
            margin-bottom: 5px;
        }
        
        .titulo-principal .paciente-nombre {
            font-size: 16pt;
            color: #763D93; /* Morado DIF */
            font-weight: bold;
        }
        
        /* SECCIONES CON COLORES DIF */
        .seccion {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .titulo-seccion {
            background: linear-gradient(135deg, #763D93 0%, #D62985 100%);
            color: white;
            padding: 8px 15px;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 5px solid #2CB7BF; /* Turquesa DIF */
        }
        
        /* TABLAS DE DATOS */
        .tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .tabla-datos td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            vertical-align: top;
        }
        
        .campo-label {
            width: 30%;
            background: #f8f8f8;
            font-weight: bold;
            color: #763D93; /* Morado DIF */
            border-right: 2px solid #2CB7BF; /* Turquesa DIF */
        }
        
        .campo-valor {
            width: 70%;
        }
        
        /* INFORMACIÓN BÁSICA EN GRID */
        .grid-datos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .item-dato {
            border-bottom: 2px solid #8FC744; /* Verde DIF */
            padding: 8px 0;
        }
        
        .dato-label {
            font-weight: bold;
            color: #763D93; /* Morado DIF */
            font-size: 10pt;
            margin-bottom: 3px;
        }
        
        .dato-valor {
            font-size: 11pt;
            color: #333;
        }
        
        /* PARA CAMPOS DE TEXTO LARGO */
        .campo-texto-largo {
            margin-bottom: 15px;
            padding: 12px;
            border: 1px solid #2CB7BF; /* Turquesa DIF */
            background: linear-gradient(135deg, rgba(44,183,191,0.05) 0%, rgba(143,199,68,0.05) 100%);
            border-radius: 5px;
            min-height: 80px;
        }
        
        .campo-texto-largo h4 {
            color: #D62985; /* Magenta DIF */
            margin-bottom: 8px;
            font-size: 11pt;
            border-bottom: 1px solid #D62985;
            padding-bottom: 3px;
        }
        
        /* SIGNOS VITALES CON COLORES DIF */
        .signos-vitales-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(143,199,68,0.1) 0%, rgba(44,183,191,0.1) 100%);
            border-radius: 8px;
            border: 1px solid #8FC744; /* Verde DIF */
        }
        
        .signo-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .signo-label {
            font-weight: bold;
            color: #763D93; /* Morado DIF */
            font-size: 10pt;
            margin-bottom: 5px;
        }
        
        .signo-valor {
            font-size: 12pt;
            font-weight: bold;
            color: #D62985; /* Magenta DIF */
        }
        
        /* DATOS DEL MÉDICO AL FINAL */
        .datos-medico {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 3px solid #763D93; /* Morado DIF */
            background: linear-gradient(135deg, rgba(118,61,147,0.05) 0%, rgba(214,41,133,0.05) 100%);
            border-radius: 8px;
            padding: 20px;
        }
        
        .firma-medico {
            text-align: center;
            margin-top: 30px;
        }
        
        .linea-firma {
            display: inline-block;
            width: 300px;
            border-top: 1px solid #000;
            margin-top: 20px;
        }
        
        /* PIE DE PÁGINA */
        .pie-pagina {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #2CB7BF; /* Turquesa DIF */
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        .contacto-dif {
            color: #D62985; /* Magenta DIF */
            font-weight: bold;
            margin: 5px 0;
        }
        
        /* CONTROL DE SALTO DE PÁGINA */
        .salto-pagina {
            page-break-before: always;
        }
        
        /* COLORES ESPECIALES PARA DESTACAR */
        .texto-magenta {
            color: #D62985;
        }
        
        .texto-morado {
            color: #763D93;
        }
        
        .texto-turquesa {
            color: #2CB7BF;
        }
        
        .texto-verde {
            color: #8FC744;
        }
        
        .bg-magenta-suave {
            background: rgba(214,41,133,0.1);
        }
        
        .bg-morado-suave {
            background: rgba(118,61,147,0.1);
        }
        
        .bg-turquesa-suave {
            background: rgba(44,183,191,0.1);
        }
        
        .bg-verde-suave {
            background: rgba(143,199,68,0.1);
        }
        
        /* PARA LAS SECCIONES QUE OCUPAN MUCHO ESPACIO */
        .nota-importante {
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 10px 0;
            font-size: 10pt;
            color: #333;
        }
        
        /* MEJORAS PARA LA IMPRESIÓN */
        @media print {
            .no-imprimir {
                display: none;
            }
            
            .seccion {
                page-break-inside: avoid;
            }
            
            .titulo-seccion {
                page-break-after: avoid;
            }
            
            body {
                font-size: 10pt;
                line-height: 1.2;
            }
        }
    </style>
</head>
<body>
    <!-- ENCABEZADO CON LOGO DIF -->
    <div class="header">
        <div class="logo-section">
            <?php if($logo_exists): ?>
                <img src="<?php echo $logo_path; ?>" alt="Logo DIF Benito Juárez" class="logo-dif">
            <?php else: ?>
                <div class="logo-placeholder">
                    DIF<br>BENITO JUÁREZ
                </div>
            <?php endif; ?>
        </div>
        
        <div class="header-text">
            <div class="municipio">BENITO JUÁREZ</div>
            <div class="admin-periodo">Administración Municipal 2024 - 2027</div>
            <div class="institucion">SISTEMA MUNICIPAL DIF</div>
            <div class="sistema-medico">SERVICIOS MÉDICOS</div>
        </div>
        
        <div style="flex: 0 0 auto; text-align: right; font-size: 9pt; color: #666;">
            <div style="margin-bottom: 3px;">Fecha: <?php echo date('d/m/Y'); ?></div>
            <div>Hora: <?php echo date('H:i'); ?></div>
        </div>
    </div>
    
    <!-- TÍTULO PRINCIPAL -->
    <div class="titulo-principal">
        <h1>HISTORIAL CLÍNICO COMPLETO</h1>
        <div class="paciente-nombre"><?php echo strtoupper($paciente['nombre']); ?></div>
        <div style="margin-top: 5px; font-size: 10pt; color: #666;">
            ID Paciente: <?php echo str_pad($paciente['id'], 6, '0', STR_PAD_LEFT); ?>
            | Fecha Nacimiento: <?php echo $paciente['fecha_nacimiento'] ?? 'NO REGISTRADA'; ?>
            | Edad: <?php echo $paciente['edad']; ?> años
        </div>
    </div>
    
    <!-- INFORMACIÓN PERSONAL DEL PACIENTE -->
    <div class="seccion">
        <div class="titulo-seccion">INFORMACIÓN PERSONAL</div>
        
        <div class="grid-datos">
            <div class="item-dato">
                <div class="dato-label">NOMBRE COMPLETO</div>
                <div class="dato-valor"><?php echo strtoupper($paciente['nombre']); ?></div>
            </div>
            <div class="item-dato">
                <div class="dato-label">EDAD</div>
                <div class="dato-valor"><?php echo $paciente['edad']; ?> años</div>
            </div>
            <div class="item-dato">
                <div class="dato-label">GÉNERO</div>
                <div class="dato-valor"><?php echo $paciente['genero']; ?></div>
            </div>
            <div class="item-dato">
                <div class="dato-label">FECHA DE NACIMIENTO</div>
                <div class="dato-valor"><?php echo $paciente['fecha_nacimiento'] ?? 'NO REGISTRADA'; ?></div>
            </div>
            <div class="item-dato">
                <div class="dato-label">CURP</div>
                <div class="dato-valor"><?php echo $paciente['curp'] ?? 'NO REGISTRADO'; ?></div>
            </div>
            <div class="item-dato">
                <div class="dato-label">TELÉFONO</div>
                <div class="dato-valor"><?php echo $paciente['telefono'] ?? 'NO REGISTRADO'; ?></div>
            </div>
            <div class="item-dato">
                <div class="dato-label">OCUPACIÓN</div>
                <div class="dato-valor"><?php echo $paciente['ocupacion'] ?? 'NO REGISTRADA'; ?></div>
            </div>
            <div class="item-dato">
                <div class="dato-label">ESCOLARIDAD</div>
                <div class="dato-valor"><?php echo $paciente['escolaridad'] ?? 'NO REGISTRADA'; ?></div>
            </div>
            <div class="item-dato">
                <div class="dato-label">RELIGIÓN</div>
                <div class="dato-valor"><?php echo $paciente['religion'] ?? 'NO REGISTRADA'; ?></div>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, rgba(143,199,68,0.1) 0%, rgba(44,183,191,0.1) 100%); border-radius: 5px; border-left: 4px solid #8FC744;">
            <div class="dato-label">DIRECCIÓN COMPLETA</div>
            <div class="dato-valor"><?php echo $paciente['direccion']; ?>, <?php echo $paciente['localidad']; ?>, <?php echo $paciente['municipio']; ?>, <?php echo $paciente['estado']; ?></div>
        </div>
    </div>
    
    <!-- ANTECEDENTES HEREDOFAMILIARES -->
    <?php if(!empty($paciente['antecedentes_familiares'])): ?>
    <div class="seccion">
        <div class="titulo-seccion">ANTECEDENTES HEREDOFAMILIARES</div>
        <div class="campo-texto-largo">
            <h4>Historial familiar</h4>
            <?php echo nl2br($paciente['antecedentes_familiares']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ANTECEDENTES PERSONALES PATOLÓGICOS -->
    <div class="seccion">
        <div class="titulo-seccion">ANTECEDENTES PERSONALES PATOLÓGICOS</div>
        
        <?php if(!empty($paciente['enfermedades_infecciosas'])): ?>
        <div class="campo-texto-largo">
            <h4>Enfermedades Infecciosas</h4>
            <?php echo nl2br($paciente['enfermedades_infecciosas']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($paciente['hospitalizaciones'])): ?>
        <div class="campo-texto-largo">
            <h4>Hospitalizaciones Previas</h4>
            <?php echo nl2br($paciente['hospitalizaciones']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($paciente['quirurgicos'])): ?>
        <div class="campo-texto-largo">
            <h4>Antecedentes Quirúrgicos</h4>
            <?php echo nl2br($paciente['quirurgicos']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($paciente['transfusiones'])): ?>
        <div class="campo-texto-largo">
            <h4>Transfusiones Sanguíneas</h4>
            <?php echo nl2br($paciente['transfusiones']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($paciente['traumaticos'])): ?>
        <div class="campo-texto-largo">
            <h4>Antecedentes Traumáticos</h4>
            <?php echo nl2br($paciente['traumaticos']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($paciente['enfermedades_infancia'])): ?>
        <div class="campo-texto-largo">
            <h4>Enfermedades de la Infancia</h4>
            <?php echo nl2br($paciente['enfermedades_infancia']); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- HÁBITOS Y CONDICIONES DE VIDA -->
    <div class="seccion">
        <div class="titulo-seccion">HÁBITOS Y CONDICIONES DE VIDA</div>
        
        <table class="tabla-datos">
            <?php if(!empty($paciente['tabaquismo'])): ?>
            <tr><td class="campo-label">Tabaquismo</td><td class="campo-valor"><?php echo $paciente['tabaquismo']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['alcoholismo'])): ?>
            <tr><td class="campo-label">Alcoholismo</td><td class="campo-valor"><?php echo $paciente['alcoholismo']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['adicciones'])): ?>
            <tr><td class="campo-label">Adicciones</td><td class="campo-valor"><?php echo $paciente['adicciones']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['zoonosis'])): ?>
            <tr><td class="campo-label">Contacto con Animales (Zoonosis)</td><td class="campo-valor"><?php echo $paciente['zoonosis']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['inmunizaciones'])): ?>
            <tr><td class="campo-label">Esquema de Inmunizaciones</td><td class="campo-valor"><?php echo $paciente['inmunizaciones']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['hacinamiento'])): ?>
            <tr><td class="campo-label">Hacinamiento</td><td class="campo-valor"><?php echo $paciente['hacinamiento']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['vivienda'])): ?>
            <tr><td class="campo-label">Tipo de Vivienda</td><td class="campo-valor"><?php echo $paciente['vivienda']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['habitos_higienico_dieteticos'])): ?>
            <tr><td class="campo-label">Hábitos Higiénico-Dietéticos</td><td class="campo-valor"><?php echo $paciente['habitos_higienico_dieteticos']; ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- ALERGIAS Y ENFERMEDADES CRÓNICAS -->
    <div class="seccion">
        <div class="titulo-seccion">ALERGIAS Y ENFERMEDADES CRÓNICAS</div>
        
        <table class="tabla-datos">
            <?php if(!empty($paciente['alergias'])): ?>
            <tr><td class="campo-label">Alergias</td><td class="campo-valor" style="color: #D62985; font-weight: bold;"><?php echo $paciente['alergias']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['enfermedades_cronicas'])): ?>
            <tr><td class="campo-label">Enfermedades Crónicas</td><td class="campo-valor"><?php echo $paciente['enfermedades_cronicas']; ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- ANTECEDENTES GINECO-OBSTÉTRICOS (solo si es mujer o tiene datos) -->
    <?php if($paciente['genero'] == 'Femenino' || !empty($paciente['menarca']) || !empty($paciente['g']) || !empty($paciente['p'])): ?>
    <div class="seccion">
        <div class="titulo-seccion">ANTECEDENTES GINECO-OBSTÉTRICOS</div>
        
        <table class="tabla-datos">
            <?php if(!empty($paciente['menarca'])): ?>
            <tr><td class="campo-label">Menarca</td><td class="campo-valor"><?php echo $paciente['menarca']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['ciclos'])): ?>
            <tr><td class="campo-label">Ciclos Menstruales</td><td class="campo-valor"><?php echo $paciente['ciclos']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['ivsa'])): ?>
            <tr><td class="campo-label">IVSA (Inicio Vida Sexual Activa)</td><td class="campo-valor"><?php echo $paciente['ivsa']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['nps'])): ?>
            <tr><td class="campo-label">Número de Parejas Sexuales</td><td class="campo-valor"><?php echo $paciente['nps']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['g'])): ?>
            <tr><td class="campo-label">Gestas (G)</td><td class="campo-valor"><?php echo $paciente['g']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['p'])): ?>
            <tr><td class="campo-label">Partos (P)</td><td class="campo-valor"><?php echo $paciente['p']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['a'])): ?>
            <tr><td class="campo-label">Abortos (A)</td><td class="campo-valor"><?php echo $paciente['a']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['c'])): ?>
            <tr><td class="campo-label">Cesáreas (C)</td><td class="campo-valor"><?php echo $paciente['c']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['fum'])): ?>
            <tr><td class="campo-label">FUM (Fecha Última Menstruación)</td><td class="campo-valor"><?php echo $paciente['fum']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['fpp'])): ?>
            <tr><td class="campo-label">FPP (Fecha Probable de Parto)</td><td class="campo-valor"><?php echo $paciente['fpp']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['fup'])): ?>
            <tr><td class="campo-label">FUP (Fecha Último Parto)</td><td class="campo-valor"><?php echo $paciente['fup']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['fuc'])): ?>
            <tr><td class="campo-label">FUC (Fecha Última Cesárea)</td><td class="campo-valor"><?php echo $paciente['fuc']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['ful'])): ?>
            <tr><td class="campo-label">FUL (Fecha Último Legrado)</td><td class="campo-valor"><?php echo $paciente['ful']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['mpf'])): ?>
            <tr><td class="campo-label">Método de Planificación Familiar</td><td class="campo-valor"><?php echo $paciente['mpf']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['papanicolaou'])): ?>
            <tr><td class="campo-label">Último Papanicolaou</td><td class="campo-valor"><?php echo $paciente['papanicolaou']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['mastografia'])): ?>
            <tr><td class="campo-label">Última Mastografía</td><td class="campo-valor"><?php echo $paciente['mastografia']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['vph'])): ?>
            <tr><td class="campo-label">Vacuna VPH</td><td class="campo-valor"><?php echo $paciente['vph']; ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- PADECIMIENTO ACTUAL -->
    <?php if(!empty($paciente['padecimiento_actual'])): ?>
    <div class="seccion">
        <div class="titulo-seccion">PADECIMIENTO ACTUAL</div>
        <div class="campo-texto-largo">
            <h4>Descripción del padecimiento</h4>
            <?php echo nl2br($paciente['padecimiento_actual']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- MEDICACIÓN ACTUAL -->
    <?php if(!empty($paciente['medicacion_actual'])): ?>
    <div class="seccion">
        <div class="titulo-seccion">MEDICACIÓN ACTUAL</div>
        <div class="campo-texto-largo">
            <h4>Medicamentos en uso</h4>
            <?php echo nl2br($paciente['medicacion_actual']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- INTERROGATORIO POR SISTEMAS -->
    <div class="seccion">
        <div class="titulo-seccion">INTERROGATORIO POR APARATOS Y SISTEMAS</div>
        
        <table class="tabla-datos">
            <?php if(!empty($paciente['interrogatorio_nervioso'])): ?>
            <tr><td class="campo-label">Sistema Nervioso</td><td class="campo-valor"><?php echo $paciente['interrogatorio_nervioso']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_digestivo'])): ?>
            <tr><td class="campo-label">Sistema Digestivo</td><td class="campo-valor"><?php echo $paciente['interrogatorio_digestivo']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_respiratorio'])): ?>
            <tr><td class="campo-label">Sistema Respiratorio</td><td class="campo-valor"><?php echo $paciente['interrogatorio_respiratorio']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_circulatorio'])): ?>
            <tr><td class="campo-label">Sistema Circulatorio</td><td class="campo-valor"><?php echo $paciente['interrogatorio_circulatorio']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_urinario'])): ?>
            <tr><td class="campo-label">Sistema Urinario</td><td class="campo-valor"><?php echo $paciente['interrogatorio_urinario']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_genital'])): ?>
            <tr><td class="campo-label">Sistema Genital</td><td class="campo-valor"><?php echo $paciente['interrogatorio_genital']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_musculo_esqueletico'])): ?>
            <tr><td class="campo-label">Sistema Musculoesquelético</td><td class="campo-valor"><?php echo $paciente['interrogatorio_musculo_esqueletico']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_piel_anexos'])): ?>
            <tr><td class="campo-label">Piel y Anexos</td><td class="campo-valor"><?php echo $paciente['interrogatorio_piel_anexos']; ?></td></tr>
            <?php endif; ?>
            
            <?php if(!empty($paciente['interrogatorio_organos_sentidos'])): ?>
            <tr><td class="campo-label">Órganos de los Sentidos</td><td class="campo-valor"><?php echo $paciente['interrogatorio_organos_sentidos']; ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- EXPLORACIÓN FÍSICA -->
    <div class="seccion">
        <div class="titulo-seccion">EXPLORACIÓN FÍSICA</div>
        
        <!-- SIGNOS VITALES Y DATOS ANTROPOMÉTRICOS -->
        <div class="signos-vitales-grid">
            <?php if(!empty($paciente['peso'])): ?>
            <div class="signo-item">
                <div class="signo-label">PESO</div>
                <div class="signo-valor"><?php echo $paciente['peso']; ?> kg</div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['talla'])): ?>
            <div class="signo-item">
                <div class="signo-label">TALLA</div>
                <div class="signo-valor"><?php echo $paciente['talla']; ?> cm</div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['imc'])): ?>
            <div class="signo-item">
                <div class="signo-label">IMC</div>
                <div class="signo-valor"><?php echo $paciente['imc']; ?></div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['cc'])): ?>
            <div class="signo-item">
                <div class="signo-label">CIRCUNFERENCIA CINTURA</div>
                <div class="signo-valor"><?php echo $paciente['cc']; ?> cm</div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['ca'])): ?>
            <div class="signo-item">
                <div class="signo-label">CIRCUNFERENCIA ABDOMINAL</div>
                <div class="signo-valor"><?php echo $paciente['ca']; ?> cm</div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['ta'])): ?>
            <div class="signo-item">
                <div class="signo-label">TENSIÓN ARTERIAL</div>
                <div class="signo-valor"><?php echo $paciente['ta']; ?></div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['fc'])): ?>
            <div class="signo-item">
                <div class="signo-label">FRECUENCIA CARDÍACA</div>
                <div class="signo-valor"><?php echo $paciente['fc']; ?> lpm</div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['fr'])): ?>
            <div class="signo-item">
                <div class="signo-label">FRECUENCIA RESPIRATORIA</div>
                <div class="signo-valor"><?php echo $paciente['fr']; ?> rpm</div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['temp'])): ?>
            <div class="signo-item">
                <div class="signo-label">TEMPERATURA</div>
                <div class="signo-valor"><?php echo $paciente['temp']; ?> °C</div>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($paciente['sat02'])): ?>
            <div class="signo-item">
                <div class="signo-label">SATURACIÓN O₂</div>
                <div class="signo-valor"><?php echo $paciente['sat02']; ?> %</div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if(!empty($paciente['dxtx'])): ?>
        <div class="campo-texto-largo">
            <h4>Diagnóstico o Tratamiento Previo (Dx/Tx)</h4>
            <?php echo nl2br($paciente['dxtx']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($paciente['exploracion_fisica'])): ?>
        <div class="campo-texto-largo">
            <h4>Exploración Física Detallada</h4>
            <?php echo nl2br($paciente['exploracion_fisica']); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- IMPRESIÓN DIAGNÓSTICA -->
    <?php if(!empty($paciente['impresion_diagnostica'])): ?>
    <div class="seccion">
        <div class="titulo-seccion">IMPRESIÓN DIAGNÓSTICA</div>
        <div class="campo-texto-largo" style="background: linear-gradient(135deg, rgba(118,61,147,0.1) 0%, rgba(214,41,133,0.1) 100%);">
            <h4>Diagnóstico médico</h4>
            <?php echo nl2br($paciente['impresion_diagnostica']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- PLAN DE TRATAMIENTO -->
    <?php if(!empty($paciente['plan'])): ?>
    <div class="seccion">
        <div class="titulo-seccion">PLAN DE TRATAMIENTO</div>
        <div class="campo-texto-largo" style="background: linear-gradient(135deg, rgba(143,199,68,0.1) 0%, rgba(44,183,191,0.1) 100%);">
            <h4>Tratamiento indicado</h4>
            <?php echo nl2br($paciente['plan']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- DATOS DEL MÉDICO Y FIRMA -->
    <div class="datos-medico">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="margin-bottom: 15px; font-size: 12pt; color: #763D93; font-weight: bold;">
                MÉDICO TRATANTE
            </div>
            
            <div style="margin-bottom: 10px; font-size: 14pt; color: #D62985; font-weight: bold;">
                <?php echo !empty($paciente['medico_nombre']) ? $paciente['medico_nombre'] : 'DR. ' . strtoupper($doctor_nombre); ?>
            </div>
            
            <div style="margin-bottom: 15px; font-size: 11pt; color: #333;">
                <strong>Cédula Profesional:</strong> <?php echo !empty($paciente['medico_cedula']) ? $paciente['medico_cedula'] : $doctor_cedula; ?>
            </div>
            
            <?php if(!empty($doctor_especialidad)): ?>
            <div style="margin-bottom: 10px; font-size: 11pt; color: #2CB7BF;">
                <strong>Especialidad:</strong> <?php echo $doctor_especialidad; ?>
            </div>
            <?php endif; ?>
            
            <?php if(!empty($doctor_universidad)): ?>
            <div style="margin-bottom: 20px; font-size: 10pt; color: #666;">
                <strong>Universidad:</strong> <?php echo $doctor_universidad; ?>
            </div>
            <?php endif; ?>
            
            <div class="firma-medico">
                <div class="linea-firma"></div>
                <div style="margin-top: 10px; font-size: 10pt; color: #333;">
                    Firma del médico tratante
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px dashed #ddd;">
            <div style="font-size: 9pt; color: #666;">
                Documento válido solo con sello y firma del médico
            </div>
        </div>
    </div>
    
    <!-- PIE DE PÁGINA -->
    <div class="pie-pagina">
        <div style="margin-bottom: 10px; color: #763D93; font-weight: bold; font-size: 10pt;">
            SISTEMA MUNICIPAL DIF BENITO JUÁREZ
        </div>
        
        <div class="contacto-dif">
            Av. Ejército Nacional S/N • Benito Juárez, Tlaxcala
        </div>
        
        <div style="margin: 5px 0; color: #2CB7BF;">
            Teléfono: 748 766 2331 • "Por una comunidad saludable y unida"
        </div>
        
        <div style="margin-top: 10px; font-size: 8pt; color: #999;">
            Documento generado electrónicamente el <?php echo date('d/m/Y H:i:s'); ?> • 
            Página 1 de 1 • ID Registro: <?php echo str_pad($paciente['id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
    </div>
    
    <script>
        // Imprimir automáticamente al cargar la página
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Cerrar ventana después de imprimir
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 500);
        };
    </script>
</body>
</html>