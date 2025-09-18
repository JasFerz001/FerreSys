<?php
session_start();

include_once '../../conexion/conexion.php';
include_once '../usuario/usuario.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$usuario = new Usuario($db);

// Revisar si estamos editando
$editUser = null;
$id_Usuario = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_Usuario > 0) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_Usuario = :id");
    $stmt->bindParam(':id', $id_Usuario);
    $stmt->execute();
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Limpiar sesión si no es POST ni actualización
if ($_SERVER["REQUEST_METHOD"] !== "POST" && !isset($_GET['message'])) {
    unset($_SESSION['old_rol'], $_SESSION['old_estado']);
}

// Función para formatear texto con primera letra mayúscula y el resto minúsculas
function formatearTexto($texto)
{
    // Convertir todo a minúsculas primero y eliminar espacios en blanco
    $texto = strtolower(trim($texto));
    // Convertir la primera letra de cada palabra a mayúscula
    return ucwords($texto);
}

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rol = formatearTexto(trim($_POST['rol']));
    $estadoSeleccionado = formatearTexto(trim($_POST['estado']));

    $_SESSION['old_rol'] = $rol;
    $_SESSION['old_estado'] = $estadoSeleccionado;

    // Validar si estamos editando o creando
    if ($editUser) {
        // MODO ACTUALIZACIÓN
        // Verificar duplicado (excluyendo el usuario actual)
        $stmtCheck = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = :rol AND id_Usuario != :id");
        $stmtCheck->bindParam(':rol', $rol);
        $stmtCheck->bindParam(':id', $id_Usuario);
        $stmtCheck->execute();
        $rowCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($rowCheck['total'] > 0) {
            header("Location: crear_usuario.php?message=rol_duplicado&id=" . $id_Usuario);
            exit();
        }

        // Actualizar usuario
        $usuario->id_Usuario = $id_Usuario;
        $usuario->rol = $rol;
        $usuario->estado = (int)$estadoSeleccionado;

        if ($usuario->actualizar()) {
            unset($_SESSION['old_rol'], $_SESSION['old_estado']);
            header("Location: crear_usuario.php?message=update_success");
            exit();
        } else {
            header("Location: crear_usuario.php?message=error&id=" . $id_Usuario);
            exit();
        }
    } else {
        // MODO CREACIÓN
        if ($estadoSeleccionado == "0") {
            header("Location: crear_usuario.php?message=estado_baja");
            exit();
        }

        // Verificar duplicado
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = :rol");
        $stmt->bindParam(':rol', $rol);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['total'] > 0) {
            header("Location: crear_usuario.php?message=rol_duplicado");
            exit();
        }

        // Crear usuario
        $usuario->rol = $rol;
        $usuario->estado = 1;

        if ($usuario->crear()) {
            unset($_SESSION['old_rol'], $_SESSION['old_estado']);

            if ($rol === "administrador" || $rol === "Administrador" || $rol === "ADMINISTRADOR") { // Asegúrate que coincida con mayúsculas
                header("Location: ../empleado/crear_empleado.php?primera_vez=1");
                exit();
            } else {
                header("Location: crear_usuario.php?message=success");
                exit();
            }
        } else {
            header("Location: crear_usuario.php?message=error");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/usuario.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if (isset($_GET['message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['message'] == 'success'): ?>
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Usuario creado correctamente.',
                        icon: 'success',
                        iconColor: '#1cbb8c',
                        confirmButtonColor: '#3b7ddd',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.href = 'crear_usuario.php';
                    });
                <?php elseif ($_GET['message'] == 'update_success'): ?>
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Usuario actualizado correctamente.',
                        icon: 'success',
                        iconColor: '#1cbb8c',
                        confirmButtonColor: '#3b7ddd',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        window.location.href = 'crear_usuario.php';
                    });
                <?php elseif ($_GET['message'] == 'error'): ?>
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al procesar el usuario.',
                        icon: 'error',
                        iconColor: '#f06548',
                        confirmButtonColor: '#3b7ddd',
                        confirmButtonText: 'Aceptar'
                    });
                <?php elseif ($_GET['message'] == 'rol_duplicado'): ?>
                    Swal.fire({
                        title: 'Atención',
                        text: 'El rol "<?php echo htmlspecialchars($_SESSION['old_rol'] ?? ''); ?>" ya existe.',
                        icon: 'warning',
                        iconColor: '#f1c40f',
                        confirmButtonColor: '#3b7ddd',
                        confirmButtonText: 'Aceptar'
                    });
                <?php elseif ($_GET['message'] == 'estado_baja'): ?>
                    Swal.fire({
                        title: 'Atención',
                        text: 'No se puede crear usuario con estado de Baja.',
                        icon: 'warning',
                        iconColor: '#f1c40f',
                        confirmButtonColor: '#3b7ddd',
                        confirmButtonText: 'Aceptar'
                    });
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

    <div class="container-box">
        <!-- Formulario -->
        <div class="card-form">
            <div class="card-title"><?php echo $editUser ? 'Actualizar Usuario' : 'Registro de Usuario'; ?></div>
            <form id="usuarioForm" method="post" action="crear_usuario.php<?php echo $editUser ? '?id=' . $id_Usuario : ''; ?>">
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-person-gear"></i> Rol</label>
                    <input type="text" class="form-control" name="rol" id="rol"
                        value="<?php echo htmlspecialchars($_SESSION['old_rol'] ?? $editUser['rol'] ?? ''); ?>"
                        placeholder="Ingrese el rol" autocomplete="off" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-toggle-on"></i> Estado</label>
                    <div>
                        <?php
                        $estado = $_SESSION['old_estado'] ?? ($editUser ? $editUser['estado'] : 1);
                        ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="estado" value="1"
                                <?php echo ($estado == 1 ? 'checked' : ''); ?>
                                <?php echo (!$editUser ? 'required' : ''); ?>>
                            <label class="form-check-label">Alta</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="estado" value="0"
                                <?php echo ($estado == 0 ? 'checked' : ''); ?>
                                <?php echo (!$editUser ? 'required' : ''); ?>>
                            <label class="form-check-label">Baja</label>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <?php if ($editUser): ?>
                        <a href="crear_usuario.php" class="btn btn-warning px-4">Cancelar</a>
                    <?php else: ?>
                        <button type="reset" class="btn btn-warning px-4">Cancelar</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success px-4"><?php echo $editUser ? 'Actualizar' : 'Guardar'; ?></button>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="table-container">
            <div class="table-box">
            <div class="card-title">Lista de Usuarios</div>
                <div class="table-responsive">
                    <table id="tablaUsuarios" class="table text-center align-middle">
                        <thead>
                            <tr>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acción</th>
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
                                    <td>
                                        <span class="badge <?php echo ($row['estado'] == 1 ? 'badge-success' : 'badge-danger'); ?>">
                                            <?php echo ($row['estado'] == 1 ? 'Alta' : 'Baja'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="crear_usuario.php?id=<?php echo $row['id_Usuario']; ?>"
                                            class="btn btn-sm btn-outline-warning btn-cuadrado me-1">
                                            <i class="bi bi-pencil"></i>
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

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tablaUsuarios').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 3,
                "lengthMenu": [3,4,5],
                "searching": true,
                "info": true
            });
        });
    </script>
    <script>
        const rolInput = document.getElementById('rol');

        rolInput.addEventListener('input', function() {
            // Permite solo letras y espacios
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        });
    </script>
</body>

</html>