<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$precio_min = isset($_GET['precio_min']) ? $_GET['precio_min'] : '';
$precio_max = isset($_GET['precio_max']) ? $_GET['precio_max'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$marca_filtro = isset($_GET['marca']) ? $_GET['marca'] : '';

$sql_where = [];
if ($precio_min !== '' && $precio_max !== '') {
    $sql_where[] = "Precio BETWEEN $precio_min AND $precio_max";
}
if ($busqueda !== '') {
    $sql_where[] = "Nombre LIKE '%$busqueda%'";
}
if ($marca_filtro !== '') {
    if ($marca_filtro === 'otras') {
        $sql_where[] = "Marca NOT IN ('iphone', 'samsung', 'motorola', 'xiaomi')";
    } else {
        $sql_where[] = "Marca = '$marca_filtro'";
    }
}
$where_clause = !empty($sql_where) ? "WHERE " . implode(" AND ", $sql_where) : "";

$productos = $conn->query("SELECT * FROM Productos $where_clause ORDER BY Precio DESC");

$resumen_marcas = $conn->query("
    SELECT 
        Marca, 
        COUNT(*) as cantidad, 
        AVG(Precio) as precio_promedio,
        SUM(Stock) as stock_total,
        MAX(Precio) as mas_caro,
        MIN(Precio) as mas_barato
    FROM Productos 
    GROUP BY Marca 
    ORDER BY cantidad DESC
");

// Productos con "Pro"
$productos_pro = $conn->query("SELECT Nombre, Marca, Precio FROM Productos WHERE Nombre LIKE '%Pro%' ORDER BY Precio DESC");

// Otras marcas (NOT IN)
$otras_marcas = $conn->query("SELECT Marca, COUNT(*) as cantidad FROM Productos WHERE Marca NOT IN ('iphone', 'samsung') GROUP BY Marca");

// Vista
$vista_resultados = $conn->query("SELECT * FROM VistaReporteAvanzado");

// Cursor 1
$cursor_resultados = null;
try {
    $cursor_resultados = $conn->query("CALL ResumenVentasPorProducto()");
    $conn->next_result();
} catch (Exception $e) {
    $cursor_resultados = null;
}

// Cursor 2: Productos con Stock Bajo
$cursor2 = null;
try {
    $cursor2 = $conn->query("CALL ProductosConStockBajo()");
    $conn->next_result(); 
} catch (Exception $e) {
    $cursor2 = null;
    echo "Error: " . $e->getMessage();
}
// Mostrar vistas
$vista1 = $conn->query("SELECT * FROM VistaReporteAvanzado");
if (!$vista1) $vista1 = null;

$vista2 = $conn->query("SELECT * FROM VistaProductosPremium");
if (!$vista2) $vista2 = null;

$vista3 = $conn->query("SELECT * FROM VistaClientesConCitas");
if (!$vista3) $vista3 = null;

// Estadísticas para tarjetas
$total_productos = $conn->query("SELECT COUNT(*) as total FROM Productos")->fetch_assoc()['total'];
$total_marcas = $conn->query("SELECT COUNT(DISTINCT Marca) as total FROM Productos")->fetch_assoc()['total'];
$stock_total = $conn->query("SELECT SUM(Stock) as total FROM Productos")->fetch_assoc()['total'];
$precio_promedio_global = $conn->query("SELECT AVG(Precio) as promedio FROM Productos")->fetch_assoc()['promedio'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes Avanzados - Admin</title>
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
                       .sidebar nav ul li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    color: #e0e0e0;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s;
    font-size: 0.85rem;
}

.sidebar nav ul li a i {
    width: 20px;
    font-size: 0.9rem;
}
        .sidebar nav ul li a.active { background: #4facfe; color: white; }
        .logout-btn { position: absolute; bottom: 30px; left: 20px; right: 20px; background: rgba(255,255,255,0.1); color: #ff6b6b; padding: 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        .main-content { margin-left: 280px; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 2rem; color: #4facfe; margin-bottom: 10px; }
        .stat-card p { color: #666; font-size: 0.9rem; }
        .report-card { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .report-card h3 { margin-bottom: 20px; color: #1e2a3a; border-left: 4px solid #4facfe; padding-left: 15px; }
        .report-card h3 i { margin-right: 10px; color: #4facfe; }
        .filter-bar { background: #f8f9fa; padding: 20px; border-radius: 15px; margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 0.8rem; font-weight: 600; color: #666; }
        .filter-group input, .filter-group select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-primary { background: #4facfe; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: 600; color: #1e2a3a; }
        tr:hover { background: #f5f5f5; }
        .badge { background: #e9ecef; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; }
        .date-badge { background: #4facfe; color: white; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem; }
        @media (max-width: 1000px) { .sidebar { width: 80px; } .sidebar span { display: none; } .main-content { margin-left: 80px; } }
        @media (max-width: 800px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
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
        
        <nav>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="clientes.php"><i class="fas fa-user-friends"></i> <span>Clientes</span></a></li>
                <li><a href="productos.php"><i class="fas fa-box"></i> <span>Productos</span></a></li>
                <li><a href="servicios.php"><i class="fas fa-tools"></i> <span>Servicios</span></a></li>
                <li><a href="citas.php"><i class="fas fa-calendar-alt"></i> <span>Citas</span></a></li>
                <li><a href="ordenes.php"><i class="fas fa-clipboard-list"></i> <span>Órdenes</span></a></li>
                <li><a href="ventas.php"><i class="fas fa-chart-line"></i> <span>Ventas</span></a></li>
                <li><a href="reportes.php"><i class="fas fa-file-alt"></i> <span>Reportes</span></a></li>
                <li><a href="reportes_avanzados.php" class="active"><i class="fas fa-chart-bar"></i> <span>Avanzado</span></a></li>
            </ul>
        </nav>
        
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span>
        </a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> Reportes Avanzados</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y'); ?></div>
        </div>

        <!-- ==================== TARJETAS DE ESTADÍSTICAS ==================== -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_productos; ?></h3>
                <p>Total Productos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_marcas; ?></h3>
                <p>Marcas Diferentes</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stock_total; ?></h3>
                <p>Stock Total</p>
            </div>
            <div class="stat-card">
                <h3>$<?php echo number_format($precio_promedio_global, 2); ?></h3>
                <p>Precio Promedio</p>
            </div>
        </div>

        <div class="report-card">
            <h3><i class="fas fa-filter"></i> Filtros de Productos</h3>
            <form method="GET" class="filter-bar">
                <div class="filter-group">
                    <label>Precio entre </label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="precio_min" placeholder="Mínimo" value="<?php echo $precio_min; ?>" style="width:100px;">
                        <span>-</span>
                        <input type="number" name="precio_max" placeholder="Máximo" value="<?php echo $precio_max; ?>" style="width:100px;">
                    </div>
                </div>
                <div class="filter-group">
                    <label>Buscar</label>
                    <input type="text" name="busqueda" placeholder="Nombre del producto..." value="<?php echo $busqueda; ?>">
                </div>
                <div class="filter-group">
                    <label>Marcas</label>
                    <select name="marca">
                        <option value="">Todas</option>
                        <option value="iphone" <?php echo $marca_filtro == 'iphone' ? 'selected' : ''; ?>>iPhone</option>
                        <option value="samsung" <?php echo $marca_filtro == 'samsung' ? 'selected' : ''; ?>>Samsung</option>
                        <option value="motorola" <?php echo $marca_filtro == 'motorola' ? 'selected' : ''; ?>>Motorola</option>
                        <option value="xiaomi" <?php echo $marca_filtro == 'xiaomi' ? 'selected' : ''; ?>>Xiaomi</option>
                        <option value="otras" <?php echo $marca_filtro == 'otras' ? 'selected' : ''; ?>>Otras marcas (NOT IN)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                </div>
            </form>

            <div style="overflow-x:auto;">
                <h4>Productos filtrados <span class="badge"><?php echo $productos->num_rows; ?> resultados</span></h4>
                <table>
                    <thead><tr><th>Nombre</th><th>Marca</th><th>Modelo</th><th>Precio</th><th>Stock</th></tr></thead>
                    <tbody>
                        <?php while($row = $productos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Nombre']; ?></td>
                            <td><?php echo $row['Marca']; ?></td>
                            <td><?php echo $row['Modelo']; ?></td>
                            <td>$<?php echo number_format($row['Precio'], 2); ?></td>
                            <td><?php echo $row['Stock']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($productos->num_rows == 0): ?>
                            <tr><td colspan="5">No hay productos con esos filtros</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Vista 3: Clientes con Citas -->
<div class="report-card">
    <h3><i class="fas fa-users"></i> Vista: Clientes con Citas</h3>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>ID Cliente</th><th>Nombre</th><th>Correo</th><th>Total Citas</th></tr>
            </thead>
            <tbody>
                <?php while($row = $vista3->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['Id_cliente']; ?></td>
                    <td><?php echo $row['Nombre']; ?></td>
                    <td><?php echo $row['Correo']; ?></td>
                    <td><?php echo $row['total_citas']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Segundo cursor: Productos con stock bajo -->
<!-- Sección del segundo cursor -->
<div class="report-card">
    <h3><i class="fas fa-exclamation-triangle"></i> Cursor: Productos con Stock Bajo</h3>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th>Stock</th>
                    <th>Clasificación</th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($cursor2) && $cursor2 && $cursor2->num_rows > 0): ?>
                    <?php while($row = $cursor2->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Id_producto']; ?></td>
                            <td><?php echo $row['Nombre']; ?></td>
                            <td><?php echo $row['Marca']; ?></td>
                            <td><?php echo $row['Stock']; ?></td>
                            <td>
                                <span style="background: #ff6b6b; color: white; padding: 4px 12px; border-radius: 20px;">
                                    <?php echo $row['Clasificacion']; ?>
                                </span>
                            </span>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No hay productos con stock bajo (menos de 10 unidades)</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Vista 2: Productos Premium -->
<div class="report-card">
    <h3><i class="fas fa-gem"></i> Vista: Productos Premium</h3>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Marca</th><th>Precio</th><th>Stock</th><th>Gama</th></tr>
            </thead>
            <tbody>
                <?php while($row = $vista2->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['Id_producto']; ?></td>
                    <td><?php echo $row['Nombre']; ?></td>
                    <td><?php echo $row['Marca']; ?></td>
                    <td>$<?php echo number_format($row['Precio'], 2); ?></td>
                    <td><?php echo $row['Stock']; ?></td>
                    <td><span class="badge"><?php echo $row['gama']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
        <!-- ==================== RESUMEN POR MARCA (GROUP BY) ==================== -->
        <div class="report-card">
            <h3><i class="fas fa-chart-pie"></i> Resumen por Marca</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr><th>Marca</th><th>Cantidad</th><th>Precio Promedio</th><th>Stock Total</th><th>Más Caro</th><th>Más Barato</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resumen_marcas->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo ucfirst($row['Marca']); ?></strong></td>
                            <td><?php echo $row['cantidad']; ?></td>
                            <td>$<?php echo number_format($row['precio_promedio'], 2); ?></td>
                            <td><?php echo $row['stock_total']; ?></td>
                            <td>$<?php echo number_format($row['mas_caro'], 2); ?></td>
                            <td>$<?php echo number_format($row['mas_barato'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== PRODUCTOS CON "Pro" (LIKE) ==================== -->
        <div class="report-card">
            <h3><i class="fas fa-tag"></i> Productos que contienen "Pro"</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Nombre</th><th>Marca</th><th>Precio</th></tr></thead>
                    <tbody>
                        <?php while($row = $productos_pro->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Nombre']; ?></td>
                            <td><?php echo $row['Marca']; ?></td>
                            <td>$<?php echo number_format($row['Precio'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== OTRAS MARCAS (NOT IN) ==================== -->
        <div class="report-card">
            <h3><i class="fas fa-ban"></i> Otras Marcas</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Marca</th><th>Cantidad de Productos</th></tr></thead>
                    <tbody>
                        <?php while($row = $otras_marcas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo ucfirst($row['Marca']); ?></td>
                            <td><?php echo $row['cantidad']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== VISTA SQL ==================== -->
        <div class="report-card">
            <h3><i class="fas fa-eye"></i> Reporte Avanzado</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr><th>Marca</th><th>Total Prod.</th><th>Precio Prom.</th><th>Precio Mín.</th><th>Precio Máx.</th><th>Stock Total</th><th>Con "Pro"</th>
                        <th>Rango Medio</th><th>Otras Marcas</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $vista_resultados->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo ucfirst($row['Marca']); ?></td>
                            <td><?php echo $row['total_productos']; ?></td>
                            <td>$<?php echo number_format($row['precio_promedio'], 2); ?></td>
                            <td>$<?php echo number_format($row['precio_minimo'], 2); ?></td>
                            <td>$<?php echo number_format($row['precio_maximo'], 2); ?></td>
                            <td><?php echo $row['stock_total']; ?></td>
                            <td><?php echo $row['productos_con_pro']; ?></td>
                            <td><?php echo $row['productos_rango_medio']; ?></td>
                            <td><?php echo $row['otras_marcas']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== CURSOR ==================== -->
        <div class="report-card">
            <h3><i class="fas fa-database"></i> Resumen de Ventas por Producto</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr><th>ID Producto</th><th>Nombre</th><th>Total Vendido</th><th>Cantidad de Ventas</th></tr>
                    </thead>
                    <tbody>
                        <?php if($cursor_resultados && $cursor_resultados->num_rows > 0): ?>
                            <?php while($row = $cursor_resultados->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_producto']; ?></td>
                                <td><?php echo $row['Nombre']; ?></td>
                                <td>$<?php echo number_format($row['TotalVendido'] ?? 0, 2); ?></td>
                                <td><?php echo $row['CantidadVentas'] ?? 0; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php $cursor_resultados->free(); ?>
                        <?php else: ?>
                            <tr><td colspan="4">No hay ventas registradas</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>