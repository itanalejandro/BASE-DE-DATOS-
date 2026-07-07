<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['agregar'])) {
        $id_proveedor = $_POST['id_proveedor'];
        $num_lote = $_POST['num_lote'];
        $nombre_produ = $_POST['nombre_produ'];
        $marca = $_POST['marca'];
        $modelo = $_POST['modelo'];
        $precio_inicial = $_POST['precio_inicial'];
        $precio_final = $_POST['precio_final'];
        $stock = $_POST['stock'];
        $fecha_llegada = $_POST['fecha_llegada'];
        
        $stmt = $conn->prepare("INSERT INTO Productos (Id_proveedor, Num_lote, Nombre_produ, Marca, Modelo, Precio_inicial, Precio_final, Stock, Fecha_llegada) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssddds", $id_proveedor, $num_lote, $nombre_produ, $marca, $modelo, $precio_inicial, $precio_final, $stock, $fecha_llegada);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert success">✅ Producto agregado correctamente</div>';
        } else {
            $mensaje = '<div class="alert error">❌ Error al agregar producto: ' . $stmt->error . '</div>';
        }
        
    } elseif (isset($_POST['eliminar'])) {
        $id = $_POST['id'];
        if ($conn->query("DELETE FROM Productos WHERE Id_producto = $id")) {
            $mensaje = '<div class="alert success">✅ Producto eliminado correctamente</div>';
        } else {
            $mensaje = '<div class="alert error">❌ Error al eliminar producto</div>';
        }
        
    } elseif (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $precio_final = $_POST['precio_final'];
        $stock = $_POST['stock'];
        
        if ($conn->query("UPDATE Productos SET Precio_final = $precio_final, Stock = $stock WHERE Id_producto = $id")) {
            $mensaje = '<div class="alert success">✅ Producto actualizado correctamente</div>';
        } else {
            $mensaje = '<div class="alert error">❌ Error al actualizar producto</div>';
        }
    }
    
    header("Location: productos.php");
    exit();
}

$productos = $conn->query("SELECT p.*, pr.Nombre_empresa as proveedor 
                           FROM Productos p 
                           LEFT JOIN Proveedores pr ON p.Id_proveedor = pr.Id_proveedor 
                           ORDER BY p.Id_producto DESC");
$proveedores = $conn->query("SELECT Id_proveedor, Nombre_empresa FROM Proveedores ORDER BY Nombre_empresa");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Productos</title>
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
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary { background: #4facfe; color: white; }
        .btn-primary:hover { background: #3d8fe0; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }
        .btn-warning { background: #ffc107; color: black; }
        .btn-warning:hover { background: #e0a800; transform: translateY(-2px); }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 10px;
            align-items: end;
        }
        .form-grid input,
        .form-grid select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 100%;
            font-size: 0.8rem;
            outline: none;
            transition: 0.3s;
        }
        .form-grid input:focus,
        .form-grid select:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79,172,254,0.15);
        }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        
        .stock-bajo { color: #dc3545; font-weight: 700; }
        .stock-medio { color: #ffc107; font-weight: 700; }
        .stock-alto { color: #28a745; font-weight: 700; }
        
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
            .form-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .header { flex-direction: column; text-align: center; gap: 10px; }
            .form-grid { grid-template-columns: 1fr; }
            table { font-size: 0.65rem; }
            th, td { padding: 4px 6px; }
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
                    <li><a href="productos.php" class="active"><i class="fas fa-box"></i> <span>Productos</span></a></li>
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
            <h1><i class="fas fa-box"></i> Gestionar Productos</h1>
            <span class="badge-count"><i class="fas fa-boxes"></i> Total: <?php echo $productos->num_rows; ?></span>
        </div>
        
        <?php echo $mensaje; ?>
        
        <!-- ===== FORMULARIO AGREGAR PRODUCTO ===== -->
        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Agregar Producto</h3>
            <form method="POST" class="form-grid">
                <select name="id_proveedor" required>
                    <option value="">Proveedor</option>
                    <?php while($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?php echo $prov['Id_proveedor']; ?>"><?php echo $prov['Nombre_empresa']; ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="number" name="num_lote" placeholder="Núm. Lote">
                <input type="text" name="nombre_produ" placeholder="Nombre" required>
                <input type="text" name="marca" placeholder="Marca" required>
                <input type="text" name="modelo" placeholder="Modelo" required>
                <input type="number" step="0.01" name="precio_inicial" placeholder="Precio Inicial" required>
                <input type="number" step="0.01" name="precio_final" placeholder="Precio Final" required>
                <input type="number" name="stock" placeholder="Stock" required>
                <input type="datetime-local" name="fecha_llegada">
                <button type="submit" name="agregar" class="btn btn-primary" style="height:42px;">
                    <i class="fas fa-save"></i> Agregar
                </button>
            </form>
        </div>

        <!-- ===== TABLA DE PRODUCTOS ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Productos</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Lote</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Precio Inicial</th>
                            <th>Precio Final</th>
                            <th>Stock</th>
                            <th>Fecha Llegada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productos->num_rows > 0): ?>
                            <?php while($row = $productos->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_producto']; ?></td>
                                <td><?php echo $row['proveedor'] ?? '—'; ?></td>
                                <td><?php echo $row['Num_lote'] ?? '—'; ?></td>
                                <td><?php echo $row['Nombre_produ']; ?></td>
                                <td><?php echo $row['Marca']; ?></td>
                                <td><?php echo $row['Modelo']; ?></td>
                                <td>$<?php echo number_format($row['Precio_inicial'], 2); ?></td>
                                <td><strong>$<?php echo number_format($row['Precio_final'], 2); ?></strong></td>
                                <td>
                                    <?php 
                                    $stock = $row['Stock'];
                                    if ($stock <= 5) {
                                        echo '<span class="stock-bajo">' . $stock . ' ⚠️</span>';
                                    } elseif ($stock <= 15) {
                                        echo '<span class="stock-medio">' . $stock . '</span>';
                                    } else {
                                        echo '<span class="stock-alto">' . $stock . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['Fecha_llegada'] ? date('d/m/Y H:i', strtotime($row['Fecha_llegada'])) : '—'; ?></td>
                                <td>
                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="id" value="<?php echo $row['Id_producto']; ?>">
                                        <input type="number" step="0.01" name="precio_final" placeholder="Precio" style="width:70px; padding:4px; border:1px solid #ddd; border-radius:4px;" required>
                                        <input type="number" name="stock" placeholder="Stock" style="width:60px; padding:4px; border:1px solid #ddd; border-radius:4px;" required>
                                        <button type="submit" name="editar" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button type="submit" name="eliminar" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar producto?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align:center; color:#999; padding:30px;">
                                    <i class="fas fa-box-open" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                    No hay productos registrados
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