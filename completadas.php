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

// Consulta para obtener las tareas pendientes del usuario
$sql = "SELECT * FROM tareas WHERE user_id = ? AND completed = 1";


// Preparar la consulta
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

// Vincular el parámetro
$stmt->bind_param("i", $user_id);


// Ejecutar la consulta
if ($stmt->execute() === FALSE) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}
$result = $stmt->get_result();

// Verificar si hay resultados
if ($result->num_rows > 0) {
    $tareas = $result->fetch_all(MYSQLI_ASSOC);

    //verificamos si hay resutlados
    //Mostrar las tareas
    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Título</th><th>Descripción</th><th>Prioridad</th><th>Fecha de Inicio</th><th>Fecha de Cierre</th></tr></thead>";
    echo "<tbody>";
    foreach ($tareas as $tarea) {
        echo "<tr>";
        echo "<td>" . $tarea['title'] . "</td>";
        echo "<td>" . $tarea['description'] . "</td>";
        echo "<td>" . $tarea['priority'] . "</td>";
        echo "<td>" . $tarea['start_date'] . "</td>";
        echo "<td>" . $tarea['end_date'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
} else {
    echo "<p>No hay tareas Completadas de este usuario.</p>";
}

// Cerrar el statement y la conexión
$stmt->close();
$conn->close();



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendientes</title>
</head>

<body>

</body>

</html>