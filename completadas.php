<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Tareas Completadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>

<body>
    <header>
        <div class="container">
            <ul class="nav nav-pills nav-fill gap-2 p-1 small bg-blue rounded-5 " id="pillNav2" role="tablist">
                <li class="nav-item"><a class="nav-link" href="pendientes.php">Ver Tareas pendientes</a></li>
                <li class="nav-item"><a class="nav-link" href="completadas.php">Historial de Tareas Completadas</a></li>
                <li class="nav-item"><a class="nav-link" href="enviadas.php">Ver Historial de Tareas Enviadas</a></li>
                <?php if ($rol == 'administrador' || $rol == 'lider'): ?>
                    <li class="nav-item"><a class="nav-link" href="tareas_usuarios.php">Ver Tareas de Usuarios</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                </li>
            </ul>
        </div>
    </header>

    <div class="container">
        <h2>Historial de Tareas Completadas</h2>

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
        // Obtener la conexión
        $conn = conectarDB();

        if (!isset($conn)) {
            die("Error: No se pudo conectar a la base de datos.");
        }

        // Verificar si el usuario está logueado
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $filtro_usuario = isset($_GET['filtro_usuario']) ? $_GET['filtro_usuario'] : $user_id;

        // Consulta para obtener las tareas completadas del usuario
        $sql = "SELECT * FROM tareas WHERE user_id = ? AND completed = 1";

        // Preparar la consulta
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Error al preparar la consulta: " . $conn->error);
        }

        // Vincular el parámetro
        $stmt->bind_param("i", $filtro_usuario);

        // Ejecutar la consulta
        if ($stmt->execute() === FALSE) {
            die("Error al ejecutar la consulta: " . $stmt->error);
        }
        $result = $stmt->get_result();

        // Verificar si hay resultados
        if ($result->num_rows > 0) {
            $tareas = $result->fetch_all(MYSQLI_ASSOC);

            // Mostrar las tareas
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>Título</th><th>Estado</th><th>Descripción</th><th>Prioridad</th><th>Fecha de Inicio</th><th>Fecha de Cierre</th><th>Acciones</th></tr></thead>";
            echo "<tbody>";
            foreach ($tareas as $tarea) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($tarea['title']) . "</td>";
                echo "<td>" . ($tarea['completed'] == 0
                    ? '<span class="badge bg-warning">Pendiente</span>'
                    : '<span class="badge bg-success">Completada</span>'
                ) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['description']) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['priority']) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['start_date']) . "</td>";
                echo "<td>" . htmlspecialchars($tarea['end_date']) . "</td>";
                echo "<td>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='tarea_id' value='" . htmlspecialchars($tarea['id']) . "'>";
                echo "<button type='submit' name='submit' value='verhistorial' class='badge bg-info'>Ver Historial de la Tarea</button>";
                echo "</form>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No hay tareas completadas de este usuario.</p>";
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
                t.evidence,
                u1.nombre AS responsable_actual,
                COALESCE(u2.nombre, 'No asignado') AS creador
                FROM tareas t
                LEFT JOIN usuarios u1 ON t.user_id = u1.id
                LEFT JOIN usuarios u2 ON t.asigned_by = u2.id
                WHERE t.id = ?";

            $stmtHistorial = $conn->prepare($sqlHistorial);
            $stmtHistorial->bind_param("i", $tarea_id);
            $stmtHistorial->execute();
            $resultHistorial = $stmtHistorial->get_result();
            $historial = $resultHistorial->fetch_assoc();

            if ($historial) {
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
                echo "<p class='card-text'><strong>Reasignaciones restantes:</strong> " . htmlspecialchars($historial['reasignaciones_restantes']) . "</p>";
                echo "<p class='card-text'><strong>Evidencia:</strong> " . htmlspecialchars($historial['evidence']) . "</p>";
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
            } else {
                echo "<p>Error: No se pudo obtener el historial de la tarea.</p>";
            }

            $stmtHistorial->close();
        }

        // Cerrar el statement y la conexión
        $stmt->close();
        $conn->close();
        ?>


    </div>
</body>

</html>