<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - Ferretería Michapa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: white;
            color: #333;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .container {
            max-width: 600px;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
            color: #e74c3c;
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .message {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
            color: #555;
        }
        
        .loading {
            display: flex;
            justify-content: center;
            margin: 30px 0;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f0f0f0;
            border-radius: 50%;
            border-top-color: #3498db;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .error-code {
            font-size: 80px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .redirect-message {
            margin-top: 20px;
            font-size: 16px;
            color: #7f8c8d;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 20px;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #95a5a6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🔨</div>
        <h1>Ferretería Michapa</h1>
        
        <div class="error-code">401</div>
        
        <div class="message">
            <p><strong>Acceso no autorizado</strong></p>
            <p>Debe iniciar sesión para acceder al sistema de Ferretería Michapa.</p>
            <p>Será redirigido automáticamente a la página de inicio de sesión.</p>
        </div>
        
        <div class="loading">
            <div class="spinner"></div>
        </div>
        
        <div class="redirect-message">
            Redirigiendo en <span id="countdown">30</span> segundos...
        </div>
        
        <a href="../login/login.php" class="btn">Ir al Inicio de Sesión</a>
        
    </div>

    <script>
       
        let countdown = 30;
        const countdownElement = document.getElementById('countdown');
        
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '../login/login.php';
            }
        }, 1000);

        
    </script>
</body>
</html>