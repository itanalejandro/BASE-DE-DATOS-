<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

// ===== ESTADÍSTICAS GENERALES =====
$total_ventas = $conn->query("SELECT SUM(Subtotal_venta) as total FROM Ventas")->fetch_assoc()['total'] ?? 0;
$total_clientes = $conn->query("SELECT COUNT(*) as total FROM Clientes")->fetch_assoc()['total'];
$total_productos = $conn->query("SELECT COUNT(*) as total FROM Productos")->fetch_assoc()['total'];
$total_ordenes = $conn->query("SELECT COUNT(*) as total FROM Ordenes_reparacion")->fetch_assoc()['total'];
$total_usuarios = $conn->query("SELECT COUNT(*) as total FROM Usuario")->fetch_assoc()['total'];

// ===== VENTAS POR MES =====
$ventas_por_mes = $conn->query("
    SELECT DATE_FORMAT(Fecha_venta, '%Y-%m') as mes, 
           SUM(Subtotal_venta) as total,
           COUNT(*) as cantidad
    FROM Ventas 
    GROUP BY mes 
    ORDER BY mes DESC 
    LIMIT 6
");

// ===== PRODUCTOS MÁS VENDIDOS =====
$productos_top = $conn->query("
    SELECT p.Nombre_produ, 
           p.Marca, 
           p.Modelo,
           COUNT(v.Id_venta) as total_vendidos,
           SUM(v.Subtotal_venta) as total_ventas
    FROM Ventas v 
    JOIN Productos p ON v.Id_producto = p.Id_producto 
    GROUP BY v.Id_producto 
    ORDER BY total_vendidos DESC 
    LIMIT 5
");

// ===== SERVICIOS MÁS SOLICITADOS =====
$servicios_top = $conn->query("
    SELECT s.Descripcion, 
           COUNT(c.Id_cita) as total_citas,
           s.Costo
    FROM Citas c 
    JOIN Servicio_reparacion s ON c.Id_servicio = s.Id_servicio 
    GROUP BY c.Id_servicio 
    ORDER BY total_citas DESC 
    LIMIT 5
");

// ===== ÓRDENES POR ESTADO =====
$ordenes_estado = $conn->query("
    SELECT Status, 
           COUNT(*) as total 
    FROM Ordenes_reparacion 
    GROUP BY Status
");

// ===== CLIENTES CON MÁS COMPRAS =====
$clientes_top = $conn->query("
    SELECT c.Nombre, 
           c.Apellido_pat,
           COUNT(v.Id_venta) as total_compras,
           SUM(v.Subtotal_venta) as total_gastado
    FROM Clientes c 
    JOIN Ventas v ON c.Id_cliente = v.Id_cliente 
    GROUP BY c.Id_cliente 
    ORDER BY total_gastado DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Admin</title>
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
            border-radius: 20px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 18px 20px;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e2a3a;
        }
        .stat-card .label {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
            display: block;
        }
        .stat-card.ventas .icon { color: #4facfe; }
        .stat-card.clientes .icon { color: #28a745; }
        .stat-card.productos .icon { color: #ffc107; }
        .stat-card.ordenes .icon { color: #ff6b6b; }
        .stat-card.usuarios .icon { color: #6c63ff; }
        
        /* ===== REPORT CARDS ===== */
        .report-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        .report-card h3 {
            font-size: 1.1rem;
            color: #1e2a3a;
            margin-bottom: 15px;
        }
        .report-card h3 i {
            color: #4facfe;
            margin-right: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge-estado {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-estado.pendiente { background: #fff3cd; color: #856404; }
        .badge-estado.en-proceso { background: #cce5ff; color: #004085; }
        .badge-estado.completada { background: #d4edda; color: #155724; }
        .badge-estado.entregada { background: #d1ecf1; color: #0c5460; }
        
        .total-ventas {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
            text-align: right;
            font-size: 1.1rem;
        }
        .total-ventas strong {
            color: #1e2a3a;
            font-size: 1.3rem;
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
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 600px) {
            .header { flex-direction: column; text-align: center; gap: 10px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            table { font-size: 0.7rem; }
            th, td { padding: 6px 8px; }
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
                    <li><a href="garantias_servicio.php"><i class="fas fa-shield-alt"></i> <span>Garantías Servicio</span></a></li>
                    <li><a href="ventas.php"><i class="fas fa-chart-line"></i> <span>Ventas</span></a></li>
                    <li><a href="garantias_venta.php"><i class="fas fa-shield-alt"></i> <span>Garantías Venta</span></a></li>
                    <li><a href="reportes.php" class="active"><i class="fas fa-file-alt"></i> <span>Reportes</span></a></li>
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
            <h1><i class="fas fa-file-alt"></i> Reportes del Sistema</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></div>
        </div>

        <!-- ===== TARJETAS DE ESTADÍSTICAS ===== -->
        <div class="stats-grid">
            <div class="stat-card ventas">
                <span class="icon"><i class="fas fa-chart-line"></i></span>
                <div class="number">$<?php echo number_format($total_ventas, 2); ?></div>
                <div class="label">Total Ventas</div>
            </div>
            <div class="stat-card clientes">
                <span class="icon"><i class="fas fa-user-friends"></i></span>
                <div class="number"><?php echo $total_clientes; ?></div>
                <div class="label">Clientes Registrados</div>
            </div>
            <div class="stat-card productos">
                <span class="icon"><i class="fas fa-box"></i></span>
                <div class="number"><?php echo $total_productos; ?></div>
                <div class="label">Productos</div>
            </div>
            <div class="stat-card ordenes">
                <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                <div class="number"><?php echo $total_ordenes; ?></div>
                <div class="label">Órdenes de Reparación</div>
            </div>
            <div class="stat-card usuarios">
                <span class="icon"><i class="fas fa-users"></i></span>
                <div class="number"><?php echo $total_usuarios; ?></div>
                <div class="label">Usuarios del Sistema</div>
            </div>
        </div>

        <!-- ===== VENTAS POR MES ===== -->
        <div class="report-card">
            <h3><i class="fas fa-chart-line"></i> Ventas por Mes</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Cantidad de Ventas</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($ventas_por_mes->num_rows > 0): ?>
                            <?php while($row = $ventas_por_mes->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $meses = ['01'=>'Enero', '02'=>'Febrero', '03'=>'Marzo', '04'=>'Abril', '05'=>'Mayo', '06'=>'Junio', '07'=>'Julio', '08'=>'Agosto', '09'=>'Septiembre', '10'=>'Octubre', '11'=>'Noviembre', '12'=>'Diciembre'];
                                    $mes = explode('-', $row['mes']);
                                    echo $meses[$mes[1]] . ' ' . $mes[0];
                                    ?>
                                </td>
                                <td><?php echo $row['cantidad']; ?></td>
                                <td><strong>$<?php echo number_format($row['total'], 2); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color:#999;">No hay ventas registradas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== PRODUCTOS MÁS VENDIDOS ===== -->
        <div class="report-card">
            <h3><i class="fas fa-star"></i> Productos Más Vendidos</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Unidades Vendidas</th>
                            <th>Total Vendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($productos_top->num_rows > 0): ?>
                            <?php while($row = $productos_top->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['Nombre_produ']; ?></strong></td>
                                <td><?php echo $row['Marca']; ?></td>
                                <td><?php echo $row['Modelo']; ?></td>
                                <td><?php echo $row['total_vendidos']; ?></td>
                                <td>$<?php echo number_format($row['total_ventas'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#999;">No hay productos vendidos</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== SERVICIOS MÁS SOLICITADOS ===== -->
        <div class="report-card">
            <h3><i class="fas fa-tools"></i> Servicios Más Solicitados</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Costo</th>
                            <th>Total de Citas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($servicios_top->num_rows > 0): ?>
                            <?php while($row = $servicios_top->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['Descripcion']; ?></strong></td>
                                <td>$<?php echo number_format($row['Costo'], 2); ?></td>
                                <td><?php echo $row['total_citas']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color:#999;">No hay servicios solicitados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== ÓRDENES POR ESTADO ===== -->
        <div class="report-card">
            <h3><i class="fas fa-clipboard-list"></i> Órdenes por Estado</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($ordenes_estado->num_rows > 0): ?>
                            <?php while($row = $ordenes_estado->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge-estado <?php echo strtolower(str_replace(' ', '-', $row['Status'])); ?>">
                                        <?php echo $row['Status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['total']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align:center; color:#999;">No hay órdenes registradas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== CLIENTES CON MÁS COMPRAS ===== -->
        <div class="report-card">
            <h3><i class="fas fa-crown"></i> Clientes con Más Compras</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Compras Realizadas</th>
                            <th>Total Gastado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($clientes_top->num_rows > 0): ?>
                            <?php 
                            $contador = 1;
                            while($row = $clientes_top->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                    if($contador == 1) echo '🥇';
                                    elseif($contador == 2) echo '🥈';
                                    elseif($contador == 3) echo '🥉';
                                    else echo '#'.$contador;
                                    ?>
                                </td>
                                <td><strong><?php echo $row['Nombre'] . ' ' . $row['Apellido_pat']; ?></strong></td>
                                <td><?php echo $row['total_compras']; ?></td>
                                <td><strong>$<?php echo number_format($row['total_gastado'], 2); ?></strong></td>
                            </tr>
                            <?php 
                            $contador++;
                            endwhile; 
                            ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:#999;">No hay clientes con compras</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== TOTAL GENERAL ===== -->
        <div class="total-ventas">
            <strong>Total General en Ventas: $<?php echo number_format($total_ventas, 2); ?></strong>
        </div>
    </div>
</body>
</html>