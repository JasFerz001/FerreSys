<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}

include_once '../../conexion/conexion.php';
include_once '../proveedores/proveedor.php';
include_once '../../modelos/bitacora/bitacora.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$proveedor = new Proveedor($db);
$bitacora = new Bitacora($db);

$message = '';
$duplicates = [];

$id_Proveedor = $nombre = $contac_referencia = $correo = $telefono = $estado = '';

function formatearTexto($texto)
{
    $texto = strtolower(trim($texto));
    return ucwords($texto);
}

// Leer proveedor si se pasa el id por GET
if (isset($_GET['id'])) {
    $id_Proveedor = (int) $_GET['id'];
    $proveedor->id_Proveedor = $id_Proveedor;

    if ($proveedor->leerUno()) {
        $nombre = $proveedor->nombre;
        $contac_referencia = $proveedor->contac_referencia;
        $telefono = $proveedor->telefono;
        $correo = $proveedor->correo;
        $estado = $proveedor->estado;
    } else {
        $message = 'No se encontró el proveedor.';
    }
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_Proveedor = (int) $_POST['id_Proveedor'];
    $nombre = formatearTexto(trim($_POST['nombre']));
    $contac_referencia = formatearTexto(trim($_POST['contac_referencia']));
    $telefono = trim($_POST['telefono']);
    $correo = strtolower(trim($_POST['correo']));
    $estado = intval($_POST['estado']);

    $proveedor->id_Proveedor = $id_Proveedor;
    $proveedor->nombre = $nombre;
    $proveedor->contac_referencia = $contac_referencia;
    $proveedor->telefono = $telefono;
    $proveedor->correo = $correo;
    $proveedor->estado = $estado;

    $result = $proveedor->actualizar();

    if ($result['success']) {
        $message = 'Proveedor actualizado exitosamente.';

        // === Registrar en bitácora ===
        $bitacora->id_Empleado = $_SESSION['id_Empleado'];
        $bitacora->accion = "Actualización de proveedor";
        $bitacora->descripcion = "Se actualizó la información del proveedor '{$nombre}' (ID: {$id_Proveedor}).";
        $bitacora->registrar();
    } else {
        if (isset($result['duplicates'])) {
            $duplicates = $result['duplicates'];
        } else {
            $message = $result['message'] ?? 'Error al actualizar el proveedor.';
        }
    }
}

// Leer proveedores
$stmt = $proveedor->leer();
$stmt1 = $proveedor->leerActivos();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar Proveedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/proveedor.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-fluid px-3">
        <div class="row form-table-container d-flex">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-form h-100">
                    <div class="card-title">Actualizar Proveedor</div>
                    <?php if ($message == 'Proveedor no encontrado'): ?>
                        <div class="alert alert-danger" role="alert">
                            Proveedor no encontrado.
                        </div>
                    <?php else: ?>
                        <form id="proveedorForm" method="post"
                            action="actualizar_proveedor.php?id=<?php echo $id_Proveedor; ?>">
                            <input type="hidden" name="id_Proveedor" id="id_Proveedor" value="<?php echo $id_Proveedor; ?>">

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label form-icon"><i class="bi bi-person-fill"></i> Nombre de la
                                        Empresa</label>
                                    <input autocomplete="off" type="text" name="nombre" class="form-control"
                                        placeholder="Ingresar Nombre de la Empresa" required maxlength="75"
                                        value="<?php echo htmlspecialchars($nombre); ?>"
                                        oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label form-icon"><i class="bi bi-person-vcard-fill"></i> Contacto de
                                        Referencia</label>
                                    <input autocomplete="off" type="text" name="contac_referencia" class="form-control"
                                        placeholder="Ingresar Contacto de Referencia" required maxlength="100"
                                        value="<?php echo htmlspecialchars($contac_referencia); ?>"
                                        oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label form-icon"><i class="bi bi-telephone-fill"></i>Teléfono</label>
                                    <input autocomplete="off" type="text" name="telefono" class="form-control"
                                        placeholder="Ingresar número de teléfono" required maxlength="9"
                                        value="<?php echo htmlspecialchars($telefono); ?>" pattern="\d{4}-\d{4}"
                                        title="Formato válido: 1234-5678"
                                        oninput="this.value=this.value.replace(/\D/g,'').slice(0,8); if(this.value.length>4){this.value=this.value.slice(0,4)+'-'+this.value.slice(4)}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-icon"><i class="bi bi-envelope-fill"></i> Correo</label>
                                    <input autocomplete="off" type="email" name="correo" class="form-control"
                                        placeholder="Ingresar correo electrónico" required
                                        value="<?php echo htmlspecialchars($correo); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-toggle-on"></i> Estado</label>
                                    <select class="form-select" name="estado" required>
                                        <option value="">Seleccione</option>
                                        <option value="1" <?php echo ($estado == 1 ? 'selected' : ''); ?>>Alta</option>
                                        <option value="0" <?php echo ($estado == 0 ? 'selected' : ''); ?>>Baja</option>
                                    </select>
                                </div>
                                <div class="text-muted small mb-2">* Actualize los datos que necesite</div>
                                <div class="col-12 text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
                                    <button type="submit" class="btn btn-success flex-grow-1 flex-sm-grow-0"
                                        style="max-width: 200px;">Actualizar</button>
                                    <button id="btnCancelar" type="button"
                                        class="btn btn-warning flex-grow-1 flex-sm-grow-0"
                                        style="max-width: 200px;">Cancelar</button>

                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla -->
            <div class="col-md-8" id="tablaCol">
                <div class="table-section">
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de Proveedores</div>
                    <div class="table-responsive">
                        <table id="tablaProveedores" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th style="text-align: center;">Nombre</th>
                                    <th style="text-align: center;">Contacto Referencia</th>
                                    <th style="text-align: center;">Telefono</th>
                                    <th style="text-align: center;">Correo</th>
                                    <th style="text-align: center;">Estado</th>
                                    <th style="text-align: center;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Reiniciar el puntero del resultado
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['contac_referencia']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($row['correo']); ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo ($row['estado'] == 1 ? 'badge-success' : 'badge-danger'); ?>">
                                                <?php echo ($row['estado'] == 1 ? 'Alta' : 'Baja'); ?>
                                            </span>
                                        </td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_proveedor.php?id=<?php echo $row['id_Proveedor']; ?>'">
                                                <i class="bi bi-pencil" style="font-size: 1.2rem;"></i>

                                                <!--
                                            <button class="btn btn-sm btn-outline-danger p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='dar_baja_empleado.php?id=<?php echo $row['id_Proveeedor']; ?>'">
                                                <i class="bi bi-trash" style="font-size: 1.2rem;"></i>
                                            </button>
                                            -->
                                            </button>
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
            window.location.href = 'crear_proveedor.php';
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Inicializar DataTable
            $('#tablaProveedores').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
            });

            // Lógica para mostrar/ocultar formulario
            const tablaTitle = document.getElementById('tablaTitle');
            const formCard = document.querySelector('.card-form');
            const tablaCol = document.getElementById('tablaCol');

            // Mostrar formulario al cargar la página si se está actualizando
            formCard.style.display = 'block';
            tablaCol.classList.remove('col-12');
            tablaCol.classList.add('col-md-8');

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

            // Mostrar SweetAlert y redireccionar después
            const message = "<?php echo $message; ?>";
            const duplicates = <?php echo json_encode($duplicates); ?>;

            if (message === 'Proveedor actualizado exitosamente.') {
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Proveedor actualizado correctamente',
                    icon: 'success',
                    iconColor: '#1cbb8c',
                    confirmButtonColor: '#3b7ddd',
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'crear_proveedor.php';
                    }
                });
            } else if (duplicates.length > 0) {
                let errorMessage = 'No se pudo completar la actualización. Los siguientes datos ya están registrados:\n\n';

                if (duplicates.includes('nombre')) errorMessage += '• Nombre de la empresa\n';
                if (duplicates.includes('contacto de referencia')) errorMessage += '• Contacto de referencia\n';
                if (duplicates.includes('correo')) errorMessage += '• Correo electrónico\n';
                if (duplicates.includes('teléfono')) errorMessage += '• Número de teléfono\n';

                errorMessage += '\nPor favor, verifique la información.';

                Swal.fire({
                    title: 'Error de actualización',
                    text: errorMessage,
                    icon: 'error',
                    iconColor: '#f06548',
                    confirmButtonColor: '#3b7ddd',
                    confirmButtonText: 'Entendido'
                });
            } else if (message && message !== 'Proveedor no encontrado.') {
                Swal.fire({
                    title: 'Error de actualización',
                    text: message,
                    icon: 'error',
                    iconColor: '#f06548',
                    confirmButtonColor: '#3b7ddd',
                    confirmButtonText: 'Entendido'
                });
            }
        });
    </script>

</body>

</html>