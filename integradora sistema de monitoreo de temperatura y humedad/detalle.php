<!DOCTYPE html>
<html lang="es" ng-app="detallesApp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Localidad</title>
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Estilos generales mejorados */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        h1 {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: white;
            padding: 25px;
            margin: 0;
            font-size: 1.8em;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        /* Sección de información */
        .info {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 20px;
            flex-wrap: wrap;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }
        .info div {
            text-align: center;
            font-size: 1.2em;
            font-weight: 600;
            margin: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            min-width: 200px;
        }
        
        /* Selector de fecha */
        .date-selector {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .date-selector label {
            font-weight: bold;
            margin-right: 10px;
        }
        .date-selector input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }
        .date-selector button {
            padding: 8px 16px;
            margin-left: 10px;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .date-selector button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Gráfica */
        .chart-container {
            width: 90%;
            height: 300px;
            margin: 30px auto;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Mapa */
        .map {
            margin: 30px auto;
            width: 90%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            overflow: hidden;
        }
        iframe {
            width: 100%;
            height: 350px;
            border: none;
            display: block;
        }
        
        /* Reloj */
        .reloj {
            font-size: 1.8rem;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            display: inline-block;
            margin: 0 10px;
        }
        .reloj.muy-caliente {
            color: #d00000;
            background: #ffeaee;
        }
        .reloj.frio {
            color: #0077b6;
            background: #e6f2ff;
        }
        
        /* Botones */
        .time-control {
            margin: 20px 0;
            text-align: center;
        }
        .time-control button {
            font-size: 1em;
            padding: 8px 16px;
            margin: 5px;
            cursor: pointer;
            background: linear-gradient(135deg, #00b4d8 0%, #0077b6 100%);
            color: white;
            border: none;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        .time-control button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* Tabla de datos */
        .data-table {
            margin: 30px auto;
            width: 90%;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        th {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .caliente { 
            color: #d00000;
            font-weight: bold;
            background: #ffeaee;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .frio { 
            color: #0077b6;
            font-weight: bold;
            background: #e6f2ff;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .normal { 
            color: #2d6a4f;
            font-weight: bold;
            background: #e8f5e9;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                margin: 10px auto;
            }
            .info {
                flex-direction: column;
            }
            .info div {
                width: 80%;
                margin: 10px auto;
            }
            .chart-container, .map, .data-table {
                width: 95%;
            }
            .time-control button {
                padding: 6px 12px;
                font-size: 0.9em;
            }
            .date-selector {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .date-selector button {
                margin-top: 10px;
                margin-left: 0;
            }
        }
    </style>
</head>
<body ng-controller="DetallesController">
    <div class="container">
        <h1>{{ localidad }}</h1>
        
        <div class="info">
            <div>
                🌡️ Temperatura: {{ temperatura }}°C
            </div>
            <div>
                💧 Humedad: {{ humedad }}%
            </div>
        </div>
        
        <div class="time-control">
            🕒 <span class="reloj" ng-class="{'muy-caliente': temperatura > 30, 'frio': temperatura < 10}">{{ hora }}</span>
            <button ng-click="cambiarHora(-1)">-1 Hora</button>
            <button ng-click="cambiarHora(1)">+1 Hora</button>
            <button ng-click="exportarCSV()">⬇️ Exportar CSV</button>
        </div>
        
        <!-- Selector de fecha -->
        <div class="date-selector">
            <label for="fecha">Ver datos de:</label>
            <input type="date" id="fecha" ng-model="fechaSeleccionada" max="{{ fechaActual }}">
            <button ng-click="cargarDatosFecha()">Cargar Datos</button>
        </div>
        
        <!-- Gráfica de líneas -->
        <div class="chart-container">
            <canvas id="grafica"></canvas>
        </div>
        
        <!-- Tabla de datos (mostrando solo 10 registros) -->
        <div class="data-table">
            <h2 style="text-align: center; color: #0077b6; margin-bottom: 20px;">📊 Datos Climáticos Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Temperatura (°C)</th>
                        <th>Humedad (%)</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr ng-repeat="dato in datosFecha | orderBy:'fecha':true | orderBy:'hora':true | limitTo:10">
                        <td>{{ dato.fecha }}</td>
                        <td>{{ dato.hora }}</td>
                        <td>{{ dato.temperatura }}</td>
                        <td>{{ dato.humedad }}</td>
                        <td>
                            <span ng-class="{
                                'caliente': dato.temperatura > 30,
                                'frio': dato.temperatura < 10,
                                'normal': dato.temperatura >= 10 && dato.temperatura <= 30
                            }">
                                {{ dato.estado }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Mapa -->
        <div class="map">
            <iframe ng-src="{{ mapaUrl }}" frameborder="0" allowfullscreen></iframe>
        </div>
    </div>

    <script>
        angular.module('detallesApp', [])
        .controller('DetallesController', function($scope, $interval, $http, $sce) {
            // Datos iniciales con coordenadas de Tepetlaoxtoc
            const localidadesCoordenadas = {
                "Benito Juárez": "19.5804239,-98.4248792",
                "Santiago Cuauala": "19.5991176,-98.6494626",
                "Calpulalpan Tlaxcala": "19.589062,-98.567933",
                "Españita": "19.4627053,-98.4261432",
                "Nanacamilpa": "19.4947082,-98.5324636",
                "Sanctórum": "19.4919091,-98.4703065",
                "Chimalpa": "19.6641539,-98.5123853",
                "Sahagún": "19.7814059,-98.5859219",
                "Ocotlán Tlaxcala": "19.3103429,-98.2125592",
                "Tepetlaoxtoc": "19.5744,-98.8197"  // Coordenadas de Tepetlaoxtoc
            };
            
            $scope.localidad = new URLSearchParams(window.location.search).get('localidad') || "Benito Juárez";
            
            // Validar localidad
            if (!localidadesCoordenadas[$scope.localidad]) {
                $scope.localidad = "Benito Juárez";
            }
            
            // Configuración inicial
            $scope.hora = new Date().toLocaleTimeString('es-MX', { hour12: true });
            $scope.humedad = 50;
            $scope.temperatura = 20;
            $scope.historicoDatos = [];
            $scope.datosFecha = [];
            $scope.fechaActual = new Date().toISOString().split('T')[0];
            $scope.fechaSeleccionada = $scope.fechaActual;
            $scope.mapaUrl = $sce.trustAsResourceUrl(
                `https://www.google.com/maps?q=${localidadesCoordenadas[$scope.localidad]}&output=embed`
            );
            
            // Generar datos de prueba para días anteriores
            function generarDatosHistoricos() {
                const datos = [];
                const hoy = new Date();
                
                // Generar datos para los últimos 7 días
                for (let d = 0; d < 7; d++) {
                    const fecha = new Date();
                    fecha.setDate(hoy.getDate() - d);
                    const fechaStr = fecha.toISOString().split('T')[0];
                    
                    // Generar 24 registros por día (uno por hora)
                    for (let h = 0; h < 24; h++) {
                        const temp = Math.floor(Math.random() * 35) + 5;
                        const hum = Math.floor(Math.random() * 60) + 20;
                        let estado = "Normal";
                        
                        if (temp > 30) estado = "Calor extremo";
                        else if (temp < 10) estado = "Frío extremo";
                        
                        datos.push({
                            fecha: fechaStr,
                            hora: h.toString().padStart(2, '0') + ':00',
                            temperatura: temp,
                            humedad: hum,
                            estado: estado
                        });
                    }
                }
                
                return datos;
            }
            
            $scope.datosHistoricos = generarDatosHistoricos();
            
            // Cargar datos para la fecha seleccionada
            $scope.cargarDatosFecha = function() {
                $scope.datosFecha = $scope.datosHistoricos.filter(d => d.fecha === $scope.fechaSeleccionada);
                actualizarGrafica();
            };
            
            // Inicializar gráfica de líneas
            const ctx = document.getElementById('grafica').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Temperatura (°C)',
                            data: [],
                            borderColor: '#ff758f',
                            backgroundColor: 'rgba(255, 117, 143, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Humedad (%)',
                            data: [],
                            borderColor: '#00b4d8',
                            backgroundColor: 'rgba(0, 180, 216, 0.1)',
                            borderWidth: 2,
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: false,
                            ticks: {
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
            
            // Función para actualizar gráfica
            function actualizarGrafica() {
                const datosMostrar = $scope.datosFecha.slice().sort((a, b) => a.hora.localeCompare(b.hora));
                
                chart.data.labels = datosMostrar.map(d => d.hora);
                chart.data.datasets[0].data = datosMostrar.map(d => d.temperatura);
                chart.data.datasets[1].data = datosMostrar.map(d => d.humedad);
                chart.update();
            }
            
            // Simular datos climáticos en tiempo real
            function generarDatoActual() {
                const ahora = new Date();
                const nuevoDato = {
                    fecha: ahora.toISOString().split('T')[0],
                    hora: ahora.toLocaleTimeString('es-MX', { hour12: true }),
                    temperatura: Math.floor(Math.random() * 35) + 5,
                    humedad: Math.floor(Math.random() * 60) + 20,
                    estado: "Normal"
                };
                
                // Determinar estado
                if (nuevoDato.temperatura > 30) nuevoDato.estado = "Calor extremo";
                else if (nuevoDato.temperatura < 10) nuevoDato.estado = "Frío extremo";
                
                // Actualizar scope
                $scope.temperatura = nuevoDato.temperatura;
                $scope.humedad = nuevoDato.humedad;
                
                // Guardar en histórico (máx 10 registros)
                $scope.historicoDatos.unshift(nuevoDato);
                if ($scope.historicoDatos.length > 10) {
                    $scope.historicoDatos.pop();
                }
                
                // Si es la fecha actual, actualizar también datosFecha
                if (nuevoDato.fecha === $scope.fechaSeleccionada) {
                    $scope.datosFecha.unshift(nuevoDato);
                    // Mantener solo los 10 registros más recientes
                    if ($scope.datosFecha.length > 10) {
                        $scope.datosFecha.pop();
                    }
                    actualizarGrafica();
                }
            }
            
            // Temporizador para datos en tiempo real
            $interval(generarDatoActual, 5000);
            
            // Reloj en tiempo real
            $interval(function() {
                $scope.hora = new Date().toLocaleTimeString('es-MX', { hour12: true });
            }, 1000);
            
            // Cambiar hora manualmente
            $scope.cambiarHora = function(cambio) {
                const ahora = new Date();
                ahora.setHours(ahora.getHours() + cambio);
                $scope.hora = ahora.toLocaleTimeString('es-MX', { hour12: true });
            };
            
            // Exportar a CSV
            $scope.exportarCSV = function() {
                let csv = "Fecha,Hora,Temperatura,Humedad,Estado\n";
                $scope.datosFecha.forEach(d => {
                    csv += `"${d.fecha}","${d.hora}",${d.temperatura},${d.humedad},"${d.estado}"\n`;
                });
                
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `datos_${$scope.localidad}_${$scope.fechaSeleccionada}.csv`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };
            
            // Carga inicial
            generarDatoActual();
            $scope.cargarDatosFecha();
        });
    </script>
</body>
</html>