<?php
include_once '../../conexion/conexion.php';
include_once '../empleado/empleado.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$empleado = new Empleado($db);

$message = '';
// Variables para mantener los valores del formulario
$id_Empleado = $nombre = $apellido = $DUI = $telefono = $direccion = $correo = $clave = "";
$id_Usuario = $estado = "";

// Obtener el ID del empleado a actualizar
if (isset($_GET['id'])) {
    $id_Empleado = (int) $_GET['id'];
    $empleado->id_Empleado = $id_Empleado;

    // Cargar datos del empleado
    if ($empleado->leerPorId()) {
        $nombre = $empleado->nombre;
        $apellido = $empleado->apellido;
        $DUI = $empleado->DUI;
        $telefono = $empleado->telefono;
        $direccion = $empleado->direccion;
        $correo = $empleado->correo;
        $clave = $empleado->clave;
        $id_Usuario = $empleado->id_Usuario;
        $estado = $empleado->estado;
    } else {
        $message = 'Empleado no encontrado.';
    }
}

// Manejo del POST para actualizar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Guardamos los valores para mantenerlos en caso de error
    $id_Empleado = (int) $_POST['id_Empleado'];
    $nombre = strtoupper(trim($_POST['nombre']));
    $apellido = strtoupper(trim($_POST['apellido']));
    $DUI = trim($_POST['DUI']);
    $telefono = trim($_POST['telefono']);
    $direccion = strtoupper(trim($_POST['direccion']));
    $correo = strtolower(trim($_POST['correo']));
    $clave = trim($_POST['clave']);
    $id_Usuario = intval($_POST['id_Usuario']);
    $estado = intval($_POST['estado']);

    // Asignar valores al objeto
    $empleado->id_Empleado = $id_Empleado;
    $empleado->nombre = $nombre;
    $empleado->apellido = $apellido;
    $empleado->DUI = $DUI;
    $empleado->telefono = $telefono;
    $empleado->direccion = $direccion;
    $empleado->correo = $correo;
    $empleado->clave = $clave;
    $empleado->id_Usuario = $id_Usuario;
    $empleado->estado = $estado;

    // Actualizar el empleado
    if ($empleado->actualizar()) {
        $message = 'success';
    } else {
        $message = 'error';
    }
}

// Leer todos los empleados
$stmt = $empleado->leer();
// Leer todos los usuarios activos para el select
$stmt1 = $empleado->leerUsuariosActivos();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar Empleado</title>
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
                    <div class="card-title">Actualizar Empleado</div>
                    <?php if ($message == 'not_found'): ?>
                        <div class="alert alert-danger" role="alert">
                            Empleado no encontrado.
                        </div>
                    <?php else: ?>
                        <form id="empleadoForm" method="post" action="actualizar_empleado.php">
                            <input type="hidden" name="id_Empleado" id="id_Empleado" value="<?php echo $id_Empleado; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-person-fill"></i>Nombre</label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Ingresar Nombre"
                                        required maxlength="25" value="<?php echo htmlspecialchars($nombre); ?>"
                                        oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i
                                            class="bi bi-person-vcard-fill"></i>Apellido</label>
                                    <input type="text" name="apellido" class="form-control" placeholder="Ingresar Apellido"
                                        required maxlength="25" value="<?php echo htmlspecialchars($apellido); ?>"
                                        oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i
                                            class="bi bi-credit-card-2-front-fill"></i>DUI</label>
                                    <input type="text" name="DUI" class="form-control" placeholder="Ingrese número de DUI"
                                        required maxlength="10" value="<?php echo htmlspecialchars($DUI); ?>"
                                        pattern="\d{8}-\d{1}" title="Formato válido: 12345678-9"
                                        oninput="this.value = this.value.replace(/\D/g,'').slice(0,9); if(this.value.length>8){this.value=this.value.slice(0,8)+'-'+this.value.slice(8)}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-telephone-fill"></i>Teléfono</label>
                                    <input type="text" name="telefono" class="form-control"
                                        placeholder="Ingresar número de teléfono" required maxlength="9"
                                        value="<?php echo htmlspecialchars($telefono); ?>" pattern="\d{4}-\d{4}"
                                        title="Formato válido: 1234-5678"
                                        oninput="this.value=this.value.replace(/\D/g,'').slice(0,8); if(this.value.length>4){this.value=this.value.slice(0,4)+'-'+this.value.slice(4)}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-icon"><i class="bi bi-house-fill"></i>Dirección</label>
                                    <input type="text" name="direccion" class="form-control"
                                        placeholder="Ingresar Dirección" required
                                        value="<?php echo htmlspecialchars($direccion); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-icon"><i class="bi bi-envelope-fill"></i>Correo</label>
                                    <input type="email" name="correo" class="form-control"
                                        placeholder="Ingresar correo electrónico" required
                                        value="<?php echo htmlspecialchars($correo); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-icon"><i class="bi bi-key-fill"></i>Clave</label>
                                    <input type="password" name="clave" class="form-control"
                                        placeholder="Ingresar clave de acceso" required minlength="8" maxlength="12"
                                        value="<?php echo htmlspecialchars($clave); ?>">
                                    <small class="form-text text-muted">Dejar en blanco si no desea cambiar la clave</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-person-gear"></i>Usuario</label>
                                    <select class="form-select" name="id_Usuario" required>
                                        <option value="">Seleccione</option>
                                        <?php
                                        // Reiniciar el puntero del resultado
                                        $stmt1->execute();
                                        while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = ($id_Usuario == $row['id_Usuario']) ? 'selected' : '';
                                            echo "<option value='{$row['id_Usuario']}' $selected>{$row['rol']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-toggle-on"></i>Estado</label>
                                    <select class="form-select" name="estado" required>
                                        <option value="">Seleccione</option>
                                        <option value="1" <?php echo ($estado == 1 ? 'selected' : ''); ?>>Alta</option>
                                        <option value="0" <?php echo ($estado == 0 ? 'selected' : ''); ?>>Baja</option>
                                    </select>
                                </div>
                                <div class="col-12 text-center mt-4">
                                    <button id="btnCancelar" type="button" class="btn btn-warning px-5 py-2">Cancelar</button>
                                    <button type="submit" class="btn btn-success px-5 py-2">Actualizar</button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla -->
            <div class="col-md-8" id="tablaCol">
                <div class="table-section">
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">LISTA DE EMPLEADOS</div>
                    <div class="table-responsive">
                        <table id="tablaEmpleados" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>DUI</th>
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
                                    <th>Correo</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Reiniciar el puntero del resultado
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($row['dui']); ?></td>
                                        <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($row['direccion']); ?></td>
                                        <td><?php echo htmlspecialchars($row['correo']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_usuario']); ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo ($row['estado'] == 1 ? 'badge-success' : 'badge-danger'); ?>">
                                                <?php echo ($row['estado'] == 1 ? 'ALTA' : 'BAJA'); ?>
                                            </span>
                                        </td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_empleado.php?id=<?php echo $row['id_Empleado']; ?>'">
                                                <i class="bi bi-pencil" style="font-size: 1.2rem;"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='dar_baja_empleado.php?id=<?php echo $row['id_Empleado']; ?>'">
                                                <i class="bi bi-trash" style="font-size: 1.2rem;"></i>
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
            window.location.href = 'crear_empleado.php';
        });

        // Mostrar SweetAlert y redireccionar después
        const message = "<?php echo $message; ?>";
        if (message === 'success') {
            Swal.fire({
                title: 'Empleado',
                text: 'Empleado actualizado correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redireccionar después de hacer clic en Aceptar
                    window.location.href = 'crear_empleado.php';
                }
            });

            // Limpiar el mensaje para evitar que se muestre al recargar
            <?php $message = ''; ?>
        } else if (message === 'error') {
            Swal.fire('Empleado', 'Error al actualizar el empleado. Puede que el DUI ya exista o el correo esta registrado.', 'error');
        }

        $(document).ready(function () {
            $('#tablaEmpleados').DataTable({
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