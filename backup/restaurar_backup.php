<?php
// Configurar zona horaria
date_default_timezone_set('America/El_Salvador');

$message = '';

// Manejo POST de restauración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === 0) {
        $tmpFile = $_FILES['backup_file']['tmp_name'];
        $fileName = $_FILES['backup_file']['name'];

        $host = 'localhost';
        $user = 'root';
        $pass = ''; // Cambia si tu MySQL tiene contraseña
        $dbname = 'ferresys';

        $mysql = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        $command = "\"$mysql\" --user=$user --password=$pass --host=$host $dbname < \"$tmpFile\"";

        system($command, $output);

        $message = 'restored';
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
    <title>Restaurar Backup - Explorador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container mt-5">
    <div class="card p-4">
        <h3 class="card-title mb-4">Restaurar Backup desde el Explorador de Archivos</h3>
        <p>Haz clic en "Seleccionar Backup" para abrir el explorador de archivos y elegir tu backup .sql.</p>

        <!-- Botón que abre el modal -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#restoreModal">
            Seleccionar Backup
        </button>

        <!-- Modal -->
        <div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Selecciona el backup a restaurar</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="file" name="backup_file" accept=".sql" required class="form-control">
                            <small class="text-muted">Navega hasta <strong>C:\Backups Ferreteria Michapa</strong> y selecciona el archivo .sql que deseas restaurar.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="restore" class="btn btn-primary">Restaurar</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const message = "<?php echo $message; ?>";

if (message === 'restored') {
    Swal.fire({
        icon: 'success',
        title: 'Backup restaurado',
        text: 'La base de datos se restauró correctamente.',
        confirmButtonColor: '#1cbb8c'
    });
} else if (message === 'restore_error') {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo restaurar el backup. Verifica que seleccionaste un archivo válido.',
        confirmButtonColor: '#f06548'
    });
}
</script>
</body>
</html>
