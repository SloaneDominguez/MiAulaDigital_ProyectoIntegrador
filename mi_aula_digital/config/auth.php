<?php

require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/helpers.php";

/*
|--------------------------------------------------------------------------
| Regenerar sesión
|--------------------------------------------------------------------------
*/

function regenerarSesion(): void
{
    session_regenerate_id(true);
}

/*
|--------------------------------------------------------------------------
| Crear sesión
|--------------------------------------------------------------------------
*/

function crearSesion(array $usuario): void
{
    regenerarSesion();

    $_SESSION['id_usuario'] = $usuario['ID_Usuario'];
    $_SESSION['nombre'] = $usuario['Nombre'];
    $_SESSION['usuario'] = $usuario['Usuario'];
    $_SESSION['correo'] = $usuario['Correo'];
    $_SESSION['rol'] = $usuario['Rol'];
}

/*
|--------------------------------------------------------------------------
| Cerrar sesión
|--------------------------------------------------------------------------
*/

function cerrarSesion(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time()-42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/*
|--------------------------------------------------------------------------
| Verificar sesión
|--------------------------------------------------------------------------
*/

function verificarSesion(): void
{
    if (!isset($_SESSION['id_usuario'])) {

        header("Location: /index.php");

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Verificar Rol
|--------------------------------------------------------------------------
*/

function verificarRol(string $rol): void
{
    verificarSesion();

    if ($_SESSION['rol'] !== $rol) {

        cerrarSesion();

        header("Location: /index.php");

        exit;
    }
}