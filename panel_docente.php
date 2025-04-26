<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'docente') {
    header("Location: inicio.php");
    exit;
}

require_once 'db_connect.php';

// Obtener lista de alumnos
$stmt = $pdo->query("SELECT * FROM alumnos ORDER BY nombre, apellido");
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades completadas
$stmt = $pdo->query("
    SELECT ac.*, a.nombre, a.apellido, a.numero_control 
    FROM actividades_completadas ac
    JOIN alumnos a ON ac.alumno_id = a.id
    ORDER BY ac.fecha_completado DESC
");
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Docente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: #4caf50;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
        nav {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            background-color: #f1f1f1;
            border-bottom: 1px solid #ddd;
        }
        nav a {
            text-decoration: none;
            color: #333;
            padding: 10px 15px;
        }
        nav a:hover {
            background-color: #ddd;
        }
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4caf50;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <header>
        <h1>Panel del Docente</h1>
        <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
    </header>
    
    <nav>
        <div>
            <a href="#alumnos">Alumnos</a>
            <a href="modulo_categorias.php">Registro de categoria</a>
            <a href="modulo_actividades.php">Actividades</a>
        </div>
        <div>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="card" id="alumnos">
            <h2>Lista de Alumnos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Número de Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $alumno): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alumno['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($alumno['numero_control']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card" id="actividades">
            <h2>Actividades Completadas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Alumno</th>
                        <th>Actividad</th>
                        <th>Puntaje</th>
                        <th>Fecha Completado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actividades as $act): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($act['nombre'] . ' ' . $act['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($act['actividad']); ?></td>
                            <td><?php echo $act['puntaje']; ?></td>
                            <td><?php echo $act['fecha_completado']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
