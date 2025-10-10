<?php
class Compra
{
    private $conn;
    private $table_name = "compra";

    public int $id_Compra;
    public string $fecha;
    public int $id_Proveedor;
    public int $id_Empleado;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET fecha=:fecha, id_Proveedor=:id_Proveedor, id_Empleado=:id_Empleado";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":fecha", $this->fecha);
        $stmt->bindParam(":id_Proveedor", $this->id_Proveedor);
        $stmt->bindParam(":id_Empleado", $this->id_Empleado);

        if ($stmt->execute()) {
            $this->id_Compra = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}

?>