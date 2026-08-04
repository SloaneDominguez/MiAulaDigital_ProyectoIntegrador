<?php
session_start();
require_once("../../vendor/autoload.php");
require_once("../../config/conexion.php");
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_GET['grupo'])||empty($_GET['grupo'])) die("Grupo no seleccionado");
$idGrupo=(int)$_GET['grupo'];

$sqlGrupo="SELECT CONCAT(Grado,'° ',Grupo) AS NombreGrupo FROM grupo WHERE ID_Grupo=?";
$stmt=$conexion->prepare($sqlGrupo);
$stmt->bind_param("i",$idGrupo);
$stmt->execute();
$grupo=$stmt->get_result()->fetch_assoc();
if(!$grupo) die("Grupo no encontrado");

$sql="SELECT CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Alumno,a.CURP,t.Token_String,CONCAT(g.Grado,'° ',g.Grupo) AS Grupo,t.Fecha_Inicio,t.Fecha_Expiracion FROM alumno a LEFT JOIN tokens t ON a.ID_Alumno=t.ID_Alumno INNER JOIN grupo g ON a.ID_Grupo=g.ID_Grupo WHERE a.ID_Grupo=? ORDER BY a.Primer_Apellido,a.Nombre";
$stmt=$conexion->prepare($sql);
$stmt->bind_param("i",$idGrupo);
$stmt->execute();
$resultado=$stmt->get_result();

$excel=new Spreadsheet();
$hoja=$excel->getActiveSheet();
$hoja->setTitle("Tokens");
$hoja->mergeCells("A1:G1"); $hoja->setCellValue("A1","MI AULA DIGITAL");
$hoja->mergeCells("A2:G2"); $hoja->setCellValue("A2","Escuela Primaria General Cuauhtémoc");
$hoja->mergeCells("A3:G3"); $hoja->setCellValue("A3","REPORTE DE TOKENS POR GRUPO");
$hoja->setCellValue("A5","Grupo:"); $hoja->setCellValue("B5",$grupo['NombreGrupo']);
$hoja->setCellValue("A6","Fecha generación:"); $hoja->setCellValue("B6",date("d-m-Y H:i"));

$fila=8; $encabezados=["No.","Alumno","CURP","Token","Grupo","Fecha Inicio","Fecha Expiración"];
$columna="A"; foreach($encabezados as $e){$hoja->setCellValue($columna.$fila,$e);$columna++;}

$fila++; $i=1;
while($dato=$resultado->fetch_assoc()){
    $hoja->setCellValue("A".$fila,$i);
    $hoja->setCellValue("B".$fila,$dato['Alumno']);
    $hoja->setCellValue("C".$fila,$dato['CURP']);
    $hoja->setCellValue("D".$fila,$dato['Token_String']??"Sin token");
    $hoja->setCellValue("E".$fila,$dato['Grupo']);
    $hoja->setCellValue("F".$fila,$dato['Fecha_Inicio']);
    $hoja->setCellValue("G".$fila,$dato['Fecha_Expiracion']);
    $fila++; $i++;
}

$hoja->getStyle("A1:G3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$hoja->getStyle("A8:G8")->getFont()->setBold(true);
$hoja->getStyle("A8:G8")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
foreach(range('A','G') as $col) $hoja->getColumnDimension($col)->setAutoSize(true);
$hoja->setAutoFilter("A8:G".($fila-1));

$nombre="Tokens_".str_replace(" ","_",$grupo['NombreGrupo']).".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$nombre.'"');
$writer=new Xlsx($excel);
$writer->save("php://output");
exit;
