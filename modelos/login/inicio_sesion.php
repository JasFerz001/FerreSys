<?php

class Inicio_sesion
{
    private $conn;
    private $table_name = "empleados";
    private $table = "usuarios";
    public string $rol;
    public string $clave;
    public string $correo;
    public int $id_Usuario;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Verifica las credenciales de inicio de sesión (correo + clave),
     * y además que tanto el empleado como el rol (usuario) estén activos.
     */

    public function existeAlgunUsuario(): bool
{
    $query = "SELECT COUNT(*) as total FROM " . $this->table;
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row['total'] > 0; // true si ya hay usuarios, false si está vacío
}


    public function verificarCredenciales(): bool
    {
        $query = "SELECT u.id_Usuario, u.rol, u.estado AS estado_usuario, 
                         e.estado AS estado_empleado, e.correo, e.clave
                  FROM empleados e 
                  INNER JOIN usuarios u ON e.id_Usuario = u.id_Usuario 
                  WHERE e.correo = :correo 
                  AND u.estado = TRUE 
                  AND e.estado = TRUE";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->correo = htmlspecialchars(strip_tags($this->correo));
        $this->clave = htmlspecialchars(strip_tags($this->clave));

        // Vincular parámetros
        $stmt->bindParam(":correo", $this->correo);

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar que la clave coincida (puede ser encriptada con password_verify)
            if ($this->clave === $row['clave']) {
                $this->id_Usuario = (int) $row['id_Usuario'];
                $this->rol = $row['rol'];
                return true;
            }
        }

        return false; // Si no pasó las validaciones
    }
}

?>
