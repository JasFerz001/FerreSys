<?php
class ConversionUnidad
{
    private $conn;
    private $table_name = "conversion_unidades";

    public $id_Conversion;
    public $id_Producto;
    public $id_Unidad_Base;
    public $id_Unidad_Venta;
    public $factor_conversion;
    public $es_activo;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Crear conversión
    public function crear(): bool
    {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET id_Producto=:id_Producto, id_Unidad_Base=:id_Unidad_Base, 
                     id_Unidad_Venta=:id_Unidad_Venta, factor_conversion=:factor_conversion,
                     es_activo=:es_activo";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_Producto", $this->id_Producto);
        $stmt->bindParam(":id_Unidad_Base", $this->id_Unidad_Base);
        $stmt->bindParam(":id_Unidad_Venta", $this->id_Unidad_Venta);
        $stmt->bindParam(":factor_conversion", $this->factor_conversion);
        $stmt->bindParam(":es_activo", $this->es_activo);

        return $stmt->execute();
    }

    public function actualizar(): bool
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET factor_conversion=:factor_conversion, es_activo=:es_activo 
                  WHERE id_Conversion=:id_Conversion";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":factor_conversion", $this->factor_conversion);
        $stmt->bindParam(":es_activo", $this->es_activo);
        $stmt->bindParam(":id_Conversion", $this->id_Conversion);

        return $stmt->execute();
    }
    // Obtener conversiones por producto
    public function obtenerConversionesPorProducto($id_Producto): array
    {
        $query = "SELECT cu.*, um_base.nombre as unidad_base, um_venta.nombre as unidad_venta, 
                         um_venta.simbolo as simbolo_venta
                  FROM " . $this->table_name . " cu
                  INNER JOIN unidad_medida um_base ON cu.id_Unidad_Base = um_base.id_Medida
                  INNER JOIN unidad_medida um_venta ON cu.id_Unidad_Venta = um_venta.id_Medida
                  WHERE cu.id_Producto = :id_Producto AND cu.es_activo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Producto", $id_Producto);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener conversión específica
    public function obtenerConversion($id_Producto, $id_Unidad_Venta)
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_Producto = :id_Producto 
                  AND id_Unidad_Venta = :id_Unidad_Venta 
                  AND es_activo = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Producto", $id_Producto);
        $stmt->bindParam(":id_Unidad_Venta", $id_Unidad_Venta);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Convertir cantidad a unidad base
    // CORRECCIÓN: Convertir cantidad a unidad base (DE venta A base)
    public function convertirABase($cantidad_venta, $factor_conversion): float
    {
        // Si 20 sacos = 1 metro cúbico, entonces:
        // cantidad_base = cantidad_venta / factor_conversion
        return $cantidad_venta / $factor_conversion;
    }

    // CORRECCIÓN: Convertir de unidad base a unidad de venta (DE base A venta)
    public function convertirAVenta($cantidad_base, $factor_conversion): float
    {
        // Si 1 metro cúbico = 20 sacos, entonces:
        // cantidad_venta = cantidad_base * factor_conversion
        return $cantidad_base * $factor_conversion;
    }

    // CORRECCIÓN en la verificación de existencia
    public function verificarExistencia($id_Detallecompra, $cantidad_vender, $factor_conversion): array
    {
        $query = "SELECT existencia FROM detalle_compra WHERE id_Detallecompra = :id_Detallecompra";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_Detallecompra", $id_Detallecompra);
        $stmt->execute();

        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$detalle) {
            return ['suficiente' => false, 'mensaje' => 'Producto no encontrado'];
        }

        $existencia_base = $detalle['existencia'];

        // CORRECCIÓN: Convertir la cantidad que se quiere vender a unidad base
        $cantidad_necesaria_base = $this->convertirABase($cantidad_vender, $factor_conversion);

        if ($existencia_base >= $cantidad_necesaria_base) {
            return [
                'suficiente' => true,
                'existencia_base' => $existencia_base,
                'cantidad_necesaria_base' => $cantidad_necesaria_base,
                'existencia_en_venta' => $this->convertirAVenta($existencia_base, $factor_conversion)
            ];
        } else {
            $maximo_vender = $this->convertirAVenta($existencia_base, $factor_conversion);
            return [
                'suficiente' => false,
                'mensaje' => "Existencia insuficiente. Máximo disponible: " . round($maximo_vender, 2) . " unidades",
                'maximo_vender' => $maximo_vender,
                'existencia_base' => $existencia_base
            ];
        }
    }

    // En la clase ConversionUnidad, agrega este método
public function obtenerConversionPorProductoYUnidad($id_Producto, $id_Unidad_Venta) {
    $query = "SELECT cu.*, um_base.nombre as unidad_base, um_venta.nombre as unidad_venta,
                     um_base.simbolo as simbolo_base, um_venta.simbolo as simbolo_venta
              FROM conversion_unidades cu
              INNER JOIN unidad_medida um_base ON cu.id_Unidad_Base = um_base.id_Medida
              INNER JOIN unidad_medida um_venta ON cu.id_Unidad_Venta = um_venta.id_Medida
              WHERE cu.id_Producto = :id_Producto 
              AND cu.id_Unidad_Venta = :id_Unidad_Venta 
              AND cu.es_activo = 1";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id_Producto", $id_Producto);
    $stmt->bindParam(":id_Unidad_Venta", $id_Unidad_Venta);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}
?>