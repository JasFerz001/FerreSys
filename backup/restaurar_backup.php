<?php
// Configurar zona horaria
date_default_timezone_set('America/El_Salvador');

$message = '';

// Manejo POST de restauración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === 0) {
        $tmpFile = $_FILES['backup_file']['tmp_name'];

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h3 class="card-title mb-3 text-primary">
                <i class="bi bi-arrow-repeat"></i> Restaurar Backup
            </h3>
            <p class="text-muted">
                Selecciona el archivo <code>.sql</code> que deseas restaurar. 
                Asegúrate de que se encuentre en 
                <strong>C:\Backups Ferreteria Michapa</strong>.
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
                    <input type="file" id="backupFile" name="backup_file" accept=".sql" required class="form-control">
                    <small class="text-muted">
                        Carpeta donde se guardan los respaldos: <strong>C:\Backups Ferreteria Michapa</strong>  
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

// Habilitar botón Restaurar cuando se seleccione un archivo
document.getElementById('backupFile').addEventListener('change', function() {
    const restoreBtn = document.getElementById('restoreBtn');
    restoreBtn.disabled = !this.files.length;
});
</script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

</body>
</html>
