<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: temas.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db_connect.php';
    
    $numero_control = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM alumnos WHERE numero_control = ?");
    $stmt->execute([$numero_control]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_type'] = 'alumno';
        header("Location: perfil_alumno.php");
        exit;
    }
    
    $error = "Número de control o contraseña incorrectos";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="inicioalumnos.css">
</head>
<div class="img-container">
    <img src="biologia.png" alt="Imagen 1" class="img-sep">
</div>
<body>
    <div class="login-container">
        <h1>Biokids</h1>
        <h2>Inicio de Sesión</h2>
        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form id="loginForm" method="POST" action="">
            <div class="input-group">
                <input type="text" id="username" name="username" required placeholder=" ">
                <label for="username">Número de Control:</label>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Contraseña:</label>
            </div>
            <button type="submit" class="btn">Iniciar sesión</button>
        </form>
        <p><a href="registroalumno.php">Registrarse</a></p>
        <p><a href="restaurar-contrasena.php">¿Olvidaste tu contraseña?</a></p>
        <p><a href="inicio.php">Iniciar como docente</a></p>
    </div>
</body>
</html>