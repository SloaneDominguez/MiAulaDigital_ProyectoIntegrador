<?php
// Validar variables para evitar advertencias de "Undefined variable" en el editor de código
$total_alumnos     = isset($total_alumnos) ? $total_alumnos : 0;
$total_grupos      = isset($total_grupos) ? $total_grupos : 0;
$total_anuncios    = isset($total_anuncios) ? $total_anuncios : 0;
$total_incidencias = isset($total_incidencias) ? $total_incidencias : 0;
$id_docente_sesion = isset($id_docente_sesion) ? $id_docente_sesion : 1;

if (isset($conexion)) {
    // UNION SQL adaptado a minúsculas: Trae avisos de Dirección (id_director IS NOT NULL) y Tareas de este docente
    $sql_recientes = "(SELECT id_publicacion, titulo, contenido, 'Aviso' AS tipo, fecha_publicacion 
                       FROM publicacion 
                       WHERE id_director IS NOT NULL)
                      UNION
                      (SELECT id_publicacion, titulo, contenido, 'Tarea' AS tipo, fecha_publicacion 
                       FROM publicacion 
                       WHERE id_docente = $id_docente_sesion)
                      ORDER BY fecha_publicacion DESC, id_publicacion DESC LIMIT 4";
    
    $res_recientes = $conexion->query($sql_recientes);

    // Consulta en minúsculas para listar los grupos asignados con contador de alumnos
    $sql_lista_grupos = "SELECT g.grado, g.grupo, COUNT(a.id_alumno) AS total_alumnos 
                         FROM docente d 
                         INNER JOIN grupo g ON d.id_grupo_asignado = g.id_grupo 
                         LEFT JOIN alumno a ON g.id_grupo = a.id_grupo 
                         WHERE d.id_docente = $id_docente_sesion 
                         GROUP BY g.id_grupo";
    $res_lista_grupos = $conexion->query($sql_lista_grupos);
}
?>

<header class="content-header">
    <h1>Panel Docente</h1>
    <p class="subtitle">Datos en tiempo real — <?php echo date('d/m/Y H:i'); ?></p>
</header>

<section class="cards-grid">
    <div class="widget-card">
        <div class="card-data">
            <span class="card-title">Mis alumnos</span>
            <h2 class="card-number"><?php echo $total_alumnos; ?></h2>
        </div>
        <div class="card-icon icon-red"><i class="fa-solid fa-user-group"></i></div>
    </div>
    <div class="widget-card">
        <div class="card-data">
            <span class="card-title">Mis grupos</span>
            <h2 class="card-number"><?php echo $total_grupos; ?></h2>
        </div>
        <div class="card-icon icon-blue"><i class="fa-solid fa-book"></i></div>
    </div>
    <div class="widget-card">
        <div class="card-data">
            <span class="card-title">Anuncios</span>
            <h2 class="card-number"><?php echo $total_anuncios; ?></h2>
        </div>
        <div class="card-icon icon-gray"><i class="fa-solid fa-bell"></i></div>
    </div>
    <div class="widget-card">
        <div class="card-data">
            <span class="card-title">Incidencias</span>
            <h2 class="card-number"><?php echo $total_incidencias; ?></h2>
        </div>
        <div class="card-icon icon-orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
    </div>
</section>

<section class="dashboard-sections">
    
    <div class="section-box">
        <h3><i class="fa-solid fa-bell text-red"></i> Anuncios recientes</h3>
        <div class="announcements-list">
            <?php if (isset($res_recientes) && $res_recientes && $res_recientes->num_rows > 0): ?>
                <?php while($row = $res_recientes->fetch_assoc()): ?>
                    <div class="announcement-item">
                        <div class="announcement-header">
                            <h4><?php echo htmlspecialchars($row['titulo']); ?></h4>
                            <span class="badge <?php echo ($row['tipo'] === 'Aviso') ? 'aviso' : 'tarea'; ?>">
                                <?php echo $row['tipo']; ?>
                            </span>
                        </div>
                        <p><?php echo htmlspecialchars($row['contenido']); ?></p>
                        <small style="color: #94a3b8; font-size: 11px; display: block; margin-top: 5px;">
                            Fecha: <?php echo date('d/m/Y', strtotime($row['fecha_publicacion'])); ?>
                        </small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-text">No hay anuncios ni tareas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-box groups-section">
        <h3><i class="fa-solid fa-users"></i> Alumnos por grupo</h3>
        <div class="group-cards-container">
            <?php if (isset($res_lista_grupos) && $res_lista_grupos && $res_lista_grupos->num_rows > 0): ?>
                <?php while($grupo = $res_lista_grupos->fetch_assoc()): ?>
                    <div class="group-custom-card">
                        <div class="group-card-left">
                            <div class="group-card-icon">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="group-card-info">
                                <span class="group-card-name">Grupo: <?php echo htmlspecialchars($grupo['grado'] . "°" . $grupo['grupo']); ?></span>
                                <span class="group-card-sub">Total de alumnos inscritos</span>
                            </div>
                        </div>
                        <div class="group-card-right">
                            <span class="group-card-counter"><?php echo $grupo['total_alumnos']; ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-text">No tienes grupos asignados.</p>
            <?php endif; ?>
        </div>
    </div>

</section>