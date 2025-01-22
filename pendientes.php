<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Tarea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>

<body>

</body>

</html>


<?php
session_start();
require_once("conexion.php");
$conn = conectarDB();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener usuarios (SOLO UNA VEZ)
$sqlUsuarios = "SELECT id, nombre FROM usuarios";
$resultUsuarios = $conn->query($sqlUsuarios);

if (!$resultUsuarios) {
    die("Error al obtener usuarios: " . $conn->error);
}

$usuarios = [];
while ($row = $resultUsuarios->fetch_assoc()) {
    $usuarios[] = $row;
}


// Obtener tareas pendientes del usuario
$sql = "SELECT 
    t.id as tarea_id, 
    t.title,
    t.completed,
    t.priority,
    t.description,
    t.start_date,
    t.end_date,
    u.nombre AS responsable
    
    FROM tareas t
    JOIN usuarios u ON t.user_id = u.id
    WHERE t.user_id = ? AND t.completed = 0
    ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

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
        echo "<button type='submit' name='submit' value='completar'>Completar</button>";
        echo "<button type='submit' name='submit' value='agregarcomentario'>Agregar Comentario</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
} else {
    echo "<tbody><tr><td colspan='6'>No hay tareas pendientes para este usuario.</td></tr></tbody>";
}
echo "</table>";



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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST) && $_POST['submit'] == 'cancelarcomentario') {
    // redirigir a la página principal
    header('Location: index.php');
    exit;
}

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
$stmt->close();
$conn->close();
