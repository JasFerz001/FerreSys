<?php
session_start();
include_once '../../conexion/conexion.php'; 
$conexion = new Conexion();
$db = $conexion->getConnection();
if (!isset($_SESSION['id_Empleado'])) {
    echo json_encode(["activo" => false]);
    exit();
}

$idEmpleado = $_SESSION['id_Empleado'];

$query = "SELECT estado FROM empleados WHERE id_Empleado = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $idEmpleado, PDO::PARAM_INT);
$stmt->execute();

$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empleado || $empleado['estado'] == 0) {
    echo json_encode(["activo" => false]);
} else {
    echo json_encode(["activo" => true]);
}
