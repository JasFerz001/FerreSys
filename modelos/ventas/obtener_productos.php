<?php
session_start();
include_once '../../conexion/conexion.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

header('Content-Type: application/json');

try {
    $idCategoria = $_POST['id_categoria'] ?? '';

    $query = "SELECT DISTINCT
                p.id_Producto,
                p.nombre AS nombre_producto,
                p.descripcion,
                c.nombre AS categoria,
                um.nombre AS unidad_medida,
                um.simbolo,
                dc.id_Detallecompra,
                dc.existencia,
                dc.precio_unitario,
                prov.nombre AS proveedor
            FROM producto p
            INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
            INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida
            INNER JOIN detalle_compra dc ON p.id_Producto = dc.id_Producto
            INNER JOIN compra comp ON dc.id_Compra = comp.id_Compra
            LEFT JOIN proveedores prov ON comp.id_Proveedor = prov.id_Proveedor
            WHERE p.estado = 1
              AND dc.existencia > 0";

    if (!empty($idCategoria)) {
        $query .= " AND c.id_Categoria = :id_categoria";
    }

    $query .= " ORDER BY p.nombre ASC";

    $stmt = $db->prepare($query);

    if (!empty($idCategoria)) {
        $stmt->bindParam(":id_categoria", $idCategoria, PDO::PARAM_INT);
    }

    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
}
