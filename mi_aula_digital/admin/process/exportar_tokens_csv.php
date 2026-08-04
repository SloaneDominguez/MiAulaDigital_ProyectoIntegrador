<?php
session_start();
require_once("../../config/conexion.php");

if (!isset($_GET['grupo'])||empty($_GET['grupo'])) die("Grupo no seleccionado.");
$idGrupo=(int)$_GET['grupo'];

$sqlGrupo="SELECT CONCAT(Grado,'° ',Grupo) AS NombreGrupo FROM grupo WHERE ID_Grupo=?";
$stmtGrupo=$conexion->prepare($sqlGrupo);
$stmtGrupo->bind_param("i",$idGrupo);
$stmtGrupo->execute();
$grupo=$stmtGrupo->get_result()->fetch_assoc();
if(!$grupo) die("Grupo no encontrado.");

$sql="SELECT CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Alumno,a.CURP,t.Token_String,CONCAT(g.Grado,'° ',g.Grupo) AS Grupo,DATE_FORMAT(t.Fecha_Inicio,'%d-%m-%Y %H:%i') AS Fecha_Inicio,DATE_FORMAT(t.Fecha_Expiracion,'%d-%m-%Y') AS Fecha_Expiracion FROM alumno a LEFT JOIN tokens t ON a.ID_Alumno=t.ID_Alumno INNER JOIN grupo g ON a.ID_Grupo=g.ID_Grupo WHERE a.ID_Grupo=? ORDER BY a.Primer_Apellido,a.Nombre";
$stmt=$conexion->prepare($sql);
$stmt->bind_param("i",$idGrupo);
$stmt->execute();
$resultado=$stmt->get_result();

$archivo="Tokens_".str_replace(" ","_",$grupo['NombreGrupo']).".csv";
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$archivo\"");
echo "\xEF\xBB\xBF";
$csv=fopen("php://output","w");

fputcsv($csv,["MI AULA DIGITAL"]);
fputcsv($csv,["Escuela Primaria General Cuauhtémoc"]);
fputcsv($csv,["REPORTE DE TOKENS POR GRUPO"]);
fputcsv($csv,["Grupo:",$grupo['NombreGrupo']]);
fputcsv($csv,["Fecha de generación:",date("d-m-Y H:i")]);
fputcsv($csv,[]);
fputcsv($csv,["No.","Alumno","CURP","Token","Grupo","Fecha Inicio","Fecha Expiración"]);

$i=1;
while($fila=$resultado->fetch_assoc()){
    fputcsv($csv,[
        $i,
        $fila['Alumno'],
        $fila['CURP'],
        $fila['Token_String']?: "Sin token",
        $fila['Grupo'],
        $fila['Fecha_Inicio']?: "-",
        $fila['Fecha_Expiracion']?: "-"
    ]);
    $i++;
}
fclose($csv);
exit;
