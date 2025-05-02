<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'docente') {
    header("Location: inicio.php");
    exit;
}

require_once 'db_connect.php';

// Configuración para subir archivos
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Función para subir archivos
function subirArchivo($file, $uploadDir) {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid() . '.' . $extension;
    $destino = $uploadDir . $nombreArchivo;
    
    if (move_uploaded_file($file['tmp_name'], $destino)) {
        return $nombreArchivo;
    }
    return null;
}

// Eliminar actividad
if (isset($_GET['eliminar_id'])) {
    $eliminarId = $_GET['eliminar_id'];
    
    // Eliminar imágenes asociadas
    $stmtImg = $pdo->prepare("SELECT imagen_base FROM actividades WHERE id = ?");
    $stmtImg->execute([$eliminarId]);
    $imagenBase = $stmtImg->fetchColumn();
    if ($imagenBase && file_exists($uploadDir . $imagenBase)) {
        unlink($uploadDir . $imagenBase);
    }
    
    $stmtOpciones = $pdo->prepare("SELECT imagen FROM opciones WHERE actividad_id = ?");
    $stmtOpciones->execute([$eliminarId]);
    $imagenesOpciones = $stmtOpciones->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imagenesOpciones as $imagen) {
        if ($imagen && file_exists($uploadDir . $imagen)) {
            unlink($uploadDir . $imagen);
        }
    }
    
    $stmtDel = $pdo->prepare("DELETE FROM actividades WHERE id = ?");
    $stmtDel->execute([$eliminarId]);
    header("Location: modulo_actividades.php");
    exit;
}

// Editar actividad
$actividadEditar = null;
$opcionesEditar = [];
if (isset($_GET['editar_id'])) {
    $editarId = $_GET['editar_id'];
    $stmtEdit = $pdo->prepare("SELECT * FROM actividades WHERE id = ?");
    $stmtEdit->execute([$editarId]);
    $actividadEditar = $stmtEdit->fetch(PDO::FETCH_ASSOC);
    
    if ($actividadEditar) {
        $stmtOpciones = $pdo->prepare("SELECT * FROM opciones WHERE actividad_id = ?");
        $stmtOpciones->execute([$editarId]);
        $opcionesEditar = $stmtOpciones->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Guardar actividad
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $tipo = $_POST['tipo'];
    $contenido = $_POST['contenido'];
    $categoria_id = $_POST['categoria_id'];
    $puntaje = $_POST['puntaje'] ?? 10;
    $titulo_partes = $_POST['titulo_partes'] ?? null;
    
    try {
        $pdo->beginTransaction();
        
        // Procesar imagen base si se subió
        $imagenBase = null;
        if (!empty($_FILES['imagen_base']['name'])) {
            $imagenBase = subirArchivo($_FILES['imagen_base'], $uploadDir);
        } elseif (isset($_POST['actividad_id']) && !empty($actividadEditar['imagen_base'])) {
            $imagenBase = $actividadEditar['imagen_base'];
        }
        
        if (isset($_POST['actividad_id'])) {
            // Actualizar actividad existente
            $actividad_id = $_POST['actividad_id'];
            $stmtUpdate = $pdo->prepare("UPDATE actividades SET titulo = ?, tipo = ?, contenido = ?, categoria_id = ?, puntaje = ?, imagen_base = ?, titulo_partes = ? WHERE id = ?");
            $stmtUpdate->execute([$titulo, $tipo, $contenido, $categoria_id, $puntaje, $imagenBase, $titulo_partes, $actividad_id]);
            
            // Eliminar opciones antiguas
            $stmtDelOpciones = $pdo->prepare("DELETE FROM opciones WHERE actividad_id = ?");
            $stmtDelOpciones->execute([$actividad_id]);
        } else {
            // Insertar nueva actividad
            $stmtInsert = $pdo->prepare("INSERT INTO actividades (titulo, tipo, contenido, categoria_id, puntaje, imagen_base, titulo_partes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$titulo, $tipo, $contenido, $categoria_id, $puntaje, $imagenBase, $titulo_partes]);
            $actividad_id = $pdo->lastInsertId();
        }
        
        // Procesar opciones según el tipo de actividad
        if ($tipo == 'quiz' || $tipo == 'verdaderofalso') {
            $opciones = $_POST['opcion'] ?? [];
            $correctas = $_POST['correcta'] ?? [];
            
            $stmtInsertOpcion = $pdo->prepare("INSERT INTO opciones (actividad_id, opcion, correcta) VALUES (?, ?, ?)");
            
            foreach ($opciones as $index => $opcion) {
                if (!empty($opcion)) {
                    $esCorrecta = in_array($index, $correctas) ? 1 : 0;
                    $stmtInsertOpcion->execute([$actividad_id, $opcion, $esCorrecta]);
                }
            }
        } elseif ($tipo == 'arrastrar') {
            $partes = $_POST['partes'] ?? [];
            
            $stmtInsertOpcion = $pdo->prepare("INSERT INTO opciones (actividad_id, opcion, imagen, pos_x, pos_y, descripcion, correcta) VALUES (?, ?, ?, ?, ?, ?, 1)");
            
            foreach ($partes as $index => $parte) {
                if (!empty($parte['nombre'])) {
                    // Procesar imagen de la parte si se subió
                    $imagenParte = null;
                    if (!empty($_FILES['partes']['name'][$index]['imagen'])) {
                        $file = [
                            'name' => $_FILES['partes']['name'][$index]['imagen'],
                            'type' => $_FILES['partes']['type'][$index]['imagen'],
                            'tmp_name' => $_FILES['partes']['tmp_name'][$index]['imagen'],
                            'error' => $_FILES['partes']['error'][$index]['imagen'],
                            'size' => $_FILES['partes']['size'][$index]['imagen']
                        ];
                        $imagenParte = subirArchivo($file, $uploadDir);
                    } elseif (isset($opcionesEditar[$index]['imagen'])) {
                        $imagenParte = $opcionesEditar[$index]['imagen'];
                    }
                    
                    $stmtInsertOpcion->execute([
                        $actividad_id,
                        $parte['nombre'],
                        $imagenParte,
                        $parte['x'] ?? 0,
                        $parte['y'] ?? 0,
                        $parte['descripcion'] ?? null
                    ]);
                }
            }
        }
        
        $pdo->commit();
        header("Location: modulo_actividades.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al guardar: " . $e->getMessage();
    }
}

// Obtener categorías
$stmtCat = $pdo->query("SELECT * FROM categorias");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades (filtradas por categoría si se especifica)
$categoriaSeleccionada = $_GET['categoria_id'] ?? null;
$sql = "SELECT a.*, c.nombre AS categoria FROM actividades a LEFT JOIN categorias c ON a.categoria_id = c.id";
if ($categoriaSeleccionada) {
    $sql .= " WHERE a.categoria_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoriaSeleccionada]);
} else {
    $stmt = $pdo->query($sql);
}
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Actividades</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        h1, h2 { 
            color: #333;
            margin-bottom: 20px;
        }
        .volver-btn {
            display: inline-block;
            margin-bottom: 20px;
            font-size: 16px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            transition: 0.3s;
        }
        .volver-btn:hover { color: #0056b3; }
        input, textarea, select, button {
            width: 100%;
            padding: 10px 14px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .tipo-actividad {
            margin: 15px 0;
        }
        .tipo-actividad label {
            display: inline-block;
            margin-right: 15px;
            cursor: pointer;
        }
        .tipo-actividad input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .acciones a {
            margin-right: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .acciones a:hover {
            color: #dc3545;
        }
        .opciones-container, .arrastrar-container {
            border: 1px dashed #ccc;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .opcion-item, .parte-item {
            border: 1px solid #eee;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .parte-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .parte-col {
            flex: 1;
        }
        .parte-col label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
            color: #666;
        }
        .parte-desc textarea {
            min-height: 60px;
        }
        .add-opcion-btn, .remove-opcion-btn {
            width: auto;
            padding: 5px 10px;
            font-size: 14px;
        }
        .add-opcion-btn {
            background-color: #28a745;
        }
        .remove-opcion-btn {
            background-color: #dc3545;
        }
        .mensaje-error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .hidden {
            display: none;
        }
        img {
            max-width: 100px;
            max-height: 100px;
        }
        .campo-error {
            border-color: #dc3545 !important;
        }
        .mensaje-campo-error {
            color: #dc3545;
            font-size: 12px;
            margin-top: -5px;
            margin-bottom: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="inicio.php" class="volver-btn"><i class="fas fa-arrow-left"></i> Volver</a>

        <?php if (isset($error)): ?>
            <div class="mensaje-error"><?= $error ?></div>
        <?php endif; ?>

        <h1><?= $actividadEditar ? 'Editar Actividad' : 'Registrar Nueva Actividad' ?></h1>
        <form method="POST" action="modulo_actividades.php" enctype="multipart/form-data" novalidate id="form-actividad">
            <?php if ($actividadEditar): ?>
                <input type="hidden" name="actividad_id" value="<?= $actividadEditar['id'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <input type="text" name="titulo" placeholder="Título de la actividad" required value="<?= isset($actividadEditar['titulo']) ? htmlspecialchars($actividadEditar['titulo']) : '' ?>">
                <div class="mensaje-campo-error" id="error-titulo">Por favor ingresa un título para la actividad</div>
            </div>
            
            <div class="tipo-actividad">
                <p><strong>Tipo de actividad:</strong></p>
                <label>
                    <input type="radio" name="tipo" value="quiz" <?= (isset($actividadEditar) && $actividadEditar['tipo'] == 'quiz') ? 'checked' : '' ?> onchange="mostrarOpcionesPorTipo(this.value)"> 
                    Quiz
                </label>
                <label>
                    <input type="radio" name="tipo" value="arrastrar" <?= (isset($actividadEditar) && $actividadEditar['tipo'] == 'arrastrar') ? 'checked' : '' ?> onchange="mostrarOpcionesPorTipo(this.value)"> 
                    Arrastrar y Soltar
                </label>
                <label>
                    <input type="radio" name="tipo" value="verdaderofalso" <?= (isset($actividadEditar) && $actividadEditar['tipo'] == 'verdaderofalso') ? 'checked' : '' ?> onchange="mostrarOpcionesPorTipo(this.value)"> 
                    Verdadero/Falso
                </label>
                <div class="mensaje-campo-error" id="error-tipo">Por favor selecciona un tipo de actividad</div>
            </div>
            
            <div class="form-group">
                <textarea name="contenido" placeholder="Contenido o instrucciones" required><?= isset($actividadEditar['contenido']) ? htmlspecialchars($actividadEditar['contenido']) : '' ?></textarea>
                <div class="mensaje-campo-error" id="error-contenido">Por favor ingresa el contenido o instrucciones</div>
            </div>
            
            <div class="form-group">
                <label for="puntaje">Puntaje:</label>
                <input type="number" id="puntaje" name="puntaje" min="1" value="<?= isset($actividadEditar['puntaje']) ? $actividadEditar['puntaje'] : '10' ?>">
                <div class="mensaje-campo-error" id="error-puntaje">Por favor ingresa un puntaje válido (mínimo 1)</div>
            </div>
            
            <div class="form-group">
                <select name="categoria_id" required>
                    <option value="">Seleccionar categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (isset($actividadEditar) && $actividadEditar['categoria_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="mensaje-campo-error" id="error-categoria">Por favor selecciona una categoría</div>
            </div>
            
            <!-- Configuración para actividades de arrastrar -->
            <div id="arrastrar-container" class="<?= (!isset($actividadEditar) || $actividadEditar['tipo'] != 'arrastrar') ? 'hidden' : '' ?>">
                <h3>Configuración de actividad de arrastrar</h3>
                
                <div class="form-group">
                    <label>Imagen base (fondo donde se colocarán las partes):</label>
                    <input type="file" name="imagen_base" accept="image/*">
                    <?php if (isset($actividadEditar) && !empty($actividadEditar['imagen_base'])): ?>
                        <p>Imagen actual: <img src="uploads/<?= htmlspecialchars($actividadEditar['imagen_base']) ?>" style="max-width: 200px;"></p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Título para las partes arrastrables (ej: "Partes de la planta", "Órganos", etc.):</label>
                    <input type="text" name="titulo_partes" value="<?= isset($actividadEditar['titulo_partes']) ? htmlspecialchars($actividadEditar['titulo_partes']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label>Partes arrastrables:</label>
                    <div id="partes-container">
                        <?php if (!empty($opcionesEditar) && $actividadEditar['tipo'] == 'arrastrar'): ?>
                            <?php foreach ($opcionesEditar as $index => $opcion): ?>
                                <div class="parte-item">
                                    <div class="parte-row">
                                        <div class="parte-col">
                                            <label>Nombre:</label>
                                            <input type="text" name="partes[<?= $index ?>][nombre]" 
                                                   value="<?= htmlspecialchars($opcion['opcion']) ?>" class="parte-nombre">
                                            <div class="mensaje-campo-error">Por favor ingresa un nombre</div>
                                        </div>
                                        <div class="parte-col">
                                            <label>Imagen (opcional):</label>
                                            <input type="file" name="partes[<?= $index ?>][imagen]" accept="image/*">
                                            <?php if (!empty($opcion['imagen'])): ?>
                                                <img src="uploads/<?= htmlspecialchars($opcion['imagen']) ?>" style="max-width: 50px;">
                                            <?php endif; ?>
                                        </div>
                                        <div class="parte-col">
                                            <label>Posición X:</label>
                                            <input type="number" name="partes[<?= $index ?>][x]" 
                                                   value="<?= $opcion['pos_x'] ?? 0 ?>" step="1" min="0">
                                        </div>
                                        <div class="parte-col">
                                            <label>Posición Y:</label>
                                            <input type="number" name="partes[<?= $index ?>][y]" 
                                                   value="<?= $opcion['pos_y'] ?? 0 ?>" step="1" min="0">
                                        </div>
                                        <div class="parte-col">
                                            <button type="button" class="remove-opcion-btn" onclick="eliminarParte(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="parte-desc">
                                        <label>Descripción (aparece al colocar correctamente):</label>
                                        <textarea name="partes[<?= $index ?>][descripcion]"><?= htmlspecialchars($opcion['descripcion'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="parte-item">
                                <div class="parte-row">
                                    <div class="parte-col">
                                        <label>Nombre:</label>
                                        <input type="text" name="partes[0][nombre]" class="parte-nombre">
                                        <div class="mensaje-campo-error">Por favor ingresa un nombre</div>
                                    </div>
                                    <div class="parte-col">
                                        <label>Imagen (opcional):</label>
                                        <input type="file" name="partes[0][imagen]" accept="image/*">
                                    </div>
                                    <div class="parte-col">
                                        <label>Posición X:</label>
                                        <input type="number" name="partes[0][x]" value="0" step="1" min="0">
                                    </div>
                                    <div class="parte-col">
                                        <label>Posición Y:</label>
                                        <input type="number" name="partes[0][y]" value="0" step="1" min="0">
                                    </div>
                                    <div class="parte-col">
                                        <button type="button" class="remove-opcion-btn" onclick="eliminarParte(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="parte-desc">
                                    <label>Descripción (aparece al colocar correctamente):</label>
                                    <textarea name="partes[0][descripcion]"></textarea>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="add-opcion-btn" onclick="agregarParte()">
                        <i class="fas fa-plus"></i> Agregar parte
                    </button>
                </div>
            </div>
            
            <!-- Opciones para quiz y verdadero/falso -->
            <div id="opciones-container" class="opciones-container <?= (!isset($actividadEditar) || ($actividadEditar['tipo'] != 'quiz' && $actividadEditar['tipo'] != 'verdaderofalso')) ? 'hidden' : '' ?>">                <h3>Opciones de respuesta</h3>
                <p id="opciones-info" class="<?= (!isset($actividadEditar) || $actividadEditar['tipo'] != 'quiz') ? 'hidden' : '' ?>">
                    <strong>Nota:</strong> Para quiz puede marcar múltiples opciones como correctas.
                </p>
                <div id="opciones-items">
                    <?php if (!empty($opcionesEditar) && ($actividadEditar['tipo'] == 'quiz' || $actividadEditar['tipo'] == 'verdaderofalso')): ?>
                        <?php foreach ($opcionesEditar as $index => $opcion): ?>
                            <div class="opcion-item">
                                <input type="text" name="opcion[<?= $index ?>]" placeholder="Opción de respuesta" value="<?= htmlspecialchars($opcion['opcion']) ?>" class="campo-opcion">
                                <div class="mensaje-campo-error">Por favor ingresa esta opción</div>
                                <label>
                                    <input type="checkbox" name="correcta[]" value="<?= $index ?>" <?= $opcion['correcta'] ? 'checked' : '' ?>> 
                                    Correcta
                                </label>
                                <button type="button" class="remove-opcion-btn" onclick="eliminarOpcion(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="opcion-item">
                            <input type="text" name="opcion[0]" placeholder="Opción de respuesta" class="campo-opcion">
                            <div class="mensaje-campo-error">Por favor ingresa esta opción</div>
                            <label>
                                <input type="checkbox" name="correcta[]" value="0"> 
                                Correcta
                            </label>
                            <button type="button" class="remove-opcion-btn" onclick="eliminarOpcion(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="opcion-item">
                            <input type="text" name="opcion[1]" placeholder="Opción de respuesta" class="campo-opcion">
                            <div class="mensaje-campo-error">Por favor ingresa esta opción</div>
                            <label>
                                <input type="checkbox" name="correcta[]" value="1"> 
                                Correcta
                            </label>
                            <button type="button" class="remove-opcion-btn" onclick="eliminarOpcion(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="add-opcion-btn" onclick="agregarOpcion()">
                    <i class="fas fa-plus"></i> Agregar opción
                </button>
            </div>
            
            <button type="submit" id="btn-submit"><?= $actividadEditar ? 'Actualizar' : 'Guardar' ?></button>
        </form>

        <h2>Filtrar Actividades por Categoría</h2>
        <form method="GET">
            <select name="categoria_id" onchange="this.form.submit()">
                <option value="">-- Todas las categorías --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoriaSeleccionada == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <h2>Lista de Actividades</h2>
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Tipo</th>
                    <th>Contenido</th>
                    <th>Puntaje</th>
                    <th>Opciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actividades as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['titulo']) ?></td>
                        <td><?= isset($a['categoria']) ? htmlspecialchars($a['categoria']) : '(Sin categoría)' ?></td>
                        <td><?= htmlspecialchars($a['tipo']) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($a['contenido'], 0, 30, '...')) ?></td>
                        <td><?= $a['puntaje'] ?></td>
                        <td>
                            <?php
                            $stmtOpcCount = $pdo->prepare("SELECT COUNT(*) FROM opciones WHERE actividad_id = ?");
                            $stmtOpcCount->execute([$a['id']]);
                            $opcionesCount = $stmtOpcCount->fetchColumn();
                            echo $opcionesCount . " opciones";
                            
                            if ($opcionesCount > 0 && $a['tipo'] != 'arrastrar') {
                                $stmtOpcCorrect = $pdo->prepare("SELECT COUNT(*) FROM opciones WHERE actividad_id = ? AND correcta = 1");
                                $stmtOpcCorrect->execute([$a['id']]);
                                $correctasCount = $stmtOpcCorrect->fetchColumn();
                                echo " ($correctasCount correctas)";
                            }
                            ?>
                        </td>
                        <td class="acciones">
                            <a href="?editar_id=<?= $a['id'] ?>" title="Editar"><i class="fas fa-edit"></i></a>
                            <a href="?eliminar_id=<?= $a['id'] ?>" onclick="return confirm('¿Estás seguro de eliminar esta actividad?')" title="Eliminar"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <script>
    // Variable para contar opciones
    let contadorOpciones = <?= !empty($opcionesEditar) ? count($opcionesEditar) : 2 ?>;
    let contadorPartes = <?= !empty($opcionesEditar) && isset($actividadEditar) && $actividadEditar['tipo'] == 'arrastrar' ? count($opcionesEditar) : 1 ?>;
    
    // Función para mostrar u ocultar opciones según el tipo de actividad
    function mostrarOpcionesPorTipo(tipo) {
        const opcionesContainer = document.getElementById('opciones-container');
        const arrastrarContainer = document.getElementById('arrastrar-container');
        const opcionesInfo = document.getElementById('opciones-info');
        
        // Ocultar todos los contenedores primero
        opcionesContainer.classList.add('hidden');
        arrastrarContainer.classList.add('hidden');
        opcionesInfo.classList.add('hidden');
        
        // Mostrar el contenedor correspondiente
        if (tipo === 'quiz' || tipo === 'verdaderofalso') {
            opcionesContainer.classList.remove('hidden');
            
            if (tipo === 'quiz') {
                opcionesInfo.classList.remove('hidden');
            }
            
            // Si es verdadero/falso, limitar a dos opciones
            if (tipo === 'verdaderofalso') {
                const opcionesItems = document.getElementById('opciones-items');
                opcionesItems.innerHTML = '';
                
                agregarOpcionPredefinida('Verdadero', 0);
                agregarOpcionPredefinida('Falso', 1);
                
                document.querySelector('.add-opcion-btn').classList.add('hidden');
            } else {
                document.querySelector('.add-opcion-btn').classList.remove('hidden');
            }
        } else if (tipo === 'arrastrar') {
            arrastrarContainer.classList.remove('hidden');
        }
    }
    
    // Agregar opción predefinida (para verdadero/falso)
    function agregarOpcionPredefinida(texto, index) {
        const opcionesItems = document.getElementById('opciones-items');
        const div = document.createElement('div');
        div.className = 'opcion-item';
        div.innerHTML = `
            <input type="text" name="opcion[${index}]" value="${texto}" readonly>
            <label>
                <input type="checkbox" name="correcta[]" value="${index}"> 
                Correcta
            </label>
        `;
        opcionesItems.appendChild(div);
    }
    
    // Agregar una nueva opción
    function agregarOpcion() {
        const opcionesItems = document.getElementById('opciones-items');
        const div = document.createElement('div');
        div.className = 'opcion-item';
        div.innerHTML = `
            <input type="text" name="opcion[${contadorOpciones}]" placeholder="Opción de respuesta" class="campo-opcion">
            <div class="mensaje-campo-error">Por favor ingresa esta opción</div>
            <label>
                <input type="checkbox" name="correcta[]" value="${contadorOpciones}"> 
                Correcta
            </label>
            <button type="button" class="remove-opcion-btn" onclick="eliminarOpcion(this)">
                <i class="fas fa-trash"></i>
            </button>
        `;
        opcionesItems.appendChild(div);
        contadorOpciones++;
    }
    
    // Eliminar una opción
    function eliminarOpcion(btn) {
        const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked')?.value;
        
        if (tipoSeleccionado === 'verdaderofalso') {
            alert('No se pueden eliminar opciones en actividades de Verdadero/Falso');
            return;
        }
        
        const items = document.querySelectorAll('.opcion-item');
        if (items.length <= 2) {
            alert('Una actividad debe tener al menos dos opciones');
            return;
        }
        
        btn.parentElement.remove();
    }
    
    // Agregar una nueva parte arrastrable
    function agregarParte() {
        const partesContainer = document.getElementById('partes-container');
        const div = document.createElement('div');
        div.className = 'parte-item';
        div.innerHTML = `
            <div class="parte-row">
                <div class="parte-col">
                    <label>Nombre:</label>
                    <input type="text" name="partes[${contadorPartes}][nombre]" class="parte-nombre">
                    <div class="mensaje-campo-error">Por favor ingresa un nombre</div>
                </div>
                <div class="parte-col">
                    <label>Imagen (opcional):</label>
                    <input type="file" name="partes[${contadorPartes}][imagen]" accept="image/*">
                </div>
                <div class="parte-col">
                    <label>Posición X:</label>
                    <input type="number" name="partes[${contadorPartes}][x]" value="0" step="1" min="0">
                </div>
                <div class="parte-col">
                    <label>Posición Y:</label>
                    <input type="number" name="partes[${contadorPartes}][y]" value="0" step="1" min="0">
                </div>
                <div class="parte-col">
                    <button type="button" class="remove-opcion-btn" onclick="eliminarParte(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="parte-desc">
                <label>Descripción (aparece al colocar correctamente):</label>
                <textarea name="partes[${contadorPartes}][descripcion]"></textarea>
            </div>
        `;
        partesContainer.appendChild(div);
        contadorPartes++;
    }
    
    // Eliminar una parte arrastrable
    function eliminarParte(btn) {
        const items = document.querySelectorAll('.parte-item');
        if (items.length <= 1) {
            alert('Debe haber al menos una parte arrastrable');
            return;
        }
        btn.closest('.parte-item').remove();
    }
    
    // Mostrar opciones correspondientes al tipo de actividad al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked')?.value;
        if (tipoSeleccionado) {
            mostrarOpcionesPorTipo(tipoSeleccionado);
        }
    });
</script>

</body>
</html>