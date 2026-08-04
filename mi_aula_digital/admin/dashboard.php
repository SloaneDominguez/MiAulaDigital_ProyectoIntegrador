<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include_once '../config/conexion.php';
/* =========================
   VALIDAR SESIÓN
========================= */
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SESSION['rol'] != 'Direccion') {
    header("Location: ../index.php");
    exit;
}

date_default_timezone_set('America/Mexico_City');

/* =========================
   USUARIO SESIÓN (DIRECCIÓN)
========================= */
$id_usuario = $_SESSION['id_usuario'];

/* =========================
   CONTEOS GLOBALES (SISTEMA COMPLETO)
========================= */

/* TOTAL ALUMNOS */
$total_alumnos = 0;
$sql_alumnos = "SELECT COUNT(*) as total_alumnos FROM alumno";
if ($res = $conexion->query($sql_alumnos)) {
    $total_alumnos = $res->fetch_assoc()['total_alumnos'];
}

/* TOTAL DOCENTES */
$total_docentes = 0;
$sql_docentes = "SELECT COUNT(*) as total_docentes FROM docente";
if ($res = $conexion->query($sql_docentes)) {
    $total_docentes = $res->fetch_assoc()['total_docentes'];
}

/* TOTAL GRUPOS */
$total_grupos = 0;
$sql_grupos = "SELECT COUNT(DISTINCT ID_Grupo) as total_grupos FROM alumno";
if ($res = $conexion->query($sql_grupos)) {
    $total_grupos = $res->fetch_assoc()['total_grupos'];
}

/* TOTAL PUBLICACIONES */
$total_anuncios = 0;
$sql_anuncios = "SELECT COUNT(*) as total_pub FROM publicacion";
if ($res = $conexion->query($sql_anuncios)) {
    $total_anuncios = $res->fetch_assoc()['total_pub'];
}

/* TOTAL INCIDENCIAS */
$total_incidencias = 0;
$sql_incidencias = "SELECT COUNT(*) as total_inc FROM incidencia";
if ($res = $conexion->query($sql_incidencias)) {
    $total_incidencias = $res->fetch_assoc()['total_inc'];
}

/* =========================
   VISTA
========================= */
$view = $_GET['view'] ?? 'panel';
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Dirección - Mi Aula Digital</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="css/modals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="dashboard-body">

    <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Abrir menú" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="layout-container">

        <aside class="sidebar">
            <div class="user-profile">
                <img src="../assets/img/escudo.jpeg" alt="Escudo" class="avatar" onerror="this.src='../assets/img/fondo.png'">
                <div class="user-info">
                    <h4>Cuauhtémoc</h4>
                    <span>Direccion</span>
                </div>
            </div>

            <nav class="sidebar-menu">

                <a href="dashboard.php?view=panel" class="menu-item <?php echo $view === 'panel' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-table-columns"></i> Dashboard
                </a>

                <a href="dashboard.php?view=docentes" class="menu-item <?php echo $view === 'docentes' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chalkboard-user"></i> Docentes
                </a>

                <a href="dashboard.php?view=solicitud" class="menu-item <?php echo $view === 'solicitud_acceso' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-envelope-open-text"></i> Solicitud de Acceso
                </a>

                <a href="dashboard.php?view=alumnos" class="menu-item <?php echo $view === 'alumnos' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-graduate"></i> Alumnos
                </a>
                
                <a href="dashboard.php?view=asignaciones" class="menu-item <?php echo $view === 'asignaciones' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-people-group"></i></i> Grupos
                </a>

                <a href="dashboard.php?view=calificaciones" class="menu-item <?php echo $view === 'calificaciones' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> Calificaciones
                </a>

                <a href="dashboard.php?view=incidencias" class="menu-item <?php echo $view === 'incidencias' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-circle-exclamation"></i> Incidencias
                </a>

                <a href="dashboard.php?view=avisos" class="menu-item <?php echo $view === 'avisos' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bullhorn"></i> Avisos Institucionales
                </a>

                <a href="dashboard.php?view=tokens" class="menu-item <?php echo $view === 'tokens' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-key"></i> Tokens por grupo
                </a>

            </nav>

            <div class="sidebar-footer">
                <div class="demo-tag">CONECTADO</div>
                <button type="button" class="logout-btn" id="openLogoutModal">
                    <div class="logout-btn-inner">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Cerrar sesión</span>
                    </div>
                </button>
            </div>
        </aside>

        <main class="main-content">
            <?php
            $vistas_validas = [
                'panel',
                'docentes',
                'solicitud',
                'alumnos',
                'asignaciones',
                'calificaciones',
                'incidencias',
                'avisos',
                'tokens'
            ];
            if (in_array($view, $vistas_validas) && file_exists("vistas/" . $view . ".php")) {
                include "vistas/" . $view . ".php";
            } else {
                include "vistas/panel.php";
            }
            ?>
        </main>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">

            <h2>
                Cerrar sesión
            </h2>

            <p>
                ¿Estás seguro de que deseas cerrar tu sesión?<br>
                Tendrás que volver a iniciar sesión para acceder al sistema.
            </p>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="closeLogoutModal">
                    Cancelar
                </button>

                <a href="../process/logout.php" class="btn-primary">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Cerrar sesión
                </a>
            </div>

        </div>
    </div>

    <script src="js/logout.js"></script>
    <script src="js/custom_alert.js"></script>
    <script src="js/responsive.js"></script>
</body>

</html>