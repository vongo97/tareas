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
    t.description,
    t.priority,
    t.start_date,
    t.end_date,
    t.completed,
    u.nombre AS usuario_nombre
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
echo "<thead><tr><th>Título</th><th>Descripción</th><th>Prioridad</th><th>Fecha de Inicio</th><th>Fecha de Cierre</th><th>Acciones</th></tr></thead>";


if ($result->num_rows > 0) {
    $tareas = $result->fetch_all(MYSQLI_ASSOC);
    echo "<tbody>";
    foreach ($tareas as $tarea) {
        echo "<tr>";
        echo "<td>" . $tarea['title'] . "</td>";
        echo "<td>" . $tarea['description'] . "</td>";
        echo "<td>" . $tarea['priority'] . "</td>";
        echo "<td>" . $tarea['start_date'] . "</td>";
        echo "<td>" . $tarea['end_date'] . "</td>";
        echo "<td>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='tarea_id' value='" . $tarea['tarea_id'] . "'>";
        echo "<button type='submit' name='submit' value='completar'>Completar</button>";
        echo "<button type='submit' name='submit' value='agregarcomentario'>Agregar un Comentario</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
} else {
    echo "<tbody><tr><td colspan='6'>No hay tareas pendientes para este usuario.</td></tr></tbody>";
}
echo "</table>";



//Si se presionan los botones
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



$stmt->close();
$conn->close();
