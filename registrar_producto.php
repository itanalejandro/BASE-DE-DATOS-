<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 'editor' && $_SESSION['rol'] != 'admin')) {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    
    $stmt = $conn->prepare("INSERT INTO productos (Nombre, Marca, Modelo, Precio, Stock) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdi", $nombre, $marca, $modelo, $precio, $stock);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Producto registrado exitosamente";
    } else {
        $mensaje = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Producto</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100%; background: linear-gradient(135deg, #1e2a3a, #0f1724); color: white; padding: 30px 20px; }
        .sidebar h2 { font-size: 1.5rem; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid rgba(255,255,255,0.2); }
        .sidebar h2 i { margin-right: 10px; color: #4facfe; }
.sidebar .user-info {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 30px;
    text-align: center;
}

.sidebar .user-info i {
    font-size: 2.5rem;
    margin-bottom: 10px;
    color: white;
    opacity: 0.8;
}

.sidebar .user-info h3 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.sidebar .user-info p {
    font-size: 0.8rem;
    opacity: 0.7;
}        .sidebar nav ul li { margin-bottom: 8px; list-style: none; }
        .sidebar nav ul li a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #e0e0e0; text-decoration: none; border-radius: 10px; transition: all 0.3s; }
        .sidebar nav ul li a:hover { background: rgba(79, 172, 254, 0.2); color: white; }
        .sidebar nav ul li a.active { background: #4facfe; color: white; }
        .logout-btn { position: absolute; bottom: 30px; left: 20px; right: 20px; background: rgba(255,255,255,0.1); color: #ff6b6b; padding: 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .main-content { margin-left: 280px; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 20px; margin-bottom: 30px; }
        .card { background: white; border-radius: 20px; padding: 30px; max-width: 600px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #1e2a3a; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        .btn-primary { background: #4facfe; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 1rem; width: 100%; }
        .mensaje { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .exito { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        @media (max-width: 1000px) { .sidebar { width: 80px; } .sidebar span { display: none; } .main-content { margin-left: 80px; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-mobile-alt"></i> <span>CellRepair</span></h2>
        <div class="user-info"><i class="fas fa-user-circle"></i><h3><?php echo $_SESSION['usuario']; ?></h3><p>Editor</p></div>
        <nav><ul>
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="registrar_cliente.php"><i class="fas fa-user-plus"></i> <span>Registrar Cliente</span></a></li>
            <li><a href="registrar_producto.php" class="active"><i class="fas fa-box"></i> <span>Registrar Producto</span></a></li>
            <li><a href="agendar_cita.php"><i class="fas fa-calendar-plus"></i> <span>Agendar Cita</span></a></li>
            <li><a href="mis_citas.php"><i class="fas fa-calendar-alt"></i> <span>Mis Citas</span></a></li>
            <li><a href="diagnostico.php"><i class="fas fa-stethoscope"></i> <span>Diagnóstico</span></a></li>
            <li><a href="registrar_venta.php"><i class="fas fa-shopping-cart"></i> <span>Registrar Venta</span></a></li>
        </ul></nav>
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a>
    </div>

    <div class="main-content">
        <div class="header"><h1><i class="fas fa-box"></i> Registrar Nuevo Producto</h1></div>
        <div class="card">
            <?php if(isset($mensaje)): ?>
                <div class="mensaje <?php echo strpos($mensaje, '✅') !== false ? 'exito' : 'error'; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Nombre del producto *</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Marca *</label>
                    <input type="text" name="marca" required>
                </div>
                <div class="form-group">
                    <label>Modelo *</label>
                    <input type="text" name="modelo" required>
                </div>
                <div class="form-group">
                    <label>Precio *</label>
                    <input type="number" step="0.01" name="precio" required>
                </div>
                <div class="form-group">
                    <label>Stock inicial *</label>
                    <input type="number" name="stock" value="0" required>
                </div>
                <button type="submit" name="registrar" class="btn-primary">Registrar Producto</button>
            </form>
        </div>
    </div>
</body>
</html>