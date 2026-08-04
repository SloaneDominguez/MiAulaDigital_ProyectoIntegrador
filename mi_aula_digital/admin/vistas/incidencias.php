<?php
include_once("../config/conexion.php");

// FILTRO
$tipo=$_GET['tipo']??'';

// INCIDENCIAS
$sql="SELECT i.ID_Incidencia,i.Tipo_Incidencia,i.Descripcion,i.Fecha,
CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Alumno,
CONCAT(g.Grado,'° ',g.Grupo) AS Grupo
FROM incidencia i INNER JOIN alumno a ON i.ID_Alumno=a.ID_Alumno
LEFT JOIN grupo g ON a.ID_Grupo=g.ID_Grupo WHERE 1=1";
if(!empty($tipo)){ $tipo_safe=$conexion->real_escape_string($tipo); $sql.=" AND i.Tipo_Incidencia='$tipo_safe'"; }
$sql.=" ORDER BY i.Fecha DESC";
$res=$conexion->query($sql);

// GRUPOS (MODAL)
$grupos=$conexion->query("SELECT * FROM grupo ORDER BY Grado,Grupo");
?>

<header class="content-header">
  <div><h1>Incidencias</h1><p class="subtitle">Registro y control de incidencias escolares</p></div>
  <button id="abrirModalIncidencia" class="btn-primary"><i class="fa-solid fa-plus"></i> Nueva incidencia</button>
</header>

<section class="section-box">
  <form method="GET" class="search-form" style="margin-bottom:15px;justify-content:flex-start;">
    <input type="hidden" name="view" value="incidencias">
    <select name="tipo" class="search-select" onchange="this.form.submit()">
      <option value="">Todas las incidencias</option>
      <option value="Llegada tarde" <?=$tipo=='Llegada tarde'?'selected':''?>>Llegada tarde</option>
      <option value="Incumplimiento de tareas" <?=$tipo=='Incumplimiento de tareas'?'selected':''?>>Incumplimiento de tareas</option>
      <option value="Falta de uniforme" <?=$tipo=='Falta de uniforme'?'selected':''?>>Falta de uniforme</option>
      <option value="Conducta inapropiada" <?=$tipo=='Conducta inapropiada'?'selected':''?>>Conducta inapropiada</option>
    </select>
  </form>

  <table class="custom-table">
    <thead><tr><th>Alumno</th><th>Grupo</th><th>Tipo de incidencia</th><th>Descripción</th><th>Fecha</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php if($res&&$res->num_rows>0): while($row=$res->fetch_assoc()): ?>
        <tr>
          <td><?=htmlspecialchars($row['Alumno'])?></td>
          <td><span class="badge"><?=htmlspecialchars($row['Grupo'])?></span></td>
          <td><span class="badge"><?=htmlspecialchars($row['Tipo_Incidencia'])?></span></td>
          <td><?=htmlspecialchars($row['Descripcion'])?></td>
          <td><?=htmlspecialchars($row['Fecha'])?></td>
          <td class="acciones">
            <i class="fa-solid fa-pen-to-square accion accion-editar editarIncidencia" data-id="<?=$row['ID_Incidencia']?>" title="Editar"></i>
            <i class="fa-solid fa-trash accion accion-eliminar eliminarIncidencia" data-id="<?=$row['ID_Incidencia']?>" title="Eliminar"></i>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="empty-text">No hay incidencias registradas</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<div id="modalIncidencia" class="modal">
  <div class="modal-content">
    <h2 id="tituloModalIncidencia">Registrar incidencia</h2>
    <form id="formIncidencia">
      <input type="hidden" name="id_incidencia" id="id_incidencia">
      <label>Grupo</label>
      <select id="id_grupo" name="id_grupo" required class="search-select">
        <option value="">Selecciona grupo</option>
        <?php while($g=$grupos->fetch_assoc()): ?>
          <option value="<?=$g['ID_Grupo']?>"><?=$g['Grado'].'° '.$g['Grupo']?></option>
        <?php endwhile; ?>
      </select>
      <label>Alumno</label>
      <select id="id_alumno" name="id_alumno" required class="search-select"><option value="">Selecciona alumno</option></select>
      <label>Tipo de incidencia</label>
      <select name="tipo_incidencia" required class="search-select">
        <option value="">Selecciona tipo</option>
        <option value="Llegada tarde">Llegada tarde</option>
        <option value="Incumplimiento de tareas">Incumplimiento de tareas</option>
        <option value="Falta de uniforme">Falta de uniforme</option>
        <option value="Conducta inapropiada">Conducta inapropiada</option>
      </select>
      <label>Descripción</label>
      <textarea name="descripcion" required class="search-input" style="height:80px;padding:10px;"></textarea>
      <label>Fecha</label>
      <input type="date" name="fecha" required class="search-input">
      <div class="modal-actions" style="margin-top:15px;display:flex;gap:10px;">
        <button type="submit" id="btnGuardarIncidencia" class="btn-primary">Guardar</button>
        <button type="button" id="cerrarModalIncidencia" class="btn-secondary">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modalEliminarIncidencia" class="modal">
  <div class="modal-content">
    <h2>Eliminar incidencia</h2>
    <p>¿Está seguro de eliminar esta incidencia?</p>
    <input type="hidden" id="idEliminarIncidencia">
    <div class="modal-actions">
      <button type="button" id="confirmarEliminarIncidencia" class="btn-primary">Eliminar</button>
      <button type="button" id="cancelarEliminarIncidencia" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script src="js/incidencias.js"></script>
<script src="js/custom_alerts.js"></script>
