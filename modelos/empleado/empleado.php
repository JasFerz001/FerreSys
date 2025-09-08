<?php
class Empleado
{
    private $conn;
    private $table_name = "empleados";

    // Atributos de la tabla empleados
    public int $id_Empleado; 
    public string $nombre;
    public string $apellido;
    public string $DUI;
    public string $telefono;
    public string $direccion;
    public string $correo;
    public string $clave;
    public int $estado;
    public int $id_Usuario;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Crear un empleado nuevo (valida DUI/correo/teléfono únicos)
    public function crear(): array
    {
        // Verificar duplicados
        $checkQuery = "SELECT id_Empleado, DUI, correo, telefono FROM " . $this->table_name . " 
                   WHERE DUI = :DUI OR correo = :correo OR telefono = :telefono LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":DUI", $this->DUI);
        $checkStmt->bindParam(":correo", $this->correo);
        $checkStmt->bindParam(":telefono", $this->telefono);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $duplicates = [];

            if ($existing['DUI'] === $this->DUI) {
                $duplicates[] = 'DUI';
            }
            if ($existing['correo'] === $this->correo) {
                $duplicates[] = 'correo';
            }
            if ($existing['telefono'] === $this->telefono) {
                $duplicates[] = 'teléfono';
            }

            return ['success' => false, 'duplicates' => $duplicates];
        }

        $clave_encriptada = password_hash($this->clave, PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table_name . " 
          SET nombre=:nombre, apellido=:apellido, DUI=:DUI, 
              telefono=:telefono, direccion=:direccion, 
              correo=:correo, clave=:clave, estado=:estado, id_Usuario=:id_Usuario";

        $stmt = $this->conn->prepare($query);

        // Sanitizar valores
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->DUI = htmlspecialchars(strip_tags($this->DUI));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->correo = htmlspecialchars(strip_tags($this->correo));
        $this->estado = (int) $this->estado;
        $this->id_Usuario = (int) $this->id_Usuario;

        // Bind params
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":DUI", $this->DUI);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":clave", $clave_encriptada);
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_INT);
        $stmt->bindParam(":id_Usuario", $this->id_Usuario, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'duplicates' => []];
        }
    }

    // Leer todos los empleados
    public function leer(): PDOStatement
    {
        $query = "
        SELECT e.*, u.rol AS nombre_usuario
        FROM " . $this->table_name . " e
        LEFT JOIN usuarios u ON e.id_Usuario = u.id_Usuario
        ORDER BY e.id_Empleado ASC
    ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer usuarios activos
    public function leerUsuariosActivos(): PDOStatement
    {
        $query = "SELECT * FROM usuarios WHERE estado = 1 ORDER BY id_Usuario ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un empleado por ID
    public function leerPorId(): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_Empleado = :id_Empleado LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_Empleado', $this->id_Empleado, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id_Empleado = $row['id_Empleado'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->DUI = $row['dui'];
            $this->telefono = $row['telefono'];
            $this->direccion = $row['direccion'];
            $this->correo = $row['correo'];
            $this->clave = $row['clave'];
            $this->estado = $row['estado'];
            $this->id_Usuario = $row['id_Usuario'];

            return true;
        }

        return false;
    }

    // Leer un empleado por DUI
    public function leerPorDUI(): bool
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE DUI = :DUI LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':DUI', $this->DUI);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->id_Empleado = $row['id_Empleado'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->DUI = $row['DUI'];
            $this->telefono = $row['telefono'];
            $this->direccion = $row['direccion'];
            $this->correo = $row['correo'];
            $this->clave = $row['clave'];
            $this->estado = $row['estado'];
            $this->id_Usuario = $row['id_Usuario'];

            return true;
        }

        return false;
    }

    // Actualizar un empleado existente (valida DUI/correo/teléfono únicos excluyendo el actual)
    public function actualizar(): array
    {
        // Verificar duplicados excluyendo el registro actual
        $checkQuery = "SELECT id_Empleado, DUI, correo, telefono FROM " . $this->table_name . " 
                   WHERE (DUI = :DUI OR correo = :correo OR telefono = :telefono) 
                   AND id_Empleado != :id_Empleado LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":DUI", $this->DUI);
        $checkStmt->bindParam(":correo", $this->correo);
        $checkStmt->bindParam(":telefono", $this->telefono);
        $checkStmt->bindParam(":id_Empleado", $this->id_Empleado, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $duplicates = [];

            if ($existing['DUI'] === $this->DUI) {
                $duplicates[] = 'DUI';
            }
            if ($existing['correo'] === $this->correo) {
                $duplicates[] = 'correo';
            }
            if ($existing['telefono'] === $this->telefono) {
                $duplicates[] = 'teléfono';
            }

            return ['success' => false, 'duplicates' => $duplicates];
        }

        // Construir la consulta UPDATE
        $query = "UPDATE " . $this->table_name . " 
          SET nombre=:nombre, apellido=:apellido, DUI=:DUI, 
              telefono=:telefono, direccion=:direccion, 
              correo=:correo, estado=:estado, id_Usuario=:id_Usuario";

        // Agregar la clave solo si se proporcionó una nueva
        if (!empty($this->clave)) {
            $clave_encriptada = password_hash($this->clave, PASSWORD_DEFAULT);
            $query .= ", clave=:clave";
        }

        $query .= " WHERE id_Empleado=:id_Empleado";

        $stmt = $this->conn->prepare($query);

        // Sanitizar valores
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->DUI = htmlspecialchars(strip_tags($this->DUI));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->correo = htmlspecialchars(strip_tags($this->correo));
        $this->estado = (int) $this->estado;
        $this->id_Usuario = (int) $this->id_Usuario;
        $this->id_Empleado = (int) $this->id_Empleado;

        // Bind params
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":DUI", $this->DUI);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_INT);
        $stmt->bindParam(":id_Usuario", $this->id_Usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_Empleado", $this->id_Empleado, PDO::PARAM_INT);

        // Bind de la clave solo si se proporcionó
        if (!empty($this->clave)) {
            $stmt->bindParam(":clave", $clave_encriptada);
        }

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'duplicates' => []];
        }
    }

    // Dar de baja (estado = 0) a un empleado
    public function darDeBaja(): bool
    {
        $query = "UPDATE " . $this->table_name . " SET estado = 0 WHERE id_Empleado = :id_Empleado AND estado = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Empleado", $this->id_Empleado, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>