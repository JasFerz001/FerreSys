<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}
 
include_once '../../conexion/conexion.php';
include_once '../login/inicio_sesion.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$login = new Inicio_sesion($db);

$correo = strtolower(trim($_POST['correo']));
$clave = trim($_POST['clave']);

// Inicializar el contador de intentos en la sesión si no existe
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}
if (!isset($_SESSION['login_attempts'][$correo])) {
    $_SESSION['login_attempts'][$correo] = 0;
}

// --- INICIO DEL CAMBIO ---

// Si el contador de sesión indica un bloqueo, verificar el estado real en la BD
if ($_SESSION['login_attempts'][$correo] >= 5) {
    $query_check_status = "SELECT estado FROM empleados WHERE correo = :correo";
    $stmt_check_status = $db->prepare($query_check_status);
    $stmt_check_status->bindParam(':correo', $correo);
    $stmt_check_status->execute();
    
    $empleado = $stmt_check_status->fetch(PDO::FETCH_ASSOC);

    // Si el empleado existe y ha sido reactivado (estado = 1), reiniciamos el contador de intentos.
    if ($empleado && $empleado['estado'] == 1) {
        $_SESSION['login_attempts'][$correo] = 0;
    } else {
        // Si sigue bloqueado en la BD (o no existe), lo redirigimos.
        header("Location: login.php?error=locked");
        exit();
    }
}

// --- FIN DEL CAMBIO ---

$login->correo = $correo;
$login->clave = $clave;

// Verificar credenciales
if ($login->verificarCredenciales()) {

    unset($_SESSION['login_attempts'][$correo]);
    
    $_SESSION['id_Usuario'] = $login->id_Usuario;
    $_SESSION['correo'] = $login->correo;
    $_SESSION['rol'] = $login->rol;

    header("Location: ../login/dashboard.html"); 
    exit();
} else {
    // Si son incorrectas, incrementar el contador
    $_SESSION['login_attempts'][$correo]++;
    $intentos_restantes = 5 - $_SESSION['login_attempts'][$correo];

    if ($intentos_restantes <= 0) {
        // Si se alcanzan los 5 intentos, bloquear la cuenta
        $query = "UPDATE empleados SET estado = 0 WHERE correo = :correo";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        
        header("Location: login.php?error=locked");
        exit();
    } else {
        // Redirigir con error y el número de intentos restantes
        $correo_encoded = urlencode($correo);
        header("Location: login.php?error=1&correo={$correo_encoded}&attempts_left={$intentos_restantes}");
        exit();
    }
}
?>