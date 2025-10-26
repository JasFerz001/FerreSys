<?php
// filepath: c:\xampp\htdocs\FerreSys\modelos\reportes\compra_proveedor.php
require_once '../../conexion/conexion.php';
require_once '../compras/detalle_compra.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$detalleCompra = new DetalleCompra($db);

$resultados = [];

// Consulta inicial para mostrar todas las compras
$sql = "SELECT dc.id_Detallecompra, pr.nombre AS producto, dc.cantidad, dc.precio_unitario, p.nombre AS proveedor
        FROM detalle_compra dc
        JOIN producto pr ON dc.id_Producto = pr.id_Producto
        JOIN compra AS c ON dc.id_Compra = c.id_Compra
        JOIN proveedores AS p ON c.id_Proveedor = p.id_Proveedor";

$stmt = $db->prepare($sql);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Compras por Proveedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/productos.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
        .table-responsive {
            overflow-x: auto;
        }

        th,
        td {
            text-align: center;
        }

        .badge {
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <div class="container-fluid px-3">
        <h2 class="text-center mb-4"><i class="fas fa-truck-loading mr-2"></i> Reporte de Compras por Proveedor</h2>
        <div class="row justify-content-center">
            <div class="col-md-8 mb-4">
                <label for="proveedor" class="form-label"><i class="fas fa-search mr-2"></i> Buscar Proveedor:</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-truck"></i></span>
                    <input type="text" id="proveedor" name="proveedor" class="form-control"
                        placeholder="Ingrese el nombre del proveedor">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tablaComprasProveedor" class="table table-bordered text-center align-middle">
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-card mr-2"></i> ID Detalle Compra</th>
                                <th><i class="fas fa-box mr-2"></i> Producto</th>
                                <th><i class="fas fa-sort-numeric-up mr-2"></i> Cantidad</th>
                                <th><i class="fas fa-dollar-sign mr-2"></i> Precio Unitario</th>
                                <th><i class="fas fa-truck mr-2"></i> Proveedor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán aquí mediante AJAX -->
                        </tbody>
                    </table>
                </div>
                <div id="mensajeSinDatos" class="text-center mt-3" style="display:none;">
                    No se encontraron datos para mostrar.
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function () {
            var tabla = $('#tablaComprasProveedor').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "searching": false,
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
                "ajax": {
                    "url": "obtener_compras.php", // Archivo PHP para obtener los datos
                    "type": "GET",
                    "data": function (d) {
                        d.proveedor = $('#proveedor').val(); // Envía el valor del proveedor
                    },
                    "error": function (xhr, error, thrown) {
                        console.log("Error en la petición AJAX:", error, thrown);
                        $('#tablaComprasProveedor').hide(); // Oculta la tabla
                        $('#mensajeSinDatos').show(); // Muestra el mensaje
                    }
                },
                "columns": [
                    { "data": "id_Detallecompra" },
                    { "data": "producto" },
                    { "data": "cantidad" },
                    { "data": "precio_unitario" },
                    { "data": "proveedor" }
                ],
                "dom": 'Bfrtip', // Habilita los botones
                "buttons": [
                    'pdf'
                ],
                "emptyTable": "No se encontraron datos" // Mensaje si la tabla está vacía
            });

            // Escucha los cambios en el campo de búsqueda
            $('#proveedor').on('keyup', function () {
                tabla.ajax.reload(); // Recarga los datos de la tabla
            });
        });
    </script>
</body>

</html>