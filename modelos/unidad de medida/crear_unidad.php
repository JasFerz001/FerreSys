<?php
include_once '../../conexion/conexion.php';
include_once '../unidad de medida/unidadMedida.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$unidadMedida = new unidadMedida($db);

$message = '';

function formatearTexto($texto)
{
    $texto = strtolower(trim($texto));
    return ucwords($texto);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = formatearTexto(trim($_POST['nombre']));
    $simbolo = trim($_POST['simbolo']);

    $unidadMedida->nombre = $nombre;
    $unidadMedida->simbolo = $simbolo;

    // Crear la Unidad de Medida
    $result = $unidadMedida->crear();

    if ($result) {
        $message = 'success';
    } else {
        $message = 'error';
    }
}

// Leer todas las unidades de medida para mostrarlas en la tabla
$stmt = $unidadMedida->leer();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Unidades de Medida</title>
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
                    <div class="card-title">Registro de Unidades de Medida</div>
                    
                    <form id="unidadMedidaForm" method="post" action="crear_unidad.php">
                        <input type="hidden" name="id_Medida" id="id_Medida">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-rulers"></i> Nombre</label>
                                <input autocomplete="off" type="text" name="nombre" class="form-control"
                                    placeholder="Ingresar Nombre" required maxlength="25"
                                    value="<?php echo isset($nombre) ? $nombre : ''; ?>"
                                    oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-icon"><i class="bi bi-type"></i> Símbolo</label>
                                <input autocomplete="off" type="text" name="simbolo" class="form-control"
                                    placeholder="Ingresar Símbolo" required maxlength="10"
                                    value="<?php echo isset($simbolo) ? $simbolo : ''; ?>">
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
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de Unidades de Medida</div>
                    <div class="table-responsive">
                        <table id="tablaUnidadesMedida" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Símbolo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['simbolo']); ?></td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_unidad.php?id=<?php echo $row['id_Medida']; ?>'">
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
            document.querySelectorAll('#unidadMedidaForm input').forEach(input => {
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
                text: 'La unidad de medida ha sido registrada correctamente en el sistema',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Limpiar formulario después de éxito
                document.querySelectorAll('#unidadMedidaForm input').forEach(input => {
                    if (input.type !== 'hidden') {
                        input.value = '';
                    }
                });
            });
        } else if (message === 'error') {
            Swal.fire({
                title: 'Error de registro',
                text: 'No se pudo completar el registro. Verifique que la unidad de medida no esté registrada ya.',
                icon: 'error',
                iconColor: '#f06548',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Entendido'
            });
        }

        $(document).ready(function () {
            $('#tablaUnidadesMedida').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
            });
        });
    </script>
</body>

</html>