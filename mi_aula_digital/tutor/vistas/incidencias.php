<?php
// incidencias.php (Vista del Padre/Tutor - Filtrada por el alumno activo en sesión)

// =======================================================
// CONTROL DE SEGURIDAD Y CONFIGURACIÓN INICIAL
// =======================================================
if (!isset($conexion)) {
    die("Error: No existe conexión con la base de datos.");
}

// Asegurar que la sesión de PHP esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_padre_sesion = $_SESSION['id_padre'] ?? null;
$id_alumno_sesion = $_SESSION['id_alumno'] ?? null;

// =======================================================
// DETERMINAR ALUMNO EN SESIÓN Y VALIDAR PERTENENCIA
// =======================================================
$alumno_activo = null;
$res_incidencias = false;

if ($id_alumno_sesion && $id_padre_sesion) {
    // Validar que el alumno pertenezca al padre logueado por seguridad
    $sql_validar_alumno = "SELECT ID_Alumno, Nombre, Primer_Apellido 
                           FROM alumno 
                           WHERE ID_Alumno = ? AND ID_Padre = ? LIMIT 1";
    
    $stmt_val = $conexion->prepare($sql_validar_alumno);
    if ($stmt_val) {
        $stmt_val->bind_param("ii", $id_alumno_sesion, $id_padre_sesion);
        $stmt_val->execute();
        $res_validar_alumno = $stmt_val->get_result();
        
        if ($res_validar_alumno && $res_validar_alumno->num_rows > 0) {
            $alumno_activo = $res_validar_alumno->fetch_assoc();
        }
        $stmt_val->close();
    }
}

// =======================================================
// CONSULTA DE INCIDENCIAS (Estricto para el alumno activo)
// =======================================================
if ($alumno_activo) {
    $id_al = $alumno_activo['ID_Alumno'];
    
    // Consulta segura con prepared statements para traer las incidencias del alumno actual
    $sql_incidencias = "SELECT i.ID_Incidencia, i.Descripcion, i.Fecha, i.Estado, 
                               a.Nombre as Nombre_Alumno, a.Primer_Apellido as Apellido_Alumno,
                               d.Nombre as Nombre_Docente, d.Primer_Apellido as Apellido_Docente
                        FROM incidencia i
                        INNER JOIN alumno a ON i.ID_Alumno = a.ID_Alumno
                        LEFT JOIN docente d ON i.ID_Docente = d.ID_Docente
                        WHERE a.ID_Alumno = ?
                        ORDER BY i.Fecha DESC";
    
    $stmt_inc = $conexion->prepare($sql_incidencias);
    if ($stmt_inc) {
        $stmt_inc->bind_param("i", $id_al);
        $stmt_inc->execute();
        $res_incidencias = $stmt_inc->get_result();
        $stmt_inc->close();
    }
}
?>

<link rel="stylesheet" href="css/incidencias.css">

<div class="incidencias-header-box">
    <h1>Incidencias del Alumno</h1>
    <p>
        <?php if($alumno_activo): ?>
            Reporte de incidencias de: <strong style="color:#0284c7;"><?php echo htmlspecialchars($alumno_activo['Nombre'] . " " . $alumno_activo['Primer_Apellido'], ENT_QUOTES, 'UTF-8'); ?></strong>
        <?php else: ?>
            No hay un alumno seleccionado o no se tienen permisos para consultarlo.
        <?php endif; ?>
    </p>
</div>

<div class="table-container-replica">
    
    <?php if ($alumno_activo && $res_incidencias && $res_incidencias->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th class="col-fecha">Fecha</th>
                    <th class="col-alumno">Alumno</th>
                    <th class="col-docente">Docente</th>
                    <th class="col-desc">Descripción</th>
                    <th class="col-estado">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res_incidencias->fetch_assoc()): ?>
                    <?php 
                        $fecha_formateada = date("d/m/Y", strtotime($row['Fecha']));
                        $docente_txt = !empty($row['Nombre_Docente']) ? $row['Nombre_Docente'] . " " . $row['Apellido_Docente'] : "Reporte de Dirección";
                        $alumno_txt = $row['Nombre_Alumno'] . " " . $row['Apellido_Alumno'];
                        
                        // Estilos del badge de estado
                        $estado = strtolower($row['Estado'] ?? 'pendiente');
                        $badge_colors = "background-color: #f1f5f9; color: #475569;"; 
                        
                        if ($estado == 'resuelta' || $estado == 'resuelto' || $estado == 'atendido') {
                            $badge_colors = "background-color: #dcfce7; color: #15803d;";
                        } elseif ($estado == 'pendiente' || $estado == 'grave') {
                            $badge_colors = "background-color: #ffeeeb; color: #b61a1a;";
                        }
                    ?>
                    <tr class="table-row-hover">
                        <td class="cell-fecha"><?php echo $fecha_formateada; ?></td>
                        <td class="cell-alumno"><?php echo htmlspecialchars($alumno_txt, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell-docente"><?php echo htmlspecialchars($docente_txt, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell-desc"><?php echo htmlspecialchars($row['Descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="badge-estado" style="<?php echo $badge_colors; ?>">
                                <?php echo htmlspecialchars($row['Estado'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        
        <div class="empty-state-container">
            <div class="empty-table-header">
                <div class="empty-header-col eh-fecha">Fecha</div>
                <div class="empty-header-col eh-docente">Docente</div>
                <div class="empty-header-col eh-desc">Descripción</div>
                <div class="empty-header-col eh-estado">Estado</div>
            </div>
            <div class="empty-state-body">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>
                    <?php 
                    if (!$alumno_activo) {
                        echo "Selecciona un alumno válido desde la sección 'Mis hijos' para ver sus incidencias.";
                    } else {
                        echo "El alumno " . htmlspecialchars($alumno_activo['Nombre'], ENT_QUOTES, 'UTF-8') . " no cuenta con incidencias registradas.";
                    }
                    ?>
                </p>
            </div>
        </div>
        
    <?php endif; ?>
</div>