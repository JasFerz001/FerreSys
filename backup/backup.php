<?php
// Configurar zona horaria de El Salvador
date_default_timezone_set('America/El_Salvador');

// Incluir la conexión
include_once __DIR__ . '/../conexion/conexion.php';

$conexion = new Conexion();
$db = $conexion->getConnection();

$message = '';
$deleteMessage = '';

// Carpeta temporal dentro del proyecto
$backupDir = __DIR__ . '/backups/';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Carpeta segura en disco C (válida en cualquier máquina)
$centralDir = 'C:\\Backups Ferreteria Michapa\\';
if (!file_exists($centralDir)) {
    mkdir($centralDir, 0755, true);
}

// Función para generar backup
function generarBackup($backupDir, $centralDir)
{
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // Cambia si tu MySQL tiene contraseña
    $dbname = 'ferresys';

    // Fecha y hora en formato 12h con AM/PM
    $fecha = date('d M Y h-i-s A');
    $backupFile = $backupDir . "ferresys $fecha.sql";

    $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    $command = "\"$mysqldump\" --user=$user --password=$pass --host=$host $dbname > \"$backupFile\"";

    system($command, $output);

    // Si se creó en temporal, lo copiamos al disco C
    if (file_exists($backupFile)) {
        $centralFile = $centralDir . basename($backupFile);
        copy($backupFile, $centralFile);
        return $centralFile;
    }
    return false;
}

// Manejo del POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Eliminar backup temporal
    if (isset($_POST['delete']) && !empty($_POST['backup_file'])) {
        $fileName = basename($_POST['backup_file']);
        $filePath = $backupDir . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
            $deleteMessage = "Backup '$fileName' eliminado correctamente.";
        } else {
            $deleteMessage = "No se pudo eliminar el backup.";
        }
    }

    // Generar nuevo backup
    elseif (isset($_POST['confirm_backup'])) {
        $confirmText = trim($_POST['confirm_backup']);
        if ($confirmText === 'CREAR') {
            $backupFile = generarBackup($backupDir, $centralDir);
            $message = $backupFile ? 'success' : 'error';
        } else {
            $message = 'invalid';
        }
    }
}

// Listar backups temporales ordenados por fecha (más recientes primero)
$backups = array_filter(scandir($backupDir), function ($file) use ($backupDir) {
    return !in_array($file, ['.', '..']);
});
usort($backups, function ($a, $b) use ($backupDir) {
    return filemtime($backupDir . $b) - filemtime($backupDir . $a);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Backup - Ferretería Michapa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="card p-4">
            <h3 class="card-title mb-4">Generar Backup de la Base de Datos</h3>
            <form method="post" id="backupForm">
                <div class="mb-3">
                    <label for="confirm_backup" class="form-label">Escribe <strong>CREAR</strong> para generar el backup:</label>
                    <input type="text" class="form-control" id="confirm_backup" name="confirm_backup" placeholder="CREAR" maxlength="6" required>
                </div>
                <button type="submit" class="btn btn-success">Generar Backup</button>
            </form>

            <hr>

            <h5>Backups existentes</h5>
            <ul class="list-group">
                <?php if (count($backups) > 0): ?>
                    <?php foreach ($backups as $backup): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($backup); ?>
                            <div>
                                <!-- Eliminar -->
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup); ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item">No hay backups disponibles.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <script>
        const message = "<?php echo $message; ?>";
        const deleteMessage = "<?php echo $deleteMessage; ?>";

        if (message === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Backup generado',
                text: 'El backup se guardó correctamente en C:\\Backups Ferreteria Michapa\\',
                confirmButtonColor: '#1cbb8c'
            });
        } else if (message === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo generar el backup. Verifique permisos.',
                confirmButtonColor: '#f06548'
            });
        } else if (message === 'invalid') {
            Swal.fire({
                icon: 'warning',
                title: 'Palabra incorrecta',
                text: 'Debes escribir exactamente la palabra CREAR en mayúsculas para generar el backup.',
                confirmButtonColor: '#f7b84b'
            });
        }

        // Mensaje después de eliminar
        if (deleteMessage) {
            Swal.fire({
                icon: 'success',
                title: 'Backup eliminado',
                text: deleteMessage,
                confirmButtonColor: '#f06548'
            });
        }

        // Forzar mayúsculas en el input
        const inputBackup = document.getElementById('confirm_backup');
        inputBackup.addEventListener('input', () => {
            inputBackup.value = inputBackup.value.toUpperCase();
        });
    </script>
</body>
</html>
