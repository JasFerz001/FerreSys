<?php
// Configurar zona horaria de El Salvador
date_default_timezone_set('America/El_Salvador');

// Iniciar sesión (necesario para leer $_SESSION)
session_start();

// Incluir conexión y modelo de bitácora
include_once __DIR__ . '/../conexion/conexion.php';
include_once __DIR__ . '/../modelos/bitacora/bitacora.php';

$conexion = new Conexion();
$db = $conexion->getConnection();
$bitacora = new Bitacora($db);

$message = '';

// Carpeta segura en disco C 
$centralDir = 'C:\\Backups Ferreteria Michapa\\';
if (!file_exists($centralDir)) {
    mkdir($centralDir, 0755, true);
}

// Función para generar backup con nombre único
function generarBackup($centralDir)
{
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // Cambia si tu MySQL tiene contraseña
    $dbname = 'ferresys';

    // Nombre único usando fecha y hora
    $fecha = date('Ymd_His'); // Formato: 20250930_144530
    $backupFile = $centralDir . "ferresys_" . $fecha . ".sql";

    $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    $command = "\"$mysqldump\" --user=$user --password=$pass --host=$host $dbname > \"$backupFile\"";

    // Ejecutar y esperar resultado
    system($command, $output);

    return file_exists($backupFile) ? $backupFile : false;
}

// Manejo del POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_password'])) {
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
            // Contraseña válida → generar backup
            $backupFile = generarBackup($centralDir);
            if ($backupFile) {
                $message = 'success';

                // Registrar en bitácora SI Y SOLO SI existe id_Empleado en sesión
                if (isset($_SESSION['id_Empleado']) && !empty($_SESSION['id_Empleado'])) {
                    $idEmpleadoSesion = (int) $_SESSION['id_Empleado']; // casteo seguro a int
                    $nombreArchivo = basename($backupFile);

                    $bitacora->id_Empleado = $idEmpleadoSesion;
                    $bitacora->accion = "Respaldo de Base de Datos";
                    $bitacora->descripcion = "Se generó un respaldo de la base de datos llamado '$nombreArchivo' exitosamente.";

                    // envolver en try/catch para que un fallo en bitácora no rompa la funcionalidad
                    try {
                        $bitacora->registrar();
                    } catch (Throwable $e) {
                        // opcional: loguear en un archivo de logs si lo deseas
                        error_log("Error al registrar bitácora de backup: " . $e->getMessage());
                    }
                } else {
                    // no hay sesión o id de empleado -> no registrar bitácora
                    error_log("Backup generado pero no se registró en bitácora: no hay id_Empleado en sesión.");
                }
            } else {
                $message = 'error';
            }
        } else {
            // Contraseña incorrecta
            $message = 'invalid';
        }
    }
}

// Listar backups en C:\Backups Ferreteria Michapa (más recientes primero)
$backups = [];
if (is_dir($centralDir)) {
    $files = array_values(array_filter(scandir($centralDir), function ($file) use ($centralDir) {
        // incluir solo archivos .sql y omitir . y ..
        return !in_array($file, ['.', '..']) && is_file($centralDir . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    }));

    usort($files, function ($a, $b) use ($centralDir) {
        return filemtime($centralDir . $b) - filemtime($centralDir . $a);
    });

    $backups = $files;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Backup - Ferretería Michapa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Input más pequeño y ancho fijo */
        .small-input {
            width: 200px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">
                <h3 class="card-title mb-3 text-success">
                    <i class="bi bi-database-down"></i> Generar Backup
                </h3>
                <p class="text-muted">
                    Ingresa la <strong>contraseña del Administrador</strong> para generar un respaldo de la base de
                    datos en:
                    <code>C:\Backups Ferreteria Michapa</code>
                </p>

                <!-- Formulario -->
                <form method="post" id="backupForm" class="mb-4 row g-2 align-items-end">
                    <div class="mb-3 col-auto">
                        <label for="admin_password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control form-control-sm small-input" id="admin_password"
                            name="admin_password" placeholder="Escribe tu contraseña" required>
                    </div>
                    <div class="mb-3 col-auto">
                        <button type="submit" id="backupBtn" class="btn btn-success btn-sm px-3">
                            <i class="bi bi-cloud-arrow-down"></i> Generar Backup
                        </button>
                    </div>
                </form>

                <hr>

                <!-- Lista de backups -->
                <h5 class="text-primary"><i class="bi bi-folder"></i> Backups existentes</h5>
                <ul class="list-group list-group-flush">
                    <?php if (count($backups) > 0): ?>
                        <?php foreach ($backups as $backup): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <i class="bi bi-file-earmark-text"></i>
                                <span><?php echo htmlspecialchars($backup); ?></span>
                                <span class="badge bg-secondary rounded-pill">
                                    <?php echo date("d/m/Y h:i:s A", filemtime($centralDir . $backup)); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No hay backups disponibles.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>
        const message = "<?php echo $message; ?>";

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
                title: 'Contraseña incorrecta',
                text: 'No es posible generar el backup.',
                confirmButtonColor: '#f7b84b'
            });
        }
    </script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</body>

</html>