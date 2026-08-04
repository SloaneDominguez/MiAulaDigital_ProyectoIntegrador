<?php
include_once("../config/conexion.php");
$pendientes=mysqli_query($conexion,"SELECT * FROM solicitud_acceso WHERE Estado='Pendiente' ORDER BY Fecha_Solicitud DESC");
$historial=mysqli_query($conexion,"SELECT * FROM solicitud_acceso WHERE Estado<>'Pendiente' ORDER BY Fecha_Revision DESC");
$totalPendientes=mysqli_fetch_assoc(mysqli_query($conexion,"SELECT COUNT(*) Total FROM solicitud_acceso WHERE Estado='Pendiente'"));
?>
<div class="content-header">
  <div>
    <h1>Solicitudes de acceso</h1>
    <p><?= $totalPendientes['Total'] ?> solicitud(es) pendiente(s)</p>
  </div>
</div>
<div class="section-box">
  <h2>Solicitudes pendientes</h2>
  <table class="custom-table">
    <thead>
      <tr><th>Nombre</th><th>Correo</th><th>Usuario</th><th>Fecha</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      <?php if(mysqli_num_rows($pendientes)>0){ while($solicitud=mysqli_fetch_assoc($pendientes)){ ?>
        <tr>
          <td><?= $solicitud['Nombre']." ".$solicitud['Primer_Apellido']." ".$solicitud['Segundo_Apellido'] ?></td>
          <td><?= $solicitud['Correo'] ?></td>
          <td><?= $solicitud['Usuario'] ?></td>
          <td><?= date("d/m/Y",strtotime($solicitud['Fecha_Solicitud'])) ?></td>
          <td>
            <button class="btn-primary btnAprobar" data-id="<?= $solicitud['ID_Solicitud'] ?>">Aprobar</button>
            <button class="btn-danger btnRechazar" data-id="<?= $solicitud['ID_Solicitud'] ?>">Rechazar</button>
          </td>
        </tr>
      <?php }}else{ ?>
        <tr><td colspan="5" class="empty-text">Sin solicitudes pendientes</td></tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<div class="section-box">
  <h2>Historial</h2>
  <table class="custom-table">
    <thead>
      <tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Fecha revisión</th></tr>
    </thead>
    <tbody>
      <?php if(mysqli_num_rows($historial)>0){ while($solicitud=mysqli_fetch_assoc($historial)){ ?>
        <tr>
          <td><?= $solicitud['Nombre']." ".$solicitud['Primer_Apellido']." ".$solicitud['Segundo_Apellido'] ?></td>
          <td><?= $solicitud['Correo'] ?></td>
          <td><?php if($solicitud['Estado']=="Aprobada"){ ?><span class="badge badge-success">Aprobada</span><?php }else{ ?><span class="badge badge-danger">Rechazada</span><?php } ?></td>
          <td><?= date("d/m/Y",strtotime($solicitud['Fecha_Revision'])) ?></td>
        </tr>
      <?php }}else{ ?>
        <tr><td colspan="4" class="empty-text">Sin historial</td></tr>
      <?php } ?>
    </tbody>
  </table>
</div>
<div id="modalAprobar" class="modal">
  <div class="modal-content">
    <h2>Aprobar solicitud</h2>
    <p>¿Deseas aprobar esta solicitud de acceso?<br>El usuario podrá ingresar al sistema.</p>
    <div class="modal-actions">
      <button type="button" id="cancelarAprobar" class="btn-secondary">Cancelar</button>
      <button type="button" id="confirmarAprobar" class="btn-primary">Aprobar</button>
    </div>
  </div>
</div>
<div id="modalRechazar" class="modal">
  <div class="modal-content">
    <h2>Rechazar solicitud</h2>
    <p>¿Deseas rechazar esta solicitud de acceso?<br>Esta acción no podrá deshacerse.</p>
    <div class="modal-actions">
      <button type="button" id="cancelarRechazar" class="btn-secondary">Cancelar</button>
      <button type="button" id="confirmarRechazar" class="btn-danger">Rechazar</button>
    </div>
  </div>
</div>
<input type="hidden" id="idSolicitudAprobar">
<input type="hidden" id="idSolicitudRechazar">
<script src="js/solicitud.js"></script>
