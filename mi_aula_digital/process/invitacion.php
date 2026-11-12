<?php
require __DIR__.'/../app/bootstrap.php';
try{
 validateCsrf();throttle('invitacion','',10);
 $code=textInput('codigo',64);$pass=passwordInput('password',8);if($pass!==passwordInput('confirmar',1))throw new DomainException('Las contraseñas no coinciden.');
 transaction(function()use($code,$pass){
  $inv=findInvitation($code,true);if(!$inv||!$inv['activo'])throw new DomainException('La invitación venció o ya se usó. Pide a Dirección una nueva.');
  requireStrongPassword($pass,[$inv['Nombre'],$inv['Usuario'],$inv['Correo']]);
  query('UPDATE usuario SET Contrasena=?,cambiar_clave=0,sesion_version=sesion_version+1 WHERE ID_Usuario=?',[password_hash($pass,PASSWORD_DEFAULT),(int)$inv['ID_Usuario']]);
  query('UPDATE invitacion SET usado=NOW() WHERE id=?',[(int)$inv['id']]);
  query('UPDATE invitacion SET vence=NOW() WHERE ID_Usuario=? AND usado IS NULL',[(int)$inv['ID_Usuario']]);
  audit(['ID_Usuario'=>$inv['ID_Usuario']],'ACEPTAR_INVITACION','usuario',(int)$inv['ID_Usuario']);
 });
 reply(['ok'=>true,'message'=>'Contraseña creada. Ya puedes iniciar sesión.','redirect'=>url('index.php?recuperada=1')]);
}catch(Throwable $ex){errorReply($ex);}
