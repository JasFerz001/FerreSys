<?php
session_start();

include_once '../../conexion/conexion.php';
include_once '../usuario/usuario.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$usuario = new Usuario($db);

// Revisar si estamos editando
$editUser = null;
if (isset($_GET['id'])) {
    $id_Usuario = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_Usuario = :id");
    $stmt->bindParam(':id', $id_Usuario);
    $stmt->execute();
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Limpiar sesión si no es POST ni actualización
if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET['message'])) {
    unset($_SESSION['old_rol'], $_SESSION['old_estado']);
}

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $rol = strtoupper(trim($_POST['rol'])); // Convertir a mayúsculas
    $estadoSeleccionado = trim($_POST['estado']);

    $_SESSION['old_rol'] = $rol;
    $_SESSION['old_estado'] = $estadoSeleccionado;

    if (!$editUser && $estadoSeleccionado == "0") {
        // Solo al crear, no permitir estado Baja
        header("Location: crear_usuario.php?message=estado_baja");
        exit();
    }


    if ($editUser) {
        // Modo actualizar
        $usuario->id_Usuario = $editUser['id_Usuario'];
        $usuario->rol = $rol;
        $usuario->estado = (int)$estadoSeleccionado;

        // Verificar duplicado como en crear
        $stmtCheck = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = :rol AND id_Usuario != :id");
        $stmtCheck->bindParam(':rol', $rol);
        $stmtCheck->bindParam(':id', $editUser['id_Usuario']);
        $stmtCheck->execute();
        $rowCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($rowCheck['total'] > 0) {
            header("Location: crear_usuario.php?message=rol_duplicado&id=" . $editUser['id_Usuario']);
            exit();
        }

        // Si no hay duplicado, actualizar
        if ($usuario->actualizar()) {
            unset($_SESSION['old_rol'], $_SESSION['old_estado']);
            header("Location: crear_usuario.php?message=update_success");
            exit();
        }
    } else {
        // Modo crear
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = :rol");
        $stmt->bindParam(':rol', $rol);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['total'] > 0) {
            header("Location: crear_usuario.php?message=rol_duplicado");
            exit();
        }

        $usuario->rol = $rol;
        $usuario->estado = 1;

        if ($usuario->crear()) {
            unset($_SESSION['old_rol'], $_SESSION['old_estado']);
            header("Location: crear_usuario.php?message=success");
            exit();
        }
    }

    header("Location: crear_usuario.php?message=error&id=" . ($editUser['id_Usuario'] ?? ''));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editUser ? 'Actualizar Usuario' : 'Registro de Usuario'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/usuario.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-box">
        <?php if (isset($_GET['message'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if ($_GET['message'] == 'success'): ?>
                        Swal.fire('Éxito', 'Usuario creado correctamente.', 'success');
                    <?php elseif ($_GET['message'] == 'update_success'): ?>
                        Swal.fire('Éxito', 'Usuario actualizado correctamente.', 'success');
                    <?php elseif ($_GET['message'] == 'error'): ?>
                        Swal.fire('Error', 'Error al procesar el usuario.', 'error');
                    <?php elseif ($_GET['message'] == 'rol_duplicado'): ?>
                        Swal.fire('Atención', 'El rol "<?php echo htmlspecialchars($_SESSION['old_rol'] ?? ''); ?>" ya existe.', 'warning');
                    <?php elseif ($_GET['message'] == 'estado_baja'): ?>
                        Swal.fire('Atención', 'No se puede guardar usuario con estado de Baja.', 'warning');
                    <?php endif; ?>
                });
            </script>
        <?php endif; ?>

        <div class="row">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-title"><?php echo $editUser ? 'Actualizar Usuario' : 'Registro de Usuario'; ?></div>
                <form id="usuarioForm" method="post" action="crear_usuario.php<?php echo $editUser ? '?id=' . $editUser['id_Usuario'] : ''; ?>">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person-gear"></i> Rol</label>
                        <input type="text" class="form-control" name="rol" id="rol"
                            value="<?php echo htmlspecialchars($_SESSION['old_rol'] ?? $editUser['rol'] ?? ''); ?>"
                            placeholder="Ingrese el rol" autocomplete="off" required>
                        <small id="rol-error" class="text-danger" style="display:none;">Solo se permiten letras y espacios</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-toggle-on"></i> Estado</label>
                        <div>
                            <?php $estado = $_SESSION['old_estado'] ?? $editUser['estado'] ?? 1; ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="estado" value="1" <?php echo ($estado == 1 ? 'checked' : ''); ?> required>
                                <label class="form-check-label">Alta</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="estado" value="0" <?php echo ($estado == 0 ? 'checked' : ''); ?> required>
                                <label class="form-check-label">Baja</label>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-gradient px-5 py-2"><?php echo $editUser ? 'Actualizar' : 'Guardar'; ?></button>
                        <?php if ($editUser): ?>
                            <a href="crear_usuario.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Tabla -->
            <div class="col-md-8 mt-4 mt-md-0">
                <div class="card-title">Usuarios Registrados</div>
                <div class="table-responsive">
                    <table class="table table-bordered text-center align-middle" id="tablaUsuarios">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $db->prepare("SELECT * FROM usuarios ORDER BY rol ASC");
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['rol']); ?></td>
                                    <td><?php echo strtoupper($row['estado'] ? 'Alta' : 'Baja'); ?></td>
                                    <td>
                                        <a href="crear_usuario.php?id=<?php echo $row['id_Usuario']; ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i> Actualizar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Validación en tiempo real SOLO letras y espacios -->
    <script>
        const inputRol = document.getElementById('rol');
        const errorRol = document.getElementById('rol-error');
        const btnGuardar = document.querySelector('button[type="submit"]');

        function validarRol() {
            const valor = inputRol.value;
            const esValido = /^[a-zA-Z\s]*$/.test(valor);

            if (!esValido) {
                errorRol.style.display = 'inline';
                inputRol.classList.add('is-invalid');
                btnGuardar.disabled = true;
            } else {
                errorRol.style.display = 'none';
                inputRol.classList.remove('is-invalid');
                btnGuardar.disabled = false;
            }
        }

        inputRol.addEventListener('input', validarRol);
        document.addEventListener('DOMContentLoaded', validarRol);
    </script>

    <script>
        let tabla = new DataTable('#tablaUsuarios', {
            pageLength: 5,
            lengthMenu: [
                [5, 10, 25, -1],
                [5, 10, 25, "Todos"]
            ],
            language: {
                emptyTable: "No hay usuarios registrados aún",
                info: "Total: _TOTAL_ usuarios",
                infoEmpty: "No hay usuarios para mostrar",
                infoFiltered: "(filtrado de _MAX_ usuarios en total)",
                lengthMenu: "Mostrar _MENU_ usuarios",
                loadingRecords: "Cargando...",
                processing: "Procesando...",
                search: " Buscar:",
                zeroRecords: "No se encontraron coincidencias",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            }
        });
    </script>
</body>

</html>