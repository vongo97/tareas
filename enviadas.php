<?php
session_start();
require_once('conexion.php'); // Importar la conexión

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

// Consulta para obtener las tareas enviadas del usuario
$sql = "SELECT 
            t.title, 
            t.description,
            t.priority,
            t.start_date, 
            t.end_date, 
            t.completed, 
            u.nombre AS usuario,  
            t.created_at, 
            t.comentarios
    FROM tareas t
    JOIN usuarios u ON t.user_id = u.id 
    WHERE t.user_id = ? ORDER BY t.created_at DESC";

// Preparar la consulta
$stmt = $conn->prepare($sql);


if ($stmt === false) {
    die("Error al preparar la consulta: " . $conn->error);
}

// Vincular el parámetro (user_id es el correcto)
if ($stmt->bind_param("i", $user_id) === false) {
    die("Error al vincular el parámetro: " . $stmt->error);
}

// Ejecutar la consulta
if ($stmt->execute() === false) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$result = $stmt->get_result();


//verificamos si hay resultados

if ($result->num_rows > 0) {
    $tareas = $result->fetch_all(MYSQLI_ASSOC);

    // Mostrar las tareas

    echo "<table class= 'table table-striped'>";
    echo "<thead><tr><th>Título</th><th>Estado</th><th>Descripción</th><th>Prioridad</th><th>Fecha de Inicio</th><th>Fecha de Cierre</th></tr></thead>";
    echo "<tbody>";

    foreach ($tareas as $tarea) {
        echo "<tr>";
        echo "<td>" . $tarea['title'] . "</td>";
        echo "<td>" . $tarea['completed'] . "</td>";
        echo "<td>" . $tarea['description'] . "</td>";
        echo "<td>" . $tarea['priority'] . "</td>";
        echo "<td>" . $tarea['start_date'] . "</td>";
        echo "<td>" . $tarea['end_date'] . "</td>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>No hay tareas enviadas de este usuario.</p>";
}

// Cerrar el statement y la conexión


$conn->close();
$stmt->close();
