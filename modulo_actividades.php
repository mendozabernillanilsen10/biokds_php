<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'docente') {
    header("Location: inicio.php");
    exit;
}

require_once 'db_connect.php';

// Eliminar actividad
if (isset($_GET['eliminar_id'])) {
    $eliminarId = $_GET['eliminar_id'];
    $stmtDel = $pdo->prepare("DELETE FROM actividades WHERE id = ?");
    $stmtDel->execute([$eliminarId]);
    header("Location: modulo_actividades.php");
    exit;
}

// Editar actividad (mostrar en formulario)
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

// Guardar nueva actividad o actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $tipo = $_POST['tipo'];
    $contenido = $_POST['contenido'];
    $categoria_id = $_POST['categoria_id'];
    $puntaje = $_POST['puntaje'] ?? 10; // Valor predeterminado si no se envía
    
    // Transacción para garantizar integridad de datos
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['actividad_id'])) {
            // Actualizar actividad existente
            $actividad_id = $_POST['actividad_id'];
            $stmtUpdate = $pdo->prepare("UPDATE actividades SET titulo = ?, tipo = ?, contenido = ?, categoria_id = ?, puntaje = ? WHERE id = ?");
            $stmtUpdate->execute([$titulo, $tipo, $contenido, $categoria_id, $puntaje, $actividad_id]);
            
            // Eliminar opciones antiguas para reemplazarlas
            $stmtDelOpciones = $pdo->prepare("DELETE FROM opciones WHERE actividad_id = ?");
            $stmtDelOpciones->execute([$actividad_id]);
        } else {
            // Insertar nueva actividad
            $stmtInsert = $pdo->prepare("INSERT INTO actividades (titulo, tipo, contenido, categoria_id, puntaje) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$titulo, $tipo, $contenido, $categoria_id, $puntaje]);
            $actividad_id = $pdo->lastInsertId();
        }
        
        // Insertar opciones si corresponde al tipo de actividad
        if ($tipo == 'quiz' || $tipo == 'verdaderofalso') {
            $opciones = $_POST['opcion'] ?? [];
            $correctas = $_POST['correcta'] ?? [];
            
            // Preparar statement para insertar opciones
            $stmtInsertOpcion = $pdo->prepare("INSERT INTO opciones (actividad_id, opcion, correcta) VALUES (?, ?, ?)");
            
            foreach ($opciones as $index => $opcion) {
                if (!empty($opcion)) {
                    $esCorrecta = in_array($index, $correctas) ? 1 : 0;
                    $stmtInsertOpcion->execute([$actividad_id, $opcion, $esCorrecta]);
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

// Obtener actividades
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
        .opciones-container {
            border: 1px dashed #ccc;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .opcion-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .opcion-item input[type="text"] {
            flex-grow: 1;
            margin-right: 10px;
        }
        .opcion-item input[type="checkbox"] {
            width: auto;
        }
        .opcion-item label {
            margin-right: 10px;
        }
        .add-opcion-btn {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: auto;
        }
        .remove-opcion-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            width: auto;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="inicio.php" class="volver-btn"><i class="fas fa-arrow-left"></i> Volver</a>

        <?php if (isset($error)): ?>
            <div class="mensaje-error"><?= $error ?></div>
        <?php endif; ?>

        <h1><?= $actividadEditar ? 'Editar Actividad' : 'Registrar Nueva Actividad' ?></h1>
        <form method="POST" action="modulo_actividades.php">
            <?php if ($actividadEditar): ?>
                <input type="hidden" name="actividad_id" value="<?= $actividadEditar['id'] ?>">
            <?php endif; ?>
            
            <input type="text" name="titulo" placeholder="Título de la actividad" required value="<?= isset($actividadEditar['titulo']) ? htmlspecialchars($actividadEditar['titulo']) : '' ?>">
            
            <div class="tipo-actividad">
                <p><strong>Tipo de actividad:</strong></p>
                <label>
                    <input type="radio" name="tipo" value="quiz" <?= (isset($actividadEditar) && $actividadEditar['tipo'] == 'quiz') ? 'checked' : '' ?> onchange="mostrarOpcionesPorTipo(this.value)"> 
                    Quiz
                </label>
                <label>
                    <input type="radio" name="tipo" value="arrastrar" <?= (isset($actividadEditar) && $actividadEditar['tipo'] == 'arrastrar') ? 'checked' : '' ?> onchange="mostrarOpcionesPorTipo(this.value)"> 
                    Arrastrar
                </label>
                <label>
                    <input type="radio" name="tipo" value="verdaderofalso" <?= (isset($actividadEditar) && $actividadEditar['tipo'] == 'verdaderofalso') ? 'checked' : '' ?> onchange="mostrarOpcionesPorTipo(this.value)"> 
                    Verdadero/Falso
                </label>
            </div>
            
            <textarea name="contenido" placeholder="Contenido o instrucciones" required><?= isset($actividadEditar['contenido']) ? htmlspecialchars($actividadEditar['contenido']) : '' ?></textarea>
            
            <div class="form-group">
                <label for="puntaje">Puntaje:</label>
                <input type="number" id="puntaje" name="puntaje" min="1" value="<?= isset($actividadEditar['puntaje']) ? $actividadEditar['puntaje'] : '10' ?>">
            </div>
            
            <select name="categoria_id" required>
                <option value="">Seleccionar categoría</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($actividadEditar) && $actividadEditar['categoria_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Contenedor para opciones (Quiz y Verdadero/Falso) -->
            <div id="opciones-container" class="opciones-container <?= (!isset($actividadEditar) || ($actividadEditar['tipo'] != 'quiz' && $actividadEditar['tipo'] != 'verdaderofalso')) ? 'hidden' : '' ?>">
                <h3>Opciones de respuesta</h3>
                <p id="opciones-info" class="<?= (!isset($actividadEditar) || $actividadEditar['tipo'] != 'quiz') ? 'hidden' : '' ?>">
                    <strong>Nota:</strong> Para quiz puede marcar múltiples opciones como correctas.
                </p>
                <div id="opciones-items">
                    <?php if (!empty($opcionesEditar)): ?>
                        <?php foreach ($opcionesEditar as $index => $opcion): ?>
                            <div class="opcion-item">
                                <input type="text" name="opcion[<?= $index ?>]" placeholder="Opción de respuesta" value="<?= htmlspecialchars($opcion['opcion']) ?>" required>
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
                            <input type="text" name="opcion[0]" placeholder="Opción de respuesta" required>
                            <label>
                                <input type="checkbox" name="correcta[]" value="0"> 
                                Correcta
                            </label>
                            <button type="button" class="remove-opcion-btn" onclick="eliminarOpcion(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="opcion-item">
                            <input type="text" name="opcion[1]" placeholder="Opción de respuesta" required>
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
            
            <button type="submit"><?= $actividadEditar ? 'Actualizar' : 'Guardar' ?></button>
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
                            
                            // Mostrar opciones correctas
                            if ($opcionesCount > 0) {
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
        
        // Función para mostrar u ocultar opciones según el tipo de actividad
        function mostrarOpcionesPorTipo(tipo) {
            const opcionesContainer = document.getElementById('opciones-container');
            const opcionesInfo = document.getElementById('opciones-info');
            
            if (tipo === 'quiz' || tipo === 'verdaderofalso') {
                opcionesContainer.classList.remove('hidden');
                
                // Mostrar mensaje sobre múltiples opciones correctas solo para quiz
                if (tipo === 'quiz') {
                    opcionesInfo.classList.remove('hidden');
                } else {
                    opcionesInfo.classList.add('hidden');
                }
                
                // Si es verdadero/falso, limitamos a dos opciones (Verdadero y Falso)
                if (tipo === 'verdaderofalso') {
                    const opcionesItems = document.getElementById('opciones-items');
                    // Limpiar opciones existentes
                    opcionesItems.innerHTML = '';
                    
                    // Agregar opciones predefinidas para verdadero/falso
                    agregarOpcionPredefinida('Verdadero', 0);
                    agregarOpcionPredefinida('Falso', 1);
                    
                    // Ocultar botón de agregar más opciones
                    document.querySelector('.add-opcion-btn').classList.add('hidden');
                } else {
                    // Para quiz normal, mostrar botón de agregar
                    document.querySelector('.add-opcion-btn').classList.remove('hidden');
                }
            } else {
                opcionesContainer.classList.add('hidden');
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
                <input type="text" name="opcion[${contadorOpciones}]" placeholder="Opción de respuesta" required>
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
            
            // Si es verdadero/falso, no permitir eliminar opciones
            if (tipoSeleccionado === 'verdaderofalso') {
                alert('No se pueden eliminar opciones en actividades de Verdadero/Falso');
                return;
            }
            
            const items = document.querySelectorAll('.opcion-item');
            // Mantener al menos dos opciones
            if (items.length <= 2) {
                alert('Una actividad debe tener al menos dos opciones');
                return;
            }
            
            btn.parentElement.remove();
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