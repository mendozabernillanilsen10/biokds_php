<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'alumno') {
    header("Location: perfil_alumno.php");
    exit;
}

require_once 'db_connect.php';

// Obtener información del alumno
$stmt = $pdo->prepare("SELECT * FROM alumnos WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$alumno = $stmt->fetch();

// Obtener actividades completadas por el alumno con el nombre de la actividad
$stmt = $pdo->prepare("
    SELECT ac.*, a.titulo as nombre_actividad 
    FROM actividades_completadas ac
    LEFT JOIN actividades a ON ac.actividad_id = a.id
    WHERE ac.alumno_id = ? 
    ORDER BY ac.fecha_completado DESC
");
$stmt->execute([$_SESSION['user_id']]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular puntaje total
$puntaje_total = 0;
foreach($actividades as $actividad) {
    if ($actividad['puntaje'] !== null) {
        $puntaje_total += $actividad['puntaje'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
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
        .info-item {
            margin-bottom: 10px;
        }
        .info-item span {
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 10px;
            background-color: #4caf50;
            color: white;
            margin-right: 5px;
            margin-bottom: 5px;
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
        <h1>Mi Perfil</h1>
        <p>Bienvenido, <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?></p>
    </header>
    
    <nav>
        <div>
            <a href="actividades.php">Actividades</a>
     
        </div>
        
        <div>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="card">
            <h2>Información Personal</h2>
            <div class="info-item">
                <span>Nombre:</span> <?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']); ?>
            </div>
            <div class="info-item">
                <span>Número de Control:</span> <?php echo htmlspecialchars($alumno['numero_control']); ?>
            </div>
            <div class="info-item">
                <span>Fecha de Registro:</span> <?php echo date('d/m/Y', strtotime($alumno['fecha_registro'])); ?>
            </div>
            <div class="info-item">
                <span>Puntaje Total:</span> <?php echo $puntaje_total; ?> puntos
            </div>
        </div>
        
        <div class="card">
            <h2>Mis Actividades</h2>
            <?php if(count($actividades) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Actividad</th>
                            <th>Puntaje</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($actividades as $actividad): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($actividad['nombre_actividad'] ? $actividad['nombre_actividad'] : 'Actividad #'.$actividad['actividad_id']); ?></td>
                            <td><?php echo $actividad['puntaje'] !== null ? htmlspecialchars($actividad['puntaje']) : '0'; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($actividad['fecha_completado'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aún no has completado ninguna actividad.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Logros</h2>
            <?php if($puntaje_total >= 100): ?>
                <div class="badge">Explorador Avanzado</div>
            <?php endif; ?>
            <?php if($puntaje_total >= 50): ?>
                <div class="badge">Aprendiz de la Naturaleza</div>
            <?php endif; ?>
            <?php if($puntaje_total >= 10): ?>
                <div class="badge">Principiante</div>
            <?php endif; ?>
            <?php if(count($actividades) == 0): ?>
                <p>¡Completa actividades para desbloquear logros!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>