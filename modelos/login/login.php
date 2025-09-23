<?php
session_start();
// Evitar que se pueda acceder con el botón atrás después de logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once '../../conexion/conexion.php';
include_once '../login/inicio_sesion.php';

// Esta lógica se queda aquí porque afecta directamente a la vista
$conexion = new Conexion();
$db = $conexion->getConnection();
$login = new Inicio_sesion($db);

// Determinar si se debe mostrar el enlace para crear el primer usuario
$mostrarCrearUsuario = !$login->existeAlgunUsuario();

// Si no hay usuarios, la primera acción debe ser crear uno.
if (isset($_GET['primera_vez']) && $_GET['primera_vez'] == '1') {
    $mostrarCrearUsuario = true;
}

// Recuperar el correo si hubo un error para no tener que volver a escribirlo
$correo = isset($_GET['correo']) ? htmlspecialchars($_GET['correo']) : '';
$error_type = $_GET['error'] ?? '';
$attempts_left = isset($_GET['attempts_left']) ? (int) $_GET['attempts_left'] : 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../css/login.css" rel="stylesheet">
</head>

<body>
    <!-- Contenedor para alertas personalizadas -->
    <div class="custom-alert-container" id="customAlertContainer"></div>
    
    <div class="login-container">
        <div class="login-header">
            <h3>INICIO DE SESIÓN</h3>
        </div>
        
        <div class="login-body">
            <div class="welcome-text">
                <h5>Bienvenido</h5>
                <p>Introduzca sus credenciales para poder iniciar sesión</p>
            </div>

            <?php if ($mostrarCrearUsuario): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Si es la primera vez que usa el sistema, deberá presionar en <strong>"Crear nuevo usuario"</strong>
                    para continuar con la configuración del entorno.
                </div>
            <?php endif; ?>

            <form method="post" action="procesar_login.php">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input autocomplete="off" type="email" name="correo" class="form-control" placeholder="Introduce tu correo" required value="<?php echo $correo; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="clave" class="form-control" placeholder="Introduce tu clave" required minlength="8" maxlength="12">
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-login btn-block text-white">Ingresar</button>
                </div>
            </form>
            
            <div class="login-links">
                <a href="recuperar_clave.php">¿Olvido su Contraseña?</a>
                <?php if ($mostrarCrearUsuario): ?>
                    <a href="../usuario/crear_usuario.php?primera_vez=1">Crear nuevo Usuario</a>
                <?php endif; ?>
            </div>
            
            <div class="brand mt-4">
                Ferreteria Michapa
            </div>
        </div>
    </div>

    <script>
        // Evitar retroceso con botón atrás
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.pushState(null, null, location.href);
        };
    </script>
    
    <script>
        const errorType = "<?php echo $error_type; ?>";
        const attemptsLeft = <?php echo $attempts_left; ?>;

        // Función para mostrar alertas personalizadas
        function showCustomAlert(type, title, message, duration = 5000) {
            const container = document.getElementById('customAlertContainer');
            
            // Crear elemento de alerta
            const alertEl = document.createElement('div');
            alertEl.className = `custom-alert custom-alert-${type}`;
            
            // Icono según el tipo
            let icon = 'exclamation-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            
            // Contenido de la alerta
            alertEl.innerHTML = `
                <i class="fas fa-${icon} custom-alert-icon"></i>
                <div class="custom-alert-content">
                    <strong>${title}</strong>
                    <div>${message}</div>
                </div>
                <button class="custom-alert-close" onclick="closeAlert(this.parentElement)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            // Agregar al contenedor
            container.appendChild(alertEl);
            
            // Auto cerrar después de un tiempo
            if (duration > 0) {
                setTimeout(() => closeAlert(alertEl), duration);
            }
        }
        
        // Función para cerrar alertas
        function closeAlert(alertEl) {
            alertEl.classList.add('alert-hidden');
            setTimeout(() => {
                if (alertEl.parentNode) {
                    alertEl.parentNode.removeChild(alertEl);
                }
            }, 500);
        }

        // Mostrar alertas según el tipo de error
        if (errorType === '1') {
            let message = 'Credenciales inválidas o usuario inactivo.';
            if (attemptsLeft > 0) {
                message += ` Le quedan ${attemptsLeft} intentos.`;
            }
            
            showCustomAlert(
                'error', 
                'Error de autenticación', 
                message
            );
        } else if (errorType === 'locked') {
            showCustomAlert(
                'warning', 
                'Cuenta Bloqueada', 
                'Cuenta desactivada por seguridad tras varios intentos fallidos. Use Recuperar contraseña para restablecer acceso.'
            );
        }
    </script>
</body>

</html>