<?php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] != 'editor' && $_SESSION['rol'] != 'admin')) {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$mensaje = '';
$tipo_mensaje = '';

// Procesar diagnóstico
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar'])) {
    $id_orden = $_POST['id_orden'];
    $diagnostico = $_POST['diagnostico'];
    $costo_final = $_POST['costo_final'];
    
    // ✅ CORREGIDO: Ordenes_reparacion con mayúscula
    $sql = "UPDATE Ordenes_reparacion SET Diagnostico = ?, Costo_final = ?, Estado = 'En proceso' WHERE Id_orden = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $diagnostico, $costo_final, $id_orden);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Diagnóstico guardado correctamente";
        $tipo_mensaje = "exito";
        echo "<script>setTimeout(function() { window.location='diagnostico.php'; }, 2000);</script>";
    } else {
        $mensaje = "❌ Error al guardar: " . $stmt->error;
        $tipo_mensaje = "error";
    }
}

$ordenes = $conn->query("SELECT o.*, cl.Nombre as cliente, s.Descripcion as servicio 
                         FROM Ordenes_reparacion o 
                         JOIN Clientes cl ON o.Id_cliente = cl.Id_cliente 
                         JOIN Servicio_reparacion s ON o.Id_servicio = s.Id_servicio 
                         WHERE o.Diagnostico IS NULL OR o.Diagnostico = '' 
                         ORDER BY o.Id_orden DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico - Editor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; }
        
        /* Sidebar */
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
        .sidebar .user-info i { font-size: 2.5rem; margin-bottom: 10px; color: white; opacity: 0.8; }
        .sidebar .user-info h3 { font-size: 1rem; margin-bottom: 5px; }
        .sidebar .user-info p { font-size: 0.8rem; opacity: 0.7; }
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
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 30px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .mensaje {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            color: #1e2a3a;
        }
        tr:hover { background: #f5f5f5; }
        .btn-primary {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-primary:hover { transform: translateY(-2px); background: linear-gradient(135deg, #3a8bcf, #00c8e0); }
        textarea, input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 5px;
            transition: border-color 0.3s;
        }
        textarea:focus, input:focus { outline: none; border-color: #4facfe; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal.show { display: flex; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
        }
        .modal-content h3 { margin-bottom: 20px; color: #1e2a3a; }
        .modal-content label { font-weight: 500; color: #666; }
        @media (max-width: 1000px) {
            .sidebar { width: 80px; }
            .sidebar span { display: none; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-mobile-alt"></i> <span>CellRepair</span></h2>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <h3><?php echo $_SESSION['usuario']; ?></h3>
            <p>Editor</p>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="registrar_cliente.php"><i class="fas fa-user-plus"></i> <span>Registrar Cliente</span></a></li>
                <li><a href="registrar_producto.php"><i class="fas fa-box"></i> <span>Registrar Producto</span></a></li>
                <li><a href="agendar_cita.php"><i class="fas fa-calendar-plus"></i> <span>Agendar Cita</span></a></li>
                <li><a href="mis_citas.php"><i class="fas fa-calendar-alt"></i> <span>Mis Citas</span></a></li>
                <li><a href="diagnostico.php" class="active"><i class="fas fa-stethoscope"></i> <span>Diagnóstico</span></a></li>
                <li><a href="registrar_venta.php"><i class="fas fa-shopping-cart"></i> <span>Registrar Venta</span></a></li>
            </ul>
        </nav>
        <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Cerrar sesión</span></a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-stethoscope"></i> Capturar Diagnóstico</h1>
        </div>

        <?php if($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <i class="fas <?php echo $tipo_mensaje == 'exito' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Órdenes pendientes de diagnóstico</h3>
            <div style="overflow-x: auto;">
                <?php if ($ordenes->num_rows == 0): ?>
                    <p style="text-align:center; color:#666; padding: 20px;">
                        <i class="fas fa-check-circle" style="color: #28a745;"></i> No hay órdenes pendientes de diagnóstico
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Servicio</th>
                                <th>Acción</th>
                            </thead>
                        <tbody>
                            <?php while($row = $ordenes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['Id_orden']; ?></td>
                                    <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($row['servicio']); ?></td>
                                    <td>
                                        <button class="btn-primary" onclick="abrirModal(<?php echo $row['Id_orden']; ?>, '<?php echo htmlspecialchars($row['cliente']); ?>', '<?php echo htmlspecialchars($row['servicio']); ?>')">
                                            <i class="fas fa-edit"></i> Agregar Diagnóstico
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para diagnóstico -->
    <div id="modal-diagnostico" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-stethoscope"></i> Diagnóstico de Reparación</h3>
            <form method="POST">
                <input type="hidden" name="id_orden" id="orden_id">
                <div style="margin: 15px 0;">
                    <label>Cliente:</label>
                    <p id="cliente_nombre" style="font-weight: bold; color: #4facfe;"></p>
                </div>
                <div style="margin: 15px 0;">
                    <label>Servicio:</label>
                    <p id="servicio_nombre" style="font-weight: bold;"></p>
                </div>
                <div style="margin: 15px 0;">
                    <label>Diagnóstico *</label>
                    <textarea name="diagnostico" rows="4" placeholder="Describa el diagnóstico del equipo..." required></textarea>
                </div>
                <div style="margin: 15px 0;">
                    <label>Costo final de reparación *</label>
                    <input type="number" step="0.01" name="costo_final" placeholder="0.00" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn-primary" onclick="cerrarModal()" style="background: #6c757d;">Cancelar</button>
                    <button type="submit" name="guardar" class="btn-primary">Guardar Diagnóstico</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal-diagnostico');
        
        function abrirModal(id, cliente, servicio) {
            document.getElementById('orden_id').value = id;
            document.getElementById('cliente_nombre').innerHTML = cliente;
            document.getElementById('servicio_nombre').innerHTML = servicio;
            modal.classList.add('show');
        }
        
        function cerrarModal() {
            modal.classList.remove('show');
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) cerrarModal();
        });
    </script>
</body>
</html>