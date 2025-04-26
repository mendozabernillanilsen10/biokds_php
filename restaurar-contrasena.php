
<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: temas.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db_connect.php';
    
    $email = $_POST['email'];
    
    // Aquí normalmente enviarías un correo electrónico para restaurar la contraseña
    // Por simplicidad, mostramos un mensaje
    $mensaje = "Si el correo existe en nuestra base de datos, recibirá instrucciones para restaurar su contraseña.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Contraseña</title>
    <link rel="stylesheet" href="registro.css">
</head>
<body>
    <div class="login-container">
        <h1>Biokids</h1>
        <h2>Restaurar Contraseña</h2>
        <?php if(isset($mensaje)): ?>
            <div class="mensaje"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <form id="resetForm" method="POST" action="">
            <div class="input-group">
                <input type="email" id="email" name="email" required placeholder=" ">
                <label for="email">Correo Electrónico:</label>
            </div>
            <button type="submit" class="btn">Enviar Instrucciones</button>
        </form>
        <p><a href="inicio.php">Volver al inicio de sesión</a></p>
    </div>
</body>
</html>