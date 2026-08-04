<?php
include_once("../config/conexion.php");
$bimestre=$_GET['bimestre']??1; $grupo=$_GET['grupo']??'';
$sql="SELECT a.ID_Alumno,CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Alumno,
CONCAT(g.Grado,'° ',g.Grupo) AS Grupo,
MAX(CASE WHEN c.ID_Campo=1 THEN c.ID_Calificacion END) AS ID_Lenguajes,
MAX(CASE WHEN c.ID_Campo=2 THEN c.ID_Calificacion END) AS ID_Saberes,
MAX(CASE WHEN c.ID_Campo=3 THEN c.ID_Calificacion END) AS ID_Etica,
MAX(CASE WHEN c.ID_Campo=4 THEN c.ID_Calificacion END) AS ID_Humano,
MAX(CASE WHEN c.ID_Campo=1 THEN c.Calificacion_Numerica END) AS Lenguajes,
MAX(CASE WHEN c.ID_Campo=2 THEN c.Calificacion_Numerica END) AS Saberes,
MAX(CASE WHEN c.ID_Campo=3 THEN c.Calificacion_Numerica END) AS Etica,
MAX(CASE WHEN c.ID_Campo=4 THEN c.Calificacion_Numerica END) AS Humano
FROM alumno a
LEFT JOIN grupo g ON a.ID_Grupo=g.ID_Grupo
LEFT JOIN calificacion c ON a.ID_Alumno=c.ID_Alumno AND c.Bimestre='$bimestre'
WHERE 1=1";
if(!empty($grupo)){ $grupo=(int)$grupo; $sql.=" AND a.ID_Grupo=$grupo"; }
$sql.=" GROUP BY a.ID_Alumno ORDER BY a.Nombre";
$res=$conexion->query($sql);
$grupos=$conexion->query("SELECT ID_Grupo,Grado,Grupo FROM grupo");
$alumnos=$conexion->query("SELECT ID_Alumno,CONCAT(Nombre,' ',Primer_Apellido,' ',IFNULL(Segundo_Apellido,'')) AS Nombre FROM alumno ORDER BY Nombre");
$campos=$conexion->query("SELECT ID_Campo,Nombre_Campo FROM campo_formativo ORDER BY ID_Campo");
?>

<header class="content-header">
  <div><h1>Calificaciones</h1><p class="subtitle">Registro de calificaciones</p></div>
  <button id="abrirModalCalificacion" class="btn-primary"><i class="fa-solid fa-plus"></i> Nueva calificación</button>
</header>

<section class="section-box">
  <form method="GET" class="search-form">
    <input type="hidden" name="view" value="calificaciones">
    <select name="grupo" class="search-input">
      <option value="">Todos los grupos</option>
      <?php while($g=$grupos->fetch_assoc()){ ?>
        <option value="<?=$g['ID_Grupo']?>" <?=$grupo==$g['ID_Grupo']?'selected':''?>><?=$g['Grado']."° ".$g['Grupo']?></option>
      <?php } ?>
    </select>
    <select name="bimestre" class="search-input">
      <?php for($i=1;$i<=5;$i++){ ?>
        <option value="<?=$i?>" <?=$bimestre==$i?'selected':''?>>Bimestre <?=$i?></option>
      <?php } ?>
    </select>
    <button class="btn-primary">Filtrar</button>
  </form>
</section>

<section class="section-box">
  <table class="custom-table">
    <thead><tr><th>Alumno</th><th>Grupo</th><th>Lenguajes</th><th>Saberes</th><th>Ética</th><th>De lo Humano</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php while($a=$res->fetch_assoc()){ ?>
        <tr>
          <td><?=$a['Alumno']?></td>
          <td><span class="badge"><?=$a['Grupo']?></span></td>
          <td><?=$a['Lenguajes']!==null?$a['Lenguajes']:'-'?></td>
          <td><?=$a['Saberes']!==null?$a['Saberes']:'-'?></td>
          <td><?=$a['Etica']!==null?$a['Etica']:'-'?></td>
          <td><?=$a['Humano']!==null?$a['Humano']:'-'?></td>
          <td class="acciones">
            <i class="fa-solid fa-pen-to-square accion accion-editar editarCalificacion" data-alumno="<?=$a['ID_Alumno']?>" title="Editar"></i>
            <i class="fa-solid fa-trash accion accion-eliminar eliminarCalificacion" data-alumno="<?=$a['ID_Alumno']?>" title="Eliminar"></i>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</section>

<div id="modalCalificacion" class="modal">
  <div class="modal-content">
    <h2 id="tituloModalCalificacion">Registrar calificación</h2>
    <form id="formCalificacion">
      <input type="hidden" name="accion" id="accion" value="guardar">
      <input type="hidden" name="id_calificacion" id="id_calificacion">
      <label>Alumno</label>
      <select name="id_alumno" id="id_alumno" required>
        <option value="">Seleccionar alumno</option>
        <?php while($a=$alumnos->fetch_assoc()){ ?><option value="<?=$a['ID_Alumno']?>"><?=$a['Nombre']?></option><?php } ?>
      </select>
      <label>Campo formativo</label>
      <select name="id_campo" id="id_campo" required>
        <option value="">Seleccionar campo</option>
        <?php while($c=$campos->fetch_assoc()){ ?><option value="<?=$c['ID_Campo']?>"><?=$c['Nombre_Campo']?></option><?php } ?>
      </select>
      <label>Bimestre</label>
      <select name="bimestre" id="bimestre_modal">
        <option value="1">Bimestre 1</option><option value="2">Bimestre 2</option><option value="3">Bimestre 3</option><option value="4">Bimestre 4</option><option value="5">Bimestre 5</option>
      </select>
      <label>Calificación</label>
      <input type="number" name="calificacion" id="calificacion" min="0" max="10" step="0.1" required>
      <div class="modal-actions">
        <button type="submit" id="btnGuardarCalificacion" class="btn-primary">Guardar</button>
        <button type="button" id="btnEliminarCalificacion" class="btn-danger" style="display:none;">Eliminar</button>
        <button type="button" id="cerrarModalCalificacion" class="btn-secondary">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<div id="modalEliminarCalificacion" class="modal">
  <div class="modal-content">
    <h2>Eliminar calificación</h2>
    <p>¿Estás seguro de que deseas eliminar esta calificación?</p>
    <div class="modal-actions">
      <button type="button" id="confirmarEliminarCalificacion" class="btn-danger">Eliminar</button>
      <button type="button" id="cancelarEliminarCalificacion" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script src="js/calificaciones.js"></script>
