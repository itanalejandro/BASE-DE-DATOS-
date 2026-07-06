<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['logged_in' => false]);
    exit();
}

echo json_encode([
    'logged_in' => true,
    'nombre' => $_SESSION['usuario'],
    'rol' => $_SESSION['rol'],
    'email' => $_SESSION['correo'] ?? ''
]);
?>