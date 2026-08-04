<?php
// ======================================================
// DOCENTES
// ======================================================
$total_docentes=0; $buscar=""; $res_docentes=false;
if(isset($conexion)){
    $buscar=trim($_GET['buscar']??'');
    $sqlTotal="SELECT COUNT(*) AS total FROM docente";
    $resTotal=$conexion->query($sqlTotal);
    if($resTotal&&$fila=$resTotal->fetch_assoc()) $total_docentes=$fila['total'];
    $sql="SELECT d.ID_Docente,CONCAT(d.Nombre,' ',d.Primer_Apellido,' ',IFNULL(d.Segundo_Apellido,'')) AS NombreCompleto,u.Usuario,u.Correo,g.Grado,g.Grupo
          FROM docente d INNER JOIN usuario u ON d.ID_Usuario=u.ID_Usuario
          LEFT JOIN grupo g ON d.ID_Grupo_Asignado=g.ID_Grupo
          WHERE CONCAT(d.Nombre,' ',d.Primer_Apellido,' ',IFNULL(d.Segundo_Apellido,'')) LIKE ? OR u.Usuario LIKE ? OR u.Correo LIKE ?
          ORDER BY d.Nombre ASC";
    $stmt=$conexion->prepare($sql);
    $param="%{$buscar}%";
    $stmt->bind_param("sss",$param,$param,$param);
    $stmt->execute();
    $res_docentes=$stmt->get_result();
}
?>

<header class="content-header">
  <div><h1>Docentes</h1><p class="subtitle"><?=$total_docentes?> docentes registrados</p></div>
  <button id="abrirModalDocente" class="btn-primary"><i class="fa-solid fa-plus"></i> Registrar docente</button>
</header>

<section class="section-box">
  <form method="GET" class="search-form">
    <input type="hidden" name="view" value="docentes">
    <input type="text" name="buscar" class="search-input" placeholder="Buscar docente..." value="<?=htmlspecialchars($buscar)?>">
    <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
  </form>
</section>

<section class="section-box">
  <table class="custom-table">
    <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Grupo</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php if($res_docentes&&$res_docentes->num_rows>0): while($docente=$res_docentes->fetch_assoc()): ?>
        <tr>
          <td><?=htmlspecialchars($docente['NombreCompleto'])?></td>
          <td><?=htmlspecialchars($docente['Usuario'])?></td>
          <td><?=htmlspecialchars($docente['Correo'])?></td>
          <td>
            <?php if($docente['Grado']): ?>
              <span class="badge"><?=$docente['Grado']?>° <?=$docente['Grupo']?></span>
            <?php else: ?>
              <span class="badge badge-gray">Sin grupo</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-success">Activo</span></td>
          <td class="acciones">
            <i class="fa-solid fa-pen-to-square accion accion-editar editarDocente" data-id="<?=$docente['ID_Docente']?>" title="Editar"></i>
            <i class="fa-solid fa-trash accion accion-eliminar eliminarDocente" data-id="<?=$docente['ID_Docente']?>" title="Eliminar"></i>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="empty-text">No existen docentes registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<div id="modalDocente" class="modal">
  <div class="modal-content">
    <h2 id="tituloModal">Registrar docente</h2>
    <form id="formDocente">
      <input type="hidden" id="accion" name="accion" value="crear">
      <input type="hidden" id="id_docente" name="id_docente">
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" maxlength="50" required autocomplete="off" pattern="[A-Za-z0-9_]+" title="Solo letras, números y guion bajo" oninput="validarUsuario(this)">
      <label for="nombre">Nombre</label>
      <input type="text" id="nombre" name="nombre" maxlength="50" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" title="El nombre solo acepta letras" oninput="soloLetras(this)">
      <label for="primer_apellido">Primer apellido</label>
      <input type="text" id="primer_apellido" name="primer_apellido" maxlength="50" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" title="El apellido solo acepta letras" oninput="soloLetras(this)">
      <label for="segundo_apellido">Segundo apellido</label>
      <input type="text" id="segundo_apellido" name="segundo_apellido" maxlength="50" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" title="El apellido solo acepta letras" oninput="soloLetras(this)">
      <label for="correo">Correo electrónico</label>
      <input type="email" id="correo" name="correo" maxlength="100" required placeholder="ejemplo@correo.com">
      <label for="contrasena">Contraseña</label>
      <input type="password" id="contrasena" name="contrasena" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{8,}" title="Debe contener mínimo 8 caracteres, una mayúscula, una minúscula y un número" placeholder="Dejar vacío para conservar contraseña">
      <label for="id_grupo">Grupo asignado</label>
      <select id="id_grupo" name="id_grupo" required>
        <option value="">Selecciona un grupo</option>
        <?php $grupos=$conexion->query("SELECT ID_Grupo,Grado,Grupo FROM grupo ORDER BY Grado,Grupo"); while($grupo=$grupos->fetch_assoc()): ?>
          <option value="<?=$grupo['ID_Grupo']?>"><?=$grupo['Grado']?>° <?=htmlspecialchars($grupo['Grupo'])?></option>
        <?php endwhile; ?>
      </select>
      <div class="modal-actions">
        <button type="submit" class="btn-primary" id="btnGuardarDocente">Guardar docente</button>
        <button type="button" id="cerrarModalDocente" class="btn-secondary">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modalEliminarDocente" class="modal">
  <div class="modal-content">
    <h2>Eliminar docente</h2>
    <p>¿Está seguro de eliminar este docente?</p>
    <input type="hidden" id="idEliminarDocente">
    <div class="modal-actions">
      <button type="button" id="confirmarEliminarDocente" class="btn-primary">Eliminar</button>
      <button type="button" id="cancelarEliminarDocente" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script src="js/crear_docente.js"></script>
