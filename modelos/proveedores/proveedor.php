<?php

Class Proveedor
{
    private $conn;
    private $table_name = "proveedores";

    // Atributos de la tabla proveedores
    public int $id_Proveedor;
    public string $nombre;
    public string $contac_referencia;
    public string $telefono;
    public string $correo;
    public int $estado;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear(): array
    {
       $query = "SELECT id_Proveedor, nombre, contac_referencia, telefono, correo FROM " . $this->table_name . " 
                   WHERE nombre = :nombre OR contac_referencia = :contac_referencia 
                   OR telefono = :telefono OR correo = :correo LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":contac_referencia", $this->contac_referencia);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $duplicates = [];

            if ($existing['nombre'] === $this->nombre) {
                $duplicates[] = 'nombre';
            }
            if ($existing['contac_referencia'] === $this->contac_referencia) {
                $duplicates[] = 'contacto de referencia';
            }
            if ($existing['telefono'] === $this->telefono) {
                $duplicates[] = 'teléfono';
            }
            if ($existing['correo'] === $this->correo) {
                $duplicates[] = 'correo';
            }
            return ['success' => false, 'duplicates' => $duplicates];
        }

        $query = "INSERT INTO " . $this->table_name . " 
          SET nombre=:nombre, contac_referencia=:contac_referencia, 
              telefono=:telefono, correo=:correo, estado=:estado";
        $stmt = $this->conn->prepare($query);

        // Sanitizar entradas
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->contac_referencia = htmlspecialchars(strip_tags($this->contac_referencia));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->correo = htmlspecialchars(strip_tags($this->correo));
        $this->estado = (int) $this->estado;
        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":contac_referencia", $this->contac_referencia);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":correo", $this->correo);
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_INT);

        // Ejecutar consulta
        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'No se pudo crear el proveedor, posiblemente tiene datos duplicados.'];
        }


        public function leer(): PDOStatement
        {
            $query = "SELECT id_Proveedor, nombre, contac_referencia, telefono, correo, estado 
                      FROM " . $this->table_name . " ORDER BY id_Proveedor DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;
        }

        public function leerUno(): bool
        {
            $query = "SELECT id_Proveedor, nombre, contac_referencia, telefono, correo, estado 
                      FROM " . $this->table_name . " 
                      WHERE id_Proveedor = :id_Proveedor LIMIT 1";
            $stmt = $this->conn->prepare($query);
            // Sanitizar entrada
            $this->id_Proveedor = (int) $this->id_Proveedor;
            // Vincular parámetro
            $stmt->bindParam(":id_Proveedor", $this->id_Proveedor, PDO::PARAM_INT);
            // Ejecutar consulta
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                // Asignar valores a los atributos del objeto
                $this->nombre = $row['nombre'];
                $this->contac_referencia = $row['contac_referencia'];
                $this->telefono = $row['telefono'];
                $this->correo = $row['correo'];
                $this->estado = (int) $row['estado'];

                return true;
            }
            return false;
        }

        public function leerActivos(): PDOStatement
        {
            $query = "SELECT * FROM proveedores WHERE estado = 1 ORDER BY id_Proveedor ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt;
        }

        public function actualizar(): array
        {
            // Verificar duplicados
            $checkQuery = "SELECT id_Proveedor, nombre, contac_referencia, telefono, correo FROM " . $this->table_name . " 
                   WHERE (nombre = :nombre OR contac_referencia = :contac_referencia 
                   OR telefono = :telefono OR correo = :correo) AND id_Proveedor != :id_Proveedor LIMIT 1";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":nombre", $this->nombre);    
            $checkStmt->bindParam(":contac_referencia", $this->contac_referencia);
            $checkStmt->bindParam(":telefono", $this->telefono);    
            $checkStmt->bindParam(":correo", $this->correo);
            $checkStmt->bindParam(":id_Proveedor", $this->id_Proveedor, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
                $duplicates = [];

                if ($existing['nombre'] === $this->nombre) {
                    $duplicates[] = 'nombre';
                }
                if ($existing['contac_referencia'] === $this->contac_referencia) {
                    $duplicates[] = 'contacto de referencia';
                }
                if ($existing['telefono'] === $this->telefono) {
                    $duplicates[] = 'teléfono';
                }
                if ($existing['correo'] === $this->correo) {
                    $duplicates[] = 'correo';
                }

                return ['success' => false, 'duplicates' => $duplicates];
            }

            $query = "UPDATE " . $this->table_name . " 
                      SET nombre=:nombre, contac_referencia=:contac_referencia, 
                          telefono=:telefono, correo=:correo, estado=:estado 
                      WHERE id_Proveedor = :id_Proveedor";

            $stmt = $this->conn->prepare($query);

            // Sanitizar entradas
            $this->id_Proveedor = (int) $this->id_Proveedor;
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->contac_referencia = htmlspecialchars(strip_tags($this->contac_referencia));
            $this->telefono = htmlspecialchars(strip_tags($this->telefono));
            $this->correo = htmlspecialchars(strip_tags($this->correo));
            $this->estado = (int) $this->estado;

            // Vincular parámetros
            $stmt->bindParam(":id_Proveedor", $this->id_Proveedor, PDO::PARAM_INT);
            $stmt->bindParam(":nombre", $this->nombre);
            $stmt->bindParam(":contac_referencia", $this->contac_referencia);
            $stmt->bindParam(":telefono", $this->telefono);
            $stmt->bindParam(":correo", $this->correo);
            $stmt->bindParam(":estado", $this->estado, PDO::PARAM_INT);

            // Ejecutar consulta
            if ($stmt->execute()) {
                return ['success' => true];
            }else {
                return ['success' => false, 'message' => 'No se pudo actualizar el proveedor, posiblemente tiene datos duplicados.'];
            }
        }

        public function dardeBaja(): bool
        {
            $query = "UPDATE " . $this->table_name . " 
                      SET estado = 0 
                      WHERE id_Proveedor = :id_Proveedor AND estado = 1";

            $stmt = $this->conn->prepare($query);

            // Sanitizar entrada
            $this->id_Proveedor = (int) $this->id_Proveedor;

            // Vincular parámetro
            $stmt->bindParam(":id_Proveedor", $this->id_Proveedor, PDO::PARAM_INT);

            // Ejecutar consulta
            $stmt->execute();
            return $stmt->rowCount() > 0;
        }

}
?>