<?php
session_start();
if(!isset($_SESSION['user_id'])) {
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Naturaleza</title>
    <style>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #4caf50;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #4caf50;
            color: white;
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
    // Mostrar cada sección con su contenido
    foreach ($categorias as $categoria): 
        // Obtener el contenido de esta categoría
        $stmt = $pdo->prepare('SELECT * FROM contenidos WHERE categoria_id = ? ORDER BY orden');
        $stmt->execute([$categoria['id']]);
        $contenidos = $stmt->fetchAll();
        
        // Determinar si esta sección es la activa
        $es_activa = ($categoria['slug'] === $categoria_activa) ? 'active' : '';
    ?>
    <div id="<?php echo $categoria['slug']; ?>" class="seccion <?php echo $es_activa; ?>">
        <h1><?php echo htmlspecialchars($categoria['nombre']); ?></h1>
        
        <?php foreach ($contenidos as $contenido): ?>
            <?php if (!empty($contenido['titulo'])): ?>
                <h2><?php echo htmlspecialchars($contenido['titulo']); ?></h2>
            <?php endif; ?>
            
            <?php echo $contenido['contenido']; ?>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- Botón para ir a actividades -->
    <div style="margin-top: 40px;">
        <button onclick="irAOtraVentana()" style="background-color: #2196F3; padding: 12px 24px; font-size: 16px; border: none; border-radius: 5px; color: white; cursor: pointer;">
            Ir a actividades
        </button>
    </div>

    <script>
        function mostrarSeccion(id) {
            const secciones = document.querySelectorAll('.seccion');
            secciones.forEach(seccion => seccion.classList.remove('active'));
            document.getElementById(id).classList.add('active');
        }

        function irAOtraVentana() {
            window.location.href = "actividades.php";
        }
    </script>
</body>
</html>