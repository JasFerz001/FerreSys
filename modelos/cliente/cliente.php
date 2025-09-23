<?php
class Cliente
{
    private $conn;
    private $table_name = "clientes";

    public int $id_Cliente;
    public String $nombre;
    public String $apellido;
    public String $dui;
    public String $direccion;
    public String $correo;

    public function __construct($db)
    {
        $this->conn = $db;
    }


    public function crear(): array
    {
        // Verificar duplicados
        $checkQuery = "SELECT id_Cliente, dui, correo FROM " . $this->table_name . " 
                   WHERE dui = :dui OR correo = :correo LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":dui", $this->dui);
        $checkStmt->bindParam(":correo", $this->correo);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $duplicates = [];

            if ($existing['dui'] === $this->dui) {
                $duplicates[] = 'DUI';
            }
            if ($existing['correo'] === $this->correo) {
                $duplicates[] = 'correo';
            }
            return ['success' => false, 'duplicates' => $duplicates];
        }

        // Insertando Cliente a la Base de Datos
        $query = "INSERT INTO " . $this->table_name . " 
              SET nombre=:nombre, apellido=:apellido, dui=:dui, 
                  direccion=:direccion, correo=:correo";
        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->dui = htmlspecialchars(strip_tags($this->dui));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->correo = htmlspecialchars(strip_tags($this->correo));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":dui", $this->dui);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":correo", $this->correo);

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'duplicates' => []];
        }
    }


    //Mostrar todos los clientes
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

    // Leer un cliente por ID
    public function leerPorId(): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_Cliente = :id_Cliente LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_Cliente', $this->id_Cliente, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id_Cliente = $row['id_Cliente'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->dui = $row['dui'];
            $this->direccion = $row['direccion'];
            $this->correo = $row['correo'];

            return true;
        }

        return false;
    }

    // Leer un cliente por DUI
    public function leerPorDUI(): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE dui = :dui LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':dui', $this->dui);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id_Cliente = $row['id_Cliente'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->dui = $row['dui'];
            $this->direccion = $row['direccion'];
            $this->correo = $row['correo'];

            return true;
        }

        return false;
    }

    // Actualizar un cliente existente (valida DUI/correo únicos excluyendo el actual)
    public function actualizar(): array
    {
        // Verificar duplicados excluyendo el registro actual
        $checkQuery = "SELECT id_Cliente, dui, correo 
                   FROM " . $this->table_name . " 
                   WHERE (dui = :dui OR correo = :correo) 
                   AND id_Cliente != :id_Cliente LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":dui", $this->dui);
        $checkStmt->bindParam(":correo", $this->correo);
        $checkStmt->bindParam(":id_Cliente", $this->id_Cliente, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $duplicates = [];

            if ($existing['dui'] === $this->dui) {
                $duplicates[] = 'dui';
            }
            if ($existing['correo'] === $this->correo) {
                $duplicates[] = 'correo';
            }

            return ['success' => false, 'duplicates' => $duplicates];
        }

        // Construir la consulta UPDATE
        $query = "UPDATE " . $this->table_name . " 
              SET nombre = :nombre, 
                  apellido = :apellido, 
                  dui = :dui, 
                  direccion = :direccion, 
                  correo = :correo
              WHERE id_Cliente = :id_Cliente";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->dui = htmlspecialchars(strip_tags($this->dui));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->correo = htmlspecialchars(strip_tags($this->correo));
        $this->id_Cliente = (int) $this->id_Cliente;

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":dui", $this->dui);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":id_Cliente", $this->id_Cliente, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'duplicates' => []];
        }
    }
}
