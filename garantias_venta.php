<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

// ===== ELIMINAR GARANTÍA =====
if (isset($_POST['eliminar'])) {
    $id_venta = $_POST['id_venta'];
    if ($conn->query("DELETE FROM Garantia_venta WHERE Id_venta = $id_venta")) {
        $mensaje = '<div class="alert success">✅ Garantía eliminada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar la garantía</div>';
    }
}

// ===== CONSULTA DE GARANTÍAS =====
$garantias = $conn->query("
    SELECT gv.*, 
           cl.Nombre as cliente_nombre, 
           cl.Apellido_pat as cliente_apellido,
           u.Nombre as usuario_nombre,
           s.Descripcion as servicio_descripcion,
           v.Subtotal_venta,
           v.fecha_venta
    FROM Garantia_venta gv
    JOIN Clientes cl ON gv.Id_cliente = cl.Id_cliente
    JOIN Usuario u ON gv.Id_usuario = u.Id_usuario
    JOIN Servicio_reparacion s ON gv.Id_servicio = s.Id_servicio
    JOIN Ventas v ON gv.Id_venta = v.Id_venta
    ORDER BY gv.Fecha_entrega DESC
");

$total_garantias = $garantias->num_rows;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Garantías de Venta - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #1a1a2e; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100%;
            background: linear-gradient(135deg, #1e2a3a, #0f1724);
            color: white;
            padding: 20px 15px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: #4facfe; border-radius: 10px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        
        .sidebar h2 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            text-align: center;
            flex-shrink: 0;
        }
        .sidebar h2 i { margin-right: 8px; color: #4facfe; }
        
        .sidebar .user-info {
            background: rgba(255,255,255,0.1);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        .sidebar .user-info i { font-size: 2.2rem; margin-bottom: 8px; opacity: 0.8; }
        .sidebar .user-info h3 { font-size: 0.9rem; margin-bottom: 3px; }
        .sidebar .user-info p { font-size: 0.7rem; opacity: 0.7; }
        
        .sidebar .menu-wrapper {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 10px;
        }
        .sidebar .menu-wrapper::-webkit-scrollbar { width: 3px; }
        .sidebar .menu-wrapper::-webkit-scrollbar-thumb { background: #4facfe; border-radius: 10px; }
        
        .sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar nav ul li { margin-bottom: 2px; }
        .sidebar nav ul li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            color: #e0e0e0;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 0.8rem;
            min-height: 36px;
            white-space: nowrap;
        }
        .sidebar nav ul li a i {
            width: 20px;
            font-size: 0.9rem;
            text-align: center;
            flex-shrink: 0;
        }
        .sidebar nav ul li a span { font-size: 0.8rem; line-height: 1.2; }
        .sidebar nav ul li a:hover { background: rgba(79, 172, 254, 0.2); color: white; }
        .sidebar nav ul li a.active { background: #4facfe; color: white; }
        
        .sidebar .logout-wrapper {
            flex-shrink: 0;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.05);
            color: #ff6b6b;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 0.8rem;
            min-height: 36px;
        }
        .logout-btn i { width: 20px; text-align: center; }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        
        .main-content {
            margin-left: 260px;
            padding: 25px;
        }
        .header {
            background: white;
            padding: 18px 22px;
            border-radius: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .header h1 {
            font-size: 1.5rem;
            color: #1e2a3a;
        }
        .header h1 i {
            color: #4facfe;
            margin-right: 10px;
        }
        .header .badge-count {
            background: #f0f2f5;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .alert {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.warning { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        
        .card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        .card h3 {
            font-size: 1.1rem;
            color: #1e2a3a;
            margin-bottom: 15px;
        }
        .card h3 i { color: #4facfe; margin-right: 8px; }
        
        .btn {
            padding: 6px 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        .info-detail { font-size: 0.7rem; color: #6c757d; }
        .badge-garantia {
            background: #28a745;
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
            font-weight: 600;
        }
        
        @media (max-width: 1000px) {
            .sidebar { width: 70px; padding: 15px 10px; }
            .sidebar span, .sidebar .user-info p { display: none; }
            .sidebar .user-info h3 { font-size: 0.7rem; }
            .sidebar h2 { font-size: 1rem; }
            .sidebar .user-info i { font-size: 1.5rem; }
            .sidebar nav ul li a { padding: 8px 6px; justify-content: center; }
            .sidebar nav ul li a i { width: auto; font-size: 1.1rem; }
            .logout-btn { justify-content: center; padding: 8px 6px; }
            .logout-btn i { width: auto; font-size: 1.1rem; }
            .main-content { margin-left: 70px; padding: 15px; }
        }
        @media (max-width: 600px) {
            .header { flex-direction: column; text-align: center; gap: 10px; }
            table { font-size: 0.7rem; }
            th, td { padding: 6px 8px; }
            .btn { font-size: 0.65rem; padding: 4px 8px; }
        }
    </style>
</head>
<body>
    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">
        <h2><i class="fas fa-mobile-alt"></i> <span>CellRepair</span></h2>
        
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <h3><?php echo $_SESSION['usuario']; ?></h3>
            <p>Administrador</p>
        </div>
        
        <div class="menu-wrapper">
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="usuarios.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                    <li><a href="solicitudes_empleados.php"><i class="fas fa-file-signature"></i> <span>Solicitudes</span></a></li>
                    <li><a href="documentos_empleados.php"><i class="fas fa-folder-open"></i> <span>Documentos Empleados</span></a></li>
                    <li><a href="clientes.php"><i class="fas fa-user-friends"></i> <span>Clientes</span></a></li>
                    <li><a href="productos.php"><i class="fas fa-box"></i> <span>Productos</span></a></li>
                    <li><a href="proveedores.php"><i class="fas fa-truck"></i> <span>Proveedores</span></a></li>
                    <li><a href="compras.php"><i class="fas fa-shopping-cart"></i> <span>Compras</span></a></li>
                    <li><a href="liquidaciones.php"><i class="fas fa-tags"></i> <span>Liquidación</span></a></li>
                    <li><a href="servicios.php"><i class="fas fa-tools"></i> <span>Servicios</span></a></li>
                    <li><a href="citas.php"><i class="fas fa-calendar-alt"></i> <span>Citas</span></a></li>
                    <li><a href="ordenes.php"><i class="fas fa-clipboard-list"></i> <span>Órdenes</span></a></li>
                    <li><a href="garantias_servicio.php"><i class="fas fa-shield-alt"></i> <span>Garantías Servicio</span></a></li>
                    <li><a href="ventas.php"><i class="fas fa-chart-line"></i> <span>Ventas</span></a></li>
                    <li><a href="garantias_venta.php" class="active"><i class="fas fa-shield-alt"></i> <span>Garantías Venta</span></a></li>
                    <li><a href="reportes.php"><i class="fas fa-file-alt"></i> <span>Reportes</span></a></li>
                </ul>
            </nav>
        </div>
        
        <div class="logout-wrapper">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span>
            </a>
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Garantías de Venta</h1>
            <span class="badge-count"><i class="fas fa-shield-alt"></i> Total: <?php echo $total_garantias; ?></span>
        </div>

        <?php echo $mensaje; ?>

        <!-- ===== TABLA DE GARANTÍAS ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Garantías de Venta</h3>
            <?php if ($total_garantias > 0): ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Venta</th>
                                <th>Cliente</th>
                                <th>Servicio Garantizado</th>
                                <th>Usuario</th>
                                <th>Monto Venta</th>
                                <th>Fecha Venta</th>
                                <th>Fecha Entrega</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $garantias->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_venta']; ?></td>
                                <td>
                                    <strong><?php echo $row['cliente_nombre']; ?></strong>
                                    <br><span class="info-detail"><?php echo $row['cliente_apellido']; ?></span>
                                </td>
                                <td><?php echo $row['servicio_descripcion']; ?></td>
                                <td><?php echo $row['usuario_nombre']; ?></td>
                                <td><strong>$<?php echo number_format($row['Subtotal_venta'], 2); ?></strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_venta'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['Fecha_entrega'])); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar esta garantía?')">
                                        <input type="hidden" name="id_venta" value="<?php echo $row['Id_venta']; ?>">
                                        <button type="submit" name="eliminar" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; color:#999; padding:40px;">
                    <i class="fas fa-shield-alt" style="font-size:3rem; display:block; margin-bottom:15px;"></i>
                    <p>No hay garantías de venta registradas</p>
                    <p style="font-size:0.8rem; margin-top:5px;">Las garantías se generan automáticamente al registrar una venta</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>