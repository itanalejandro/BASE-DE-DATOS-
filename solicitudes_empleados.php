<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

// APROBAR solicitud
if (isset($_POST['aprobar'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("UPDATE Solicitudes_Empleado SET Estado_soli = 'aprobado' WHERE Id_solicitud = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = '<div class="alert success">✅ Solicitud aprobada. El usuario se creará automáticamente.</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al aprobar</div>';
    }
}

// RECHAZAR solicitud
if (isset($_POST['rechazar'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("UPDATE Solicitudes_Empleado SET Estado_soli = 'rechazado' WHERE Id_solicitud = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = '<div class="alert error">❌ Solicitud rechazada</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al rechazar</div>';
    }
}

// ELIMINAR solicitud
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    if ($conn->query("DELETE FROM Solicitudes_Empleado WHERE Id_solicitud = $id")) {
        $mensaje = '<div class="alert success">✅ Solicitud eliminada</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar</div>';
    }
}

// OBTENER ESTADÍSTICAS
$total = $conn->query("SELECT COUNT(*) as total FROM Solicitudes_Empleado")->fetch_assoc()['total'];
$pendientes = $conn->query("SELECT COUNT(*) as total FROM Solicitudes_Empleado WHERE Estado_soli = 'pendiente'")->fetch_assoc()['total'];
$aprobadas = $conn->query("SELECT COUNT(*) as total FROM Solicitudes_Empleado WHERE Estado_soli = 'aprobado'")->fetch_assoc()['total'];
$rechazadas = $conn->query("SELECT COUNT(*) as total FROM Solicitudes_Empleado WHERE Estado_soli = 'rechazado'")->fetch_assoc()['total'];

$solicitudes = $conn->query("SELECT * FROM Solicitudes_Empleado ORDER BY Fecha_solicitud DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitudes de Empleados</title>
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
        
        .btn-nueva-solicitud {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-nueva-solicitud:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.4);
        }
        
        .alert {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
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
        
        table { width: 100%; border-collapse: collapse; font-size: 0.7rem; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; white-space: nowrap; }
        tr:hover { background: #f8f9fa; }
        
        .btn {
            padding: 4px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.65rem;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-warning:hover { background: #e0a800; }
        .btn-sm { padding: 3px 8px; font-size: 0.6rem; }
        
        .estado-pendiente { background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 12px; font-size: 0.6rem; white-space: nowrap; }
        .estado-aprobado { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 12px; font-size: 0.6rem; white-space: nowrap; }
        .estado-rechazado { background: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 12px; font-size: 0.6rem; white-space: nowrap; }
        
        .archivo-link { color: #4facfe; text-decoration: none; font-size: 0.65rem; display: inline-block; }
        .archivo-link:hover { text-decoration: underline; }
        
        .acciones { display: flex; gap: 4px; flex-wrap: wrap; }

        /* ===== ESTADÍSTICAS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 18px 20px;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .stat-info h3 {
            font-size: 0.7rem;
            color: #6c757d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .stat-info .number {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1e2a3a;
        }
        .stat-card .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
        }
        .stat-card.total .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-card.pendiente .stat-icon { background: linear-gradient(135deg, #ffc107, #ff9800); }
        .stat-card.aprobado .stat-icon { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-card.rechazado .stat-icon { background: linear-gradient(135deg, #dc3545, #e74c3c); }
        .stat-card.total .number { color: #4facfe; }
        .stat-card.pendiente .number { color: #ff9800; }
        .stat-card.aprobado .number { color: #28a745; }
        .stat-card.rechazado .number { color: #dc3545; }

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
            table { font-size: 0.6rem; }
            th, td { padding: 4px 6px; }
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
                    <li><a href="solicitudes_empleados.php" class="active"><i class="fas fa-file-signature"></i> <span>Solicitudes</span></a></li>
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

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-signature"></i> Solicitudes de Empleados</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="../iniciosoli.php" class="btn-nueva-solicitud">
                    <i class="fas fa-plus-circle"></i> Nueva Solicitud
                </a>
                <span style="font-size:0.8rem;color:#666;">Total: <?php echo $total; ?></span>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <!-- ===== ESTADÍSTICAS ===== -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-info">
                    <h3><i class="fas fa-inbox"></i> Total</h3>
                    <div class="number"><?php echo $total; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-file-signature"></i></div>
            </div>
            <div class="stat-card pendiente">
                <div class="stat-info">
                    <h3><i class="fas fa-clock"></i> Pendientes</h3>
                    <div class="number"><?php echo $pendientes; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
            <div class="stat-card aprobado">
                <div class="stat-info">
                    <h3><i class="fas fa-check-circle"></i> Aprobadas</h3>
                    <div class="number"><?php echo $aprobadas; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-check"></i></div>
            </div>
            <div class="stat-card rechazado">
                <div class="stat-info">
                    <h3><i class="fas fa-times-circle"></i> Rechazadas</h3>
                    <div class="number"><?php echo $rechazadas; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-times"></i></div>
            </div>
        </div>

        <!-- ===== TABLA DE SOLICITUDES ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Solicitudes</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="min-width:40px;">ID</th>
                            <th style="min-width:120px;">Nombre</th>
                            <th style="min-width:130px;">Correo</th>
                            <th style="min-width:80px;">Teléfono</th>
                            <th style="min-width:140px;">CURP</th>
                            <th style="min-width:90px;">NSS</th>
                            <th style="min-width:80px;">Estado Civil</th>
                            <th style="min-width:70px;">Estado</th>
                            <th style="min-width:150px;">Documentos</th>
                            <th style="min-width:80px;">Fecha</th>
                            <th style="min-width:150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $solicitudes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Id_solicitud']; ?></td>
                            <td><?php echo $row['Nombre'] . ' ' . $row['Apellido_pat'] . ' ' . $row['Apellido_mat']; ?></td>
                            <td style="font-size:0.65rem;"><?php echo $row['Correo']; ?></td>
                            <td><?php echo $row['Telefono']; ?></td>
                            <td style="font-size:0.6rem;"><?php echo $row['CURP']; ?></td>
                            <td><?php echo $row['NSS']; ?></td>
                            <td><?php echo $row['Estado_civil']; ?></td>
                            <td>
                                <span class="estado-<?php echo $row['Estado_soli']; ?>">
                                    <?php echo ucfirst($row['Estado_soli']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['Certificado_estudio']): ?>
                                    <a href="../uploads/solicitudes/<?php echo $row['Certificado_estudio']; ?>" class="archivo-link" target="_blank">
                                        📄 <?php echo substr($row['Certificado_estudio'], 0, 20); ?>...
                                    </a><br>
                                <?php endif; ?>
                                <?php if($row['Comprobante_domicilio']): ?>
                                    <a href="../uploads/solicitudes/<?php echo $row['Comprobante_domicilio']; ?>" class="archivo-link" target="_blank">
                                        🏠 <?php echo substr($row['Comprobante_domicilio'], 0, 20); ?>...
                                    </a>
                                <?php endif; ?>
                                <?php if(!$row['Certificado_estudio'] && !$row['Comprobante_domicilio']): ?>
                                    <span style="color:#999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($row['Fecha_solicitud'])); ?></td>
                            <td>
                                <div class="acciones">
                                    <?php if($row['Estado_soli'] == 'pendiente'): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?php echo $row['Id_solicitud']; ?>">
                                            <button type="submit" name="aprobar" class="btn btn-success btn-sm" title="Aprobar">✅</button>
                                            <button type="submit" name="rechazar" class="btn btn-danger btn-sm" title="Rechazar">❌</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if($row['Estado_soli'] != 'pendiente'): ?>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?php echo $row['Id_solicitud']; ?>">
                                            <button type="submit" name="eliminar" class="btn btn-warning btn-sm" onclick="return confirm('¿Eliminar esta solicitud?')" title="Eliminar">🗑️</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($solicitudes->num_rows == 0): ?>
                        <tr>
                            <td colspan="11" style="text-align:center; color:#999; padding:30px;">
                                <i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                No hay solicitudes de empleados registradas
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