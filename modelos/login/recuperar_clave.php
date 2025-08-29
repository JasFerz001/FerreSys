
<?php
// Aquí iría la lógica para manejar la recuperación de la contraseña.
// Por ejemplo:
// 1. Conectar a la base de datos.
// 2. Verificar si el correo electrónico enviado por POST existe.
// 3. Generar un token único y guardarlo en la base de datos junto con una fecha de expiración.
// 4. Enviar un correo electrónico al usuario con un enlace para restablecer la contraseña (ej: restablecer.php?token=...).
// 5. Mostrar un mensaje de confirmación al usuario.

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lógica de recuperación...
    // Por ahora, solo mostraremos un mensaje de ejemplo.
    $correo = $_POST['correo'];
    $message = 'Si una cuenta con el correo ' . htmlspecialchars($correo) . ' existe, hemos enviado un enlace para restablecer la contraseña.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="card shadow p-4" style="width: 25rem;">
            <h3 class="text-center mb-4">Recuperar Contraseña</h3>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted small">
                    Ingrese su correo electrónico y le enviaremos las instrucciones para restablecer su contraseña.
                </p>
            <?php endif; ?>

            <form method="post" action="recuperar_clave.php" class="mt-2">
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo Electrónico</label>
                    <input type="email" name="correo" id="correo" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Enviar Instrucciones</button>
                </div>
            </form>
            <div class="mt-3 text-center">
                <a href="login.php" class="btn btn-link">Volver a Iniciar Sesión</a>
            </div>
        </div>
    </div>