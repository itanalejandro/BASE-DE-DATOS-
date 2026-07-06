<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "venrep_celu";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['username'];
    $correo = $_POST['email'];
    $confirmar_correo = $_POST['confirmar_email'];
    $telefono = $_POST['telefono'];
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    
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
        $check = $conn->prepare("SELECT Id_cliente FROM Clientes WHERE Correo = ?");
        $check->bind_param("s", $correo);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = "❌ Este correo ya está registrado";
        } else {
            $sql = "INSERT INTO Clientes (Nombre, Correo, Telefono, Password) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nombre, $correo, $telefono, $password);
            
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
            max-width: 480px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #003366;
            margin-bottom: 30px;
            text-align: center;
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
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        
        .input-group {
            margin-bottom: 15px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #003366;
            font-size: 0.9rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #b3e0ff;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            outline: none;
        }
        
        .input-group input:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.2);
        }
        
        .input-group input.match {
            border-color: #28a745;
        }
        
        .input-group input.no-match {
            border-color: #dc3545;
        }
        
        .input-hint {
            font-size: 0.75rem;
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
        
        @media (max-width: 800px) {
            .container {
                flex-direction: column;
                margin: 20px;
            }
            .image-column {
                display: none;
            }
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-column">
            <div class="centered-content">
                <h1 class="page-title">CellRepair</h1>
                
                <div class="form-container">
                    <h2 class="form-title">Registro de Cliente</h2>
                    
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="registroForm">
                        <div class="input-group">
                            <label>Nombre completo</label>
                            <input type="text" name="username" placeholder="Tu nombre completo" required>
                        </div>
                        
                        <div class="input-group">
                            <label>Correo electrónico</label>
                            <input type="email" name="email" id="email" placeholder="tu@email.com" required>
                        </div>
                        
                        <div class="input-group">
                            <label>Confirmar correo electrónico</label>
                            <input type="email" name="confirmar_email" id="confirmar_email" placeholder="Confirma tu correo" required>
                        </div>
                        
                        <div class="input-group">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" placeholder="Ej: 8123456789">
                        </div>
                        
                        <div class="input-group">
                            <label>Contraseña</label>
                            <input type="password" name="password" id="password" placeholder="••••••••" required>
                        </div>
                        
                        <div class="input-group">
                            <label>Confirmar contraseña</label>
                            <input type="password" name="confirmar_password" id="confirmar_password" placeholder="Confirma tu contraseña" required>
                        </div>
                        
                        <button type="submit" class="btn-primary">Registrarse</button>
                        
                        <p class="switch-auth">
                            ¿Ya tienes cuenta? <a href="index.html">Iniciar sesión</a>
                        </p>
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