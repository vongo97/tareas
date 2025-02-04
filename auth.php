<?php
session_start();
require_once 'conexion.php';

function login($username, $password)
{
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT id, nombre, contrasena, rol FROM usuarios WHERE nombre = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['contrasena'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                return true;
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("Error en login: " . $e->getMessage());
        return false;
    }
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function get_user_role()
{
    return $_SESSION['rol'] ?? null;
}

function logout()
{
    session_destroy();
    header('Location: ingreso.php');
    exit;
}
