<?php
session_start();
include_once("../../config/conexion.php");
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $accion = $_POST['accion'] ?? 'crear';
    $id_director = null;

    if (isset($_SESSION['id_usuario'])) {
        $sqlDirector = "SELECT ID_Director FROM director WHERE ID_Usuario=?";
        $stmtDirector = $conexion->prepare($sqlDirector);
        $stmtDirector->bind_param("i", $_SESSION['id_usuario']);
        $stmtDirector->execute();
        $resultadoDirector = $stmtDirector->get_result();
        if ($resultadoDirector->num_rows > 0) {
            $director = $resultadoDirector->fetch_assoc();
            $id_director = $director['ID_Director'];
        }
    }

    if ($accion === 'crear') {
        $titulo = trim($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $tipo = $_POST['tipo_aviso'] ?? '';
        $id_grupo = !empty($_POST['id_grupo']) ? $_POST['id_grupo'] : null;
        if ($titulo === '' || $contenido === '' || $tipo === '') {
            echo json_encode(["status"=>"error","msg"=>"Campos obligatorios vacíos"]); exit;
        }
        $conexion->begin_transaction();
        $sql = "INSERT INTO publicacion (Titulo,Contenido,Fecha_Publicacion,ID_Director,ID_Grupo) VALUES (?,?,CURDATE(),?,?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssii",$titulo,$contenido,$id_director,$id_grupo);
        $stmt->execute();
        $id_publicacion = $stmt->insert_id;
        $sql2 = "INSERT INTO aviso (Tipo_Aviso,ID_Publicacion) VALUES (?,?)";
        $stmt2 = $conexion->prepare($sql2);
        $stmt2->bind_param("si",$tipo,$id_publicacion);
        $stmt2->execute();
        $conexion->commit();
        echo json_encode(["status"=>"ok","msg"=>"Aviso publicado correctamente"]); exit;
    }

    if ($accion === 'editar') {
        $id_publicacion = $_POST['id_publicacion'] ?? '';
        $titulo = trim($_POST['titulo'] ?? '');
        $contenido = trim($_POST['contenido'] ?? '');
        $tipo = $_POST['tipo_aviso'] ?? '';
        $id_grupo = !empty($_POST['id_grupo']) ? $_POST['id_grupo'] : null;
        if (empty($id_publicacion)||empty($titulo)||empty($contenido)||empty($tipo)) {
            echo json_encode(["status"=>"error","msg"=>"Datos incompletos"]); exit;
        }
        $conexion->begin_transaction();
        $sql = "UPDATE publicacion SET Titulo=?,Contenido=?,ID_Grupo=? WHERE ID_Publicacion=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssii",$titulo,$contenido,$id_grupo,$id_publicacion);
        $stmt->execute();
        $sql2 = "UPDATE aviso SET Tipo_Aviso=? WHERE ID_Publicacion=?";
        $stmt2 = $conexion->prepare($sql2);
        $stmt2->bind_param("si",$tipo,$id_publicacion);
        $stmt2->execute();
        $conexion->commit();
        echo json_encode(["status"=>"ok","msg"=>"Aviso actualizado correctamente"]); exit;
    }

    if ($accion === 'eliminar') {
        $id_publicacion = $_POST['id_publicacion'] ?? '';
        if (empty($id_publicacion)) {
            echo json_encode(["status"=>"error","msg"=>"ID no recibido"]); exit;
        }
        $conexion->begin_transaction();
        $sql = "DELETE FROM aviso WHERE ID_Publicacion=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i",$id_publicacion);
        $stmt->execute();
        $sql2 = "DELETE FROM publicacion WHERE ID_Publicacion=?";
        $stmt2 = $conexion->prepare($sql2);
        $stmt2->bind_param("i",$id_publicacion);
        $stmt2->execute();
        $conexion->commit();
        echo json_encode(["status"=>"ok","msg"=>"Aviso eliminado correctamente"]); exit;
    }

    echo json_encode(["status"=>"error","msg"=>"Acción no válida"]);
} catch (Exception $e) {
    if ($conexion) $conexion->rollback();
    echo json_encode(["status"=>"error","msg"=>$e->getMessage()]);
}
