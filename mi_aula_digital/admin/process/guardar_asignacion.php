<?php
include_once '../../config/conexion.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){exit('Acceso no permitido.');}
$accion=$_POST['accion']??'';
switch($accion){
  case 'crear':
    $grado=trim($_POST['grado']??'');$grupo=strtoupper(trim($_POST['grupo']??''));
    if($grado===''||$grupo===''){exit('Complete todos los campos.');}
    $stmt=$conexion->prepare("SELECT ID_Grupo FROM grupo WHERE Grado=? AND Grupo=?");
    $stmt->bind_param("ss",$grado,$grupo);$stmt->execute();
    if($stmt->get_result()->num_rows>0){exit('Ese grupo ya existe.');}
    $stmt=$conexion->prepare("INSERT INTO grupo(Grado,Grupo) VALUES(?,?)");
    $stmt->bind_param("ss",$grado,$grupo);
    echo $stmt->execute()?"Grupo creado correctamente.":"No fue posible crear el grupo.";
    break;

  case 'editar':
    $idGrupo=intval($_POST['id_grupo']??0);$grado=trim($_POST['grado']??'');$grupo=strtoupper(trim($_POST['grupo']??''));$idDocente=intval($_POST['id_docente']??0);
    if($idGrupo<=0){exit("Grupo inválido.");}
    $stmt=$conexion->prepare("SELECT ID_Grupo FROM grupo WHERE Grado=? AND Grupo=? AND ID_Grupo<>?");
    $stmt->bind_param("ssi",$grado,$grupo,$idGrupo);$stmt->execute();
    if($stmt->get_result()->num_rows>0){exit("Ya existe ese grupo.");}
    $conexion->begin_transaction();
    try{
      $stmt=$conexion->prepare("UPDATE grupo SET Grado=?,Grupo=? WHERE ID_Grupo=?");
      $stmt->bind_param("ssi",$grado,$grupo,$idGrupo);$stmt->execute();
      $stmt=$conexion->prepare("UPDATE docente SET ID_Grupo_Asignado=NULL WHERE ID_Grupo_Asignado=?");
      $stmt->bind_param("i",$idGrupo);$stmt->execute();
      if($idDocente>0){
        $stmt=$conexion->prepare("UPDATE docente SET ID_Grupo_Asignado=NULL WHERE ID_Docente=?");
        $stmt->bind_param("i",$idDocente);$stmt->execute();
        $stmt=$conexion->prepare("UPDATE docente SET ID_Grupo_Asignado=? WHERE ID_Docente=?");
        $stmt->bind_param("ii",$idGrupo,$idDocente);$stmt->execute();
      }
      $conexion->commit();echo "Grupo actualizado correctamente.";
    }catch(Exception $e){$conexion->rollback();echo "No fue posible actualizar el grupo.";}
    break;

  case 'eliminar':
    $idGrupo=intval($_POST['id_grupo']??0);
    if($idGrupo<=0){exit("Grupo inválido.");}
    $conexion->begin_transaction();
    try{
      $stmt=$conexion->prepare("UPDATE docente SET ID_Grupo_Asignado=NULL WHERE ID_Grupo_Asignado=?");
      $stmt->bind_param("i",$idGrupo);$stmt->execute();
      $stmt=$conexion->prepare("DELETE FROM grupo WHERE ID_Grupo=?");
      $stmt->bind_param("i",$idGrupo);$stmt->execute();
      $conexion->commit();echo "Grupo eliminado correctamente.";
    }catch(Exception $e){$conexion->rollback();echo "No fue posible eliminar el grupo.";}
    break;

  case 'asignar':
    $idDocente=intval($_POST['id_docente']??0);$idGrupo=intval($_POST['id_grupo']??0);
    if($idDocente<=0){exit("Docente inválido.");}
    if($idGrupo<=0){exit("Seleccione un grupo.");}
    $conexion->begin_transaction();
    try{
      $stmt=$conexion->prepare("UPDATE docente SET ID_Grupo_Asignado=NULL WHERE ID_Docente=?");
      $stmt->bind_param("i",$idDocente);$stmt->execute();
      $stmt=$conexion->prepare("UPDATE docente SET ID_Grupo_Asignado=NULL WHERE ID_Grupo_Asignado=?");
      $stmt->bind_param("i",$idGrupo);$stmt->execute();
      $stmt=$conexion->prepare("UPDATE docente SET ID_Grupo_Asignado=? WHERE ID_Docente=?");
      $stmt->bind_param("ii",$idGrupo,$idDocente);$stmt->execute();
      $conexion->commit();echo "Grupo asignado correctamente.";
    }catch(Exception $e){$conexion->rollback();echo "No fue posible guardar la asignación.";}
    break;

  default:echo "Acción no válida.";break;
}
$conexion->close();

