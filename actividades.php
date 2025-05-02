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

// Determinar qué categoría está activa
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

        if (!$actividad_completada) {
            // Insertar la actividad completada
            $stmt = $pdo->prepare('INSERT INTO actividades_completadas (alumno_id, actividad_id, puntaje, fecha_completado) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$alumno_id, $actividad_id, $puntaje]);
            
            // Redirigir para evitar resubir el formulario
            header("Location: perfil_alumno.php?categoria=" . $categoria_activa);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades Interactivas</title>
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

        .actividad {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 20px;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Estilos para actividades de arrastrar */
        .arrastrar-container {
            position: relative;
            margin: 20px auto;
            max-width: 800px;
        }

        .imagen-base-container {
            position: relative;
            margin-bottom: 20px;
        }

        .imagen-base {
            max-width: 100%;
            height: auto;
            border: 2px solid #333;
        }

        .partes-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .parte-arrastrable {
            cursor: move;
            min-width: 80px;
            min-height: 40px;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px dashed #999;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .parte-arrastrable img {
            max-width: 60px;
            max-height: 60px;
        }

        .parte-arrastrable span {
            font-size: 14px;
        }

        .zona-destino {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 2px dashed #4CAF50;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(76, 175, 80, 0.1);
        }

        .parte-colocada {
            position: absolute;
            z-index: 10;
            pointer-events: none;
        }

        .feedback-container {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .feedback-item {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }

        .feedback-correcto {
            background-color: rgba(40, 167, 69, 0.2);
            border-left: 4px solid #28a745;
        }

        .feedback-incorrecto {
            background-color: rgba(220, 53, 69, 0.2);
            border-left: 4px solid #dc3545;
        }

        .resaltado {
            background-color: rgba(255, 193, 7, 0.3);
        }

        .invisible {
            opacity: 0.3;
        }

        /* Estilos para quiz y verdadero/falso */
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

        /* Ocultar tablas extras */
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

    <?php foreach ($categorias as $categoria): 
        // Obtener actividades de esta categoría
        $stmt = $pdo->prepare('SELECT a.* FROM actividades a JOIN categorias c ON a.categoria_id = c.id WHERE c.slug = ?');
        $stmt->execute([$categoria['slug']]);
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
                
                // Solo mostrar actividades no completadas
                if (!$actividad_completada):
                ?>
                    <div class="actividad">
                        <h2><?php echo htmlspecialchars($actividad['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars($actividad['contenido']); ?></p>
                        
                        <div id="quiz-<?php echo $actividad['id']; ?>" class="quiz-container">
                            <?php
                            // Obtener opciones de la actividad
                            $stmtOpciones = $pdo->prepare('SELECT * FROM opciones WHERE actividad_id = ?');
                            $stmtOpciones->execute([$actividad['id']]);
                            $opciones = $stmtOpciones->fetchAll();
                            ?>
                            
                            <?php if ($actividad['tipo'] == 'arrastrar'): ?>
                                <!-- Actividad de arrastrar y soltar -->
                                <div class="arrastrar-container">
                                    <?php if (!empty($actividad['imagen_base'])): ?>
                                        <div class="imagen-base-container">
                                            <img src="uploads/<?php echo htmlspecialchars($actividad['imagen_base']); ?>" 
                                                 alt="<?php echo htmlspecialchars($actividad['titulo']); ?>" class="imagen-base">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="instrucciones">
                                        <h3><?php echo htmlspecialchars($actividad['titulo_partes'] ?? 'Partes a colocar'); ?>:</h3>
                                        <p>Arrastra cada elemento a su posición correcta en la imagen.</p>
                                    </div>
                                    
                                    <div class="partes-container">
                                        <?php foreach ($opciones as $opcion): ?>
                                            <div class="parte-arrastrable" draggable="true" 
                                                 data-id="<?php echo $opcion['id']; ?>"
                                                 data-nombre="<?php echo htmlspecialchars($opcion['opcion']); ?>"
                                                 data-descripcion="<?php echo htmlspecialchars($opcion['descripcion'] ?? ''); ?>"
                                                 data-posx="<?php echo $opcion['pos_x']; ?>"
                                                 data-posy="<?php echo $opcion['pos_y']; ?>">
                                                <?php if (!empty($opcion['imagen'])): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($opcion['imagen']); ?>" 
                                                         alt="<?php echo htmlspecialchars($opcion['opcion']); ?>">
                                                <?php else: ?>
                                                    <span><?php echo htmlspecialchars($opcion['opcion']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="zonas-destino"></div>
                                    <div class="feedback-container"></div>
                                    
                                    <form method="post" class="actividad-form">
                                        <input type="hidden" name="formulario-actividad" value="1">
                                        <input type="hidden" name="actividad_id" value="<?php echo $actividad['id']; ?>">
                                        <input type="hidden" name="puntaje" value="0" class="puntaje-input">
                                        <button type="submit" class="opcion submit-btn">Finalizar y Enviar</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Actividades de quiz o verdadero/falso -->
                                <form method="post" class="actividad-form">
                                    <input type="hidden" name="formulario-actividad" value="1">
                                    <input type="hidden" name="actividad_id" value="<?php echo $actividad['id']; ?>">
                                    <input type="hidden" name="puntaje" value="0" class="puntaje-input">
                                    
                                    <?php foreach ($opciones as $opcion): ?>
                                        <div class="opcion-container">
                                            <label class="opcion-label">
                                                <input type="<?php echo $actividad['tipo'] == 'quiz' ? 'checkbox' : 'radio'; ?>" 
                                                       name="respuesta-<?php echo $actividad['id']; ?>[]" 
                                                       value="<?php echo $opcion['id']; ?>"
                                                       data-correcta="<?php echo $opcion['correcta']; ?>"
                                                       onclick="evaluarOpcion(this, <?php echo $actividad['id']; ?>)">
                                                <?php echo htmlspecialchars($opcion['opcion']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <button type="submit" class="opcion submit-btn">Finalizar y Enviar</button>
                                </form>
                            <?php endif; ?>
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
        // Función para inicializar actividades de arrastrar
        function inicializarArrastrar(actividadId) {
            const contenedor = document.querySelector(`#quiz-${actividadId} .arrastrar-container`);
            const imagenBase = contenedor.querySelector('.imagen-base');
            const zonasDestino = contenedor.querySelector('.zonas-destino');
            const partesArrastrables = contenedor.querySelectorAll('.parte-arrastrable');
            const feedbackContainer = contenedor.querySelector('.feedback-container');
            const puntajeInput = contenedor.querySelector('.puntaje-input');
            
            // Variables de estado
            let partesColocadas = [];
            let feedbackMostrado = false;
            
            // Crear zonas de destino
            partesArrastrables.forEach(parte => {
                const zona = document.createElement('div');
                zona.className = 'zona-destino';
                zona.style.left = `${parte.dataset.posx}px`;
                zona.style.top = `${parte.dataset.posy}px`;
                zona.dataset.id = parte.dataset.id;
                zona.dataset.nombre = parte.dataset.nombre;
                zonasDestino.appendChild(zona);
                
                // Eventos de arrastrar
                parte.addEventListener('dragstart', e => {
                    e.dataTransfer.setData('text/plain', parte.dataset.id);
                    setTimeout(() => parte.classList.add('invisible'), 0);
                });
                
                parte.addEventListener('dragend', () => {
                    parte.classList.remove('invisible');
                });
            });
            
            // Eventos para zonas de destino
            zonasDestino.querySelectorAll('.zona-destino').forEach(zona => {
                zona.addEventListener('dragover', e => {
                    e.preventDefault();
                    zona.classList.add('resaltado');
                });
                
                zona.addEventListener('dragleave', () => {
                    zona.classList.remove('resaltado');
                });
                
                zona.addEventListener('drop', e => {
                    e.preventDefault();
                    zona.classList.remove('resaltado');
                    
                    const parteId = e.dataTransfer.getData('text/plain');
                    const parte = document.querySelector(`.parte-arrastrable[data-id="${parteId}"]`);
                    
                    // Verificar si ya fue colocada
                    if (partesColocadas.includes(parteId)) {
                        mostrarFeedback('Esta parte ya fue colocada', false);
                        return;
                    }
                    
                    // Verificar si es la zona correcta
                    if (parteId === zona.dataset.id) {
                        // Parte correcta
                        const clone = parte.cloneNode(true);
                        clone.classList.add('parte-colocada');
                        clone.style.left = '10px';
                        clone.style.top = '10px';
                        clone.draggable = false;
                        zona.appendChild(clone);
                        
                        // Marcar como colocada
                        partesColocadas.push(parteId);
                        
                        // Mostrar descripción si existe
                        if (parte.dataset.descripcion) {
                            mostrarFeedback(`<strong>${parte.dataset.nombre}:</strong> ${parte.dataset.descripcion}`, true);
                        }
                        
                        // Calcular puntaje
                        calcularPuntaje();
                    } else {
                        // Parte incorrecta
                        mostrarFeedback(`"${parte.dataset.nombre}" no pertenece aquí`, false);
                    }
                });
            });
            
            // Función para mostrar retroalimentación
            function mostrarFeedback(mensaje, esCorrecto) {
                if (feedbackMostrado) return;
                
                const div = document.createElement('div');
                div.className = `feedback-item ${esCorrecto ? 'feedback-correcto' : 'feedback-incorrecto'}`;
                div.innerHTML = mensaje;
                feedbackContainer.appendChild(div);
                
                feedbackMostrado = true;
                setTimeout(() => {
                    feedbackContainer.removeChild(div);
                    feedbackMostrado = false;
                }, 3000);
            }
            
            // Función para calcular puntaje
            function calcularPuntaje() {
                const correctas = partesColocadas.length;
                const total = partesArrastrables.length;
                const porcentaje = (correctas / total) * 100;
                const puntajeMaximo = <?php echo $actividad['puntaje']; ?>;
                const puntajeObtenido = Math.round((porcentaje / 100) * puntajeMaximo);
                
                puntajeInput.value = puntajeObtenido;
            }
        }
        
        // Función para evaluar opciones en quiz/verdadero-falso
        function evaluarOpcion(elemento, actividadId) {
            const esCorrecta = elemento.dataset.correcta === '1';
            const quizContainer = document.getElementById(`quiz-${actividadId}`);
            const puntajeInput = quizContainer.querySelector('.puntaje-input');
            
            // Lógica para calcular puntaje según respuestas seleccionadas
            const respuestas = quizContainer.querySelectorAll('input[type="checkbox"], input[type="radio"]');
            let correctas = 0;
            let seleccionadas = 0;
            
            respuestas.forEach(resp => {
                if (resp.checked) {
                    seleccionadas++;
                    if (resp.dataset.correcta === '1') {
                        correctas++;
                    }
                }
            });
            
            // Calcular puntaje (ajusta esta lógica según tus necesidades)
            const porcentaje = (correctas / seleccionadas) * 100;
            const puntajeMaximo = <?php echo $actividad['puntaje']; ?>;
            const puntajeObtenido = Math.round((porcentaje / 100) * puntajeMaximo);
            
            puntajeInput.value = puntajeObtenido;
        }
        
        // Mostrar sección según categoría
        function mostrarSeccion(id) {
            const secciones = document.querySelectorAll('.seccion');
            secciones.forEach(seccion => seccion.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            
            // Actualizar URL
            window.history.replaceState(null, null, `?categoria=${id}`);
        }
        
        // Redirigir
        function irAOtraVentana() {
            window.location.href = 'perfil_alumno.php';
        }
        
        // Inicializar actividades de arrastrar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.arrastrar-container').forEach(contenedor => {
                const actividadId = contenedor.closest('.actividad').querySelector('input[name="actividad_id"]').value;
                inicializarArrastrar(actividadId);
            });
        });
    </script>
</body>
</html>