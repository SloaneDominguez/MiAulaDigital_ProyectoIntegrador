<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro Tutor | Mi Aula Digital</title>

    <link rel="stylesheet" href="assets/css/registro_docente.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

</head>

<body>

    <div class="register-container">

        <div class="register-card">

            <div class="register-header">

                <h1>Registro de Tutor</h1>
                <p>
                    Completa el formulario para crear tu cuenta en
                    <strong>Mi Aula Digital</strong>.
                    Posteriormente Dirección asignará a los alumnos correspondientes.
                </p>
            </div>

            <form id="formRegistroTutor" autocomplete="off">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="nombre">Nombre completo</label>
                        <input type="text" id="nombre" name="nombre" minlength="5" maxlength="150" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" title="Solo letras y espacios." autocomplete="name" required>
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo electrónico</label>
                        <input type="email" id="correo" name="correo" maxlength="100" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" minlength="10" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" title="Ingresa un número de 10 dígitos." autocomplete="tel" required>
                    </div>

                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" minlength="4" maxlength="20" pattern="[A-Za-z0-9._]+" title="Solo letras, números, punto y guion bajo." autocomplete="username" required>
                    </div>

                    <div class="form-group">

                        <label for="password">Contraseña</label>
                        <div class="password-box">
                            <input type="password" id="password" name="password" minlength="8" maxlength="50" autocomplete="new-password" required>
                            <button type="button" class="toggle-password" data-target="password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>

                    </div>

                    <div class="form-group">

                        <label for="confirmar_password">Confirmar contraseña</label>
                        <div class="password-box">
                            <input type="password" id="confirmar_password" name="confirmar_password" minlength="8" maxlength="50" autocomplete="new-password" required>
                            <button type="button" class="toggle-password" data-target="confirmar_password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>

                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-register">
                        Crear cuenta
                    </button>
                </div>

                <div class="login-link">
                    ¿Ya tienes una cuenta?
                    <a href="index.php">Iniciar sesión</a>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/custom_alert.js"></script>
    <script src="assets/js/registro_tutor.js"></script>

</body>

</html>