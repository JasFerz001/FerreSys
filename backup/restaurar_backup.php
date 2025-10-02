<?php
// Configurar zona horaria
date_default_timezone_set('America/El_Salvador');

// Incluir la conexión
include_once __DIR__ . '/../conexion/conexion.php';
$conexion = new Conexion();
$db = $conexion->getConnection();

$message = '';

// Manejo POST de restauración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    if (
        isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === 0 &&
        isset($_POST['admin_password'])
    ) {
        $passwordIngresada = $_POST['admin_password'];

        // Buscar contraseña del administrador
        $sql = "SELECT e.clave
                FROM empleados e
                JOIN usuarios u ON e.id_Usuario = u.id_Usuario
                WHERE u.rol = 'Administrador' AND u.estado = 1 AND e.estado = 1
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($passwordIngresada, $admin['clave'])) {
            // Clave válida → ejecutar restauración
            $tmpFile = $_FILES['backup_file']['tmp_name'];

            $host = 'localhost';
            $user = 'root';
            $pass = ''; // cambia si tu MySQL tiene contraseña
            $dbname = 'ferresys';

            $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
            $command = "\"$mysql\" --user=$user --password=$pass --host=$host $dbname < \"$tmpFile\"";

            system($command, $output);

            $message = 'restored';
        } else {
            // Clave incorrecta
            $message = 'invalid';
        }
    } else {
        $message = 'restore_error';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurar Backup - Ferretería Michapa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h3 class="card-title mb-3 text-primary">
                <i class="bi bi-arrow-repeat"></i> Restaurar Backup
            </h3>
            <p class="text-muted">
                Selecciona el archivo <code>.sql</code> que deseas restaurar y escribe la <strong>contraseña del Administrador</strong>. 
                Por defecto, los backups se guardan en: <code>C:\Backups Ferreteria Michapa</code>.
            </p>

            <!-- Botón que abre el modal -->
            <button type="button" 
                    class="btn btn-primary btn-sm px-3" 
                    data-bs-toggle="modal" 
                    data-bs-target="#restoreModal">
                <i class="bi bi-folder2-open"></i> Seleccionar Backup
            </button>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-sm border-0 rounded-3">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Selecciona el backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Selección de archivo -->
                    <div class="mb-3">
                        <label for="backupFile" class="form-label">Archivo .sql</label>
                        <input type="file" id="backupFile" name="backup_file" accept=".sql" required class="form-control">
                    </div>

                    <!-- Contraseña administrador -->
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Contraseña Administrador</label>
                        <input type="password" id="admin_password" name="admin_password" 
                               class="form-control form-control-sm" placeholder="Escribe tu contraseña" required>
                    </div>

                    <small class="text-muted">
                        Carpeta por defecto de respaldos: <strong>C:\Backups Ferreteria Michapa</strong>  
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="restoreBtn" name="restore" class="btn btn-success btn-sm" disabled>
                        <i class="bi bi-cloud-upload"></i> Restaurar
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const message = "<?php echo $message; ?>";

// Alertas SweetAlert2
if (message === 'restored') {
    // Éxito → mostrar alerta y cerrar modal
    Swal.fire({
        icon: 'success',
        title: 'Backup restaurado',
        text: 'La base de datos se restauró correctamente.',
        confirmButtonColor: '#1cbb8c'
    }).then(() => {
        // Cerrar modal si estaba abierto
        const modalEl = document.getElementById('restoreModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    });
} else if (message === 'restore_error') {
    // Error en restauración
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo restaurar el backup. Verifica el archivo seleccionado.',
        confirmButtonColor: '#f06548'
    }).then(() => {
        // Reabrir el modal si hubo error
        const modalEl = document.getElementById('restoreModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    });
} else if (message === 'invalid') {
    // Contraseña incorrecta
    Swal.fire({
        icon: 'warning',
        title: 'Contraseña incorrecta',
        text: 'No es posible restaurar el backup.',
        confirmButtonColor: '#f7b84b'
    }).then(() => {
        // Reabrir modal automáticamente
        const modalEl = document.getElementById('restoreModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    });
}

// Habilitar botón Restaurar cuando se seleccione un archivo
document.getElementById('backupFile').addEventListener('change', function() {
    const restoreBtn = document.getElementById('restoreBtn');
    restoreBtn.disabled = !this.files.length;
});
</script>


</body>
</html>
