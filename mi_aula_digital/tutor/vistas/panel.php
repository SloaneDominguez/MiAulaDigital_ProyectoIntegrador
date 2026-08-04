<?php
// vistas/panel.php

$id_alumno_tutorado   = $id_alumno_tutorado ?? null;
$nombre_alumno         = $nombre_alumno ?? "Estudiante";
$id_grupo_alumno       = $id_grupo_alumno ?? null;
$total_anuncios        = $total_anuncios ?? 0;
$total_calificaciones  = $total_calificaciones ?? 0;
$total_incidencias     = $total_incidencias ?? 0;

$res_recientes_tutor = false;

if (isset($conexion)) {
    $id_grupo_filtrar = $id_grupo_alumno ? intval($id_grupo_alumno) : 0;

    // Consulta adaptada a los nombres exactos de columnas de tu base de datos
    $sql_recientes_tutor = "
        SELECT 
            p.Titulo, 
            p.Contenido, 
            p.Fecha_Publicacion,
            CASE 
                WHEN a.Tipo_Aviso = 'Institucional' OR p.ID_Director IS NOT NULL OR p.ID_Grupo IS NULL THEN 'Aviso'
                ELSE 'Tarea'
            END AS Tipo_Publicacion
        FROM publicacion p
        LEFT JOIN aviso a ON p.ID_Publicacion = a.ID_Publicacion
        WHERE 
            (p.ID_Grupo = ?) 
            OR (p.ID_Grupo IS NULL)          
            OR (p.ID_Director IS NOT NULL)   
            OR (a.Tipo_Aviso IS NOT NULL)    
        ORDER BY p.Fecha_Publicacion DESC, p.ID_Publicacion DESC 
        LIMIT 3
    ";
    
    $stmt_recientes = $conexion->prepare($sql_recientes_tutor);
    if ($stmt_recientes) {
        $stmt_recientes->bind_param("i", $id_grupo_filtrar);
        $stmt_recientes->execute();
        $res_recientes_tutor = $stmt_recientes->get_result();
    }
}
?>

<header class="content-header">
    <h1 style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">Panel Padre / Tutor</h1>
    <p class="subtitle" style="font-size: 13px; color: #64748b; margin: 0;">Seguimiento académico y actividades de: <strong><?php echo htmlspecialchars($nombre_alumno, ENT_QUOTES, 'UTF-8'); ?></strong></p>
</header>

<div class="tutor-dashboard-grid">
    
    <div class="panel-card-box">
        <h3><i class="fa-solid fa-bullhorn" style="color: #b61a1a; margin-right: 8px;"></i> Actividades y anuncios recientes</h3>
        
        <div class="notices-list">
            <?php if ($res_recientes_tutor && $res_recientes_tutor->num_rows > 0): ?>
                <?php while ($publicacion = $res_recientes_tutor->fetch_assoc()): ?>
                    <div class="notice-item-row" style="padding: 14px 0; border-bottom: 1px dashed #e2e8f0;">
                        <div class="notice-meta" style="display: flex; justify-content: space-between; margin-bottom: 6px; align-items: center;">
                            <?php if ($publicacion['Tipo_Publicacion'] === 'Aviso'): ?>
                                <span class="badge-item" style="background-color: #fee2e2; color: #b61a1a; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Institucional</span>
                            <?php else: ?>
                                <span class="badge-item" style="background-color: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">Tarea Escolar</span>
                            <?php endif; ?>
                            <span style="font-size: 12px; color: #64748b;"><?php echo date('d/m/Y', strtotime($publicacion['Fecha_Publicacion'])); ?></span>
                        </div>
                        <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;"><?php echo htmlspecialchars($publicacion['Titulo'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p style="font-size: 13px; color: #475569; margin: 0; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($publicacion['Contenido'], ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; color: #94a3b8; font-size: 14px;">
                    <i class="fa-regular fa-folder-open" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                    No hay publicaciones o tareas recientes para este alumno.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel-card-box">
        <h3><i class="fa-solid fa-chart-simple" style="color: #64748b; margin-right: 8px;"></i> Resumen académico</h3>
        
        <div class="metrics-column">
            
            <a href="dashboard.php?view=calificaciones" class="metric-row-card" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center;">
                <div class="metric-row-left" style="display: flex; align-items: center; gap: 12px;">
                    <div class="metric-icon-wrapper bg-emerald text-emerald" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px;">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div class="metric-row-info">
                        <span class="metric-label" style="display: block; font-weight: 600; color: #0f172a;">Calificaciones</span>
                        <span class="metric-desc" style="font-size: 12px; color: #64748b;">Evaluaciones asentadas</span>
                    </div>
                </div>
                <span class="metric-counter counter-emerald" style="font-weight: 700; font-size: 16px;"><?php echo $total_calificaciones; ?></span>
            </a>

            <a href="dashboard.php?view=anuncios" class="metric-row-card" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <div class="metric-row-left" style="display: flex; align-items: center; gap: 12px;">
                    <div class="metric-icon-wrapper bg-blue text-blue" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px;">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="metric-row-info">
                        <span class="metric-label" style="display: block; font-weight: 600; color: #0f172a;">Tareas y Avisos</span>
                        <span class="metric-desc" style="font-size: 12px; color: #64748b;">Pendientes y circulares</span>
                    </div>
                </div>
                <span class="metric-counter counter-blue" style="font-weight: 700; font-size: 16px;"><?php echo $total_anuncios; ?></span>
            </a>

            <a href="dashboard.php?view=incidencias" class="metric-row-card" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                <div class="metric-row-left" style="display: flex; align-items: center; gap: 12px;">
                    <div class="metric-icon-wrapper bg-amber text-amber" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="metric-row-info">
                        <span class="metric-label" style="display: block; font-weight: 600; color: #0f172a;">Incidencias</span>
                        <span class="metric-desc" style="font-size: 12px; color: #64748b;">Reportes de comportamiento</span>
                    </div>
                </div>
                <span class="metric-counter counter-amber" style="font-weight: 700; font-size: 16px;"><?php echo $total_incidencias; ?></span>
            </a>

        </div>
    </div>
</div>