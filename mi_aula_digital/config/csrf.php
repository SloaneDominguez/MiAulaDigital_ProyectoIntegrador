<?php

/**
 * ==========================================
 * TOKEN CSRF
 * ==========================================
 */

require_once "session.php";

/*
|--------------------------------------------------------------------------
| Generar Token
|--------------------------------------------------------------------------
*/

function generarTokenCSRF()
{
    if (empty($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/*
|--------------------------------------------------------------------------
| Imprimir input oculto
|--------------------------------------------------------------------------
*/

function campoCSRF()
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(generarTokenCSRF(), ENT_QUOTES, 'UTF-8') .
        '">';
}

/*
|--------------------------------------------------------------------------
| Validar token
|--------------------------------------------------------------------------
*/

function validarCSRF()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {

        return false;
    }

    if (!isset($_POST['csrf_token'])) {

        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    );
}
