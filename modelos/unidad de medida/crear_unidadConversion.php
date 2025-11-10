<?php
// Verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}

include_once '../../conexion/conexion.php';
include_once '../unidad de medida/unidadMedida.php';
include_once '../unidad de medida/conversionunidad.php';
include_once '../productos/productos.php';
include_once '../bitacora/bitacora.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$conversionUnidad = new ConversionUnidad($db);
$productos = new Productos($db);
$unidadMedida = new unidadMedida($db);
$bitacora = new Bitacora($db);

$message = '';

// Obtener productos y unidades de medida para los select
$stmtProductos = $productos->leerActivos();
$stmtUnidades = $unidadMedida->leer();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_Producto = trim($_POST['id_Producto']);
    $id_Unidad_Base = trim($_POST['id_Unidad_Base']);
    $id_Unidad_Venta = trim($_POST['id_Unidad_Venta']);
    $factor_conversion = trim($_POST['factor_conversion']);
    $es_activo = 1; // Asegurar que siempre se registre como "alta"

    $conversionUnidad->id_Producto = $id_Producto;
    $conversionUnidad->id_Unidad_Base = $id_Unidad_Base;
    $conversionUnidad->id_Unidad_Venta = $id_Unidad_Venta;
    $conversionUnidad->factor_conversion = $factor_conversion;
    $conversionUnidad->es_activo = $es_activo;

    // Crear la Conversión de Unidad
    $result = $conversionUnidad->crear();

    if ($result) {
        // Registrar en bitácora
        $bitacora->id_Empleado = $_SESSION['id_Empleado'];
        $bitacora->accion = "Creación de unidad de conversión";
        $bitacora->descripcion = "Se registró la unidad de conversion.";
        $bitacora->registrar();
        $message = 'success';
    } else {
        $message = 'error';
    }
}

// Leer todas las conversiones para mostrarlas en la tabla
$query = "SELECT cu.*, p.nombre as producto, 
                 um_base.nombre as unidad_base, 
                 um_venta.nombre as unidad_venta
          FROM conversion_unidades cu
          INNER JOIN producto p ON cu.id_Producto = p.id_Producto
          INNER JOIN unidad_medida um_base ON cu.id_Unidad_Base = um_base.id_Medida
          INNER JOIN unidad_medida um_venta ON cu.id_Unidad_Venta = um_venta.id_Medida
          ORDER BY cu.id_Conversion ASC";
$stmtConversiones = $db->prepare($query);
$stmtConversiones->execute();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Conversiones de Unidades</title>
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
                    <div class="card-title">Registro de Conversiones de Unidades</div>
                    
                    <form id="conversionUnidadForm" method="post" action="crear_unidadConversion.php">
                        <input type="hidden" name="id_Conversion" id="id_Conversion">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-box"></i> Producto</label>
                                <select name="id_Producto" class="form-control" required>
                                    <option value="">Seleccionar Producto</option>
                                    <?php while ($row = $stmtProductos->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id_Producto']; ?>">
                                            <?php echo htmlspecialchars($row['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-rulers"></i> Unidad Base</label>
                                <select name="id_Unidad_Base" class="form-control" required>
                                    <option value="">Seleccionar Unidad Base</option>
                                    <?php 
                                    // Reiniciar el puntero del resultado
                                    $stmtUnidades->execute();
                                    while ($row = $stmtUnidades->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id_Medida']; ?>">
                                            <?php echo htmlspecialchars($row['nombre'] . ' (' . $row['simbolo'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-arrow-left-right"></i> Unidad Venta</label>
                                <select name="id_Unidad_Venta" class="form-control" required>
                                    <option value="">Seleccionar Unidad Venta</option>
                                    <?php 
                                    // Reiniciar el puntero del resultado
                                    $stmtUnidades->execute();
                                    while ($row = $stmtUnidades->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id_Medida']; ?>">
                                            <?php echo htmlspecialchars($row['nombre'] . ' (' . $row['simbolo'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-calculator"></i> Factor de Conversión</label>
                                <input autocomplete="off" type="number" step="0.001" name="factor_conversion" class="form-control"
                                    placeholder="Ingresar Factor" required min="0.001"
                                    value="<?php echo isset($factor_conversion) ? $factor_conversion : ''; ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-toggle-on"></i> Estado</label>
                                <select class="form-select" name="es_activo" required disabled>
                                    <option value="1" selected>Alta</option>
                                </select>
                                <input type="hidden" name="estado" value="1">
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
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de Conversiones de Unidades</div>
                    <div class="table-responsive">
                        <table id="tablaConversionesUnidad" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Unidad Base</th>
                                    <th>Unidad Venta</th>
                                    <th>Factor</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $stmtConversiones->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['producto']); ?></td>
                                        <td><?php echo htmlspecialchars($row['unidad_base']); ?></td>
                                        <td><?php echo htmlspecialchars($row['unidad_venta']); ?></td>
                                        <td><?php echo htmlspecialchars($row['factor_conversion']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $row['es_activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $row['es_activo'] ? 'alta' : 'baja'; ?>
                                            </span>
                                        </td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_unidadConversion.php?id=<?php echo $row['id_Conversion']; ?>'">
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
            document.querySelectorAll('#conversionUnidadForm input').forEach(input => {
                if (input.type !== 'hidden') {
                    input.value = '';
                }
            });
            document.querySelectorAll('#conversionUnidadForm select').forEach(select => {
                select.selectedIndex = 0;
            });
            document.getElementById('es_activo').checked = true;
        });

        // Mostrar SweetAlert con mensajes específicos
        const message = "<?php echo $message; ?>";

        if (message === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'La conversión de unidades ha sido registrada correctamente en el sistema',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                // Limpiar formulario después de éxito
                document.querySelectorAll('#conversionUnidadForm input').forEach(input => {
                    if (input.type !== 'hidden') {
                        input.value = '';
                    }
                });
                document.querySelectorAll('#conversionUnidadForm select').forEach(select => {
                    select.selectedIndex = 0;
                });
                document.getElementById('es_activo').checked = true;
            });
        } else if (message === 'error') {
            Swal.fire({
                title: 'Error de registro',
                text: 'No se pudo completar el registro. Verifique que la conversión no esté registrada ya.',
                icon: 'error',
                iconColor: '#f06548',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Entendido'
            });
        }

        $(document).ready(function () {
            $('#tablaConversionesUnidad').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
            });
        });
    </script>
</body>

</html>