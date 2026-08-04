<?php
// ASIGNACIÓN DE GRUPOS
$total_grupos=0;
$buscar=trim($_GET['buscar']??'');
$res_grupos=false;
if(isset($conexion)){
  $sqlTotal="SELECT COUNT(*) AS total FROM grupo";
  $resTotal=$conexion->query($sqlTotal);
  if($resTotal&&$fila=$resTotal->fetch_assoc()){$total_grupos=$fila['total'];}
  $sql="SELECT g.ID_Grupo,g.Grado,g.Grupo,d.ID_Docente,
        CONCAT(d.Nombre,' ',d.Primer_Apellido,' ',IFNULL(d.Segundo_Apellido,'')) AS Docente
        FROM grupo g
        LEFT JOIN docente d ON d.ID_Grupo_Asignado=g.ID_Grupo
        WHERE (CONCAT(g.Grado,' ',g.Grupo) LIKE ? OR CONCAT(d.Nombre,' ',d.Primer_Apellido,' ',IFNULL(d.Segundo_Apellido,'')) LIKE ?)
        ORDER BY CAST(g.Grado AS UNSIGNED),g.Grupo ASC";
  $stmt=$conexion->prepare($sql);
  $param="%{$buscar}%";
  $stmt->bind_param("ss",$param,$param);
  $stmt->execute();
  $res_grupos=$stmt->get_result();
}
?>
<header class="content-header">
  <div><h1>Asignación de grupos</h1><p class="subtitle"><?= $total_grupos ?> grupos registrados</p></div>
  <button class="btn-primary" id="btnNuevoGrupo"><i class="fa-solid fa-plus"></i>Crear grupo</button>
</header>
<section class="section-box">
  <div class="toolbar">
    <form method="GET" class="search-form">
      <input type="hidden" name="view" value="asignaciones">
      <input type="text" name="buscar" class="search-input" placeholder="Buscar grupo o docente..." value="<?= htmlspecialchars($buscar) ?>">
      <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i>Buscar</button>
    </form>
  </div>
</section>
<section class="section-box">
  <table class="custom-table">
    <thead><tr><th width="18%">Grupo</th><th width="18%">Estado</th><th>Docente asignado</th><th width="15%">Acciones</th></tr></thead>
    <tbody>
      <?php if($res_grupos&&$res_grupos->num_rows>0): ?>
        <?php while($grupo=$res_grupos->fetch_assoc()): ?>
          <tr>
            <td><span class="badge"><?= $grupo['Grado'] ?>° <?= htmlspecialchars($grupo['Grupo']) ?></span></td>
            <td><?php if(!empty($grupo['ID_Docente'])): ?><span class="badge badge-success">Asignado</span><?php else: ?><span class="badge badge-warning">Sin asignar</span><?php endif; ?></td>
            <td><?php if(!empty($grupo['ID_Docente'])): ?><?= htmlspecialchars($grupo['Docente']) ?><?php else: ?><span class="empty-text">Sin docente asignado</span><?php endif; ?></td>
            <td class="acciones">
              <button type="button" class="accion accion-editar btnEditarGrupo" title="Editar grupo" data-id="<?= $grupo['ID_Grupo'] ?>" data-grado="<?= $grupo['Grado'] ?>" data-grupo="<?= htmlspecialchars($grupo['Grupo']) ?>" data-docente="<?= $grupo['ID_Docente']??'' ?>"><i class="fa-solid fa-pen-to-square"></i></button>
              <button type="button" class="accion accion-eliminar btnEliminarGrupo" title="Eliminar grupo" data-id="<?= $grupo['ID_Grupo'] ?>" data-nombre="<?= $grupo['Grado'].'° '.htmlspecialchars($grupo['Grupo']) ?>"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4" class="empty-text">No se encontraron grupos.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<!-- MODAL CREAR GRUPO -->
<div id="modalCrearGrupo" class="modal">
  <div class="modal-content">
    <h2>Crear grupo</h2>
    <form id="formCrearGrupo">
      <input type="hidden" name="accion" value="crear">
      <label>Grado</label>
      <select name="grado" id="crearGrado" required>
        <option value="">Seleccione...</option><option value="1">1°</option><option value="2">2°</option><option value="3">3°</option><option value="4">4°</option><option value="5">5°</option><option value="6">6°</option>
      </select>
      <label>Grupo</label>
      <input type="text" name="grupo" id="crearGrupo" maxlength="2" oninput="this.value=this.value.toUpperCase()" required>
      <div class="modal-actions">
        <button type="submit" class="btn-primary">Guardar</button>
        <button type="button" class="btn-secondary" id="cerrarCrearGrupo">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDITAR GRUPO -->
<div id="modalEditarGrupo" class="modal">
  <div class="modal-content">
    <h2>Editar grupo</h2>
    <form id="formEditarGrupo">
      <input type="hidden" name="accion" value="editar">
      <input type="hidden" name="id_grupo" id="editarIdGrupo">
      <label>Grado</label>
      <select name="grado" id="editarGrado" required>
        <option value="1">1°</option><option value="2">2°</option><option value="3">3°</option><option value="4">4°</option><option value="5">5°</option><option value="6">6°</option>
      </select>
      <label>Grupo</label>
      <input type="text" name="grupo" id="editarGrupo" maxlength="2" oninput="this.value=this.value.toUpperCase()" required>
      <label>Docente asignado</label>
      <select name="id_docente" id="editarDocente">
        <option value="">Sin asignar</option>
        <?php
        $docentes=$conexion->query("SELECT ID_Docente,Nombre,Primer_Apellido,Segundo_Apellido FROM docente ORDER BY Nombre,Primer_Apellido");
        while($docente=$docentes->fetch_assoc()): ?>
          <option value="<?= $docente['ID_Docente'] ?>"><?= htmlspecialchars($docente['Nombre'].' '.$docente['Primer_Apellido'].' '.$docente['Segundo_Apellido']) ?></option>
        <?php endwhile; ?>
      </select>
      <div class="modal-actions">
        <button type="submit" class="btn-primary">Guardar cambios</button>
        <button type="button" class="btn-secondary" id="cerrarEditarGrupo">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<div id="modalEliminarGrupo" class="modal">
  <div class="modal-content">
    <h2>Eliminar grupo</h2>
    <form id="formEliminarGrupo">
      <input type="hidden" name="accion" value="eliminar">
      <input type="hidden" name="id_grupo" id="eliminarIdGrupo">
      <p>¿Desea eliminar el grupo <strong id="nombreGrupoEliminar"></strong>?</p>
      <p class="empty-text">El docente asignado quedará sin grupo automáticamente.</p>
      <div class="modal-actions">
        <button type="submit" class="btn-danger">Eliminar</button>
        <button type="button" class="btn-secondary" id="cerrarEliminarGrupo">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script src="js/asignaciones.js"></script>

