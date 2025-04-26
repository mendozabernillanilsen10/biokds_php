<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update']) && isset($_POST['id'])) {
        // Actualizar categoría existente
        $update_id = $_POST['id'];
        $new_name = trim($_POST['nombre']);
        if (!empty($new_name)) {
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\s]/', '', $new_name)));

            // Verificar si otro registro tiene este slug
            $check = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE slug = ? AND id != ?");
            $check->execute([$slug, $update_id]);
            $slugExists = $check->fetchColumn();

            if ($slugExists) {
                $slug .= '-' . rand(1, 999);
            }

            $stmt = $pdo->prepare("UPDATE categorias SET nombre = ?, slug = ? WHERE id = ?");
            $stmt->execute([$new_name, $slug, $update_id]);
            header("Location: modulo_categorias.php");
            exit;
        }
    } else {
        // Crear nueva categoría
        $nombre = trim($_POST['nombre']);
        if (!empty($nombre)) {
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\s]/', '', $nombre)));

            $check = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE slug = ?");
            $check->execute([$slug]);
            $slugExists = $check->fetchColumn();

            if ($slugExists) {
                $slug .= '-' . rand(1, 999);
            }

            $stmt = $pdo->prepare("INSERT INTO categorias (nombre, slug) VALUES (?, ?)");
            $stmt->execute([$nombre, $slug]);
            header("Location: modulo_categorias.php");
            exit;
        }
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: modulo_categorias.php");
    exit;
}

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
    $stmt->execute([$edit_id]);
    $categoria_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$categorias = $pdo->query("SELECT * FROM categorias")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Categorías</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0,0,0,0.08);
        }

        h1 {
            color: #333;
        }

        form {
            margin-bottom: 25px;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        button:hover {
            background-color: #218838;
        }

        .cancel-btn {
            background-color: #6c757d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .delete-btn {
            background-color: #dc3545;
            border: none;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .delete-btn:hover {
            background-color: #bd2130;
        }

        .edit-btn {
            background-color: #ffc107;
            border: none;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 6px;
        }

        .edit-btn:hover {
            background-color: #e0a800;
        }

        @media (max-width: 600px) {
            input[type="text"], button {
                width: 100%;
            }

            th, td {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestión de Categorías</h1>
        <a href="panel_docente.php" style="text-decoration:none; display:inline-flex; align-items:center; margin-bottom:15px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="#007bff" viewBox="0 0 16 16" style="margin-right:6px;">
        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 1-.5.5H3.707l4.147 4.146a.5.5 0 0 1-.708.708l-5-5a.5.5 0 0 1 0-.708l5-5a.5.5 0 0 1 .708.708L3.707 7.5H14.5A.5.5 0 0 1 15 8z"/>
    </svg>
    <span style="color:#007bff; font-weight:bold;">Volver</span>
</a>

        <form method="POST">
            <input type="hidden" name="id" value="<?= isset($categoria_edit) ? $categoria_edit['id'] : '' ?>">
            <input type="text" name="nombre" placeholder="Nombre de la categoría" required value="<?= isset($categoria_edit) ? htmlspecialchars($categoria_edit['nombre']) : '' ?>">
            <?php if (isset($categoria_edit)): ?>
                <button type="submit" name="update">Actualizar Categoría</button>
                <a href="modulo_categorias.php"><button type="button" class="cancel-btn">Cancelar</button></a>
            <?php else: ?>
                <button type="submit">Agregar Categoría</button>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?= $cat['id'] ?></td>
                        <td><?= htmlspecialchars($cat['nombre']) ?></td>
                        <td>
                            <a href="?edit=<?= $cat['id'] ?>">
                                <button class="edit-btn">Editar</button>
                            </a>
                            <a href="?delete=<?= $cat['id'] ?>" onclick="return confirm('¿Eliminar esta categoría?')">
                                <button class="delete-btn">Eliminar</button>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
