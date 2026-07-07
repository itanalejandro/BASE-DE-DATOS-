<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

// ===== CREAR NUEVA ORDEN =====
if (isset($_POST['crear_orden'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_servicio = $_POST['id_servicio'];
    $diagnostico = $_POST['diagnostico'];
    $estado = $_POST['estado'];
    $costo_final = !empty($_POST['costo_final']) ? $_POST['costo_final'] : null;
    $id_usuario = $_SESSION['id'];
    
    $sql = "INSERT INTO Ordenes_reparacion (Id_cliente, Id_servicio, Id_usuario, Diagnostico, Status, Costo_total) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissd", $id_cliente, $id_servicio, $id_usuario, $diagnostico, $estado, $costo_final);
    
    if ($stmt->execute()) {
        $orden_id = $stmt->insert_id;
        
        // Si la orden se crea con estado 'Completada' o 'Entregada', crear garantía
        if ($estado == 'Completada' || $estado == 'Entregada') {
            $sql_garantia = "INSERT INTO Garantia_servicio (Id_orden, Id_cliente, Id_servicio, Id_usuario, Fecha_entrega) 
                             VALUES (?, ?, ?, ?, NOW())";
            $stmt_garantia = $conn->prepare($sql_garantia);
            $stmt_garantia->bind_param("iiii", $orden_id, $id_cliente, $id_servicio, $id_usuario);
            
            if ($stmt_garantia->execute()) {
                $mensaje = '<div class="alert success">✅ Orden creada y garantía generada exitosamente</div>';
            } else {
                $mensaje = '<div class="alert warning">⚠️ Orden creada pero error al generar garantía: ' . $stmt_garantia->error . '</div>';
            }
        } else {
            $mensaje = '<div class="alert success">✅ Orden creada exitosamente</div>';
        }
    } else {
        $mensaje = '<div class="alert error">❌ Error al crear la orden: ' . $conn->error . '</div>';
    }
}

// ===== ACTUALIZAR ORDEN =====
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $costo_final = $_POST['costo_final'];
    
    // Obtener datos actuales de la orden ANTES de actualizar
    $orden_actual = $conn->query("SELECT Status, Id_cliente, Id_servicio, Id_usuario FROM Ordenes_reparacion WHERE Id_orden = $id")->fetch_assoc();
    
    if (!$orden_actual) {
        $mensaje = '<div class="alert error">❌ Orden no encontrada</div>';
    } else {
        $estado_anterior = $orden_actual['Status'];
        
        // Actualizar la orden
        $stmt = $conn->prepare("UPDATE Ordenes_reparacion SET Status = ?, Costo_total = ? WHERE Id_orden = ?");
        $stmt->bind_param("sdi", $estado, $costo_final, $id);
        
        if ($stmt->execute()) {
            // ===== CREAR GARANTÍA SI EL NUEVO ESTADO ES 'Completada' o 'Entregada' =====
            if (($estado == 'Completada' || $estado == 'Entregada') && $estado != $estado_anterior) {
                $sql_garantia = "INSERT INTO Garantia_servicio (Id_orden, Id_cliente, Id_servicio, Id_usuario, Fecha_entrega) 
                                 VALUES (?, ?, ?, ?, NOW())";
                $stmt_garantia = $conn->prepare($sql_garantia);
                $stmt_garantia->bind_param("iiii", $id, $orden_actual['Id_cliente'], $orden_actual['Id_servicio'], $orden_actual['Id_usuario']);
                
                if ($stmt_garantia->execute()) {
                    $mensaje = '<div class="alert success">✅ Orden actualizada y garantía generada correctamente</div>';
                } else {
                    $mensaje = '<div class="alert warning">⚠️ Orden actualizada pero error al generar garantía: ' . $stmt_garantia->error . '</div>';
                }
            } else {
                $mensaje = '<div class="alert success">✅ Orden actualizada correctamente</div>';
            }
        } else {
            $mensaje = '<div class="alert error">❌ Error al actualizar la orden: ' . $stmt->error . '</div>';
        }
    }
}

// ===== ELIMINAR ORDEN =====
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    
    // Primero eliminar la garantía si existe
    $conn->query("DELETE FROM Garantia_servicio WHERE Id_orden = $id");
    
    if ($conn->query("DELETE FROM Ordenes_reparacion WHERE Id_orden = $id")) {
        $mensaje = '<div class="alert success">✅ Orden eliminada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar la orden</div>';
    }
}

// ===== CONSULTAS =====
$ordenes = $conn->query("SELECT o.*, 
                                cl.Nombre as cliente, 
                                cl.Apellido_pat as cliente_apellido,
                                s.Descripcion as servicio,
                                u.Nombre as usuario
                         FROM Ordenes_reparacion o 
                         JOIN Clientes cl ON o.Id_cliente = cl.Id_cliente 
                         JOIN Servicio_reparacion s ON o.Id_servicio = s.Id_servicio
                         JOIN Usuario u ON o.Id_usuario = u.Id_usuario
                         ORDER BY o.Id_orden DESC");

$clientes = $conn->query("SELECT Id_cliente, Nombre, Apellido_pat FROM Clientes ORDER BY Nombre");
$servicios = $conn->query("SELECT Id_servicio, Descripcion FROM Servicio_reparacion WHERE Status = 'Activo' ORDER BY Descripcion");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Órdenes - Admin</title>
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
        .btn-primary { background: #4facfe; color: white; }
        .btn-primary:hover { background: #3d8fe0; transform: translateY(-2px); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        
        .estado-pendiente { background: #fff3cd; color: #856404; padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .estado-en-proceso { background: #cce5ff; color: #004085; padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .estado-completada { background: #d4edda; color: #155724; padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .estado-entregada { background: #d1ecf1; color: #0c5460; padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        
        .acciones-form {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            align-items: center;
        }
        .acciones-form select {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.7rem;
        }
        .acciones-form input[type="number"] {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.7rem;
            width: 80px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.85rem;
            transition: 0.3s;
            outline: none;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79,172,254,0.15);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
            .acciones-form { flex-direction: column; align-items: stretch; }
            .acciones-form select,
            .acciones-form input[type="number"] { width: 100%; }
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
                    <li><a href="liquidacion.php"><i class="fas fa-tags"></i> <span>Liquidación</span></a></li>
                    <li><a href="servicios.php"><i class="fas fa-tools"></i> <span>Servicios</span></a></li>
                    <li><a href="citas.php"><i class="fas fa-calendar-alt"></i> <span>Citas</span></a></li>
                    <li><a href="ordenes.php" class="active"><i class="fas fa-clipboard-list"></i> <span>Órdenes</span></a></li>
                    <li><a href="garantias_servicio.php"><i class="fas fa-shield-alt"></i> <span>Garantías Servicio</span></a></li>
                    <li><a href="ventas.php"><i class="fas fa-chart-line"></i> <span>Ventas</span></a></li>
                    <li><a href="garantias_venta.php"><i class="fas fa-shield-alt"></i> <span>Garantías Venta</span></a></li>
                    <li><a href="reportes.php"><i class="fas fa-file-alt"></i> <span>Reportes</span></a></li>
                    <li><a href="reportes_avanzados.php"><i class="fas fa-chart-bar"></i> <span>Avanzado</span></a></li>
                </ul>
            </nav>
        </div>
        
        <div class="logout-wrapper">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-clipboard-list"></i> Órdenes de Reparación</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-success" onclick="abrirModalOrden()">
                    <i class="fas fa-plus-circle"></i> Nueva Orden
                </button>
                <span class="badge-count"><i class="fas fa-clipboard-list"></i> Total: <?php echo $ordenes->num_rows; ?></span>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Órdenes</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Usuario</th>
                            <th>Diagnóstico</th>
                            <th>Status</th>
                            <th>Costo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ordenes->num_rows > 0): ?>
                            <?php while($row = $ordenes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_orden']; ?></td>
                                <td>
                                    <strong><?php echo $row['cliente']; ?></strong>
                                    <br><small><?php echo $row['cliente_apellido']; ?></small>
                                </td>
                                <td><?php echo $row['servicio']; ?></td>
                                <td><?php echo $row['usuario']; ?></td>
                                <td><?php echo $row['Diagnostico'] ?: '—'; ?></td>
                                <td>
                                    <span class="estado-<?php echo strtolower(str_replace(' ', '-', $row['Status'])); ?>">
                                        <?php echo $row['Status']; ?>
                                    </span>
                                </td>
                                <td><strong>$<?php echo number_format($row['Costo_total'] ?? 0, 2); ?></strong></td>
                                <td>
                                    <div class="acciones-form">
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?php echo $row['Id_orden']; ?>">
                                            <select name="estado">
                                                <option value="Pendiente" <?php echo $row['Status'] == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="En proceso" <?php echo $row['Status'] == 'En proceso' ? 'selected' : ''; ?>>En proceso</option>
                                                <option value="Completada" <?php echo $row['Status'] == 'Completada' ? 'selected' : ''; ?>>Completada</option>
                                                <option value="Entregada" <?php echo $row['Status'] == 'Entregada' ? 'selected' : ''; ?>>Entregada</option>
                                            </select>
                                            <input type="number" step="0.01" name="costo_final" placeholder="Costo" value="<?php echo $row['Costo_total']; ?>">
                                            <button type="submit" name="actualizar" class="btn btn-primary btn-sm">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar esta orden?')">
                                            <input type="hidden" name="id" value="<?php echo $row['Id_orden']; ?>">
                                            <button type="submit" name="eliminar" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; color:#999; padding:30px;">
                                    <i class="fas fa-clipboard-list" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                    No hay órdenes de reparación registradas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== MODAL NUEVA ORDEN ===== -->
    <div class="modal-overlay" id="modalOrden">
        <div class="modal">
            <button class="close-modal" onclick="cerrarModalOrden()">&times;</button>
            <h2><i class="fas fa-clipboard-list"></i> Nueva Orden de Reparación</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Cliente <span class="required">*</span></label>
                    <select name="id_cliente" required>
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
                    <label>Servicio <span class="required">*</span></label>
                    <select name="id_servicio" required>
                        <option value="">Seleccione un servicio</option>
                        <?php 
                        $servicios->data_seek(0);
                        while($serv = $servicios->fetch_assoc()): ?>
                            <option value="<?php echo $serv['Id_servicio']; ?>"><?php echo $serv['Descripcion']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Diagnóstico</label>
                    <textarea name="diagnostico" rows="3" placeholder="Describa el diagnóstico del equipo..."></textarea>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="Pendiente">Pendiente</option>
                        <option value="En proceso">En proceso</option>
                        <option value="Completada">Completada</option>
                        <option value="Entregada">Entregada</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Costo final</label>
                    <input type="number" step="0.01" name="costo_final" placeholder="0.00">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModalOrden()">Cancelar</button>
                    <button type="submit" name="crear_orden" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Orden
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalOrden() {
            document.getElementById('modalOrden').classList.add('active');
        }
        function cerrarModalOrden() {
            document.getElementById('modalOrden').classList.remove('active');
        }
        document.getElementById('modalOrden').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalOrden();
        });
    </script>
</body>
</html>