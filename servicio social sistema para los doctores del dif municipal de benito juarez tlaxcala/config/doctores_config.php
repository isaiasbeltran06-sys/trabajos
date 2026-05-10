<?php
// config/doctores_config.php

$doctores_autorizados = [
    // Doctores actuales
    '13851292' => [
        'id' => 1,
        'password' => 'medico123',
        'nombre' => 'Med. Vania Velázquez Camacho',
        'especialidad' => 'Lic. Médico Cirujano',
        'universidad' => 'Universidad Autónoma de Tlaxcala',
        'cedula' => '13851292',
        'email' => 'vania.velazquez@difbenitojuarez.mx',
        'iniciales' => 'VV',
        'activo' => true
    ],
    '10932380' => [
        'id' => 2,
        'password' => 'medico456',
        'nombre' => 'Dr. Antonio Betzaid Vaca Sánchez',
        'especialidad' => 'Lic. Médico Cirujano',
        'universidad' => 'Universidad Popular Autónoma del Estado de Puebla',
        'cedula' => '10932380',
        'email' => 'antonio.vaca@difbenitojuarez.mx',
        'iniciales' => 'AV',
        'activo' => true
    ],
    
    // EJEMPLO: Para agregar nuevos doctores, copia y pega este bloque:
    /*
    '12345678' => [
        'id' => 3, // Incrementa este número
        'password' => 'nueva_password',
        'nombre' => 'Dr. Nuevo Doctor',
        'especialidad' => 'Especialidad del doctor',
        'universidad' => 'Universidad del doctor',
        'cedula' => '12345678',
        'email' => 'nuevo.doctor@difbenitojuarez.mx',
        'iniciales' => 'ND',
        'activo' => true
    ]
    */
];
?>