<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: temas.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db_connect.php';
    
    $nombre = $_POST['username'];
    $apellido = $_POST['apellido'];
    $numero_control = $_POST['numero_control'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Verificar si el número de control ya existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM alumnos WHERE numero_control = ?");
    $stmt->execute([$numero_control]);
    if($stmt->fetchColumn() > 0) {
        $error = "Este número de control ya está registrado";
    } else {
        $stmt = $pdo->prepare("INSERT INTO alumnos (nombre, apellido, numero_control, password) VALUES (?, ?, ?, ?)");
        if($stmt->execute([$nombre, $apellido, $numero_control, $password])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_type'] = 'alumno';
            header("Location: perfil_alumno.php");
            exit;
        } else {
            $error = "Error al registrar. Por favor, intente nuevamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Alumno</title>
    <link rel="stylesheet" href="registro.css">
</head>
<body>
    <div class="login-container">
        <h1>Biokids</h1>
        <h2>Registro Alumno</h2>
        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form id="loginForm" method="POST" action="">
            <div class="input-group">
                <input type="text" id="username" name="username" required placeholder=" ">
                <label for="username">Nombre(s):</label>
            </div>
            <div class="input-group">
                <input type="text" id="apellido" name="apellido" required placeholder=" ">
                <label for="apellido">Apellido:</label>
            </div>
            <div class="input-group">
                <input type="text" id="numero_control" name="numero_control" required placeholder=" ">
                <label for="numero_control">Número de Control:</label>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Contraseña:</label>
            </div>
            <button type="submit" class="btn">Crear Cuenta</button>
        </form>
        <p><a href="inicioalumnos.php">Ya tengo una cuenta</a></p>
    </div>
</body>
</html>