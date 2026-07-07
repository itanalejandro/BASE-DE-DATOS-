<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = "";

// ===== CREAR USUARIO =====
if (isset($_POST['crear_usuario'])) {
    // Obtener datos del formulario
    $id_solicitud = $_POST['id_solicitud'] ?? null;
    $nombre = $_POST['nombre'];
    $apellido_pat = $_POST['apellido_pat'];
    $apellido_mat = $_POST['apellido_mat'] ?? null;
    $genero = $_POST['genero'];
    $calle = $_POST['calle'] ?? null;
    $num_exterior = $_POST['num_exterior'] ?? null;
    $num_interior = $_POST['num_interior'] ?? null;
    $colonia = $_POST['colonia'] ?? null;
    $codigo_pos = $_POST['codigo_pos'] ?? null;
    $municipio = $_POST['municipio'] ?? null;
    $estado = $_POST['estado'] ?? null;
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'] ?? null;
    $rfc = $_POST['rfc'] ?? null;
    $curp = $_POST['curp'];
    $nss = $_POST['nss'];
    $estado_civil = $_POST['estado_civil'] ?? null;
    $rol = $_POST['rol'];
    $salario = $_POST['salario'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // ✅ VALIDACIÓN: Si no hay Id_solicitud, buscar la solicitud aprobada por correo
    if (empty($id_solicitud)) {
        // Buscar si existe una solicitud aprobada con este correo
        $buscar_solicitud = $conn->query("SELECT Id_solicitud FROM Solicitudes_Empleado WHERE Correo = '$correo' AND Estado_soli = 'aprobado'");
        if ($buscar_solicitud->num_rows > 0) {
            $solicitud = $buscar_solicitud->fetch_assoc();
            $id_solicitud = $solicitud['Id_solicitud'];
        } else {
            // Si no existe, crear un valor por defecto o mostrar error
            $mensaje = '<div class="alert error">❌ No se encontró una solicitud aprobada para este correo. Primero debe aprobar la solicitud del empleado.</div>';
            $error = true;
        }
    }
    
    if (!isset($error)) {
        // ✅ CORREGIDO: Incluir Id_solicitud en el INSERT
        $sql = "INSERT INTO Usuario (Id_solicitud, Nombre, Apellido_pat, Apellido_mat, Genero, Calle, Num_exterior, Num_interior, Colonia, Codigo_pos, Municipio, Estado, Correo, Telefono, RFC, CURP, NSS, Estado_civil, Rol, Salario, Password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssiisssssssssssd",
            $id_solicitud, $nombre, $apellido_pat, $apellido_mat, $genero,
            $calle, $num_exterior, $num_interior, $colonia, $codigo_pos,
            $municipio, $estado, $correo, $telefono, $rfc,
            $curp, $nss, $estado_civil, $rol, $salario, $password
        );
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert success">✅ Usuario creado exitosamente</div>';
        } else {
            $mensaje = '<div class="alert error">❌ Error al crear usuario: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}

// ===== ELIMINAR USUARIO =====
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    if ($conn->query("DELETE FROM Usuario WHERE Id_usuario = $id")) {
        $mensaje = '<div class="alert success">✅ Usuario eliminado correctamente</div>';
    } else {
        $mensaje = '<div class="alert error">❌ Error al eliminar usuario</div>';
    }
}

// ===== CONSULTAS =====
$usuarios = $conn->query("SELECT u.*, s.Estado_soli 
                         FROM Usuario u 
                         LEFT JOIN Solicitudes_Empleado s ON u.Id_solicitud = s.Id_solicitud 
                         ORDER BY u.Id_usuario DESC");

$total_usuarios = $usuarios->num_rows;

// Obtener solicitudes aprobadas para el select
$solicitudes = $conn->query("SELECT Id_solicitud, Nombre, Apellido_pat, Correo FROM Solicitudes_Empleado WHERE Estado_soli = 'aprobado' AND Id_solicitud NOT IN (SELECT Id_solicitud FROM Usuario) ORDER BY Nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios - Admin</title>
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
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-2px); }
        .btn-sm { padding: 4px 10px; font-size: 0.7rem; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        .rol-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
            font-weight: 600;
        }
        .rol-Admin { background: #dc3545; color: white; }
        .rol-Editor { background: #ffc107; color: #212529; }
        .rol-Consultor { background: #17a2b8; color: white; }
        
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            .form-row { grid-template-columns: 1fr; }
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
                    <li><a href="usuarios.php" class="active"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
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
        
        <div class="logout-wrapper">
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span>
            </a>
        </div>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Gestionar Usuarios</h1>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button class="btn-success" onclick="abrirModalUsuario()">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
                <span class="badge-count"><i class="fas fa-users"></i> Total: <?php echo $total_usuarios; ?></span>
            </div>
        </div>

        <?php echo $mensaje; ?>

        <!-- ===== TABLA DE USUARIOS ===== -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Lista de Usuarios</h3>
            <?php if ($total_usuarios > 0): ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellidos</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Rol</th>
                                <th>Solicitud</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $usuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_usuario']; ?></td>
                                <td><?php echo $row['Nombre']; ?></td>
                                <td><?php echo $row['Apellido_pat'] . ' ' . $row['Apellido_mat']; ?></td>
                                <td><?php echo $row['Correo']; ?></td>
                                <td><?php echo $row['Telefono'] ?: '—'; ?></td>
                                <td>
                                    <span class="rol-badge rol-<?php echo $row['Rol']; ?>">
                                        <?php echo $row['Rol']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['Id_solicitud'] ? '#' . $row['Id_solicitud'] : 'N/A'; ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar este usuario?')">
                                        <input type="hidden" name="id" value="<?php echo $row['Id_usuario']; ?>">
                                        <button type="submit" name="eliminar" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; color:#999; padding:40px;">
                    <i class="fas fa-users" style="font-size:3rem; display:block; margin-bottom:15px;"></i>
                    <p>No hay usuarios registrados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== MODAL NUEVO USUARIO ===== -->
    <div class="modal-overlay" id="modalUsuario">
        <div class="modal">
            <button class="close-modal" onclick="cerrarModalUsuario()">&times;</button>
            <h2><i class="fas fa-user-plus"></i> Nuevo Usuario</h2>
            <form method="POST">
                <!-- ✅ Campo obligatorio: Id_solicitud -->
                <div class="form-group">
                    <label>Solicitud de Empleo <span class="required">*</span></label>
                    <select name="id_solicitud" required>
                        <option value="">Seleccione una solicitud aprobada</option>
                        <?php 
                        if ($solicitudes->num_rows > 0):
                            while($sol = $solicitudes->fetch_assoc()): ?>
                                <option value="<?php echo $sol['Id_solicitud']; ?>">
                                    <?php echo $sol['Nombre'] . ' ' . $sol['Apellido_pat'] . ' - ' . $sol['Correo']; ?>
                                </option>
                            <?php endwhile;
                        else: ?>
                            <option value="">No hay solicitudes aprobadas disponibles</option>
                        <?php endif; ?>
                    </select>
                    <small style="color: #6c757d; font-size: 0.7rem;">
                        <i class="fas fa-info-circle"></i> 
                        Primero debe aprobar la solicitud del empleado en "Solicitudes"
                    </small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombre <span class="required">*</span></label>
                        <input type="text" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label>Apellido Paterno <span class="required">*</span></label>
                        <input type="text" name="apellido_pat" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Apellido Materno</label>
                        <input type="text" name="apellido_mat">
                    </div>
                    <div class="form-group">
                        <label>Género <span class="required">*</span></label>
                        <select name="genero" required>
                            <option value="">Seleccione</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Prefiero no Decirlo">Prefiero no Decirlo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Calle</label>
                        <input type="text" name="calle">
                    </div>
                    <div class="form-group">
                        <label>Número Exterior</label>
                        <input type="number" name="num_exterior">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Número Interior</label>
                        <input type="number" name="num_interior">
                    </div>
                    <div class="form-group">
                        <label>Colonia</label>
                        <input type="text" name="colonia">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Código Postal</label>
                        <input type="number" name="codigo_pos">
                    </div>
                    <div class="form-group">
                        <label>Municipio</label>
                        <input type="text" name="municipio">
                    </div>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <input type="text" name="estado">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Correo Electrónico <span class="required">*</span></label>
                        <input type="email" name="correo" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>RFC</label>
                        <input type="text" name="rfc">
                    </div>
                    <div class="form-group">
                        <label>CURP <span class="required">*</span></label>
                        <input type="text" name="curp" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>NSS <span class="required">*</span></label>
                        <input type="text" name="nss" required>
                    </div>
                    <div class="form-group">
                        <label>Estado Civil</label>
                        <select name="estado_civil">
                            <option value="">Seleccione</option>
                            <option value="Casado">Casado</option>
                            <option value="Soltero">Soltero</option>
                            <option value="Viudo">Viudo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rol <span class="required">*</span></label>
                        <select name="rol" required>
                            <option value="Admin">Admin</option>
                            <option value="Editor">Editor</option>
                            <option value="Consultor" selected>Consultor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Salario <span class="required">*</span></label>
                        <input type="number" name="salario" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Contraseña <span class="required">*</span></label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModalUsuario()">Cancelar</button>
                    <button type="submit" name="crear_usuario" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalUsuario() {
            document.getElementById('modalUsuario').classList.add('active');
        }
        function cerrarModalUsuario() {
            document.getElementById('modalUsuario').classList.remove('active');
        }
        document.getElementById('modalUsuario').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalUsuario();
        });
    </script>
</body>
</html>