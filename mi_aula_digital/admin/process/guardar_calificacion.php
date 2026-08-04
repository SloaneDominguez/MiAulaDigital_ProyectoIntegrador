<?php
include_once("../../config/conexion.php");

$accion = $_POST['accion'] ?? 'guardar';

if ($accion=="buscar") {
    $id_alumno=$_POST['id_alumno']; $bimestre=$_POST['bimestre'];
    $sql=$conexion->prepare("SELECT c.ID_Calificacion,c.ID_Campo,cf.Nombre_Campo,c.Calificacion_Numerica FROM calificacion c INNER JOIN campo_formativo cf ON c.ID_Campo=cf.ID_Campo WHERE c.ID_Alumno=? AND c.Bimestre=? ORDER BY c.ID_Campo");
    $sql->bind_param("ii",$id_alumno,$bimestre); $sql->execute();
    $res=$sql->get_result(); $datos=[];
    while($fila=$res->fetch_assoc()) $datos[]=$fila;
    echo json_encode($datos); exit();
}

if ($accion=="buscar_campo") {
    $id_alumno=$_POST['id_alumno']; $id_campo=$_POST['id_campo']; $bimestre=$_POST['bimestre'];
    $sql=$conexion->prepare("SELECT ID_Calificacion,Calificacion_Numerica FROM calificacion WHERE ID_Alumno=? AND ID_Campo=? AND Bimestre=? LIMIT 1");
    $sql->bind_param("iii",$id_alumno,$id_campo,$bimestre); $sql->execute();
    $resultado=$sql->get_result();
    if($fila=$resultado->fetch_assoc()){
        echo json_encode(["existe"=>true,"id_calificacion"=>$fila['ID_Calificacion'],"calificacion"=>$fila['Calificacion_Numerica']]);
    } else {
        echo json_encode(["existe"=>false,"id_calificacion"=>"","calificacion"=>""]);
    }
    exit();
}

$id_calificacion=$_POST['id_calificacion']??''; $id_alumno=$_POST['id_alumno']??''; $id_campo=$_POST['id_campo']??''; $bimestre=$_POST['bimestre']??''; $calificacion=$_POST['calificacion']??'';

if ($accion=="editar") {
    $sql=$conexion->prepare("UPDATE calificacion SET ID_Campo=?,Bimestre=?,Calificacion_Numerica=?,ID_Alumno=? WHERE ID_Calificacion=?");
    $sql->bind_param("isdii",$id_campo,$bimestre,$calificacion,$id_alumno,$id_calificacion);
    echo $sql->execute()?"Calificación actualizada correctamente.":"Error al actualizar la calificación."; exit();
}

if ($accion=="eliminar") {
    $sql=$conexion->prepare("DELETE FROM calificacion WHERE ID_Calificacion=?");
    $sql->bind_param("i",$id_calificacion);
    echo $sql->execute()?"Calificación eliminada correctamente.":"Error al eliminar la calificación."; exit();
}

$validar=$conexion->prepare("SELECT ID_Calificacion FROM calificacion WHERE ID_Alumno=? AND ID_Campo=? AND Bimestre=?");
$validar->bind_param("iis",$id_alumno,$id_campo,$bimestre); $validar->execute();
$resultado=$validar->get_result();
if($resultado->num_rows>0){
    echo "Ya existe una calificación registrada para este alumno en ese campo formativo y bimestre.";
    $validar->close(); $conexion->close(); exit();
}

$sql=$conexion->prepare("INSERT INTO calificacion (ID_Campo,Bimestre,Calificacion_Numerica,ID_Alumno) VALUES (?,?,?,?)");
$sql->bind_param("isdi",$id_campo,$bimestre,$calificacion,$id_alumno);
echo $sql->execute()?"Calificación registrada correctamente.":"Ocurrió un error al registrar la calificación.";
$sql->close(); $validar->close(); $conexion->close();
