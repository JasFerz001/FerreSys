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
     * Verifica las credenciales de inicio de sesión usando id_Usuario.
     * 
     * @return bool true si las credenciales son válidas, false en caso contrario.
     */
    public function verificarCredencialesPorId(): bool
    {
        // Query: solo trae la info del usuario/empleado si ambos están activos
        $query = "SELECT  u.id_Usuario, u.rol, u.estado AS estado_usuario, e.estado AS estado_empleado, e.correo, e.clave
                  FROM empleados e INNER JOIN usuarios u ON e.id_Usuario = u.id_Usuario WHERE u.id_Usuario = :id_Usuario
                  AND u.estado = TRUE 
                  AND e.estado = TRUE;";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->id_Usuario = (int) htmlspecialchars(strip_tags($this->id_Usuario));

        // Vincular parámetros
        $stmt->bindParam(":id_Usuario", $this->id_Usuario, PDO::PARAM_INT);

        // Ejecutar consulta
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar correo y clave
            if ($this->correo === $row['correo'] && $this->clave === $row['clave']) {
                $this->rol = $row['rol'];
                $this->correo = $row['correo'];
                return true;
            }
        }

        // No coincide o está inactivo
        return false;
    }
}

?>
