<?php
//verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}
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
    <title>Inventario | Ferretería Michapa Cuscatlán</title>

    <!-- Bootstrap + DataTables + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #2C3E50;
            --primary-dark: #1a252f;
            --primary-light: #34495e;
            --secondary: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
        }

        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            min-height: 100vh;
        }

        .header-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-top: 25px;
            border-left: 5px solid var(--primary);
        }

        .company-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(44, 62, 80, 0.3);
        }

        .company-info h1 {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-dark);
            margin: 0;
        }

        .company-info p {
            color: var(--gray);
            margin: 0;
            font-size: 0.9rem;
        }

        .report-title {
            text-align: center;
            margin: 25px 0;
        }

        .report-title h2 {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .report-title p {
            color: var(--gray);
            margin: 0;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            margin-top: 25px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(44, 62, 80, 0.4);
            filter: brightness(0.9);
            color: white !important;
        }

        .form-select {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        table.dataTable {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            width: 100% !important;
        }

        table.dataTable thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        table.dataTable thead th {
            border: none;
            padding: 15px 10px;
            font-weight: 500;
            text-align: center;
        }

        table.dataTable tbody tr {
            transition: all 0.2s ease;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #f8fafc;
        }

        table.dataTable tbody tr:hover {
            background-color: #e0f2fe;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table.dataTable tbody td {
            padding: 12px 10px;
            border-color: #e2e8f0;
            text-align: center;
            vertical-align: middle;
        }

        .product-image {
            width: 50px;
            height: 50px;
            cursor: pointer;
            border-radius: 8px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-image:hover {
            transform: scale(1.1);
        }

        .no-image {
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
        }

        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .stock-high {
            background-color: #d1fae5;
            color: #065f46;
        }

        .stock-medium {
            background-color: #fef3c7;
            color: #92400e;
        }

        .stock-low {
            background-color: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .company-logo {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid px-4">
        <!-- Encabezado -->
        <div class="header-container">
            <div class="company-info">
                <h1>FerreSys</h1>
                <p>Ferretería Michapa Cuscatlán</p>
            </div>

            <div class="report-title">
                <h2><i class="fas fa-clipboard-list me-2"></i>Información de Inventario</h2>
            </div>
        </div>

        <!-- Selección de Categoría -->
        <div class="content-card">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="categoria" class="form-label">Seleccionar Categoría</label>
                        <select id="categoria" name="id_categoria" class="form-select" required>
                            <option value="">Seleccione una categoría</option>
                            <?php
                            while ($row = $categoriasList->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$row['id_Categoria']}'>{$row['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4 text-end">
                    <button class="btn btn-primary" type="submit" id="btnCompra">
                        <i class="fas fa-shopping-cart me-2"></i>Realizar Compra
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0" style="color: var(--primary-dark);">
                    <i class="fas fa-list-alt me-2"></i>Lista de Productos
                </h3>
            </div>

            <div class="table-responsive">
                <table id="tablaEmpleados" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            let tabla = $('#tablaEmpleados').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 5,
                "lengthMenu": [5, 10, 15, 25],
                "ordering": false,
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>'
            });

            // Evento de cambio en categoría
            $('#categoria').on('change', function() {
                let id_categoria = $(this).val();
                if (id_categoria) {
                    $.ajax({
                        url: '', // mismo archivo
                        type: 'POST',
                        data: {
                            id_categoria: id_categoria
                        },
                        dataType: 'json',
                        success: function(data) {
                            tabla.clear().draw(); // limpiar tabla

                            if (data.length === 0) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Sin productos',
                                    text: 'No hay productos en esta categoría',
                                    confirmButtonColor: '#2C3E50'
                                });
                                return;
                            }

                            data.forEach(function(producto) {
                                let imagenHtml = producto.Imagen ?
                                    `<img src='../../img/productos/${producto.Imagen}' class='product-image' onclick="mostrarImagen('../../img/productos/${producto.Imagen}')">` :
                                    `<i class='fas fa-image text-muted no-image' onclick='mostrarSinImagen()'></i>`;

                                // Determinar clase del stock
                                let stockClass = 'stock-high';
                                let stockValue = parseInt(producto.Stock);

                                if (stockValue <= 10) {
                                    stockClass = 'stock-low';
                                } else if (stockValue <= 25) {
                                    stockClass = 'stock-medium';
                                }

                                let stockHtml = `<span class="stock-badge ${stockClass}">${producto.Stock}</span>`;

                                tabla.row.add([
                                    imagenHtml,
                                    producto.Producto,
                                    producto.UnidadMedida,
                                    producto.Proveedor,
                                    stockHtml
                                ]).draw(false);
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al cargar los productos',
                                confirmButtonColor: '#2C3E50'
                            });
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
                imageHeight: 300,
                showCloseButton: true,
                confirmButtonColor: '#2C3E50',
                background: 'white',
                customClass: {
                    popup: 'rounded-3'
                }
            });
        }

        function mostrarSinImagen() {
            Swal.fire({
                icon: 'info',
                title: 'Sin imagen',
                text: 'No hay imagen disponible para este producto',
                confirmButtonColor: '#2C3E50'
            });
        }

        document.getElementById('btnCompra').addEventListener('click', function() {
            window.location.href = '../compras/realizar_compra.php';
        });
    </script>
</body>

</html>