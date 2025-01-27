<?php
session_start();
require_once("conexion.php");
$conn = conectarDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener el rol del usuario
$user_id = $_SESSION['user_id'];
$sql = "SELECT rol FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$rol = $user['rol'];

$stmt->close();

// Obtener usuarios *una sola vez* al inicio.
$sql = "SELECT id, nombre FROM usuarios";
$result = $conn->query($sql);

if (!$result) {
    die("Error al obtener usuarios: " . $conn->error);
}

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Tarea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("form");
            form.addEventListener("submit", function() {
                const submitButton = form.querySelector("button[type='submit']");
                submitButton.disabled = true;
            });
        });
    </script>
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
            </ul>
        </div>
    </header>
    <div class="container">
        <h2>Asignar Tarea</h2>
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'):
            $title = $_POST['title'];
            $description = $_POST['description'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $priority = (int)$_POST['priority']; // Convertir a entero
            $observations = $_POST['observations'];
            $comentarios = $_POST['comentarios'];
            $user_id = (int)$_POST['user_id'];
            $asigned_by = (int)$_SESSION['user_id'];

            // Consulta SQL modificada para incluir asigned_by
            $sql = "INSERT INTO tareas (
        user_id, 
        title, 
        description, 
        start_date, 
        end_date, 
        priority, 
        observations, 
        comentarios, 
        asigned_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                die("Error en prepare: " . $conn->error);
            }

            // Modificar bind_param para incluir asigned_by
            $stmt->bind_param(
                "isssssssi",
                $user_id,
                $title,
                $description,
                $start_date,
                $end_date,
                $priority,
                $observations,
                $comentarios,
                $asigned_by
            );

            if ($stmt->execute()) {
                echo '<div class="alert alert-success">Tarea asignada correctamente.</div>';
            } else {
                echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
            }

            $stmt->close();
        ?>
        <?php else: ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Título:</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <!-- ... Resto de los campos del formulario ... -->
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción:</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Fecha de Inicio:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">Fecha de Cierre:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                </div>
                <div class="mb-3">
                    <label for="observations" class="form-label">Observaciones:</label>
                    <textarea class="form-control" id="observations" name="observations" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="comentarios" class="form-label">Comentarios:</label>
                    <textarea class="form-control" id="comentarios" name="comentarios" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="priority" class="form-label">Prioridad:</label>
                    <select class="form-select" id="priority" name="priority" required>
                        <option value="1">Baja</option>
                        <option value="2">Media</option>
                        <option value="3">Alta</option>
                        <option value="4">Urgente</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="user_id" class="form-label">Encargado:</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"><?php echo $usuario['nombre']; ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No hay usuarios disponibles.</option>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Asignar</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>