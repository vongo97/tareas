<?php
session_start();
require_once('conexion.php');

// Debug de sesión
var_dump($_SESSION);

if (!isset($_SESSION['user_id'])) {
    die("<div class='alert alert-danger'>No hay sesión activa</div>");
}

$conn = conectarDB();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug de POST
    var_dump($_POST);

    // Sanitizar datos
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $start_date = htmlspecialchars(trim($_POST['start_date']));
    $end_date = htmlspecialchars(trim($_POST['end_date']));
    $priority = htmlspecialchars(trim($_POST['priority']));
    $observations = htmlspecialchars(trim($_POST['observations']));
    $comentarios = htmlspecialchars(trim($_POST['comentarios']));
    $user_id = (int)$_POST['user_id'];
    $asigned_by = (int)$_SESSION['user_id'];

    // Debug de variables
    echo "asigned_by: " . $asigned_by . "<br>";

    // Consulta modificada - notar que hay 9 campos y 9 placeholders
    $sql = "INSERT INTO tareas (
        title,
        description,
        start_date,
        end_date,
        user_id,
        asigned_by,
        priority,
        observations,
        comentarios
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("<div class='alert alert-danger'>Error en prepare: " . $conn->error . "</div>");
    }

    // Modificar bind_param para incluir correctamente asigned_by
    if (!$stmt->bind_param(
        "ssssiisss",
        $title,
        $description,
        $start_date,
        $end_date,
        $user_id,
        $asigned_by, // integer
        $priority,
        $observations,
        $comentarios
    )) {
        die("<div class='alert alert-danger'>Error en bind_param: " . $stmt->error . "</div>");
    }

    // Debug pre-execute
    var_dump([
        'SQL' => $sql,
        'Params' => [
            'title' => $title,
            'description' => $description,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'user_id' => $user_id,
            'asigned_by' => $asigned_by,
            'priority' => $priority,
            'observations' => $observations,
            'comentarios' => $comentarios
        ]
    ]);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Tarea asignada correctamente</div>";
    } else {
        echo "<div class='alert alert-danger'>Error en execute: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

$conn->close();
