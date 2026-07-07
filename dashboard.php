<?php
session_start();
date_default_timezone_set('America/Monterrey');

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}

include '../conexion.php';

$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM Usuario")->fetch_assoc()['total'];
$total_clientes = $conn->query("SELECT COUNT(*) as total FROM Clientes")->fetch_assoc()['total'];
$total_productos = $conn->query("SELECT COUNT(*) as total FROM Productos")->fetch_assoc()['total'];
$total_ventas = $conn->query("SELECT COUNT(*) as total FROM Ventas")->fetch_assoc()['total'];
$total_solicitudes = $conn->query("SELECT COUNT(*) as total FROM Solicitudes_Empleado")->fetch_assoc()['total'];
$solicitudes_pendientes = $conn->query("SELECT COUNT(*) as total FROM Solicitudes_Empleado WHERE Estado_soli = 'pendiente'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | CellRepair</title>
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
        .date-badge {
            background: #f0f2f5;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-info h3 {
            font-size: 0.75rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .stat-info .number {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1e2a3a;
        }
        .stat-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-icon i {
            font-size: 1.4rem;
            color: white;
        }
        .stat-card.pendiente .stat-icon {
            background: linear-gradient(135deg, #ffc107, #ff9800);
        }
        .stat-card.pendiente .number {
            color: #ff9800;
        }
        .quick-actions {
            background: white;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .quick-actions h3 {
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: #1e2a3a;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #1e2a3a;
            transition: all 0.3s;
            border: 1px solid #e9ecef;
            font-size: 0.85rem;
        }
        .action-btn:hover {
            background: #4facfe;
            color: white;
            transform: translateY(-2px);
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
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; text-align: center; gap: 10px; }
        }
    </style>
</head>
<body>
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
                    <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
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

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-tachometer-alt"></i> Panel de Control</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info"><h3>Usuarios</h3><div class="number"><?php echo $total_usuarios; ?></div></div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h3>Clientes</h3><div class="number"><?php echo $total_clientes; ?></div></div>
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h3>Productos</h3><div class="number"><?php echo $total_productos; ?></div></div>
                <div class="stat-icon"><i class="fas fa-box"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h3>Ventas</h3><div class="number"><?php echo $total_ventas; ?></div></div>
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info"><h3>Solicitudes</h3><div class="number"><?php echo $total_solicitudes; ?></div></div>
                <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
            </div>
            <div class="stat-card pendiente">
                <div class="stat-info"><h3>Pendientes</h3><div class="number"><?php echo $solicitudes_pendientes; ?></div></div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
        </div>

        <div class="quick-actions">
            <h3><i class="fas fa-bolt"></i> Acciones rápidas</h3>
            <div class="actions-grid">
                <a href="usuarios.php" class="action-btn"><i class="fas fa-user-plus"></i> Nuevo usuario</a>
                <a href="solicitudes_empleados.php" class="action-btn"><i class="fas fa-file-signature"></i> Solicitudes</a>
                <a href="clientes.php" class="action-btn"><i class="fas fa-user-plus"></i> Nuevo cliente</a>
                <a href="productos.php" class="action-btn"><i class="fas fa-plus-circle"></i> Nuevo producto</a>
                <a href="servicios.php" class="action-btn"><i class="fas fa-plus-circle"></i> Nuevo servicio</a>
                <a href="citas.php" class="action-btn"><i class="fas fa-calendar-plus"></i> Nueva cita</a>
                <a href="ventas.php" class="action-btn"><i class="fas fa-shopping-cart"></i> Nueva venta</a>
                <a href="proveedores.php" class="action-btn"><i class="fas fa-truck"></i> Nuevo proveedor</a>
                <a href="compras.php" class="action-btn"><i class="fas fa-shopping-cart"></i> Nueva compra</a>
            </div>
        </div>
    </div>
</body>
</html>