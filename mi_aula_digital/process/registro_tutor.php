<?php

session_start();

header("Content-Type: application/json");

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);
    exit;
}

// Obtener datos

$nombre = trim($_POST["nombre"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$usuario = trim($_POST["usuario"] ?? "");
$password = $_POST["password"] ?? "";

// Validar campos obligatorios

if (
    empty($nombre) ||
    empty($correo) ||
    empty($telefono) ||
    empty($usuario) ||
    empty($password)
) {
    echo json_encode([
        "success" => false,
        "message" => "Completa todos los campos obligatorios."
    ]);
    exit;
}

// Validar nombre

if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$/u', $nombre)) {
    echo json_encode([
        "success" => false,
        "message" => "El nombre solo puede contener letras y espacios."
    ]);
    exit;
}

// Validar correo

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Correo electrónico inválido."
    ]);
    exit;
}

// Validar teléfono

if (!preg_match('/^[0-9]{10}$/', $telefono)) {
    echo json_encode([
        "success" => false,
        "message" => "El teléfono debe contener exactamente 10 dígitos."
    ]);
    exit;
}

// Validar usuario

if (!preg_match('/^[A-Za-z0-9._]{4,20}$/', $usuario)) {
    echo json_encode([
        "success" => false,
        "message" => "Usuario inválido."
    ]);
    exit;
}

// Validar contraseña

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,50}$/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "La contraseña debe contener al menos 8 caracteres, una mayúscula, una minúscula y un número."
    ]);
    exit;
}

// Validar usuario o correo existente

$sql = "
    SELECT ID_Usuario
    FROM usuario
    WHERE Correo = ? OR Usuario = ?
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
        "message" => "El correo o el usuario ya se encuentran registrados."
    ]);
    exit;
}

$stmt->close();

// Crear usuario y tutor

$conexion->begin_transaction();

try {

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $rol = "PadreTutor";

    $sql = "
        INSERT INTO usuario
        (
            Nombre,
            Usuario,
            Correo,
            Contrasena,
            Rol
        )
        VALUES (?,?,?,?,?)
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "sssss",
        $nombre,
        $usuario,
        $correo,
        $passwordHash,
        $rol
    );

    if (!$stmt->execute()) {
        throw new Exception("No fue posible crear el usuario.");
    }

    $id_usuario = $conexion->insert_id;

    $stmt->close();

    $sql = "
        INSERT INTO padre_tutor
        (
            Nombre_Completo,
            Correo,
            Telefono,
            ID_Usuario
        )
        VALUES (?,?,?,?)
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "sssi",
        $nombre,
        $correo,
        $telefono,
        $id_usuario
    );

    if (!$stmt->execute()) {
        throw new Exception("No fue posible registrar al tutor.");
    }

    $conexion->commit();

    echo json_encode([
        "success" => true,
        "message" => "Cuenta creada correctamente. Ya puedes iniciar sesión."
    ]);
} catch (Exception $e) {

    $conexion->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

if (isset($stmt)) {
    $stmt->close();
}

$conexion->close();
