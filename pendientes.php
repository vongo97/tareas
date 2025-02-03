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
    <?php
    // Configurar la zona horaria (ajusta según tu ubicación)
    date_default_timezone_set('America/Bogota');

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
    <header>
        <div class="container">
            <ul class="nav nav-pills nav-fill gap-2 p-1 small bg-blue rounded-5" id="pillNav2" role="tablist">
                <li class="nav-item"><a class="nav-link" href="pendientes.php">Ver Tareas pendientes</a></li>
                <li class="nav-item"><a class="nav-link" href="completadas.php">Historial de Tareas Completadas</a></li>
                <li class="nav-item"><a class="nav-link" href="enviadas.php">Ver Historial de Tareas Enviadas</a></li>
                <?php if ($rolUsuario == 'administrador' || $rolUsuario == 'lider'): ?>
                    <li class="nav-item"><a class="nav-link" href="tareas_usuarios.php">Ver Tareas de Usuarios</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
            </ul>
        </div>
    </header>

    <div class="container">
        <h2>Panel de Tareas Pendientes</h2>

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
        $params = [];

        // Consulta base para mostrar tareas pendientes
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

        // Mostrar las tareas pendientes
        echo "<table class='table table-striped'>";
        echo "<thead><tr>
                <th>Título</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>Responsable</th>
                <th>Descripción</th>
                <th>Fecha de Inicio</th>
                <th>Fecha de Cierre</th>
                <th>Acciones</th>
              </tr></thead>";

        if ($result->num_rows > 0) {
            $tareas = $result->fetch_all(MYSQLI_ASSOC);
            echo "<tbody>";
            foreach ($tareas as $tarea) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($tarea['title']) . "</td>";
                echo "<td>" . ($tarea['completed'] == 0
                    ? '<span class="badge bg-warning">Pendiente</span>'
                    : '<span class="badge bg-success">Completada</span>'
                ) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['priority']) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['responsable']) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['description']) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['start_date']) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['end_date']) . "</td>";
                echo "<td>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='tarea_id' value='" . $tarea['tarea_id'] . "'>";
                echo "<button type='button' class='badge bg-success' data-bs-toggle='modal' data-bs-target='#evidenceModal' data-tarea-id='" . htmlspecialchars($tarea['tarea_id']) . "'>Completar</button> ";
                echo "<button type='submit' name='submit' value='agregarcomentario' class='badge bg-warning'>Agregar Comentario</button> ";
                if ($tarea['reasignaciones_restantes'] > 0) {
                    echo "<button type='submit' name='submit' value='reasignar' class='badge bg-warning'>Reasignar</button> ";
                }
                echo "<button type='submit' name='submit' value='verhistorial' class='badge bg-info'>Ver Historial de la Tarea</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
        } else {
            echo "<tbody><tr><td colspan='8'>No hay tareas pendientes para este usuario.</td></tr></tbody>";
        }
        echo "</table>";
        ?>

        <!-- Modal para agregar evidencia -->
        <div class="modal fade" id="evidenceModal" tabindex="-1" aria-labelledby="evidenceModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="evidenceModalLabel">Agregar Evidencia</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="evidenceForm" method="POST">
                            <input type="hidden" name="tarea_id" id="modalTareaId">
                            <div class="mb-3">
                                <label for="evidence" class="form-label">Evidencia:</label>
                                <textarea class="form-control" id="evidence" name="evidence" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="submit" value="completarTarea" class="btn btn-primary">Completar Tarea</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var evidenceModal = document.getElementById('evidenceModal');
                evidenceModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var tareaId = button.getAttribute('data-tarea-id');
                    var modalTareaId = document.getElementById('modalTareaId');
                    modalTareaId.value = tareaId;
                });
            });
        </script>

        <?php
        // Procesamiento para completar la tarea
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'completarTarea') {
            $tarea_id = $_POST['tarea_id'];
            $evidence = $_POST['evidence'];

            // Obtener la tarea para extraer la prioridad y la fecha de inicio
            $sqlTask = "SELECT priority, start_date FROM tareas WHERE id = ?";
            $stmtTask = $conn->prepare($sqlTask);
            $stmtTask->bind_param("i", $tarea_id);
            $stmtTask->execute();
            $resultTask = $stmtTask->get_result();
            $task = $resultTask->fetch_assoc();
            echo "Prioridad recuperada: " . print_r($task, true);



            if (!$task) {
                echo '<div class="alert alert-danger">Tarea no encontrada.</div>';
                exit;
            }


            // Definir los arrays con los intervalos y textos para el SLA (usando números como claves)
            $tiempos_sla = [
                "urgente" => 'PT4H',    // 4 horas
                "alta" => 'P1D',        // 1 día
                "media" => 'P3D',       // 3 días
                "baja" => 'P1W'         // 1 semana
            ];

            $sla_textos = [
                "urgente" => '4 horas',
                "alta" => '1 día',
                "media" => '3 días',
                "baja" => '1 semana'
            ];

            // Obtener la prioridad directamente como string (sin trim ni strtolower)
            $priority = !empty($task['priority']) ? strtolower(trim($task['priority'])) : '';

            // Usar directamente el valor de prioridad como clave
            if (isset($tiempos_sla[$priority])) {
                $intervalSpec = $tiempos_sla[$priority];
                $slaTime = $sla_textos[$priority];
            } else {
                // Valor por defecto en caso de prioridad no definida
                $intervalSpec = 'P1D';
                $slaTime = '4 Horas';
            }

            // Calcular la fecha límite sumando el intervalo a la fecha de inicio
            $startDate = new DateTime($task['start_date']);
            $deadline = clone $startDate;
            $deadline->add(new DateInterval($intervalSpec));

            // Obtener la fecha de finalización (momento actual)
            $completionDate = new DateTime();

            // Resetear la hora para comparar solo fechas
            $startDateOnly = clone $startDate;
            $startDateOnly->setTime(0, 0, 0);

            $deadlineOnly = clone $deadline;
            $deadlineOnly->setTime(0, 0, 0);

            $completionDateOnly = clone $completionDate;
            $completionDateOnly->setTime(0, 0, 0);

            // Determinar si se cumplió el objetivo según el SLA
            if ($completionDateOnly <= $deadlineOnly) {
                $slaStatus = 'cumplido';
            } else {
                $slaStatus = 'incumplido';
            }


            // Actualizar la tarea: guardar evidencia, marcar como completada y almacenar el SLA
            $sqlUpdate = "UPDATE tareas SET evidence = ?, completed = 1, sla_status = ?, sla_time = ?, completed_at = NOW() WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("sssi", $evidence, $slaStatus, $slaTime, $tarea_id);

            if ($stmtUpdate->execute()) {
                echo '<div class="alert alert-success">Tarea completada y evidencia agregada correctamente.</div>';
            } else {
                echo '<div class="alert alert-danger">Error: ' . $stmtUpdate->error . '</div>';
            }

            $stmtUpdate->close();
        }

        // Procesamiento para agregar comentario
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'agregarcomentario') {
            $tarea_id = $_POST['tarea_id'];
            echo "<div class='modal' style='display:block;'>
                    <div class='modal-dialog'>
                        <div class='modal-content'>
                            <div class='modal-header'><h5>Agregar Comentario</h5></div>
                            <div class='modal-body'>
                                <form method='POST'>
                                    <input type='hidden' name='tarea_id' value='" . $tarea_id . "'>
                                    <textarea name='nuevo_comentario' class='form-control'></textarea>
                                    <div class='modal-footer'>
                                        <button type='submit' name='submit' value='guardarcomentario' class='btn btn-primary'>Guardar</button>
                                        <button type='submit' name='submit' value='cancelarcomentario' class='btn btn-secondary'>Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                  </div>";
        }

        // Cancelar comentario
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'cancelarcomentario') {
            header('Location: pendientes.php');
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
            $resultSelect = $stmtSelect->get_result();
            $row = $resultSelect->fetch_assoc();

            // Concatenar nuevo comentario con fecha
            $fecha_actual = date("Y-m-d H:i:s");
            $comentarios_actualizados = $row['comentarios'] . "\n[" . $fecha_actual . "] " . $nuevo_comentario;

            // Actualizar la base de datos
            $sqlUpdate = "UPDATE tareas SET comentarios = ? WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("si", $comentarios_actualizados, $tarea_id);

            if ($stmtUpdate->execute()) {
                echo "<p class='alert alert-success'>Comentario agregado exitosamente.</p>";
                header("Refresh:2");
            } else {
                echo "<p class='alert alert-danger'>Error al agregar el comentario.</p>";
            }

            $stmtUpdate->close();
        }

        // Procesamiento para reasignar la tarea
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'reasignar') {
            $tarea_id = $_POST['tarea_id'];

            // Consultar las reasignaciones restantes
            $sqlReasignaciones = "SELECT reasignaciones_restantes FROM tareas WHERE id = ?";
            $stmtReasignaciones = $conn->prepare($sqlReasignaciones);
            $stmtReasignaciones->bind_param("i", $tarea_id);
            $stmtReasignaciones->execute();
            $resultReasignaciones = $stmtReasignaciones->get_result();
            $reasignaciones = $resultReasignaciones->fetch_assoc();
        ?>
            <!-- Modal para reasignación -->
            <div class="modal" style="display:block;">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Reasignar Tarea</h5>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="tarea_id" value="<?php echo $tarea_id; ?>">
                                <div class="mb-3">
                                    <label for="nuevo_usuario" class="form-label">Seleccionar nuevo responsable:</label>
                                    <select name="nuevo_usuario" class="form-select" required>
                                        <option value="">Seleccione un usuario</option>
                                        <?php
                                        foreach ($usuarios as $usuario) {
                                            echo "<option value='" . $usuario['id'] . "'>" . htmlspecialchars($usuario['nombre']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="submit" value="confirmar_reasignacion" class="btn btn-primary">Confirmar</button>
                                    <button type="submit" name="submit" value="cancelar_reasignacion" class="btn btn-secondary">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }

        // Confirmar reasignación
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'confirmar_reasignacion') {
            $tarea_id = $_POST['tarea_id'];
            $nuevo_usuario = $_POST['nuevo_usuario'];

            $sqlUpdate = "UPDATE tareas SET user_id = ?, reasignaciones_restantes = reasignaciones_restantes - 1 WHERE id = ?";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("ii", $nuevo_usuario, $tarea_id);

            if ($stmtUpdate->execute()) {
                echo "<p class='alert alert-success'>Tarea reasignada exitosamente.</p>";
                header("Refresh:2");
            } else {
                echo "<p class='alert alert-danger'>Error al reasignar la tarea.</p>";
            }

            $stmtUpdate->close();
        }

        // Cancelar reasignación
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit']) && $_POST['submit'] == 'cancelar_reasignacion') {
            header("Location: pendientes.php");
            exit;
        }
        ?>

    </div>
</body>

</html>