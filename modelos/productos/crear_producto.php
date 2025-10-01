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

$categoriasList = $categorias->leer();
$medidasList = $medidas->leer();

$message = '';

function formatearTexto($texto)
{
    $texto = strtolower(trim($texto));
    return ucwords($texto);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = formatearTexto(trim($_POST['nombre']));
    $descripcion = formatearTexto(trim($_POST['descripcion']));
    $id_Categoria = $_POST['id_Categoria'];
    $id_Medida = $_POST['id_Medida'];
    $estado = 1; // Siempre activo por defecto

    $producto->nombre = $nombre;
    $producto->descripcion = $descripcion;
    $producto->id_Categoria = $id_Categoria;
    $producto->id_Medida = $id_Medida;
    $producto->estado = $estado;

    // Procesar imagen si se subió
    if (!empty($_FILES['imagen']['name'])) {
        $imagen_nombre = $_FILES['imagen']['name'];
        $imagen_temporal = $_FILES['imagen']['tmp_name'];
        $directorio_imagenes = "../../img/productos/";

        // Crear directorio si no existe
        if (!is_dir($directorio_imagenes)) {
            mkdir($directorio_imagenes, 0777, true);
        }

        // Mover la imagen al directorio
        $ruta_imagen = $directorio_imagenes . $imagen_nombre;
        if (move_uploaded_file($imagen_temporal, $ruta_imagen)) {
            $producto->imagen = $imagen_nombre;
        }
    } else {
        $producto->imagen = ""; // Imagen vacía si no se sube
    }

    // Crear el Producto
    if ($producto->crear()) {
        $message = 'success';
    } else {
        $message = 'error';
    }
}

// Leer todos los productos para mostrarlos en la tabla
$productosList = $producto->leer();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/productos.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-fluid px-3">
        <div class="row form-table-container d-flex">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-form h-100">
                    <div class="card-title">Registro de Producto</div>
                    <div class="text-muted small mb-3">*Todos los campos son obligatorios</div>
                    <form id="productoForm" method="post" action="crear_producto.php" enctype="multipart/form-data">
                        <input type="hidden" name="id_Producto" id="id_Producto">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-box-seam"></i>Nombre</label>
                                <input autocomplete="off" type="text" name="nombre" class="form-control"
                                    placeholder="Ingresar Nombre del Producto" required maxlength="75"
                                    value="<?php echo isset($nombre) ? $nombre : ''; ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-card-text"></i>Descripción</label>
                                <textarea autocomplete="off" name="descripcion" class="form-control"
                                    placeholder="Ingresar Descripción" required maxlength="100"
                                    rows="3"><?php echo isset($descripcion) ? $descripcion : ''; ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label form-icon"><i class="bi bi-image"></i>Imagen</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*">
                                <div id="imagenPreview" class="mt-2"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-tags"></i>Categoría</label>
                                <select class="form-select" name="id_Categoria" required>
                                    <option value="">Seleccione</option>
                                    <?php
                                    while ($row = $categoriasList->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = (isset($id_Categoria) && $id_Categoria == $row['id_Categoria']) ? 'selected' : '';
                                        echo "<option value='{$row['id_Categoria']}' $selected>{$row['nombre']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-rulers"></i>Medida</label>
                                <select class="form-select" name="id_Medida" required>
                                    <option value="">Seleccione</option>
                                    <?php
                                    while ($row = $medidasList->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = (isset($id_Medida) && $id_Medida == $row['id_Medida']) ? 'selected' : '';
                                        echo "<option value='{$row['id_Medida']}' $selected>{$row['nombre']} ({$row['simbolo']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6" style="display: none;">
                                <label class="form-label form-icon"><i class="bi bi-toggle-on"></i>Estado</label>
                                <select class="form-select" name="estado" required>
                                    <option value="">Seleccione</option>
                                    <option value="1" selected>Alta</option>
                                    <option value="0">Baja</option>
                                </select>
                            </div>
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
                    <div class="card-title" id="tablaTitle">LISTA DE PRODUCTOS</div>
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
                                                    alt="Imagen producto" class="product-image">
                                            <?php else: ?>
                                                <i class="bi bi-image text-muted" style="font-size: 1.5rem;"></i>
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
        // Limpiar formulario al hacer clic en Cancelar
        document.getElementById('btnCancelar').addEventListener('click', () => {
            document.querySelectorAll('#productoForm input, #productoForm textarea, #productoForm select').forEach(element => {
                if (element.type !== 'hidden' && element.name !== 'estado') {
                    element.value = '';
                }
            });
            $('#imagenPreview').empty();
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
            } else {
                $('#imagenPreview').empty();
            }
        });

        // Mostrar SweetAlert con mensajes específicos
        const message = "<?php echo $message; ?>";
        if (message === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'El producto ha sido registrado correctamente en el sistema',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Limpiar formulario después de éxito
                document.querySelectorAll('#productoForm input, #productoForm textarea, #productoForm select').forEach(element => {
                    if (element.type !== 'hidden' && element.name !== 'estado') {
                        element.value = '';
                    }
                });
                $('#imagenPreview').empty();
            });
        } else if (message === 'error') {
            let errorMessage = 'No se pudo completar el registro. Verifique que el producto no esté registrado ya.';
            Swal.fire({
                title: 'Error de registro',
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
    </script>
</body>

</html>