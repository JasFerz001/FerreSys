<?php
session_start();
require_once "inicio_sesion.php"; // tu clase

// Conexión a BD (ajustá tus credenciales)
$host = "localhost";
$db_name = "ferresys";
$username = "root";
$password = "";
try {
    $db = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Crear objeto de inicio de sesión
$login = new Inicio_sesion($db);
$login->correo = $_POST['correo'];
$login->clave  = $_POST['clave'];

// Verificar credenciales
if ($login->verificarCredencialesPorId()) {
    // Guardar datos en sesión
    $_SESSION['correo'] = $login->correo;
    $_SESSION['rol']    = $login->rol;

    // Redirigir a crear_empleado.php
    header("Location: crear_empleado.php");
    exit;
} else {
    // Volver al login con error
    echo "<script>alert('Credenciales inválidas o usuario inactivo.'); window.location.href='index.html';</script>";
}
