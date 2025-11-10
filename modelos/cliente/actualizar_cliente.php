<?php
//verificar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['id_Empleado']) || empty($_SESSION['id_Empleado'])) {
    header("Location: ../acceso/acceso_denegado.php");
    exit();
}
include_once '../../conexion/conexion.php';
include_once '../cliente/cliente.php';
include_once '../bitacora/bitacora.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$cliente = new Cliente($db);
$bitacora = new Bitacora($db);

$message = '';
$duplicates = [];
// Variables para mantener los valores del formulario
$nombre = $apellido = $dui = $direccion = $correo = '';

// Función para formatear texto con primera letra mayúscula y el resto minúsculas
function formatearTexto($texto)
{
    // Convertir todo a minúsculas primero y eliminar espacios en blanco
    $texto = strtolower(trim($texto));
    // Convertir la primera letra de cada palabra a mayúscula
    return ucwords($texto);
}

// Obtener el ID del cliente a actualizar
if (isset($_GET['id'])) {
    $id_Cliente = (int) $_GET['id'];
    $cliente->id_Cliente = $id_Cliente;

    // Cargar datos del cliente
    if ($cliente->leerPorId()) {
        $nombre = $cliente->nombre;
        $apellido = $cliente->apellido;
        $dui = $cliente->dui;
        $direccion = $cliente->direccion;
        $correo = $cliente->correo;
    } else {
        $message = 'not_found';
    }
}

// Manejo del POST para actualizar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Guardamos los valores para mantenerlos en caso de error
    $id_Cliente = (int) $_POST['id_Cliente'];
    $nombre = formatearTexto(trim($_POST['nombre']));  // Aplicar formato
    $apellido = formatearTexto(trim($_POST['apellido']));  // Aplicar formato
    $dui = trim($_POST['dui']);
    $direccion = formatearTexto(trim($_POST['direccion']));  // Aplicar formato
    $correo = strtolower(trim($_POST['correo']));


    // Asignar valores al objeto
    $cliente->id_Cliente = $id_Cliente;
    $cliente->nombre = $nombre;
    $cliente->apellido = $apellido;
    $cliente->dui = $dui;
    $cliente->direccion = $direccion;
    $cliente->correo = $correo;

    // Actualizar el cliente
    $result = $cliente->actualizar();

    if ($result['success']) {
        $message = 'success';

          // Registrar en bitácora solo si el empleado está logueado
        if (!empty($_SESSION['id_Empleado'])) {
            $bitacora->id_Empleado = $_SESSION['id_Empleado'];
            $bitacora->accion = "Actualizar Cliente";
            $bitacora->descripcion = htmlspecialchars_decode("Se actualizo el cliente '$nombre' en la base de datos.");
            $bitacora->registrar();
        }
        

    } else {
        $message = 'error';
        $duplicates = $result['duplicates'];
    }
}

// Leer todos los clientes
$stmt = $cliente->leer();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/cliente.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-fluid px-3">
        <div class="row form-table-container d-flex">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-form h-100">
                    <div class="card-title">Actualizar cliente</div>
                    <?php if ($message == 'not_found'): ?>
                        <div class="alert alert-danger" role="alert">
                            cliente no encontrado.
                        </div>
                    <?php else: ?>
                        <form id="clienteForm" method="post"
                            action="actualizar_cliente.php?id=<?php echo $id_Cliente; ?>">
                            <input type="hidden" name="id_Cliente" id="id_Cliente" value="<?php echo $id_Cliente; ?>">
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
                                <div class="col-md-12">
                                    <label class="form-label form-icon"><i class="bi bi-credit-card-2-front-fill"></i>
                                        dui</label>
                                    <input autocomplete="off" type="text" name="dui" class="form-control"
                                        placeholder="Ingrese número de dui" required maxlength="10"
                                        value="<?php echo htmlspecialchars($dui); ?>" pattern="\d{8}-\d{1}"
                                        title="Formato válido: 12345678-9"
                                        oninput="this.value = this.value.replace(/\D/g,'').slice(0,9); if(this.value.length>8){this.value=this.value.slice(0,8)+'-'+this.value.slice(8)}">
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
                                <div class="text-muted small mb-3">* Todos los campos son obligatorios</div>
                                <div class="col-12 text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
                                    <button type="submit" class="btn btn-success flex-grow-1 flex-sm-grow-0"
                                        style="max-width: 200px;">Guardar</button>
                                    <button id="btnCancelar" type="button"
                                        class="btn btn-warning flex-grow-1 flex-sm-grow-0"
                                        style="max-width: 200px;"
                                        onclick="window.location.href='crear_cliente.php'">Cancelar</button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tabla -->
            <div class="col-md-8" id="tablaCol">
                <div class="table-section">
                    <div class="card-title" id="tablaTitle" style="cursor: pointer;">Lista de clientes</div>
                    <div class="table-responsive">
                        <table id="tablaclientes" class="table table-bordered text-center align-middle">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>DUI</th>
                                    <th>Dirección</th>
                                    <th>Correo</th>
                                    <th>Acción</th>
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
                                        <td><?php echo htmlspecialchars($row['direccion']); ?></td>
                                        <td><?php echo htmlspecialchars($row['correo']); ?></td>
                                        <td class="actions-column">
                                            <button class="btn btn-sm btn-outline-warning me-1 p-1"
                                                style="width: 40px; height: 40px;"
                                                onclick="location.href='actualizar_cliente.php?id=<?php echo $row['id_Cliente']; ?>'">
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('clienteForm');

            // Validar antes de enviar el formulario
            form.addEventListener('submit', function(e) {
                const dui = document.getElementById('dui').value.trim();
                const correo = document.getElementById('correo').value.trim();

                if (!nombre || !apellido || !dui || !direccion || !correo) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Campos obligatorios',
                        text: 'Todos los campos son obligatorios.',
                        confirmButtonColor: '#dc3545'
                    });
                }

            });
        });

        document.getElementById('btnCancelar').addEventListener('click', () => {
            // Vaciar todos los campos input del formulario
            document.querySelectorAll('#clienteForm input').forEach(input => input.value = '');
        });

        // Mostrar SweetAlert con mensajes específicos
        const message = "<?php echo $message; ?>";
        const duplicates = <?php echo json_encode($duplicates); ?>;

        if (message === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Cliente actualizado correctamente',
                icon: 'success',
                iconColor: '#1cbb8c',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Aceptar',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'crear_cliente.php';
                }
            });
        } else if (message === 'error') {
            let errorMessage = 'No se pudo completar el registro. ';

            if (duplicates.length > 0) {
                errorMessage += 'Los siguientes datos ya están registrados:\n\n';

                if (duplicates.includes('DUI')) errorMessage += '• Número de DUI\n';
                if (duplicates.includes('correo')) errorMessage += '• Correo electrónico\n';

                errorMessage += '\nPor favor, verifique la información.';
            } else {
                errorMessage += 'Ocurrió un error inesperado.';
            }

            Swal.fire({
                title: 'Error de registro',
                text: errorMessage,
                icon: 'error',
                iconColor: '#f06548',
                confirmButtonColor: '#3b7ddd',
                confirmButtonText: 'Entendido'
            });
        }


        $(document).ready(function() {
            $('#tablaClientes').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
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