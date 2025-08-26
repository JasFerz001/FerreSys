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

    /**
     * Crear un nuevo empleado
     * - Verifica que no exista un empleado con el mismo DUI.
     * - Si no existe, inserta el registro en la tabla.
     * 
     * true si se creó correctamente, false si el DUI ya existe o falla la inserción.
     */
    public function crear(): bool
    {
        // Verificar si ya existe un empleado con el mismo DUI
        $checkQuery = "SELECT id_Empleado FROM " . $this->table_name . " WHERE DUI = :DUI LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":DUI", $this->DUI);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            // Ya existe un empleado con ese DUI
            return false;
        }

        // Query para insertar un nuevo empleado
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
        $this->clave = htmlspecialchars(strip_tags($this->clave));
        $this->estado = (int) $this->estado;
        $this->id_Usuario = (int) $this->id_Usuario;

        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":DUI", $this->DUI);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":clave", $this->clave); //
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_INT);
        $stmt->bindParam(":id_Usuario", $this->id_Usuario, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Leer todos los empleados.
     * 
     *  PDOStatement Conjunto de resultados con todos los empleados.
     */
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

     /*llamar todos los roles activos para seleccion en el form empleado*/ 

    public function leerUsuariosActivos(): PDOStatement
    {
        $query = "SELECT * FROM usuarios WHERE estado = 1 ORDER BY id_Usuario ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }


    /**
     * Leer un empleado por su DUI.
     * - Busca el empleado con el DUI especificado.
     * - Carga los datos en los atributos de la clase.
     * 
     * true si encontró el empleado, false si no existe.
     */
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

    /**
     * Actualizar los datos de un empleado existente.
     * - Verifica que no exista otro empleado con el mismo DUI.
     * - Si la validación pasa, actualiza los datos.
     * 
     * true si se actualizó correctamente, false si ya existe otro DUI o falla la actualización.
     */
    public function actualizar(): bool
    {
        // Verificar si el DUI ya existe en otro registro
        $checkQuery = "SELECT id_Empleado FROM " . $this->table_name . " 
                       WHERE DUI = :DUI AND id_Empleado != :id_Empleado LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":DUI", $this->DUI);
        $checkStmt->bindParam(":id_Empleado", $this->id_Empleado, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            // Ya existe otro empleado con ese DUI
            return false;
        }

        // Query para actualizar
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre=:nombre, apellido=:apellido, DUI=:DUI, 
                      telefono=:telefono, direccion=:direccion, 
                      correo=:correo, clave=:clave, estado=:estado, id_Usuario=:id_Usuario
                  WHERE id_Empleado=:id_Empleado";

        $stmt = $this->conn->prepare($query);

        // Sanitizar valores
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->DUI = htmlspecialchars(strip_tags($this->DUI));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->correo = htmlspecialchars(strip_tags($this->correo));
        $this->clave = htmlspecialchars(strip_tags($this->clave));
        $this->estado = (int) $this->estado;
        $this->id_Usuario = (int) $this->id_Usuario;
        $this->id_Empleado = (int) $this->id_Empleado;

        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":DUI", $this->DUI);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":clave", $this->clave); // ⚠️ Clave sin encriptar
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_INT);
        $stmt->bindParam(":id_Usuario", $this->id_Usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_Empleado", $this->id_Empleado, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Dar de baja a un empleado
     * - Cambia el estado del empleado a 0 (inactivo/baja)
     * - No elimina el registro de la base de datos
     * 
     * true si se actualizó correctamente, false si falla
     */
    public function darDeBaja(): bool
    {
        $query = "UPDATE " . $this->table_name . " SET estado = 0 WHERE id_Empleado = :id_Empleado";
        $stmt = $this->conn->prepare($query);

        // Sanitizar id_Empleado
        $this->id_Empleado = (int) $this->id_Empleado;

        // Vincular parámetro
        $stmt->bindParam(":id_Empleado", $this->id_Empleado, PDO::PARAM_INT);

        return $stmt->execute();
    }

}
?>