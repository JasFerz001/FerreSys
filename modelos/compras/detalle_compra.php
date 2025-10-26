<?php
class DetalleCompra
{
    private $conn;
    private $table_name = "detalle_compra";

    public int $id_DetalleCompra;
    public int $id_Compra;
    public int $id_Producto;
    public int $cantidad;
    public int $existencia;
    public float $precio_unitario;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET id_Compra=:id_Compra, id_Producto=:id_Producto, 
                     cantidad=:cantidad, existencia=:existencia, precio_unitario=:precio_unitario";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_Compra", $this->id_Compra);
        $stmt->bindParam(":id_Producto", $this->id_Producto);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":existencia", $this->existencia);
        $stmt->bindParam(":precio_unitario", $this->precio_unitario);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function obtenerDetalleCompra($id_Compra)
    {
        $query = "SELECT 
                    co.id_Compra AS CodigoCompra,
                    co.fecha AS Fecha,
                    pr.nombre AS Proveedor,
                    CONCAT(e.nombre, ' ', e.apellido) AS Empleado,
                    p.nombre AS Producto,
                    dc.cantidad AS Cantidad,
                    dc.precio_unitario AS PrecioUnitario,
                    (dc.cantidad * dc.precio_unitario) AS Subtotal
                FROM detalle_compra dc
                INNER JOIN compra co ON dc.id_Compra = co.id_Compra
                INNER JOIN proveedores pr ON co.id_Proveedor = pr.id_Proveedor
                INNER JOIN empleados e ON co.id_Empleado = e.id_Empleado
                INNER JOIN producto p ON dc.id_Producto = p.id_Producto
                WHERE co.id_Compra = :id_Compra";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Compra", $id_Compra);
        $stmt->execute();

        return $stmt;
    }


    public function obtenerListadoCompras()
    {
        $query = "SELECT 
                    co.id_Compra AS CodigoCompra,
                    co.fecha AS Fecha,
                    CONCAT(e.nombre, ' ', e.apellido) AS Empleado
                FROM compra co
                INNER JOIN empleados e ON co.id_Empleado = e.id_Empleado
                ORDER BY co.fecha DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
 
        return $stmt;
    }

    // Método para obtener productos por categoría
    public function obtenerProductosPorCategoria($id_categoria)
    {
        $query = "SELECT 
                    p.nombre AS Producto,
                    c.nombre AS Categoria,
                    um.nombre AS UnidadMedida,
                    pr.nombre AS Proveedor,
                    SUM(dc.existencia) AS Stock,
                    p.imagen AS Imagen
                  FROM producto p
                  INNER JOIN categoria c ON p.id_Categoria = c.id_Categoria
                  INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida
                  INNER JOIN detalle_compra dc ON p.id_Producto = dc.id_Producto
                  INNER JOIN compra co ON dc.id_Compra = co.id_Compra
                  INNER JOIN proveedores pr ON co.id_Proveedor = pr.id_Proveedor
                  WHERE p.id_Categoria = :id_categoria
                  GROUP BY p.id_Producto, pr.id_Proveedor
                  ORDER BY p.nombre, pr.nombre";

        $stmt = $this->conn->prepare($query); 
        $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}

?>