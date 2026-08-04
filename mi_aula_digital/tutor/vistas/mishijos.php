<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/csrf.php';

$idPadre = (int)($_SESSION['id_padre'] ?? 0);
$idAlumnoActivo = (int)($_SESSION['id_alumno'] ?? 0);
$hijos = [];

if (isset($conexion) && $idPadre > 0) {
    $sql = "SELECT a.ID_Alumno, a.Nombre, a.Primer_Apellido, a.Segundo_Apellido,
                   a.CURP, a.Fecha_Registro, g.Grado, g.Grupo,
                   (SELECT COUNT(*) FROM calificacion c WHERE c.ID_Alumno = a.ID_Alumno) AS Total_Calificaciones,
                   (SELECT COUNT(*) FROM incidencia i WHERE i.ID_Alumno = a.ID_Alumno) AS Total_Incidencias
            FROM alumno a
            LEFT JOIN grupo g ON g.ID_Grupo = a.ID_Grupo
            WHERE a.ID_Padre = ?
            ORDER BY a.Nombre, a.Primer_Apellido, a.Segundo_Apellido";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $idPadre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        while ($fila = $resultado->fetch_assoc()) {
            $hijos[] = $fila;
        }
        $stmt->close();
    }
}

$mensajes = [
    'vinculado_correctamente' => ['success', 'Alumno vinculado correctamente. Ya puedes consultar su información.'],
    'curp_no_encontrada' => ['error', 'No encontramos un alumno con esa CURP.'],
    'alumno_ya_vinculado' => ['error', 'Ese alumno ya está vinculado a otro tutor.'],
    'alumno_ya_es_tuyo' => ['success', 'Ese alumno ya estaba vinculado a tu cuenta y quedó seleccionado.'],
    'token_invalido' => ['error', 'El token es incorrecto, no corresponde al alumno o ya expiró.'],
    'datos_invalidos' => ['error', 'Revisa que la CURP tenga 18 caracteres y escribe el token.'],
    'csrf_invalido' => ['error', 'La sesión del formulario expiró. Vuelve a intentarlo.'],
    'error_bd' => ['error', 'No fue posible completar la vinculación. Inténtalo nuevamente.']
];
$codigo = $_GET['exito'] ?? $_GET['error'] ?? null;
?>
<link rel="stylesheet" href="css/mishijos.css">

<?php if ($codigo && isset($mensajes[$codigo])): [$tipo, $texto] = $mensajes[$codigo]; ?>
<div class="hijos-alert <?= $tipo === 'success' ? 'is-success' : 'is-error' ?>" role="alert">
    <i class="fa-solid <?= $tipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
    <span><?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?></span>
</div>
<?php endif; ?>

<header class="hijos-header-container">
    <div>
        <h1>Mis hijos</h1>
        <p>Vincula a tus hijos, revisa sus datos y elige con quién deseas trabajar.</p>
    </div>
    <button class="btn-agregar-hijo" id="btnOpenAddModal" type="button">
        <i class="fa-solid fa-user-plus"></i> Vincular hijo
    </button>
</header>

<div class="hijos-grid-layout">
<?php if ($hijos): ?>
    <?php foreach ($hijos as $hijo):
        $id = (int)$hijo['ID_Alumno'];
        $nombre = trim($hijo['Nombre'].' '.$hijo['Primer_Apellido'].' '.($hijo['Segundo_Apellido'] ?? ''));
        $grupo = ($hijo['Grado'] !== null || $hijo['Grupo'] !== null)
            ? trim(($hijo['Grado'] ?? '').'° '.($hijo['Grupo'] ?? ''))
            : 'Sin grupo asignado';
        $activo = $id === $idAlumnoActivo;
        $inicial = mb_strtoupper(mb_substr($hijo['Nombre'], 0, 1, 'UTF-8'), 'UTF-8');
    ?>
    <article class="replica-student-card <?= $activo ? 'is-active' : '' ?>">
        <div class="card-top-block">
            <div class="card-avatar"><?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="card-info">
                <h3><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($activo): ?><span class="active-student-badge">Alumno activo</span><?php endif; ?>
            </div>
        </div>

        <dl class="student-summary">
            <div><dt>CURP</dt><dd><?= htmlspecialchars($hijo['CURP'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Calificaciones</dt><dd><?= (int)$hijo['Total_Calificaciones'] ?></dd></div>
            <div><dt>Incidencias</dt><dd><?= (int)$hijo['Total_Incidencias'] ?></dd></div>
        </dl>

        <div class="card-actions">
            <button type="button" class="btn-ver-hijo"
                data-id="<?= $id ?>"
                data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                data-curp="<?= htmlspecialchars($hijo['CURP'], ENT_QUOTES, 'UTF-8') ?>"
                data-grupo="<?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>"
                data-registro="<?= htmlspecialchars((string)$hijo['Fecha_Registro'], ENT_QUOTES, 'UTF-8') ?>"
                data-calificaciones="<?= (int)$hijo['Total_Calificaciones'] ?>"
                data-incidencias="<?= (int)$hijo['Total_Incidencias'] ?>">
                <i class="fa-solid fa-eye"></i> Ver información
            </button>
            <?php if (!$activo): ?>
                <a href="cambiar_alumno.php?id=<?= $id ?>" class="btn-seleccionar-hijo">
                    Seleccionar <i class="fa-solid fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
<?php else: ?>
    <div class="hijos-empty-state">
        <i class="fa-solid fa-children"></i>
        <h3>Aún no tienes hijos vinculados</h3>
        <p>Solicita a la escuela la CURP y el token del alumno, después pulsa “Vincular hijo”.</p>
    </div>
<?php endif; ?>
</div>

<div class="modal-vinculacion-overlay" id="addHijoOverlay" aria-hidden="true">
    <div class="modal-vinculacion-card" role="dialog" aria-modal="true" aria-labelledby="tituloVincularHijo">
        <button id="btnCloseAddModal" type="button" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        <h2 id="tituloVincularHijo">Vincular hijo mediante token</h2>
        <p class="modal-help-text">Los datos deben coincidir exactamente con el alumno registrado por la escuela.</p>
        <form action="vistas/procesar_vinculacion.php" method="POST" class="modal-form" id="formVincularHijo">
            <?= campoCSRF() ?>
            <div class="form-group">
                <label for="curpAlumno">CURP del alumno</label>
                <input id="curpAlumno" type="text" name="curp_alumno" maxlength="18" minlength="18" autocomplete="off" placeholder="18 caracteres" required>
                <small>Se convertirá automáticamente a mayúsculas.</small>
            </div>
            <div class="form-group">
                <label for="tokenAlumno">Token de vinculación</label>
                <input id="tokenAlumno" type="text" name="token_alumno" autocomplete="off" placeholder="Token proporcionado por la escuela" required>
            </div>
            <button type="submit" class="btn-submit-modal"><i class="fa-solid fa-link"></i> Vincular alumno</button>
        </form>
    </div>
</div>

<div class="modal-vinculacion-overlay" id="detailHijoOverlay" aria-hidden="true">
    <div class="modal-vinculacion-card detail-student-modal" role="dialog" aria-modal="true" aria-labelledby="detailHijoNombre">
        <button id="btnCloseDetailModal" type="button" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        <div class="detail-avatar"><i class="fa-solid fa-user-graduate"></i></div>
        <h2 id="detailHijoNombre">Información del alumno</h2>
        <div class="detail-grid">
            <div><span>CURP</span><strong id="detailHijoCurp">—</strong></div>
            <div><span>Grupo</span><strong id="detailHijoGrupo">—</strong></div>
            <div><span>Fecha de registro</span><strong id="detailHijoRegistro">—</strong></div>
            <div><span>Calificaciones registradas</span><strong id="detailHijoCalificaciones">0</strong></div>
            <div><span>Incidencias registradas</span><strong id="detailHijoIncidencias">0</strong></div>
        </div>
    </div>
</div>

<script src="js/mishijos.js"></script>
