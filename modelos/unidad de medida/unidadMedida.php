<?php 
class unidadMedida
{
    private $conn;
    private $table_name = "unidad_medida";

    public int $id_Medida; 
    public string $nombre;
    public string $simbolo;
    
    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear(): bool
    {
        // Verificar si el nombre ya existe
        $checkQuery = "SELECT id_Medida FROM " . $this->table_name . " WHERE nombre = :nombre LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":nombre", $this->nombre);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return false;
        }

        // Sanitizar valores
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->simbolo = htmlspecialchars(strip_tags($this->simbolo));

        // Insertar la nueva unidad de medida
        $query = "INSERT INTO " . $this->table_name . " (nombre, simbolo) VALUES (:nombre, :simbolo)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":simbolo", $this->simbolo);

        return $stmt->execute();
    }

    public function actualizar(): bool
    {
        // Verificar duplicados excluyendo el registro actual (solo por nombre)
        $checkQuery = "SELECT id_Medida FROM " . $this->table_name . " 
                   WHERE nombre = :nombre AND id_Medida != :id_Medida LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":nombre", $this->nombre);
        $checkStmt->bindParam(":id_Medida", $this->id_Medida, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return false;
        }

        // Sanitizar valores
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->simbolo = htmlspecialchars(strip_tags($this->simbolo));
        $this->id_Medida = (int) $this->id_Medida;

        // Construir la consulta UPDATE
        $query = "UPDATE " . $this->table_name . " 
              SET nombre = :nombre, simbolo = :simbolo
              WHERE id_Medida = :id_Medida";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":simbolo", $this->simbolo);
        $stmt->bindParam(":id_Medida", $this->id_Medida, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Leer todas las unidades de medida
    public function leer(): PDOStatement
    {
        $query = "
            SELECT id_Medida, nombre, simbolo
            FROM " . $this->table_name . "
            ORDER BY id_Medida ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer una unidad de medida por ID
    public function leerPorId(): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_Medida = :id_Medida LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_Medida', $this->id_Medida, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id_Medida = $row['id_Medida'];
            $this->nombre = $row['nombre'];
            $this->simbolo = $row['simbolo'];

            return true;
        }

        return false;
    }
}
?>