<?php
session_start();
include_once '../../conexion/conexion.php';

$message = '';
$message_type = 'info';
$token_valido = false;
$token = $_GET['token'] ?? '';

// Verificar si el token existe y es válido
if (!empty($token)) {
    $conexion = new Conexion();
    $db = $conexion->getConnection();
    
    // Verificar token y que no haya expirado
    $query = "SELECT rc.*, e.nombre, e.correo 
              FROM recuperacion_clave rc 
              INNER JOIN empleados e ON rc.id_Empleado = e.id_Empleado 
              WHERE rc.token = :token AND rc.expires_at > NOW() AND e.estado = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $token_valido = true;
        $id_Empleado = $token_data['id_Empleado'];
        $nombre_empleado = $token_data['nombre'];
        
        // Procesar el formulario de cambio de contraseña
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nueva_clave = $_POST['nueva_clave'];
            $confirmar_clave = $_POST['confirmar_clave'];
            
            // Validar que las contraseñas coincidan
            if ($nueva_clave !== $confirmar_clave) {
                $message = 'Las contraseñas no coinciden.';
                $message_type = 'danger';
            } 
            // Validar longitud de la contraseña
            elseif (strlen($nueva_clave) < 8 || strlen($nueva_clave) > 12) {
                $message = 'La contraseña debe tener entre 8 y 12 caracteres.';
                $message_type = 'danger';
            } else {
                // Actualizar la contraseña en la base de datos
                $clave_encriptada = password_hash($nueva_clave, PASSWORD_DEFAULT);
                
                $update_query = "UPDATE empleados SET clave = :clave WHERE id_Empleado = :id_Empleado";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':clave', $clave_encriptada);
                $update_stmt->bindParam(':id_Empleado', $id_Empleado, PDO::PARAM_INT);
                
                if ($update_stmt->execute()) {
                    // Eliminar el token usado
                    $delete_query = "DELETE FROM recuperacion_clave WHERE token = :token";
                    $delete_stmt = $db->prepare($delete_query);
                    $delete_stmt->bindParam(':token', $token);
                    $delete_stmt->execute();
                    
                    $message = 'Contraseña restablecida exitosamente. Será redirigido al login en 5 segundos.';
                    $message_type = 'success';
                    
                    // Redirigir después de 5 segundos
                    header("refresh:5;url=login.php");
                } else {
                    $message = 'Error al actualizar la contraseña. Inténtelo de nuevo.';
                    $message_type = 'danger';
                }
            }
        }
    } else {
        $message = 'El enlace de restablecimiento es inválido o ha expirado.';
        $message_type = 'danger';
    }
} else {
    $message = 'Token de restablecimiento no proporcionado.';
    $message_type = 'danger';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../css/restablecer.css" rel="stylesheet">
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <h3><i class="fas fa-lock me-2"></i>Restablecer Contraseña</h3>
        </div>
        
        <div class="password-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                    <?php if ($message_type === 'success'): ?>
                        <div class="mt-2">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            Redirigiendo al login...
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($token_valido && $message_type !== 'success'): ?>
                <p class="text-center">Hola <strong><?php echo htmlspecialchars($nombre_empleado); ?></strong>, ingresa tu nueva contraseña.</p>
                
                <form method="post" action="restablecer_clave.php?token=<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="nueva_clave" id="nueva_clave" class="form-control" 
                                   placeholder="Mínimo 8 caracteres" required minlength="8" maxlength="12"
                                   oninput="checkPasswordStrength(this.value)">
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <small class="form-text text-muted">La contraseña debe tener entre 8 y 12 caracteres.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                            <input type="password" name="confirmar_clave" id="confirmar_clave" class="form-control" 
                                   placeholder="Repite tu contraseña" required minlength="8" maxlength="12"
                                   oninput="checkPasswordMatch()">
                        </div>
                        <small id="passwordMatch" class="form-text"></small>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-reset">Restablecer Contraseña</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <a href="login.php" class="btn btn-link backgroundColor: #2c3e50;">Volver al Login</a>
                </div>
            <?php elseif (!$token_valido): ?>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary backgroundColor: #2c3e50;">Volver al Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]+/)) strength += 25;
            if (password.match(/[A-Z]+/)) strength += 25;
            if (password.match(/[0-9]+/)) strength += 25;
            
            // Actualizar barra de fortaleza
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
        }
        
        function checkPasswordMatch() {
            const nuevaClave = document.getElementById('nueva_clave').value;
            const confirmarClave = document.getElementById('confirmar_clave').value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirmarClave.length > 0) {
                if (nuevaClave === confirmarClave) {
                    matchText.textContent = 'Las contraseñas coinciden.';
                    matchText.style.color = '#28a745';
                } else {
                    matchText.textContent = 'Las contraseñas no coinciden.';
                    matchText.style.color = '#dc3545';
                }
            } else {
                matchText.textContent = '';
            }
        }
    </script>
</body>
</html>