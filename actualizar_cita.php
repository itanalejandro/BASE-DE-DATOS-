<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 'editor' && $_SESSION['rol'] != 'admin')) {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

// Verificar que se recibieron los datos
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cita = $_POST['id_cita'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    // Actualizar el estado de la cita
    $sql = "UPDATE Citas SET Estado = ? WHERE Id_cita = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $id_cita);
    
    if ($stmt->execute()) {
        // Redirigir con mensaje de éxito
        header("Location: mis_citas.php?mensaje=exito");
        exit();
    } else {
        // Redirigir con mensaje de error
        header("Location: mis_citas.php?mensaje=error");
        exit();
    }
} else {
    // Si no es POST, redirigir
    header("Location: mis_citas.php");
    exit();
}
?>