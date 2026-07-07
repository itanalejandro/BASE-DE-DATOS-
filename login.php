<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ===== BUSCAR EN USUARIOS (admin, editor, consultor) =====
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
            $_SESSION['nombre'] = $usuario['Nombre'];
            $_SESSION['apellido_pat'] = $usuario['Apellido_pat'];
            $_SESSION['apellido_mat'] = $usuario['Apellido_mat'];
            
            // Redirigir según el rol
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

    // ===== BUSCAR EN CLIENTES =====
    // ¡CORREGIDO! La columna se llama Correo_cli
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
            $_SESSION['correo'] = $cliente['Correo_cli']; // ¡CORREGIDO!
            $_SESSION['nombre'] = $cliente['Nombre'];
            $_SESSION['apellido_pat'] = $cliente['Apellido_pat'];
            $_SESSION['apellido_mat'] = $cliente['Apellido_mat'];
            
            header("Location: cliente/inicio.php");
            exit();
        } else {
            echo "<script>alert('❌ Contraseña incorrecta'); window.location='index.html';</script>";
            exit();
        }
    }

    // Si no se encontró en ninguna tabla
    echo "<script>alert('❌ Usuario no encontrado'); window.location='index.html';</script>";
    exit();
}

header("Location: index.html");
exit();
?>