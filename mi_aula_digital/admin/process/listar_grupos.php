<?php
include_once("../../config/conexion.php");

$sql = "SELECT ID_Grupo, Grado, Grupo FROM grupo";
$res = $conexion->query($sql);
$data = [];

while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
?>