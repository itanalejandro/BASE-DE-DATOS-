<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "VenRep_celu"; // ¡OJO! Con V mayúscula

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir todos los datos del formulario
    $nombre = $_POST['nombre'];
    $apellido_pat = $_POST['apellido_pat'];
    $apellido_mat = $_POST['apellido_mat'];
    $genero = $_POST['genero'];
    $calle = $_POST['calle'];
    $num_exterior = $_POST['num_exterior'];
    $num_interior = $_POST['num_interior'];
    $colonia = $_POST['colonia'];
    $codigo_pos = $_POST['codigo_pos'];
    $municipio = $_POST['municipio'];
    $estado = $_POST['estado'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['email'];
    $confirmar_correo = $_POST['confirmar_email'];
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    $nota_domicilio = $_POST['nota_domicilio'];
    
    // VALIDAR CORREOS
    if ($correo !== $confirmar_correo) {
        $error = "❌ Los correos electrónicos no coinciden";
    }
    // VALIDAR CONTRASEÑAS
    elseif ($password !== $confirmar_password) {
        $error = "❌ Las contraseñas no coinciden";
    }
    // VALIDAR CORREO DUPLICADO
    else {
        $check = $conn->prepare("SELECT Id_cliente FROM Clientes WHERE Correo_cli = ?");
        $check->bind_param("s", $correo);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "❌ Este correo ya está registrado";
        } else {
            $sql = "INSERT INTO Clientes (
                Nombre, Apellido_pat, Apellido_mat, Genero, 
                Calle, Num_exterior, Num_interior, Colonia, 
                Codigo_pos, Municipio, Estado, Telefono, 
                Correo_cli, Password, Nota_domicilio
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?
            )";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssiissssssss", 
                $nombre, 
                $apellido_pat, 
                $apellido_mat, 
                $genero, 
                $calle, 
                $num_exterior, 
                $num_interior, 
                $colonia, 
                $codigo_pos, 
                $municipio, 
                $estado, 
                $telefono, 
                $correo, 
                $password,
                $nota_domicilio
            );
            
            if ($stmt->execute()) {
                echo "<script>alert('✅ Registro exitoso'); window.location='index.html';</script>";
                exit();
            } else {
                $error = "❌ Error al registrar: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CellRepair - Registro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            background: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-radius: 30px;
            overflow: hidden;
        }
        
        .form-column {
            flex: 1;
            background: #b3e0ff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        
        .image-column {
            flex: 1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        
        .phone-image {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 30px;
        }
        
        .centered-content {
            width: 100%;
            max-width: 520px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #003366;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .page-title i {
            margin-right: 10px;
            color: #4facfe;
        }
        
        .form-container {
            background: white;
            padding: 30px 35px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0, 51, 102, 0.2);
        }
        
        .form-title {
            font-size: 1.6rem;
            font-weight: 600;
            color: #003366;
            margin-bottom: 25px;
            text-align: center;
        }
        
        /* ========== ESTILOS DEL BANNER DE CONTRATACIÓN ========== */
        .hiring-banner {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 12px 18px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(238, 90, 36, 0.3);
            animation: pulse-banner 2s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .hiring-banner::before {
            content: "🔥";
            position: absolute;
            font-size: 70px;
            right: -10px;
            top: -15px;
            opacity: 0.1;
            transform: rotate(15deg);
        }

        .hiring-banner .banner-text {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            z-index: 1;
        }

        .hiring-banner .banner-icon {
            font-size: 1.6rem;
            animation: bounce-icon 1.5s ease-in-out infinite;
        }

        .hiring-banner .banner-content h3 {
            font-size: 0.95rem;
            margin: 0;
            font-weight: 700;
        }

        .hiring-banner .banner-content p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.8rem;
        }

        .hiring-banner .btn-apply {
            background: white;
            color: #ee5a24;
            padding: 8px 22px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            z-index: 1;
            position: relative;
            white-space: nowrap;
        }

        .hiring-banner .btn-apply:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
        }

        .hiring-badge {
            background: #ffd93d;
            color: #333;
            padding: 2px 10px;
            border-radius: 50px;
            font-size: 0.55rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
        }

        @keyframes pulse-banner {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.01); }
        }

        @keyframes bounce-icon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        /* ========== FIN ESTILOS BANNER ========== */
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .input-group {
            margin-bottom: 12px;
        }
        
        .input-group.full-width {
            grid-column: span 2;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
            color: #003366;
            font-size: 0.8rem;
        }
        
        .input-group label i {
            margin-right: 6px;
            color: #4facfe;
        }
        
        .input-group label .required {
            color: #dc3545;
            font-weight: 700;
        }
        
        .input-group input, 
        .input-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #b3e0ff;
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s;
            outline: none;
            background: #fafafa;
        }
        
        .input-group input:focus, 
        .input-group select:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.2);
            background: white;
        }
        
        .input-group input.match {
            border-color: #28a745;
        }
        
        .input-group input.no-match {
            border-color: #dc3545;
        }
        
        .input-hint {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 3px;
            display: block;
        }
        
        .input-hint.success {
            color: #28a745;
        }
        
        .input-hint.error {
            color: #dc3545;
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #003366;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            background: #002244;
            transform: translateY(-2px);
        }
        
        .btn-primary i {
            margin-right: 8px;
        }
        
        .switch-auth {
            text-align: center;
            margin-top: 20px;
            color: #003366;
        }
        
        .switch-auth a {
            color: #003366;
            text-decoration: none;
            font-weight: 700;
        }
        
        .switch-auth a:hover {
            text-decoration: underline;
        }
        
        /* Botón de regreso al login */
        .back-to-login {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #f0f2f5;
            color: #6c757d;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.3s;
        }
        .back-to-login:hover {
            color: #003366;
        }
        .back-to-login i { margin-right: 8px; }
        
        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #003366;
            margin: 15px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #b3e0ff;
            grid-column: span 2;
        }
        
        @media (max-width: 800px) {
            .container {
                flex-direction: column;
                margin: 10px;
                border-radius: 20px;
            }
            .image-column {
                display: none;
            }
            .form-container {
                padding: 20px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .input-group.full-width {
                grid-column: span 1;
            }
            .section-title {
                grid-column: span 1;
            }
            .hiring-banner {
                flex-direction: column;
                text-align: center;
            }
            .hiring-banner .btn-apply {
                width: 100%;
                justify-content: center;
            }
            .hiring-banner .banner-text {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-column">
            <div class="centered-content">
                <h1 class="page-title"><i class="fas fa-mobile-alt"></i>CellRepair</h1>
                
                <div class="form-container">
                    <!-- ====== BANNER DE CONTRATACIÓN ====== -->
                    <div class="hiring-banner">
                        <div class="banner-text">
                            <span class="banner-icon">💼</span>
                            <div class="banner-content">
                                <h3>¡Estamos Contratando! 🚀</h3>
                                <p>Únete al equipo de CellRepair</p>
                                <span class="hiring-badge">📢 Oportunidad Laboral</span>
                            </div>
                        </div>
                        <a href="iniciosoli.php" class="btn-apply">
                            <i class="fas fa-paper-plane"></i> Postularme
                        </a>
                    </div>
                    <!-- ====== FIN BANNER ====== -->
                    
                    <h2 class="form-title">Registro de Cliente</h2>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="registroForm">
                        <div class="form-grid">
                            <!-- ===== DATOS PERSONALES ===== -->
                            <div class="section-title"><i class="fas fa-user"></i> Datos Personales</div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-user"></i> Nombre <span class="required">*</span></label>
                                <input type="text" name="nombre" placeholder="Tu nombre" required>
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-user"></i> Apellido Paterno <span class="required">*</span></label>
                                <input type="text" name="apellido_pat" placeholder="Apellido paterno" required>
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-user"></i> Apellido Materno</label>
                                <input type="text" name="apellido_mat" placeholder="Apellido materno">
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-venus-mars"></i> Género <span class="required">*</span></label>
                                <select name="genero" required>
                                    <option value="">Selecciona</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Prefiero no Decirlo">Prefiero no Decirlo</option>
                                </select>
                            </div>
                            
                            <!-- ===== DATOS DE CONTACTO ===== -->
                            <div class="section-title"><i class="fas fa-address-card"></i> Datos de Contacto</div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-envelope"></i> Correo Electrónico <span class="required">*</span></label>
                                <input type="email" name="email" id="email" placeholder="tu@email.com" required>
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-envelope"></i> Confirmar Correo <span class="required">*</span></label>
                                <input type="email" name="confirmar_email" id="confirmar_email" placeholder="Confirma tu correo" required>
                                <span class="input-hint" id="email_hint">Confirma tu correo</span>
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-phone"></i> Teléfono <span class="required">*</span></label>
                                <input type="tel" name="telefono" placeholder="81 2345 6789" required>
                            </div>
                            
                            <!-- ===== DOMICILIO ===== -->
                            <div class="section-title"><i class="fas fa-home"></i> Domicilio</div>
                            
                            <div class="input-group full-width">
                                <label><i class="fas fa-road"></i> Calle</label>
                                <input type="text" name="calle" placeholder="Calle y número">
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-hashtag"></i> Núm. Exterior</label>
                                <input type="number" name="num_exterior" placeholder="Número exterior">
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-hashtag"></i> Núm. Interior</label>
                                <input type="number" name="num_interior" placeholder="Número interior">
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-map-pin"></i> Colonia</label>
                                <input type="text" name="colonia" placeholder="Colonia">
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-mail-bulk"></i> Código Postal</label>
                                <input type="number" name="codigo_pos" placeholder="Código postal">
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-city"></i> Municipio</label>
                                <input type="text" name="municipio" placeholder="Municipio">
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-flag"></i> Estado</label>
                                <input type="text" name="estado" placeholder="Estado">
                            </div>
                            
                            <div class="input-group full-width">
                                <label><i class="fas fa-info-circle"></i> Nota de domicilio</label>
                                <input type="text" name="nota_domicilio" placeholder="Referencias, entre calles, etc.">
                            </div>
                            
                            <!-- ===== SEGURIDAD ===== -->
                            <div class="section-title"><i class="fas fa-lock"></i> Seguridad</div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-lock"></i> Contraseña <span class="required">*</span></label>
                                <input type="password" name="password" id="password" placeholder="••••••••" required>
                            </div>
                            
                            <div class="input-group">
                                <label><i class="fas fa-lock"></i> Confirmar Contraseña <span class="required">*</span></label>
                                <input type="password" name="confirmar_password" id="confirmar_password" placeholder="Confirma tu contraseña" required>
                                <span class="input-hint" id="password_hint">Confirma tu contraseña</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </button>
                        
                        <p class="switch-auth">
                            ¿Ya tienes cuenta? <a href="index.html">Iniciar sesión</a>
                        </p>

                        <!-- Botón de regreso al login -->
                        <a href="index.html" class="back-to-login">
                            <i class="fas fa-arrow-left"></i> Volver a la página de inicio de sesión
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="image-column">
            <img src="cellrepair.png" alt="Celular" class="phone-image" onerror="this.src='https://via.placeholder.com/400x600?text=CellRepair'">
        </div>
    </div>

    <script>
        // ========== VALIDACIÓN EN TIEMPO REAL ==========
        
        const email = document.getElementById('email');
        const confirmarEmail = document.getElementById('confirmar_email');
        const emailHint = document.getElementById('email_hint');
        
        const password = document.getElementById('password');
        const confirmarPassword = document.getElementById('confirmar_password');
        const passwordHint = document.getElementById('password_hint');
        
        // Validar correos
        function validarCorreos() {
            const emailVal = email.value;
            const confirmVal = confirmarEmail.value;
            
            if (confirmVal === '') {
                emailHint.textContent = 'Confirma tu correo';
                emailHint.className = 'input-hint';
                confirmarEmail.className = '';
                return;
            }
            
            if (emailVal === confirmVal) {
                emailHint.textContent = '✅ Los correos coinciden';
                emailHint.className = 'input-hint success';
                confirmarEmail.className = 'match';
            } else {
                emailHint.textContent = '❌ Los correos no coinciden';
                emailHint.className = 'input-hint error';
                confirmarEmail.className = 'no-match';
            }
        }
        
        // Validar contraseñas
        function validarPasswords() {
            const passVal = password.value;
            const confirmVal = confirmarPassword.value;
            
            if (confirmVal === '') {
                passwordHint.textContent = 'Confirma tu contraseña';
                passwordHint.className = 'input-hint';
                confirmarPassword.className = '';
                return;
            }
            
            if (passVal === confirmVal) {
                passwordHint.textContent = '✅ Las contraseñas coinciden';
                passwordHint.className = 'input-hint success';
                confirmarPassword.className = 'match';
            } else {
                passwordHint.textContent = '❌ Las contraseñas no coinciden';
                passwordHint.className = 'input-hint error';
                confirmarPassword.className = 'no-match';
            }
        }
        
        email.addEventListener('input', validarCorreos);
        confirmarEmail.addEventListener('input', validarCorreos);
        password.addEventListener('input', validarPasswords);
        confirmarPassword.addEventListener('input', validarPasswords);
        
        // ========== VALIDACIÓN ANTES DE ENVIAR ==========
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            const emailVal = email.value;
            const confirmEmailVal = confirmarEmail.value;
            const passVal = password.value;
            const confirmPassVal = confirmarPassword.value;
            
            if (emailVal !== confirmEmailVal) {
                e.preventDefault();
                alert('❌ Los correos electrónicos no coinciden');
                confirmarEmail.focus();
                return;
            }
            
            if (passVal !== confirmPassVal) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden');
                confirmarPassword.focus();
                return;
            }
        });
    </script>
</body>
</html>