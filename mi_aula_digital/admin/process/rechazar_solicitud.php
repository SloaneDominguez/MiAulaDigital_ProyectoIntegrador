<?php
include_once("../../config/conexion.php");

if ($_SERVER["REQUEST_METHOD"]!="POST") exit("Acceso no permitido");

$id=(int)$_POST["id"];

$stmt=mysqli_prepare($conexion,"UPDATE solicitud_acceso SET Estado='Rechazada',Fecha_Revision=NOW() WHERE ID_Solicitud=? AND Estado='Pendiente'");
mysqli_stmt_bind_param($stmt,"i",$id);

echo mysqli_stmt_execute($stmt) ? "OK" : "Ocurrió un error al rechazar la solicitud.";
