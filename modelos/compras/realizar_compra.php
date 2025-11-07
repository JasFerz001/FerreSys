<?php
session_start();
date_default_timezone_set('America/El_Salvador');

//verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}

$id_empleado = $_SESSION['id_Empleado'] ?? '';
$nombre_empleado = $_SESSION['nombre'] ?? '';
$apellido_empleado = $_SESSION['apellido'] ?? '';
$nombre_completo_empleado = trim($nombre_empleado . ' ' . $apellido_empleado);

include_once '../../conexion/conexion.php';
include_once '../proveedores/proveedor.php';
include_once '../productos/productos.php';
include_once '../empleado/empleado.php';
include_once 'compra.php';
include_once 'detalle_compra.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

$proveedor = new Proveedor($db);
$productos = new Productos($db);
$empleado = new Empleado($db);
$compra = new Compra($db);
$detalleCompra = new DetalleCompra($db);

$proveedores = $proveedor->leerActivos();
$productosList = $productos->leerActivos();

// Convertir a array para usar en JS
$productosArray = [];
while ($row = $productosList->fetch(PDO::FETCH_ASSOC)) {
    $productosArray[] = [
        'id' => $row['id_Producto'],
        'nombre' => $row['nombre'],
        'unidad' => $row['medida_nombre'] . ' (' . $row['simbolo'] . ')'
    ];
}

// Variable para mensajes
$mensaje_script = '';

// Procesar la compra cuando se envía el formulario
if ($_POST && isset($_POST['procesar_compra'])) {
    try {
        // Validar que hay productos
        if (!isset($_POST['productos']) || empty($_POST['productos'])) {
            throw new Exception("Debe agregar al menos un producto a la compra");
        }

        // Validar proveedor
        if (empty($_POST['id_proveedor'])) {
            throw new Exception("Debe seleccionar un proveedor");
        }

        // Validar empleado
        if (empty($id_empleado)) {
            throw new Exception("No se encontró información del empleado");
        }

        // Preparar datos de la compra
        $compra->fecha = date('Y-m-d');
        $compra->id_Proveedor = $_POST['id_proveedor'];
        $compra->id_Empleado = $id_empleado;

        // Crear la compra
        if ($compra->crear()) {
            $id_compra_generada = $compra->id_Compra;

            // Procesar cada producto del detalle
            $productos_detalle = json_decode($_POST['productos'], true);

            foreach ($productos_detalle as $producto) {
                $detalleCompra->id_Compra = $id_compra_generada;
                $detalleCompra->id_Producto = $producto['id_producto'];
                $detalleCompra->cantidad = $producto['cantidad'];
                $detalleCompra->precio_unitario = $producto['precio'];
                $detalleCompra->existencia = $producto['cantidad']; // La existencia inicial es igual a la cantidad comprada

                if (!$detalleCompra->crear()) {
                    throw new Exception("Error al registrar el detalle de compra");
                }
            }


            include_once '../bitacora/Bitacora.php';
            $bitacora = new Bitacora($db);

            $bitacora->id_Empleado = $id_empleado;
            $bitacora->accion = "Registrar Compra";
            $bitacora->descripcion = "Se registró la compra con ID #$id_compra_generada con " . count($productos_detalle) . " productos.";
            $bitacora->registrar();


            // Éxito - preparar mensaje
            $mensaje_exito = "Compra registrada exitosamente.";
            $mensaje_script = "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: '¡Compra Registrada!',
                    text: '$mensaje_exito',
                    confirmButtonText: 'Aceptar',
                    timer: 5000,
                    timerProgressBar: true
                }).then(() => {
                    document.getElementById('tabla-detalle').getElementsByTagName('tbody')[0].innerHTML = '';
                    document.getElementById('proveedor').value = '';
                    calcularTotales();
                });
            });
            </script>";
        } else {
            throw new Exception("Error al registrar la compra");
        }
    } catch (Exception $e) {
        $mensaje_error = $e->getMessage();
        $mensaje_script = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '$mensaje_error',
                confirmButtonText: 'Aceptar'
            });
        });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Compra</title>
    <link rel="stylesheet" href="../../css/compra.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>

<body>
    <?php echo $mensaje_script; ?>

    <div class="container">
        <div class="card">
            <h2 class="card-title">Información de la Compra</h2>
            <form id="form-compra" method="POST">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="fecha">Fecha</label>
                            <!-- ✅ Fecha corregida: ahora muestra el día actual según la hora de El Salvador -->
                            <input type="text" id="fecha" value="<?php echo date('d-m-Y'); ?>" readonly
                                class="form-control">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="empleado">Empleado</label>
                            <input type="text" value="<?php echo htmlspecialchars($nombre_completo_empleado); ?>"
                                readonly class="form-control">
                            <input type="hidden" name="id_empleado" value="<?php echo $id_empleado; ?>">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="proveedor">Proveedor</label>
                            <select id="proveedor" name="id_proveedor" required>
                                <option value="">Seleccionar proveedor</option>
                                <?php
                                $proveedores = $proveedor->leerActivos();
                                while ($row = $proveedores->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row['id_Proveedor']}'>{$row['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="productos" id="productos-data">
                <input type="hidden" name="procesar_compra" value="1">
            </form>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2 class="card-title">Detalle de Compra</h2>
                <button class="btn btn-primary" id="btn-agregar-producto">
                    <span style="margin-right: 5px;">+</span> Agregar Producto
                </button>
            </div>

            <table id="tabla-detalle">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <div class="totals">
                <p>Subtotal: <span id="subtotal">$0.00</span></p>
                <p>IVA (13%): <span id="iva">$0.00</span></p>
                <p class="total-amount">Total: <span id="total">$0.00</span></p>
            </div>

            <div class="actions">
                <button class="btn btn-success" id="btn-procesar-compra">Procesar Compra</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-productos">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Seleccionar Productos</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <input type="text" id="buscar-producto" placeholder="Buscar producto...">
                </div>
                <table id="tabla-productos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Unidad de Medida</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function calcularTotales() {
            let subtotal = 0;
            const filas = document.getElementById('tabla-detalle').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < filas.length; i++) {
                const cantidad = parseFloat(filas[i].querySelector('.cantidad').value) || 0;
                const precio = parseFloat(filas[i].querySelector('.precio').value) || 0;
                const totalProducto = cantidad * precio;

                filas[i].querySelector('.total-producto').textContent = `$${totalProducto.toFixed(2)}`;
                subtotal += totalProducto;
            }

            const iva = subtotal * 0.13;
            const total = subtotal + iva;

            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('iva').textContent = `$${iva.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const productos = <?php echo json_encode($productosArray); ?>;
            const productosMap = {};
            productos.forEach(p => productosMap[p.nombre] = p);

            const modalProductos = document.getElementById('modal-productos');
            const btnAgregarProducto = document.getElementById('btn-agregar-producto');
            const btnProcesarCompra = document.getElementById('btn-procesar-compra');
            const closeModal = document.querySelector('.close-modal');
            const tablaProductos = document.getElementById('tabla-productos').getElementsByTagName('tbody')[0];
            const tablaDetalle = document.getElementById('tabla-detalle').getElementsByTagName('tbody')[0];
            const buscarProducto = document.getElementById('buscar-producto');
            const formCompra = document.getElementById('form-compra');
            const productosData = document.getElementById('productos-data');

            btnAgregarProducto.addEventListener('click', function () {
                modalProductos.style.display = 'flex';
                cargarProductos();
            });

            closeModal.addEventListener('click', function () {
                modalProductos.style.display = 'none';
            });

            window.addEventListener('click', function (event) {
                if (event.target === modalProductos) {
                    modalProductos.style.display = 'none';
                }
            });

            btnProcesarCompra.addEventListener('click', function () {
                const filas = tablaDetalle.getElementsByTagName('tr');
                if (filas.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'Productos requeridos', text: 'Debe agregar al menos un producto a la compra' });
                    return;
                }

                const proveedorSelect = document.getElementById('proveedor');
                if (!proveedorSelect.value) {
                    Swal.fire({ icon: 'warning', title: 'Proveedor requerido', text: 'Debe seleccionar un proveedor' });
                    return;
                }

                let preciosValidos = true;
                for (let i = 0; i < filas.length; i++) {
                    const precio = parseFloat(filas[i].querySelector('.precio').value);
                    if (precio <= 0) {
                        preciosValidos = false;
                        break;
                    }
                }

                if (!preciosValidos) {
                    Swal.fire({ icon: 'warning', title: 'Precios inválidos', text: 'Todos los productos deben tener un precio mayor a 0' });
                    return;
                }

                Swal.fire({
                    icon: 'question',
                    title: '¿Confirmar compra?',
                    html: `<div style="text-align: left;">
                            <p><strong>¿Está seguro de registrar la compra?</strong></p>
                            <p style="color: #e74c3c; font-size: 14px;">Al registrarla no podrá modificar ni eliminar el registro</p>
                            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <p style="margin: 5px 0;"><strong>Resumen:</strong></p>
                                <p style="margin: 5px 0;">Productos: ${filas.length}</p>
                                <p style="margin: 5px 0;">Total: ${document.getElementById('total').textContent}</p>
                            </div>
                        </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar compra',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const productosEnviar = [];
                        for (let i = 0; i < filas.length; i++) {
                            const nombreProducto = filas[i].cells[0].textContent;
                            const cantidad = parseFloat(filas[i].querySelector('.cantidad').value);
                            const precio = parseFloat(filas[i].querySelector('.precio').value);
                            const producto = productosMap[nombreProducto];

                            productosEnviar.push({
                                id_producto: producto.id,
                                nombre: nombreProducto,
                                cantidad: cantidad,
                                precio: precio
                            });
                        }

                        Swal.fire({
                            title: 'Procesando compra...',
                            text: 'Por favor espere',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        productosData.value = JSON.stringify(productosEnviar);
                        formCompra.submit();
                    }
                });
            });

            function cargarProductos(productosFiltrados = productos) {
                tablaProductos.innerHTML = '';
                productosFiltrados.forEach(producto => {
                    const fila = tablaProductos.insertRow();
                    fila.innerHTML = `
                        <td>${producto.nombre}</td>
                        <td>${producto.unidad}</td>
                        <td><button class="btn btn-primary agregar-producto" data-id="${producto.id}">Agregar</button></td>
                    `;
                });

                document.querySelectorAll('.agregar-producto').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        const producto = productos.find(p => p.id == id);
                        agregarProductoDetalle(producto);
                        modalProductos.style.display = 'none';

                        Swal.fire({
                            icon: 'success',
                            title: 'Producto agregado',
                            text: `${producto.nombre} ha sido agregado a la compra`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    });
                });
            }

            function agregarProductoDetalle(producto) {
                const filas = tablaDetalle.getElementsByTagName('tr');
                for (let i = 0; i < filas.length; i++) {
                    const nombreProducto = filas[i].cells[0].textContent;
                    if (nombreProducto === producto.nombre) {
                        const inputCantidad = filas[i].querySelector('.cantidad');
                        inputCantidad.value = parseInt(inputCantidad.value) + 1;
                        calcularTotales();
                        Swal.fire({
                            icon: 'info',
                            title: 'Cantidad actualizada',
                            text: `Se incrementó la cantidad de ${producto.nombre}`,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        return;
                    }
                }

                const fila = tablaDetalle.insertRow();
                fila.innerHTML = `
                    <td>${producto.nombre}</td>
                    <td><input type="number" min="1" value="1" class="cantidad" style="width: 70px;"></td>
                    <td><input type="number" min="0" step="0.01" value="0.00" class="precio" style="width: 100px;"></td>
                    <td class="total-producto">$0.00</td>
                    <td><button class="btn btn-danger eliminar-producto">Eliminar</button></td>
                `;

                const inputCantidad = fila.querySelector('.cantidad');
                const inputPrecio = fila.querySelector('.precio');

                inputCantidad.addEventListener('input', calcularTotales);
                inputPrecio.addEventListener('input', calcularTotales);

                fila.querySelector('.eliminar-producto').addEventListener('click', function () {
                    const nombreProducto = fila.cells[0].textContent;
                    Swal.fire({
                        icon: 'question',
                        title: 'Eliminar producto',
                        text: `¿Está seguro de eliminar ${nombreProducto} de la compra?`,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fila.remove();
                            calcularTotales();
                            Swal.fire({
                                icon: 'success',
                                title: 'Producto eliminado',
                                text: `${nombreProducto} ha sido eliminado de la compra`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                        }
                    });
                });

                calcularTotales();
            }

            buscarProducto.addEventListener('input', function () {
                const texto = this.value.toLowerCase();
                const productosFiltrados = productos.filter(p => p.nombre.toLowerCase().includes(texto));
                cargarProductos(productosFiltrados);
            });
        });
    </script>
</body>

</html>