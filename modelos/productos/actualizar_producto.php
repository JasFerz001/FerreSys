<?php
include_once '../../conexion/conexion.php';
include_once '../categoria/categoria.php';
include_once '../unidad de medida/unidadMedida.php';
include_once '../productos/productos.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

$producto = new productos($db);
$categorias = new categoria($db);
$medidas = new unidadMedida($db);

$message = '';

function formatearTexto($texto)
{
    $texto = strtolower(trim($texto));
    return ucwords($texto);
}

// Obtener el ID del producto a editar
$id_Producto = isset($_GET['id']) ? $_GET['id'] : '';

// Si no hay ID, redirigir a la página principal
if (empty($id_Producto)) {
    header("Location: crear_producto.php");
    exit();
}

// Cargar los datos del producto
$producto->id_Producto = $id_Producto;
$productoEncontrado = $producto->leerPorId();

// Si no se encuentra el producto, redirigir
if (!$productoEncontrado) {
    header("Location: crear_producto.php");
    exit();
}

// Asignar valores para mostrar en el formulario
$nombre = $producto->nombre;
$descripcion = $producto->descripcion;
$imagen = $producto->imagen;
$id_Categoria = $producto->id_Categoria;
$id_Medida = $producto->id_Medida;
$estado = $producto->estado;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = formatearTexto(trim($_POST['nombre']));
    $descripcion = formatearTexto(trim($_POST['descripcion']));
    $id_Categoria = $_POST['id_Categoria'];
    $id_Medida = $_POST['id_Medida'];
    $estado = $_POST['estado'];

    $producto->id_Producto = $id_Producto;
    $producto->nombre = $nombre;
    $producto->descripcion = $descripcion;
    $producto->id_Categoria = $id_Categoria;
    $producto->id_Medida = $id_Medida;
    $producto->estado = $estado;

    // Procesar imagen si se subió una nueva
    if (!empty($_FILES['imagen']['name'])) {
        $imagen_nombre = $_FILES['imagen']['name'];
        $imagen_temporal = $_FILES['imagen']['tmp_name'];
        $directorio_imagenes = "../../img/productos/";

        // Crear directorio si no existe
        if (!is_dir($directorio_imagenes)) {
            mkdir($directorio_imagenes, 0777, true);
        }

        // Mover la nueva imagen al directorio
        $ruta_imagen = $directorio_imagenes . $imagen_nombre;
        if (move_uploaded_file($imagen_temporal, $ruta_imagen)) {
            $producto->imagen = $imagen_nombre;

            // Eliminar imagen anterior si existe y es diferente a la nueva
            if (!empty($imagen) && $imagen != $imagen_nombre && file_exists($directorio_imagenes . $imagen)) {
                unlink($directorio_imagenes . $imagen);
            }
        }
    } else {
        // Mantener la imagen actual si no se subió una nueva
        $producto->imagen = $imagen;
    }

    // Actualizar el Producto
    $result = $producto->actualizar();

    if ($result['success']) {
        $message = 'success';
        // Actualizar la variable de imagen si se cambió
        if (!empty($_FILES['imagen']['name'])) {
            $imagen = $producto->imagen;
        }
    } else {
        $message = 'error';
    }
}

// Leer todas las categorías y medidas para los selects
$categoriasList = $categorias->leer();
$medidasList = $medidas->leer();

// Leer todos los productos para mostrarlos en la tabla
$productosList = $producto->leer();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualización de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/productos.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .modal-imagen {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s;
        }

        .modal-contenido {
            position: relative;
            margin: auto;
            display: block;
            width: auto;
            /* Cambiado de 80% a auto */
            max-width: none;
            /* Eliminado el max-width */
            margin-top: 5%;
            animation: zoomIn 0.3s;
            text-align: center;
            /* Centrar la imagen */
        }

        .modal-imagen img {
            width: 300px !important;
            /* Tamaño fijo 90px */
            height: 300px !important;
            /* Tamaño fijo 90px */
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            object-fit: cover;
            /* Mantener proporción */
        }

        .cerrar-modal {
            position: absolute;
            top: -50px;
            /* Ajustado para la nueva posición */
            right: 50%;
            /* Centrado horizontalmente */
            transform: translateX(50%);
            /* Centrado preciso */
            color: #fff;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cerrar-modal:hover {
            color: #bbb;
            transform: translateX(50%) scale(1.1);
            /* Mantener centrado al hacer hover */
        }

        /* IMÁGENES EN LA TABLA - TAMAÑO PEQUEÑO */
        .product-image {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 4px;
            width: 60px !important;
            height: 60px !important;
            object-fit: cover !important;
        }

        .product-image:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .no-image {
            cursor: pointer;
            transition: color 0.3s ease;
            font-size: 2rem !important;
        }

        .no-image:hover {
            color: #6c757d !important;
        }

        /* PREVIEW EN FORMULARIO */
        #imagenPreview img {
            width: 80px !important;
            height: 80px !important;
            object-fit: cover !important;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-contenido {
                margin-top: 20%;
            }

            .cerrar-modal {
                top: -40px;
                font-size: 25px;
                width: 40px;
                height: 40px;
            }

            .product-image {
                width: 50px !important;
                height: 50px !important;
            }
        }
    </style>
</head>

<body>
    <!-- Modal para imagen ampliada -->
    <div id="modalImagen" class="modal-imagen">
        <span class="cerrar-modal">&times;</span>
        <div class="modal-contenido">
            <img id="imagenAmpliada" src="" alt="Imagen ampliada del producto">
        </div>
    </div>
    <div class="container-fluid px-3">
        <div class="row form-table-container d-flex">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-form h-100">
                    <div class="card-title">Actualización de Productos</div>

                    <form id="productoForm" method="post"
                        action="actualizar_producto.php?id=<?php echo $id_Producto; ?>" enctype="multipart/form-data">
                        <input type="hidden" name="id_Producto" id="id_Producto" value="<?php echo $id_Producto; ?>">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-box-seam"></i> Nombre</label>
                                <input autocomplete="off" type="text" name="nombre" class="form-control"
                                    placeholder="Ingresar Nombre del Producto" required maxlength="75"
                                    value="<?php echo htmlspecialchars($nombre); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-card-text"></i> Descripción</label>
                                <textarea autocomplete="off" name="descripcion" class="form-control"
                                    placeholder="Ingresar Descripción" required maxlength="100"
                                    rows="3"><?php echo htmlspecialchars($descripcion); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-image"></i> Imagen</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                                <div id="imagenPreview" class="mt-2">
                                    <?php if (!empty($imagen)): ?>
                                        <p class="small text-muted">Imagen actual:</p>
                                        <img src="../../img/productos/<?php echo htmlspecialchars($imagen); ?>"
                                            class="product-image" alt="Imagen actual">
                                        <p class="small text-muted mt-1"><?php echo htmlspecialchars($imagen); ?></p>
                                    <?php else: ?>
                                        <p class="small text-muted">No hay imagen actual</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-tags"></i> Categoría</label>
                                <select class="form-select" name="id_Categoria" required>
                                    <option value="">Seleccione</option>
                                    <?php
                                    while ($row = $categoriasList->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = ($id_Categoria == $row['id_Categoria']) ? 'selected' : '';
                                        echo "<option value='{$row['id_Categoria']}' $selected>{$row['nombre']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-rulers"></i> Medida</label>
                                <select class="form-select" name="id_Medida" required>
                                    <option value="">Seleccione</option>
                                    <?php
                                    while ($row = $medidasList->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = ($id_Medida == $row['id_Medida']) ? 'selected' : '';
                                        echo "<option value='{$row['id_Medida']}' $selected>{$row['nombre']} ({$row['simbolo']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-toggle-on"></i> Estado</label>
                                <select class="form-select" name="estado" required>
                                    <option value="">Seleccione</option>
                                    <option value="1" <?php echo $estado == 1 ? 'selected' : ''; ?>>Activo</option>
                                    <option value="0" <?php echo $estado == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                            <div class="text-muted small mb-3">*Todos los campos son obligatorios</div>
                            <div class="col-12 text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
                                <button type="submit" class="btn btn-success flex-grow-1 flex-sm-grow-0"
                                    style="max-width: 200px;">Guardar</button>
                                <button id="btnCancelar" type="button"
                                    class="btn btn-warning flex-grow-1 flex-sm-grow-0"
                                    style="max-width: 200px;">Cancelar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
            <div class="col-md-8" id="tablaCol">
                <div class="table-section">
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de Productos</div>
                    <div class="table-responsive">
                        <table id="tablaProductos" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th>Medida</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $productosList->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($row['imagen'])): ?>
                                                <img src="../../img/productos/<?php echo htmlspecialchars($row['imagen']); ?>"
                                                    alt="Imagen producto" class="product-image"
                                                    onclick="mostrarImagen('../../img/productos/<?php echo htmlspecialchars($row['imagen']); ?>')">
                                            <?php else: ?>
                                                <i class="bi bi-image text-muted no-image" style="font-size: 1.5rem;"
                                                    onclick="mostrarSinImagen()"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($row['categoria_nombre'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['medida_nombre'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge <?php echo $row['estado'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $row['estado'] ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_producto.php?id=<?php echo $row['id_Producto']; ?>'">
                                                <i class="bi bi-pencil" style="font-size: 1.2rem;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        document.getElementById('btnCancelar').addEventListener('click', () => {
            window.location.href = 'crear_producto.php';
        });
        // Función para mostrar imagen ampliada
        function mostrarImagen(rutaImagen) {
            const modal = document.getElementById('modalImagen');
            const imagenAmpliada = document.getElementById('imagenAmpliada');

            imagenAmpliada.src = rutaImagen;
            modal.style.display = 'block';
        }

        // Función para mostrar mensaje cuando no hay imagen
        function mostrarSinImagen() {
            Swal.fire({
                title: 'Sin imagen',
                text: 'Este producto no tiene imagen disponible',
                icon: 'info',
                iconColor: '#17a2b8',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar'
            });
        }

        // Cerrar modal al hacer clic en la X
        document.querySelector('.cerrar-modal').addEventListener('click', function () {
            document.getElementById('modalImagen').style.display = 'none';
        });

        // Cerrar modal al hacer clic fuera de la imagen
        document.getElementById('modalImagen').addEventListener('click', function (e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.getElementById('modalImagen').style.display = 'none';
            }
        });
        // Previsualización de imagen
        $('input[name="imagen"]').change(function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    $('#imagenPreview').html(`<img src="${e.target.result}" class="product-image" alt="Vista previa">`);
                }
                reader.readAsDataURL(file);
            }
        });

        // Mostrar SweetAlert con mensajes específicos
        const message = "<?php echo $message; ?>";

        if (message === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'El producto ha sido actualizado correctamente en el sistema',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crear_producto.php';
                }
            });
        } else if (message === 'error') {
            let errorMessage = 'No se pudo completar la actualización. Verifique que el producto no esté duplicado.';
            Swal.fire({
                title: 'Error de actualización',
                text: errorMessage,
                icon: 'error',
                iconColor: '#f06548',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Entendido'
            });
        }

        $(document).ready(function () {
            $('#tablaProductos').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
            });
        });
        // Toggle form
        const tablaTitle = document.getElementById('tablaTitle');
        const formCard = document.querySelector('.card-form');
        const tablaCol = document.getElementById('tablaCol');
        window.addEventListener('load', () => {
            formCard.style.display = 'block';
            tablaCol.classList.remove('col-12');
            tablaCol.classList.add('col-md-8');
        });
        tablaTitle.addEventListener('click', () => {
            if (formCard.style.display === 'none') {
                formCard.style.display = 'block';
                tablaCol.classList.remove('col-12');
                tablaCol.classList.add('col-md-8');
            } else {
                formCard.style.display = 'none';
                tablaCol.classList.remove('col-md-8');
                tablaCol.classList.add('col-12');
            }
        });
    </script>
</body>

</html>