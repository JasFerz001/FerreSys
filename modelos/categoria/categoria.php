<?php
class Categoria
{
    private $conn;
    private $table_name = "categoria";

    public int $id_Categoria;
    public string $nombre;
    public string $descripcion;
    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear(): array
    {
        // Verificar si el nombre ya existe
        $checkQuery = "SELECT id_Categoria FROM " . $this->table_name . " WHERE nombre = :nombre LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":nombre", $this->nombre);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return ['success' => false];
        }

        // Sanitizar valores
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

        // Insertar la nueva categoría
        $query = "INSERT INTO " . $this->table_name . " (nombre, descripcion) VALUES (:nombre, :descripcion)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false];
        }
    }


    // Leer todas las categorías
    public function leer(): PDOStatement
    {
        $query = "
        SELECT id_Categoria, nombre, descripcion
        FROM " . $this->table_name . "
        ORDER BY id_Categoria ASC
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): array
    {
        // Verificar duplicados excluyendo el registro actual
        $checkQuery = "SELECT id_Categoria FROM " . $this->table_name . " 
                   WHERE nombre = :nombre AND id_Categoria != :id_Categoria LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":nombre", $this->nombre);
        $checkStmt->bindParam(":id_Categoria", $this->id_Categoria, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            return ['success' => false];
        }

        // Sanitizar valores
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->id_Categoria = (int) $this->id_Categoria;

        // Construir la consulta UPDATE
        $query = "UPDATE " . $this->table_name . " 
              SET nombre = :nombre, descripcion = :descripcion
              WHERE id_Categoria = :id_Categoria";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":id_Categoria", $this->id_Categoria, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false];
        }
    }

    // Leer una categoría por ID
    public function leerPorId(): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_Categoria = :id_Categoria LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_Categoria', $this->id_Categoria, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id_Categoria = $row['id_Categoria'];
            $this->nombre = $row['nombre'];
            $this->descripcion = $row['descripcion'];

            return true;
        }

        return false;
    }
    public function darDeBaja(): bool
    {
        $query = "UPDATE " . $this->table_name . " 
              SET estado = 0 
              WHERE id_Categoria = :id_Categoria AND estado = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Categoria", $this->id_Categoria, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>