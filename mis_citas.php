<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 'editor' && $_SESSION['rol'] != 'admin')) {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$citas = $conn->query("SELECT c.Id_cita, c.Fecha, c.Hora, c.Estado, c.Descripcion,
                              cl.Nombre as cliente, s.Descripcion as servicio 
                       FROM Citas c 
                       JOIN Clientes cl ON c.Id_cliente = cl.Id_cliente 
                       JOIN Servicio_reparacion s ON c.Id_servicio = s.Id_servicio 
                       ORDER BY c.Fecha DESC, c.Hora DESC");

$citas_select = $conn->query("SELECT c.Id_cita, cl.Nombre as cliente, s.Descripcion as servicio, c.Fecha 
                              FROM Citas c 
                              JOIN Clientes cl ON c.Id_cliente = cl.Id_cliente 
                              JOIN Servicio_reparacion s ON c.Id_servicio = s.Id_servicio 
                              ORDER BY c.Fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Citas - Editor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(135deg, #1e2a3a, #0f1724);
            color: white;
            padding: 30px 20px;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }
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
}
        .sidebar nav ul { list-style: none; }
        .sidebar nav ul li { margin-bottom: 8px; }
        .sidebar nav ul li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #e0e0e0;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .sidebar nav ul li a:hover { background: rgba(79, 172, 254, 0.2); color: white; }
        .sidebar nav ul li a.active { background: #4facfe; color: white; }
        .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            background: white;
            padding: 20px 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .cards-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-bottom: 20px;
            color: #1e2a3a;
            border-left: 4px solid #4facfe;
            padding-left: 15px;
            font-size: 1.2rem;
        }
        
        .card h3 i {
            margin-right: 10px;
            color: #4facfe;
        }
        
        .update-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .form-group-update {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group-update label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #666;
        }
        
        .form-group-update label i {
            margin-right: 5px;
            color: #4facfe;
        }
        
        select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        select:focus {
            outline: none;
            border-color: #4facfe;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin-top: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background: #f8f9fa;
            color: #1e2a3a;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .estado-programada { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; display: inline-block; font-weight: 500; }
        .estado-completada { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; display: inline-block; font-weight: 500; }
        .estado-cancelada { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; display: inline-block; font-weight: 500; }
        
        .separator {
            height: 2px;
            background: linear-gradient(90deg, transparent, #4facfe, transparent);
            margin: 10px 0;
        }
        
        @media (max-width: 1000px) {
            .sidebar { width: 80px; }
            .sidebar span { display: none; }
            .main-content { margin-left: 80px; }
        }
        
        @media (max-width: 768px) {
            .update-form {
                flex-direction: column;
            }
            .form-group-update {
                width: 100%;
            }
            .btn-update {
                width: 100%;
                justify-content: center;
            }
        }
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
            <h1><i class="fas fa-calendar-alt"></i> Gestión de Citas</h1>
            <p style="color: #666; margin-top: 10px;">Administra las citas y actualiza su estado fácilmente</p>
        </div>
        
        <div class="cards-container">
            <!-- Card 1: Actualizar Estado -->
            <div class="card">
                <h3><i class="fas fa-edit"></i> Actualizar Estado de Cita</h3>
                <form method="POST" action="actualizar_cita.php" class="update-form">
    <div class="form-group-update">
        <label><i class="fas fa-calendar-check"></i> Seleccionar cita</label>
        <select name="id_cita" required>
            <option value="">-- Seleccione una cita --</option>
            <?php while($row = $citas_select->fetch_assoc()): ?>
                <option value="<?php echo $row['Id_cita']; ?>">
                    <?php echo $row['cliente'] . " - " . $row['servicio'] . " - " . $row['Fecha']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="form-group-update">
        <label><i class="fas fa-tag"></i> Nuevo estado</label>
        <select name="nuevo_estado">
            <option value="Programada">📅 Programada</option>
            <option value="Completada">✔️ Completada</option>
            <option value="Cancelada">❌ Cancelada</option>
        </select>
    </div>
    <button type="submit" class="btn-update">
        <i class="fas fa-sync-alt"></i> Actualizar Estado
    </button>
</form>
            </div>

            <div class="separator"></div>

            <!-- Card 2: Lista de Citas -->
            <div class="card">
                <h3><i class="fas fa-list"></i> Lista de Citas</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($citas->num_rows > 0): ?>
                                <?php while($row = $citas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['Id_cita']; ?></td>
                                        <td><?php echo $row['cliente']; ?></td>
                                        <td><?php echo $row['servicio']; ?></td>
                                        <td><?php echo $row['Fecha']; ?></td>
                                        <td><?php echo substr($row['Hora'], 0, 5); ?></td>
                                        <td>
                                            <?php 
                                            $estado = $row['Estado'];
                                            $icono = '';
                                            if($estado == 'Programada') $icono = '📅 ';
                                            elseif($estado == 'Completada') $icono = '✔️ ';
                                            elseif($estado == 'Cancelada') $icono = '❌ ';
                                            ?>
                                            <span class="estado-<?php echo strtolower($estado); ?>">
                                                <?php echo $icono . $estado; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-calendar-times" style="font-size: 2rem; color: #ccc;"></i>
                                        <p style="margin-top: 10px; color: #666;">No hay citas registradas</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>