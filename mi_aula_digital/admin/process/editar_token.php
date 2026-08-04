<?php
include_once("../../config/conexion.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD']==='GET') {
    $accion=$_GET['accion']??'';
    if ($accion==='obtener') {
        $id=(int)($_GET['id']??0);
        if(!$id){echo json_encode(["success"=>false,"msg"=>"Token inválido"]);exit;}
        $stmt=$conexion->prepare("SELECT t.ID_Token,t.Token_String,t.Fecha_Expiracion,CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Alumno FROM tokens t INNER JOIN alumno a ON t.ID_Alumno=a.ID_Alumno WHERE t.ID_Token=? LIMIT 1");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $resultado=$stmt->get_result();
        if($resultado->num_rows==0){echo json_encode(["success"=>false,"msg"=>"No se encontró el token"]);exit;}
        echo json_encode($resultado->fetch_assoc());exit;
    }
}

$data=json_decode(file_get_contents("php://input"),true);
$accion=$data['accion']??'';

switch($accion){
    case 'editar':
        $id=(int)($data['id']??0);
        $expiracion=$data['expiracion']??'1 year';
        if(!$id){echo json_encode(["success"=>false,"msg"=>"Token inválido"]);exit;}
        $stmt=$conexion->prepare("SELECT a.ID_Alumno,a.Nombre,a.Primer_Apellido,a.Segundo_Apellido,a.ID_Grupo FROM tokens t INNER JOIN alumno a ON t.ID_Alumno=a.ID_Alumno WHERE t.ID_Token=? LIMIT 1");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $resultado=$stmt->get_result();
        if($resultado->num_rows==0){echo json_encode(["success"=>false,"msg"=>"Alumno no encontrado"]);exit;}
        $alumno=$resultado->fetch_assoc();
        $nombreCompleto=trim($alumno['Nombre']." ".$alumno['Primer_Apellido']." ".($alumno['Segundo_Apellido']??''));
        $iniciales=""; foreach(explode(" ",$nombreCompleto) as $p) if(!empty($p)) $iniciales.=strtoupper(substr($p,0,1));
        $fechaCorta=date('Ymd'); $random=strtoupper(substr(bin2hex(random_bytes(2)),0,4));
        $nuevoToken=$alumno['ID_Grupo']."-".$iniciales."-".$fechaCorta."-".$random;
        $fechaExp=date('Y-m-d H:i:s',strtotime("+$expiracion"));
        $stmt=$conexion->prepare("UPDATE tokens SET Token_String=?,Fecha_Expiracion=? WHERE ID_Token=?");
        $stmt->bind_param("ssi",$nuevoToken,$fechaExp,$id);
        $stmt->execute();
        echo json_encode(["success"=>true,"msg"=>"Token generado correctamente.","token"=>$nuevoToken]);exit;

    case 'eliminar':
        $id=(int)($data['id']??0);
        if(!$id){echo json_encode(["success"=>false,"msg"=>"Token inválido"]);exit;}
        $stmt=$conexion->prepare("SELECT ID_Alumno FROM tokens WHERE ID_Token=? LIMIT 1");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $resultado=$stmt->get_result();
        if($resultado->num_rows==0){echo json_encode(["success"=>false,"msg"=>"Token no encontrado"]);exit;}
        $idAlumno=$resultado->fetch_assoc()['ID_Alumno'];
        $stmt=$conexion->prepare("UPDATE alumno SET ID_Token=NULL WHERE ID_Alumno=?");
        $stmt->bind_param("i",$idAlumno);
        $stmt->execute();
        $stmt=$conexion->prepare("DELETE FROM tokens WHERE ID_Token=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        echo json_encode(["success"=>true,"msg"=>"Token eliminado correctamente."]);exit;

    default:
        echo json_encode(["success"=>false,"msg"=>"Acción no válida."]);exit;
}
