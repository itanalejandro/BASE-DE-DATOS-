<?php
session_start();
$mensaje = "";

// Conectar a la base de datos
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar'])) {
    // Recibir datos del formulario
    $nombre = $_POST['nombre'];
    $apellidopa = $_POST['apellidopa'];
    $apellidoma = $_POST['apellidoma'];
    $genero = $_POST['genero'];
    $calle = $_POST['calle'];
    $numex = $_POST['numex'];
    $numin = $_POST['numin'];
    $colonia = $_POST['colonia'];
    $codigo_pos = $_POST['codigo_pos'];
    $municipio = $_POST['municipio'];
    $estado = $_POST['estado'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $rfc = $_POST['rfc'];
    $curp = $_POST['curp'];
    $nss = $_POST['nss'];
    $estado_civil = $_POST['estado_civil'];
    
    // Validar correo único
    $check = $conn->query("SELECT Id_solicitud FROM Solicitudes_Empleado WHERE Correo = '$correo'");
    if ($check->num_rows > 0) {
        $mensaje = '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align:center;">
            ❌ Este correo ya está registrado en una solicitud.
        </div>';
    } else {
        // Subir documentos
        $certificado = $_FILES['certificado'];
        $comprobante = $_FILES['comprobante'];
        
        $certificado_nombre = "";
        $comprobante_nombre = "";
        
        // Crear carpeta si no existe
        if (!is_dir("uploads/solicitudes/")) {
            mkdir("uploads/solicitudes/", 0777, true);
        }
        
        // Subir certificado
        if ($certificado['error'] == 0) {
            $certificado_nombre = time() . "_cert_" . basename($certificado['name']);
            $ruta_cert = "uploads/solicitudes/" . $certificado_nombre;
            move_uploaded_file($certificado['tmp_name'], $ruta_cert);
        }
        
        // Subir comprobante
        if ($comprobante['error'] == 0) {
            $comprobante_nombre = time() . "_comp_" . basename($comprobante['name']);
            $ruta_comp = "uploads/solicitudes/" . $comprobante_nombre;
            move_uploaded_file($comprobante['tmp_name'], $ruta_comp);
        }
        
        // Insertar solicitud
        $sql = "INSERT INTO Solicitudes_Empleado (
            Nombre, Apellido_pat, Apellido_mat, Genero, Calle, 
            Num_exterior, Num_interior, Colonia, Codigo_pos, 
            Municipio, Estado, Correo, Telefono, 
            RFC, CURP, NSS, Estado_civil, 
            Certificado_estudio, Comprobante_domicilio
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?
        )";
        
        $stmt = $conn->prepare($sql);
        
        // TODOS 's' (texto) - 19 VECES
        $stmt->bind_param(
            "sssssssssssssssssss", 
            $nombre, $apellidopa, $apellidoma, $genero, $calle, 
            $numex, $numin, $colonia, $codigo_pos, 
            $municipio, $estado, $correo, $telefono, 
            $rfc, $curp, $nss, $estado_civil, 
            $certificado_nombre, $comprobante_nombre
        );
        
        if ($stmt->execute()) {
            $mensaje = '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align:center;">
                ✅ Solicitud enviada exitosamente. Espera la aprobación del administrador.
            </div>';
        } else {
            $mensaje = '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align:center;">
                ❌ Error al enviar la solicitud: ' . $stmt->error . '
            </div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Empleo | CellRepair</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f0f2f5 0%, #e6e9f0 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        
        .container { max-width: 850px; width: 100%; background: white; border-radius: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); padding: 40px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { font-size: 2rem; color: #1e2a3a; }
        .logo h1 i { color: #4facfe; margin-right: 10px; }
        .logo p { color: #6c757d; font-size: 0.9rem; }
        h2 { font-size: 1.3rem; color: #1e2a3a; border-left: 4px solid #4facfe; padding-left: 15px; margin-bottom: 25px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 0.8rem; font-weight: 600; color: #555; }
        .form-group label i { margin-right: 6px; color: #4facfe; }
        .form-group input, .form-group select { padding: 10px 14px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 0.9rem; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus { border-color: #4facfe; outline: none; box-shadow: 0 0 0 3px rgba(79,172,254,0.15); }
        .file-group { display: flex; gap: 20px; flex-wrap: wrap; }
        .file-group .form-group { flex: 1; min-width: 200px; }
        .file-group .form-group input[type="file"] { padding: 10px; border: 2px dashed #ddd; background: #fafafa; cursor: pointer; }
        .file-group .form-group input[type="file"]:hover { border-color: #4facfe; background: #f0f9ff; }
        .btn-submit { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; border: none; padding: 14px 30px; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; flex:1; min-width:200px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(79,172,254,0.4); }
        .btn-submit i { margin-right: 8px; }
        .btn-reset { flex:1; min-width:200px; background: #6c757d; color: white; border: none; padding: 14px 30px; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-reset:hover { background: #5a6268; transform: translateY(-2px); }
        .btn-group { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px; }
        .note { text-align: center; font-size: 0.75rem; color: #999; margin-top: 15px; }
        .requerido { color: #ff6b6b; font-size: 0.75rem; }
        @media (max-width: 700px) {
            .container { padding: 25px; }
            .form-grid { grid-template-columns: 1fr; }
            .file-group { flex-direction: column; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1><i class="fas fa-mobile-alt"></i> CellRepair</h1>
            <p>Solicitud de Empleo</p>
        </div>

        <?php echo $mensaje; ?>

        <form method="POST" enctype="multipart/form-data">
            <h2><i class="fas fa-user"></i> Datos Personales</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombre *</label>
                    <input type="text" name="nombre" placeholder="Tu nombre" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Apellido Paterno *</label>
                    <input type="text" name="apellidopa" placeholder="Apellido paterno" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Apellido Materno</label>
                    <input type="text" name="apellidoma" placeholder="Apellido materno">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-venus-mars"></i> Género *</label>
                    <select name="genero" required>
                        <option value="">Selecciona</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Prefiero no Decirlo">Prefiero no Decirlo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Correo Electrónico *</label>
                    <input type="email" name="correo" placeholder="correo@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="text" name="telefono" placeholder="8123456789">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> RFC</label>
                    <input type="text" name="rfc" placeholder="RFC (13 caracteres)">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> CURP *</label>
                    <input type="text" name="curp" placeholder="CURP (18 caracteres)" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> NSS *</label>
                    <input type="text" name="nss" placeholder="NSS (11 dígitos)" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-heart"></i> Estado Civil</label>
                    <select name="estado_civil">
                        <option value="">Selecciona</option>
                        <option value="Casado">Casado</option>
                        <option value="Soltero">Soltero</option>
                        <option value="Viudo">Viudo</option>
                    </select>
                </div>
            </div>

            <h2 style="margin-top:25px;"><i class="fas fa-home"></i> Domicilio</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-road"></i> Calle</label>
                    <input type="text" name="calle" placeholder="Calle">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Núm. Exterior</label>
                    <input type="number" name="numex" placeholder="Número exterior">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Núm. Interior</label>
                    <input type="number" name="numin" placeholder="Número interior">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-pin"></i> Colonia</label>
                    <input type="text" name="colonia" placeholder="Colonia">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-mail-bulk"></i> Código Postal</label>
                    <input type="number" name="codigo_pos" placeholder="Código postal">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-city"></i> Municipio</label>
                    <input type="text" name="municipio" placeholder="Municipio">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-flag"></i> Estado</label>
                    <input type="text" name="estado" placeholder="Estado">
                </div>
            </div>

            <h2 style="margin-top:25px;"><i class="fas fa-file-upload"></i> Documentos</h2>
            <div class="file-group">
                <div class="form-group">
                    <label><i class="fas fa-graduation-cap"></i> Certificado de Estudios</label>
                    <input type="file" name="certificado" accept=".pdf,.jpg,.png">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-home"></i> Comprobante de Domicilio</label>
                    <input type="file" name="comprobante" accept=".pdf,.jpg,.png">
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" name="enviar" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Enviar Solicitud
                </button>
                <button type="reset" class="btn-reset">
                    <i class="fas fa-eraser"></i> Limpiar Campos
                </button>
            </div>
        </form>
        <div class="note">
            Los campos marcados con <span class="requerido">*</span> son obligatorios.
        </div>
    </div>
</body>
</html>