<?php
require_once('conexion.php');

$conn = conectarDB(); // obtener la conexión

// Obtener usuarios para asignar tareas
$sql = "SELECT id, nombre FROM usuarios";
$result = $conn->query($sql);
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}

// Asignar tarea si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $nombre_tarea = $_POST['title'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $observations = $_POST['observations'];
    $comentarios = $_POST['comentarios'];
    $priority = $_POST['priority'];

    if (empty($_POST['title'])) {
        echo "El título de la tarea es requerido.";
        exit;
    }

    // Sentencia preparada
    $stmt = $conn->prepare("INSERT INTO tareas (user_id, title, description, start_date, end_date, observations, comentarios, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $nombre_tarea, $description, $start_date, $end_date, $observations, $comentarios, $priority); // <- "isssssss" porque son un entero y siete strings


    if ($stmt->execute()) {
        echo "Tarea asignada correctamente";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Cerrar la conexión
$conn->close();
