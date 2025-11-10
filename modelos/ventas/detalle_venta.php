<?php
class Detalle_venta
{
    private $conn;
    private $table_name = "detalle_venta";

    public $id_detalleventa;
    public $id_Detallecompra;
    public $id_Venta;
    public $precio_venta;
    public $cantidad;
    public $total;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear()
    {
        $query = "INSERT INTO {$this->table_name}
                  (id_Detallecompra, id_Venta, precio_venta, cantidad, total)
                  VALUES (:id_Detallecompra, :id_Venta, :precio_venta, :cantidad, :total)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_Detallecompra", $this->id_Detallecompra);
        $stmt->bindParam(":id_Venta", $this->id_Venta);
        $stmt->bindParam(":precio_venta", $this->precio_venta);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":total", $this->total);

        return $stmt->execute();
    }

    public function obtenerDetalleVenta($id_Venta)
    {
        $query = "SELECT 
                    dv.id_detalleventa,
                    p.nombre AS producto,
                    c.nombre AS categoria,
                    u.simbolo AS unidad_medida,
                    dv.precio_venta,
                    dv.cantidad,
                    dv.total
                FROM detalle_venta dv
                INNER JOIN detalle_compra dc ON dv.id_Detallecompra = dc.id_Detallecompra
                INNER JOIN producto p ON dc.id_Producto = p.id_Producto
                INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
                INNER JOIN unidad_medida u ON p.id_Medida = u.id_Medida
                WHERE dv.id_Venta = :id_Venta";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Venta", $id_Venta, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerListadoProductos($id_Categoria)
    {
        $query = "SELECT 
                    p.id_Producto,
                    p.nombre AS nombre_producto,
                    p.imagen,
                    p.descripcion,
                    c.nombre AS categoria,
                    um.nombre AS unidad_medida,
                    um.simbolo,
                    dc.existencia,
                    prov.nombre AS proveedor
                FROM producto p
                INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
                INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida
                LEFT JOIN detalle_compra dc ON p.id_Producto = dc.id_Producto
                LEFT JOIN compra comp ON dc.id_Compra = comp.id_Compra
                LEFT JOIN proveedores prov ON comp.id_Proveedor = prov.id_Proveedor
                WHERE c.id_Categoria =:id_Categoria AND p.estado = 1
                ORDER BY p.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Categoria", $id_Categoria);
        $stmt->execute();

        return $stmt;

    }

    // En tu archivo detalle_venta.php, modifica el método obtenerDetalleVenta
    public function obtenerDetalledeVenta($id_Venta)
    {
        $query = "SELECT 
    dv.id_detalleventa,
    p.nombre AS producto,
    c.nombre AS categoria,
    -- 🔹 Si el producto no tiene conversión, usa la unidad de producto
    COALESCE(ub.simbolo, ubase.simbolo) AS unidad_base,
    -- 🔹 Si no existe unidad de venta, deja el campo vacío
    CASE 
        WHEN uv.simbolo IS NULL THEN '' 
        ELSE uv.simbolo 
    END AS unidad_venta,
    dv.precio_venta,
    dv.cantidad,
    dv.total,
    ve.fecha AS Fecha,
    CONCAT(emp.nombre, ' ', emp.apellido) AS Empleado,
    CONCAT(cli.nombre, ' ', cli.apellido) AS Cliente
FROM detalle_venta dv
INNER JOIN detalle_compra dc ON dv.id_Detallecompra = dc.id_Detallecompra
INNER JOIN producto p ON dc.id_Producto = p.id_Producto
INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
-- 🔹 Unimos con conversion_unidades
LEFT JOIN conversion_unidades cu ON p.id_Producto = cu.id_Producto
LEFT JOIN unidad_medida ub ON cu.id_Unidad_Base = ub.id_Medida
LEFT JOIN unidad_medida uv ON cu.id_Unidad_Venta = uv.id_Medida
-- 🔹 Esta es la unidad base definida directamente en el producto
INNER JOIN unidad_medida ubase ON p.id_Medida = ubase.id_Medida
INNER JOIN ventas ve ON dv.id_Venta = ve.id_Venta
INNER JOIN empleados emp ON ve.id_Empleado = emp.id_Empleado
INNER JOIN clientes cli ON ve.id_Cliente = cli.id_Cliente
WHERE dv.id_Venta = :id_Venta
ORDER BY ve.id_Venta DESC;


";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Venta", $id_Venta, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProductosConExistencia($idCategoria = '')
    {
        $query = "SELECT DISTINCT
                p.id_Producto,
                p.imagen,
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
            LEFT JOIN compra comp ON dc.id_Compra = comp.id_Compra
            LEFT JOIN proveedores prov ON comp.id_Proveedor = prov.id_Proveedor
            WHERE p.estado = 1 AND dc.existencia > 0.01";

        if ($idCategoria) {
            $query .= " AND c.id_Categoria = :id_categoria";
        }

        $query .= " ORDER BY p.nombre, prov.nombre";

        $stmt = $this->conn->prepare($query);

        if ($idCategoria) {
            $stmt->bindParam(":id_categoria", $idCategoria);
        }

        $stmt->execute();
        return $stmt;
    }

    // En tu clase Detalle_venta
// En tu clase Detalle_venta, modifica el método obtenerUnidadesVenta
    public function obtenerUnidadesVenta($id_Producto)
    {
        // Primero obtener la unidad base del producto
        $query_producto = "SELECT p.id_Medida, um.nombre as unidad_base, um.simbolo as simbolo_base 
                      FROM producto p 
                      INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida 
                      WHERE p.id_Producto = :id_Producto";
        $stmt_producto = $this->conn->prepare($query_producto);
        $stmt_producto->bindParam(":id_Producto", $id_Producto);
        $stmt_producto->execute();
        $producto = $stmt_producto->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            return [];
        }

        // Obtener las conversiones definidas para este producto
        $query_conversiones = "SELECT cu.id_Unidad_Venta, cu.factor_conversion, 
                                  um.nombre as unidad_venta, um.simbolo as simbolo_venta
                           FROM conversion_unidades cu
                           INNER JOIN unidad_medida um ON cu.id_Unidad_Venta = um.id_Medida
                           WHERE cu.id_Producto = :id_Producto AND cu.es_activo = 1";
        $stmt_conversiones = $this->conn->prepare($query_conversiones);
        $stmt_conversiones->bindParam(":id_Producto", $id_Producto);
        $stmt_conversiones->execute();
        $conversiones = $stmt_conversiones->fetchAll(PDO::FETCH_ASSOC);

        return [
            'unidad_base' => $producto,
            'conversiones' => $conversiones
        ];
    }

    public function obtenerProductosConExistenciaYConversiones($idCategoria = '')
    {
        $query = "SELECT DISTINCT
                p.id_Producto,
                p.nombre AS nombre_producto,
                p.descripcion,
                c.nombre AS categoria,
                um.nombre AS unidad_medida,
                um.simbolo,
                um.id_Medida,
                dc.id_Detallecompra,
                dc.existencia,
                dc.precio_unitario,
                prov.nombre AS proveedor
            FROM producto p
            INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
            INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida
            INNER JOIN detalle_compra dc ON p.id_Producto = dc.id_Producto
            LEFT JOIN compra comp ON dc.id_Compra = comp.id_Compra
            LEFT JOIN proveedores prov ON comp.id_Proveedor = prov.id_Proveedor
            WHERE p.estado = 1 AND dc.existencia > 0";

        if ($idCategoria) {
            $query .= " AND c.id_Categoria = :id_categoria";
        }

        $query .= " ORDER BY p.nombre, prov.nombre";

        $stmt = $this->conn->prepare($query);

        if ($idCategoria) {
            $stmt->bindParam(":id_categoria", $idCategoria);
        }

        $stmt->execute();
        return $stmt;
    }

}



?>