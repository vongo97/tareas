<?php

$conn = null;


function conectarDB()
{
    global $conn;
    if (!isset($conn)) {
        $host =
            'localhost';
        $user =
            'AAA';
        $password =
            '@PACOa4a';
        $db_name =
            'mensajeria';

        try {
            $conn = new mysqli($host, $user, $password, $db_name);
            if ($conn->connect_errno) {
                die("Error de conexión: " . $conn->connect_error);
            }
        } catch (Exception $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    return $conn;
}


$conn = conectarDB();
