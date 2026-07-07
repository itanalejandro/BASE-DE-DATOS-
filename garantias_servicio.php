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
    $id_orden = $_POST['id_orden'];
    $id_cliente = $_POST['id_cliente'];
    $id_servicio = $_POST['id_servicio'];
    $id_usuario = $_POST['id_usuario'];
    
    $stmt = $conn->prepare("DELETE FROM Garantia_servicio WHERE Id_orden = ? AND Id_cliente = ? AND Id_servicio = ? AND Id_usuario = ?");
    $stmt->bind_param("iiii", $id_orden, $id_cliente, $id_servicio, $id_usuario);
    
    if ($stmt->execute()) {
        $mensaje = '<div class="alert success">✅ Garantía eliminada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar la garantía</div>';
    }
}

$garantias = $conn->query("
    SELECT g.*, 
           c.Nombre as cliente_nombre, 
           c.Apellido_pat as cliente_apellido,
           c.Telefono as cliente_telefono,
           u.Nombre as usuario_nombre,
           u.Apellido_pat as usuario_apellido,
           s.Descripcion as servicio_desc,
           s.Costo as servicio_costo
    FROM Garantia_servicio g
    INNER JOIN Clientes c ON g.Id_cliente = c.Id_cliente
    INNER JOIN Usuario u ON g.Id_usuario = u.Id_usuario
    INNER JOIN Servicio_reparacion s ON g.Id_servicio = s.Id_servicio
    ORDER BY g.Fecha_entrega DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Garantías Servicio | CellRepair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #1a1a2e; }
        
        /* ===== SIDEBAR ===== */
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
        
        /* ===== MENU - CRECIBLE ===== */
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
        
        /* ===== BOTÓN CERRAR SESIÓN FIJO AL FINAL ===== */
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
        
        /* ===== MAIN CONTENT ===== */
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
        .header .badge-auto {
            background: #4facfe;
            color: white;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .badge-garantia {
            background: #d4edda;
            color: #155724;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
            font-weight: 600;
        }
        .badge-garantia i { margin-right: 4px; }
        
        .info-detail {
            font-size: 0.7rem;
            color: #6c757d;
        }
        
        /* ===== RESPONSIVE ===== */
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
            .header { flex-wrap: wrap; gap: 10px; }
        }
        @media (max-width: 600px) {
            .header { flex-direction: column; text-align: center; gap: 10px; }
            .header .badge-auto { width: 100%; justify-content: center; }
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
        
        <!-- ===== MENÚ DESPLAZABLE ===== -->
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
                    <li><a href="garantias_servicio.php" class="active"><i class="fas fa-shield-alt"></i> <span>Garantías Servicio</span></a></li>
                    <li><a href="ventas.php"><i class="fas fa-chart-line"></i> <span>Ventas</span></a></li>
                    <li><a href="garantias_venta.php"><i class="fas fa-shield-alt"></i> <span>Garantías Venta</span></a></li>
                    <li><a href="reportes.php"><i class="fas fa-file-alt"></i> <span>Reportes</span></a></li>
                </ul>
            </nav>
        </div>
        
        <!-- ===== BOTÓN CERRAR SESIÓN FIJO ===== -->
        <div class="logout-wrapper">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span>
            </a>
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Garantías de Servicio</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="badge-auto">
                    <i class="fas fa-info-circle"></i> Se generan automáticamente
                </span>
                <span class="badge-count"><i class="fas fa-shield-alt"></i> Total: <?php echo $garantias->num_rows; ?></span>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <!-- ===== TABLA DE GARANTÍAS ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Garantías de Servicio</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Usuario</th>
                            <th>Fecha Entrega</th>
                            <th>Status</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($garantias->num_rows > 0): ?>
                            <?php while($row = $garantias->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $row['Id_orden']; ?></strong>
                                    <br><span class="info-detail">Costo: $<?php echo number_format($row['servicio_costo'], 2); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $row['cliente_nombre']; ?></strong>
                                    <br><span class="info-detail"><?php echo $row['cliente_apellido']; ?></span>
                                    <br><span class="info-detail">📱 <?php echo $row['cliente_telefono']; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $row['servicio_desc']; ?></strong>
                                </td>
                                <td>
                                    <?php echo $row['usuario_nombre']; ?>
                                    <br><span class="info-detail"><?php echo $row['usuario_apellido']; ?></span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($row['Fecha_entrega'])); ?>
                                </td>
                                <td>
                                    <span class="badge-garantia">
                                        <i class="fas fa-check-circle"></i> Activa
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta garantía?')">
                                        <input type="hidden" name="id_orden" value="<?php echo $row['Id_orden']; ?>">
                                        <input type="hidden" name="id_cliente" value="<?php echo $row['Id_cliente']; ?>">
                                        <input type="hidden" name="id_servicio" value="<?php echo $row['Id_servicio']; ?>">
                                        <input type="hidden" name="id_usuario" value="<?php echo $row['Id_usuario']; ?>">
                                        <button type="submit" name="eliminar" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#999; padding:30px;">
                                    <i class="fas fa-shield-alt" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                    No hay garantías de servicio registradas
                                    <br><span class="info-detail">Las garantías se generan automáticamente al completar una orden</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>