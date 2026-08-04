<?php
// Configuración de errores para depuración si fuera necesario
error_reporting(E_ALL);
ini_set('display_errors', 0); // En producción se recomienda en 0 para evitar pantallas rotas

session_start();
date_default_timezone_set('America/Mexico_City');

include_once '../config/conexion.php';

if (!isset($_SESSION['id_usuario'], $_SESSION['rol']) || $_SESSION['rol'] !== "Docente") {
    header("Location: ../login.php");
    exit();
}

$id_usuario = (int)$_SESSION['id_usuario'];

/* Consulta adaptada a minúsculas para prevenir errores 500 en Linux */
$sql = "SELECT id_docente, id_grupo_asignado, nombre, primer_apellido, segundo_apellido FROM docente WHERE id_usuario = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    // Si falla la preparación, redirige o detiene para evitar error crítico sin avisar
    header("Location: ../login.php");
    exit();
}

$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$docente = $res->fetch_assoc();
$stmt->close();

$id_docente = (int)$docente['id_docente'];
$id_docente_sesion = $id_docente;

$_SESSION['id_docente'] = $id_docente;
$_SESSION['id_grupo'] = $docente['id_grupo_asignado'];
$_SESSION['nombre'] = $docente['nombre'] . " " . $docente['primer_apellido'] . " " . $docente['segundo_apellido'];

function contar($conexion, $sql, $id)
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return 0;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $resultado['total'] ?? 0;
}

/* Consultas con tablas y columnas en minúsculas */
$total_alumnos = contar(
    $conexion,
    "SELECT COUNT(a.id_alumno) AS total FROM alumno a INNER JOIN docente d ON a.id_grupo = d.id_grupo_asignado WHERE d.id_docente = ?",
    $id_docente
);

$total_grupos = contar(
    $conexion,
    "SELECT COUNT(DISTINCT id_grupo_asignado) AS total FROM docente WHERE id_docente = ? AND id_grupo_asignado IS NOT NULL",
    $id_docente
);

$total_anuncios = contar(
    $conexion,
    "SELECT COUNT(*) AS total FROM publicacion WHERE id_docente = ?",
    $id_docente
);

$total_incidencias = contar(
    $conexion,
    "SELECT COUNT(*) AS total FROM incidencia WHERE id_docente = ?",
    $id_docente
);

$view = $_GET['view'] ?? 'panel';
$vistas_validas = ['panel', 'grupos', 'anuncios', 'calificaciones', 'incidencias'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente - Mi Aula Digital</title>
    <link rel="stylesheet" href="css/estilos.css">
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
                    <span>Docente</span>
                </div>
            </div>

            <nav class="sidebar-menu">

                <a href="?view=panel" class="menu-item <?= $view == 'panel' ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-columns"></i> Dashboard
                </a>

                <a href="?view=grupos" class="menu-item <?= $view == 'grupos' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Mis grupos
                </a>

                <a href="?view=anuncios" class="menu-item <?= $view == 'anuncios' ? 'active' : '' ?>">
                    <i class="fa-solid fa-bell"></i> Anuncios
                </a>

                <a href="?view=calificaciones" class="menu-item <?= $view == 'calificaciones' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i> Calificaciones
                </a>

                <a href="?view=incidencias" class="menu-item <?= $view == 'incidencias' ? 'active' : '' ?>">
                    <i class="fa-solid fa-triangle-exclamation"></i> Incidencias
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
            if (in_array($view, $vistas_validas) && file_exists("vistas/$view.php")) {
                include "vistas/$view.php";
            } else {
                include "vistas/panel.php";
            }
            ?>

        </main>

    </div>


    <div class="logout-overlay" id="logoutOverlay">

        <div class="logout-modal" id="logoutModal">

            <div class="logout-modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <h3>¿Cerrar sesión?</h3>

            <p>Estás a punto de salir del Panel Docente. Tendrás que ingresar tus credenciales nuevamente para acceder.</p>

            <div class="logout-modal-actions">

                <button type="button" class="btn-cancel" id="closeLogoutModal">
                    <i class="fa-solid fa-xmark"></i> Cancelar
                </button>

                <a href="../process/logout.php" class="btn-confirm">
                    <i class="fa-solid fa-right-from-bracket"></i> Salir
                </a>

            </div>

        </div>

    </div>

    <script src="js/app.js"></script>

    <script src="js/responsive.js"></script>
</body>

</html>