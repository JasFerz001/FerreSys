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
    public int $id_Empleado;
    public string $nombre;
    public string $apellido;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function existeAlgunUsuario(): bool
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total'] > 0;
    }

    public function verificarCredenciales(): bool
    {
        // CONSULTA CORREGIDA: Sin comentarios dentro del SQL
        $query = "SELECT u.id_Usuario, u.rol, u.estado AS estado_usuario, 
                         e.id_Empleado, e.nombre, e.apellido, 
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

            // Verificar que la clave coincida
            if (password_verify($this->clave, $row['clave'])) {
                // GUARDAR TODOS LOS DATOS EN LAS PROPIEDADES
                $this->id_Usuario = (int) $row['id_Usuario'];
                $this->id_Empleado = (int) $row['id_Empleado'];
                $this->nombre = $row['nombre'];
                $this->apellido = $row['apellido'];
                $this->rol = $row['rol'];
                return true;
            }
        }

        return false;
    }
}
?>