<?php
class Ventas
{
    private $conn;
    private $table_name = "ventas";

    public $id_Venta;
    public $fecha;
    public $id_Cliente;
    public $id_Empleado;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear()
    {
        $query = "INSERT INTO {$this->table_name} (fecha, id_Cliente, id_Empleado)
                  VALUES (:fecha, :id_Cliente, :id_Empleado)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":id_Cliente", $this->id_Cliente);
        $stmt->bindParam(":id_Empleado", $this->id_Empleado);

        return $stmt->execute();
    }

    public function obtenerUltimaVenta()
    {
        $query = "SELECT MAX(id_Venta) AS id_Venta FROM {$this->table_name}";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['id_Venta'] ?? null;
    }

    public function obtenerVenta($id_Venta)
    {
        $query = "SELECT 
                    ve.id_Venta AS CodigoVenta,
                    ve.fecha AS Fecha,
                    cl.nombre AS Cliente,
                    CONCAT(e.nombre, ' ', e.apellido) AS Empleado
                FROM ventas ve
                INNER JOIN clientes cl ON ve.id_Cliente = cl.id_Cliente
                INNER JOIN empleados e ON ve.id_Empleado = e.id_Empleado
                WHERE ve.id_Venta = :id_Venta";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Venta", $id_Venta);
        $stmt->execute();
        return $stmt;
    }

    public function leer(): PDOStatement
    {
        $query = "
    SELECT c.id_Cliente,
           c.nombre,
           c.apellido,
           c.dui,
           c.direccion,
           c.correo
    FROM clientes c
    ORDER BY c.id_Cliente ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // En tu archivo ventas.php, agrega este método
    public function obtenerListadoVentas()
    { 
        $query = "SELECT 
                ve.id_Venta AS CodigoVenta,
                ve.fecha AS Fecha,
                CONCAT(e.nombre, ' ', e.apellido) AS Empleado,
                CONCAT(cl.nombre, ' ', cl.apellido) AS Cliente
            FROM ventas ve
            INNER JOIN empleados e ON ve.id_Empleado = e.id_Empleado
            INNER JOIN clientes cl ON ve.id_Cliente = cl.id_Cliente
                        GROUP BY ve.id_Venta
            ORDER BY ve.fecha DESC
";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
}
?>