<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

// Crear nueva cita
if (isset($_POST['crear_cita'])) {
    $id_cliente = $_POST['id_cliente'];
    $id_servicio = $_POST['id_servicio'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $descripcion = $_POST['descripcion'];
    $id_usuario = $_SESSION['id']; // ✅ Obtener el usuario de la sesión
    
    // 1. VALIDACIÓN: Comprobar si ya existe una cita programada a la misma fecha y hora
    $sql_verificar = "SELECT Id_cita FROM Citas WHERE Fecha = ? AND Hora = ? AND Status != 'Cancelada'";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("ss", $fecha, $hora);
    $stmt_verificar->execute();
    $stmt_verificar->store_result();
    
    if ($stmt_verificar->num_rows > 0) {
        // Ya existe una cita en ese horario
        $mensaje = '<div class="alert warning">⚠️ Lo sentimos, ya existe una cita programada para la fecha y hora seleccionadas.</div>';
    } else {
        // ✅ CORREGIDO: Ahora incluimos Id_usuario
        $sql = "INSERT INTO Citas (Id_cliente, Id_servicio, Id_usuario, Fecha, Hora, Descripcion, Status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Programada')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisss", $id_cliente, $id_servicio, $id_usuario, $fecha, $hora, $descripcion);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert success">✅ Cita creada exitosamente</div>';
        } else {
            $mensaje = '<div class="alert error">❌ Error al crear la cita: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
    $stmt_verificar->close();
}

// Actualizar estado de cita
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar'])) {
    $id = $_POST['id'];
    $estado = $_POST['status'];
    
    // ✅ CORREGIDO: Cambiar "Estado" a "Status" (el nombre correcto de la columna)
    $sql_update = "UPDATE Citas SET Status = ? WHERE Id_cita = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $estado, $id);
    
    if ($stmt_update->execute()) {
        $mensaje = '<div class="alert success">✅ Estado actualizado correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al actualizar estado</div>';
    }
    $stmt_update->close();
}

// Eliminar cita
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    if ($conn->query("DELETE FROM Citas WHERE Id_cita = $id")) {
        $mensaje = '<div class="alert success">✅ Cita eliminada correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar cita</div>';
    }
}

$citas = $conn->query("SELECT c.*, cl.Nombre as cliente, cl.Apellido_pat as cliente_apellido, s.Descripcion as servicio 
                       FROM Citas c 
                       JOIN Clientes cl ON c.Id_cliente = cl.Id_cliente 
                       JOIN Servicio_reparacion s ON c.Id_servicio = s.Id_servicio 
                       ORDER BY c.Fecha DESC, c.Hora DESC");

// Obtener listas para selects
$clientes = $conn->query("SELECT Id_cliente, Nombre, Apellido_pat FROM Clientes ORDER BY Nombre");
$servicios = $conn->query("SELECT Id_servicio, Descripcion FROM Servicio_reparacion WHERE Status = 'Activo' ORDER BY Descripcion");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Citas - Admin</title>
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
        
        .estado-Programada { background: #fff3cd; color: #856404; padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .estado-Completada { background: #d4edda; color: #155724; padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        .estado-Cancelada { background: #f8d7da; color: #721c24; padding: 3px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-block; font-weight: 600; }
        
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
            .acciones-form { flex-direction: column; align-items: stretch; }
            .acciones-form select { width: 100%; }
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
                    <li><a href="citas.php" class="active"><i class="fas fa-calendar-alt"></i> <span>Citas</span></a></li>
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
            <h1><i class="fas fa-calendar-alt"></i> Gestionar Citas</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-success" onclick="abrirModalCita()">
                    <i class="fas fa-plus-circle"></i> Nueva Cita
                </button>
                <span class="badge-count"><i class="fas fa-calendar-check"></i> Total: <?php echo $citas->num_rows; ?></span>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <!-- ===== TABLA DE CITAS ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Citas</h3>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Servicio</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Descripción</th>
                            <th>Status</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($citas->num_rows > 0): ?>
                            <?php while($row = $citas->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_cita']; ?></td>
                                <td>
                                    <strong><?php echo $row['cliente']; ?></strong>
                                    <br><small><?php echo $row['cliente_apellido']; ?></small>
                                </td>
                                <td><?php echo $row['servicio']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['Fecha'])); ?></td>
                                <td><?php echo substr($row['Hora'], 0, 5); ?></td>
                                <td><?php echo $row['Descripcion'] ?: '—'; ?></td>
                                <td>
                                    <span class="estado-<?php echo $row['Status']; ?>">
                                        <?php echo $row['Status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-form">
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?php echo $row['Id_cita']; ?>">
                                            <select name="status">
                                                <option value="Programada" <?php echo $row['Status'] == 'Programada' ? 'selected' : ''; ?>>Programada</option>
                                                <option value="Completada" <?php echo $row['Status'] == 'Completada' ? 'selected' : ''; ?>>Completada</option>
                                                <option value="Cancelada" <?php echo $row['Status'] == 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                            </select>
                                            <button type="submit" name="actualizar" class="btn btn-primary btn-sm">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('¿Eliminar esta cita?')">
                                            <input type="hidden" name="id" value="<?php echo $row['Id_cita']; ?>">
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
                                    <i class="fas fa-calendar-times" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                    No hay citas registradas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== MODAL NUEVA CITA ===== -->
    <div class="modal-overlay" id="modalCita">
        <div class="modal">
            <button class="close-modal" onclick="cerrarModalCita()">&times;</button>
            <h2><i class="fas fa-calendar-plus"></i> Nueva Cita</h2>
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
                    <label>Fecha <span class="required">*</span></label>
                    <input type="date" name="fecha" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Hora <span class="required">*</span></label>
                    <input type="time" name="hora" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Descripción del problema..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModalCita()">Cancelar</button>
                    <button type="submit" name="crear_cita" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Cita
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalCita() {
            document.getElementById('modalCita').classList.add('active');
        }
        function cerrarModalCita() {
            document.getElementById('modalCita').classList.remove('active');
        }
        document.getElementById('modalCita').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalCita();
        });
    </script>
</body>
</html>