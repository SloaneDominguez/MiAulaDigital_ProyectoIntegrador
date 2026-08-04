<?php

/**
 * ==========================================
 * CONFIGURACIÓN SEGURA DE SESIONES
 * Mi Aula Digital
 * ==========================================
 */

if (session_status() === PHP_SESSION_NONE) {

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_name('MAD_SESSION');

    session_start();
}

/*
|--------------------------------------------------------------------------
| Tiempo máximo de inactividad
|--------------------------------------------------------------------------
*/

define('SESSION_TIMEOUT', 1800); // 30 minutos

if (isset($_SESSION['LAST_ACTIVITY'])) {

    if ((time() - $_SESSION['LAST_ACTIVITY']) > SESSION_TIMEOUT) {

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: ../index.php?error=sesion");
        exit;
    }
}

$_SESSION['LAST_ACTIVITY'] = time();

/*
|--------------------------------------------------------------------------
| Fingerprint del navegador
|--------------------------------------------------------------------------
*/

$fingerprint = hash(
    'sha256',
    ($_SERVER['HTTP_USER_AGENT'] ?? '') .
        ($_SERVER['REMOTE_ADDR'] ?? '')
);

if (!isset($_SESSION['fingerprint'])) {

    $_SESSION['fingerprint'] = $fingerprint;
} else {

    if ($_SESSION['fingerprint'] !== $fingerprint) {

        session_destroy();

        header("Location: ../index.php");
        exit;
    }
}
