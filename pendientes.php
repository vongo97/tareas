<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas pendientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>

<body>

    <div class="container">

        <h2>Panel de Tareas Pendientes</h2>
        <?php
        session_start();
        require_once("conexion.php");
        $conn = conectarDB();

        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        // Obtener el rol del usuario actual
        $sqlRol = "SELECT rol FROM usuarios WHERE id = ?";
        $stmtRol = $conn->prepare($sqlRol);
        $stmtRol->bind_param("i", $_SESSION['user_id']);
        $stmtRol->execute();
        $resultRol = $stmtRol->get_result();
        $rolUsuario = $resultRol->fetch_assoc()['rol'];
        ?>

        <?php if ($rolUsuario == 'admin' || $rolUsuario == 'lider'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label for="filtro_usuario" class="col-form-label">Ver tareas de:</label>
                        </div>
                        <div class="col-auto">
                            <select name="filtro_usuario" id="filtro_usuario" class="form-select" onchange="this.form.submit()">
                                <option value="">Todos los usuarios</option>
                                <?php
                                $sqlUsuarios = "SELECT id, nombre FROM usuarios";
                                $resultUsuarios = $conn->query($sqlUsuarios);
                                while ($usuario = $resultUsuarios->fetch_assoc()) {
                                    $selected = (isset($_GET['filtro_usuario']) && $_GET['filtro_usuario'] == $usuario['id']) ? 'selected' : '';
                                    echo "<option value='{$usuario['id']}' {$selected}>{$usuario['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php


        // Después de obtener el rol
        $usuario_filtrado = isset($_GET['filtro_usuario']) ? $_GET['filtro_usuario'] : null;

        // Modificar la consulta SQL base
        $sql = "SELECT 
    t.id as tarea_id, 
    t.title,
    t.completed,
    t.priority,
    t.description,
    t.start_date,
    t.end_date,
    t.reasignaciones_restantes,
    t.asigned_by,
    u.nombre AS responsable,
    u2.nombre AS asignador
    FROM tareas t
    JOIN usuarios u ON t.user_id = u.id
    LEFT JOIN usuarios u2 ON t.asigned_by = u2.id
    WHERE 1=1";

        // Aplicar filtros según rol y usuario seleccionado
        if ($rolUsuario == 'receptor') {
            $sql .= " AND t.user_id = ?";
            $params[] = $_SESSION['user_id'];
        } elseif ($usuario_filtrado) {
            $sql .= " AND t.user_id = ?";
            $params[] = $usuario_filtrado;
        }

        $sql .= " AND t.completed = 0 ORDER BY t.created_at DESC";

        // Preparar y ejecutar consulta
        $stmt = $conn->prepare($sql);

        if (!empty($params)) {
            $types = str_repeat("i", count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        // Obtener usuarios para el formulario de reasignación
        $sqlUsuarios = "SELECT id, nombre FROM usuarios";
        $resultUsuarios = $conn->query($sqlUsuarios);
        $usuarios = [];
        while ($row = $resultUsuarios->fetch_assoc()) {
            $usuarios[] = $row;
        }

        // Muestra las tareas pendientes (o un mensaje si no hay)
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Título</th><th>Estado</th><th>Prioridad</th><th>Responsable</th><th>Descripción</th><th>Fecha de Inicio</th><th>Fecha de Cierre</th><th>Acciones</th></tr></thead>";


        if ($result->num_rows > 0) {
            $tareas = $result->fetch_all(MYSQLI_ASSOC);
            echo "<tbody>";
            foreach ($tareas as $tarea) {
                echo "<tr>";
                echo "<td>" . $tarea['title'] . "</td>";
                echo "<td>" . ($tarea['completed'] == 0
                    ? '<span class= "badge bg-warning">Pendiente</span>'
                    : '<span class= "badge bg-success">Completada</span">'
                ) . "</td>";
                echo "<td>" . $tarea['priority'] . "</td>";
                echo "<td>" . $tarea['responsable'] . "</td>";
                echo "<td>" . $tarea['description'] . "</td>";
                echo "<td>" . $tarea['start_date'] . "</td>";
                echo "<td>" . $tarea['end_date'] . "</td>";
                echo "<td>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='tarea_id' value='" . $tarea['tarea_id'] . "'>";
                echo "<button type='submit' name='submit' value='completar' class='badge bg-success'>Completar</button>";
                echo "<button type='submit' name='submit' value='agregarcomentario' class='badge bg-warning'>Agregar Comentario</button>";
                if ($tarea['reasignaciones_restantes'] > 0) {
                    echo "<button type='submit' name='submit' value='reasignar' class='badge bg-warning'>Reasignar</button>";
                }
                echo "<button type='submit' name='submit' value='verhistorial' class='badge bg-info'>Ver Historial de la Tarea</button>";
                echo "</form>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
        } else {
            echo "<tbody><tr><td colspan='6'>No hay tareas pendientes para este usuario.</td></tr></tbody>";
        }
        echo "</table>";


        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'completar') {

            $tarea_id = $_POST['tarea_id'];
            $sqlUpdate = "UPDATE tareas SET completed = 1 WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);



            if ($stmtUpdate === false) {
                die("Error al preparar la consulta: " . $conn->error);
            }


            $stmtUpdate->bind_param("i", $tarea_id);
            if ($stmtUpdate->execute() === false) {
                die("Error al ejecutar la consulta: " . $stmtUpdate->error);
            }
            $stmtUpdate->close();

            echo "<p class='alert alert-success'>Tarea completada exitosamente.</p>";

            //Actualiza la página para mostrar las tareas actualizadas (opcional).
            // header("Refresh:0");  //Para recargar la página actual
        }

        //asignar comentario
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'agregarcomentario') {
            $tarea_id = $_POST['tarea_id'];

            // Mostrar formulario para agregar comentario
            echo "<div class='modal' style='display:block;'>";
            echo "<div class='modal-dialog'>";
            echo "<div class='modal-content'>";
            echo "<div class='modal-header'><h5>Agregar Comentario</h5></div>";
            echo "<div class='modal-body'>";
            echo "<form method='POST'>";
            echo "<input type='hidden' name='tarea_id' value='" . $tarea_id . "'>";
            echo "<textarea name='nuevo_comentario' class='form-control'></textarea>";
            echo "<div class='modal-footer'>";
            echo "<button type='submit' name='submit' value='guardarcomentario' class='btn btn-primary'>Guardar</button>";
            echo "<button type='submit' name='submit' value='cancelar' class='btn btn-secondary'>Cancelar</button>";
            echo "</div>";
            echo "</form>";
            echo "</div></div></div></div>";
        }
        // Cancelar comentario
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST) && $_POST['submit'] == 'cancelarcomentario') {
            // redirigir a la página principal
            header('Location: index.php');
            exit;
        }

        // Guardar comentario
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'guardarcomentario') {
            $tarea_id = $_POST['tarea_id'];
            $nuevo_comentario = $_POST['nuevo_comentario'];

            // Primero obtener comentarios existentes
            $sqlSelect = "SELECT comentarios FROM tareas WHERE id = ?";
            $stmtSelect = $conn->prepare($sqlSelect);
            $stmtSelect->bind_param("i", $tarea_id);
            $stmtSelect->execute();
            $result = $stmtSelect->get_result();
            $row = $result->fetch_assoc();

            // Concatenar nuevo comentario con fecha
            $fecha_actual = date("Y-m-d H:i:s");
            $comentarios_actualizados = $row['comentarios'] . "\n[" . $fecha_actual . "] " . $nuevo_comentario;

            // Actualizar la base de datos
            $sqlUpdate = "UPDATE tareas SET comentarios = ? WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("si", $comentarios_actualizados, $tarea_id);

            if ($stmtUpdate->execute()) {
                echo "<p class='alert alert-success'>Comentario agregado exitosamente.</p>";
                // Recargar la página después de 2 segundos
                header("Refresh:2");
            } else {
                echo "<p class='alert alert-danger'>Error al agregar el comentario.</p>";
            }

            $stmtUpdate->close();
        }

        // Agregar el código para el modal de reasignación después del foreach
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'reasignar') {
            $tarea_id = $_POST['tarea_id'];

            // Consultar reasignaciones restantes
            $sqlReasignaciones = "SELECT reasignaciones_restantes FROM tareas WHERE id = ?";
            $stmtReasignaciones = $conn->prepare($sqlReasignaciones);
            $stmtReasignaciones->bind_param("i", $tarea_id);
            $stmtReasignaciones->execute();
            $resultReasignaciones = $stmtReasignaciones->get_result();
            $reasignaciones = $resultReasignaciones->fetch_assoc();

            echo "<div class='modal' style='display:block;'>";
            echo "<div class='modal-dialog'>";
            echo "<div class='modal-content'>";
            echo "<div class='modal-header'><h5>Reasignar Tarea</h5></div>";
            echo "<div class='modal-body'>";

            // Agregar contador de reasignaciones
            echo "<div class='alert alert-info'>";
            echo "Reasignaciones restantes: <strong>" . $reasignaciones['reasignaciones_restantes'] . "</strong>";
            echo "</div>";

            echo "<form method='POST'>";
            echo "<input type='hidden' name='tarea_id' value='" . $tarea_id . "'>";
            echo "<div class='mb-3'>";
            echo "<label for='nuevo_responsable' class='form-label'>Nuevo Responsable:</label>";
            echo "<select name='nuevo_responsable' class='form-control' required>";
            foreach ($usuarios as $usuario) {
                echo "<option value='" . $usuario['id'] . "'>" . $usuario['nombre'] . "</option>";
            }
            echo "</select>";
            echo "</div>";
            echo "<div class='mb-3'>";
            echo "<label for='motivo' class='form-label'>Motivo de la reasignación:</label>";
            echo "<textarea name='motivo' class='form-control' required></textarea>";
            echo "</div>";
            echo "<button type='submit' name='submit' value='confirmar_reasignacion' class='btn btn-primary'>Confirmar Reasignación</button>";
            echo "<button type='button' class='btn btn-secondary' onclick='window.location.href=\"pendientes.php\"'>Cancelar</button>";
            echo "</form>";
            echo "</div></div></div></div>";
        }
        // Procesar la reasignación
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'confirmar_reasignacion') {
            $tarea_id = $_POST['tarea_id'];
            $nuevo_responsable = $_POST['nuevo_responsable'];
            $motivo = $_POST['motivo'];

            // Verificar número de reasignaciones
            $sqlCheck = "SELECT reasignaciones_restantes FROM tareas WHERE id = ?";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("i", $tarea_id);
            $stmtCheck->execute();
            $resultCheck = $stmtCheck->get_result();
            $row = $resultCheck->fetch_assoc();

            if ($row['reasignaciones_restantes'] > 0) {
                // Actualizar la tarea
                $sqlUpdate = "UPDATE tareas SET user_id = ?, reasignaciones_restantes = reasignaciones_restantes - 1, 
                      comentarios = CONCAT(comentarios, '\n[', NOW(), '] Reasignada: ', ?) 
                      WHERE id = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bind_param("isi", $nuevo_responsable, $motivo, $tarea_id);

                if ($stmtUpdate->execute()) {
                    echo "<p class='alert alert-success'>Tarea reasignada exitosamente.</p>";
                    header("Refresh:2");
                } else {
                    echo "<p class='alert alert-danger'>Error al reasignar la tarea.</p>";
                }
                $stmtUpdate->close();
            } else {
                echo "<p class='alert alert-warning'>Esta tarea ya ha sido reasignada el máximo número de veces permitido.</p>";
            }
        }

        // Agregar el modal del historial
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'verhistorial') {
            $tarea_id = $_POST['tarea_id'];

            // Consulta para obtener datos completos de la tarea
            $sqlHistorial = "SELECT 
        t.title,
        t.description,
        t.comentarios,
        t.reasignaciones_restantes,
        u1.nombre AS responsable_actual,
        u2.nombre AS creador
        FROM tareas t
        LEFT JOIN usuarios u1 ON t.user_id = u1.id
        LEFT JOIN usuarios u2 ON t.asigned_by = u2.id
        WHERE t.id = ?";

            $stmtHistorial = $conn->prepare($sqlHistorial);
            $stmtHistorial->bind_param("i", $tarea_id);
            $stmtHistorial->execute();
            $resultHistorial = $stmtHistorial->get_result();
            $historial = $resultHistorial->fetch_assoc();

            echo "<div class='modal' style='display:block;'>";
            echo "<div class='modal-dialog modal-lg'>";
            echo "<div class='modal-content'>";
            echo "<div class='modal-header'>";
            echo "<h5 class='modal-title'>Historial de Tarea</h5>";
            echo "</div>";
            echo "<div class='modal-body'>";

            echo "<div class='card mb-3'>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>" . htmlspecialchars($historial['title']) . "</h5>";
            echo "<p class='card-text'><strong>Descripción:</strong> " . htmlspecialchars($historial['description']) . "</p>";
            echo "<p class='card-text'><strong>Asignado por:</strong> " . htmlspecialchars($historial['creador']) . "</p>";
            echo "<p class='card-text'><strong>Reasignaciones restantes:</strong> " . $historial['reasignaciones_restantes'] . "</p>";
            echo "<div class='card mt-3'>";
            echo "<div class='card-header'><strong>Comentarios y Cambios</strong></div>";
            echo "<div class='card-body'>";
            echo "<pre class='card-text'>" . htmlspecialchars($historial['comentarios']) . "</pre>";
            echo "</div></div>";
            echo "</div></div>";

            echo "</div>";
            echo "<div class='modal-footer'>";
            echo "<button type='button' class='btn btn-secondary' onclick='window.location.href=\" \"'>Cancelar</button>";
            echo "</div>";
            echo "</div></div></div>";
        }

        $stmt->close();
        $conn->close();
        ?>

    </div>


</body>

</html>