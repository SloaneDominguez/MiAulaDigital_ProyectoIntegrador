<?php

session_start();

header("Content-Type: application/json");

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido"
    ]);
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$primer = trim($_POST["primer_apellido"] ?? "");
$segundo = trim($_POST["segundo_apellido"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$usuario = trim($_POST["usuario"] ?? "");
$password = $_POST["password"] ?? "";

if (
    empty($nombre) ||
    empty($primer) ||
    empty($correo) ||
    empty($usuario) ||
    empty($password)
) {
    echo json_encode([
        "success" => false,
        "message" => "Completa todos los campos obligatorios."
    ]);
    exit;
}

// Validar solicitud existente

$sql = "
    SELECT ID_Solicitud
    FROM solicitud_acceso
    WHERE (Correo = ? OR Usuario = ?)
    AND Estado = 'Pendiente'
";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "ss",
    $correo,
    $usuario
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Ya existe una solicitud pendiente con esos datos."
    ]);
    exit;
}

// Insertar solicitud

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$sql = "
    INSERT INTO solicitud_acceso
    (
        Nombre,
        Primer_Apellido,
        Segundo_Apellido,
        Usuario,
        Correo,
        Contrasena,
        Rol
    )
    VALUES (?,?,?,?,?,?,?)
";

$stmt = $conexion->prepare($sql);

$rol = "Docente";

$stmt->bind_param(
    "sssssss",
    $nombre,
    $primer,
    $segundo,
    $usuario,
    $correo,
    $passwordHash,
    $rol
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Solicitud enviada correctamente. Dirección revisará tu acceso."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No fue posible enviar la solicitud."
    ]);
}

$stmt->close();
$conexion->close();
