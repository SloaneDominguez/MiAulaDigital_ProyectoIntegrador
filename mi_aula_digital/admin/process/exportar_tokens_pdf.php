<?php
session_start();
require_once("../../config/conexion.php");
require_once("../../fpdf/fpdf.php");

if (!isset($_GET['grupo'])||empty($_GET['grupo'])) die("Grupo no seleccionado.");
$idGrupo=(int)$_GET['grupo'];

$sqlGrupo="SELECT CONCAT(Grado,'° ',Grupo) AS NombreGrupo FROM grupo WHERE ID_Grupo=?";
$stmtGrupo=$conexion->prepare($sqlGrupo);
$stmtGrupo->bind_param("i",$idGrupo);
$stmtGrupo->execute();
$resultadoGrupo=$stmtGrupo->get_result();
if($resultadoGrupo->num_rows==0) die("Grupo no encontrado.");
$grupo=$resultadoGrupo->fetch_assoc();

$sql="SELECT CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Alumno,t.Token_String,CONCAT(g.Grado,'° ',g.Grupo) AS Grupo FROM alumno a LEFT JOIN tokens t ON a.ID_Alumno=t.ID_Alumno INNER JOIN grupo g ON a.ID_Grupo=g.ID_Grupo WHERE a.ID_Grupo=? ORDER BY a.Primer_Apellido,a.Segundo_Apellido,a.Nombre";
$stmt=$conexion->prepare($sql);
$stmt->bind_param("i",$idGrupo);
$stmt->execute();
$resultado=$stmt->get_result();

class PDF extends FPDF {
    function Header(){
        $logo="../../assets/img/escudo.jpeg";
        if(file_exists($logo)) $this->Image($logo,10,8,25,25);
        $this->SetFont("Arial","B",14);
        $this->Cell(0,10,utf8_decode("Escuela Primaria General Cuauhtémoc"),0,1,"C");
        $this->SetFont("Arial","B",12);
        $this->Cell(0,8,utf8_decode("Reporte de Tokens por Grupo"),0,1,"C");
        $this->Ln(5); $this->Line(10,38,200,38); $this->Ln(10);
    }
    function Footer(){
        $this->SetY(-15); $this->SetFont("Arial","I",8);
        $this->Cell(0,10,utf8_decode("Mi Aula Digital - Página ").$this->PageNo(),0,0,"C");
    }
}

$pdf=new PDF();
$pdf->AddPage();
$pdf->SetFont("Arial","",10);
$pdf->Cell(40,8,utf8_decode("Grupo:")); $pdf->Cell(50,8,utf8_decode($grupo['NombreGrupo']));
$pdf->Cell(40,8,utf8_decode("Fecha:")); $pdf->Cell(50,8,date("d/m/Y")); $pdf->Ln(12);

$pdf->SetFillColor(220,220,220);
$pdf->SetFont("Arial","B",10);
$pdf->Cell(15,8,"No.",1,0,"C",true);
$pdf->Cell(95,8,"Alumno",1,0,"C",true);
$pdf->Cell(70,8,"Token",1,1,"C",true);
$pdf->SetFont("Arial","",9);

$contador=1;
while($fila=$resultado->fetch_assoc()){
    $pdf->Cell(15,8,$contador,1,0,"C");
    $pdf->Cell(95,8,utf8_decode($fila['Alumno']),1);
    $pdf->Cell(70,8,$fila['Token_String']?: "Sin token",1,1,"C");
    $contador++;
}

$pdf->Ln(8);
$pdf->SetFont("Arial","B",10);
$pdf->Cell(0,8,utf8_decode("Total de alumnos: ").($contador-1),0,1);

$nombreArchivo="Tokens_".str_replace(" ","_",$grupo['NombreGrupo']).".pdf";
$pdf->Output("I",$nombreArchivo);
