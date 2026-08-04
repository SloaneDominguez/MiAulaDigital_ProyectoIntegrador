<?php
$total_alumnos=$total_docentes=$total_padres=$total_grupos=0;
if(isset($conexion)){
    $res=$conexion->query("SELECT COUNT(*) total FROM alumno"); if($res&&$fila=$res->fetch_assoc()) $total_alumnos=$fila['total'];
    $res=$conexion->query("SELECT COUNT(*) total FROM docente"); if($res&&$fila=$res->fetch_assoc()) $total_docentes=$fila['total'];
    $res=$conexion->query("SELECT COUNT(*) total FROM padre_tutor"); if($res&&$fila=$res->fetch_assoc()) $total_padres=$fila['total'];
    $res=$conexion->query("SELECT COUNT(*) total FROM grupo"); if($res&&$fila=$res->fetch_assoc()) $total_grupos=$fila['total'];

    $sql_avisos="SELECT p.Titulo,p.Contenido,p.Fecha_Publicacion,a.Tipo_Aviso
                 FROM publicacion p INNER JOIN aviso a ON p.ID_Publicacion=a.ID_Publicacion
                 ORDER BY p.Fecha_Publicacion DESC,p.ID_Publicacion DESC LIMIT 4";
    $res_avisos=$conexion->query($sql_avisos);

    $sql_incidencias="SELECT i.Tipo_Incidencia,i.Descripcion,i.Fecha,i.Estado,
                      CONCAT(al.Nombre,' ',al.Primer_Apellido) Alumno
                      FROM incidencia i INNER JOIN alumno al ON i.ID_Alumno=al.ID_Alumno
                      ORDER BY i.Fecha DESC,i.ID_Incidencia DESC LIMIT 4";
    $res_incidencias=$conexion->query($sql_incidencias);

    $sql_solicitudes="SELECT ID_Solicitud,Nombre,Primer_Apellido,Segundo_Apellido,Usuario,Correo,Fecha_Solicitud
                      FROM solicitud_acceso WHERE Estado='Pendiente'
                      ORDER BY Fecha_Solicitud DESC LIMIT 4";
    $res_solicitudes=$conexion->query($sql_solicitudes);
}
?>
<header class="content-header">
  <h1>Panel Dirección</h1>
  <p class="subtitle">Datos en tiempo real — <?=date('d/m/Y H:i');?></p>
</header>
<section class="cards-grid">
  <div class="widget-card"><div class="card-data"><span class="card-title">Alumnos</span><h2 class="card-number"><?=$total_alumnos;?></h2></div><div class="card-icon icon-red"><i class="fa-solid fa-user-graduate"></i></div></div>
  <div class="widget-card"><div class="card-data"><span class="card-title">Docentes</span><h2 class="card-number"><?=$total_docentes;?></h2></div><div class="card-icon icon-blue"><i class="fa-solid fa-chalkboard-user"></i></div></div>
  <div class="widget-card"><div class="card-data"><span class="card-title">Padres</span><h2 class="card-number"><?=$total_padres;?></h2></div><div class="card-icon icon-gray"><i class="fa-solid fa-people-roof"></i></div></div>
  <div class="widget-card"><div class="card-data"><span class="card-title">Grupos</span><h2 class="card-number"><?=$total_grupos;?></h2></div><div class="card-icon icon-orange"><i class="fa-solid fa-school"></i></div></div>
</section>

<section class="dashboard-sections">
  <!-- SOLICITUDES -->
  <div class="section-box">
    <h3><i class="fa-solid fa-clock text-red"></i> Solicitudes pendientes</h3>
    <div class="announcements-list">
      <?php if(isset($res_solicitudes)&&$res_solicitudes->num_rows>0): while($sol=$res_solicitudes->fetch_assoc()): ?>
        <div class="announcement-item">
          <div class="announcement-header">
            <h4><?=htmlspecialchars($sol['Nombre'].' '.$sol['Primer_Apellido'].' '.$sol['Segundo_Apellido']);?></h4>
            <span class="badge aviso">Pendiente</span>
          </div>
          <p><strong>Usuario:</strong> <?=htmlspecialchars($sol['Usuario']);?></p>
          <p><strong>Correo:</strong> <?=htmlspecialchars($sol['Correo']);?></p>
          <small style="color:#94a3b8;display:block;margin-top:5px;font-size:11px;">Solicitud enviada el <?=date('d/m/Y H:i',strtotime($sol['Fecha_Solicitud']));?></small>
        </div>
      <?php endwhile; else: ?><p class="empty-text">No existen solicitudes pendientes.</p><?php endif; ?>
    </div>
  </div>

  <!-- AVISOS -->
  <div class="section-box">
    <h3><i class="fa-solid fa-bell text-red"></i> Avisos recientes</h3>
    <div class="announcements-list">
      <?php if(isset($res_avisos)&&$res_avisos->num_rows>0): while($row=$res_avisos->fetch_assoc()): ?>
        <div class="announcement-item">
          <div class="announcement-header">
            <h4><?=htmlspecialchars($row['Titulo']);?></h4>
            <span class="badge aviso"><?=htmlspecialchars($row['Tipo_Aviso']);?></span>
          </div>
          <p><?=htmlspecialchars($row['Contenido']);?></p>
          <small style="color:#94a3b8;display:block;margin-top:5px;font-size:11px;">Fecha: <?=date('d/m/Y',strtotime($row['Fecha_Publicacion']));?></small>
        </div>
      <?php endwhile; else: ?><p class="empty-text">No existen avisos registrados.</p><?php endif; ?>
    </div>
  </div>

  <!-- INCIDENCIAS -->
  <div class="section-box">
    <h3><i class="fa-solid fa-triangle-exclamation text-red"></i> Incidencias recientes</h3>
    <div class="announcements-list">
      <?php if(isset($res_incidencias)&&$res_incidencias->num_rows>0): while($inc=$res_incidencias->fetch_assoc()): ?>
        <div class="announcement-item">
          <div class="announcement-header">
            <h4><?=htmlspecialchars($inc['Tipo_Incidencia']);?></h4>
            <span class="badge tarea"><?=htmlspecialchars($inc['Estado']);?></span>
          </div>
          <p><strong>Alumno:</strong> <?=htmlspecialchars($inc['Alumno']);?></p>
          <p><?=htmlspecialchars($inc['Descripcion']);?></p>
          <small style="color:#94a3b8;display:block;margin-top:5px;font-size:11px;">Fecha: <?=date('d/m/Y',strtotime($inc['Fecha']));?></small>
        </div>
      <?php endwhile; else: ?><p class="empty-text">No existen incidencias registradas.</p><?php endif; ?>
    </div>
  </div>
</section>
