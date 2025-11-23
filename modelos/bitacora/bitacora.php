<?php
class Bitacora
{
    private $conn;
    private $table_name = "bitacora";

    public int $id_Bitacora;
    public int $id_Empleado;
    public string $accion;
    public string $descripcion;
    public string $fecha_hora;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function registrar(): bool
    {
        // Ajustar zona horaria a El Salvador
        date_default_timezone_set('America/El_Salvador');

        // Obtener la fecha y hora local correctas
        $this->fecha_hora = date('Y-m-d H:i:s');

        $query = "INSERT INTO " . $this->table_name . " 
              (id_Empleado, accion, descripcion, fecha_hora)
              VALUES (:id_Empleado, :accion, :descripcion, :fecha_hora)";

        $stmt = $this->conn->prepare($query);

        $this->accion = strip_tags($this->accion);
        $this->descripcion = strip_tags($this->descripcion);

        $stmt->bindParam(":id_Empleado", $this->id_Empleado);
        $stmt->bindParam(":accion", $this->accion);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":fecha_hora", $this->fecha_hora);

        return $stmt->execute();
    }


    // 🔹 Leer todas las acciones registradas (últimas primero)
    public function leer(): PDOStatement
    {
        $query = "
            SELECT 
                b.id_Bitacora,
                e.nombre AS empleado,
                b.accion,
                b.descripcion,
                b.fecha_hora
            FROM bitacora b
            INNER JOIN empleados e ON b.id_Empleado = e.id_Empleado
            ORDER BY b.fecha_hora DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // 🔹 Leer las acciones por empleado
    public function leerPorEmpleado(): PDOStatement
    {
        $query = "
            SELECT 
                b.id_Bitacora,
                b.accion,
                b.descripcion,
                b.fecha_hora
            FROM " . $this->table_name . " b
            WHERE b.id_Empleado = :id_Empleado
            ORDER BY b.fecha_hora DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Empleado", $this->id_Empleado);
        $stmt->execute();
        return $stmt;
    }
}
?>