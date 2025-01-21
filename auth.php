<?php


session_start();
require_once 'conexion.php';
$conn = conectarDB();


function login($username, $password)
{
    global $conn;

    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id, nombre, contrasena FROM usuarios WHERE nombre = ?"); // Seleccionar también la contraseña
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        // Verificar la contraseña (¡importante!)
        if (password_verify($password, $row['contrasena'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['nombre'] = $row['nombre'];
            header('Location: index.php');
            exit;
        }
    }
    return false;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}
