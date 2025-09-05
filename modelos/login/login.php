<?php
session_start();
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
$attempts_left = isset($_GET['attempts_left']) ? (int)$_GET['attempts_left'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="card shadow p-4" style="width: 25rem;">
            <h3 class="text-center mb-4">Iniciar Sesión</h3>

            <?php if ($mostrarCrearUsuario): ?>
                <div class="alert alert-info small" role="alert">
                    <p class="mb-0">
                        Si es la primera vez que usa el sistema, deberá presionar en <strong>"Crear nuevo usuario"</strong> para continuar con la configuración del entorno.
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="procesar_login.php" class="mt-2">
                <div class="mb-3">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" required 
                           value="<?php echo $correo; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Clave</label>
                    <input type="password" name="clave" class="form-control" required minlength="8" maxlength="12">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Ingresar</button>
                </div>
            </form>
            <div class="mt-3 text-center">
                <a href="recuperar_clave.php" class="btn btn-link">Recuperar usuario</a>
<?php if ($mostrarCrearUsuario): ?>
                    <a href="../usuario/crear_usuario.php?primera_vez=1" class="btn btn-link">Crear nuevo usuario</a>
<?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const errorType = "<?php echo $error_type; ?>";
        const attemptsLeft = <?php echo $attempts_left; ?>;

        if (errorType === '1') {
            let message = 'Credenciales inválidas o usuario inactivo.';
            if (attemptsLeft > 0) {
                message += ` Le quedan ${attemptsLeft} intentos.`;
            }
            Swal.fire('Error', message, 'error');
        } else if (errorType === 'locked') {
            Swal.fire({
                icon: 'error',
                title: 'Cuenta Bloqueada',
                text: 'Ha superado el número máximo de intentos de inicio de sesión. Su cuenta ha sido desactivada por seguridad.',
            });
        }
    </script>
</body>
</html>
