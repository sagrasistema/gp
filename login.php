<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema SAGRAGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* ESTILOS GLOBALES (Modo Oscuro Persistente) */
        :root {
            --bg-color: #121212;
            --surface-color: #1e1e1e;
            --accent-color: #00bcd4; /* Cian de acento */
            --text-primary: #ffffff;
            --text-secondary: #b0bec5;
            --border-color: #333333;
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Ocupa todo el alto de la ventana */
            overflow: hidden;
        }

        /* CONTENEDOR DE LA TARJETA DE LOGIN */
        .login-card {
            background-color: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ENCABEZADO DE LA TARJETA */
        .login-header {
            padding: 30px 20px 10px 20px;
            text-align: center;
        }

        .login-header h2 {
            font-weight: 600;
            font-size: 24px;
            color: var(--text-primary);
            letter-spacing: 1px;
        }

        /* CUERPO DEL FORMULARIO */
        .login-body {
            padding: 20px 30px;
        }

        /* GRUPO DE ENTRADA CON ICONOS */
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        /* Icono prefijo (izquierdo) */
        .input-group-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
            pointer-events: none; /* Asegura que el clic pase al input */
            transition: color 0.3s ease;
        }

        /* Estilo del Input */
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px; /* Padding izquierdo extra para el icono */
            font-size: 16px;
            border-radius: 8px;
            background-color: #2c2c2c; /* Fondo ligeramente más claro que la tarjeta */
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 8px rgba(0, 188, 212, 0.3);
        }

        /* Cambiar color del icono en focus */
        .form-control:focus + .input-group-icon {
            color: var(--accent-color);
        }

        /* OJO MOSTRAR/OCULTAR CONTRASEÑA (Icono sufijo) */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--text-primary);
        }

        /* BOTÓN DE ACCIÓN */
        .btn-login {
            display: inline-block;
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            background-color: var(--accent-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .btn-login:hover {
            background-color: #00acc1; /* Un cian un poco más oscuro al hover */
        }

        .btn-login:active {
            transform: scale(0.98); /* Pequeño efecto de clic */
        }

        /* ENLACES ADICIONALES */
        .login-footer {
            padding: 10px 30px 30px 30px;
            text-align: center;
        }

        .forgot-password-link {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password-link:hover {
            color: #4dd0e1; /* Un cian más claro al hover */
            text-decoration: underline;
        }

        /* RESPONSIVO */
        @media (max-width: 480px) {
            .login-card {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h2>Iniciar Sesión</h2>
        </div>

        <form class="login-body" action="login_process.php" method="POST"> <div class="input-group">
                <input type="text" id="username" name="username" class="form-control" placeholder="Usuario" required autocomplete="username">
                <i class="fas fa-user input-group-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña" required autocomplete="current-password">
                <i class="fas fa-lock input-group-icon"></i>
                <i class="fas fa-eye toggle-password" id="togglePassword"></i> </div>

            <button type="submit" class="btn-login">Ingresar</button>
        </form>

        <div class="login-footer">
            <a href="recuperar_password.php" class="forgot-password-link">¿Olvidaste tu contraseña?</a>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // Alternar el tipo de input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Alternar el icono (ojo vs ojo tachado)
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>

</body>
</html>