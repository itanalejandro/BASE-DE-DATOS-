<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.html");
    exit();
}
include '../conexion.php';

$citas = $conn->query("SELECT c.*, cl.Nombre as cliente, s.Descripcion as servicio 
                       FROM Citas c 
                       JOIN Clientes cl ON c.Id_cliente = cl.Id_cliente 
                       JOIN Servicio_reparacion s ON c.Id_servicio = s.Id_servicio 
                       ORDER BY c.Fecha DESC, c.Hora DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Todas las Citas - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; padding: 30px; }
        .card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; }
        .estado-programada { background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 20px; font-size: 0.8rem; display: inline-block; }
        .estado-completada { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 20px; font-size: 0.8rem; display: inline-block; }
        .estado-cancelada { background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 20px; font-size: 0.8rem; display: inline-block; }
        .btn-back { background: #4facfe; color: white; padding: 10px 20px; text-decoration: none; border-radius: 10px; display: inline-block; margin-bottom: 20px; transition: all 0.3s; }
        .btn-back:hover { background: #3a8fd6; transform: translateX(-5px); }
        h1 { color: #1e2a3a; margin-bottom: 20px; }
    </style>
</head>
<body>
    <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
    <div class="card">
        <h1><i class="fas fa-calendar-alt"></i> Todas las Citas</h1>
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
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $citas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['Id_cita']; ?></td>
                        <td><?php echo $row['cliente']; ?></td>
                        <td><?php echo $row['servicio']; ?></td>
                        <td><?php echo $row['Fecha']; ?></td>
                        <td><?php echo substr($row['Hora'], 0, 5); ?></td>
                        <td><?php echo $row['Descripcion'] ?: '—'; ?></td>
                        <td>
                            <span class="estado-<?php echo strtolower($row['Estado']); ?>">
                                <?php echo $row['Estado']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>