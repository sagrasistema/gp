<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SAGRAGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* VARIABLES DE DISEÑO (Estética de tu Sistema) */
        :root {
            --bg-dark: #0f0f11;
            --bg-form: #16161a;
            --accent-cian: #00bcd4;
            --accent-glow: rgba(0, 188, 212, 0.15);
            --text-main: #ffffff;
            --text-muted: #8e9297;
            --border-color: #2a2b2f;
            --font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-dark);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* LAYOUT DIVIDIDO (50/50) */
        .split-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* COLUMNA IZQUIERDA: FORMULARIO */
        .login-column {
            width: 50%;
            background-color: var(--bg-form);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            z-index: 2;
            border-right: 1px solid var(--border-color);
        }

        .login-wrapper {
            width: 100%;
            max-width: 380px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* TEXTOS DE BIENVENIDA */
        .brand-logo {
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo span {
            color: var(--accent-cian);
        }

        .welcome-text {
            margin-bottom: 35px;
        }

        .welcome-text h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .welcome-text p {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* ENTRADAS Y FORMULARIOS */
        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 42px;
            font-size: 15px;
            background-color: var(--bg-dark);
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-main);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-cian);
            box-shadow: 0 0 0 4px var(--accent-glow);
        }

        .form-control:focus ~ .input-icon {
            color: var(--accent-cian);
        }

        /* MOSTRAR / OCULTAR CONTRASEÑA */
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--text-main);
        }

        /* BOTÓN DE ENTRADA */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--accent-cian);
            color: #121212;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #00acc1;
            box-shadow: 0 4px 15px rgba(0, 188, 212, 0.4);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        /* RECUPERACIÓN */
        .forgot-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: var(--accent-cian);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .forgot-link:hover {
            color: #4dd0e1;
            text-decoration: underline;
        }


        /* COLUMNA DERECHA: IMAGEN / MOSAICO CORPORATIVO */
        .hero-column {
            width: 50%;
            background-color: var(--bg-dark);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* CAPA DE MOSAICO SVG INTEGRADA */
        .mosaico-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.25; /* Sutil y corporativo */
            z-index: 1;
        }

        /* Brillo de acento circular detrás del mosaico */
        .glow-overlay {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,188,212,0.15) 0%, rgba(0,0,0,0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            pointer-events: none;
        }

        /* CONTENIDO DE MARCA DERECHO */
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 40px;
            max-width: 480px;
        }

        .hero-content h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        .hero-content p {
            color: var(--text-muted);
            font-size: 16px;
            line-height: 1.6;
        }

        /* RESPONSIVO */
        @media (max-width: 900px) {
            .hero-column {
                display: none; /* Esconde el panel corporativo en dispositivos móviles */
            }
            .login-column {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="split-container">
        
        <div class="login-column">
            <div class="login-wrapper">
                
                <div class="brand-logo">
                    <i class="fas fa-shield-alt"></i> SAGRA<span>GP</span>
                </div>

                <div class="welcome-text">
                    <h1>Bienvenido de nuevo</h1>
                    <p>Ingresa tus datos para acceder al portal.</p>
                </div>

                <form action="login_process.php" method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">Usuario o Email</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-control" required placeholder="ejemplo@sagra.com">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
                            <i class="fas fa-lock input-icon"></i>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Iniciar Sesión</button>
                </form>

                <a href="recuperar.php" class="forgot-link">¿Olvidaste tu contraseña?</a>
            </div>
        </div>

        <div class="hero-column">
            
            <div class="glow-overlay"></div>

            <svg class="mosaico-background" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255, 255, 255, 0.07)" stroke-width="1"/>
                        <circle cx="40" cy="40" r="1.5" fill="var(--accent-cian)" opacity="0.3"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
                
                <path d="M150 100 L300 250 L200 400 Z" fill="none" stroke="var(--accent-cian)" stroke-width="1.5" stroke-dasharray="5 5" opacity="0.4" />
                <path d="M450 300 L600 150 L500 450 Z" fill="none" stroke="var(--accent-cian)" stroke-width="1" opacity="0.2" />
            </svg>

            <div class="hero-content">
                <h2>Tecnología y Auditoría</h2>
                <p>Plataforma integral para la evaluación continua de riesgos, cumplimiento normativo y gestión de aceptación de clientes.</p>
            </div>

        </div>

    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            // Alternar visibilidad de contraseña
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Cambiar icono
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>