<?php
include_once("../../config/conexion.php");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion == "obtener") {
        $id_docente = intval($_POST["id_docente"]);
        $stmt = $conexion->prepare("SELECT d.ID_Docente,d.Nombre,d.Primer_Apellido,d.Segundo_Apellido,d.ID_Grupo_Asignado,u.Usuario,u.Correo FROM docente d INNER JOIN usuario u ON d.ID_Usuario=u.ID_Usuario WHERE d.ID_Docente=?");
        $stmt->bind_param("i",$id_docente);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
        exit;
    }

    if ($accion == "eliminar") {
        $id_docente = intval($_POST["id_docente"]);
        $conexion->begin_transaction();
        $stmt = $conexion->prepare("SELECT ID_Usuario FROM docente WHERE ID_Docente=?");
        $stmt->bind_param("i",$id_docente);
        $stmt->execute();
        $docente = $stmt->get_result()->fetch_assoc();
        if (!$docente) throw new Exception("El docente no existe.");
        $id_usuario = $docente["ID_Usuario"];
        $stmt = $conexion->prepare("DELETE FROM docente WHERE ID_Docente=?");
        $stmt->bind_param("i",$id_docente);
        $stmt->execute();
        $stmt = $conexion->prepare("DELETE FROM usuario WHERE ID_Usuario=?");
        $stmt->bind_param("i",$id_usuario);
        $stmt->execute();
        $conexion->commit();
        echo "Docente eliminado correctamente.";
        exit;
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $primer_apellido = trim($_POST['primer_apellido'] ?? '');
    $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');
    $id_grupo = intval($_POST['id_grupo'] ?? 0);

    if ($accion == "editar") {
        $id_docente = intval($_POST['id_docente']);
        if ($nombre==''||$primer_apellido==''||$usuario==''||$correo==''||$id_grupo<=0) throw new Exception("Todos los campos son obligatorios.");
        if (!filter_var($correo,FILTER_VALIDATE_EMAIL)) throw new Exception("Correo no válido.");
        $conexion->begin_transaction();
        $stmt = $conexion->prepare("SELECT ID_Usuario FROM docente WHERE ID_Docente=?");
        $stmt->bind_param("i",$id_docente);
        $stmt->execute();
        $docente = $stmt->get_result()->fetch_assoc();
        if (!$docente) throw new Exception("El docente no existe.");
        $id_usuario = $docente["ID_Usuario"];
        $stmt = $conexion->prepare("SELECT ID_Usuario FROM usuario WHERE Usuario=? AND ID_Usuario<>?");
        $stmt->bind_param("si",$usuario,$id_usuario);
        $stmt->execute();
        if ($stmt->get_result()->num_rows>0) throw new Exception("El usuario ya existe.");
        $stmt = $conexion->prepare("SELECT ID_Usuario FROM usuario WHERE Correo=? AND ID_Usuario<>?");
        $stmt->bind_param("si",$correo,$id_usuario);
        $stmt->execute();
        if ($stmt->get_result()->num_rows>0) throw new Exception("El correo ya existe.");
        $nombre_completo = trim($nombre." ".$primer_apellido." ".$segundo_apellido);
        if ($contrasena!="") {
            $password = password_hash($contrasena,PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE usuario SET Nombre=?,Usuario=?,Correo=?,Contrasena=? WHERE ID_Usuario=?");
            $stmt->bind_param("ssssi",$nombre_completo,$usuario,$correo,$password,$id_usuario);
        } else {
            $stmt = $conexion->prepare("UPDATE usuario SET Nombre=?,Usuario=?,Correo=? WHERE ID_Usuario=?");
            $stmt->bind_param("sssi",$nombre_completo,$usuario,$correo,$id_usuario);
        }
        $stmt->execute();
        $stmt = $conexion->prepare("UPDATE docente SET Nombre=?,Primer_Apellido=?,Segundo_Apellido=?,ID_Grupo_Asignado=? WHERE ID_Docente=?");
        $stmt->bind_param("sssii",$nombre,$primer_apellido,$segundo_apellido,$id_grupo,$id_docente);
        $stmt->execute();
        $conexion->commit();
        echo "Docente actualizado correctamente.";
        exit;
    }

    if ($nombre==''||$primer_apellido==''||$usuario==''||$correo==''||$contrasena==''||$id_grupo<=0) throw new Exception("Todos los campos son obligatorios.");
    if (!filter_var($correo,FILTER_VALIDATE_EMAIL)) throw new Exception("Correo no válido.");
    if (strlen($contrasena)<6) throw new Exception("La contraseña debe tener mínimo 6 caracteres.");
    $conexion->begin_transaction();
    $stmt = $conexion->prepare("SELECT ID_Usuario FROM usuario WHERE Usuario=? OR Correo=?");
    $stmt->bind_param("ss",$usuario,$correo);
    $stmt->execute();
    if ($stmt->get_result()->num_rows>0) throw new Exception("El usuario o correo ya existe.");
    $nombre_completo = trim($nombre." ".$primer_apellido." ".$segundo_apellido);
    $password = password_hash($contrasena,PASSWORD_DEFAULT);
    $stmt = $conexion->prepare("INSERT INTO usuario (Nombre,Usuario,Correo,Contrasena,Rol) VALUES (?,?,?,?, 'Docente')");
    $stmt->bind_param("ssss",$nombre_completo,$usuario,$correo,$password);
    $stmt->execute();
    $id_usuario = $conexion->insert_id;
    $stmt = $conexion->prepare("INSERT INTO docente (Nombre,Primer_Apellido,Segundo_Apellido,ID_Usuario,ID_Grupo_Asignado) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssii",$nombre,$primer_apellido,$segundo_apellido,$id_usuario,$id_grupo);
    $stmt->execute();
    $conexion->commit();
    echo "Docente registrado correctamente.";
} catch (Exception $e) {
    if ($conexion->errno==0) $conexion->rollback();
    echo $e->getMessage();
}
