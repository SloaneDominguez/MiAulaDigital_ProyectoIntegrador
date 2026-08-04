<?php

$servidor = "localhost";
// También puedes usar 127.0.0.1

$usuario = "root";
$password = "";
$base_datos = "mi_aula_digital";

$conexion = new mysqli(
    $servidor,
    $usuario,
    $password,
    $base_datos
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");