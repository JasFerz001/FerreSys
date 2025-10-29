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
    global $venta, $detalleVenta, $db, $conversionUnidad;
    
    try {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Obtener datos de la venta (los mismos de antes)
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $id_Cliente = $_POST['id_Cliente'] ?? null;
        $id_Empleado = $_POST['id_Empleado'] ?? $_SESSION['id_Empleado'];
        $detalles = json_decode($_POST['detalles'], true) ?? [];
        
        // Validaciones básicas (las mismas de antes)
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
        
        // Procesar detalles de la venta CON CONVERSIONES
        foreach ($detalles as $detalle) {
            $id_Detallecompra = $detalle['id_Detallecompra'] ?? null;
            $precio_venta = $detalle['precio_venta'] ?? null;
            $cantidad = $detalle['cantidad'] ?? null;
            $factor_conversion = $detalle['factor_conversion'] ?? 1; // Nuevo: factor de conversión
            $usar_conversion = $detalle['usar_conversion'] ?? false; // Nuevo: si usa conversión
            
            if (!$id_Detallecompra || !$precio_venta || !$cantidad) {
                throw new Exception('Datos incompletos en los detalles de la venta');
            }
            
            // Calcular cantidad a restar (considerando conversión)
            if ($usar_conversion) {
        // Si estamos vendiendo en unidades derivadas (sacos, cubetadas)
        // y factor_conversion = 20 (1 metro = 20 sacos)
        // entonces: cantidad_a_restar = cantidad_vendida / factor_conversion
        $cantidad_a_restar = $cantidad / $factor_conversion;
    } else {
        // Si estamos vendiendo en la unidad base
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
            
            // Crear detalle de venta (guardamos también la unidad de venta y factor si aplica)
            $detalleVenta->id_Detallecompra = $id_Detallecompra;
            $detalleVenta->id_Venta = $id_Venta;
            $detalleVenta->precio_venta = $precio_venta;
            $detalleVenta->cantidad = $cantidad;
            $detalleVenta->total = $total;
            
            // Agregar campos para conversión si es necesario
            if ($usar_conversion) {
                // Podemos agregar campos a la tabla detalle_venta o manejarlo de otra forma
                // Por simplicidad, asumiremos que la cantidad ya está en la unidad correcta
            }
            
            if (!$detalleVenta->crear()) {
                throw new Exception('Error al crear el detalle de venta');
            }
            
            // Actualizar existencia (restando la cantidad convertida a unidad base)
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
        
        echo json_encode([
            'success' => true, 
            'message' => 'Venta realizada exitosamente',
            'id_Venta' => $id_Venta
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>