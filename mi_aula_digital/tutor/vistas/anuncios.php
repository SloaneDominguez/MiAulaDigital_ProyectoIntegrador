<?php
// anuncios.php (Vista del Padre/Tutor - Filtrada estrictamente por el alumno activo en sesión)

// =======================================================
// CONTROL DE SEGURIDAD Y CONFIGURACIÓN INICIAL
// =======================================================
if (!isset($conexion)) {
    die("Error: No existe conexión con la base de datos.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_padre_sesion = $_SESSION['id_padre'] ?? null;
$id_alumno_sesion = $_SESSION['id_alumno'] ?? null;

// =======================================================
// 1. DETERMINAR Y VALIDAR EL ALUMNO EN SESIÓN
// =======================================================
$id_alumno_seleccionado = null;
$id_grupo_alumno = null;

if ($id_alumno_sesion && $id_padre_sesion) {
    // Validar de forma segura que el alumno pertenezca al padre logueado
    $sql_val_al = "SELECT ID_Alumno, ID_Grupo FROM alumno WHERE ID_Alumno = ? AND ID_Padre = ? LIMIT 1";
    $stmt_val = $conexion->prepare($sql_val_al);
    
    if ($stmt_val) {
        $stmt_val->bind_param("ii", $id_alumno_sesion, $id_padre_sesion);
        $stmt_val->execute();
        $res_val = $stmt_val->get_result();
        
        if ($res_val && $res_val->num_rows > 0) {
            $row_al = $res_val->fetch_assoc();
            $id_alumno_seleccionado = $row_al['ID_Alumno'];
            $id_grupo_alumno = $row_al['ID_Grupo'];
        }
        $stmt_val->close();
    }
}

// Fallback por si no hay un alumno en sesión pero sí hijos registrados
if (!$id_alumno_seleccionado && $id_padre_sesion) {
    $sql_primer_hijo = "SELECT ID_Alumno, ID_Grupo FROM alumno WHERE ID_Padre = ? LIMIT 1";
    $stmt_primer = $conexion->prepare($sql_primer_hijo);
    if ($stmt_primer) {
        $stmt_primer->bind_param("i", $id_padre_sesion);
        $stmt_primer->execute();
        $res_primer = $stmt_primer->get_result();
        if ($res_primer && $res_primer->num_rows > 0) {
            $row_p = $res_primer->fetch_assoc();
            $id_alumno_seleccionado = $row_p['ID_Alumno'];
            $id_grupo_alumno = $row_p['ID_Grupo'];
            // Actualizar la sesión para sincronizar el alumno activo
            $_SESSION['id_alumno'] = $id_alumno_seleccionado;
        }
        $stmt_primer->close();
    }
}

$avisos_institucionales = [];
$tareas_docentes = [];

// =======================================================
// EXTRACCIÓN DE DATOS FILTRADOS POR EL ALUMNO ACTIVO
// =======================================================
if ($id_alumno_seleccionado) {
    $id_grupo_filtrar = $id_grupo_alumno ? intval($id_grupo_alumno) : 0;
    
    // A. OBTENER AVISOS INSTITUCIONALES (Avisos generales, sin grupo o dirigidos al grupo del alumno)
    $sql_avisos = "SELECT p.Titulo, p.Contenido, p.Fecha_Publicacion, a.Tipo_Aviso 
                   FROM publicacion p
                   INNER JOIN aviso a ON p.ID_Publicacion = a.ID_Publicacion
                   WHERE (p.ID_Grupo IS NULL OR p.ID_Grupo = ? OR p.ID_Director IS NOT NULL)
                   ORDER BY p.Fecha_Publicacion DESC";
                   
    $stmt_avisos = $conexion->prepare($sql_avisos);
    if ($stmt_avisos) {
        $stmt_avisos->bind_param("i", $id_grupo_filtrar);
        $stmt_avisos->execute();
        $res_avisos = $stmt_avisos->get_result();
        if ($res_avisos) {
            while ($row = $res_avisos->fetch_assoc()) {
                $avisos_institucionales[] = $row;
            }
        }
        $stmt_avisos->close();
    }

    // B. OBTENER TAREAS ESPECÍFICAS (Exclusivas del grupo al que pertenece el alumno activo)
    if ($id_grupo_alumno) {
        $sql_tareas = "SELECT p.Titulo, p.Contenido, p.Fecha_Publicacion, g.Grado, g.Grupo
                       FROM publicacion p
                       INNER JOIN grupo g ON p.ID_Grupo = g.ID_Grupo
                       WHERE p.ID_Grupo = ? 
                         AND p.ID_Docente IS NOT NULL
                       ORDER BY p.Fecha_Publicacion DESC";
                       
        $stmt_tareas = $conexion->prepare($sql_tareas);
        if ($stmt_tareas) {
            $stmt_tareas->bind_param("i", $id_grupo_filtrar);
            $stmt_tareas->execute();
            $res_tareas = $stmt_tareas->get_result();
            if ($res_tareas) {
                while ($row = $res_tareas->fetch_assoc()) {
                    $tareas_docentes[] = $row;
                }
            }
            $stmt_tareas->close();
        }
    }
}
?>

<link rel="stylesheet" href="css/anuncios.css">

<div class="anuncios-header-box">
    <h1>Anuncios y Tareas</h1>
    <p>Comunicados institucionales y tareas asignadas al alumno activo</p>
</div>

<div class="anuncios-tabs-container">
    <button class="tab-btn active" data-target="panel-avisos" type="button">
        <i class="fa-regular fa-bell"></i> Avisos institucionales
    </button>
    <button class="tab-btn" data-target="panel-tareas" type="button">
        <i class="fa-solid fa-book-open"></i> Tareas
    </button>
</div>

<div class="paneles-contenido">
    
    <div id="panel-avisos" class="tab-panel">
        <?php if (!empty($avisos_institucionales)): ?>
            <?php foreach ($avisos_institucionales as $aviso): ?>
                <div class="aviso-card-replica">
                    <div>
                        <span class="badge-aviso institucional">
                            <?php echo htmlspecialchars($aviso['Tipo_Aviso'] ?? 'Institucional', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <h3><?php echo htmlspecialchars($aviso['Titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($aviso['Contenido'], ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                    <div>
                        <span class="date-badge"><?php echo date("d/m/Y", strtotime($aviso['Fecha_Publicacion'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-panel-state">
                <i class="fa-regular fa-bell-slash"></i> No hay avisos institucionales para mostrar.
            </div>
        <?php endif; ?>
    </div>

    <div id="panel-tareas" class="tab-panel" style="display: none;">
        <?php if (!empty($tareas_docentes)): ?>
            <?php foreach ($tareas_docentes as $tarea): ?>
                <div class="aviso-card-replica">
                    <div>
                        <span class="badge-aviso tarea">
                            Tarea <?php echo htmlspecialchars($tarea['Grado'] . "°" . $tarea['Grupo'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <h3><?php echo htmlspecialchars($tarea['Titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($tarea['Contenido'], ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                    <div>
                        <span class="date-badge"><?php echo date("d/m/Y", strtotime($tarea['Fecha_Publicacion'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-panel-state">
                <i class="fa-solid fa-book-open"></i> No hay tareas asignadas para el grupo de este alumno.
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="js/anuncios.js"></script>