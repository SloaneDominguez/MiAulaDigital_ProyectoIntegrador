<?php
include_once("../../config/conexion.php");
if ($_SERVER["REQUEST_METHOD"] !== "POST") exit("Acceso no permitido");

$id_alumno = $_POST['id_alumno'] ?? '';
$accion = $_POST['accion'] ?? '';

if ($accion == "eliminar") {
    if (empty($id_alumno)) exit("ID de alumno no recibido");
    $sql = "DELETE FROM alumno WHERE ID_Alumno=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_alumno);
    echo $stmt->execute() ? "Alumno eliminado correctamente" : "Error al eliminar alumno";
    exit();
}

$nombre = $_POST['nombre'] ?? '';
$primer = $_POST['primer_apellido'] ?? '';
$segundo = $_POST['segundo_apellido'] ?? '';
$curp = $_POST['curp'] ?? '';
$id_grupo = $_POST['id_grupo'] ?? '';

if (!empty($id_alumno)) {
    if (!$nombre || !$primer || !$curp || !$id_grupo) exit("Todos los campos obligatorios");
    $sql = "UPDATE alumno SET Nombre=?, Primer_Apellido=?, Segundo_Apellido=?, CURP=?, ID_Grupo=? WHERE ID_Alumno=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssii", $nombre, $primer, $segundo, $curp, $id_grupo, $id_alumno);
    echo $stmt->execute() ? "Alumno actualizado correctamente" : "Error al actualizar alumno";
    exit();
}

if (!$nombre || !$primer || !$curp || !$id_grupo) exit("Todos los campos obligatorios");
$sql = "INSERT INTO alumno (Nombre, Primer_Apellido, Segundo_Apellido, CURP, ID_Grupo) VALUES (?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssssi", $nombre, $primer, $segundo, $curp, $id_grupo);
echo $stmt->execute() ? "Alumno registrado correctamente" : "Error al registrar alumno";
?>
