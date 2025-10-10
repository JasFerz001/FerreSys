<?php
include_once '../../conexion/conexion.php';
include_once '../empleado/empleado.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$empleado = new Empleado($db);

$message = '';
$duplicates = [];
// Variables para mantener los valores del formulario
$id_Empleado = $nombre = $apellido = $DUI = $telefono = $direccion = $correo = $clave = "";
$id_Usuario = $estado = "";

// Función para formatear texto con primera letra mayúscula y el resto minúsculas
function formatearTexto($texto)
{
    // Convertir todo a minúsculas primero y eliminar espacios en blanco
    $texto = strtolower(trim($texto));
    // Convertir la primera letra de cada palabra a mayúscula
    return ucwords($texto);
}

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
        $id_Usuario = $empleado->id_Usuario;
        $estado = $empleado->estado;
    } else {
        $message = 'not_found';
    }
}

// Manejo del POST para actualizar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Guardamos los valores para mantenerlos en caso de error
    $id_Empleado = (int) $_POST['id_Empleado'];
    $nombre = formatearTexto(trim($_POST['nombre']));  // Aplicar formato
    $apellido = formatearTexto(trim($_POST['apellido']));  // Aplicar formato
    $DUI = trim($_POST['DUI']);
    $telefono = trim($_POST['telefono']);
    $direccion = formatearTexto(trim($_POST['direccion']));  // Aplicar formato
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
    $empleado->clave = $clave; // Puede estar vacío
    $empleado->id_Usuario = $id_Usuario;
    $empleado->estado = $estado;

    // Actualizar el empleado
    $result = $empleado->actualizar();

    if ($result['success']) {
        $message = 'success';
    } else {
        $message = 'error';
        $duplicates = $result['duplicates'];
    }
}

// Leer todos los empleados
$stmt = $empleado->leer();
$idAdmin = $empleado->obtenerIdAdministrador();
$esAdministrador = ($id_Usuario == $idAdmin);
if ($id_Usuario === $idAdmin) {
    // Leer todos los usuarios activos para el select
    $stmt1 = $empleado->leerUsuariosActivosTodos();
} else {
    $stmt1 = $empleado->leerUsuariosActivos();
}

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
                        <form id="empleadoForm" method="post"
                            action="actualizar_empleado.php?id=<?php echo $id_Empleado; ?>">
                            <input type="hidden" name="id_Empleado" id="id_Empleado" value="<?php echo $id_Empleado; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-person-fill"></i> Nombre</label>
                                    <input autocomplete="off" type="text" name="nombre" class="form-control"
                                        placeholder="Ingresar Nombre" required maxlength="25"
                                        value="<?php echo htmlspecialchars($nombre); ?>"
                                        oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-person-vcard-fill"></i>
                                        Apellido</label>
                                    <input autocomplete="off" type="text" name="apellido" class="form-control"
                                        placeholder="Ingresar Apellido" required maxlength="25"
                                        value="<?php echo htmlspecialchars($apellido); ?>"
                                        oninput="this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '')">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-credit-card-2-front-fill"></i>
                                        DUI</label>
                                    <input autocomplete="off" type="text" name="DUI" class="form-control"
                                        placeholder="Ingrese número de DUI" required maxlength="10"
                                        value="<?php echo htmlspecialchars($DUI); ?>" pattern="\d{8}-\d{1}"
                                        title="Formato válido: 12345678-9"
                                        oninput="this.value = this.value.replace(/\D/g,'').slice(0,9); if(this.value.length>8){this.value=this.value.slice(0,8)+'-'+this.value.slice(8)}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-telephone-fill"></i>
                                        Teléfono</label>
                                    <input autocomplete="off" type="text" name="telefono" class="form-control"
                                        placeholder="Ingresar número de teléfono" required maxlength="9"
                                        value="<?php echo htmlspecialchars($telefono); ?>" pattern="\d{4}-\d{4}"
                                        title="Formato válido: 1234-5678"
                                        oninput="this.value=this.value.replace(/\D/g,'').slice(0,8); if(this.value.length>4){this.value=this.value.slice(0,4)+'-'+this.value.slice(4)}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-icon">
                                        <i class="bi bi-house-fill"></i> Dirección
                                    </label>
                                    <input autocomplete="off" type="text" name="direccion" class="form-control"
                                        placeholder="Ingresar Dirección" required
                                        value="<?php echo htmlspecialchars($direccion); ?>"
                                        oninput="this.value = this.value.replace(/[^A-Za-zÀ-ÿ0-9\s,\.#\-\/ºª]/g, '')">
                                </div>


                                <div class="col-12">
                                    <label class="form-label form-icon"><i class="bi bi-envelope-fill"></i> Correo</label>
                                    <input autocomplete="off" type="email" name="correo" class="form-control"
                                        placeholder="Ingresar correo electrónico" required
                                        value="<?php echo htmlspecialchars($correo); ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-icon"><i class="bi bi-key-fill"></i> Clave</label>
                                    <input autocomplete="off" type="password" name="clave" class="form-control"
                                        placeholder="Dejar en blanco para mantener la actual" minlength="8" maxlength="12"
                                        value="">
                                    <small class="form-text text-muted">Dejar en blanco si no desea cambiar la clave</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-person-gear"></i> Usuario</label>
                                    <select class="form-select" name="id_Usuario" required <?php echo ($id_Usuario == $empleado->obtenerIdAdministrador()) ? 'disabled' : ''; ?>>
                                        <option value="">Seleccione</option>
                                        <?php
                                        while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                                            $selected = ($id_Usuario == $row['id_Usuario']) ? 'selected' : '';
                                            echo "<option value='{$row['id_Usuario']}' $selected>{$row['rol']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <?php if ($id_Usuario == $empleado->obtenerIdAdministrador()): ?>
                                        <input type="hidden" name="id_Usuario" value="<?php echo $id_Usuario; ?>">
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label form-icon"><i class="bi bi-toggle-on"></i> Estado</label>
                                    <select class="form-select" name="estado" required <?php echo ($id_Usuario == $empleado->obtenerIdAdministrador()) ? 'disabled' : ''; ?>>
                                        <option value="">Seleccione</option>
                                        <option value="1" <?php echo ($estado == 1 ? 'selected' : ''); ?>>Alta</option>
                                        <option value="0" <?php echo ($estado == 0 ? 'selected' : ''); ?>>Baja</option>
                                    </select>
                                    <?php if ($id_Usuario == $empleado->obtenerIdAdministrador()): ?>
                                        <input type="hidden" name="estado" value="<?php echo $estado; ?>">
                                    <?php endif; ?>
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
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla -->
            <div class="col-md-8" id="tablaCol">
                <div class="table-section">
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de Empleados</div>
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
                                    <th>Accion</th>
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
                                                <?php echo ($row['estado'] == 1 ? 'Alta' : 'Baja'); ?>
                                            </span>
                                        </td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_empleado.php?id=<?php echo $row['id_Empleado']; ?>'">
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
            window.location.href = 'crear_empleado.php';
        });

        document.addEventListener('DOMContentLoaded', function () {
            const passInput = document.querySelector('input[name="clave"]');
            const form = document.getElementById('empleadoForm');
            const passFeedback = document.createElement('div');
            passFeedback.className = 'small mt-1';
            passInput.parentNode.appendChild(passFeedback);

            // Función para validar si la contraseña cumple todos los requisitos
            function validarContrasenaCompleta(pass) {
                // Si la contraseña está vacía, es válida (no se cambia)
                if (pass === '') {
                    return true;
                }

                const hasUpper = /[A-Z]/.test(pass);
                const hasLower = /[a-z]/.test(pass);
                const hasNumber = /[0-9]/.test(pass);
                const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pass);
                const hasLength = pass.length >= 8 && pass.length <= 12;

                return hasUpper && hasLower && hasNumber && hasSpecial && hasLength;
            }

            // Validación en tiempo real
            passInput.addEventListener('input', function () {
                const pass = this.value;

                // Si está vacío, ocultar el feedback
                if (pass === '') {
                    passFeedback.innerHTML = '';
                    return;
                }

                const hasUpper = /[A-Z]/.test(pass);
                const hasLower = /[a-z]/.test(pass);
                const hasNumber = /[0-9]/.test(pass);
                const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pass);
                const hasLength = pass.length >= 8 && pass.length <= 12;

                let message = '';
                message = `
            <div class="text-${hasLength ? 'success' : 'danger'}">✓ 8-12 caracteres</div>
            <div class="text-${hasUpper ? 'success' : 'danger'}">✓ Mayúscula</div>
            <div class="text-${hasLower ? 'success' : 'danger'}">✓ Minúscula</div>
            <div class="text-${hasNumber ? 'success' : 'danger'}">✓ Número</div>
            <div class="text-${hasSpecial ? 'success' : 'danger'}">✓ Carácter especial</div>
        `;
                passFeedback.innerHTML = message;
            });

            // Validar antes de enviar el formulario
            form.addEventListener('submit', function (e) {
                const pass = passInput.value;

                // Solo validar si se ingresó una contraseña (no está vacía)
                if (pass !== '' && !validarContrasenaCompleta(pass)) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Contraseña inválida',
                        text: 'La contraseña debe cumplir todos los requisitos: 8-12 caracteres, mayúscula, minúscula, número y carácter especial.',
                        confirmButtonColor: '#dc3545'
                    });

                    // Enfocar el campo de contraseña
                    passInput.focus();
                }
            });
        });

        // Mostrar SweetAlert y redireccionar después
        const message = "<?php echo $message; ?>";
        const duplicates = <?php echo json_encode($duplicates); ?>;

        if (message === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Empleado actualizado correctamente',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crear_empleado.php';
                }
            });
        } else if (message === 'error') {
            let errorMessage = 'No se pudo completar la actualización. ';

            if (duplicates.length > 0) {
                errorMessage += 'Los siguientes datos ya están registrados:\n\n';

                if (duplicates.includes('DUI')) errorMessage += '• Número de DUI\n';
                if (duplicates.includes('correo')) errorMessage += '• Correo electrónico\n';
                if (duplicates.includes('teléfono')) errorMessage += '• Número de teléfono\n';

                errorMessage += '\nPor favor, verifique la información.';
            } else {
                errorMessage += 'Ocurrió un error inesperado.';
            }

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