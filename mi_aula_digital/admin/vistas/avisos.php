<?php
include_once("../config/conexion.php");

/* CONSULTA AVISOS */
$sqlAvisos="SELECT p.ID_Publicacion,p.Titulo,p.Contenido,p.Fecha_Publicacion,p.ID_Grupo,g.Grado,g.Grupo,a.Tipo_Aviso
FROM aviso a INNER JOIN publicacion p ON a.ID_Publicacion=p.ID_Publicacion
LEFT JOIN grupo g ON p.ID_Grupo=g.ID_Grupo ORDER BY p.Fecha_Publicacion DESC";
$resAvisos=$conexion->query($sqlAvisos);

/* CONSULTA TAREAS */
$sqlTareas="SELECT p.ID_Publicacion,p.Titulo,p.Contenido,p.Fecha_Publicacion,g.Grado,g.Grupo
FROM publicacion p LEFT JOIN grupo g ON p.ID_Grupo=g.ID_Grupo
LEFT JOIN aviso a ON a.ID_Publicacion=p.ID_Publicacion
WHERE p.ID_Docente IS NOT NULL AND a.ID_Publicacion IS NULL
ORDER BY p.Fecha_Publicacion DESC";
$resTareas=$conexion->query($sqlTareas);

/* GRUPOS MODAL */
$grupos=$conexion->query("SELECT ID_Grupo,Grado,Grupo FROM grupo ORDER BY Grado,Grupo");
?>

<header class="content-header">
  <div>
    <h1>Avisos Institucionales</h1>
    <p class="subtitle">Comunicados y tareas escolares</p>
  </div>
  <button id="abrirModalAviso" class="btn-primary"><i class="fa-solid fa-plus"></i> Nuevo aviso</button>
</header>

<div class="section-box" style="padding:15px;margin-bottom:20px;">
  <div style="display:flex;gap:10px;">
    <button id="btnTabAvisos" type="button" class="btn-primary"><i class="fa-solid fa-bullhorn"></i> Anuncios</button>
    <button id="btnTabTareas" type="button" class="btn-primary"><i class="fa-solid fa-book"></i> Tareas</button>
  </div>
</div>

<div id="vistaAvisos">
  <section class="section-box">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;">
      <?php if($resAvisos&&$resAvisos->num_rows>0): while($a=$resAvisos->fetch_assoc()): ?>
        <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
          <span class="badge" style="background:#fee2e2;color:#dc2626;"><?=htmlspecialchars($a['Tipo_Aviso'])?></span>
          <h3 style="margin-top:12px;"><?=htmlspecialchars($a['Titulo'])?></h3>
          <p style="margin-top:10px;color:#4b5563;line-height:1.5;"><?=nl2br(htmlspecialchars($a['Contenido']))?></p>
          <div style="margin-top:18px;color:#9ca3af;font-size:13px;">
            <i class="fa-regular fa-calendar"></i> <?=$a['Fecha_Publicacion']?><br>
            <i class="fa-solid fa-users"></i>
            <?php if(!empty($a['Grado'])): ?><?=$a['Grado']."° ".$a['Grupo']?><?php else: ?>Todos los grupos<?php endif; ?>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:12px;margin-bottom:10px;">
            <i class="fa-solid fa-pen-to-square accion accion-editar editarAviso" data-id="<?=$a['ID_Publicacion']?>" data-titulo="<?=htmlspecialchars($a['Titulo'],ENT_QUOTES)?>" data-contenido="<?=htmlspecialchars($a['Contenido'],ENT_QUOTES)?>" data-tipo="<?=$a['Tipo_Aviso']?>" data-grupo="<?=$a['ID_Grupo']?>" title="Editar"></i>
            <i class="fa-solid fa-trash accion accion-eliminar eliminarAviso" data-id="<?=$a['ID_Publicacion']?>" title="Eliminar"></i>
          </div>
        </div>
      <?php endwhile; else: ?><p class="empty-text">No existen avisos registrados.</p><?php endif; ?>
    </div>
  </section>
</div>

<div id="vistaTareas" style="display:none;">
  <section class="section-box">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;">
      <?php if($resTareas&&$resTareas->num_rows>0): while($t=$resTareas->fetch_assoc()): ?>
        <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;">
          <span class="badge" style="background:#DBEAFE;color:#1D4ED8;">Tarea</span>
          <h3 style="margin-top:12px;"><?=htmlspecialchars($t['Titulo'])?></h3>
          <p style="margin-top:10px;color:#4b5563;line-height:1.5;"><?=nl2br(htmlspecialchars($t['Contenido']))?></p>
          <div style="margin-top:18px;color:#9ca3af;font-size:13px;">
            <i class="fa-regular fa-calendar"></i> <?=$t['Fecha_Publicacion']?><br>
            <i class="fa-solid fa-users"></i>
            <?php if(!empty($t['Grado'])):?><br><?=$t['Grado']."° ".$t['Grupo']?><?php else: ?>Todos los grupos<?php endif; ?>
          </div>
        </div>
      <?php endwhile; else: ?><p class="empty-text">No existen tareas registradas.</p><?php endif; ?>
    </div>
  </section>
</div>

<div id="modalAviso" class="modal">
  <div class="modal-content">
    <h2 id="tituloModalAviso">Nuevo aviso institucional</h2>
    <form id="formAviso">
      <input type="hidden" name="id_publicacion" id="id_publicacion">
      <label>Título</label><input type="text" name="titulo" maxlength="150" required>
      <label>Contenido</label><textarea name="contenido" rows="6" required></textarea>
      <label>Tipo de aviso</label>
      <select name="tipo_aviso" required>
        <option value="">Seleccione...</option>
        <option value="Institucional">Institucional</option>
        <option value="Urgente">Urgente</option>
      </select>
      <label>Grupo</label>
      <select name="id_grupo">
        <option value="">Todos los grupos</option>
        <?php while($g=$grupos->fetch_assoc()): ?>
          <option value="<?=$g['ID_Grupo']?>"><?=$g['Grado']."° ".$g['Grupo']?></option>
        <?php endwhile; ?>
      </select>
      <div class="modal-actions">
        <button type="submit" id="btnGuardarAviso" class="btn-primary">Publicar aviso</button>
        <button type="button" id="cerrarModalAviso" class="btn-secondary">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modalEliminarAviso" class="modal">
  <div class="modal-content" style="max-width:400px;">
    <h2>Eliminar aviso</h2>
    <p style="margin:20px 0;color:#4b5563;">¿Está seguro que desea eliminar este aviso?<br>Esta acción no se puede deshacer.</p>
    <div class="modal-actions">
      <button type="button" id="confirmarEliminarAviso" class="btn-primary">Eliminar</button>
      <button type="button" id="cancelarEliminarAviso" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script src="js/crear_aviso.js"></script>
<script src="js/tabs.js"></script>
