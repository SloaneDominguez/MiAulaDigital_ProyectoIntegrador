<?php
include_once("../../config/conexion.php");
$action = $_GET['action'] ?? '';

if ($action === "get_alumnos") {
    $id_grupo = intval($_GET['id_grupo'] ?? 0);
    $stmt = $conexion->prepare("SELECT ID_Alumno, CONCAT(Nombre,' ',Primer_Apellido,' ',IFNULL(Segundo_Apellido,'')) AS Nombre FROM alumno WHERE ID_Grupo=?");
    $stmt->bind_param("i",$id_grupo);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode($data);
    exit;
}

if ($action === "get") {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $conexion->prepare("SELECT i.ID_Incidencia,i.Tipo_Incidencia,i.Descripcion,i.Fecha,a.ID_Alumno,a.ID_Grupo FROM incidencia i INNER JOIN alumno a ON i.ID_Alumno=a.ID_Alumno WHERE i.ID_Incidencia=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode($res->num_rows>0 ? $res->fetch_assoc() : []);
    exit;
}

if ($action === "insert") {
    $tipo = $_POST['tipo_incidencia'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $id_alumno = intval($_POST['id_alumno'] ?? 0);
    if (!$tipo||!$descripcion||!$fecha||!$id_alumno) {echo "Completa todos los campos"; exit;}
    $stmt = $conexion->prepare("INSERT INTO incidencia (Tipo_Incidencia,Descripcion,Fecha,ID_Alumno) VALUES (?,?,?,?)");
    $stmt->bind_param("sssi",$tipo,$descripcion,$fecha,$id_alumno);
    $stmt->execute();
    echo "Incidencia registrada correctamente";
    exit;
}

if ($action === "update") {
    $id = intval($_POST['id_incidencia'] ?? 0);
    $tipo = trim($_POST['tipo_incidencia'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? '';
    $id_alumno = intval($_POST['id_alumno'] ?? 0);
    if (!$id||!$tipo||!$descripcion||!$fecha||!$id_alumno) {echo "Completa todos los campos"; exit;}
    $stmt = $conexion->prepare("UPDATE incidencia SET Tipo_Incidencia=?,Descripcion=?,Fecha=?,ID_Alumno=? WHERE ID_Incidencia=?");
    $stmt->bind_param("sssii",$tipo,$descripcion,$fecha,$id_alumno,$id);
    $stmt->execute();
    echo "Incidencia actualizada correctamente";
    exit;
}

if ($action === "delete") {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) {echo "ID inválido"; exit;}
    $stmt = $conexion->prepare("DELETE FROM incidencia WHERE ID_Incidencia=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    echo "Incidencia eliminada correctamente";
    exit;
}

echo "Acción no válida";
