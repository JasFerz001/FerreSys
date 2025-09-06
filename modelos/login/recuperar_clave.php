<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que el autoloader de Composer esté incluido.
// La ruta puede variar dependiendo de la estructura de tu proyecto.
require '../../vendor/autoload.php'; 

// Incluir archivos necesarios
include_once '../../conexion/conexion.php';

$message = '';
$message_type = 'info'; // Puede ser 'info', 'success', o 'danger'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $message = 'Por favor, ingrese una dirección de correo electrónico válida.';
        $message_type = 'danger';
    } else {
        $conexion = new Conexion();
        $db = $conexion->getConnection();

        // 1. Verificar si el correo electrónico existe en la tabla de empleados y está activo
        $query_empleado = "SELECT id_Empleado, nombre FROM empleados WHERE correo = :correo AND estado = 1 LIMIT 1";
        $stmt_empleado = $db->prepare($query_empleado);
        $stmt_empleado->bindParam(':correo', $correo);
        $stmt_empleado->execute();

        if ($stmt_empleado->rowCount() > 0) {
            $empleado = $stmt_empleado->fetch(PDO::FETCH_ASSOC);
            $id_Empleado = $empleado['id_Empleado'];
            $nombre_empleado = $empleado['nombre'];

            // 2. Generar un token único y seguro
            $token = bin2hex(random_bytes(32));

            // 3. Definir una fecha de expiración (1 hora desde ahora)
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // 4. Guardar el token en la base de datos
            $query_reset = "INSERT INTO recuperacion_clave (id_Empleado, token, expires_at) VALUES (:id_Empleado, :token, :expires_at)";
            $stmt_reset = $db->prepare($query_reset);
            $stmt_reset->bindParam(':id_Empleado', $id_Empleado);
            $stmt_reset->bindParam(':token', $token);
            $stmt_reset->bindParam(':expires_at', $expires_at);
            
            if ($stmt_reset->execute()) {
                // 5. Enviar el correo electrónico con PHPMailer
                $reset_link = "http://localhost/FerreSys/modelos/login/restablecer_clave.php?token=" . $token;
                
                $mail = new PHPMailer(true);

                try {
                    // Configuración del servidor SMTP
                    //$mail->SMTPDebug = SMTP::DEBUG_SERVER; // Habilita la salida de depuración detallada
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // REEMPLAZA con tu servidor SMTP (ej. smtp.gmail.com)
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'guillermohasael123@gmail.com'; // REEMPLAZA con tu correo
                    $mail->Password   = 'lhsy zhyq unxj fort'; // REEMPLAZA con tu contraseña o contraseña de aplicación
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet    = 'UTF-8';

                    // Remitente y Destinatarios
                    $mail->setFrom('no-reply@ferresys.com', 'Soporte FerreSys');
                    $mail->addAddress($correo, $nombre_empleado);

                    // Contenido del correo
                    $mail->isHTML(true);
                    $mail->Subject = 'Restablecimiento de Contraseña - FerreSys';
                    $mail->Body    = "
                        <p>Hola $nombre_empleado,</p>
                        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en FerreSys.</p>
                        <p>Para continuar, haz clic en el siguiente enlace:</p>
                        <p><a href='$reset_link' style='padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Restablecer Contraseña</a></p>
                        <p>Si el botón no funciona, copia y pega la siguiente URL en tu navegador:</p>
                        <p><code>$reset_link</code></p>
                        <p>Este enlace es válido por 1 hora. Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
                        <br>
                        <p>Saludos,<br>El equipo de FerreSys</p>";
                    $mail->AltBody = "Hola $nombre_empleado,\n\nCopia y pega el siguiente enlace en tu navegador para restablecer tu contraseña: $reset_link\n\nEl enlace expirará en 1 hora.";

                    $mail->send();
                    $message = 'Si una cuenta con ese correo existe, hemos enviado las instrucciones para restablecer la contraseña.';
                    $message_type = 'success';

                } catch (Exception $e) {
                    // En caso de error, no reveles detalles al usuario. Regístralo en un log.
                    // error_log("Error al enviar correo: " . $mail->ErrorInfo);
                    $message = 'No se pudieron enviar las instrucciones. Por favor, inténtalo más tarde.';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Hubo un error al procesar tu solicitud. Inténtalo de nuevo.';
                $message_type = 'danger';
            }
        } else {
            // Si el correo no existe, mostramos el mismo mensaje genérico por seguridad.
            $message = 'Si una cuenta con ese correo existe, hemos enviado las instrucciones para restablecer la contraseña.';
            $message_type = 'success';
        }
    }
}
?> 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="card shadow p-4" style="width: 25rem;">
            <h3 class="text-center mb-4">Recuperar Contraseña</h3>
            
            <?php if (!empty($_POST)): // Mostrar el mensaje solo después de enviar el formulario ?>
                <div class="alert alert-<?php echo $message_type; ?>">
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
</body>
</html>