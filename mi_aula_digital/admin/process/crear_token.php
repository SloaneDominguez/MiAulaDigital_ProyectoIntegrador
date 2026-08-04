<?php
include_once("../../config/conexion.php");
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$grupo = (int)($data['grupo'] ?? 0);
$expiracion = $data['expiracion'] ?? '1 year';

if (!$grupo) {echo json_encode(["msg"=>"Grupo inválido"]); exit;}

$sql = "SELECT ID_Alumno,Nombre,Primer_Apellido,Segundo_Apellido,ID_Token FROM alumno WHERE ID_Grupo=$grupo";
$res = $conexion->query($sql);
if (!$res) {echo json_encode(["msg"=>"Error al obtener alumnos"]); exit;}

$creados=0; $omitidos=0;
while ($row=$res->fetch_assoc()) {
    $idAlumno=$row['ID_Alumno'];
    if (!empty($row['ID_Token'])) {$omitidos++; continue;}

    $nombreCompleto=trim($row['Nombre']." ".$row['Primer_Apellido']." ".($row['Segundo_Apellido']??''));
    $iniciales=""; foreach (explode(" ",$nombreCompleto) as $p) if (!empty($p)) $iniciales.=strtoupper(substr($p,0,1));
    $fechaCorta=date('Ymd'); $random=substr(bin2hex(random_bytes(2)),0,4);
    $token="{$grupo}-{$iniciales}-{$fechaCorta}-{$random}";
    $fechaExp=date('Y-m-d H:i:s',strtotime("+$expiracion"));

    $stmt=$conexion->prepare("INSERT INTO tokens (Token_String,Fecha_Inicio,Fecha_Expiracion,ID_Alumno) VALUES (?,NOW(),?,?)");
    $stmt->bind_param("ssi",$token,$fechaExp,$idAlumno);
    $stmt->execute();
    $idToken=$conexion->insert_id;

    $conexion->query("UPDATE alumno SET ID_Token=$idToken WHERE ID_Alumno=$idAlumno");
    $creados++;
}

echo json_encode(["msg"=>"Proceso terminado. Creados: $creados | Omitidos: $omitidos"]);
