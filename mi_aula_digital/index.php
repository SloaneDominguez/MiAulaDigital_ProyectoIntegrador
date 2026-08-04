<?php
session_start();
if(isset($_SESSION['id_usuario'],$_SESSION['rol'])){
    switch($_SESSION['rol']){
        case 'Direccion': header("Location: admin/dashboard.php"); exit();
        case 'Docente': header("Location: docente/dashboard.php"); exit();
        case 'PadreTutor': header("Location: tutor/dashboard.php"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Aula Digital | Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <section class="login-box">
            <div class="login-header"><h1>Mi Aula Digital</h1><p>Ingresa a tu cuenta</p></div>
            <form id="loginForm" autocomplete="off">
                <input type="hidden" name="rol" id="rolSeleccionado" value="">
                <div class="form-group"><label id="labelUsuario">Usuario</label><input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required></div>
                <div class="form-group password-group">
                    <label id="labelPassword">Contraseña</label>
                    <div class="password-container"><input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required><i class="fa-solid fa-eye" id="togglePassword"></i></div>
                </div>
                <button type="submit" class="btn-login">Iniciar sesión</button>
            </form>
            <div class="login-footer"><div class="register-option" id="registerOption" style="display:none;"><a href="#" id="registerLink">¿No tienes cuenta? Regístrate aquí</a></div></div>
        </section>
        <section class="role-box">
            <div class="role-header"><h2>Tipo de usuario</h2><p>Selecciona cómo ingresarás</p></div>
            <div class="role-buttons">
                <button type="button" class="role-btn" data-role="Direccion"><span><i class="fa-solid fa-school"></i></span>Dirección</button>
                <button type="button" class="role-btn" data-role="Docente"><span><i class="fa-solid fa-chalkboard-user"></i></span>Docente</button>
                <button type="button" class="role-btn" data-role="PadreTutor"><span><i class="fa-solid fa-users"></i></span>Padre / Tutor</button>
            </div>
        </section>
    </div>
    <script src="assets/js/custom_alert.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
