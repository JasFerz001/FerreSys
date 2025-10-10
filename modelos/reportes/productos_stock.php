<?php
include_once '../../conexion/conexion.php';
include_once '../compras/detalle_compra.php';
include_once '../categoria/categoria.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

$detalleCompra = new DetalleCompra($db);
$categorias = new Categoria($db);
$categoriasList = $categorias->leer();

// Si llega una petición AJAX (sin recargar)
if (isset($_POST['id_categoria'])) {
    header('Content-Type: application/json; charset=utf-8');
    $productos = $detalleCompra->obtenerProductosPorCategoria($_POST['id_categoria']);
    echo json_encode($productos);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .product-image {
            width: 50px;
            height: 50px;
            cursor: pointer;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.3rem;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="container-fluid px-3 d-flex flex-column align-items-center">

        <!-- Selección de Categoría -->
        <div class="card mb-3 w-100" style="max-width: 1200px;">
            <div class="card-body">
                <h2 class="card-title">Información de Inventario</h2>

                <div class="row align-items-end">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="categoria">Lista de Categorías</label>
                            <select id="categoria" name="id_categoria" class="form-select" required>
                                <option value="">Seleccionar la categoría</option>
                                <?php
                                while ($row = $categoriasList->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='{$row['id_Categoria']}'>{$row['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <!-- Botón en la misma línea, alineado a la derecha -->
                    <div class="col-md-6 text-end">
                        <button class="btn btn-primary" type="submit" id="btnCompra">
                            Realizar Compra
                        </button>
                    </div>
                </div>
            </div>
        </div>



        <!-- Tabla de Productos -->
        <div class="card w-100" style="max-width: 1200px;">
            <div class="card-body">
                <div class="card-title">Lista de Productos</div>
                <div class="table-responsive">
                    <table id="tablaEmpleados" class="table table-bordered text-center align-middle">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Unidad de Medida</th>
                                <th>Proveedor</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llenará con AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function () {
            // Inicializar DataTable
            let tabla = $('#tablaEmpleados').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
                "ordering": false
            });

            // Evento de cambio en categoría
            $('#categoria').on('change', function () {
                let id_categoria = $(this).val();
                if (id_categoria) {
                    $.ajax({
                        url: '', // mismo archivo
                        type: 'POST',
                        data: { id_categoria: id_categoria },
                        dataType: 'json',
                        success: function (data) {
                            tabla.clear().draw(); // limpiar tabla

                            if (data.length === 0) {
                                Swal.fire('No hay productos en esta categoría');
                                return;
                            }

                            data.forEach(function (producto) {
                                let imagenHtml = producto.Imagen
                                    ? `<img src='../../img/productos/${producto.Imagen}' class='product-image' onclick="mostrarImagen('../../img/productos/${producto.Imagen}')">`
                                    : `<i class='bi bi-image text-muted no-image' style='font-size: 1.5rem;' onclick='mostrarSinImagen()'></i>`;

                                tabla.row.add([
                                    imagenHtml,
                                    producto.Producto,
                                    producto.Categoria,
                                    producto.UnidadMedida,
                                    producto.Proveedor,
                                    producto.Stock
                                ]).draw(false);
                            });
                        },
                        error: function (xhr, status, error) {
                            console.error(error);
                        }
                    });
                } else {
                    tabla.clear().draw();
                }
            });
        });

        function mostrarImagen(url) {
            Swal.fire({
                imageUrl: url,
                imageAlt: 'Imagen del producto',
                showCloseButton: true
            });
        }

        function mostrarSinImagen() {
            Swal.fire('No hay imagen disponible para este producto');
        }
    </script>
</body>

</html>