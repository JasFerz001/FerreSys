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

$conexion = new Conexion();
$db = $conexion->getConnection();
$conversionUnidad = new ConversionUnidad($db);
$productos = new Productos($db);
$unidadMedida = new unidadMedida($db);

$message = '';

// Obtener el ID de la conversión a editar
$id_Conversion = isset($_GET['id']) ? $_GET['id'] : '';

if (!$id_Conversion) {
    header("Location: crear_unidadConversion.php");
    exit();
}

// Cargar los datos de la conversión
$conversionUnidad->id_Conversion = $id_Conversion;

// Obtener productos y unidades de medida para los select
$stmtProductos = $productos->leerActivos();
$stmtUnidades = $unidadMedida->leer();

// Cargar datos existentes
$query = "SELECT cu.*, p.nombre as producto_nombre, 
                 um_base.nombre as unidad_base_nombre, um_base.simbolo as unidad_base_simbolo,
                 um_venta.nombre as unidad_venta_nombre, um_venta.simbolo as unidad_venta_simbolo
          FROM conversion_unidades cu
          INNER JOIN producto p ON cu.id_Producto = p.id_Producto
          INNER JOIN unidad_medida um_base ON cu.id_Unidad_Base = um_base.id_Medida
          INNER JOIN unidad_medida um_venta ON cu.id_Unidad_Venta = um_venta.id_Medida
          WHERE cu.id_Conversion = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_Conversion]);
$conversion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversion) {
    header("Location: crear_unidadConversion.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_Producto = trim($_POST['id_Producto']);
    $id_Unidad_Base = trim($_POST['id_Unidad_Base']);
    $id_Unidad_Venta = trim($_POST['id_Unidad_Venta']);
    $factor_conversion = trim($_POST['factor_conversion']);
    
    // CORRECCIÓN: Capturar el valor del select correctamente
    $es_activo = isset($_POST['es_activo']) ? (int)$_POST['es_activo'] : 0;

    $conversionUnidad->id_Conversion = $id_Conversion;
    $conversionUnidad->id_Producto = $id_Producto;
    $conversionUnidad->id_Unidad_Base = $id_Unidad_Base;
    $conversionUnidad->id_Unidad_Venta = $id_Unidad_Venta;
    $conversionUnidad->factor_conversion = $factor_conversion;
    $conversionUnidad->es_activo = $es_activo;

    // Actualizar la Conversión de Unidad
    $result = $conversionUnidad->actualizar();

    if ($result) {
        $message = 'success';
        // Recargar datos actualizados
        $stmt = $db->prepare($query);
        $stmt->execute([$id_Conversion]);
        $conversion = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = 'error';
    }
}

// Leer todas las conversiones para mostrarlas en la tabla
$queryAll = "SELECT cu.*, p.nombre as producto, 
                    um_base.nombre as unidad_base, 
                    um_venta.nombre as unidad_venta
             FROM conversion_unidades cu
             INNER JOIN producto p ON cu.id_Producto = p.id_Producto
             INNER JOIN unidad_medida um_base ON cu.id_Unidad_Base = um_base.id_Medida
             INNER JOIN unidad_medida um_venta ON cu.id_Unidad_Venta = um_venta.id_Medida
             ORDER BY cu.id_Conversion ASC";
$stmtConversiones = $db->prepare($queryAll);
$stmtConversiones->execute();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar Conversión de Unidades</title>
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
                    <div class="card-title">Actualizar Conversión de Unidades</div>

                    <form id="conversionUnidadForm" method="post"
                        action="actualizar_unidadConversion.php?id=<?php echo $id_Conversion; ?>">
                        <input type="hidden" name="id_Conversion" id="id_Conversion"
                            value="<?php echo $conversion['id_Conversion']; ?>">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-box"></i> Producto</label>
                                <select name="id_Producto" class="form-control" required>
                                    <option value="">Seleccionar Producto</option>
                                    <?php
                                    $stmtProductos->execute();
                                    while ($row = $stmtProductos->fetch(PDO::FETCH_ASSOC)):
                                        $selected = ($row['id_Producto'] == $conversion['id_Producto']) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $row['id_Producto']; ?>" <?php echo $selected; ?>>
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
                                    $stmtUnidades->execute();
                                    while ($row = $stmtUnidades->fetch(PDO::FETCH_ASSOC)):
                                        $selected = ($row['id_Medida'] == $conversion['id_Unidad_Base']) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $row['id_Medida']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($row['nombre'] . ' (' . $row['simbolo'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-arrow-left-right"></i> Unidad
                                    Venta</label>
                                <select name="id_Unidad_Venta" class="form-control" required>
                                    <option value="">Seleccionar Unidad Venta</option>
                                    <?php
                                    $stmtUnidades->execute();
                                    while ($row = $stmtUnidades->fetch(PDO::FETCH_ASSOC)):
                                        $selected = ($row['id_Medida'] == $conversion['id_Unidad_Venta']) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $row['id_Medida']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($row['nombre'] . ' (' . $row['simbolo'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-calculator"></i> Factor de
                                    Conversión</label>
                                <input autocomplete="off" type="number" step="0.001" name="factor_conversion"
                                    class="form-control" placeholder="Ingresar Factor" required min="0.001"
                                    value="<?php echo htmlspecialchars($conversion['factor_conversion']); ?>">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label form-icon"><i class="bi bi-toggle-on"></i> Estado</label>
                                <select name="es_activo" class="form-control" required>
                                    <option value="1" <?php echo $conversion['es_activo'] ? 'selected' : ''; ?>>Activo
                                    </option>
                                    <option value="0" <?php echo !$conversion['es_activo'] ? 'selected' : ''; ?>>
                                        Inactivo</option>
                                </select>
                            </div>
                            <div class="text-muted small mb-3">*Todos los campos son obligatorios</div>
                            <div class="col-12 text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">

                                <button type="submit" class="btn btn-success flex-grow-1 flex-sm-grow-0"
                                    style="max-width: 200px;">Actualizar</button>
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
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de Conversiones de Unidades
                    </div>
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
                                <?php while ($row = $stmtConversiones->fetch(PDO::FETCH_ASSOC)):
                                    $isCurrent = $row['id_Conversion'] == $id_Conversion;
                                    ?>
                                    <tr class="<?php echo $isCurrent ? 'table-active' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($row['producto']); ?>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-primary">Editando</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['unidad_base']); ?></td>
                                        <td><?php echo htmlspecialchars($row['unidad_venta']); ?></td>
                                        <td><?php echo htmlspecialchars($row['factor_conversion']); ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo $row['es_activo'] ? 'badge-success' : 'badge-danger'; ?>">
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
            // Restaurar valores originales
            document.querySelector('select[name="id_Producto"]').value = '<?php echo $conversion['id_Producto']; ?>';
            document.querySelector('select[name="id_Unidad_Base"]').value = '<?php echo $conversion['id_Unidad_Base']; ?>';
            document.querySelector('select[name="id_Unidad_Venta"]').value = '<?php echo $conversion['id_Unidad_Venta']; ?>';
            document.querySelector('input[name="factor_conversion"]').value = '<?php echo $conversion['factor_conversion']; ?>';
            document.getElementById('es_activo').checked = <?php echo $conversion['es_activo'] ? 'true' : 'false'; ?>;
        });

        // Mostrar SweetAlert con mensajes específicos
        const message = "<?php echo $message; ?>";

        if (message === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'La conversión de unidades ha sido actualizada correctamente',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crear_unidadConversion.php';
                }
            });
        } else if (message === 'error') {
            Swal.fire({
                title: 'Error de actualización',
                text: 'No se pudo completar la actualización. Verifique que los datos sean correctos.',
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
                "drawCallback": function (settings) {
                    // Resaltar la fila que se está editando
                    $('tr.table-active').each(function () {
                        $(this).addClass('table-active');
                    });
                }
            });
        });

        // Validación adicional del formulario
        document.getElementById('conversionUnidadForm').addEventListener('submit', function (e) {
            const factor = document.querySelector('input[name="factor_conversion"]').value;
            const idUnidadBase = document.querySelector('select[name="id_Unidad_Base"]').value;
            const idUnidadVenta = document.querySelector('select[name="id_Unidad_Venta"]').value;

            if (idUnidadBase === idUnidadVenta) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error de validación',
                    text: 'La unidad base y la unidad de venta no pueden ser la misma.',
                    icon: 'warning',
                    iconColor: '#f06548',
                    confirmButtonColor: '#3b7ddd',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            if (parseFloat(factor) <= 0) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error de validación',
                    text: 'El factor de conversión debe ser mayor a 0.',
                    icon: 'warning',
                    iconColor: '#f06548',
                    confirmButtonColor: '#3b7ddd',
                    confirmButtonText: 'Entendido'
                });
            }
        });
    </script>
</body>

</html>