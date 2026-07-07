<?php
session_start();
include '../conexion.php';

if (!isset($_GET['id'])) {
    die('ID no proporcionado');
}

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM Usuario WHERE Id_usuario = $id");

if ($result->num_rows == 0) {
    die('Usuario no encontrado');
}

header('Content-Type: application/json');
echo json_encode($result->fetch_assoc());
?>