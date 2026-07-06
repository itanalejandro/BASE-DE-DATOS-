<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($correo) || empty($password)) {
        echo "<script>alert('❌ Correo y contraseña son obligatorios'); window.location='index.html';</script>";
        exit();
    }

    $sql = "SELECT * FROM Usuario WHERE Correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $usuario = $result->fetch_assoc();
        
        if ($password == $usuario['Password']) {
            $_SESSION['usuario'] = $usuario['Nombre'] . ' ' . $usuario['Apellido_pat'];
            $_SESSION['id'] = $usuario['Id_usuario'];
            $_SESSION['rol'] = $usuario['Rol'];
            $_SESSION['correo'] = $usuario['Correo'];
            
            if ($usuario['Rol'] == 'Admin') {
                header("Location: admin/dashboard.php");
                exit();
            } elseif ($usuario['Rol'] == 'Editor') {
                header("Location: editor/dashboard.php");
                exit();
            } elseif ($usuario['Rol'] == 'Consultor') {
                header("Location: consultor/dashboard.php");
                exit();
            }
        } else {
            echo "<script>alert('❌ Contraseña incorrecta'); window.location='index.html';</script>";
            exit();
        }
    }

    $sql2 = "SELECT * FROM Clientes WHERE Correo_cli = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $correo);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($result2->num_rows == 1) {
        $cliente = $result2->fetch_assoc();
        
        if ($password == $cliente['Password']) {
            $_SESSION['usuario'] = $cliente['Nombre'] . ' ' . $cliente['Apellido_pat'];
            $_SESSION['id'] = $cliente['Id_cliente'];
            $_SESSION['rol'] = 'cliente';
            $_SESSION['correo'] = $cliente['Correo_cli'];
            
            header("Location: cliente/inicio.php");
            exit();
        } else {
            echo "<script>alert('❌ Contraseña incorrecta'); window.location='index.html';</script>";
            exit();
        }
    }

    echo "<script>alert('❌ Usuario no encontrado'); window.location='index.html';</script>";
    exit();
}

header("Location: index.html");
exit();
?>