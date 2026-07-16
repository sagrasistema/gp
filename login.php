<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAGRAGP - Mosaico de Fondo</title>
    <style>
        /* Estilos Globales y Reset */
        :root {
            --primary-color: #007bff; /* Azul para botones y enlaces */
            --bg-color: #f8f9fa;      /* Fondo claro para el panel derecho */
            --text-color: #333;
            --border-radius: 8px;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden; /* Evita scrolls globales */
        }

        /* Contenedor Principal con Flexbox */
        .main-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* --- Panel Izquierdo (25% de ancho) --- */
        .left-panel {
            flex: 0 0 25%; /* Ancho fijo del 25%, no se encoge ni crece */
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
            box-sizing: border-box;
            position: relative; /* Para el overlay opcional */
            
            /* --- FONDO DE MOSAICO (ESTA ES LA PARTE CLAVE) --- */
            /* 1. Imagen de Fondo: Asegúrate de que el nombre coincida con tu archivo */
            background-image: url('tu-imagen-mosaico.png'); 
            
            /* 2. Repetición: Esto crea el efecto de mosaico */
            background-repeat: repeat; 
            
            /* 3. Posición: Empieza desde la esquina superior izquierda */
            background-position: top left;
            
            /* 4. Tamaño: 'auto' mantiene el tamaño original de la imagen para el mosaico.
               Si la imagen es muy grande, puedes usar un valor en px (ej: 100px) 
               para que se repita más veces. */
            background-size: auto; 
        }

        /* Overlay opcional para oscurecer el fondo si el mosaico es muy claro y afecta la legibilidad del texto */
        .left-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.4); /* Capa negra semi-transparente */
            z-index: 1;
        }

        /* Asegurar que el contenido esté por encima del overlay */
        .left-panel-content {
            position: relative;
            z-index: 2;
        }

        .left-panel h1 {
            font-size: 3rem;
            margin: 0 0 1rem;
            font-weight: bold;
        }

        .left-panel p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* --- Panel Derecho (75% de ancho) --- */
        .right-panel {
            flex: 1; /* Ocupa el espacio restante (75%) */
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            box-sizing: border-box;
            overflow-y: auto; /* Permite scroll si el formulario es largo */
        }

        /* Estilos del Formulario de Login */
        .login-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px; /* Ancho máximo para el formulario */
        }

        .login-card h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--text-color);
            font-size: 1.8rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            box-sizing: border-box; /* Importante para que el padding no sume al ancho */
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: var(--border-radius);
            background-color: var(--primary-color);
            color: white;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .login-button:hover {
            background-color: #0056b3;
        }

        .extra-links {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .extra-links a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .extra-links a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

    <div class="main-container">
        <div class="left-panel">
            <div class="left-panel-content">
                <h1>SAGRAGP</h1>
                <p>Sistema de Gestión del Riesgo</p>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <h2>Iniciar Sesión</h2>
                <form>
                    <div class="form-group">
                        <label for="username">Usuario</label>
                        <input type="text" id="username" name="username" placeholder="Tu usuario" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="Tu contraseña" required>
                    </div>
                    <button type="submit" class="login-button">Entrar</button>
                </form>
                <div class="extra-links">
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>