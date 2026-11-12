<?php
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/mailer.php';

/*
 * Recuperación de contraseña.
 * - Con correo configurado: se envía al correo de la cuenta un enlace privado
 *   (30 minutos, un solo uso). Máximo 3 por cuenta al día y uno cada 2 minutos.
 * - Sin correo configurado: se crea una solicitud y Dirección emite el código
 *   después de verificar la identidad (flujo anterior).
 * La respuesta es la misma exista o no la cuenta, para no revelar quién está registrado.
 */
try{validateCsrf();$action=textInput('action',20);throttle('recuperacion','',8);
if($action==='solicitar'){
 $account=textInput('cuenta',100);
 $generic=mailAvailable()
  ? 'Si los datos corresponden a una cuenta activa, enviamos un enlace al correo registrado. Revisa también spam y promociones. El enlace vence en 30 minutos. Si no llega, contacta a la escuela.'
  : 'Si los datos corresponden a una cuenta activa, Dirección podrá atender tu solicitud. Contacta a la escuela para verificar tu identidad.';
 if(mailAvailable()){
  $token=bin2hex(random_bytes(16));
  $issued=transaction(function()use($account,$token){
   $u=one("SELECT ID_Usuario,Nombre,Correo FROM usuario WHERE (Usuario=? OR Correo=?) AND activo=1 AND Correo IS NOT NULL AND Correo<>'' FOR UPDATE",[$account,$account]);
   if(!$u)return null;
   $uid=(int)$u['ID_Usuario'];
   $today=(int)(one("SELECT COUNT(*) n FROM recuperacion WHERE ID_Usuario=? AND estado IN ('Emitida','Usada','Cancelada') AND solicitado>DATE_SUB(NOW(),INTERVAL 1 DAY)",[$uid])['n']??0);
   $recent=one("SELECT 1 ok FROM recuperacion WHERE ID_Usuario=? AND estado='Emitida' AND solicitado>DATE_SUB(NOW(),INTERVAL 2 MINUTE)",[$uid]);
   if($today>=3||$recent)return null;
   query("UPDATE recuperacion SET estado='Cancelada',token_hash=NULL WHERE ID_Usuario=? AND usado IS NULL AND estado IN ('Pendiente','Emitida')",[$uid]);
   query("INSERT INTO recuperacion (ID_Usuario,estado,token_hash,vence) VALUES (?,'Emitida',?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))",[$uid,hash('sha256',$token)]);
   return $u+['id'=>(int)db()->insert_id];
  });
  if($issued){
   $link=absoluteUrl('recuperar.php?codigo='.$token);
   try{
    $body=mailTemplate('Recupera tu acceso',[
     'Hola '.$issued['Nombre'].':',
     'Recibimos una solicitud para crear una nueva contraseña en tu cuenta. Usa el botón para hacerlo. El enlace vence en 30 minutos y solo funciona una vez.',
     'Si no fuiste tú, ignora este mensaje: tu contraseña actual sigue funcionando.',
    ],null,['label'=>'Crear nueva contraseña','url'=>$link]);
    $sent=sendMail((string)$issued['Correo'],(string)$issued['Nombre'],'Recupera tu acceso a '.$body['system'],$body);
    if(!$sent)reply(['ok'=>true,'message'=>$generic,'secret'=>'Modo de pruebas (solo en tu computadora): '.$link]);
   }catch(Throwable $ex){
    // Si el correo falla, la solicitud queda para Dirección (flujo anterior).
    error_log('MAD recovery mail: '.$ex->getMessage());
    query("UPDATE recuperacion SET estado='Pendiente',token_hash=NULL WHERE id=?",[(int)$issued['id']]);
   }
  }
  reply(['ok'=>true,'message'=>$generic]);
 }
 transaction(function()use($account){$u=one('SELECT ID_Usuario FROM usuario WHERE (Usuario=? OR Correo=?) AND activo=1 FOR UPDATE',[$account,$account]);if($u&&!one("SELECT id FROM recuperacion WHERE ID_Usuario=? AND estado IN ('Pendiente','Emitida') AND solicitado>DATE_SUB(NOW(),INTERVAL 1 DAY)",[(int)$u['ID_Usuario']]))query('INSERT INTO recuperacion(ID_Usuario) VALUES (?)',[(int)$u['ID_Usuario']]);});
 reply(['ok'=>true,'message'=>$generic]);
}
if($action!=='restablecer')throw new DomainException('Operación inválida.');$code=textInput('codigo',64);$pass=passwordInput('password',8);if($pass!==passwordInput('confirmar',1))throw new DomainException('Las contraseñas no coinciden.');
transaction(function()use($code,$pass){$r=one("SELECT r.*,u.activo,u.Nombre,u.Usuario,u.Correo FROM recuperacion r JOIN usuario u ON u.ID_Usuario=r.ID_Usuario WHERE token_hash=? AND estado='Emitida' AND vence>NOW() AND usado IS NULL FOR UPDATE",[hash('sha256',$code)]);if(!$r||!$r['activo'])throw new DomainException('El código no es válido o ya venció.');requireStrongPassword($pass,[$r['Nombre'],$r['Usuario'],$r['Correo']??'']);query("UPDATE usuario SET Contrasena=?,cambiar_clave=0,sesion_version=sesion_version+1,metodo_acceso=IF(metodo_acceso='google','ambos',metodo_acceso) WHERE ID_Usuario=?",[password_hash($pass,PASSWORD_DEFAULT),(int)$r['ID_Usuario']]);query("UPDATE recuperacion SET usado=NOW(),estado='Usada',token_hash=NULL WHERE id=?",[(int)$r['id']]);query("UPDATE recuperacion SET estado='Cancelada',token_hash=NULL WHERE ID_Usuario=? AND id<>? AND usado IS NULL",[(int)$r['ID_Usuario'],(int)$r['id']]);audit(['ID_Usuario'=>$r['ID_Usuario']],'RECUPERAR_CLAVE','usuario',(int)$r['ID_Usuario']);});reply(['ok'=>true,'message'=>'Contraseña restablecida. Ya puedes iniciar sesión con la nueva contraseña.','redirect'=>url('index.php?recuperada=1')]);
}catch(Throwable $ex){errorReply($ex);}
