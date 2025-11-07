<?php
session_start();

include_once '../../conexion/conexion.php';
include_once '../../modelos/bitacora/bitacora.php';

// Solo registrar en bitácora si el usuario estaba autenticado
if (isset($_SESSION['id_Empleado'])) {
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    $bitacora = new Bitacora($db);

    $bitacora->id_Empleado = $_SESSION['id_Empleado'];
    $bitacora->accion = "Cierre de sesión";
    $bitacora->descripcion = "El empleado " . $_SESSION['nombre'] . " " . $_SESSION['apellido'] . " cerró sesión correctamente.";
    $bitacora->registrar();
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: ../login/login.php");
exit();
?>