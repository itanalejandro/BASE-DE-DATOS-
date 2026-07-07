<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 'editor' && $_SESSION['rol'] != 'admin')) {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = '';
$tipo_mensaje = '';

$clientes = $conn->query("SELECT Id_cliente, Nombre FROM Clientes ORDER BY Nombre");
$servicios = $conn->query("SELECT Id_servicio, Descripcion, Costo FROM Servicio_reparacion WHERE Estado = 'Activo' ORDER BY Descripcion");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agendar'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_servicio = $_POST['id_servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    
    $check = $conn->query("SELECT Id_cliente FROM Clientes WHERE Id_cliente = '$id_cliente'");
    if ($check->num_rows == 0) {
        $mensaje = "❌ El cliente no existe";
        $tipo_mensaje = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO Citas (Id_cliente, Id_servicio, Fecha, Hora, Descripcion, Estado) VALUES (?, ?, ?, ?, ?, 'Programada')");
        $stmt->bind_param("iisss", $id_cliente, $id_servicio, $fecha, $hora, $descripcion);
        
        if ($stmt->execute()) {
            $cita_id = $stmt->insert_id;
            
            $stmt_orden = $conn->prepare("INSERT INTO Ordenes_reparacion (Id_cliente, Id_servicio, Diagnostico, Estado, Costo_final) VALUES (?, ?, ?, 'En proceso', 0)");
            $stmt_orden->bind_param("iis", $id_cliente, $id_servicio, $descripcion);
            $stmt_orden->execute();
            
            $mensaje = "✅ Cita agendada correctamente";
            $tipo_mensaje = "exito";
            
            // Redirigir después de 2 segundos
            echo "<script>setTimeout(function() { window.location='agendar_cita.php'; }, 2000);</script>";
        } else {
            $mensaje = "❌ Error: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita</title>
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
}
        .sidebar nav ul li { margin-bottom: 8px; list-style: none; }
        .sidebar nav ul li a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #e0e0e0; text-decoration: none; border-radius: 10px; transition: all 0.3s; }
        .sidebar nav ul li a:hover { background: rgba(79, 172, 254, 0.2); color: white; }
        .sidebar nav ul li a.active { background: #4facfe; color: white; }
        .logout-btn { position: absolute; bottom: 30px; left: 20px; right: 20px; background: rgba(255,255,255,0.1); color: #ff6b6b; padding: 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        .main-content { margin-left: 280px; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 20px; margin-bottom: 30px; }
        .card { background: white; border-radius: 20px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #1e2a3a; }
        label i { margin-right: 8px; color: #4facfe; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #4facfe; }
        .btn-primary { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 1rem; width: 100%; transition: transform 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); }
        .mensaje { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
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
</div>

    <div class="main-content">
        <div class="header"><h1><i class="fas fa-calendar-plus"></i> Agendar Nueva Cita</h1></div>
        <div class="card">
            <?php if($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <i class="fas <?php echo $tipo_mensaje == 'exito' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Cliente *</label>
                    <select name="id_cliente" required>
                        <option value="">Seleccione un cliente</option>
                        <?php 
                        $clientes->data_seek(0);
                        while($row = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $row['Id_cliente']; ?>"><?php echo htmlspecialchars($row['Nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-tools"></i> Servicio *</label>
                    <select name="id_servicio" required>
                        <option value="">Seleccione un servicio</option>
                        <?php 
                        $servicios->data_seek(0);
                        while($row = $servicios->fetch_assoc()): ?>
                            <option value="<?php echo $row['Id_servicio']; ?>"><?php echo htmlspecialchars($row['Descripcion']); ?> - $<?php echo number_format($row['Costo'], 2); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Fecha *</label>
                    <input type="date" name="fecha" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Hora *</label>
                    <input type="time" name="hora" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-file-alt"></i> Descripción del problema</label>
                    <textarea name="descripcion" rows="4" placeholder="Describa el problema..."></textarea>
                </div>
                <button type="submit" name="agendar" class="btn-primary"><i class="fas fa-save"></i> 📅 Agendar Cita</button>
            </form>
        </div>
    </div>
</body>
</html>