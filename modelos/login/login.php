<?php
session_start();
include_once '../../conexion/conexion.php';
include_once '../inicio_sesion/inicio_sesion.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$login = new Inicio_sesion($db);

$message = '';
$correo = $clave = "";
$id_Usuario = 0;

// Manejo del POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = strtolower(trim($_POST['correo']));
    $clave = trim($_POST['clave']);
    $id_Usuario = intval($_POST['id_Usuario']); // viene del formulario

    // Asignar valores al objeto
    $login->correo = $correo;
    $login->clave = $clave;
    $login->id_Usuario = $id_Usuario;

    // Verificar credenciales
    if ($login->verificarCredencialesPorId()) {
        // Guardamos la sesión
        $_SESSION['id_Usuario'] = $login->id_Usuario;
        $_SESSION['correo'] = $login->correo;
        $_SESSION['rol'] = $login->rol;

        header("Location: ../empleado/crear_empleado.php"); // redirigir
        exit();
    } else {
        $message = 'error';
    }
}
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
            <form method="post" action="login.php">
                <div class="mb-3">
                    <label class="form-label">ID Usuario</label>
                    <input type="number" name="id_Usuario" class="form-control" required value="<?php echo htmlspecialchars($id_Usuario); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" required value="<?php echo htmlspecialchars($correo); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Clave</label>
                    <input type="password" name="clave" class="form-control" required minlength="8" maxlength="12">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Ingresar</button>
                </div>
            </form>
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
