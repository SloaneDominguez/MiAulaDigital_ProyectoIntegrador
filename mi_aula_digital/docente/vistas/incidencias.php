<?php
// =======================================================
// PROCESAMIENTO DE ACCIONES (REGISTRAR, EDITAR, ELIMINAR)
// =======================================================

if (!isset($conexion)) {
    die("Error: No existe conexión con la base de datos.");
}

if (!isset($id_docente_sesion)) {
    die("Error: No se encontró el ID del docente en sesión.");
}

// 1. ACCIÓN: GUARDAR NUEVA INCIDENCIA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
    
    $id_alumno = intval($_POST['id_alumno'] ?? 0);
    $tipo_incidencia = trim($_POST['tipo_incidencia'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_actual = date('Y-m-d');
    $estado_inicial = 'Pendiente';

    if ($id_alumno > 0 && !empty($tipo_incidencia) && !empty($descripcion)) {
        $stmt = $conexion->prepare("
            INSERT INTO incidencia 
            (tipo_incidencia, descripcion, fecha, estado, id_alumno, id_docente) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param("ssssii", $tipo_incidencia, $descripcion, $fecha_actual, $estado_inicial, $id_alumno, $id_docente_sesion);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?view=incidencias&status=success_reg");
                exit();
            }
            $stmt->close();
        }
    }
    header("Location: dashboard.php?view=incidencias&status=error_reg");
    exit();
}

// 2. ACCIÓN: EDITAR INCIDENCIA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    
    $id_incidencia = intval($_POST['id_incidencia'] ?? 0);
    $tipo_incidencia = trim($_POST['tipo_incidencia'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado = trim($_POST['estado'] ?? 'Pendiente');

    if ($id_incidencia > 0 && !empty($tipo_incidencia) && !empty($descripcion)) {
        // Verificamos que pertenezca al docente en sesión por seguridad
        $stmt = $conexion->prepare("
            UPDATE incidencia 
            SET tipo_incidencia = ?, descripcion = ?, estado = ? 
            WHERE id_incidencia = ? AND id_docente = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("sssii", $tipo_incidencia, $descripcion, $estado, $id_incidencia, $id_docente_sesion);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?view=incidencias&status=success_edit");
                exit();
            }
            $stmt->close();
        }
    }
    header("Location: dashboard.php?view=incidencias&status=error_edit");
    exit();
}

// 3. ACCIÓN: ELIMINAR INCIDENCIA
if (isset($_GET['action']) && $_GET['action'] === 'eliminar' && isset($_GET['id'])) {
    $id_incidencia = intval($_GET['id']);

    if ($id_incidencia > 0) {
        $stmt = $conexion->prepare("DELETE FROM incidencia WHERE id_incidencia = ? AND id_docente = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $id_incidencia, $id_docente_sesion);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?view=incidencias&status=success_del");
                exit();
            }
            $stmt->close();
        }
    }
    header("Location: dashboard.php?view=incidencias&status=error_del");
    exit();
}

// =======================================================
// CONSULTAS PARA MOSTRAR DATOS EN LA VISTA
// =======================================================

$sql_lista_incidencias = "
    SELECT 
        i.id_incidencia, 
        i.tipo_incidencia, 
        i.descripcion, 
        i.fecha, 
        i.estado, 
        a.id_alumno,
        a.nombre, 
        a.primer_apellido, 
        a.segundo_apellido, 
        g.grado, 
        g.grupo 
    FROM incidencia i 
    INNER JOIN alumno a ON i.id_alumno = a.id_alumno 
    INNER JOIN grupo g ON a.id_grupo = g.id_grupo
    WHERE i.id_docente = ? 
    ORDER BY i.fecha DESC, i.id_incidencia DESC
";

$stmt_inc = $conexion->prepare($sql_lista_incidencias);
if ($stmt_inc) {
    $stmt_inc->bind_param("i", $id_docente_sesion);
    $stmt_inc->execute();
    $res_lista_incidencias = $stmt_inc->get_result();
    $stmt_inc->close();
} else {
    $res_lista_incidencias = null;
}

$sql_mis_alumnos = "
    SELECT a.id_alumno, a.nombre, a.primer_apellido, a.segundo_apellido, g.id_grupo, g.grado, g.grupo
    FROM alumno a
    INNER JOIN docente d ON a.id_grupo = d.id_grupo_asignado
    INNER JOIN grupo g ON a.id_grupo = g.id_grupo
    WHERE d.id_docente = ?
    ORDER BY g.grado, g.grupo, a.primer_apellido, a.nombre
";
$stmt_alumn = $conexion->prepare($sql_mis_alumnos);

$alumnos_json = [];
$grupos_unicos = [];

if ($stmt_alumn) {
    $stmt_alumn->bind_param("i", $id_docente_sesion);
    $stmt_alumn->execute();
    $res_mis_alumnos = $stmt_alumn->get_result();

    while ($al = $res_mis_alumnos->fetch_assoc()) {
        $alumnos_json[] = $al;
        $id_g = $al['id_grupo'];
        $grupos_unicos[$id_g] = $al['grado'] . "°" . $al['grupo'];
    }
    $stmt_alumn->close();
}
?>

<link rel="stylesheet" href="css/incidencias.css">

<div class="incidencias-container">
    
    <header class="incidencias-header">
        <div>
            <h1>Incidencias</h1>
            <p class="subtitle">Reporte y seguimiento de conductas o situaciones escolares</p>
        </div>
        <button class="btn-nueva-incidencia" id="btnAbrirModalInc">
            <i class="fa-solid fa-plus"></i> Reportar incidencia
        </button>
    </header>

    <!-- MENSAJES DE ALERTA AUTOMÁTICOS -->
    <?php if (isset($_GET['status'])): ?>
        <?php 
            $mensajes = [
                'success_reg' => ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'El reporte de incidencia ha sido creado exitosamente.'],
                'error_reg'   => ['bg' => '#fee2e2', 'color' => '#991b1b', 'txt' => 'Error al procesar el reporte.'],
                'success_edit'=> ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'La incidencia ha sido actualizada correctamente.'],
                'error_edit'  => ['bg' => '#fee2e2', 'color' => '#991b1b', 'txt' => 'Error al actualizar la incidencia.'],
                'success_del' => ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'La incidencia ha sido eliminada exitosamente.'],
                'error_del'   => ['bg' => '#fee2e2', 'color' => '#991b1b', 'txt' => 'Error al eliminar la incidencia.']
            ];
            $status_key = $_GET['status'];
            if (isset($mensajes[$status_key])):
        ?>
            <div id="alerta-mensaje" style="padding:15px; margin-bottom:20px; border-radius:8px; font-weight:500; background:<?= $mensajes[$status_key]['bg']; ?>; color:<?= $mensajes[$status_key]['color']; ?>;">
                <?= $mensajes[$status_key]['txt']; ?>
            </div>
        <?php endif; endif; ?>

    <div class="table-container">
        <table class="incidencias-table">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Grupo</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($res_lista_incidencias) && $res_lista_incidencias && $res_lista_incidencias->num_rows > 0): ?>
                    <?php while ($inc = $res_lista_incidencias->fetch_assoc()): ?>
                        <tr>
                            <td class="student-name">
                                <?= htmlspecialchars($inc['primer_apellido'] . ' ' . $inc['segundo_apellido'] . ' ' . $inc['nombre']); ?>
                            </td>
                            <td>
                                <span class="badge-group"><?= htmlspecialchars($inc['grado'] . '°' . $inc['grupo']); ?></span>
                            </td>
                            <td>
                                <span class="badge-tipo">
                                    <i class="fa-solid fa-triangle-exclamation" style="color: #d97706;"></i> 
                                    <?= htmlspecialchars($inc['tipo_incidencia']); ?>
                                </span>
                            </td>
                            <td class="cell-descripcion">
                                <?= nl2br(htmlspecialchars($inc['descripcion'])); ?>
                            </td>
                            <td class="cell-date"><?= date('d/m/Y', strtotime($inc['fecha'])); ?></td>
                            <td>
                                <?php if ($inc['estado'] === 'Pendiente'): ?>
                                    <span class="status-badge-pending">Pendiente</span>
                                <?php else: ?>
                                    <span class="status-badge-success">Resuelto</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <!-- Botón Editar -->
                                <button class="btn-editar-inc" 
                                    style="background:#3b82f6; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; margin-right:4px;"
                                    data-id="<?= $inc['id_incidencia']; ?>"
                                    data-tipo="<?= htmlspecialchars($inc['tipo_incidencia']); ?>"
                                    data-descripcion="<?= htmlspecialchars($inc['descripcion'], ENT_QUOTES); ?>"
                                    data-estado="<?= $inc['estado']; ?>"
                                    data-alumno="<?= htmlspecialchars($inc['primer_apellido'] . ' ' . $inc['segundo_apellido'] . ' ' . $inc['nombre']); ?>">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                
                                <!-- Botón Eliminar -->
                                <a href="dashboard.php?view=incidencias&action=eliminar&id=<?= $inc['id_incidencia']; ?>" 
                                   style="background:#ef4444; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; text-decoration:none; display:inline-block;"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar esta incidencia?');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 30px; text-align: center; color: #94a3b8;">
                            No se han registrado reportes de incidencias.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL ÚNICO (ADAPTABLE PARA REGISTRAR Y EDITAR) -->
<div class="modal-overlay" id="modalIncidencia">
    <div class="modal-content">
        <button class="btn-cerrar-x" id="btnCerrarX">&times;</button>
        <h2 id="modalTitulo">Nueva incidencia</h2>
        
        <form method="POST" action="dashboard.php?view=incidencias" class="modal-form-vertical" id="formIncidenciaModal">
            <!-- Input oculto para cambiar la acción: 'registrar' o 'editar' -->
            <input type="hidden" name="accion" id="inputAccionModal" value="registrar">
            <input type="hidden" name="id_incidencia" id="inputIdIncidencia" value="">

            <div class="form-group" id="grupoContenedorSelects">
                <label>Grupo</label>
                <select id="select_grupo_modal" required>
                    <option value="">Grupo</option>
                    <?php foreach ($grupos_unicos as $id_g => $nombre_g): ?>
                        <option value="<?= $id_g; ?>"><?= htmlspecialchars($nombre_g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="alumnoContenedorSelects">
                <label>Alumno</label>
                <select name="id_alumno" id="select_alumno_modal" disabled required>
                    <option value="">Alumno</option>
                </select>
            </div>

            <!-- Campo de texto estático que se muestra solo al editar para ver el alumno sin modificarlo -->
            <div class="form-group" id="alumnoContenedorTexto" style="display: none;">
                <label>Alumno</label>
                <input type="text" id="inputAlumnoTextoFijo" readonly style="background: #f1f5f9; cursor: not-allowed;">
            </div>

            <div class="form-group">
                <label>Tipo de incidencia</label>
                <select name="tipo_incidencia" id="selectTipoIncidencia" required>
                    <option value="">Seleccionar</option>
                    <option value="Conducta">Conducta Inadecuada</option>
                    <option value="Académica">Bajo Rendimiento Académico</option>
                    <option value="Inasistencia">Inasistencias Frecuentes</option>
                    <option value="Salud">Situación de Salud / Médica</option>
                    <option value="Otro">Otro caso particular</option>
                </select>
            </div>

            <!-- Campo de Estado (Solo visible al Editar) -->
            <div class="form-group" id="campoEstadoModal" style="display: none;">
                <label>Estado</label>
                <select name="estado" id="selectEstadoIncidencia">
                    <option value="Pendiente">Pendiente</option>
                    <option value="Resuelto">Resuelto</option>
                </select>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="textareaDescripcion" placeholder="Escribe la descripción aquí..." required></textarea>
            </div>

            <button type="submit" class="btn-registrar-submit" id="btnSubmitModalText">Registrar incidencia</button>
        </form>
    </div>
</div>

<script>
    const alumnosDisponibles = <?= json_encode($alumnos_json); ?>;
</script>

<script src="js/incidencias.js"></script>