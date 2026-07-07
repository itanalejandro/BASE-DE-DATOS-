<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

// ===== CREAR NUEVA VENTA =====
if (isset($_POST['crear_venta'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_producto = $_POST['id_producto'];
    $cantidad = $_POST['cantidad'];
    $id_usuario = $_SESSION['id'];
    $fecha_venta = $_POST['fecha_venta'];
    
    if ($cantidad <= 0) {
        $mensaje = '<div class="alert error">❌ La cantidad debe ser mayor a 0</div>';
    } else {
        $producto = $conn->query("SELECT Precio_final, Stock FROM Productos WHERE Id_producto = $id_producto")->fetch_assoc();
        
        if ($producto['Stock'] < $cantidad) {
            $mensaje = '<div class="alert error">❌ Stock insuficiente. Disponible: ' . $producto['Stock'] . '</div>';
        } else {
            $subtotal = $producto['Precio_final'] * $cantidad;
            
            // ✅ AHORA CON fecha_venta (TODO MINÚSCULAS)
            $sql = "INSERT INTO Ventas (fecha_venta, Subtotal_venta, Id_cliente, Id_usuario, Id_producto) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdiii", $fecha_venta, $subtotal, $id_cliente, $id_usuario, $id_producto);
            
            if ($stmt->execute()) {
                $nuevo_stock = $producto['Stock'] - $cantidad;
                $conn->query("UPDATE Productos SET Stock = $nuevo_stock WHERE Id_producto = $id_producto");
                $mensaje = '<div class="alert success">✅ Venta registrada exitosamente</div>';
            } else {
                $mensaje = '<div class="alert error">❌ Error: ' . $stmt->error . '</div>';
            }
        }
    }
}

// ===== ELIMINAR VENTA =====
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    
    $venta = $conn->query("SELECT Id_producto, Subtotal_venta FROM Ventas WHERE Id_venta = $id")->fetch_assoc();
    $producto = $conn->query("SELECT Precio_final FROM Productos WHERE Id_producto = " . $venta['Id_producto'])->fetch_assoc();
    $cantidad = $venta['Subtotal_venta'] / $producto['Precio_final'];
    
    if ($conn->query("DELETE FROM Ventas WHERE Id_venta = $id")) {
        $conn->query("UPDATE Productos SET Stock = Stock + $cantidad WHERE Id_producto = " . $venta['Id_producto']);
        $mensaje = '<div class="alert success">✅ Venta eliminada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar la venta</div>';
    }
}

// ===== CONSULTAS =====
$ventas = $conn->query("SELECT v.*, 
                                cl.Nombre as cliente, 
                                cl.Apellido_pat as cliente_apellido,
                                u.Nombre as usuario,
                                p.Nombre_produ as producto,
                                p.Marca as producto_marca,
                                p.Modelo as producto_modelo
                        FROM Ventas v 
                        JOIN Clientes cl ON v.Id_cliente = cl.Id_cliente 
                        JOIN Usuario u ON v.Id_usuario = u.Id_usuario 
                        JOIN Productos p ON v.Id_producto = p.Id_producto 
                        ORDER BY v.Id_venta DESC");

$total_general = $conn->query("SELECT SUM(Subtotal_venta) as total FROM Ventas")->fetch_assoc()['total'];

$clientes = $conn->query("SELECT Id_cliente, Nombre, Apellido_pat FROM Clientes ORDER BY Nombre");
$productos_lista = $conn->query("SELECT Id_producto, Nombre_produ, Marca, Modelo, Precio_final, Stock FROM Productos WHERE Stock > 0 ORDER BY Nombre_produ");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas - Admin</title>
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
        
        .btn-success {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-success:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40,167,69,0.3); }
        
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
        .btn-primary { background: #4facfe; color: white; }
        .btn-primary:hover { background: #3d8fe0; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        .info-detail { font-size: 0.7rem; color: #6c757d; }
        
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
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white;
            border-radius: 25px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 35px;
            position: relative;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal .close-modal {
            position: sticky;
            top: 0;
            float: right;
            background: none;
            border: none;
            font-size: 1.8rem;
            color: #999;
            cursor: pointer;
            transition: 0.3s;
            z-index: 10;
        }
        .modal .close-modal:hover { color: #333; transform: rotate(90deg); }
        .modal h2 {
            font-size: 1.3rem;
            color: #1e2a3a;
            margin-bottom: 20px;
        }
        .modal h2 i { color: #4facfe; margin-right: 10px; }
        
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #495057;
        }
        .form-group label .required { color: #dc3545; }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.85rem;
            transition: 0.3s;
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79,172,254,0.15);
        }
        
        .precio-info {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 10px;
            margin-top: 10px;
            text-align: center;
            font-size: 0.9rem;
        }
        .precio-info strong {
            font-size: 1.1rem;
            color: #1e2a3a;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-cancel:hover { background: #5a6268; }
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-save:hover { background: #218838; }
        
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
            .modal { padding: 20px; }
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
                    <li><a href="ventas.php" class="active"><i class="fas fa-chart-line"></i> <span>Ventas</span></a></li>
                    <li><a href="garantias_venta.php"><i class="fas fa-shield-alt"></i> <span>Garantías Venta</span></a></li>
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
            <h1><i class="fas fa-chart-line"></i> Gestionar Ventas</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-success" onclick="abrirModalVenta()">
                    <i class="fas fa-plus-circle"></i> Nueva Venta
                </button>
                <span class="badge-count"><i class="fas fa-shopping-cart"></i> Total: <?php echo $ventas->num_rows; ?></span>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <!-- ===== TABLA DE VENTAS ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Ventas</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Vendedor</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ventas->num_rows > 0): ?>
                            <?php while($row = $ventas->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_venta']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_venta'])); ?></td>
                                <td>
                                    <strong><?php echo $row['cliente']; ?></strong>
                                    <br><span class="info-detail"><?php echo $row['cliente_apellido']; ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $row['producto']; ?></strong>
                                    <br><span class="info-detail"><?php echo $row['producto_marca'] . ' ' . $row['producto_modelo']; ?></span>
                                </td>
                                <td><?php echo $row['usuario']; ?></td>
                                <td><strong>$<?php echo number_format($row['Subtotal_venta'], 2); ?></strong></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta venta? Esto restaurará el stock.')">
                                        <input type="hidden" name="id" value="<?php echo $row['Id_venta']; ?>">
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
                                    <i class="fas fa-chart-line" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                    No hay ventas registradas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="total-ventas">
                <strong>Total General: $<?php echo number_format($total_general ?? 0, 2); ?></strong>
            </div>
        </div>
    </div>

    <!-- ===== MODAL NUEVA VENTA ===== -->
    <div class="modal-overlay" id="modalVenta">
        <div class="modal">
            <button class="close-modal" onclick="cerrarModalVenta()">&times;</button>
            <h2><i class="fas fa-shopping-cart"></i> Nueva Venta</h2>
            <form method="POST" id="formVenta">
                <div class="form-group">
                    <label>📅 Fecha de Venta <span class="required">*</span></label>
                    <input type="datetime-local" name="fecha_venta" id="fecha_venta" required>
                </div>
                <div class="form-group">
                    <label>Cliente <span class="required">*</span></label>
                    <select name="id_cliente" id="select_cliente" required>
                        <option value="">Seleccione un cliente</option>
                        <?php 
                        $clientes->data_seek(0);
                        while($cli = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $cli['Id_cliente']; ?>">
                                <?php echo $cli['Nombre'] . ' ' . $cli['Apellido_pat']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Producto <span class="required">*</span></label>
                    <select name="id_producto" id="select_producto" required onchange="actualizarPrecio()">
                        <option value="">Seleccione un producto</option>
                        <?php 
                        $productos_lista->data_seek(0);
                        while($prod = $productos_lista->fetch_assoc()): ?>
                            <option value="<?php echo $prod['Id_producto']; ?>" data-precio="<?php echo $prod['Precio_final']; ?>" data-stock="<?php echo $prod['Stock']; ?>">
                                <?php echo $prod['Nombre_produ'] . ' - ' . $prod['Marca'] . ' ' . $prod['Modelo']; ?> (Stock: <?php echo $prod['Stock']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cantidad <span class="required">*</span></label>
                    <input type="number" name="cantidad" id="cantidad" min="1" value="1" required onchange="calcularTotal()" oninput="calcularTotal()">
                </div>
                <div class="precio-info" id="precio-info">
                    Precio unitario: <strong>$0.00</strong><br>
                    Stock disponible: <strong>0</strong><br>
                    <span style="font-size:1.1rem;">Total: <strong>$0.00</strong></span>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModalVenta()">Cancelar</button>
                    <button type="submit" name="crear_venta" class="btn-save">
                        <i class="fas fa-save"></i> Registrar Venta
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Productos data para JavaScript
        const productos = <?php 
            $productos_data = [];
            $productos_lista->data_seek(0);
            while($row = $productos_lista->fetch_assoc()) {
                $productos_data[$row['Id_producto']] = ['precio' => $row['Precio_final'], 'stock' => $row['Stock']];
            }
            echo json_encode($productos_data);
        ?>;
        
        // Establecer fecha y hora actual por defecto
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const fechaActual = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            document.getElementById('fecha_venta').value = fechaActual;
        });
        
        function actualizarPrecio() {
            const productoSelect = document.getElementById('select_producto');
            const productoId = productoSelect.value;
            const cantidad = parseInt(document.getElementById('cantidad').value) || 1;
            
            if (productoId && productos[productoId]) {
                const precio = productos[productoId].precio;
                const stock = productos[productoId].stock;
                const total = precio * cantidad;
                
                document.getElementById('precio-info').innerHTML = `
                    Precio unitario: <strong>$${precio.toFixed(2)}</strong><br>
                    Stock disponible: <strong>${stock}</strong><br>
                    <span style="font-size:1.1rem;">Total: <strong>$${total.toFixed(2)}</strong></span>
                `;
            } else {
                document.getElementById('precio-info').innerHTML = `
                    Precio unitario: <strong>$0.00</strong><br>
                    Stock disponible: <strong>0</strong><br>
                    <span style="font-size:1.1rem;">Total: <strong>$0.00</strong></span>
                `;
            }
        }
        
        function calcularTotal() {
            actualizarPrecio();
        }
        
        function abrirModalVenta() {
            document.getElementById('modalVenta').classList.add('active');
            // Actualizar fecha al abrir el modal
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const fechaActual = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            document.getElementById('fecha_venta').value = fechaActual;
            actualizarPrecio();
        }
        
        function cerrarModalVenta() {
            document.getElementById('modalVenta').classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalVenta').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalVenta();
        });
    </script>
</body>
</html>