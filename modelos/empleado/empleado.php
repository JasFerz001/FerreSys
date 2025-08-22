<?php
class Empleado {
     private $conn;
    private $table_name = "usuario";
    private int $id_Empleado;
    private string $nombre;
    private string $apellido;
    private string $DUI;
    private string $telefono;
    private string $direccion;
    private string $correo;
    private string $clave;
    private int $estado;
    private int $id_Usuario;

    
     public function __construct($db)
    {
        $this->conn = $db;
    }

}
?>