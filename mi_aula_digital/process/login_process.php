<?php
require __DIR__.'/../app/bootstrap.php';
try {
    validateCsrf();
    authEnsureSchema();

    $active=currentUser();
    if($active){
        throw new DomainException(
            'Ya hay una sesión activa en este navegador como '.($active['Nombre']??$active['Usuario']).'. Cierra esa sesión antes de entrar con otra cuenta. Para probar Director y Docente al mismo tiempo, usa otro navegador o una ventana de incógnito.',
            409
        );
    }

    $username=textInput('usuario',100);
    $password=passwordInput('password',1,200);

    // Solo los intentos fallidos cuentan para el bloqueo de acceso.
    loginThrottleGuard('login',$username,10);
    loginThrottleGuard('login-ip','',40);

    $u=one('SELECT * FROM usuario WHERE (Usuario=? OR LOWER(Correo)=LOWER(?)) AND activo=1 LIMIT 1',[$username,$username]);
    $hash=$u['Contrasena']??'$2y$10$7eqJtq98hPqEX7fNZaFWoOe5DUQqvZ5O5E8eF7Up9fPJ0fK4U8QYy';
    $valid=password_verify($password,$hash);

    if(!$u||!$valid){
        loginThrottleFail('login',$username);
        loginThrottleFail('login-ip','');
        throw new DomainException('Usuario o contraseña incorrectos.',401);
    }
    if(!authAllowsLocal($u)){
        throw new DomainException('Esta cuenta está configurada para iniciar con Google.',403);
    }

    profile($u); // No inicia una sesión sin perfil válido.
    loginThrottleReset('login',$username);
    loginThrottleReset('login-ip','');

    $_SESSION=[];
    session_regenerate_id(true);
    $_SESSION['id_usuario']=(int)$u['ID_Usuario'];
    $_SESSION['sesion_version']=(int)$u['sesion_version'];
    $_SESSION['rol']=$u['Rol'];
    $_SESSION['csrf']=bin2hex(random_bytes(32));
    $_SESSION['last_activity']=time();

    if(password_needs_rehash($u['Contrasena'],PASSWORD_DEFAULT)){
        query('UPDATE usuario SET Contrasena=? WHERE ID_Usuario=?',[
            password_hash($password,PASSWORD_DEFAULT),
            (int)$u['ID_Usuario']
        ]);
    }

    reply([
        'ok'=>true,
        'redirect'=>url($u['cambiar_clave']?'cuenta.php':homeFor($u))
    ]);
} catch(Throwable $ex) {
    errorReply($ex);
}
