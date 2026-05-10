<!DOCTYPE html>
<html lang="es" ng-app="localidadesApp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Localidades</title>
    
    <!-- Carga de AngularJS -->
    <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
    
    <style>
        /* Estilos con mejor contraste en sombras */
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        h1 {
            background: #0077b6;
            color: white;
            padding: 20px;
            margin: 0;
            text-align: center;
            font-size: 1.8em;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            padding: 25px;
        }
        .card {
            width: 180px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25);
        }
        .card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
            border-bottom: 1px solid #e0e0e0;
        }
        .card-content {
            padding: 10px;
            text-align: center;
        }
        .card-title {
            color: #2d3748;
            font-weight: bold;
            font-size: 1em;
            margin-bottom: 8px;
        }
        .card-data {
            font-size: 0.85em;
            color: #4a5568;
            margin: 3px 0;
        }
        .card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .card a:hover .card-title {
            color: #0077b6;
        }
        .temp-high {
            color: #d00000;
            font-weight: bold;
        }
        .temp-low {
            color: #0077b6;
            font-weight: bold;
        }
    </style>
</head>
<body ng-controller="LocalidadesController">
    <div class="container">
        <h1>Localidades</h1>
        <div class="grid">
            <!-- Iteración sobre cada localidad usando AngularJS -->
            <div class="card" ng-repeat="localidad in localidades">
                <!-- Enlace que cubre toda la tarjeta -->
                <a href="detalle.php?localidad={{localidad.nombre}}">
                    <img ng-src="{{localidad.imagen}}" alt="{{localidad.nombre}}">
                    <div class="card-content">
                        <p class="card-title">{{localidad.nombre}}</p>
                        <p class="card-data" ng-class="{'temp-high': localidad.temperatura > 30, 'temp-low': localidad.temperatura < 10}">
                            🌡️ {{localidad.temperatura}}°C
                        </p>
                        <p class="card-data">💧 {{localidad.humedad}}%</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Definición del módulo AngularJS y el controlador
        angular.module('localidadesApp', [])
        .controller('LocalidadesController', function($scope) {
            // Lista de localidades con sus imágenes y datos climáticos simulados
            $scope.localidades = [
                {
                    "nombre": "Benito Juárez", 
                    "imagen": "img/juarez.jpg",
                    "temperatura": 28,
                    "humedad": 45
                },
                {
                    "nombre": "Santiago Cuauala", 
                    "imagen": "img/cuaula.png",
                    "temperatura": 24,
                    "humedad": 60
                },
                {
                    "nombre": "Calpulalpan Tlaxcala", 
                    "imagen": "img/calpulalpan.jpg",
                    "temperatura": 18,
                    "humedad": 70
                },
                {
                    "nombre": "Españita", 
                    "imagen": "img/aeiou.jpg",
                    "temperatura": 32,
                    "humedad": 40
                },
                {
                    "nombre": "Nanacamilpa", 
                    "imagen": "img/milpa.png",
                    "temperatura": 15,
                    "humedad": 75
                },
                {
                    "nombre": "Sanctórum", 
                    "imagen": "img/santorum.jpg",
                    "temperatura": 22,
                    "humedad": 65
                },
                {
                    "nombre": "Sahagún", 
                    "imagen": "img/sahagun.jpg",
                    "temperatura": 26,
                    "humedad": 55
                },
                {
                    "nombre": "Chimalpa", 
                    "imagen": "img/chimalpa.jpg",
                    "temperatura": 20,
                    "humedad": 68
                },
                {
                    "nombre": "Ocotlán Tlaxcala", 
                    "imagen": "img/ocotlan.jpg",
                    "temperatura": 17,
                    "humedad": 72
                }
            ];

            // Función para actualizar datos climáticos periódicamente (simulación)
            setInterval(function() {
                $scope.localidades.forEach(function(localidad) {
                    // Variación aleatoria de ±2°C y ±5% de humedad
                    localidad.temperatura += Math.floor(Math.random() * 5) - 2;
                    localidad.humedad += Math.floor(Math.random() * 10) - 5;
                    
                    // Asegurar valores dentro de rangos razonables
                    localidad.temperatura = Math.max(5, Math.min(40, localidad.temperatura));
                    localidad.humedad = Math.max(20, Math.min(90, localidad.humedad));
                });
                $scope.$apply(); // Actualizar la vista
            }, 5000); // Actualizar cada 5 segundos
        });
    </script>
</body>
</html>