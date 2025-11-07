<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

include_once '../../conexion/conexion.php';
include_once '../login/inicio_sesion.php';
include_once '../../modelos/bitacora/bitacora.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$login = new Inicio_sesion($db);
$bitacora = new Bitacora($db);

$correo = strtolower(trim($_POST['correo']));
$clave = trim($_POST['clave']);

// Inicializar el contador de intentos
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}
if (!isset($_SESSION['login_attempts'][$correo])) {
    $_SESSION['login_attempts'][$correo] = 0;
}

// Verificar si está bloqueado
if ($_SESSION['login_attempts'][$correo] >= 5) {
    $query_check_status = "SELECT estado FROM empleados WHERE correo = :correo";
    $stmt_check_status = $db->prepare($query_check_status);
    $stmt_check_status->bindParam(':correo', $correo);
    $stmt_check_status->execute();

    $empleado = $stmt_check_status->fetch(PDO::FETCH_ASSOC);

    if ($empleado && $empleado['estado'] == 1) {
        $_SESSION['login_attempts'][$correo] = 0;
    } else {
        header("Location: login.php?error=locked");
        exit();
    }
}

$login->correo = $correo;
$login->clave = $clave;

if ($login->verificarCredenciales()) {
    unset($_SESSION['login_attempts'][$correo]);

    // Guardar datos en sesión
    $_SESSION['id_Usuario'] = $login->id_Usuario;
    $_SESSION['id_Empleado'] = $login->id_Empleado;
    $_SESSION['correo'] = $login->correo;
    $_SESSION['rol'] = $login->rol;
    $_SESSION['nombre'] = $login->nombre;
    $_SESSION['apellido'] = $login->apellido;

    // ✅ Registrar en bitácora antes de redirigir
    $bitacora->id_Empleado = $_SESSION['id_Empleado'];
    $bitacora->accion = "Inicio de sesión";
    $bitacora->descripcion = "El empleado " . $_SESSION['nombre'] . " " . $_SESSION['apellido'] . " inició sesión correctamente.";
    $bitacora->registrar();

    header("Location: ../login/Dashboard.php");
    exit();
} else {
    $_SESSION['login_attempts'][$correo]++;
    $intentos_restantes = 5 - $_SESSION['login_attempts'][$correo];

    if ($intentos_restantes <= 0) {
        $query = "UPDATE empleados SET estado = 0 WHERE correo = :correo";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        header("Location: login.php?error=locked");
        exit();
    } else {
        $correo_encoded = urlencode($correo);
        header("Location: login.php?error=1&correo={$correo_encoded}&attempts_left={$intentos_restantes}");
        exit();
    }
}
?>