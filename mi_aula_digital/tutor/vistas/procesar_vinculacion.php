<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/csrf.php';

if (!isset($_SESSION['id_usuario'], $_SESSION['rol'], $_SESSION['id_padre']) || $_SESSION['rol'] !== 'PadreTutor') {
    header('Location: ../../index.php');
    exit;
}

function volver(string $tipo, string $codigo): never {
    header('Location: ../dashboard.php?view=mishijos&'.rawurlencode($tipo).'='.rawurlencode($codigo));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') volver('error', 'metodo_invalido');
if (!validarCSRF()) volver('error', 'csrf_invalido');

$curp = strtoupper(preg_replace('/\s+/', '', trim($_POST['curp_alumno'] ?? '')));
$token = trim($_POST['token_alumno'] ?? '');
$idPadre = (int)$_SESSION['id_padre'];

if (!preg_match('/^[A-Z0-9]{18}$/', $curp) || $token === '') volver('error', 'datos_invalidos');

$conexion->begin_transaction();
try {
    $stmt = $conexion->prepare('SELECT ID_Alumno, ID_Padre, ID_Token FROM alumno WHERE CURP = ? FOR UPDATE');
    if (!$stmt) throw new RuntimeException('No se pudo consultar al alumno');
    $stmt->bind_param('s', $curp);
    $stmt->execute();
    $alumno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$alumno) {
        $conexion->rollback();
        volver('error', 'curp_no_encontrada');
    }

    $idAlumno = (int)$alumno['ID_Alumno'];
    $padreActual = (int)($alumno['ID_Padre'] ?? 0);

    if ($padreActual > 0 && $padreActual !== $idPadre) {
        $conexion->rollback();
        volver('error', 'alumno_ya_vinculado');
    }

    if ($padreActual === $idPadre) {
        $_SESSION['id_alumno'] = $idAlumno;
        $conexion->commit();
        volver('exito', 'alumno_ya_es_tuyo');
    }

    // Solo acepta el token vigente que actualmente tiene asignado el alumno.
    $stmt = $conexion->prepare(
        'SELECT t.ID_Token FROM tokens t
         WHERE t.ID_Alumno = ? AND t.ID_Token = ? AND BINARY t.Token_String = ?
           AND t.Fecha_Expiracion >= NOW()
         FOR UPDATE'
    );
    if (!$stmt) throw new RuntimeException('No se pudo validar el token');
    $idTokenAsignado = (int)($alumno['ID_Token'] ?? 0);
    $stmt->bind_param('iis', $idAlumno, $idTokenAsignado, $token);
    $stmt->execute();
    $tokenValido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tokenValido) {
        $conexion->rollback();
        volver('error', 'token_invalido');
    }

    $stmt = $conexion->prepare('UPDATE alumno SET ID_Padre = ? WHERE ID_Alumno = ? AND ID_Padre IS NULL');
    if (!$stmt) throw new RuntimeException('No se pudo vincular al alumno');
    $stmt->bind_param('ii', $idPadre, $idAlumno);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new RuntimeException('El alumno cambió de estado durante la vinculación');
    }
    $stmt->close();

    $_SESSION['id_alumno'] = $idAlumno;
    $conexion->commit();
    volver('exito', 'vinculado_correctamente');
} catch (Throwable $e) {
    $conexion->rollback();
    error_log('Error vinculando alumno: '.$e->getMessage());
    volver('error', 'error_bd');
}
