<?php
include_once("../../config/conexion.php");
if($_SERVER["REQUEST_METHOD"]!="POST"){exit("Acceso no permitido");}
$id=(int)$_POST["id"];
$conexion->begin_transaction();
try{
    $consulta=mysqli_prepare($conexion,"SELECT * FROM solicitud_acceso WHERE ID_Solicitud=? AND Estado='Pendiente'");
    mysqli_stmt_bind_param($consulta,"i",$id);
    mysqli_stmt_execute($consulta);
    $resultado=mysqli_stmt_get_result($consulta);
    if(mysqli_num_rows($resultado)==0){throw new Exception("Solicitud no encontrada.");}
    $solicitud=mysqli_fetch_assoc($resultado);
    $rol="Docente";
    $insertUsuario=mysqli_prepare($conexion,"INSERT INTO usuario (Nombre,Usuario,Correo,Contrasena,Rol) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($insertUsuario,"sssss",$solicitud["Nombre"],$solicitud["Usuario"],$solicitud["Correo"],$solicitud["Contrasena"],$rol);
    mysqli_stmt_execute($insertUsuario);
    $idUsuario=mysqli_insert_id($conexion);
    $grupo=NULL;
    $insertDocente=mysqli_prepare($conexion,"INSERT INTO docente (Nombre,Primer_Apellido,Segundo_Apellido,ID_Usuario,ID_Grupo_Asignado) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($insertDocente,"sssii",$solicitud["Nombre"],$solicitud["Primer_Apellido"],$solicitud["Segundo_Apellido"],$idUsuario,$grupo);
    mysqli_stmt_execute($insertDocente);
    $actualizar=mysqli_prepare($conexion,"UPDATE solicitud_acceso SET Estado='Aprobada',Fecha_Revision=NOW() WHERE ID_Solicitud=?");
    mysqli_stmt_bind_param($actualizar,"i",$id);
    mysqli_stmt_execute($actualizar);
    $conexion->commit();
    echo "OK";
}catch(Exception $e){
    $conexion->rollback();
    echo $e->getMessage();
}
?>
