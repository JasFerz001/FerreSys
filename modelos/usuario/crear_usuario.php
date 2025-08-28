<?php
include_once '../../conexion/conexion.php';
include_once '../usuario/usuario.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$usuario = new Usuario($db);


$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $rol = trim($_POST['rol']);
    $estadoSeleccionado = trim($_POST['estado']); 

    // Validación: no permitir crear usuarios con estado Baja
    if ($estadoSeleccionado == "0") {
        header("Location: crear_usuario.php?message=estado_baja");
        exit();
    }

    // Validación: solo un Administrador o un Empleado
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = :rol");
    $stmt->bindParam(':rol', $rol);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row['total'] > 0) {
        if ($rol === "Administrador") {
            header("Location: crear_usuario.php?message=admin_existe");
        } elseif ($rol === "Empleado") {
            header("Location: crear_usuario.php?message=empleado_existe");
        }
        exit();
    }

    // Crear usuario con estado Alta
    $usuario->rol = $rol;
    $usuario->estado = 1; // Alta

    if ($usuario->crear()) {
        if ($rol === "Administrador") {
            // Si se crea el admin, redirigir a crear empleado
            header("Location: ../empleado/crear_empleado.php?primera_vez=1");
            exit();
        } else {
            // Para otros roles, mostrar mensaje de éxito
            header("Location: crear_usuario.php?message=success");
            exit();
        }
    } else {
        header("Location: crear_usuario.php?message=error");
        exit();
    }
} ?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/usuario.css">
    <!-- Estilos de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Mensajes -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <div class="container-box">
        <?php if (isset($_GET['message'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if ($_GET['message'] == 'success'): ?>
                        Swal.fire('Éxito', 'Usuario creado correctamente.', 'success');
                    <?php elseif ($_GET['message'] == 'error'): ?>
                        Swal.fire('Error', 'Error al crear el usuario.', 'error');
                    <?php elseif ($_GET['message'] == 'admin_existe'): ?>
                        Swal.fire('Atención', 'Ya existe un administrador. No se puede crear otro.', 'warning');
                    <?php elseif ($_GET['message'] == 'empleado_existe'): ?>
                        Swal.fire('Atención', 'Ya existe un empleado. No se puede crear otro.', 'warning');
                    <?php elseif ($_GET['message'] == 'estado_baja'): ?>
                        Swal.fire('Atención', 'No se puede crear un usuario con estado Baja.', 'warning');
                    <?php endif; ?>
                });
            </script>
        <?php endif; ?>


        <div class="row">
            <!-- Formulario -->
            <div class="col-md-4">
                <div class="card-title">Registro de Usuario</div>
                <form id="usuarioForm" method="post" action="crear_usuario.php">
                    <input type="hidden" name="id_Usuario" value="<?php echo $id_Usuario ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person-gear"></i> Rol</label>
                        <select class="form-select" name="rol" required>
                            <option value="">Seleccione</option>
                            <option value="Administrador" <?php echo (isset($rol) && $rol == "Administrador" ? 'selected' : ''); ?>>Administrador</option>
                            <option value="Empleado" <?php echo (isset($rol) && $rol == "Empleado" ? 'selected' : ''); ?>>Empleado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-toggle-on"></i> Estado</label>
                        <select class="form-select" name="estado" required>
                            <option value="">Seleccione</option>
                            <option value="1" <?php echo (isset($estado) && $estado == 1 ? 'selected' : ''); ?>>Alta</option>
                            <option value="0" <?php echo (isset($estado) && $estado == 0 ? 'selected' : ''); ?>>Baja</option>
                        </select>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-gradient px-5 py-2">Guardar</button>
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
                            // Conexión y consulta de usuarios
                            $stmt = $db->prepare("SELECT * FROM usuarios ORDER BY rol ASC");
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['rol']); ?></td>
                                    <td><?php echo ($row['estado'] ? 'Alta' : 'Baja'); ?></td>
                                    <td>
                                        <a href="actualizar_usuario.php?id=<?php echo $row['id_Usuario']; ?>" class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i> Actualizar
                                        </a>
                                        <a href="dar_baja_usuario.php?id=<?php echo $row['id_Usuario']; ?>" class="btn btn-danger btn-sm">
                                            Dar de baja
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let tabla = new DataTable('#tablaUsuarios', {
            pageLength: 5, // Mostrar 5 registros por página
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