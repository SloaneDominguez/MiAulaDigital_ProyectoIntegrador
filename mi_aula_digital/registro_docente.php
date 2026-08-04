<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Docente | Mi Aula Digital</title>
    <link rel="stylesheet" href="assets/css/registro_docente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Registro de Docente</h1>
                <p>Completa el formulario para solicitar acceso a <strong>Mi Aula Digital</strong>. Tu solicitud será revisada por Dirección antes de ser aprobada.</p>
            </div>
            <form id="formRegistroDocente" autocomplete="off">
                <div class="form-grid">
                    <div class="form-group"><label for="nombre">Nombre(s)</label><input type="text" id="nombre" name="nombre" maxlength="100" required></div>
                    <div class="form-group"><label for="primer_apellido">Primer apellido</label><input type="text" id="primer_apellido" name="primer_apellido" maxlength="100" required></div>
                    <div class="form-group"><label for="segundo_apellido">Segundo apellido</label><input type="text" id="segundo_apellido" name="segundo_apellido" maxlength="100"></div>
                    <div class="form-group"><label for="correo">Correo electrónico</label><input type="email" id="correo" name="correo" maxlength="100" required></div>
                    <div class="form-group"><label for="usuario">Usuario</label><input type="text" id="usuario" name="usuario" maxlength="50" required></div>
                    <div class="form-group"><label for="password">Contraseña</label>
                        <div class="password-box">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password" data-target="password"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div class="form-group confirm-password"><label for="confirmar_password">Confirmar contraseña</label>
                    <div class="password-box">
                        <input type="password" id="confirmar_password" name="confirmar_password" required>
                        <button type="button" class="toggle-password" data-target="confirmar_password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <div class="form-actions"><button type="submit" class="btn-register"><i class="fa-solid fa-paper-plane"></i>Enviar solicitud</button></div>
                <div class="login-link">¿Ya tienes una cuenta? <a href="index.php">Iniciar sesión</a></div>
            </form>
        </div>
    </div>
    <script src="assets/js/custom_alert.js"></script>
    <script src="assets/js/registro_docente.js"></script>
</body>

</html>