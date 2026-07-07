<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 'editor' && $_SESSION['rol'] != 'admin')) {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];
    
    $check = $conn->prepare("SELECT Id_cliente FROM Clientes WHERE Correo = ?");
    $check->bind_param("s", $correo);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        $mensaje = '<div class="mensaje error"><i class="fas fa-exclamation-circle"></i> ❌ Error: El correo electrónico ya está registrado</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO Clientes (Nombre, Telefono, Correo, Password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $telefono, $correo, $password);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="mensaje exito"><i class="fas fa-check-circle"></i> ✅ Cliente registrado exitosamente</div>';
        } else {
            $mensaje = '<div class="mensaje error"><i class="fas fa-exclamation-circle"></i> ❌ Error: ' . $stmt->error . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Cliente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100%; background: linear-gradient(135deg, #1e2a3a, #0f1724); color: white; padding: 30px 20px; }
        .sidebar h2 { font-size: 1.5rem; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid rgba(255,255,255,0.2); }
        .sidebar h2 i { margin-right: 10px; color: #4facfe; }
        .sidebar .user-info { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; margin-bottom: 30px; text-align: center; }
        .sidebar .user-info i { font-size: 2.5rem; margin-bottom: 10px; color: white; opacity: 0.8; }
        .sidebar .user-info h3 { font-size: 1rem; margin-bottom: 5px; }
        .sidebar .user-info p { font-size: 0.8rem; opacity: 0.7; }
        .sidebar nav ul li { margin-bottom: 8px; list-style: none; }
        .sidebar nav ul li a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #e0e0e0; text-decoration: none; border-radius: 10px; transition: all 0.3s; }
        .sidebar nav ul li a:hover { background: rgba(79, 172, 254, 0.2); color: white; }
        .sidebar nav ul li a.active { background: #4facfe; color: white; }
        .logout-btn { position: absolute; bottom: 30px; left: 20px; right: 20px; background: rgba(255,255,255,0.1); color: #ff6b6b; padding: 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        .main-content { margin-left: 280px; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 20px; margin-bottom: 30px; }
        .card { background: white; border-radius: 20px; padding: 30px; max-width: 500px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #1e2a3a; }
        label i { margin-right: 8px; color: #4facfe; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
        input:focus, select:focus { outline: none; border-color: #4facfe; box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1); }
        .btn-primary { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 1rem; width: 100%; transition: transform 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); background: linear-gradient(135deg, #3a8bcf, #00c8e0); }
        .mensaje { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .requerido { color: #ff6b6b; font-size: 0.8rem; margin-top: 5px; }
        .password-hint { font-size: 0.8rem; color: #666; margin-top: 5px; }
        @media (max-width: 1000px) { .sidebar { width: 80px; } .sidebar span { display: none; } .main-content { margin-left: 80px; } }
    </style>
</head>
<body>
 <div class="sidebar">
    <h2><i class="fas fa-mobile-alt"></i> <span>CellRepair</span></h2>
    
    <div class="user-info">
        <i class="fas fa-user-circle"></i>
        <h3><?php echo $_SESSION['usuario']; ?></h3>
        <p><?php echo $_SESSION['rol'] == 'admin' ? 'Administrador' : 'Editor'; ?></p>
    </div>
    
    <nav>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="registrar_cliente.php"><i class="fas fa-user-plus"></i> <span>Registrar Cliente</span></a></li>
            <li><a href="registrar_producto.php"><i class="fas fa-box"></i> <span>Registrar Producto</span></a></li>
            <li><a href="agendar_cita.php"><i class="fas fa-calendar-plus"></i> <span>Agendar Cita</span></a></li>
            <li><a href="mis_citas.php"><i class="fas fa-calendar-alt"></i> <span>Mis Citas</span></a></li>
            <li><a href="diagnostico.php"><i class="fas fa-stethoscope"></i> <span>Diagnóstico</span></a></li>
            <li><a href="registrar_venta.php"><i class="fas fa-shopping-cart"></i> <span>Registrar Venta</span></a></li>
        </ul>
    </nav>
    
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span>
    </a>
</div>>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-user-plus"></i> Registrar Nuevo Cliente</h1>
        </div>
        <div class="card">
            <?php echo $mensaje; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombre completo *</label>
                    <input type="text" name="nombre" placeholder="Ej: Juan Pérez Gómez" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="tel" name="telefono" placeholder="Ej: 1234567890">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Correo electrónico *</label>
                    <input type="email" name="correo" placeholder="Ej: cliente@email.com" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Contraseña *</label>
                    <input type="password" name="password" placeholder="Ingrese su contraseña" required>
                    <div class="password-hint">La contraseña se guarda como está (texto plano)</div>
                </div>
                
                <button type="submit" name="registrar" class="btn-primary">
                    <i class="fas fa-save"></i> Registrar Cliente
                </button>
            </form>
        </div>
    </div>
</body>
</html>