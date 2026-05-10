<?php
session_start();
// Verificar sesión
if (!isset($_SESSION['doctor_id'])) {
    header('Location: login.php?error=2');
    exit;
}

require_once 'config/database.php';

// Crear la conexión
$database = new Database();
$conn = $database->getConnection();

// Información del doctor para la navbar
$doctor_nombre = $_SESSION['doctor_nombre'];
$doctor_especialidad = $_SESSION['doctor_especialidad'];
$doctor_iniciales = $_SESSION['doctor_iniciales'];
$doctor_cedula = $_SESSION['doctor_cedula'];
$doctor_universidad = $_SESSION['doctor_universidad'] ?? '';

$accion = $_GET['accion'] ?? 'listar';

// Guardar paciente nuevo
if($accion == 'guardar' && $_SERVER['REQUEST_METHOD'] == 'POST'){
    try {
        $sql = "INSERT INTO pacientes (
            nombre, edad, genero, fecha_nacimiento, direccion, localidad, municipio, estado, 
            telefono, curp, escolaridad, ocupacion, religion, antecedentes_familiares,
            tabaquismo, alcoholismo, adicciones, zoonosis, inmunizaciones, hacinamiento,
            vivienda, habitos_higienico_dieteticos, enfermedades_infecciosas, hospitalizaciones,
            quirurgicos, transfusiones, enfermedades_cronicas, traumaticos, alergias,
            enfermedades_infancia, menarca, ciclos, ivsa, nps, g, p, a, c, fum, fpp,
            fup, fuc, ful, mpf, papanicolaou, mastografia, vph, padecimiento_actual,
            medicacion_actual, interrogatorio_nervioso, interrogatorio_digestivo,
            interrogatorio_respiratorio, interrogatorio_urinario, interrogatorio_circulatorio,
            interrogatorio_genital, interrogatorio_musculo_esqueletico, interrogatorio_piel_anexos,
            interrogatorio_organos_sentidos, peso, talla, imc, cc, ca, ta, fc, fr, temp,
            sat02, dxtx, exploracion_fisica, impresion_diagnostica, plan, medico_nombre, medico_cedula
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            $_POST['nombre'] ?? '',
            $_POST['edad'] ?? '',
            $_POST['genero'] ?? '',
            $_POST['fecha_nacimiento'] ?? '',
            $_POST['direccion'] ?? '',
            $_POST['localidad'] ?? '',
            $_POST['municipio'] ?? '',
            $_POST['estado'] ?? '',
            $_POST['telefono'] ?? '',
            
            !empty($_POST['curp']) ? strtoupper($_POST['curp']) : null,
            $_POST['escolaridad'] ?? '',
            $_POST['ocupacion'] ?? '',
            $_POST['religion'] ?? '',
            $_POST['antecedentes_familiares'] ?? '',
            $_POST['tabaquismo'] ?? '',
            $_POST['alcoholismo'] ?? '',
            $_POST['adicciones'] ?? '',
            $_POST['zoonosis'] ?? '',
            $_POST['inmunizaciones'] ?? '',
            $_POST['hacinamiento'] ?? '',
            $_POST['vivienda'] ?? '',
            $_POST['habitos_higienico_dieteticos'] ?? '',
            $_POST['enfermedades_infecciosas'] ?? '',
            $_POST['hospitalizaciones'] ?? '',
            $_POST['quirurgicos'] ?? '',
            $_POST['transfusiones'] ?? '',
            $_POST['enfermedades_cronicas'] ?? '',
            $_POST['traumaticos'] ?? '',
            $_POST['alergias'] ?? '',
            $_POST['enfermedades_infancia'] ?? '',
            $_POST['menarca'] ?? '',
            $_POST['ciclos'] ?? '',
            $_POST['ivsa'] ?? '',
            $_POST['nps'] ?? '',
            $_POST['g'] ?? '',
            $_POST['p'] ?? '',
            $_POST['a'] ?? '',
            $_POST['c'] ?? '',
            $_POST['fum'] ?? '',
            $_POST['fpp'] ?? '',
            $_POST['fup'] ?? '',
            $_POST['fuc'] ?? '',
            $_POST['ful'] ?? '',
            $_POST['mpf'] ?? '',
            $_POST['papanicolaou'] ?? '',
            $_POST['mastografia'] ?? '',
            $_POST['vph'] ?? '',
            $_POST['padecimiento_actual'] ?? '',
            $_POST['medicacion_actual'] ?? '',
            $_POST['interrogatorio_nervioso'] ?? '',
            $_POST['interrogatorio_digestivo'] ?? '',
            $_POST['interrogatorio_respiratorio'] ?? '',
            $_POST['interrogatorio_urinario'] ?? '',
            $_POST['interrogatorio_circulatorio'] ?? '',
            $_POST['interrogatorio_genital'] ?? '',
            $_POST['interrogatorio_musculo_esqueletico'] ?? '',
            $_POST['interrogatorio_piel_anexos'] ?? '',
            $_POST['interrogatorio_organos_sentidos'] ?? '',
            $_POST['peso'] ?? '',
            $_POST['talla'] ?? '',
            $_POST['imc'] ?? '',
            $_POST['cc'] ?? '',
            $_POST['ca'] ?? '',
            $_POST['ta'] ?? '',
            $_POST['fc'] ?? '',
            $_POST['fr'] ?? '',
            $_POST['temp'] ?? '',
            $_POST['sat02'] ?? '',
            $_POST['dxtx'] ?? '',
            $_POST['exploracion_fisica'] ?? '',
            $_POST['impresion_diagnostica'] ?? '',
            $_POST['plan'] ?? '',
            
            // AQUÍ ESTÁ EL CAMBIO IMPORTANTE
            'DR. ' . strtoupper($_SESSION['doctor_nombre']),  // medico_nombre
            $_SESSION['doctor_cedula']  // medico_cedula
        ];
        
        $stmt->execute($params);
        
        header("Location: pacientes.php");
        exit;
        
    } catch (PDOException $e) {
        $error = "Error al guardar: " . $e->getMessage();
    }
}

// Actualizar paciente existente
if($accion == 'actualizar' && $_SERVER['REQUEST_METHOD'] == 'POST'){
    try {
        $sql = "UPDATE pacientes SET 
            nombre=?, edad=?, genero=?, fecha_nacimiento=?, direccion=?, localidad=?, municipio=?, estado=?, 
            telefono=?, curp=?, escolaridad=?, ocupacion=?, religion=?, antecedentes_familiares=?,
            tabaquismo=?, alcoholismo=?, adicciones=?, zoonosis=?, inmunizaciones=?, hacinamiento=?,
            vivienda=?, habitos_higienico_dieteticos=?, enfermedades_infecciosas=?, hospitalizaciones=?,
            quirurgicos=?, transfusiones=?, enfermedades_cronicas=?, traumaticos=?, alergias=?,
            enfermedades_infancia=?, menarca=?, ciclos=?, ivsa=?, nps=?, g=?, p=?, a=?, c=?, fum=?, fpp=?,
            fup=?, fuc=?, ful=?, mpf=?, papanicolaou=?, mastografia=?, vph=?, padecimiento_actual=?,
            medicacion_actual=?, interrogatorio_nervioso=?, interrogatorio_digestivo=?,
            interrogatorio_respiratorio=?, interrogatorio_urinario=?, interrogatorio_circulatorio=?,
            interrogatorio_genital=?, interrogatorio_musculo_esqueletico=?, interrogatorio_piel_anexos=?,
            interrogatorio_organos_sentidos=?, peso=?, talla=?, imc=?, cc=?, ca=?, ta=?, fc=?, fr=?, temp=?,
            sat02=?, dxtx=?, exploracion_fisica=?, impresion_diagnostica=?, plan=?, medico_nombre=?, medico_cedula=?
            WHERE id=?";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            $_POST['nombre'] ?? '',
            $_POST['edad'] ?? '',
            $_POST['genero'] ?? '',
            $_POST['fecha_nacimiento'] ?? '',
            $_POST['direccion'] ?? '',
            $_POST['localidad'] ?? '',
            $_POST['municipio'] ?? '',
            $_POST['estado'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['curp'] ?? '',
            $_POST['escolaridad'] ?? '',
            $_POST['ocupacion'] ?? '',
            $_POST['religion'] ?? '',
            $_POST['antecedentes_familiares'] ?? '',
            $_POST['tabaquismo'] ?? '',
            $_POST['alcoholismo'] ?? '',
            $_POST['adicciones'] ?? '',
            $_POST['zoonosis'] ?? '',
            $_POST['inmunizaciones'] ?? '',
            $_POST['hacinamiento'] ?? '',
            $_POST['vivienda'] ?? '',
            $_POST['habitos_higienico_dieteticos'] ?? '',
            $_POST['enfermedades_infecciosas'] ?? '',
            $_POST['hospitalizaciones'] ?? '',
            $_POST['quirurgicos'] ?? '',
            $_POST['transfusiones'] ?? '',
            $_POST['enfermedades_cronicas'] ?? '',
            $_POST['traumaticos'] ?? '',
            $_POST['alergias'] ?? '',
            $_POST['enfermedades_infancia'] ?? '',
            $_POST['menarca'] ?? '',
            $_POST['ciclos'] ?? '',
            $_POST['ivsa'] ?? '',
            $_POST['nps'] ?? '',
            $_POST['g'] ?? '',
            $_POST['p'] ?? '',
            $_POST['a'] ?? '',
            $_POST['c'] ?? '',
            $_POST['fum'] ?? '',
            $_POST['fpp'] ?? '',
            $_POST['fup'] ?? '',
            $_POST['fuc'] ?? '',
            $_POST['ful'] ?? '',
            $_POST['mpf'] ?? '',
            $_POST['papanicolaou'] ?? '',
            $_POST['mastografia'] ?? '',
            $_POST['vph'] ?? '',
            $_POST['padecimiento_actual'] ?? '',
            $_POST['medicacion_actual'] ?? '',
            $_POST['interrogatorio_nervioso'] ?? '',
            $_POST['interrogatorio_digestivo'] ?? '',
            $_POST['interrogatorio_respiratorio'] ?? '',
            $_POST['interrogatorio_urinario'] ?? '',
            $_POST['interrogatorio_circulatorio'] ?? '',
            $_POST['interrogatorio_genital'] ?? '',
            $_POST['interrogatorio_musculo_esqueletico'] ?? '',
            $_POST['interrogatorio_piel_anexos'] ?? '',
            $_POST['interrogatorio_organos_sentidos'] ?? '',
            $_POST['peso'] ?? '',
            $_POST['talla'] ?? '',
            $_POST['imc'] ?? '',
            $_POST['cc'] ?? '',
            $_POST['ca'] ?? '',
            $_POST['ta'] ?? '',
            $_POST['fc'] ?? '',
            $_POST['fr'] ?? '',
            $_POST['temp'] ?? '',
            $_POST['sat02'] ?? '',
            $_POST['dxtx'] ?? '',
            $_POST['exploracion_fisica'] ?? '',
            $_POST['impresion_diagnostica'] ?? '',
            $_POST['plan'] ?? '',
            
            // AQUÍ ESTÁ EL CAMBIO IMPORTANTE
            'DR. ' . strtoupper($_SESSION['doctor_nombre']),  // medico_nombre
            $_SESSION['doctor_cedula'],  // medico_cedula
            
            // El ID siempre va al final
            $_POST['id'] ?? ''
        ];
        
        $stmt->execute($params);
        
        header("Location: pacientes.php");
        exit;
        
    } catch (PDOException $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// Borrar paciente
if($accion == 'borrar' && isset($_GET['id'])){
    try {
        $stmt = $conn->prepare("DELETE FROM pacientes WHERE id=?");
        $stmt->execute([$_GET['id']]);
        header("Location: pacientes.php");
        exit;
    } catch (PDOException $e) {
        $error = "Error al borrar: " . $e->getMessage();
    }
}

// Generar Excel - VERSIÓN COMPLETA CON TODOS LOS CAMPOS
if($accion == 'excel' && isset($_GET['id'])){
    // Obtener TODOS los campos del paciente
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
        FROM pacientes WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($paciente){
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="historia_clinica_' . $paciente['nombre'] . '_' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Historia Clínica - ' . $paciente['nombre'] . '</title></head><body>';
        echo '<table border="1" style="width: 100%; border-collapse: collapse;">';
        
        // ENCABEZADO
        echo '<tr>';
        echo '<th colspan="4" style="background-color: #763D93; color: white; font-size: 18px; padding: 15px; text-align: center;">';
        echo 'HISTORIA CLÍNICA COMPLETA<br>';
        echo '<span style="font-size: 14px;">' . $paciente['nombre'] . ' | ID: ' . $paciente['id'] . '</span>';
        echo '</th>';
        echo '</tr>';
        
        // FUNCIÓN PARA AGREGAR FILAS
        function agregarFilaExcel($titulo, $valor, $color = '#f2f2f2') {
            if(!empty($valor)) {
                echo '<tr>';
                echo '<td style="background-color: ' . $color . '; font-weight: bold; padding: 8px; width: 25%;">' . htmlspecialchars($titulo) . '</td>';
                echo '<td colspan="3" style="padding: 8px;">' . nl2br(htmlspecialchars($valor)) . '</td>';
                echo '</tr>';
            }
        }
        
        // FICHA DE IDENTIFICACIÓN
        echo '<tr><td colspan="4" style="background-color: #D62985; color: white; font-weight: bold; padding: 10px; text-align: center;">FICHA DE IDENTIFICACIÓN</td></tr>';
        agregarFilaExcel('Nombre completo', $paciente['nombre'], '#f8e0ef');
        agregarFilaExcel('Edad', $paciente['edad'] . ' años', '#f8e0ef');
        agregarFilaExcel('Género', $paciente['genero'], '#f8e0ef');
        agregarFilaExcel('Fecha de nacimiento', $paciente['fecha_nacimiento'], '#f8e0ef');
        agregarFilaExcel('Dirección', $paciente['direccion'], '#f8e0ef');
        agregarFilaExcel('Localidad', $paciente['localidad'], '#f8e0ef');
        agregarFilaExcel('Municipio', $paciente['municipio'], '#f8e0ef');
        agregarFilaExcel('Estado', $paciente['estado'], '#f8e0ef');
        agregarFilaExcel('Teléfono', $paciente['telefono'], '#f8e0ef');
        agregarFilaExcel('CURP', $paciente['curp'], '#f8e0ef');
        agregarFilaExcel('Escolaridad', $paciente['escolaridad'], '#f8e0ef');
        agregarFilaExcel('Ocupación', $paciente['ocupacion'], '#f8e0ef');
        agregarFilaExcel('Religión', $paciente['religion'], '#f8e0ef');
        
        // ANTECEDENTES HEREDOFAMILIARES
        if(!empty($paciente['antecedentes_familiares'])) {
            echo '<tr><td colspan="4" style="background-color: #2CB7BF; color: white; font-weight: bold; padding: 10px; text-align: center;">ANTECEDENTES HEREDOFAMILIARES</td></tr>';
            agregarFilaExcel('Antecedentes familiares', $paciente['antecedentes_familiares'], '#e6f7f8');
        }
        
        // ANTECEDENTES PERSONALES NO PATOLÓGICOS
        echo '<tr><td colspan="4" style="background-color: #8FC744; color: white; font-weight: bold; padding: 10px; text-align: center;">ANTECEDENTES PERSONALES NO PATOLÓGICOS</td></tr>';
        agregarFilaExcel('Tabaquismo', $paciente['tabaquismo'], '#f0f8e6');
        agregarFilaExcel('Alcoholismo', $paciente['alcoholismo'], '#f0f8e6');
        agregarFilaExcel('Adicciones', $paciente['adicciones'], '#f0f8e6');
        agregarFilaExcel('Zoonosis', $paciente['zoonosis'], '#f0f8e6');
        agregarFilaExcel('Inmunizaciones', $paciente['inmunizaciones'], '#f0f8e6');
        agregarFilaExcel('Hacinamiento', $paciente['hacinamiento'], '#f0f8e6');
        agregarFilaExcel('Vivienda', $paciente['vivienda'], '#f0f8e6');
        agregarFilaExcel('Hábitos higiénico-dietéticos', $paciente['habitos_higienico_dieteticos'], '#f0f8e6');
        
        // ANTECEDENTES PERSONALES PATOLÓGICOS
        echo '<tr><td colspan="4" style="background-color: #FF6B6B; color: white; font-weight: bold; padding: 10px; text-align: center;">ANTECEDENTES PERSONALES PATOLÓGICOS</td></tr>';
        agregarFilaExcel('Enfermedades infecciosas', $paciente['enfermedades_infecciosas'], '#ffebee');
        agregarFilaExcel('Hospitalizaciones', $paciente['hospitalizaciones'], '#ffebee');
        agregarFilaExcel('Antecedentes quirúrgicos', $paciente['quirurgicos'], '#ffebee');
        agregarFilaExcel('Transfusiones', $paciente['transfusiones'], '#ffebee');
        agregarFilaExcel('Enfermedades crónicas', $paciente['enfermedades_cronicas'], '#ffebee');
        agregarFilaExcel('Traumáticos', $paciente['traumaticos'], '#ffebee');
        agregarFilaExcel('Alergias', $paciente['alergias'], '#ffebee');
        agregarFilaExcel('Enfermedades de la infancia', $paciente['enfermedades_infancia'], '#ffebee');
        
        // ANTECEDENTES GINECO-OBSTÉTRICOS (si aplica)
        if($paciente['genero'] == 'Femenino' || !empty($paciente['menarca']) || !empty($paciente['g'])) {
            echo '<tr><td colspan="4" style="background-color: #9C27B0; color: white; font-weight: bold; padding: 10px; text-align: center;">ANTECEDENTES GINECO-OBSTÉTRICOS</td></tr>';
            agregarFilaExcel('Menarca', $paciente['menarca'], '#f3e5f5');
            agregarFilaExcel('Ciclos menstruales', $paciente['ciclos'], '#f3e5f5');
            agregarFilaExcel('IVSA', $paciente['ivsa'], '#f3e5f5');
            agregarFilaExcel('NPS', $paciente['nps'], '#f3e5f5');
            agregarFilaExcel('Gestas (G)', $paciente['g'], '#f3e5f5');
            agregarFilaExcel('Partos (P)', $paciente['p'], '#f3e5f5');
            agregarFilaExcel('Abortos (A)', $paciente['a'], '#f3e5f5');
            agregarFilaExcel('Cesáreas (C)', $paciente['c'], '#f3e5f5');
            agregarFilaExcel('FUM', $paciente['fum'], '#f3e5f5');
            agregarFilaExcel('FPP', $paciente['fpp'], '#f3e5f5');
            agregarFilaExcel('FUP', $paciente['fup'], '#f3e5f5');
            agregarFilaExcel('FUC', $paciente['fuc'], '#f3e5f5');
            agregarFilaExcel('FUL', $paciente['ful'], '#f3e5f5');
            agregarFilaExcel('MPF', $paciente['mpf'], '#f3e5f5');
            agregarFilaExcel('Papanicolaou', $paciente['papanicolaou'], '#f3e5f5');
            agregarFilaExcel('Mastografía', $paciente['mastografia'], '#f3e5f5');
            agregarFilaExcel('VPH', $paciente['vph'], '#f3e5f5');
        }
        
        // PADECIMIENTO ACTUAL
        if(!empty($paciente['padecimiento_actual'])) {
            echo '<tr><td colspan="4" style="background-color: #FF9800; color: white; font-weight: bold; padding: 10px; text-align: center;">PADECIMIENTO ACTUAL</td></tr>';
            agregarFilaExcel('Padecimiento actual', $paciente['padecimiento_actual'], '#fff3e0');
        }
        
        // MEDICACIÓN ACTUAL
        if(!empty($paciente['medicacion_actual'])) {
            echo '<tr><td colspan="4" style="background-color: #4CAF50; color: white; font-weight: bold; padding: 10px; text-align: center;">MEDICACIÓN ACTUAL</td></tr>';
            agregarFilaExcel('Medicación actual', $paciente['medicacion_actual'], '#e8f5e9');
        }
        
        // INTERROGATORIO POR SISTEMAS
        echo '<tr><td colspan="4" style="background-color: #2196F3; color: white; font-weight: bold; padding: 10px; text-align: center;">INTERROGATORIO POR APARATOS Y SISTEMAS</td></tr>';
        agregarFilaExcel('Sistema Nervioso', $paciente['interrogatorio_nervioso'], '#e3f2fd');
        agregarFilaExcel('Sistema Digestivo', $paciente['interrogatorio_digestivo'], '#e3f2fd');
        agregarFilaExcel('Sistema Respiratorio', $paciente['interrogatorio_respiratorio'], '#e3f2fd');
        agregarFilaExcel('Sistema Urinario', $paciente['interrogatorio_urinario'], '#e3f2fd');
        agregarFilaExcel('Sistema Circulatorio', $paciente['interrogatorio_circulatorio'], '#e3f2fd');
        agregarFilaExcel('Sistema Genital', $paciente['interrogatorio_genital'], '#e3f2fd');
        agregarFilaExcel('Sistema Musculoesquelético', $paciente['interrogatorio_musculo_esqueletico'], '#e3f2fd');
        agregarFilaExcel('Piel y Anexos', $paciente['interrogatorio_piel_anexos'], '#e3f2fd');
        agregarFilaExcel('Órganos de los Sentidos', $paciente['interrogatorio_organos_sentidos'], '#e3f2fd');
        
        // EXPLORACIÓN FÍSICA
        echo '<tr><td colspan="4" style="background-color: #673AB7; color: white; font-weight: bold; padding: 10px; text-align: center;">EXPLORACIÓN FÍSICA</td></tr>';
        
        // Somatometría y Signos Vitales
        echo '<tr><td colspan="4" style="background-color: #9575CD; color: white; font-weight: bold; padding: 8px;">SOMATOMETRÍA Y SIGNOS VITALES</td></tr>';
        
        // Grid para signos vitales
        echo '<tr>';
        if(!empty($paciente['peso'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">Peso</td><td style="padding: 8px;">' . $paciente['peso'] . ' kg</td>';
        if(!empty($paciente['talla'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">Talla</td><td style="padding: 8px;">' . $paciente['talla'] . ' cm</td>';
        echo '</tr>';
        
        echo '<tr>';
        if(!empty($paciente['imc'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">IMC</td><td style="padding: 8px;">' . $paciente['imc'] . '</td>';
        if(!empty($paciente['cc'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">CC</td><td style="padding: 8px;">' . $paciente['cc'] . ' cm</td>';
        echo '</tr>';
        
        echo '<tr>';
        if(!empty($paciente['ca'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">CA</td><td style="padding: 8px;">' . $paciente['ca'] . ' cm</td>';
        if(!empty($paciente['ta'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">TA</td><td style="padding: 8px;">' . $paciente['ta'] . '</td>';
        echo '</tr>';
        
        echo '<tr>';
        if(!empty($paciente['fc'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">FC</td><td style="padding: 8px;">' . $paciente['fc'] . ' lpm</td>';
        if(!empty($paciente['fr'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">FR</td><td style="padding: 8px;">' . $paciente['fr'] . ' rpm</td>';
        echo '</tr>';
        
        echo '<tr>';
        if(!empty($paciente['temp'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">Temperatura</td><td style="padding: 8px;">' . $paciente['temp'] . ' °C</td>';
        if(!empty($paciente['sat02'])) echo '<td style="background-color: #f3e5f5; font-weight: bold; padding: 8px;">SatO2</td><td style="padding: 8px;">' . $paciente['sat02'] . ' %</td>';
        echo '</tr>';
        
        // DxTx
        if(!empty($paciente['dxtx'])) {
            agregarFilaExcel('Dx/Tx (Diagnóstico o Tratamiento Previo)', $paciente['dxtx'], '#efebe9');
        }
        
        // Exploración Física Detallada
        if(!empty($paciente['exploracion_fisica'])) {
            echo '<tr><td colspan="4" style="background-color: #A1887F; color: white; font-weight: bold; padding: 8px;">EXPLORACIÓN FÍSICA DETALLADA</td></tr>';
            echo '<tr><td colspan="4" style="padding: 10px;">' . nl2br(htmlspecialchars($paciente['exploracion_fisica'])) . '</td></tr>';
        }
        
        // IMPRESIÓN DIAGNÓSTICA
        if(!empty($paciente['impresion_diagnostica'])) {
            echo '<tr><td colspan="4" style="background-color: #C62828; color: white; font-weight: bold; padding: 10px; text-align: center;">IMPRESIÓN DIAGNÓSTICA</td></tr>';
            agregarFilaExcel('Impresión diagnóstica', $paciente['impresion_diagnostica'], '#ffebee');
        }
        
        // PLAN
        if(!empty($paciente['plan'])) {
            echo '<tr><td colspan="4" style="background-color: #388E3C; color: white; font-weight: bold; padding: 10px; text-align: center;">PLAN DE TRATAMIENTO</td></tr>';
            agregarFilaExcel('Plan de tratamiento', $paciente['plan'], '#e8f5e9');
        }
        
        // DATOS DEL MÉDICO
        echo '<tr><td colspan="4" style="background-color: #5D4037; color: white; font-weight: bold; padding: 10px; text-align: center;">DATOS DEL MÉDICO</td></tr>';
        agregarFilaExcel('Nombre del médico', !empty($paciente['medico_nombre']) ? $paciente['medico_nombre'] : 'DR. ' . strtoupper($doctor_nombre), '#efebe9');
        agregarFilaExcel('Cédula profesional', !empty($paciente['medico_cedula']) ? $paciente['medico_cedula'] : $doctor_cedula, '#efebe9');
        
        if(!empty($doctor_especialidad)) {
            agregarFilaExcel('Especialidad', $doctor_especialidad, '#efebe9');
        }
        
        if(!empty($doctor_universidad)) {
            agregarFilaExcel('Universidad', $doctor_universidad, '#efebe9');
        }
        
        // PIE DE PÁGINA
        echo '<tr><td colspan="4" style="background-color: #757575; color: white; padding: 10px; text-align: center;">';
        echo 'Documento generado electrónicamente el ' . date('d/m/Y H:i:s') . ' | Sistema DIF Benito Juárez';
        echo '</td></tr>';
        
        echo '</table>';
        echo '</body></html>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pacientes - DIF Benito Juárez</title>
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
    
    /* Logo placeholder si no existe imagen */
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

    /* ====== Contenido principal ====== */
    .main-content {
        flex: 1;
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    /* ====== Container para listar pacientes ====== */
    .container { 
        background: var(--white); 
        border-radius: 20px; 
        padding: 2rem; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        margin-top: 1rem; 
        border: 2px solid var(--dif-verde-light);
    }

    /* ====== Títulos ====== */
    h2 { 
        color: var(--dif-morado); 
        margin-bottom: 1.5rem; 
        font-size: 2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    h3 { 
        color: var(--dif-morado); 
        margin: 1.5rem 0 1rem 0; 
        padding-bottom: 0.5rem; 
        border-bottom: 2px solid var(--dif-turquesa);
        font-size: 1.5rem;
    }

    h4 { 
        color: var(--dif-turquesa); 
        margin: 1rem 0 0.5rem 0; 
        font-size: 1.2rem;
    }

    .table-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 1.5rem; 
        flex-wrap: wrap; 
        gap: 15px; 
    }

    /* ====== Tablas ====== */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        border-radius: var(--border-radius); 
        overflow: hidden; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
    }

    th, td { 
        padding: 14px 16px; 
        text-align: left; 
        border-bottom: 1px solid #e1e5e9;
    }

    th { 
        background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
        color: #fff; 
        font-weight: 600; 
        font-size: 15px;
    }

    tr:nth-child(even) { 
        background: var(--dif-turquesa-light); 
    }

    tr:hover { 
        background: var(--dif-magenta-light); 
    }

    /* ====== Botones ====== */
    .btn { 
        padding: 12px 24px; 
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
        background: linear-gradient(135deg, var(--dif-magenta), var(--dif-morado));
    }
    
    .btn-outline { 
        background-color: transparent; 
        border: 2px solid var(--dif-verde); 
        color: var(--dif-verde-dark); 
    }
    
    .btn-outline:hover {
        background-color: var(--dif-verde-light); 
        transform: translateY(-3px); 
    }

    .btn-success { 
        background: linear-gradient(135deg, var(--dif-verde) 0%, #72A538 100%); 
        color: white; 
        box-shadow: 0 4px 15px rgba(143,199,68,0.3); 
        border: 2px solid var(--dif-verde);
    }
    
    .btn-success:hover {
        transform: translateY(-3px); 
        box-shadow: 0 8px 20px rgba(143,199,68,0.4); 
    }
    
    .btn-warning { 
        background: linear-gradient(135deg, var(--dif-turquesa) 0%, var(--dif-turquesa-dark) 100%); 
        color: white; 
        box-shadow: 0 4px 15px rgba(44,183,191,0.3); 
        border: 2px solid var(--dif-turquesa);
    }
    
    .btn-warning:hover {
        transform: translateY(-3px); 
        box-shadow: 0 8px 20px rgba(44,183,191,0.4); 
    }

    /* ====== Secciones de formulario ====== */
    .form-section { 
        background: var(--white); 
        padding: 1.5rem; 
        margin: 1rem 0; 
        border-radius: var(--border-radius); 
        border-left: 4px solid var(--dif-turquesa); 
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
        padding: 12px; 
        width: 100%; 
        margin: 8px 0; 
        border-radius: 8px; 
        border: 2px solid #e1e5e9; 
        box-sizing: border-box; 
        font-size: 15px;
        transition: all 0.3s;
    }

    form input:focus, 
    form select:focus, 
    form textarea:focus { 
        outline: none; 
        border-color: var(--dif-turquesa); 
        box-shadow: 0 0 0 3px rgba(44,183,191,0.1);
    }

    form label { 
        font-weight: 600; 
        display: block; 
        margin-bottom: 6px; 
        color: var(--dif-morado);
    }

    textarea { 
        resize: vertical; 
        min-height: 100px; 
    }

    .small-input { 
        max-width: 200px; 
    }

    .vital-signs-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
        gap: 1rem; 
        margin: 1rem 0; 
    }

    .interrogatorio-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
        gap: 1rem; 
        margin: 1rem 0; 
    }

    .error { 
        background: #f8d7da; 
        color: #721c24; 
        padding: 15px; 
        border-radius: 5px; 
        margin: 10px 0; 
        border-left: 4px solid #dc3545;
    }

    .success { 
        background: #d4edda; 
        color: #155724; 
        padding: 15px; 
        border-radius: 5px; 
        margin: 10px 0; 
        border-left: 4px solid #28a745;
    }

    .export-buttons { 
        display: flex; 
        gap: 10px; 
        margin: 1rem 0; 
        flex-wrap: wrap; 
    }

    /* ====== Container de acciones ====== */
    .actions-container {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: center;
    }

    /* Botones visibles principales */
    .visible-actions {
        display: flex;
        gap: 6px;
    }

    .btn-sm {
        padding: 8px 10px;
        font-size: 13px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        min-width: 36px;
        height: 36px;
    }

    .btn-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-primary.btn-sm {
        background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%);
        color: white;
    }

    .btn-success.btn-sm {
        background: linear-gradient(135deg, var(--dif-verde) 0%, #72A538 100%);
        color: white;
    }

    .btn-warning.btn-sm {
        background: linear-gradient(135deg, var(--dif-turquesa) 0%, var(--dif-turquesa-dark) 100%);
        color: white;
    }

    /* Dropdown Actions */
    .actions-dropdown {
        position: relative;
        display: inline-block;
    }

    .btn-dropdown {
        padding: 8px 10px;
        background: var(--dif-morado);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        min-width: 36px;
        height: 36px;
    }

    .btn-dropdown:hover {
        background: var(--dif-morado-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(118,61,147,0.3);
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 2px solid var(--dif-magenta-light);
        border-radius: 8px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        min-width: 200px;
        z-index: 1000;
        display: none;
        margin-top: 5px;
        animation: dropdownFadeIn 0.2s ease-out;
    }

    .dropdown-menu.dropdown-up {
        top: auto;
        bottom: 100%;
        margin-top: 0;
        margin-bottom: 5px;
        animation: dropdownFadeInUp 0.2s ease-out;
    }

    .dropdown-menu.show {
        display: block;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        text-decoration: none;
        color: var(--dif-morado);
        border-bottom: 1px solid var(--dif-magenta-light);
        transition: all 0.2s;
        font-size: 14px;
        font-weight: 500;
    }

    .dropdown-item:last-child {
        border-bottom: none;
    }

    .dropdown-item:hover {
        background: var(--dif-turquesa-light);
        color: var(--dif-morado);
    }

    .dropdown-item i {
        width: 16px;
        text-align: center;
        color: var(--dif-morado);
    }

    .dropdown-divider {
        margin: 5px 0;
        border: none;
        border-top: 1px solid var(--dif-magenta-light);
    }

    .dropdown-danger {
        color: #dc3545 !important;
    }

    .dropdown-danger:hover {
        background: #f8d7da !important;
        color: #721c24 !important;
    }

    .dropdown-danger i {
        color: #dc3545 !important;
    }

    /* Animaciones */
    @keyframes dropdownFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes dropdownFadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Tooltips */
    .custom-tooltip {
        position: fixed;
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        z-index: 10000;
        white-space: nowrap;
        pointer-events: none;
        animation: fadeIn 0.2s ease-in-out;
    }

    @keyframes fadeIn {
        from { 
            opacity: 0; 
            transform: translateY(5px); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0); 
        }
    }

    td:last-child {
        width: 200px;
        text-align: center;
    }

    /* ====== Footer ====== */
    .footer { 
        background: linear-gradient(135deg, var(--dif-morado) 0%, var(--dif-magenta) 100%); 
        color: var(--white); 
        text-align: center; 
        padding: 1.5rem; 
        margin-top: 2rem; 
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
        
        .main-content {
            padding: 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .logo-text h1 {
            font-size: 22px;
        }
        
        .logo-image {
            height: 45px;
        }
        
        .nav-link {
            padding: 12px 16px;
            font-size: 14px;
        }
        
        .user-info .name {
            display: none;
        }
        
        .container {
            padding: 1.5rem;
        }
        
        .form-row {
            flex-direction: column;
        }
        
        .table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
    }
    
    @media (max-width: 480px) {
        .logo-text span {
            display: none;
        }
        
        .logo-image {
            height: 40px;
        }
        
        .nav-link span {
            display: none;
        }
        
        .nav-link {
            padding: 10px;
        }
        
        .container {
            padding: 1rem;
        }
        
        .dropdown-menu {
            min-width: 180px;
        }
    }
    /* ====== ANIMACIONES ====== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .container {
            animation: fadeIn 0.8s ease-out;
        }

    /* ====== Badge de colores DIF ====== */
    .dif-colors-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 10px;
    }
    
    .color-sample {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    
    .color-magenta { background-color: var(--dif-magenta); }
    .color-morado { background-color: var(--dif-morado); }
    .color-turquesa { background-color: var(--dif-turquesa); }
    .color-verde { background-color: var(--dif-verde); }
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
                <a href="dashboard.php" class="nav-link" role="menuitem">
                    <i class="fas fa-home" aria-hidden="true"></i> 
                    <span>Inicio</span>
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="pacientes.php" class="nav-link active" role="menuitem" aria-current="page">
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
        <div class="container">
            <?php if(isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="success">Operación realizada correctamente</div>
            <?php endif; ?>

            <?php if($accion == 'listar'): ?>
                <div class="table-header">
                    <h2><i class="fas fa-users"></i> Lista de Pacientes</h2>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-home"></i> Inicio</a>
                        <a href="pacientes.php?accion=registrar" class="btn btn-primary"><i class="fas fa-user-plus"></i> Registrar paciente</a>
                        <a href="pacientes.php?accion=buscar" class="btn btn-outline"><i class="fas fa-search"></i> Buscar</a>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Edad</th>
                            <th>Género</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT * FROM pacientes ORDER BY id DESC");
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo $row['nombre']; ?></strong></td>
                            <td><?php echo $row['edad']; ?> años</td>
                            <td><?php echo $row['genero']; ?></td>
                            <td><?php echo $row['telefono'] ?? 'N/A'; ?></td>
                            
                            <td>
                                <div class="actions-container">
                                    <div class="visible-actions">
                                        <a href="historial.php?paciente_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" title="Historial Médico">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <a href="consultas.php?accion=nueva&paciente_id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm" title="Nueva Consulta">
                                            <i class="fas fa-file-medical"></i>
                                        </a>
                                        <a href="recetas.php?accion=nueva&paciente_id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" title="Nueva Receta">
                                            <i class="fas fa-file-prescription"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="actions-dropdown">
                                        <button class="btn-dropdown" onclick="toggleDropdown(<?php echo $row['id']; ?>)" title="Más acciones">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu" id="dropdown-<?php echo $row['id']; ?>">
                                            <a href="pacientes.php?accion=ver&id=<?php echo $row['id']; ?>" class="dropdown-item">
                                                <i class="fas fa-eye"></i> Ver Ficha Completa
                                            </a>
                                            <a href="pacientes.php?accion=editar&id=<?php echo $row['id']; ?>" class="dropdown-item">
                                                <i class="fas fa-edit"></i> Editar Paciente
                                            </a>
                                            <hr class="dropdown-divider">
                                            <a href="pacientes.php?accion=borrar&id=<?php echo $row['id']; ?>" 
                                               class="dropdown-item dropdown-danger" 
                                               onclick="return confirm('¿Seguro que quieres borrar este paciente?');">
                                                <i class="fas fa-trash"></i> Eliminar Paciente
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif($accion == 'registrar' || $accion == 'editar'): 
                if($accion == 'editar' && isset($_GET['id'])){
                    $stmt = $conn->prepare("SELECT * FROM pacientes WHERE id=?");
                    $stmt->execute([$_GET['id']]);
                    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
                    if(!$paciente) {
                        echo "<p>Paciente no encontrado.</p>";
                        exit;
                    }
                }
            ?>
                <h2><?php echo $accion=='editar'?'Editar paciente':'Registrar nuevo paciente'; ?></h2>
                <a href="pacientes.php" class="btn btn-outline" style="margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Volver</a>

                <form method="post" action="pacientes.php?accion=<?php echo $accion=='editar'?'actualizar':'guardar'; ?>">
                    <?php if($accion=='editar'): ?>
                        <input type="hidden" name="id" value="<?php echo $paciente['id']; ?>">
                    <?php endif; ?>

                    <!-- FICHA DE IDENTIFICACIÓN -->
                    <div class="form-section">
                        <h3>FICHA DE IDENTIFICACIÓN</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre completo:</label>
                                <input type="text" name="nombre" required value="<?php echo $paciente['nombre'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Edad:</label>
                                <input type="text" name="edad" required value="<?php echo $paciente['edad'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Género:</label>
                                <select name="genero" required>
                                    <option value="">Seleccionar</option>
                                    <option value="Masculino" <?php if(($paciente['genero']??'')=='Masculino') echo 'selected'; ?>>Masculino</option>
                                    <option value="Femenino" <?php if(($paciente['genero']??'')=='Femenino') echo 'selected'; ?>>Femenino</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Fecha de nacimiento:</label>
                                <input type="date" name="fecha_nacimiento" value="<?php echo $paciente['fecha_nacimiento'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Dirección:</label>
                                <input type="text" name="direccion" value="<?php echo $paciente['direccion'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Localidad:</label>
                                <input type="text" name="localidad" value="<?php echo $paciente['localidad'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Municipio:</label>
                                <input type="text" name="municipio" value="<?php echo $paciente['municipio'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Estado:</label>
                                <input type="text" name="estado" value="<?php echo $paciente['estado'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Teléfono:</label>
                                <input type="text" name="telefono" value="<?php echo $paciente['telefono'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
    <label><i class="fas fa-id-card"></i> CURP:</label>
    <input type="text" name="curp" id="curp" 
           placeholder="Ej: XEXX010101HNEXXXA" 
           pattern="[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}"
           title="Formato: 4 letras, 6 números, 1 letra (H/M), 5 letras, 2 caracteres alfanuméricos"
           maxlength="18"
           style="text-transform: uppercase;">
    <small class="small-text">Formato: 4 letras, 6 números (fecha nacimiento), 1 letra (H/M), 5 letras, 2 caracteres</small>
</div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Escolaridad:</label>
                                <input type="text" name="escolaridad" value="<?php echo $paciente['escolaridad'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ocupación:</label>
                                <input type="text" name="ocupacion" value="<?php echo $paciente['ocupacion'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Religión:</label>
                                <input type="text" name="religion" value="<?php echo $paciente['religion'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ANTECEDENTES HEREDOFAMILIARES -->
                    <div class="form-section">
                        <h3>ANTECEDENTES HEREDOFAMILIARES</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Antecedentes familiares:</label>
                                <textarea name="antecedentes_familiares"><?php echo $paciente['antecedentes_familiares'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ANTECEDENTES PERSONALES NO PATOLOGICOS -->
                    <div class="form-section">
                        <h3>ANTECEDENTES PERSONALES NO PATOLÓGICOS</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tabaquismo:</label>
                                <input type="text" name="tabaquismo" value="<?php echo $paciente['tabaquismo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Alcoholismo:</label>
                                <input type="text" name="alcoholismo" value="<?php echo $paciente['alcoholismo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Adicciones:</label>
                                <input type="text" name="adicciones" value="<?php echo $paciente['adicciones'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Zoonosis:</label>
                                <input type="text" name="zoonosis" value="<?php echo $paciente['zoonosis'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Inmunizaciones:</label>
                                <input type="text" name="inmunizaciones" value="<?php echo $paciente['inmunizaciones'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Hacinamiento:</label>
                                <input type="text" name="hacinamiento" value="<?php echo $paciente['hacinamiento'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Vivienda:</label>
                                <input type="text" name="vivienda" value="<?php echo $paciente['vivienda'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Hábitos higiénico-dietéticos:</label>
                                <textarea name="habitos_higienico_dieteticos"><?php echo $paciente['habitos_higienico_dieteticos'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- ANTECEDENTES PERSONALES PATOLOGICOS -->
                    <div class="form-section">
                        <h3>ANTECEDENTES PERSONALES PATOLÓGICOS</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Enfermedades infecciosas:</label>
                                <input type="text" name="enfermedades_infecciosas" value="<?php echo $paciente['enfermedades_infecciosas'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Hospitalizaciones:</label>
                                <input type="text" name="hospitalizaciones" value="<?php echo $paciente['hospitalizaciones'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Quirúrgicos:</label>
                                <input type="text" name="quirurgicos" value="<?php echo $paciente['quirurgicos'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Transfusiones:</label>
                                <input type="text" name="transfusiones" value="<?php echo $paciente['transfusiones'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Enfermedades crónicas:</label>
                                <input type="text" name="enfermedades_cronicas" value="<?php echo $paciente['enfermedades_cronicas'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Traumáticos:</label>
                                <input type="text" name="traumaticos" value="<?php echo $paciente['traumaticos'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Alergias:</label>
                                <input type="text" name="alergias" value="<?php echo $paciente['alergias'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Enfermedades de la infancia:</label>
                                <input type="text" name="enfermedades_infancia" value="<?php echo $paciente['enfermedades_infancia'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ANTECEDENTES GINECO-OBSTETRICOS -->
                    <div class="form-section">
                        <h3>ANTECEDENTES GINECO-OBSTÉTRICOS</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Menarca:</label>
                                <input type="text" name="menarca" class="small-input" value="<?php echo $paciente['menarca'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Ciclos:</label>
                                <input type="text" name="ciclos" class="small-input" value="<?php echo $paciente['ciclos'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>IVSA:</label>
                                <input type="text" name="ivsa" class="small-input" value="<?php echo $paciente['ivsa'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>NPS:</label>
                                <input type="text" name="nps" class="small-input" value="<?php echo $paciente['nps'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>G:</label>
                                <input type="text" name="g" class="small-input" value="<?php echo $paciente['g'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>P:</label>
                                <input type="text" name="p" class="small-input" value="<?php echo $paciente['p'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>A:</label>
                                <input type="text" name="a" class="small-input" value="<?php echo $paciente['a'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>C:</label>
                                <input type="text" name="c" class="small-input" value="<?php echo $paciente['c'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>FUM:</label>
                                <input type="text" name="fum" value="<?php echo $paciente['fum'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>FPP:</label>
                                <input type="text" name="fpp" value="<?php echo $paciente['fpp'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>FUP:</label>
                                <input type="text" name="fup" value="<?php echo $paciente['fup'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>FUC:</label>
                                <input type="text" name="fuc" value="<?php echo $paciente['fuc'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>FUL:</label>
                                <input type="text" name="ful" value="<?php echo $paciente['ful'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>MPF:</label>
                                <input type="text" name="mpf" value="<?php echo $paciente['mpf'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Papanicolaou:</label>
                                <input type="text" name="papanicolaou" value="<?php echo $paciente['papanicolaou'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Mastografía:</label>
                                <input type="text" name="mastografia" value="<?php echo $paciente['mastografia'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>VPH:</label>
                                <input type="text" name="vph" value="<?php echo $paciente['vph'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- PADECIMIENTO ACTUAL -->
                    <div class="form-section">
                        <h3>PADECIMIENTO ACTUAL</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <textarea name="padecimiento_actual" placeholder="Describa el padecimiento actual del paciente..."><?php echo $paciente['padecimiento_actual'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- MEDICACIÓN ACTUAL -->
                    <div class="form-section">
                        <h3>MEDICACIÓN ACTUAL</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <textarea name="medicacion_actual" placeholder="Lista de medicamentos que toma actualmente el paciente..."><?php echo $paciente['medicacion_actual'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- INTERROGATORIO POR APARATOS Y SISTEMAS -->
                    <div class="form-section">
                        <h3>INTERROGATORIO POR APARATOS Y SISTEMAS</h3>
                        <div class="interrogatorio-grid">
                            <div class="form-group">
                                <label>Nervioso:</label>
                                <input type="text" name="interrogatorio_nervioso" value="<?php echo $paciente['interrogatorio_nervioso'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Digestivo:</label>
                                <input type="text" name="interrogatorio_digestivo" value="<?php echo $paciente['interrogatorio_digestivo'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Respiratorio:</label>
                                <input type="text" name="interrogatorio_respiratorio" value="<?php echo $paciente['interrogatorio_respiratorio'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Urinario:</label>
                                <input type="text" name="interrogatorio_urinario" value="<?php echo $paciente['interrogatorio_urinario'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Circulatorio:</label>
                                <input type="text" name="interrogatorio_circulatorio" value="<?php echo $paciente['interrogatorio_circulatorio'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Genital:</label>
                                <input type="text" name="interrogatorio_genital" value="<?php echo $paciente['interrogatorio_genital'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Músculo-esquelético:</label>
                                <input type="text" name="interrogatorio_musculo_esqueletico" value="<?php echo $paciente['interrogatorio_musculo_esqueletico'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Piel y anexos:</label>
                                <input type="text" name="interrogatorio_piel_anexos" value="<?php echo $paciente['interrogatorio_piel_anexos'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Órganos de los sentidos:</label>
                                <input type="text" name="interrogatorio_organos_sentidos" value="<?php echo $paciente['interrogatorio_organos_sentidos'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- EXPLORACIÓN FÍSICA -->
                    <div class="form-section">
                        <h3>EXPLORACIÓN FÍSICA</h3>

                        <h4>Somatometría</h4>
                        <div class="vital-signs-grid">
                            <div class="form-group">
                                <label>Peso (kg):</label>
                                <input type="text" name="peso" value="<?php echo $paciente['peso'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Talla (cm):</label>
                                <input type="text" name="talla" value="<?php echo $paciente['talla'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>IMC:</label>
                                <input type="text" name="imc" value="<?php echo $paciente['imc'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>CC:</label>
                                <input type="text" name="cc" value="<?php echo $paciente['cc'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>CA:</label>
                                <input type="text" name="ca" value="<?php echo $paciente['ca'] ?? ''; ?>">
                            </div>
                        </div>

                        <h4>Signos Vitales</h4>
                        <div class="vital-signs-grid">
                            <div class="form-group">
                                <label>TA:</label>
                                <input type="text" name="ta" value="<?php echo $paciente['ta'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>FC:</label>
                                <input type="text" name="fc" value="<?php echo $paciente['fc'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>FR:</label>
                                <input type="text" name="fr" value="<?php echo $paciente['fr'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>TEMP:</label>
                                <input type="text" name="temp" value="<?php echo $paciente['temp'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>SatO2:</label>
                                <input type="text" name="sat02" value="<?php echo $paciente['sat02'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>DXTX:</label>
                                <input type="text" name="dxtx" value="<?php echo $paciente['dxtx'] ?? ''; ?>">
                            </div>
                        </div>

                        <h4>Exploración Física Detallada</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <textarea name="exploracion_fisica" placeholder="Descripción detallada de la exploración física..."><?php echo $paciente['exploracion_fisica'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- IMPRESIÓN DIAGNÓSTICA -->
                    <div class="form-section">
                        <h3>IMPRESIÓN DIAGNÓSTICA</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <textarea name="impresion_diagnostica" placeholder="Impresión diagnóstica..."><?php echo $paciente['impresion_diagnostica'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- PLAN -->
                    <div class="form-section">
                        <h3>PLAN</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <textarea name="plan" placeholder="Plan de tratamiento..."><?php echo $paciente['plan'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- DATOS DEL MÉDICO -->
<div class="form-section">
    <h3><i class="fas fa-user-md" style="color: var(--dif-magenta);"></i> DATOS DEL MÉDICO</h3>
    <div class="form-row">
        <div class="form-group">
            <label><i class="fas fa-user-md"></i> Nombre del médico:</label>
            <?php
            // Lista de posibles títulos profesionales
            $titulos_profesionales = [
                'DR\.', 'DRA\.', 'DR', 'DRA',
                'DOC\.', 'DOC', 'DOCTOR', 'DOCTORA',
                'MÉDICO', 'MÉDICA', 'MEDICO', 'MEDICA',
                'LIC\.', 'LIC', 'LICENCIADO', 'LICENCIADA',
                'ING\.', 'ING', 'INGENIERO', 'INGENIERA',
                'ARQ\.', 'ARQ', 'ARQUITECTO', 'ARQUITECTA',
                'MTRO\.', 'MTRO', 'MAESTRO', 'MAESTRA',
                'PROF\.', 'PROF', 'PROFESOR', 'PROFESORA',
                'C\.', 'C', 'CONTADOR', 'CONTADORA',
                'PSIC\.', 'PSIC', 'PSICÓLOGO', 'PSICOLOGA'
            ];
            
            // Crear patrón regex
            $patron_titulos = '/^(' . implode('|', $titulos_profesionales) . ')\s+/i';
            
            if(isset($paciente) && !empty($paciente['medico_nombre'])) {
                $nombre_medico = $paciente['medico_nombre'];
            } else {
                $nombre_doctor = trim($doctor_nombre);
                
                // Verificar si ya tiene título
                if (preg_match($patron_titulos, $nombre_doctor)) {
                    $nombre_medico = $nombre_doctor; // Ya tiene título
                } else {
                    // Detectar género para asignar título apropiado
                    $primer_nombre = explode(' ', $nombre_doctor)[0];
                    $primer_nombre = strtolower($primer_nombre);
                    
                    // Nombres femeninos comunes
                    $nombres_femeninos = ['maria', 'ana', 'rosa', 'lucia', 'carmen', 'isabel', 
                                         'elena', 'patricia', 'monica', 'teresa', 'vania', 'vanesa',
                                         'adriana', 'claudia', 'diana', 'elisa', 'fernanda', 'gabriela',
                                         'jimena', 'karina', 'laura', 'nadia', 'olivia', 'paola',
                                         'rebeca', 'sofia', 'valeria', 'ximena', 'yolanda', 'zoe'];
                    
                    if (in_array($primer_nombre, $nombres_femeninos)) {
                        $nombre_medico = 'DRA. ' . $nombre_doctor;
                    } else {
                        $nombre_medico = 'DR. ' . $nombre_doctor;
                    }
                }
            }
            ?>
            <input type="text" name="medico_nombre" value="<?php echo htmlspecialchars($nombre_medico); ?>" 
                   readonly style="background-color: #f5f5f5;">
        </div>
        <div class="form-group">
            <label><i class="fas fa-id-card"></i> Cédula profesional:</label>
            <?php
            if(isset($paciente) && !empty($paciente['medico_cedula'])) {
                $cedula_medico = $paciente['medico_cedula'];
            } else {
                $cedula_medico = $doctor_cedula;
            }
            ?>
            <input type="text" name="medico_cedula" value="<?php echo htmlspecialchars($cedula_medico); ?>" 
                   readonly style="background-color: #f5f5f5;">
        </div>
    </div>
    
    <!-- Información adicional (opcional) -->
    <div style="margin-top: 10px; padding: 5px; background: #f8f9fa; border-radius: 4px; font-size: 12px;">
        <i class="fas fa-info-circle" style="color: #666;"></i>
        <span style="color: #666;">
            Datos del médico actual: 
            <strong><?php echo htmlspecialchars($doctor_nombre); ?></strong>
            <?php if(!empty($doctor_especialidad)): ?>
                - <?php echo htmlspecialchars($doctor_especialidad); ?>
            <?php endif; ?>
        </span>
    </div>
</div>
    
    <!-- Información adicional del médico (opcional) -->
    <?php if(!empty($doctor_especialidad) || !empty($doctor_universidad)): ?>
    <div style="margin-top: 15px; padding: 10px; background: linear-gradient(135deg, rgba(118,61,147,0.05), rgba(214,41,133,0.05)); border-radius: 8px; border-left: 4px solid var(--dif-morado);">
        <?php if(!empty($doctor_especialidad)): ?>
        <div style="font-size: 13px; color: var(--dif-morado); font-weight: bold; margin-bottom: 3px;">Especialidad:</div>
        <div style="font-size: 14px;"><?php echo $doctor_especialidad; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($doctor_universidad)): ?>
        <div style="font-size: 13px; color: var(--dif-magenta); font-weight: bold; margin-top: 8px; margin-bottom: 3px;">Universidad:</div>
        <div style="font-size: 14px;"><?php echo $doctor_universidad; ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 10px; padding: 8px; background: var(--dif-turquesa-light); border-radius: 5px; border: 1px solid var(--dif-turquesa);">
        <small style="color: var(--dif-turquesa-dark); display: flex; align-items: center; gap: 5px;">
            <i class="fas fa-info-circle"></i>
            Los datos del médico son autocompletados según la sesión actual
        </small>
    </div>
</div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $accion=='editar'?'Actualizar':'Guardar'; ?> paciente</button>
                </form>

            <?php elseif($accion == 'ver' && isset($_GET['id'])):
                // Obtener TODOS los campos del paciente
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
                    FROM pacientes WHERE id=?";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$_GET['id']]);
                $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($paciente):
            ?>
                <h2><i class="fas fa-user-injured"></i> Ficha del paciente: <?php echo $paciente['nombre']; ?></h2>

                <!-- BOTONES DE EXPORTACIÓN -->
                <div class="export-buttons">
                    <a href="pacientes.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
                    <a href="imprimir_historial.php?id=<?php echo $paciente['id']; ?>" target="_blank" class="btn btn-warning"><i class="fas fa-print"></i> Imprimir Historial</a>
                    <a href="pacientes.php?accion=excel&id=<?php echo $paciente['id']; ?>" class="btn btn-success"><i class="fas fa-file-excel"></i> Descargar Excel Completo</a>
                    <a href="recetas.php?accion=nueva&paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-warning"><i class="fas fa-file-prescription"></i> Generar Receta</a>
                    <a href="pacientes.php?accion=editar&id=<?php echo $paciente['id']; ?>" class="btn btn-info" style="background: linear-gradient(135deg, var(--dif-turquesa), var(--dif-turquesa-dark)); color: white;"><i class="fas fa-edit"></i> Editar Paciente</a>
                </div>

                <!-- FICHA DE IDENTIFICACIÓN -->
                <div class="form-section">
                    <h3 style="border-left: 4px solid var(--dif-morado);">FICHA DE IDENTIFICACIÓN</h3>
                    <div class="grid-datos" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 15px 0;">
                        <div class="info-item" style="background: var(--dif-morado-light); padding: 12px; border-radius: 8px;">
                            <div style="font-weight: bold; color: var(--dif-morado); margin-bottom: 5px;">Nombre completo</div>
                            <div style="font-size: 16px; color: #333;"><?php echo $paciente['nombre']; ?></div>
                        </div>
                        
                        <div class="info-item" style="background: var(--dif-magenta-light); padding: 12px; border-radius: 8px;">
                            <div style="font-weight: bold; color: var(--dif-magenta); margin-bottom: 5px;">Edad</div>
                            <div style="font-size: 16px; color: #333;"><?php echo $paciente['edad']; ?> años</div>
                        </div>
                        
                        <div class="info-item" style="background: var(--dif-turquesa-light); padding: 12px; border-radius: 8px;">
                            <div style="font-weight: bold; color: var(--dif-turquesa); margin-bottom: 5px;">Género</div>
                            <div style="font-size: 16px; color: #333;"><?php echo $paciente['genero']; ?></div>
                        </div>
                        
                        <div class="info-item" style="background: var(--dif-verde-light); padding: 12px; border-radius: 8px;">
                            <div style="font-weight: bold; color: var(--dif-verde); margin-bottom: 5px;">Fecha de nacimiento</div>
                            <div style="font-size: 16px; color: #333;"><?php echo $paciente['fecha_nacimiento'] ?? 'No registrada'; ?></div>
                        </div>
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <tr><th style="width: 30%; background: var(--dif-morado-light); color: var(--dif-morado); padding: 10px;">Dirección</th><td style="padding: 10px;"><?php echo $paciente['direccion']; ?></td></tr>
                        <tr><th style="background: var(--dif-morado-light); color: var(--dif-morado); padding: 10px;">Localidad</th><td style="padding: 10px;"><?php echo $paciente['localidad']; ?></td></tr>
                        <tr><th style="background: var(--dif-morado-light); color: var(--dif-morado); padding: 10px;">Municipio</th><td style="padding: 10px;"><?php echo $paciente['municipio']; ?></td></tr>
                        <tr><th style="background: var(--dif-morado-light); color: var(--dif-morado); padding: 10px;">Estado</th><td style="padding: 10px;"><?php echo $paciente['estado']; ?></td></tr>
                        <tr><th style="background: var(--dif-magenta-light); color: var(--dif-magenta); padding: 10px;">Teléfono</th><td style="padding: 10px;"><?php echo $paciente['telefono'] ?? 'No registrado'; ?></td></tr>
                        <tr><th style="background: var(--dif-magenta-light); color: var(--dif-magenta); padding: 10px;">CURP</th><td style="padding: 10px; font-family: monospace;"><?php echo $paciente['curp'] ?? 'No registrado'; ?></td></tr>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 10px;">Escolaridad</th><td style="padding: 10px;"><?php echo $paciente['escolaridad'] ?? 'No registrada'; ?></td></tr>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 10px;">Ocupación</th><td style="padding: 10px;"><?php echo $paciente['ocupacion'] ?? 'No registrada'; ?></td></tr>
                        <tr><th style="background: var(--dif-verde-light); color: var(--dif-verde); padding: 10px;">Religión</th><td style="padding: 10px;"><?php echo $paciente['religion'] ?? 'No registrada'; ?></td></tr>
                    </table>
                </div>

                <!-- ANTECEDENTES HEREDOFAMILIARES -->
                <?php if(!empty($paciente['antecedentes_familiares'])): ?>
                <div class="form-section">
                    <h3 style="border-left: 4px solid var(--dif-magenta);">ANTECEDENTES HEREDOFAMILIARES</h3>
                    <div style="background: linear-gradient(135deg, rgba(214,41,133,0.05), rgba(118,61,147,0.05)); padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <?php echo nl2br($paciente['antecedentes_familiares']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ANTECEDENTES PERSONALES NO PATOLÓGICOS -->
                <div class="form-section">
                    <h3 style="border-left: 4px solid var(--dif-turquesa);">ANTECEDENTES PERSONALES NO PATOLÓGICOS</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <?php if(!empty($paciente['tabaquismo'])): ?>
                        <tr><th style="width: 30%; background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Tabaquismo</th><td style="padding: 8px;"><?php echo $paciente['tabaquismo']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['alcoholismo'])): ?>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Alcoholismo</th><td style="padding: 8px;"><?php echo $paciente['alcoholismo']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['adicciones'])): ?>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Adicciones</th><td style="padding: 8px;"><?php echo $paciente['adicciones']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['zoonosis'])): ?>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Zoonosis</th><td style="padding: 8px;"><?php echo $paciente['zoonosis']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['inmunizaciones'])): ?>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Inmunizaciones</th><td style="padding: 8px;"><?php echo $paciente['inmunizaciones']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['hacinamiento'])): ?>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Hacinamiento</th><td style="padding: 8px;"><?php echo $paciente['hacinamiento']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['vivienda'])): ?>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Vivienda</th><td style="padding: 8px;"><?php echo $paciente['vivienda']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['habitos_higienico_dieteticos'])): ?>
                        <tr><th style="background: var(--dif-turquesa-light); color: var(--dif-turquesa); padding: 8px;">Hábitos higiénico-dietéticos</th><td style="padding: 8px;"><?php echo $paciente['habitos_higienico_dieteticos']; ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- ANTECEDENTES PERSONALES PATOLÓGICOS -->
                <div class="form-section">
                    <h3 style="border-left: 4px solid #ff6b6b;">ANTECEDENTES PERSONALES PATOLÓGICOS</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <?php if(!empty($paciente['enfermedades_infecciosas'])): ?>
                        <tr><th style="width: 30%; background: #ffebee; color: #d32f2f; padding: 8px;">Enfermedades infecciosas</th><td style="padding: 8px;"><?php echo $paciente['enfermedades_infecciosas']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['hospitalizaciones'])): ?>
                        <tr><th style="background: #ffebee; color: #d32f2f; padding: 8px;">Hospitalizaciones</th><td style="padding: 8px;"><?php echo $paciente['hospitalizaciones']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['quirurgicos'])): ?>
                        <tr><th style="background: #ffebee; color: #d32f2f; padding: 8px;">Quirúrgicos</th><td style="padding: 8px;"><?php echo $paciente['quirurgicos']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['transfusiones'])): ?>
                        <tr><th style="background: #ffebee; color: #d32f2f; padding: 8px;">Transfusiones</th><td style="padding: 8px;"><?php echo $paciente['transfusiones']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['enfermedades_cronicas'])): ?>
                        <tr><th style="background: #ffebee; color: #d32f2f; padding: 8px;">Enfermedades crónicas</th><td style="padding: 8px;"><?php echo $paciente['enfermedades_cronicas']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['traumaticos'])): ?>
                        <tr><th style="background: #ffebee; color: #d32f2f; padding: 8px;">Traumáticos</th><td style="padding: 8px;"><?php echo $paciente['traumaticos']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['alergias'])): ?>
                        <tr><th style="background: #ffebee; color: #d32f2f; padding: 8px;">Alergias</th><td style="padding: 8px; color: #d32f2f; font-weight: bold;"><?php echo $paciente['alergias']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['enfermedades_infancia'])): ?>
                        <tr><th style="background: #ffebee; color: #d32f2f; padding: 8px;">Enfermedades de la infancia</th><td style="padding: 8px;"><?php echo $paciente['enfermedades_infancia']; ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- ANTECEDENTES GINECO-OBSTÉTRICOS (solo si es mujer o tiene datos) -->
                <?php if($paciente['genero'] == 'Femenino' || !empty($paciente['menarca']) || !empty($paciente['g']) || !empty($paciente['p'])): ?>
                <div class="form-section">
                    <h3 style="border-left: 4px solid #9c27b0;">ANTECEDENTES GINECO-OBSTÉTRICOS</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <?php if(!empty($paciente['menarca'])): ?>
                        <tr><th style="width: 30%; background: #f3e5f5; color: #7b1fa2; padding: 8px;">Menarca</th><td style="padding: 8px;"><?php echo $paciente['menarca']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['ciclos'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">Ciclos</th><td style="padding: 8px;"><?php echo $paciente['ciclos']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['ivsa'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">IVSA</th><td style="padding: 8px;"><?php echo $paciente['ivsa']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['nps'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">NPS</th><td style="padding: 8px;"><?php echo $paciente['nps']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['g'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">G (Gestas)</th><td style="padding: 8px;"><?php echo $paciente['g']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['p'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">P (Partos)</th><td style="padding: 8px;"><?php echo $paciente['p']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['a'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">A (Abortos)</th><td style="padding: 8px;"><?php echo $paciente['a']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['c'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">C (Cesáreas)</th><td style="padding: 8px;"><?php echo $paciente['c']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['fum'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">FUM</th><td style="padding: 8px;"><?php echo $paciente['fum']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['fpp'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">FPP</th><td style="padding: 8px;"><?php echo $paciente['fpp']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['fup'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">FUP</th><td style="padding: 8px;"><?php echo $paciente['fup']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['fuc'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">FUC</th><td style="padding: 8px;"><?php echo $paciente['fuc']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['ful'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">FUL</th><td style="padding: 8px;"><?php echo $paciente['ful']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['mpf'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">MPF</th><td style="padding: 8px;"><?php echo $paciente['mpf']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['papanicolaou'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">Papanicolaou</th><td style="padding: 8px;"><?php echo $paciente['papanicolaou']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['mastografia'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">Mastografía</th><td style="padding: 8px;"><?php echo $paciente['mastografia']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['vph'])): ?>
                        <tr><th style="background: #f3e5f5; color: #7b1fa2; padding: 8px;">VPH</th><td style="padding: 8px;"><?php echo $paciente['vph']; ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
                <?php endif; ?>

                <!-- PADECIMIENTO ACTUAL -->
                <?php if(!empty($paciente['padecimiento_actual'])): ?>
                <div class="form-section">
                    <h3 style="border-left: 4px solid #ff9800;">PADECIMIENTO ACTUAL</h3>
                    <div style="background: linear-gradient(135deg, rgba(255,152,0,0.05), rgba(255,87,34,0.05)); padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <?php echo nl2br($paciente['padecimiento_actual']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- MEDICACIÓN ACTUAL -->
                <?php if(!empty($paciente['medicacion_actual'])): ?>
                <div class="form-section">
                    <h3 style="border-left: 4px solid #4caf50;">MEDICACIÓN ACTUAL</h3>
                    <div style="background: linear-gradient(135deg, rgba(76,175,80,0.05), rgba(56,142,60,0.05)); padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <?php echo nl2br($paciente['medicacion_actual']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- INTERROGATORIO POR APARATOS Y SISTEMAS -->
                <div class="form-section">
                    <h3 style="border-left: 4px solid #2196f3;">INTERROGATORIO POR APARATOS Y SISTEMAS</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <?php if(!empty($paciente['interrogatorio_nervioso'])): ?>
                        <tr><th style="width: 30%; background: #e3f2fd; color: #1565c0; padding: 8px;">Nervioso</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_nervioso']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_digestivo'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Digestivo</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_digestivo']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_respiratorio'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Respiratorio</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_respiratorio']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_urinario'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Urinario</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_urinario']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_circulatorio'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Circulatorio</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_circulatorio']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_genital'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Genital</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_genital']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_musculo_esqueletico'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Músculo-esquelético</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_musculo_esqueletico']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_piel_anexos'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Piel y anexos</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_piel_anexos']; ?></td></tr>
                        <?php endif; ?>
                        
                        <?php if(!empty($paciente['interrogatorio_organos_sentidos'])): ?>
                        <tr><th style="background: #e3f2fd; color: #1565c0; padding: 8px;">Órganos de los sentidos</th><td style="padding: 8px;"><?php echo $paciente['interrogatorio_organos_sentidos']; ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- EXPLORACIÓN FÍSICA -->
                <div class="form-section">
                    <h3 style="border-left: 4px solid #673ab7;">EXPLORACIÓN FÍSICA</h3>
                    
                    <!-- SOMATOMETRÍA Y SIGNOS VITALES -->
                    <div style="margin: 15px 0;">
                        <h4 style="color: var(--dif-morado); margin-bottom: 10px;">Somatometría y Signos Vitales</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 20px;">
                            <?php if(!empty($paciente['peso'])): ?>
                            <div style="background: var(--dif-morado-light); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: var(--dif-morado);">Peso</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['peso']; ?> kg</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['talla'])): ?>
                            <div style="background: var(--dif-magenta-light); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: var(--dif-magenta);">Talla</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['talla']; ?> cm</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['imc'])): ?>
                            <div style="background: var(--dif-turquesa-light); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: var(--dif-turquesa);">IMC</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['imc']; ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['cc'])): ?>
                            <div style="background: var(--dif-verde-light); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: var(--dif-verde);">CC</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['cc']; ?> cm</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['ca'])): ?>
                            <div style="background: #e1f5fe; padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: #0277bd;">CA</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['ca']; ?> cm</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['ta'])): ?>
                            <div style="background: #fce4ec; padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: #ad1457;">TA</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['ta']; ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['fc'])): ?>
                            <div style="background: #e8f5e8; padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: #2e7d32;">FC</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['fc']; ?> lpm</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['fr'])): ?>
                            <div style="background: #f3e5f5; padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: #6a1b9a;">FR</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['fr']; ?> rpm</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['temp'])): ?>
                            <div style="background: #fff3e0; padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: #ef6c00;">TEMP</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['temp']; ?> °C</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($paciente['sat02'])): ?>
                            <div style="background: #e0f7fa; padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-weight: bold; color: #006064;">SatO2</div>
                                <div style="font-size: 18px; font-weight: bold;"><?php echo $paciente['sat02']; ?> %</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if(!empty($paciente['dxtx'])): ?>
                    <div style="margin: 15px 0;">
                        <h4 style="color: #d84315; margin-bottom: 10px;">Dx/Tx (Diagnóstico o Tratamiento Previo)</h4>
                        <div style="background: #fbe9e7; padding: 12px; border-radius: 8px;">
                            <?php echo nl2br($paciente['dxtx']); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($paciente['exploracion_fisica'])): ?>
                    <div style="margin: 15px 0;">
                        <h4 style="color: #5d4037; margin-bottom: 10px;">Exploración Física Detallada</h4>
                        <div style="background: #efebe9; padding: 15px; border-radius: 8px;">
                            <?php echo nl2br($paciente['exploracion_fisica']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- IMPRESIÓN DIAGNÓSTICA -->
                <?php if(!empty($paciente['impresion_diagnostica'])): ?>
                <div class="form-section">
                    <h3 style="border-left: 4px solid #c62828;">IMPRESIÓN DIAGNÓSTICA</h3>
                    <div style="background: linear-gradient(135deg, rgba(198,40,40,0.05), rgba(183,28,28,0.05)); padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <?php echo nl2br($paciente['impresion_diagnostica']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PLAN -->
                <?php if(!empty($paciente['plan'])): ?>
                <div class="form-section">
                    <h3 style="border-left: 4px solid #388e3c;">PLAN DE TRATAMIENTO</h3>
                    <div style="background: linear-gradient(135deg, rgba(56,142,60,0.05), rgba(27,94,32,0.05)); padding: 15px; border-radius: 8px; margin-top: 10px;">
                        <?php echo nl2br($paciente['plan']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- DATOS DEL MÉDICO -->
                <div class="form-section">
                    <h3 style="border-left: 4px solid var(--dif-morado); background: linear-gradient(135deg, rgba(118,61,147,0.1), rgba(214,41,133,0.1)); padding: 15px; border-radius: 8px 8px 0 0; margin: -1.5rem -1.5rem 15px -1.5rem;">
                        <i class="fas fa-user-md" style="margin-right: 10px;"></i> DATOS DEL MÉDICO
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 10px;">
                        <div style="background: var(--dif-morado-light); padding: 15px; border-radius: 8px;">
                            <div style="font-weight: bold; color: var(--dif-morado); margin-bottom: 8px; font-size: 14px;">
                                <i class="fas fa-user-md"></i> NOMBRE DEL MÉDICO
                            </div>
                            <div style="font-size: 16px; font-weight: bold; color: #333;">
                                <?php echo !empty($paciente['medico_nombre']) ? $paciente['medico_nombre'] : 'DR. ' . strtoupper($doctor_nombre); ?>
                            </div>
                        </div>
                        
                        <div style="background: var(--dif-magenta-light); padding: 15px; border-radius: 8px;">
                            <div style="font-weight: bold; color: var(--dif-magenta); margin-bottom: 8px; font-size: 14px;">
                                <i class="fas fa-id-card"></i> CÉDULA PROFESIONAL
                            </div>
                            <div style="font-size: 16px; font-weight: bold; color: #333;">
                                <?php echo !empty($paciente['medico_cedula']) ? $paciente['medico_cedula'] : $doctor_cedula; ?>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div style="margin-top: 15px; padding: 12px; background: var(--white); border-radius: 8px; border: 2px solid var(--dif-morado); text-align: center;">
                        <small style="color: var(--dif-morado); display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-info-circle"></i>
                            Documento válido solo con sello y firma del médico tratante
                        </small>
                    </div>
                </div>

            <?php else: ?>
                <div class="error" style="padding: 20px; text-align: center;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: #d32f2f; margin-bottom: 10px;"></i>
                    <p style="font-size: 18px; font-weight: bold;">Paciente no encontrado</p>
                    <p>El paciente que buscas no existe o ha sido eliminado.</p>
                    <a href="pacientes.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-arrow-left"></i> Volver a la lista de pacientes
                    </a>
                </div>
            <?php endif; ?>

            <?php elseif($accion == 'buscar'): ?>
                <h2>Buscar paciente</h2>
                <a href="pacientes.php" class="btn btn-outline" style="margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Volver</a>
                <form method="post" action="pacientes.php?accion=buscar">
                    <label>Nombre del paciente:</label>
                    <input type="text" name="nombre" placeholder="Ingresa el nombre del paciente" required>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar paciente</button>
                </form>
                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['nombre'])) {
                    $nombre = "%".$_POST['nombre']."%";
                    $stmt = $conn->prepare("SELECT * FROM pacientes WHERE nombre LIKE ? ORDER BY nombre");
                    $stmt->execute([$nombre]);
                    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if ($resultados):
                ?>
                <h3 style="margin-top: 2rem;">Resultados de búsqueda:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Edad</th>
                            <th>Género</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($resultados as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['nombre']; ?></td>
                            <td><?php echo $row['edad']; ?></td>
                            <td><?php echo $row['genero']; ?></td>
                            <td><?php echo $row['telefono'] ?? 'N/A'; ?></td>

                            <td>
                                <div class="actions-container">
                                    <div class="visible-actions">
                                        <a href="historial.php?paciente_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" title="Historial Médico">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <a href="consultas.php?accion=nueva&paciente_id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm" title="Nueva Consulta">
                                            <i class="fas fa-file-medical"></i>
                                        </a>
                                        <a href="recetas.php?accion=nueva&paciente_id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm" title="Nueva Receta">
                                            <i class="fas fa-file-prescription"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="actions-dropdown">
                                        <button class="btn-dropdown" onclick="toggleDropdown(<?php echo $row['id']; ?>)" title="Más acciones">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu" id="dropdown-<?php echo $row['id']; ?>">
                                            <a href="pacientes.php?accion=ver&id=<?php echo $row['id']; ?>" class="dropdown-item">
                                                <i class="fas fa-eye"></i> Ver Ficha Completa
                                            </a>
                                            <a href="pacientes.php?accion=editar&id=<?php echo $row['id']; ?>" class="dropdown-item">
                                                <i class="fas fa-edit"></i> Editar Paciente
                                            </a>
                                            <hr class="dropdown-divider">
                                            <a href="pacientes.php?accion=borrar&id=<?php echo $row['id']; ?>" 
                                               class="dropdown-item dropdown-danger" 
                                               onclick="return confirm('¿Seguro que quieres borrar este paciente?');">
                                                <i class="fas fa-trash"></i> Eliminar Paciente
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style='margin-top:1rem;color:#d9534f;'>No se encontraron pacientes con ese nombre.</p>
                <?php endif; } ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- ====== Footer ====== -->
    <footer class="footer" role="contentinfo">
        <p>© <?php echo date('Y'); ?> DIF Municipal Benito Juárez Tlaxcala | Sistema de Gestión Médica</p>
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

        // Versión simplificada - siempre hacia arriba para los últimos elementos
        function toggleDropdown(patientId) {
            const dropdown = document.getElementById('dropdown-' + patientId);
            const allDropdowns = document.querySelectorAll('.dropdown-menu');
            const button = event.currentTarget;
            
            // Cerrar todos los demás dropdowns
            allDropdowns.forEach(d => {
                if (d.id !== 'dropdown-' + patientId) {
                    d.classList.remove('show');
                }
            });
            
            // Si ya está abierto, cerrarlo
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                return;
            }
            
            // Para los últimos 3-4 pacientes, abrir hacia arriba
            const tableRows = document.querySelectorAll('tbody tr');
            const currentRow = button.closest('tr');
            const rowIndex = Array.from(tableRows).indexOf(currentRow);
            
            // Si está entre los últimos 3 registros, abrir hacia arriba
            if (rowIndex >= tableRows.length - 3) {
                dropdown.classList.add('dropdown-up');
            } else {
                dropdown.classList.remove('dropdown-up');
            }
            
            dropdown.classList.add('show');
        }
    </script>   
</body>
</html>