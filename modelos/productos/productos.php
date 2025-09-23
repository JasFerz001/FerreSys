<?php
class Productos
{
    private $conn;
    private $table_name = "producto";

    public int $id_Producto;
    public string $nombre;
    public $imagen;
    public string $descripcion;
    public int $estado;
    public int $id_Categoria;
    public int $id_Medida;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear(): bool
    {
        // Primero verificamos si ya existe un producto con el mismo nombre
        $query_verificar = "SELECT id_Producto FROM " . $this->table_name . " WHERE nombre = :nombre";
        $stmt_verificar = $this->conn->prepare($query_verificar);
        $stmt_verificar->bindParam(":nombre", $this->nombre);
        $stmt_verificar->execute();

        if ($stmt_verificar->rowCount() > 0) {
            return false;
        }

        // Si no existe, procedemos a insertar 
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, imagen, descripcion, estado, id_Categoria, id_Medida) 
                  VALUES (:nombre, :imagen, :descripcion, :estado, :id_Categoria, :id_Medida)";

        $stmt = $this->conn->prepare($query);

        // Limpiar y bindear los datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":imagen", $this->imagen);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id_Categoria", $this->id_Categoria);
        $stmt->bindParam(":id_Medida", $this->id_Medida);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function leer(): PDOStatement
    {
        $query = "
        SELECT p.id_Producto, p.nombre, p.imagen, p.descripcion, p.estado,
               c.nombre as categoria_nombre, 
               m.nombre as medida_nombre, m.simbolo
        FROM " . $this->table_name . " p
        LEFT JOIN categoria c ON p.id_Categoria = c.id_Categoria
        LEFT JOIN unidad_medida m ON p.id_Medida = m.id_Medida
        ORDER BY p.id_Producto ASC
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): array
    {
        // Primero verificamos si ya existe otro producto con el mismo nombre (excluyendo el actual)
        $query_verificar = "SELECT id_Producto FROM " . $this->table_name . " 
                       WHERE nombre = :nombre AND id_Producto != :id_Producto";
        $stmt_verificar = $this->conn->prepare($query_verificar);
        $stmt_verificar->bindParam(":nombre", $this->nombre);
        $stmt_verificar->bindParam(":id_Producto", $this->id_Producto);
        $stmt_verificar->execute();

        if ($stmt_verificar->rowCount() > 0) {
            return ['success' => false, 'message' => 'Ya existe un producto con ese nombre'];
        }

        // Si no existe conflicto, procedemos a actualizar
        $query = "UPDATE " . $this->table_name . " 
              SET nombre = :nombre, 
                  imagen = :imagen, 
                  descripcion = :descripcion, 
                  estado = :estado, 
                  id_Categoria = :id_Categoria, 
                  id_Medida = :id_Medida 
              WHERE id_Producto = :id_Producto";

        $stmt = $this->conn->prepare($query);

        // Limpiar y bindear los datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

        $stmt->bindParam(":id_Producto", $this->id_Producto);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":imagen", $this->imagen);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id_Categoria", $this->id_Categoria);
        $stmt->bindParam(":id_Medida", $this->id_Medida);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Producto actualizado correctamente'];
        }

        return ['success' => false, 'message' => 'Error al actualizar el producto'];
    }
    public function leerPorId(): bool
    {
        $query = "
    SELECT p.id_Producto, p.nombre, p.imagen, p.descripcion, p.estado,
           p.id_Categoria, p.id_Medida,
           c.nombre as categoria_nombre, 
           m.nombre as medida_nombre, m.simbolo
    FROM " . $this->table_name . " p
    LEFT JOIN categoria c ON p.id_Categoria = c.id_Categoria
    LEFT JOIN unidad_medida m ON p.id_Medida = m.id_Medida
    WHERE p.id_Producto = :id_Producto
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Producto", $this->id_Producto);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->nombre = $row['nombre'];
            $this->imagen = $row['imagen'];
            $this->descripcion = $row['descripcion'];
            $this->estado = $row['estado'];
            $this->id_Categoria = $row['id_Categoria'];
            $this->id_Medida = $row['id_Medida'];

            return true;
        }

        return false;
    }

}
?>