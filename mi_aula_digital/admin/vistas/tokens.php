<?php
include_once("../config/conexion.php");
$buscar=$_GET['buscar']??''; $grupo=$_GET['grupo']??'';
$sql="SELECT a.ID_Alumno,t.ID_Token,CONCAT(a.Nombre,' ',a.Primer_Apellido,' ',IFNULL(a.Segundo_Apellido,'')) AS Alumno,a.CURP,t.Token_String,t.Fecha_Inicio,t.Fecha_Expiracion,g.Grado,g.Grupo
FROM alumno a
LEFT JOIN tokens t ON a.ID_Alumno=t.ID_Alumno
LEFT JOIN grupo g ON a.ID_Grupo=g.ID_Grupo
WHERE 1=1";
if(!empty($buscar)){ $b=$conexion->real_escape_string($buscar); $sql.=" AND (a.Nombre LIKE '%$b%' OR a.Primer_Apellido LIKE '%$b%' OR a.CURP LIKE '%$b%' OR t.Token_String LIKE '%$b%')"; }
if(!empty($grupo)){ $g=(int)$grupo; $sql.=" AND a.ID_Grupo=$g"; }
$sql.=" ORDER BY a.ID_Alumno DESC";
$res=$conexion->query($sql);
$grupos=$conexion->query("SELECT ID_Grupo,Grado,Grupo FROM grupo");
?>
<header class="content-header">
  <div>
    <h1>Tokens por grupo</h1>
    <p class="subtitle">Generación de tokens por grupo</p>
  </div>
  <div class="header-actions">
    <button type="button" class="btn-secondary" id="btnCSV"><i class="fa-solid fa-file-csv"></i> CSV</button>
    <button type="button" class="btn-danger" id="btnExcel"><i class="fa-solid fa-file-excel"></i> Excel</button>
    <button type="button" class="btn-danger" id="btnPDF"><i class="fa-solid fa-file-pdf"></i> PDF</button>
    <button type="button" class="btn-primary" onclick="abrirModal()"><i class="fa-solid fa-plus"></i> Generar token</button>
  </div>
</header>
<section class="section-box">
  <form method="GET" class="search-form">
    <input type="hidden" name="view" value="tokens">
    <input type="text" name="buscar" class="search-input" placeholder="Buscar alumno o token..." value="<?= htmlspecialchars($buscar) ?>">
    <select name="grupo" class="search-input">
      <option value="">Todos los grupos</option>
      <?php while($g=$grupos->fetch_assoc()): ?>
        <option value="<?= $g['ID_Grupo']; ?>" <?= $grupo==$g['ID_Grupo']?'selected':''; ?>><?= $g['Grado']; ?>° <?= $g['Grupo']; ?></option>
      <?php endwhile; ?>
    </select>
    <button class="btn-primary">Filtrar</button>
  </form>
</section>
<section class="section-box">
  <table class="custom-table">
    <thead>
      <tr><th>Alumno</th><th>CURP</th><th>Token</th><th>Grupo</th><th>Fecha Inicio</th><th>Expira</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      <?php if($res&&$res->num_rows>0): while($t=$res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($t['Alumno']); ?></td>
          <td><?= htmlspecialchars($t['CURP']); ?></td>
          <td><?= $t['Token_String']?htmlspecialchars($t['Token_String']):'Sin token'; ?></td>
          <td><?= $t['Grado']; ?>° <?= $t['Grupo']; ?></td>
          <td><?= $t['Fecha_Inicio']??'-'; ?></td>
          <td><?= $t['Fecha_Expiracion']??'-'; ?></td>
          <td class="acciones">
            <?php if(!empty($t['ID_Token'])): ?>
              <i class="fa-solid fa-pen-to-square accion accion-editar editarToken" data-id="<?= $t['ID_Token']; ?>" title="Editar"></i>
              <i class="fa-solid fa-trash accion accion-eliminar eliminarToken" data-id="<?= $t['ID_Token']; ?>" title="Eliminar"></i>
            <?php else: ?><span style="color:#999;">Sin token</span><?php endif; ?>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="7" style="text-align:center;">No hay registros</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<!-- MODAL GENERAR TOKENS -->
<div id="modalToken" class="modal">
  <div class="modal-content">
    <h2>Generar tokens por grupo</h2>
    <form id="formToken">
      <label>Grupo</label>
      <select id="grupoSeleccionado" required><option value="">Selecciona grupo</option></select>
      <label>Token base</label>
      <div style="display:flex;gap:10px;">
        <input type="text" id="tokenBase" readonly>
        <button type="button" class="btn-primary" onclick="generarToken()">Generar</button>
      </div>
      <label>Expiración del token</label>
      <select id="expiracionToken">
        <option value="1 year">1 Año</option>
        <option value="6 months">6 Meses</option>
        <option value="3 months">3 Meses</option>
        <option value="30 days">30 Días</option>
      </select>
      <div class="modal-actions">
        <button type="button" class="btn-primary" onclick="guardarToken()">Guardar grupo</button>
        <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDITAR TOKEN -->
<div id="modalEditarToken" class="modal">
  <div class="modal-content">
    <h2>Editar token</h2>
    <form id="formEditarToken">
      <input type="hidden" id="editarIdToken">
      <label>Alumno</label>
      <input type="text" id="editarAlumno" readonly>
      <label>Token</label>
      <div style="display:flex;gap:10px;">
        <input type="text" id="editarToken" readonly>
        <button type="button" class="btn-primary" id="btnGenerarNuevoToken">Generar</button>
      </div>
      <label>Expiración</label>
      <select id="editarExpiracion">
        <option value="1 year">1 Año</option>
        <option value="6 months">6 Meses</option>
        <option value="3 months">3 Meses</option>
        <option value="30 days">30 Días</option>
      </select>
      <div class="modal-actions">
        <button type="submit" class="btn-primary">Guardar cambios</button>
        <button type="button" class="btn-secondary" id="cerrarEditarToken">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL ELIMINAR TOKEN -->
<div id="modalEliminarToken" class="modal">
  <div class="modal-content modal-small">
    <h2>Eliminar token</h2>
    <p style="margin:20px 0;text-align:left;">¿Deseas eliminar este token?<br>Esta acción no se puede deshacer.</p>
    <input type="hidden" id="eliminarIdToken">
    <div class="modal-actions">
      <button type="button" class="btn-danger" id="confirmarEliminarToken">Eliminar</button>
      <button type="button" class="btn-secondary" id="cancelarEliminarToken">Cancelar</button>
    </div>
  </div>
</div>

<script src="js/tokens.js"></script>
