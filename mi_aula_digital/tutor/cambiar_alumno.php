<?php
session_start();
include_once '../config/conexion.php';

if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== "PadreTutor") {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id']) && isset($_SESSION['id_padre'])) {
    $nuevo_id_alumno = intval($_GET['id']);
    $id_padre = $_SESSION['id_padre'];

    // Verificar que el alumno pertenezca realmente a este padre por seguridad
    $sql_val = "SELECT ID_Alumno FROM alumno WHERE ID_Alumno = ? AND ID_Padre = ?";
    $stmt = $conexion->prepare($sql_val);
    $stmt->bind_param("ii", $nuevo_id_alumno, $id_padre);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $_SESSION['id_alumno'] = $nuevo_id_alumno;
    }
    $stmt->close();
}

header("Location: dashboard.php?view=panel");
exit();
?>