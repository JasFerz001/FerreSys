<?php
session_start();
include_once '../../conexion/conexion.php';
include_once '../login/inicio_sesion.php';
include_once '../usuario/usuario.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$login = new Inicio_sesion($db);
$usuario = new Usuario($db);

$message = '';
$correo = $clave = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = strtolower(trim($_POST['correo']));
    $clave = trim($_POST['clave']);

    $login->correo = $correo;
    $login->clave = $clave;

    if (!$login->existeAlgunUsuario()) {
        header("Location: ../usuario/crear_usuario.php?primera_vez=1");
        exit();
    }


    if ($login->verificarCredenciales()) {

        $_SESSION['id_Usuario'] = $login->id_Usuario;
        $_SESSION['correo'] = $login->correo;
        $_SESSION['rol'] = $login->rol;

        header("Location: ../empleado/crear_empleado.php"); 
        exit();
    } else {
        $message = 'error';
    }
}

$mostrarCrearUsuario = !$login->existeAlgunUsuario();
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

            <form method="post" action="login.php" class="mt-2">
                <div class="mb-3">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" required 
                           value="<?php echo htmlspecialchars($correo); ?>">
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
        const message = "<?php echo $message; ?>";
        if (message === 'error') {
            Swal.fire('Error', 'Credenciales inválidas o usuario inactivo', 'error');
        }
    </script>
</body>
</html>
