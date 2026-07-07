<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

// ===== PROCESAR AGREGAR =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar'])) {
    $id_proveedor = $_POST['id_proveedor'];
    $fecha_compra = $_POST['fecha_compra'];
    $subtotal_comp = $_POST['subtotal_comp'];
    $total_comp = $_POST['total_comp'];
    $num_remision = $_POST['num_remision'];
    
    $stmt = $conn->prepare("INSERT INTO Compra (Id_proveedor, Fecha_compra, Subtotal_comp, Total_comp, Num_remision) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isddd", $id_proveedor, $fecha_compra, $subtotal_comp, $total_comp, $num_remision);
    
    if ($stmt->execute()) {
        $mensaje = '<div class="alert success">✅ Compra registrada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error: ' . $stmt->error . '</div>';
    }
}

// ===== PROCESAR ELIMINAR =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    if ($conn->query("DELETE FROM Compra WHERE Id_compra = $id")) {
        $mensaje = '<div class="alert success">✅ Compra eliminada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar</div>';
    }
}

// ===== PROCESAR EDITAR =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $id_proveedor = $_POST['id_proveedor'];
    $fecha_compra = $_POST['fecha_compra'];
    $subtotal_comp = $_POST['subtotal_comp'];
    $total_comp = $_POST['total_comp'];
    $num_remision = $_POST['num_remision'];
    
    $stmt = $conn->prepare("UPDATE Compra SET Id_proveedor=?, Fecha_compra=?, Subtotal_comp=?, Total_comp=?, Num_remision=? WHERE Id_compra=?");
    $stmt->bind_param("isdddi", $id_proveedor, $fecha_compra, $subtotal_comp, $total_comp, $num_remision, $id);
    
    if ($stmt->execute()) {
        $mensaje = '<div class="alert success">✅ Compra actualizada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error: ' . $stmt->error . '</div>';
    }
}

$compras = $conn->query("SELECT c.*, p.Nombre_empresa FROM Compra c INNER JOIN Proveedores p ON c.Id_proveedor = p.Id_proveedor ORDER BY c.Id_compra DESC");
$proveedores = $conn->query("SELECT Id_proveedor, Nombre_empresa FROM Proveedores");

$compra_editar = null;
if (isset($_GET['editar'])) {
    $result = $conn->query("SELECT * FROM Compra WHERE Id_compra = " . $_GET['editar']);
    $compra_editar = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compras | CellRepair</title>
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
        
        .btn-add {
            background: #4facfe;
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
        .btn-add:hover { background: #3d8fe0; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(79,172,254,0.3); }
        
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-edit { background: #ffc107; color: #1e2a3a; }
        .btn-edit:hover { background: #e0a800; transform: translateY(-2px); }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; transform: translateY(-2px); }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        
        /* ===== MODAL ===== */
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
            max-width: 600px;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-grid .full { grid-column: span 2; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #495057;
        }
        .form-group label .required { color: #dc3545; }
        .form-group input,
        .form-group select {
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
        .btn-submit {
            background: #4facfe;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.9rem;
            grid-column: span 2;
        }
        .btn-submit:hover { background: #3d8fe0; transform: translateY(-2px); }
        
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
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full { grid-column: span 1; }
            .btn-submit { grid-column: span 1; }
        }
        @media (max-width: 600px) {
            .header { flex-direction: column; text-align: center; gap: 10px; }
            .modal { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full { grid-column: span 1; }
            .btn-submit { grid-column: span 1; }
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
                    <li><a href="compras.php" class="active"><i class="fas fa-shopping-cart"></i> <span>Compras</span></a></li>
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
            <h1><i class="fas fa-shopping-cart"></i> Gestionar Compras</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-add" onclick="openModal('add')">
                    <i class="fas fa-plus-circle"></i> Registrar Compra
                </button>
                <span class="badge-count"><i class="fas fa-file-invoice"></i> Total: <?php echo $compras->num_rows; ?></span>
            </div>
        </div>
        
        <?php echo $mensaje; ?>
        
        <!-- ===== TABLA DE COMPRAS ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Compras</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Subtotal</th>
                            <th>Total</th>
                            <th>Remisión</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($compras->num_rows > 0): ?>
                        <?php while($row = $compras->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Id_compra']; ?></td>
                            <td><strong><?php echo $row['Nombre_empresa']; ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['Fecha_compra'])); ?></td>
                            <td>$<?php echo number_format($row['Subtotal_comp'], 2); ?></td>
                            <td><strong>$<?php echo number_format($row['Total_comp'], 2); ?></strong></td>
                            <td><?php echo $row['Num_remision']; ?></td>
                            <td>
                                <a href="?editar=<?php echo $row['Id_compra']; ?>" class="btn btn-edit btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta compra?')">
                                    <input type="hidden" name="id" value="<?php echo $row['Id_compra']; ?>">
                                    <button type="submit" name="eliminar" class="btn btn-delete btn-sm">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:#999; padding:30px;">
                                <i class="fas fa-shopping-cart" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                No hay compras registradas
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== MODAL AGREGAR ===== -->
    <div class="modal-overlay" id="modalAdd">
        <div class="modal">
            <button class="close-modal" onclick="closeModal('add')">&times;</button>
            <h2><i class="fas fa-plus-circle"></i> Registrar Nueva Compra</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Proveedor <span class="required">*</span></label>
                        <select name="id_proveedor" required>
                            <option value="">Selecciona un proveedor</option>
                            <?php while($prov = $proveedores->fetch_assoc()): ?>
                                <option value="<?php echo $prov['Id_proveedor']; ?>"><?php echo $prov['Nombre_empresa']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Fecha y Hora <span class="required">*</span></label>
                        <input type="datetime-local" name="fecha_compra" required>
                    </div>
                    <div class="form-group">
                        <label>Subtotal <span class="required">*</span></label>
                        <input type="number" step="0.01" name="subtotal_comp" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Total <span class="required">*</span></label>
                        <input type="number" step="0.01" name="total_comp" required placeholder="0.00">
                    </div>
                    <div class="form-group full">
                        <label>Número de Remisión <span class="required">*</span></label>
                        <input type="number" name="num_remision" required placeholder="Número de remisión">
                    </div>
                    <button type="submit" name="agregar" class="btn-submit">
                        <i class="fas fa-save"></i> Registrar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL EDITAR ===== -->
    <div class="modal-overlay <?php echo isset($_GET['editar']) ? 'active' : ''; ?>" id="modalEdit">
        <div class="modal">
            <button class="close-modal" onclick="closeModal('edit')">&times;</button>
            <h2><i class="fas fa-edit"></i> Editar Compra</h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $compra_editar['Id_compra'] ?? ''; ?>">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Proveedor <span class="required">*</span></label>
                        <select name="id_proveedor" required>
                            <option value="">Selecciona un proveedor</option>
                            <?php 
                            $proveedores->data_seek(0);
                            while($prov = $proveedores->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $prov['Id_proveedor']; ?>" <?php echo ($compra_editar['Id_proveedor'] ?? '') == $prov['Id_proveedor'] ? 'selected' : ''; ?>>
                                    <?php echo $prov['Nombre_empresa']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Fecha y Hora <span class="required">*</span></label>
                        <input type="datetime-local" name="fecha_compra" required value="<?php echo isset($compra_editar['Fecha_compra']) ? date('Y-m-d\TH:i', strtotime($compra_editar['Fecha_compra'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Subtotal <span class="required">*</span></label>
                        <input type="number" step="0.01" name="subtotal_comp" required value="<?php echo $compra_editar['Subtotal_comp'] ?? 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>Total <span class="required">*</span></label>
                        <input type="number" step="0.01" name="total_comp" required value="<?php echo $compra_editar['Total_comp'] ?? 0; ?>">
                    </div>
                    <div class="form-group full">
                        <label>Número de Remisión <span class="required">*</span></label>
                        <input type="number" name="num_remision" required value="<?php echo $compra_editar['Num_remision'] ?? 0; ?>">
                    </div>
                    <button type="submit" name="editar" class="btn-submit">
                        <i class="fas fa-save"></i> Actualizar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(type) {
            if (type === 'add') {
                document.getElementById('modalAdd').classList.add('active');
            }
        }
        function closeModal(type) {
            if (type === 'add') {
                document.getElementById('modalAdd').classList.remove('active');
            } else if (type === 'edit') {
                document.getElementById('modalEdit').classList.remove('active');
                if (window.history && window.history.pushState) {
                    window.history.pushState('', '', window.location.pathname);
                }
            }
        }
        document.getElementById('modalAdd').addEventListener('click', function(e) {
            if (e.target === this) closeModal('add');
        });
        document.getElementById('modalEdit').addEventListener('click', function(e) {
            if (e.target === this) closeModal('edit');
        });
    </script>
</body>
</html>