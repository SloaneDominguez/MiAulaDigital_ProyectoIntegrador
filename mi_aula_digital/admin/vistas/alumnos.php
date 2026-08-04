<?php
include_once("../config/conexion.php");

$buscar=$_GET['buscar']??''; $grupo=$_GET['grupo']??'';
$sql="SELECT a.ID_Alumno,CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Nombre,a.CURP,g.Grado,g.Grupo FROM alumno a LEFT JOIN grupo g ON a.ID_Grupo=g.ID_Grupo WHERE 1=1";

if(!empty($buscar)){
  $b=$conexion->real_escape_string($buscar);
  $sql.=" AND (a.Nombre LIKE '%$b%' OR a.Primer_Apellido LIKE '%$b%' OR a.CURP LIKE '%$b%')";
}
if(!empty($grupo)){
  $g=(int)$grupo;
  $sql.=" AND a.ID_Grupo=$g";
}
$sql.=" ORDER BY a.Nombre ASC";
$res=$conexion->query($sql);
$grupos=$conexion->query("SELECT ID_Grupo,Grado,Grupo FROM grupo");
?>

<header class="content-header">
  <div><h1>Alumnos</h1><p class="subtitle">Registro de alumnos</p></div>
  <button id="abrirModalAlumno" class="btn-primary"><i class="fa-solid fa-plus"></i> Nuevo alumno</button>
</header>

<section class="section-box">
  <form method="GET" class="search-form" style="justify-content:flex-start;gap:10px;">
    <input type="hidden" name="view" value="alumnos">
    <input type="text" name="buscar" class="search-input" placeholder="Buscar por nombre o CURP..." value="<?=htmlspecialchars($buscar)?>">
    <select name="grupo" class="search-input">
      <option value="">Todos los grupos</option>
      <?php while($g=$grupos->fetch_assoc()): ?>
        <option value="<?=$g['ID_Grupo']?>" <?=($grupo==$g['ID_Grupo'])?'selected':''?>><?=$g['Grado'].'° '.$g['Grupo']?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn-primary">Filtrar</button>
  </form>
</section>

<section class="section-box">
  <table class="custom-table">
    <thead><tr><th>Nombre</th><th>CURP</th><th>Grupo</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php if($res&&$res->num_rows>0): while($a=$res->fetch_assoc()): ?>
        <tr>
          <td><?=htmlspecialchars($a['Nombre'])?></td>
          <td><?=htmlspecialchars($a['CURP'])?></td>
          <td>
            <?php if(!empty($a['Grado'])): ?>
              <span class="badge"><?=$a['Grado'].'° '.$a['Grupo']?></span>
            <?php else: ?>
              <span class="badge badge-gray">Sin grupo</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if(!empty($a['Grado'])): ?>
              <span class="badge badge-success">Activo</span>
            <?php else: ?>
              <span class="badge badge-warning">Pendiente</span>
            <?php endif; ?>
          </td>
          <td class="acciones">
            <i class="fa-solid fa-pen-to-square accion accion-editar editarAlumno" data-id="<?=$a['ID_Alumno']?>" title="Editar"></i>
            <i class="fa-solid fa-trash accion accion-eliminar eliminarAlumno" data-id="<?=$a['ID_Alumno']?>" title="Eliminar"></i>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="5" class="empty-text">No hay alumnos registrados</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<div id="modalAlumno" class="modal">
  <div class="modal-content">
    <h2 id="tituloModalAlumno">Registrar alumno</h2>
    <form id="formAlumno">
      <input type="hidden" id="id_alumno" name="id_alumno">
      <label>Nombre</label><input type="text" name="nombre" required maxlength="50" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" title="El nombre solo puede contener letras" oninput="soloLetras(this)">
      <label>Primer apellido</label><input type="text" name="primer_apellido" required maxlength="50" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" title="El apellido solo puede contener letras" oninput="soloLetras(this)">
      <label>Segundo apellido</label><input type="text" name="segundo_apellido" maxlength="50" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" title="El apellido solo puede contener letras" oninput="soloLetras(this)">
      <label>CURP</label><input type="text" name="curp" maxlength="18" minlength="18" required pattern="[A-Z0-9]{18}" title="La CURP debe contener 18 caracteres en mayúsculas y números" oninput="validarCURP(this)">
      <label>Grupo</label>
      <select name="id_grupo" required>
        <option value="">Selecciona grupo</option>
        <?php $grupos2=$conexion->query("SELECT ID_Grupo,Grado,Grupo FROM grupo"); while($g2=$grupos2->fetch_assoc()): ?>
          <option value="<?=$g2['ID_Grupo']?>"><?=$g2['Grado'].'° '.$g2['Grupo']?></option>
        <?php endwhile; ?>
      </select>
      <div class="modal-actions">
        <button type="submit" class="btn-primary" id="btnGuardarAlumno">Guardar</button>
        <button type="button" id="cerrarModalAlumno" class="btn-secondary">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modalEliminarAlumno" class="modal">
  <div class="modal-content">
    <h2>Eliminar alumno</h2>
    <p>¿Estás seguro de eliminar este alumno?</p>
    <form id="formEliminarAlumno">
      <input type="hidden" id="idEliminarAlumno" name="id_alumno">
      <div class="modal-actions">
        <button type="submit" class="btn-primary">Eliminar</button>
        <button type="button" id="cerrarEliminarAlumno" class="btn-secondary">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script src="js/crear_alumno.js"></script>
