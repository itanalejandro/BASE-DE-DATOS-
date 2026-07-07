<?php
// Configuración para XAMPP (local)
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "venrep_celu";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer charset para evitar problemas con acentos
$conn->set_charset("utf8");
?>