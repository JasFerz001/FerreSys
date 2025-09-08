<?php
class Usuario
{
    private $conn;
    private $table_name = "usuarios";

    public int $id_Usuario;
    public string $rol;
    public bool $estado;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /* Función para insertar usuarios a la BD */
    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET rol=:rol, estado=:estado";
        $stmt = $this->conn->prepare($query);

        $this->rol = strtoupper(htmlspecialchars(strip_tags($this->rol)));
        $this->estado = (int)$this->estado;

        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    /* Función para leer los usuarios de la BD */
    public function leer()
    {
        $query = "SELECT id_Usuario, rol, estado 
              FROM " . $this->table_name . "
              ORDER BY id_Usuario ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    /* Función para leer los usuarios por rol */
    public function leerPorRol()
    {
        $query = "SELECT * FROM " . $this->table_name . " 
              WHERE rol = :rol LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":rol", $this->rol, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id_Usuario = $row['id_Usuario'];
            $this->estado = $row['estado'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            exit;
        }
    }
    /* Función para actualizar los usuarios */
    public function actualizar()
    {
        $query = "UPDATE " . $this->table_name . " 
              SET rol = :rol, estado = :estado
              WHERE id_Usuario = :id_Usuario";

        $stmt = $this->conn->prepare($query);

        $this->id_Usuario = (int)$this->id_Usuario;
        $this->estado = (int)$this->estado;
         $this->rol = strtoupper(trim($this->rol));

        $stmt->bindParam(":id_Usuario", $this->id_Usuario, PDO::PARAM_INT);
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_BOOL);
        $stmt->bindParam(":rol", $this->rol, PDO::PARAM_STR);

        return $stmt->execute();
    }
}
