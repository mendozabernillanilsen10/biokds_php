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
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM docentes WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetchColumn() > 0) {
        $error = "Este correo electrónico ya está registrado";
    } else {
        $stmt = $pdo->prepare("INSERT INTO docentes (nombre, apellido, email, password) VALUES (?, ?, ?, ?)");
        if($stmt->execute([$nombre, $apellido, $email, $password])) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_type'] = 'docente';
            header("Location: panel_docente.php");
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
    <title>Registro</title>
    <link rel="stylesheet" href="registro.css">
</head>
<body>
    <div class="login-container">
        <h1>Biokids</h1>
        <h2>Registro Docente</h2>
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
                <input type="email" id="email" name="email" required placeholder=" ">
                <label for="email">Correo Electrónico:</label>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" required placeholder=" ">
                <label for="password">Contraseña:</label>
            </div>
            <button type="submit" class="btn">Crear Cuenta</button>
        </form>
        <p><a href="inicio.php">Ya tengo una cuenta</a></p>
    </div>
</body>
</html>