<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: inicio.php");
    exit;
}

$user_name = $_SESSION['user_name'];

// Incluir configuración de base de datos
require_once 'db_connect.php';

// Obtener todas las categorías
$stmt = $pdo->query('SELECT * FROM categorias ORDER BY id');
$categorias = $stmt->fetchAll();

// Determinar qué categoría está activa (por defecto la primera)
$categoria_activa = isset($_GET['categoria']) ? $_GET['categoria'] : $categorias[0]['slug'];

// Registrar la actividad completada
if (isset($_POST['formulario-actividad'])) {
    if (isset($_POST['actividad_id']) && isset($_POST['puntaje'])) {
        $actividad_id = $_POST['actividad_id'];
        $alumno_id = $_SESSION['user_id'];
        $puntaje = $_POST['puntaje'];

        // Verificar si la actividad ya fue completada
        $stmt = $pdo->prepare('SELECT * FROM actividades_completadas WHERE alumno_id = ? AND actividad_id = ?');
        $stmt->execute([$alumno_id, $actividad_id]);
        $actividad_completada = $stmt->fetch();

        if ($actividad_completada) {
            echo "<script>alert('Ya completaste esta actividad.');</script>";
        } else {
            // Insertar la actividad completada en la base de datos
            $stmt = $pdo->prepare('INSERT INTO actividades_completadas (alumno_id, actividad_id, puntaje, fecha_completado) VALUES (?, ?, ?, NOW())');
            if ($stmt->execute([$alumno_id, $actividad_id, $puntaje])) {
                echo "<script>alert('Actividad registrada correctamente.');</script>";
            } else {
                echo "<script>alert('Error al registrar la actividad: " . $pdo->errorInfo()[2] . "');</script>";
            }
            
            // Redirigir a la misma página para evitar resubir el formulario
            header("Location: perfil_alumno.php?categoria=" . $categoria_activa);
            exit;
        }
    } else {
        echo "<script>alert('Faltan parámetros en el formulario.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades de Naturaleza</title>
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-image: url("Biology.jpg.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 20px;
        }

        .user-info {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 5px;
        }

        .menu {
            margin-bottom: 20px;
        }

        .menu button {
            background-color: hsl(172, 94%, 51%);
            color: #161616;
            border: none;
            padding: 15px 32px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            border-radius: 5px;
        }

        .menu button:hover {
            background-color: #45a049;
        }

        .seccion {
            display: none;
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .active {
            display: block;
        }

        .actividad {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 20px;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .opcion {
            display: block;
            margin: 10px auto;
            padding: 10px 20px;
            font-size: 18px;
            background-color: #81c784;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        .opcion:hover {
            background-color: #66bb6a;
        }

        .correcto {
            background-color: rgba(144, 238, 144, 0.3);
            border: 2px solid green;
        }

        .incorrecto {
            background-color: rgba(255, 99, 71, 0.3);
            border: 2px solid red;
        }

        .submit-btn {
            display: block;
            margin: 20px auto;
            background-color: #4CAF50;
            color: white;
        }

        /* Solucionar el problema de las tablas extras */
        table {
            display: none;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Bienvenido, <?php echo htmlspecialchars($user_name); ?> | 
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <div class="menu">
        <?php foreach ($categorias as $categoria): ?>
            <button onclick="mostrarSeccion('<?php echo $categoria['slug']; ?>')"><?php echo htmlspecialchars($categoria['nombre']); ?></button>
        <?php endforeach; ?>
    </div>

    <?php
    // Mostrar cada sección con sus actividades
    foreach ($categorias as $categoria): 
        // Obtener las actividades de esta categoría
        $stmt = $pdo->prepare('SELECT * FROM actividades WHERE categoria_id = ?');
        $stmt->execute([$categoria['id']]);
        $actividades = $stmt->fetchAll();
        
        // Determinar si esta sección es la activa
        $es_activa = ($categoria['slug'] === $categoria_activa) ? 'active' : '';
    ?>
    <div id="<?php echo $categoria['slug']; ?>" class="seccion <?php echo $es_activa; ?>">
        <h1>Actividades de <?php echo htmlspecialchars($categoria['nombre']); ?></h1>
        
        <?php if (empty($actividades)): ?>
            <p>No hay actividades disponibles para esta categoría.</p>
        <?php else: ?>
            <?php foreach ($actividades as $actividad): ?>
                <?php
                // Verificar si la actividad ya fue completada
                $stmt = $pdo->prepare('SELECT * FROM actividades_completadas WHERE alumno_id = ? AND actividad_id = ?');
                $stmt->execute([$_SESSION['user_id'], $actividad['id']]);
                $actividad_completada = $stmt->fetch();
                
                // Solo mostrar actividades que no han sido completadas
                if (!$actividad_completada):
                ?>
                    <div class="actividad">
                        <h2><?php echo htmlspecialchars($actividad['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars($actividad['contenido']); ?></p>
                        
                        <div id="quiz-<?php echo $actividad['id']; ?>" class="quiz-container">
                            <form method="post" class="actividad-form">
                                <input type="hidden" name="formulario-actividad" value="1">
                                <input type="hidden" name="actividad_id" value="<?php echo $actividad['id']; ?>">
                                <input type="hidden" name="puntaje" value="0" class="puntaje-input">
                                
                                <?php 
                                // Obtener las opciones del quiz
                                $stmt = $pdo->prepare('SELECT * FROM opciones WHERE actividad_id = ?');
                                $stmt->execute([$actividad['id']]);
                                $opciones = $stmt->fetchAll();
                                foreach ($opciones as $opcion): 
                                ?>
                                    <div class="opcion-container">
                                        <?php if ($actividad['tipo'] == 'quiz'): ?>
                                            <label class="verdaderofalso">
                                                <input type="radio" name="respuesta-<?php echo $actividad['id']; ?>" 
                                                       data-correcta="<?php echo $opcion['correcta']; ?>"
                                                       onclick="evaluarOpcion(this, <?php echo $actividad['id']; ?>)">
                                                <?php echo htmlspecialchars($opcion['opcion']); ?>
                                            </label>

                                        <?php elseif ($actividad['tipo'] == 'arrastrar'): ?>
                                            <div class="opcion" draggable="true" data-correcta="<?php echo $opcion['correcta']; ?>"
                                                 ondragstart="event.dataTransfer.setData('text/plain', this.dataset.correcta)">
                                                <?php echo htmlspecialchars($opcion['opcion']); ?>
                                            </div>
                                        <?php elseif ($actividad['tipo'] == 'verdaderofalso'): ?>
                                            <label class="verdaderofalso">
                                                <input type="radio" name="respuesta-<?php echo $actividad['id']; ?>" 
                                                       data-correcta="<?php echo $opcion['correcta']; ?>"
                                                       onclick="evaluarOpcion(this, <?php echo $actividad['id']; ?>)">
                                                <?php echo htmlspecialchars($opcion['opcion']); ?>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <button type="submit" class="opcion submit-btn">Finalizar y Enviar</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div style="margin-top: 40px;">
        <button onclick="irAOtraVentana()" style="background-color: #2196F3; padding: 12px 24px; font-size: 16px; border: none; border-radius: 5px; color: white; cursor: pointer;">
            Regresar a perfil_alumno
        </button>
    </div>

    <script>
        function evaluarOpcion(elemento, actividadId) {
            const esCorrecta = elemento.dataset.correcta === '1';
            const quizContainer = document.getElementById(`quiz-${actividadId}`);
            const puntajeInput = quizContainer.querySelector('.puntaje-input');
        
        }

        function mostrarSeccion(id) {
            const secciones = document.querySelectorAll('.seccion');
            secciones.forEach(seccion => seccion.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            
            // Actualizar la URL para mantener la categoría activa
            window.history.replaceState(null, null, `?categoria=${id}`);
        }

        function irAOtraVentana() {
            window.location.href = 'perfil_alumno.php';
        }
    </script>
</body>
</html>