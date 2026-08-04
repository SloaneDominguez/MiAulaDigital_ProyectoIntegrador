<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once("../config/conexion.php");

// Validar método
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit();
}

// Datos recibidos
$usuario = trim($_POST["usuario"] ?? "");
$password = $_POST["password"] ?? "";
$rol = trim($_POST["rol"] ?? "");
if (empty($usuario) || empty($password) || empty($rol)) {
    echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios."]);
    exit();
}

// Login general
$sql = "SELECT ID_Usuario,Nombre,Usuario,Correo,Contrasena,Rol FROM usuario WHERE Usuario=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Usuario o contraseña incorrectos."]);
    exit();
}
$datos = $resultado->fetch_assoc();
if (!password_verify($password, $datos["Contrasena"])) {
    echo json_encode(["success" => false, "message" => "Usuario o contraseña incorrectos."]);
    exit();
}
if ($datos["Rol"] !== $rol) {
    echo json_encode(["success" => false, "message" => "El usuario no pertenece al tipo seleccionado."]);
    exit();
}

session_regenerate_id(true);
$_SESSION["id_usuario"] = $datos["ID_Usuario"];
$_SESSION["nombre"] = $datos["Nombre"];
$_SESSION["usuario"] = $datos["Usuario"];
$_SESSION["rol"] = $datos["Rol"];

// Datos adicionales Docente
if ($datos["Rol"] === "Docente") {
    $sqlDocente = "SELECT ID_Docente,ID_Grupo_Asignado FROM docente WHERE ID_Usuario=? LIMIT 1";
    $stmtDocente = $conexion->prepare($sqlDocente);
    $stmtDocente->bind_param("i", $datos["ID_Usuario"]);
    $stmtDocente->execute();
    $resultadoDocente = $stmtDocente->get_result();
    if ($resultadoDocente->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "El docente no tiene un registro asociado."]);
        exit();
    }
    $docente = $resultadoDocente->fetch_assoc();
    $_SESSION["id_docente"] = $docente["ID_Docente"];
    $_SESSION["id_grupo"] = $docente["ID_Grupo_Asignado"];
}

// Datos adicionales Tutor
if ($datos["Rol"] === "PadreTutor") {
    $sqlTutor = "SELECT ID_Padre FROM padre_tutor WHERE ID_Usuario=? LIMIT 1";
    $stmtTutor = $conexion->prepare($sqlTutor);
    $stmtTutor->bind_param("i", $datos["ID_Usuario"]);
    $stmtTutor->execute();
    $resultadoTutor = $stmtTutor->get_result();
    if ($resultadoTutor->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "El tutor no tiene un registro asociado."]);
        exit();
    }
    $tutor = $resultadoTutor->fetch_assoc();
    $_SESSION["id_padre"] = $tutor["ID_Padre"];
}

// Redirecciones
switch ($datos["Rol"]) {
    case "Direccion":
        $redirect = "admin/dashboard.php";
        break;
    case "Docente":
        $redirect = "docente/dashboard.php";
        break;
    case "PadreTutor":
        $redirect = "tutor/dashboard.php";
        break;
    default:
        echo json_encode(["success" => false, "message" => "Rol inválido."]);
        exit();
}

// Respuesta exitosa
echo json_encode(["success" => true, "message" => "Bienvenido a Mi Aula Digital.", "redirect" => $redirect]);

// Cerrar recursos
if (isset($stmtDocente)) $stmtDocente->close();
if (isset($stmtTutor)) $stmtTutor->close();
$stmt->close();
$conexion->close();
exit();

