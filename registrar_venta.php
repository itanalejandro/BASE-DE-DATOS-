<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 'editor' && $_SESSION['rol'] != 'admin')) {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$clientes = $conn->query("SELECT Id_cliente, Nombre FROM Clientes ORDER BY Nombre");
$productos = $conn->query("SELECT Id_producto, Nombre, Marca, Modelo, Precio, Stock FROM Productos WHERE Stock > 0 ORDER BY Nombre");
$vendedores = $conn->query("SELECT Id_usuario, Nombre FROM Usuario ORDER BY Nombre");

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_producto = $_POST['id_producto'];
    $cantidad = $_POST['cantidad'];
    $id_usuario = $_POST['id_vendedor'];
    
    $producto = $conn->query("SELECT Precio, Stock FROM Productos WHERE Id_producto = $id_producto")->fetch_assoc();
    
    if ($producto['Stock'] < $cantidad) {
        $mensaje = "❌ Stock insuficiente. Disponible: " . $producto['Stock'];
        $tipo_mensaje = "error";
    } else {
        $total = $producto['Precio'] * $cantidad;
        $fecha = date('Y-m-d');
        
        $sql = "INSERT INTO Ventas (Fecha, Total, Id_cliente, Id_usuario, Id_producto) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdiii", $fecha, $total, $id_cliente, $id_usuario, $id_producto);
        
        if ($stmt->execute()) {
            $nuevo_stock = $producto['Stock'] - $cantidad;
            $conn->query("UPDATE Productos SET Stock = $nuevo_stock WHERE Id_producto = $id_producto");
            $mensaje = "✅ Venta registrada exitosamente. Total: $" . number_format($total, 2);
            $tipo_mensaje = "exito";
            
            // Recargar productos para el select
            $productos = $conn->query("SELECT Id_producto, Nombre, Marca, Modelo, Precio, Stock FROM Productos WHERE Stock > 0 ORDER BY Nombre");
        } else {
            $mensaje = "❌ Error al registrar venta: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }
}

$productos = $conn->query("SELECT Id_producto, Nombre, Marca, Modelo, Precio, Stock FROM Productos WHERE Stock > 0 ORDER BY Nombre");

$productos_array = [];
$productos_temp = $conn->query("SELECT Id_producto, Precio, Stock FROM Productos");
while($row = $productos_temp->fetch_assoc()) {
    $productos_array[$row['Id_producto']] = [
        'precio' => floatval($row['Precio']),
        'stock' => intval($row['Stock'])
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Venta - Editor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        
        .sidebar {
            position: fixed; left: 0; top: 0; width: 280px; height: 100%;
            background: linear-gradient(135deg, #1e2a3a, #0f1724); color: white;
            padding: 30px 20px; overflow-y: auto;
        }
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
}        .sidebar nav ul { list-style: none; }
        .sidebar nav ul li { margin-bottom: 8px; }
        .sidebar nav ul li a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #e0e0e0; text-decoration: none; border-radius: 10px; transition: all 0.3s; }
        .sidebar nav ul li a:hover { background: rgba(79, 172, 254, 0.2); color: white; }
        .sidebar nav ul li a.active { background: #4facfe; color: white; }
        .logout-btn { position: absolute; bottom: 30px; left: 20px; right: 20px; background: rgba(255,255,255,0.1); color: #ff6b6b; padding: 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; gap: 12px; }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        
        .main-content { margin-left: 280px; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 20px; margin-bottom: 30px; }
        .card { background: white; border-radius: 20px; padding: 30px; max-width: 650px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #1e2a3a; }
        label i { margin-right: 8px; color: #4facfe; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; }
        input:focus, select:focus { outline: none; border-color: #4facfe; }
        .btn-primary { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 1rem; width: 100%; transition: transform 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); }
        .mensaje { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .precio-info { margin-top: 15px; padding: 15px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 12px; text-align: center; font-size: 1rem; }
        .precio-info strong { font-size: 1.2rem; color: #4facfe; }
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
        <div class="header">
            <h1><i class="fas fa-shopping-cart"></i> Registrar Venta</h1>
        </div>

        <div class="card">
            <?php if($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>">
                    <i class="fas <?php echo $tipo_mensaje == 'exito' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="ventaForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Cliente *</label>
                    <select name="id_cliente" required>
                        <option value="">-- Seleccione un cliente --</option>
                        <?php 
                        $clientes->data_seek(0);
                        while($row = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $row['Id_cliente']; ?>"><?php echo htmlspecialchars($row['Nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-box"></i> Producto *</label>
                    <select name="id_producto" id="producto" required onchange="actualizarInfoProducto()">
                        <option value="">-- Seleccione un producto --</option>
                        <?php 
                        $productos->data_seek(0);
                        while($row = $productos->fetch_assoc()): ?>
                            <option value="<?php echo $row['Id_producto']; ?>" 
                                    data-precio="<?php echo $row['Precio']; ?>" 
                                    data-stock="<?php echo $row['Stock']; ?>">
                                <?php echo htmlspecialchars($row['Nombre'] . ' ' . $row['Marca'] . ' ' . $row['Modelo']); ?> - $<?php echo number_format($row['Precio'], 2); ?> (Stock: <?php echo $row['Stock']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Cantidad *</label>
                    <input type="number" name="cantidad" id="cantidad" min="1" value="1" required oninput="calcularTotal()">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user-tie"></i> Vendedor *</label>
                    <select name="id_vendedor" required>
                        <option value="">-- Seleccione un vendedor --</option>
                        <?php 
                        $vendedores->data_seek(0);
                        while($row = $vendedores->fetch_assoc()): ?>
                            <option value="<?php echo $row['Id_usuario']; ?>" <?php echo ($row['Id_usuario'] == $_SESSION['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['Nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="precio-info" id="precio-info">
                    <i class="fas fa-info-circle"></i> Seleccione un producto para ver el total
                </div>
                
                <button type="submit" name="registrar" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Registrar Venta
                </button>
            </form>
        </div>
    </div>

    <script>
        const productosData = <?php echo json_encode($productos_array); ?>;
        
        function actualizarInfoProducto() {
            const productoSelect = document.getElementById('producto');
            const productoId = productoSelect.value;
            const cantidadInput = document.getElementById('cantidad');
            const cantidad = parseInt(cantidadInput.value) || 1;
            
            const infoDiv = document.getElementById('precio-info');
            
            if (productoId && productosData[productoId]) {
                const precio = productosData[productoId].precio;
                const stock = productosData[productoId].stock;
                const total = precio * cantidad;
                
                if (cantidad > stock) {
                    infoDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle" style="color: #ff6b6b;"></i>
                        ⚠️ Stock insuficiente<br>
                        Precio unitario: $${precio.toFixed(2)}<br>
                        Stock disponible: ${stock}<br>
                        <strong style="color: #ff6b6b;">Cantidad excede el stock disponible</strong>
                    `;
                } else {
                    infoDiv.innerHTML = `
                        <i class="fas fa-tag"></i> Precio unitario: <strong>$${precio.toFixed(2)}</strong><br>
                        <i class="fas fa-boxes"></i> Stock disponible: ${stock}<br>
                        <i class="fas fa-chart-line"></i> <strong style="font-size: 1.2rem;">Total: $${total.toFixed(2)}</strong>
                    `;
                }
            } else {
                infoDiv.innerHTML = `<i class="fas fa-info-circle"></i> Seleccione un producto para ver el total`;
            }
        }
        
        function calcularTotal() {
            const cantidadInput = document.getElementById('cantidad');
            let cantidad = parseInt(cantidadInput.value);
            
            if (isNaN(cantidad) || cantidad < 1) {
                cantidadInput.value = 1;
                cantidad = 1;
            }
            
            actualizarInfoProducto();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            actualizarInfoProducto();
        });
    </script>
</body>
</html>