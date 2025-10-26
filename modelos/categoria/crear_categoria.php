<?php
//verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}

include_once '../../conexion/conexion.php';
include_once '../categoria/categoria.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$categoria = new Categoria($db);

$message = '';

function formatearTexto($texto)
{
    $texto = strtolower(trim($texto));
    return ucwords($texto);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = formatearTexto(trim($_POST['nombre']));
    $descripcion = formatearTexto(trim($_POST['descripcion']));

    $categoria->nombre = $nombre;
    $categoria->descripcion = $descripcion;

    // Crear la Categoría
    $result = $categoria->crear();

    if ($result['success']) {
        $message = 'success';
        // Limpiar los campos solo si fue exitoso
        $nombre = $descripcion = "";
    } else {
        $message = 'error';
    }
}

// Leer todos las categorias para mostrarlas en la tabla
$stmt = $categoria->leer();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Categorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/empleado.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-fluid px-3">
        <div class="row form-table-container d-flex">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-form h-100">
                    <div class="card-title">Registro de Categorias</div>

                    <form id="categoriaForm" method="post" action="crear_categoria.php">
                        <input type="hidden" name="id_Categoria" id="id_Categoria">
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label form-icon">
                                    <i class="bi bi-tags-fill"></i> Nombre
                                </label>
                                <input autocomplete="off" type="text" name="nombre" class="form-control"
                                    placeholder="Ingresar Nombre" required maxlength="25"
                                    value="<?php echo isset($nombre) ? $nombre : ''; ?>"
                                    oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label form-icon">
                                    <i class="bi bi-chat-left-dots-fill"></i> Descripción
                                </label>
                                <textarea name="descripcion" class="form-control" rows="3"
                                    placeholder="Ingresar Descripción" required
                                    maxlength="75"><?php echo isset($descripcion) ? $descripcion : ''; ?></textarea>
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
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de Categorias</div>
                    <div class="table-responsive">
                        <table id="tablaCategorias" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_categoria.php?id=<?php echo $row['id_Categoria']; ?>'">
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
            document.querySelectorAll('#categoriaForm input').forEach(input => {
                if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
        });

        // Mostrar SweetAlert con mensajes específicos
        const message = "<?php echo $message; ?>";
        if (message === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'La categoría ha sido registrada correctamente en el sistema',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Limpiar formulario después de éxito
                document.querySelectorAll('#categoriaForm input').forEach(input => {
                    if (input.type !== 'hidden') {
                        input.value = '';
                    }
                });
            });
        } else if (message === 'error') {
            let errorMessage = 'No se pudo completar el registro verifique que la categoria no esta registrada ya. ';
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
            $('#tablaCategorias').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
            });
        });
    </script>
</body>

</html>