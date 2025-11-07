<?php
session_start();
date_default_timezone_set('America/El_Salvador');
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}

// Incluir la conexión y clases
include_once '../../conexion/conexion.php';
include_once '../ventas/ventas.php';
include_once '../ventas/detalle_venta.php';
include_once '../cliente/cliente.php';
include_once '../empleado/empleado.php';
include_once '../categoria/categoria.php';
include_once '../unidad de medida/conversionunidad.php';
include_once '../bitacora/bitacora.php';

if (!isset($_SESSION['id_Empleado'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit;
}
$conexion = new Conexion();
$db = $conexion->getConnection();

// Inicializar objetos
$venta = new Ventas($db);
$detalleVenta = new Detalle_venta($db);
$cliente = new Cliente($db);
$empleado = new Empleado($db);
$categoria = new Categoria($db);
$conversionUnidad = new ConversionUnidad($db);
$bitacora = new Bitacora($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}

function handleGetRequest()
{
    global $cliente, $empleado, $categoria, $detalleVenta;

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'getClientes':
            getClientes();
            break;
        case 'getCategorias':
            getCategorias();
            break;
        case 'getProductos':
            getProductos();
            break;
        case 'getEmpleadoActual':
            getEmpleadoActual();
            break;
        case 'getUnidadesVenta':
            getUnidadesVenta();
            break;
        case 'verificarExistenciaConversion':
            verificarExistenciaConversion();
            break;
            case 'getConversionEspecifica':
    getConversionEspecifica();
    break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
}

function handlePostRequest()
{
    global $venta, $detalleVenta;

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'crearVenta':
            crearVenta();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
}


function getClientes()
{
    global $cliente;

    try {
        $stmt = $cliente->leer();
        $clientes = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clientes[] = [
                'id_Cliente' => $row['id_Cliente'],
                'nombre' => $row['nombre'] . ' ' . $row['apellido'],
                'dui' => $row['dui']
            ];
        }

        echo json_encode(['success' => true, 'clientes' => $clientes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener clientes: ' . $e->getMessage()]);
    }
}

function getCategorias()
{
    global $categoria;

    try {
        $stmt = $categoria->leer();
        $categorias = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categorias[] = [
                'id_Categoria' => $row['id_Categoria'],
                'nombre' => $row['nombre']
            ];
        }

        echo json_encode(['success' => true, 'categorias' => $categorias]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener categorías: ' . $e->getMessage()]);
    }
}

function getProductos()
{
    global $detalleVenta;

    $idCategoria = $_GET['idCategoria'] ?? '';

    try {
        $stmt = $detalleVenta->obtenerProductosConExistencia($idCategoria);
        $productos = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Convertir imagen BLOB a base64 si existe
            $imagenBase64 = null;
            if (!empty($row['imagen'])) {
                $imagenBase64 = 'data:image/png;base64,' . base64_encode($row['imagen']);
            }

            $productos[] = [
                'id_Detallecompra' => $row['id_Detallecompra'],
                'id_Producto' => $row['id_Producto'],
                'imagen' => "../../img/productos/" . $row['imagen'],
                'nombre_producto' => $row['nombre_producto'],
                'descripcion' => $row['descripcion'],
                'categoria' => $row['categoria'],
                'unidad_medida' => $row['unidad_medida'],
                'simbolo' => $row['simbolo'],
                'existencia' => $row['existencia'],
                'precio_unitario' => $row['precio_unitario'],
                'proveedor' => $row['proveedor']
            ];
        }

        echo json_encode(['success' => true, 'productos' => $productos]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener productos: ' . $e->getMessage()]);
    }
}


function getEmpleadoActual()
{
    global $empleado;

    try {
        $empleado->id_Empleado = $_SESSION['id_Empleado'];
        if ($empleado->leerPorId()) {
            echo json_encode([
                'success' => true,
                'empleado' => [
                    'id_Empleado' => $empleado->id_Empleado,
                    'nombre' => $empleado->nombre . ' ' . $empleado->apellido
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener empleado: ' . $e->getMessage()]);
    }
}

function getUnidadesVenta()
{
    global $detalleVenta;

    $id_Producto = $_GET['id_Producto'] ?? '';

    try {
        if (!$id_Producto) {
            echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado']);
            return;
        }

        $unidades = $detalleVenta->obtenerUnidadesVenta($id_Producto);
        echo json_encode(['success' => true, 'unidades' => $unidades]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener unidades: ' . $e->getMessage()]);
    }
}

function getConversionEspecifica() {
    global $conversionUnidad;
    
    $id_Producto = $_GET['id_Producto'] ?? '';
    $id_Unidad_Venta = $_GET['id_Unidad_Venta'] ?? '';
    
    try {
        if (!$id_Producto || !$id_Unidad_Venta) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            return;
        }
        
        $conversion = $conversionUnidad->obtenerConversionPorProductoYUnidad($id_Producto, $id_Unidad_Venta);
        
        if ($conversion) {
            echo json_encode(['success' => true, 'conversion' => $conversion]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Conversión no encontrada']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener conversión: ' . $e->getMessage()]);
    }
}

function verificarExistenciaConversion()
{
    global $conversionUnidad;

    $id_Detallecompra = $_GET['id_Detallecompra'] ?? '';
    $cantidad = $_GET['cantidad'] ?? 0;
    $factor_conversion = $_GET['factor_conversion'] ?? 1;

    try {
        if (!$id_Detallecompra) {
            echo json_encode(['success' => false, 'message' => 'ID de detalle no proporcionado']);
            return;
        }

        $resultado = $conversionUnidad->verificarExistencia($id_Detallecompra, $cantidad, $factor_conversion);
        echo json_encode(['success' => true, 'resultado' => $resultado]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al verificar existencia: ' . $e->getMessage()]);
    }
}
function crearVenta() {
    global $venta, $detalleVenta, $db, $conversionUnidad, $bitacora;
    
    try {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Obtener datos de la venta
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $id_Cliente = $_POST['id_Cliente'] ?? null;
        $id_Empleado = $_POST['id_Empleado'] ?? $_SESSION['id_Empleado'];
        $detalles = json_decode($_POST['detalles'], true) ?? [];
        
        // Validaciones básicas
        if (!$id_Cliente) {
            throw new Exception('Debe seleccionar un cliente');
        }
        
        if (empty($detalles)) {
            throw new Exception('Debe agregar al menos un producto a la venta');
        }
        
        // Crear la venta
        $venta->fecha = $fecha;
        $venta->id_Cliente = $id_Cliente;
        $venta->id_Empleado = $id_Empleado;
        
        if (!$venta->crear()) {
            throw new Exception('Error al crear la venta');
        }
        
        // Obtener el ID de la venta recién creada
        $id_Venta = $venta->obtenerUltimaVenta();
        
        if (!$id_Venta) {
            throw new Exception('Error al obtener el ID de la venta');
        }
        
        $totalVenta = 0;
        $detallesVenta = [];
        
        // Procesar detalles de la venta CON CONVERSIONES
        foreach ($detalles as $detalle) {
            $id_Detallecompra = $detalle['id_Detallecompra'] ?? null;
            $precio_venta = $detalle['precio_venta'] ?? null;
            $cantidad = $detalle['cantidad'] ?? null;
            $factor_conversion = $detalle['factor_conversion'] ?? 1;
            $usar_conversion = $detalle['usar_conversion'] ?? false;
            $unidad_venta_frontend = $detalle['unidad_venta'] ?? null;
            
            if (!$id_Detallecompra || !$precio_venta || !$cantidad) {
                throw new Exception('Datos incompletos en los detalles de la venta');
            }
            
            // Calcular cantidad a restar (considerando conversión)
            if ($usar_conversion) {
                $cantidad_a_restar = $cantidad / $factor_conversion;
            } else {
                $cantidad_a_restar = $cantidad;
            }
            
            // Verificar existencia (considerando conversión)
            $query = "SELECT existencia FROM detalle_compra WHERE id_Detallecompra = :id_Detallecompra";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id_Detallecompra', $id_Detallecompra);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            if ($producto['existencia'] < $cantidad_a_restar) {
                throw new Exception('No hay suficiente existencia para el producto');
            }
            
            // Calcular total
            $total = $precio_venta * $cantidad;
            $totalVenta += $total;
            
            // Obtener información completa del producto incluyendo unidad de medida base
            $queryProducto = "SELECT 
                p.nombre,
                p.id_Producto,
                um.nombre as unidad_base,
                um.simbolo as simbolo_base,
                pr.nombre as proveedor
                FROM detalle_compra dc
                INNER JOIN producto p ON dc.id_Producto = p.id_Producto
                INNER JOIN unidad_medida um ON p.id_Medida = um.id_Medida
                INNER JOIN compra c ON dc.id_Compra = c.id_Compra
                INNER JOIN proveedores pr ON c.id_Proveedor = pr.id_Proveedor
                WHERE dc.id_Detallecompra = :id_Detallecompra";
            $stmtProducto = $db->prepare($queryProducto);
            $stmtProducto->bindParam(':id_Detallecompra', $id_Detallecompra);
            $stmtProducto->execute();
            $infoProducto = $stmtProducto->fetch(PDO::FETCH_ASSOC);
            
            if (!$infoProducto) {
                throw new Exception('No se pudo obtener la información del producto');
            }
            
            // 🔹 NUEVO: Determinar la unidad y símbolo para el ticket
            if ($usar_conversion && $unidad_venta_frontend) {
                // Buscar la unidad de venta en conversion_unidades para obtener el símbolo correcto
                $queryUnidadVenta = "SELECT 
                    umv.nombre as unidad_venta,
                    umv.simbolo as simbolo_venta
                    FROM conversion_unidades cu
                    INNER JOIN unidad_medida umv ON cu.id_Unidad_Venta = umv.id_Medida
                    WHERE cu.id_Producto = :id_Producto 
                    AND umv.nombre = :unidad_venta
                    AND cu.es_activo = 1
                    LIMIT 1";
                $stmtUnidadVenta = $db->prepare($queryUnidadVenta);
                $stmtUnidadVenta->bindParam(':id_Producto', $infoProducto['id_Producto']);
                $stmtUnidadVenta->bindParam(':unidad_venta', $unidad_venta_frontend);
                $stmtUnidadVenta->execute();
                $unidadVentaInfo = $stmtUnidadVenta->fetch(PDO::FETCH_ASSOC);
                
                $unidad_venta_ticket = $unidadVentaInfo['unidad_venta'] ?? $unidad_venta_frontend;
                $simbolo_venta = $unidadVentaInfo['simbolo_venta'] ?? substr($unidad_venta_frontend, 0, 2);
            } else {
                // Usar unidad base
                $unidad_venta_ticket = $infoProducto['unidad_base'];
                $simbolo_venta = $infoProducto['simbolo_base'];
            }
            
            // Guardar información para el ticket
            $detallesVenta[] = [
                'nombre_producto' => $infoProducto['nombre'],
                'cantidad' => $cantidad,
                'unidad_venta' => $unidad_venta_ticket,
                'simbolo_venta' => $simbolo_venta, // ← NUEVO: símbolo desde la BD
                'precio_venta' => $precio_venta,
                'total' => $total,
                'proveedor' => $infoProducto['proveedor']
            ];
            
            // Crear detalle de venta en la tabla detalle_venta
            $detalleVenta->id_Detallecompra = $id_Detallecompra;
            $detalleVenta->id_Venta = $id_Venta;
            $detalleVenta->precio_venta = $precio_venta;
            $detalleVenta->cantidad = $cantidad;
            $detalleVenta->total = $total;
            
            if (!$detalleVenta->crear()) {
                throw new Exception('Error al crear el detalle de venta');
            }
            
            // Actualizar existencia en detalle_compra
            $query = "UPDATE detalle_compra 
                     SET existencia = existencia - :cantidad 
                     WHERE id_Detallecompra = :id_Detallecompra";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad_a_restar);
            $stmt->bindParam(':id_Detallecompra', $id_Detallecompra);
            
            if (!$stmt->execute()) {
                throw new Exception('Error al actualizar la existencia');
            }
        }
        
        // Confirmar transacción
        $db->commit();
        
        // Registrar en la bitácora
        $bitacora->id_Empleado = $_SESSION['id_Empleado'];
        $bitacora->accion = "Registro de venta";
        $bitacora->descripcion = "Se realizó una nueva venta con ID #$id_Venta al cliente ID #$id_Cliente.";
        $bitacora->registrar();
        
        // Obtener información del cliente para el ticket
        $queryCliente = "SELECT nombre, apellido FROM clientes WHERE id_Cliente = :id_Cliente";
        $stmtCliente = $db->prepare($queryCliente);
        $stmtCliente->bindParam(':id_Cliente', $id_Cliente);
        $stmtCliente->execute();
        $infoCliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);
        
        // Obtener información del empleado para el ticket
        $queryEmpleado = "SELECT nombre, apellido FROM empleados WHERE id_Empleado = :id_Empleado";
        $stmtEmpleado = $db->prepare($queryEmpleado);
        $stmtEmpleado->bindParam(':id_Empleado', $id_Empleado);
        $stmtEmpleado->execute();
        $infoEmpleado = $stmtEmpleado->fetch(PDO::FETCH_ASSOC);
        
        // Devolver datos completos para el ticket
        echo json_encode([
            'success' => true, 
            'message' => 'Venta realizada exitosamente',
            'venta' => [
                'id_Venta' => $id_Venta,
                'total' => $totalVenta,
                'fecha' => $fecha,
                'cliente' => [
                    'nombre' => $infoCliente['nombre'] . ' ' . $infoCliente['apellido']
                ],
                'empleado' => [
                    'nombre' => $infoEmpleado['nombre'] . ' ' . $infoEmpleado['apellido']
                ],
                'detalles' => $detallesVenta
            ]
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>