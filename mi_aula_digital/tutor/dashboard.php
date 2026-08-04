<?php
session_start();
date_default_timezone_set('America/Mexico_City');

// ==========================================
// EVITAR CACHÉ DEL NAVEGADOR
// ==========================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ==========================================
// VALIDAR SESIÓN ACTIVA Y ROL
// ==========================================
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== "PadreTutor") {
    header("Location: ../login.php");
    exit();
}

include_once '../config/conexion.php';

$id_usuario = $_SESSION['id_usuario'];
$id_padre = null;
$nombre_tutor = "Tutor";

// =======================================================
// OBTENER DATOS DE LA TABLA `padre_tutor`
// =======================================================
$sql_tutor = "SELECT ID_Padre, Nombre_Completo FROM padre_tutor WHERE ID_Usuario = ? LIMIT 1";
$stmt_t = $conexion->prepare($sql_tutor);
if ($stmt_t) {
    $stmt_t->bind_param("i", $id_usuario);
    $stmt_t->execute();
    $res_t = $stmt_t->get_result();
    if($tutor_data = $res_t->fetch_assoc()) {
        $id_padre = $tutor_data['ID_Padre'];
        $nombre_tutor = $tutor_data['Nombre_Completo'];
        $_SESSION['id_padre'] = $id_padre;
        $_SESSION['nombre'] = $nombre_tutor;
    }
    $stmt_t->close();
}

if (!$id_padre) {
    header("Location: ../login.php");
    exit();
}

// =======================================================
// OBTENER ALUMNO ASOCIADO (Si no hay sesión de alumno, toma el primero)
// =======================================================
if (!isset($_SESSION['id_alumno'])) {
    $sql_primer_hijo = "SELECT ID_Alumno FROM alumno WHERE ID_Padre = ? LIMIT 1";
    $stmt_h = $conexion->prepare($sql_primer_hijo);
    $stmt_h->bind_param("i", $id_padre);
    $stmt_h->execute();
    $res_h = $stmt_h->get_result();
    if($hijo_def = $res_h->fetch_assoc()) {
        $_SESSION['id_alumno'] = $hijo_def['ID_Alumno'];
    }
    $stmt_h->close();
}

$id_alumno_sesion = $_SESSION['id_alumno'] ?? null;
$id_alumno_tutorado = null;
$nombre_alumno = "Estudiante";
$id_grupo_alumno = null;

if ($id_alumno_sesion) {
    $sql_hijo = "SELECT ID_Alumno, Nombre, Primer_Apellido, Segundo_Apellido, ID_Grupo FROM alumno WHERE ID_Alumno = ? AND ID_Padre = ? LIMIT 1";
    $stmt = $conexion->prepare($sql_hijo);
    $stmt->bind_param("ii", $id_alumno_sesion, $id_padre);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($hijo = $resultado->fetch_assoc()) {
        $id_alumno_tutorado = $hijo['ID_Alumno'];
        $nombre_alumno = $hijo['Nombre'] . " " . $hijo['Primer_Apellido'] . " " . ($hijo['Segundo_Apellido'] ?? '');
        $id_grupo_alumno = $hijo['ID_Grupo'];
    }
    $stmt->close();
}

// =======================================================
// CONTEOS DEL DASHBOARD (Usando nombres reales de tablas)
// =======================================================
$total_anuncios = 0;
if ($id_grupo_alumno) {
    $sql_anuncios = "SELECT COUNT(*) AS total FROM publicacion WHERE ID_Director IS NOT NULL OR ID_Grupo = ?";
    $stmt = $conexion->prepare($sql_anuncios);
    $stmt->bind_param("i", $id_grupo_alumno);
    $stmt->execute();
    $res_anuncios = $stmt->get_result();
    if ($dato = $res_anuncios->fetch_assoc()) {
        $total_anuncios = $dato['total'];
    }
    $stmt->close();
}

$total_calificaciones = 0;
if ($id_alumno_tutorado) {
    $sql_calif = "SELECT COUNT(*) AS total FROM calificacion WHERE ID_Alumno = ?";
    $stmt = $conexion->prepare($sql_calif);
    $stmt->bind_param("i", $id_alumno_tutorado);
    $stmt->execute();
    $res_calif = $stmt->get_result();
    if ($dato = $res_calif->fetch_assoc()) {
        $total_calificaciones = $dato['total'];
    }
    $stmt->close();
}

$total_incidencias = 0;
if ($id_alumno_tutorado) {
    $sql_incid = "SELECT COUNT(*) AS total FROM incidencia WHERE ID_Alumno = ?";
    $stmt = $conexion->prepare($sql_incid);
    $stmt->bind_param("i", $id_alumno_tutorado);
    $stmt->execute();
    $res_incid = $stmt->get_result();
    if ($dato = $res_incid->fetch_assoc()) {
        $total_incidencias = $dato['total'];
    }
    $stmt->close();
}

// ==========================================
// CONTROL DE VISTAS SEGURO
// ==========================================
$view = $_GET['view'] ?? 'panel';
$vistas_validas = ['panel', 'mishijos', 'anuncios', 'calificaciones', 'incidencias'];
if (!in_array($view, $vistas_validas)) {
    $view = 'panel';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Padre/Tutor - Mi Aula Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="dashboard-body">

    <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="layout-container">
        <aside class="sidebar">
            <div class="user-profile">
                <img src="../assets/img/escudo.jpeg" alt="Escudo Escuela" class="avatar-img" onerror="this.src='https://via.placeholder.com/45'">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_tutor, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="user-role">Padre / Tutor</span>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php?view=panel" class="menu-item <?= $view === 'panel' ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-columns"></i>
                    <span>Dashboard</span>
                </a>
                <a href="dashboard.php?view=mishijos" class="menu-item <?= $view === 'mishijos' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i>
                    <span>Mis hijos</span>
                </a>
                <a href="dashboard.php?view=anuncios" class="menu-item <?= $view === 'anuncios' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>Avisos</span>
                </a>
                <a href="dashboard.php?view=calificaciones" class="menu-item <?= $view === 'calificaciones' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book"></i>
                    <span>Calificaciones</span>
                </a>
                <a href="dashboard.php?view=incidencias" class="menu-item <?= $view === 'incidencias' ? 'active' : '' ?>">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Incidencias</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="status-connected-box">
                    <span class="dot-online">•</span> CONECTADO
                </div>
                <button type="button" class="logout-action-btn" id="openLogoutModal">
                    <div class="logout-btn-inner">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Cerrar sesión</span>
                    </div>
                </button>
            </div>
        </aside>

        <main class="main-content">
            <?php 
            $archivo_vista = "vistas/" . $view . ".php";
            if (file_exists($archivo_vista)) {
                include $archivo_vista;
            } else {
                include "vistas/panel.php";
            }
            ?>
        </main>
    </div>

    <div class="logout-overlay" id="logoutOverlay" style="display:none;">
        <div class="logout-modal">
            <div class="logout-modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3>¿Cerrar sesión?</h3>
            <p>Saldrás del panel de tutor.</p>
            <div class="logout-modal-actions">
                <button class="btn-cancel" id="closeLogoutModal">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </button>
                <a href="../process/logout.php" class="btn-confirm">
                    <i class="fa-solid fa-check"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/responsive.js"></script>
</body>
</html>