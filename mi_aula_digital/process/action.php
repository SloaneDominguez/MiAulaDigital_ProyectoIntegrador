<?php
require __DIR__.'/../app/bootstrap.php';
try {
 validateCsrf(); $u=requireUser(); $p=profile($u); $action=textInput('action',40); require __DIR__.'/../app/extended_actions.php'; $extended=extendedAction($u,$p,$action); if($extended!==null)reply($extended);
 $permissions=[
  'student_save'=>['Direccion'],'student_archive'=>['Direccion'],'student_restore'=>['Direccion'],
  'group_save'=>['Direccion'],'group_delete'=>['Direccion'],'teacher_save'=>['Direccion'],'user_archive'=>['Direccion'],
  'request_review'=>['Direccion'],'cycle_save'=>['Direccion'],'token_renew'=>['Direccion'],
  'grade_save'=>['Direccion','Docente'],'incident_save'=>['Direccion','Docente'],'incident_resolve'=>['Direccion','Docente'],
  'publication_save'=>['Direccion','Docente'],'publication_delete'=>['Direccion','Docente'],'link_student'=>['PadreTutor'],
 ];
 if(!isset($permissions[$action])||!in_array($u['Rol'],$permissions[$action],true)) throw new DomainException('No tienes permiso para esta operación.',403);
 $message='Cambios guardados.'; $extra=[];
 switch($action) {
 case 'student_save':
  $id=idInput('id',true); $curpRaw=mb_strtoupper(textInput('curp',18));
  // Una CURP ya guardada (datos anteriores) se respeta al editar; una nueva se valida completa.
  $curp=($id&&one('SELECT 1 FROM alumno WHERE ID_Alumno=? AND CURP=?',[$id,$curpRaw]))?$curpRaw:validCurp($curpRaw);
  $name=validPersonName(textInput('nombre',50),'el nombre'); $first=validPersonName(textInput('primer_apellido',50),'el primer apellido'); $last=validPersonName(textInput('segundo_apellido',50,false),'el segundo apellido',false); $group=$id?(one('SELECT ID_Grupo FROM alumno WHERE ID_Alumno=?',[$id])['ID_Grupo']??null):idInput('grupo',true);
  if($group&&!one('SELECT ID_Grupo FROM grupo WHERE ID_Grupo=?',[$group])) throw new DomainException('El grupo no existe.');
  $familySet=false; $family=$familySet?idInput('padre',true):null;
  if($family&&!one('SELECT p.ID_Padre FROM padre_tutor p JOIN usuario u ON u.ID_Usuario=p.ID_Usuario WHERE p.ID_Padre=? AND u.activo=1',[$family])) throw new DomainException('La cuenta familiar no está disponible.');
  transaction(function()use($u,$id,$curp,$name,$first,$last,$group,$familySet,$family){
   $dupCurp=one("SELECT ID_Alumno,CONCAT_WS(' ',Nombre,Primer_Apellido,Segundo_Apellido) nombre,activo FROM alumno WHERE CURP=? AND ID_Alumno<>?",[$curp,$id??0]);
   if($dupCurp) throw new DomainException('Esa CURP ya pertenece a '.$dupCurp['nombre'].($dupCurp['activo']?'':' (dado de baja; puedes reactivarlo)').'. Un alumno no puede registrarse dos veces.',409);
   $checkGroup=$id?(one('SELECT ID_Grupo FROM inscripcion WHERE ID_Alumno=? AND id_ciclo=? AND activo=1',[$id,activeCycleId()])['ID_Grupo']??$group):$group;
   if($checkGroup&&one("SELECT 1 FROM alumno WHERE activo=1 AND ID_Grupo=? AND ID_Alumno<>? AND LOWER(TRIM(Nombre))=LOWER(?) AND LOWER(TRIM(Primer_Apellido))=LOWER(?) AND LOWER(TRIM(COALESCE(Segundo_Apellido,'')))=LOWER(?)",[$checkGroup,$id??0,$name,$first,$last])) throw new DomainException('Ya existe un alumno con el mismo nombre completo en ese grupo. Revisa que no lo estés registrando dos veces.',409);
   if($id){ $old=studentAllowed($u,$id,true); query('UPDATE alumno SET CURP=?,Nombre=?,Primer_Apellido=?,Segundo_Apellido=?,ID_Grupo=? WHERE ID_Alumno=?',[$curp,$name,$first,$last,$group,$id]);
    if($familySet && (int)$old['ID_Padre']!==(int)$family){query('UPDATE alumno SET ID_Padre=?,ID_Token=NULL WHERE ID_Alumno=?',[$family,$id]);query('DELETE FROM tokens WHERE ID_Alumno=?',[$id]);}
   }else {query('INSERT INTO alumno (CURP,Nombre,Primer_Apellido,Segundo_Apellido,ID_Grupo,ID_Padre) VALUES (?,?,?,?,?,?)',[$curp,$name,$first,$last,$group,$family]);$id=db()->insert_id;if($group&&($cycle=activeCycleId())){$g=one('SELECT * FROM grupo WHERE ID_Grupo=?',[$group]);setEnrollment($id,$cycle,$group,$g['Grado'].'° '.$g['Grupo']);}}
  });
  break;
 case 'student_archive':
  $id=idInput('id'); transaction(function()use($u,$id){studentAllowed($u,$id,true); query('UPDATE alumno SET activo=0 WHERE ID_Alumno=?',[$id]);query('UPDATE inscripcion SET activo=0 WHERE ID_Alumno=? AND id_ciclo=?',[$id,activeCycleId()]);audit($u,'BAJA','alumno',$id);});
  $message='Alumno dado de baja. Su historial se conserva.'; break;
 case 'student_restore':
  $id=idInput('id');transaction(function()use($u,$id){studentAccess($u,$id,null,true,true);query('UPDATE alumno SET activo=1 WHERE ID_Alumno=?',[$id]);query('UPDATE inscripcion SET activo=1 WHERE ID_Alumno=? AND id_ciclo=?',[$id,activeCycleId()]);audit($u,'REACTIVAR','alumno',$id);}); $message='Alumno reactivado.'; break;
 case 'group_save':
  $id=idInput('id',true); $grade=idInput('grado'); $letter=mb_strtoupper(textInput('grupo',2));
  if($grade>6||!preg_match('/^[A-Z]{1,2}$/',$letter)) throw new DomainException('Selecciona un grado del 1 al 6 y una letra de grupo.');
  transaction(function()use($id,$grade,$letter){
   // Serializa el catálogo sin imponer una migración destructiva sobre datos existentes.
   rows('SELECT ID_Grupo FROM grupo FOR UPDATE');
   if(one('SELECT ID_Grupo FROM grupo WHERE Grado=? AND Grupo=? AND ID_Grupo<>?',[$grade,$letter,$id??0])) throw new DomainException('Este grupo ya existe.',409);
   if($id) query('UPDATE grupo SET Grado=?,Grupo=? WHERE ID_Grupo=?',[$grade,$letter,$id]);
   else query('INSERT INTO grupo (Grado,Grupo) VALUES (?,?)',[$grade,$letter]);
  }); break;
 case 'group_delete':
  $id=idInput('id'); transaction(function()use($id){
   if(!one('SELECT ID_Grupo FROM grupo WHERE ID_Grupo=? FOR UPDATE',[$id])) throw new DomainException('El grupo no existe.',404);
   if(one('SELECT 1 FROM inscripcion WHERE ID_Grupo=? LIMIT 1',[$id])||one('SELECT 1 FROM asignacion_ciclo WHERE ID_Grupo=? LIMIT 1',[$id])||one('SELECT ID_Alumno FROM alumno WHERE ID_Grupo=? LIMIT 1',[$id])||one('SELECT ID_Docente FROM docente WHERE ID_Grupo_Asignado=? LIMIT 1',[$id])||one('SELECT ID_Publicacion FROM publicacion WHERE ID_Grupo=? LIMIT 1',[$id])) throw new DomainException('Este grupo tiene inscripciones, asignaciones o registros asociados. Se conserva para proteger el historial.');
   query('DELETE FROM grupo WHERE ID_Grupo=?',[$id]);
  }); break;
 case 'teacher_save':
  $id=idInput('id',true); $name=validPersonName(textInput('nombre',50),'el nombre'); $first=validPersonName(textInput('primer_apellido',50),'el primer apellido'); $last=validPersonName(textInput('segundo_apellido',50,false),'el segundo apellido',false); $full=trim("$name $first $last");
  if(mb_strlen($full)>100) throw new DomainException('El nombre completo no puede superar 100 caracteres.');
  $email=validEmail(textInput('correo',100));
  $username=validUsername(textInput('usuario',50));
  $group=null;
  $pass=passwordInput('password'); if(!$id&&$pass==='') throw new DomainException('Escribe una contraseña temporal para el docente.');
  if($pass!=='') requireStrongPassword($pass,[$name,$first,$last,$username,$email]);
  transaction(function()use($id,$name,$first,$last,$full,$email,$username,$group,$pass){
   if($id) {
    $d=one('SELECT * FROM docente WHERE ID_Docente=? FOR UPDATE',[$id]); if(!$d) throw new DomainException('El docente no existe.',404);
    requireFreeAccount($username,$email,(int)$d['ID_Usuario']);
    query('UPDATE docente SET Nombre=?,Primer_Apellido=?,Segundo_Apellido=?,ID_Grupo_Asignado=? WHERE ID_Docente=?',[$name,$first,$last,$group,$id]);
    query('UPDATE usuario SET Nombre=?,Usuario=?,Correo=?,activo=1 WHERE ID_Usuario=?',[$full,$username,$email,(int)$d['ID_Usuario']]);
    if($pass!=='') query('UPDATE usuario SET Contrasena=?,cambiar_clave=1,sesion_version=sesion_version+1 WHERE ID_Usuario=?',[password_hash($pass,PASSWORD_DEFAULT),(int)$d['ID_Usuario']]);
   } else {
    requireFreeAccount($username,$email);
    query("INSERT INTO usuario (Nombre,Usuario,Correo,Contrasena,Rol) VALUES (?,?,?,?,'Docente')",[$full,$username,$email,password_hash($pass,PASSWORD_DEFAULT)]); $uid=db()->insert_id; query('UPDATE usuario SET cambiar_clave=1 WHERE ID_Usuario=?',[$uid]);
    query('INSERT INTO docente (Nombre,Primer_Apellido,Segundo_Apellido,ID_Usuario,ID_Grupo_Asignado) VALUES (?,?,?,?,?)',[$name,$first,$last,$uid,$group]);
   }
  }); break;
 case 'user_archive':
  $id=idInput('id'); if($id===(int)$u['ID_Usuario']) throw new DomainException('No puedes desactivar tu propia cuenta.');
  $target=one('SELECT Rol FROM usuario WHERE ID_Usuario=?',[$id]);
  if(!$target||$target['Rol']!=='Docente') throw new DomainException('Esta operación solo permite desactivar docentes.');
  query('UPDATE usuario SET activo=0,sesion_version=sesion_version+1 WHERE ID_Usuario=?',[$id]); $message='Acceso desactivado. El historial permanece disponible.'; break;
 case 'request_review':
  $id=idInput('id'); $decision=textInput('decision',10); if(!in_array($decision,['aprobar','rechazar'],true)) throw new DomainException('Decisión inválida.');
  $reviewed=transaction(function()use($id,$decision){
   $r=one('SELECT * FROM solicitud_acceso WHERE ID_Solicitud=? FOR UPDATE',[$id]); if(!$r||$r['Estado']!=='Pendiente') throw new DomainException('Esta solicitud ya fue atendida.',409);
   if($decision==='aprobar') {
    requireFreeAccount((string)$r['Usuario'],(string)$r['Correo']);
    $full=trim($r['Nombre'].' '.$r['Primer_Apellido'].' '.$r['Segundo_Apellido']); if(mb_strlen($full)>100) throw new DomainException('La solicitud tiene un nombre demasiado largo. Revisa sus datos.');
    authEnsureSchema(); $verified=(int)($r['correo_verificado']??0)===1?1:0;
    query("INSERT INTO usuario (Nombre,Usuario,Correo,Contrasena,Rol,correo_verificado) VALUES (?,?,?,?,'Docente',?)",[$full,$r['Usuario'],$r['Correo'],$r['Contrasena'],$verified]); $uid=db()->insert_id;
    query('INSERT INTO docente (Nombre,Primer_Apellido,Segundo_Apellido,ID_Usuario) VALUES (?,?,?,?)',[$r['Nombre'],$r['Primer_Apellido'],$r['Segundo_Apellido'],$uid]);
   }
   query('UPDATE solicitud_acceso SET Estado=?,Fecha_Revision=NOW() WHERE ID_Solicitud=?',[$decision==='aprobar'?'Aprobada':'Rechazada',$id]);
   return $r;
  }); $message='Solicitud atendida.';
  // Aviso por correo (fuera de la transacción: si el correo falla, la decisión ya quedó guardada).
  require_once __DIR__.'/../app/mailer.php';
  if(mailConfigured()){
   try{ notifyTeacherReview($reviewed,$decision==='aprobar'); $message.=' Se avisó por correo a '.maskEmail((string)$reviewed['Correo']).'.'; }
   catch(Throwable $mailError){ $message.=' No se pudo enviar el aviso por correo; avísale por otro medio.'; }
  }
  break;
 case 'token_renew':
  $id=idInput('alumno'); $days=idInput('dias'); if(!in_array($days,[7,30,90,180,365],true)) throw new DomainException('Selecciona una vigencia válida.');
  $token=bin2hex(random_bytes(32)); $expires=(new DateTimeImmutable())->modify("+$days days")->format('Y-m-d H:i:s');
  transaction(function()use($u,$id,$token,$expires){
   studentAllowed($u,$id,true);
   query('DELETE FROM tokens WHERE ID_Alumno=?',[$id]);
   query('INSERT INTO tokens (Token_String,Fecha_Inicio,Fecha_Expiracion,ID_Alumno) VALUES (?,NOW(),?,?)',[$token,$expires,$id]); $tid=db()->insert_id;
   query('UPDATE alumno SET ID_Token=? WHERE ID_Alumno=?',[$tid,$id]);
  }); $message='Código renovado. Los códigos anteriores de este alumno dejaron de funcionar.'; $extra=['token'=>$token]; break;
 case 'link_student':
  $token=textInput('token',500); throttle('vincular','',8);
  if(!preg_match('/^[a-f0-9]{64}$/',$token)) throw new DomainException('El código no es válido. Pide a Dirección un código nuevo de 64 caracteres.');
  transaction(function()use($token,$p){
   $t=one('SELECT * FROM tokens WHERE Token_String=? AND Fecha_Expiracion>NOW() ORDER BY ID_Token DESC LIMIT 1 FOR UPDATE',[$token]);
   if(!$t) throw new DomainException('El código no es válido o ya venció. Pide uno nuevo a Dirección.');
   $a=one('SELECT * FROM alumno WHERE ID_Alumno=? AND activo=1 FOR UPDATE',[(int)$t['ID_Alumno']]);
   if(!$a||(int)$a['ID_Token']!==(int)$t['ID_Token']) throw new DomainException('El código ya no está disponible.');
   
   if(one('SELECT 1 FROM alumno_tutor WHERE ID_Alumno=? AND ID_Padre=?',[(int)$a['ID_Alumno'],(int)$p['ID_Padre']])) throw new DomainException('Este alumno ya está vinculado a tu cuenta.');
   query('INSERT INTO alumno_tutor(ID_Alumno,ID_Padre) VALUES (?,?)',[(int)$a['ID_Alumno'],(int)$p['ID_Padre']]); query('UPDATE alumno SET ID_Padre=COALESCE(ID_Padre,?) WHERE ID_Alumno=?',[(int)$p['ID_Padre'],(int)$a['ID_Alumno']]);
   query('UPDATE alumno SET ID_Token=NULL WHERE ID_Alumno=?',[(int)$a['ID_Alumno']]);
   query('DELETE FROM tokens WHERE ID_Alumno=?',[(int)$a['ID_Alumno']]);
  }); $message='Alumno vinculado a tu cuenta.'; break;
 case 'incident_save':
  $id=idInput('alumno'); $type=validText(textInput('tipo',100),'el tipo de incidencia',3); $description=validText(textInput('descripcion',10000),'la descripción',5); $date=validEventDate(dateInput('fecha'),'La fecha de la incidencia');
  transaction(function()use($u,$p,$id,$type,$description,$date){ studentAllowed($u,$id,true); query("INSERT INTO incidencia (Tipo_Incidencia,Descripcion,Fecha,Estado,ID_Alumno,ID_Docente) VALUES (?,?,?,'Pendiente',?,?)",[$type,$description,$date,$id,$u['Rol']==='Docente'?(int)$p['ID_Docente']:null]); }); break;
 case 'publication_save':
  require_once __DIR__.'/../app/data.php';announcementEnsureSchema();
  $id=idInput('id',true); $title=validText(textInput('titulo',150),'el título',3); $body=validText(textInput('contenido',10000),'el mensaje',5); $group=idInput('grupo',true); $type=textInput('tipo',20);
  if(!in_array($type,['Anuncio','Tarea','Reunión'],true)) throw new DomainException('Tipo de anuncio inválido.');
  if($u['Rol']==='Docente') { $groups=array_column(teacherGroups($u,activeCycleId()),'ID_Grupo'); if(!$group||!in_array($group,array_map('intval',$groups),true)) throw new DomainException('Solo puedes publicar para un grupo asignado.',403); }
  if($group&&!one('SELECT ID_Grupo FROM grupo WHERE ID_Grupo=?',[$group])) throw new DomainException('El grupo no existe.');
  transaction(function()use($u,$p,$id,$title,$body,$group,$type){
   if($id){ $pub=one('SELECT * FROM publicacion WHERE ID_Publicacion=? FOR UPDATE',[$id]); if(!$pub) throw new DomainException('El anuncio no existe.',404);
    if($u['Rol']==='Docente' && (int)$pub['ID_Docente']!==(int)$p['ID_Docente']) throw new DomainException('Solo puedes editar tus propios anuncios.',403);
    query('UPDATE publicacion SET Titulo=?,Contenido=?,ID_Grupo=? WHERE ID_Publicacion=?',[$title,$body,$group,$id]);
    query('DELETE FROM publicacion_lectura WHERE ID_Publicacion=?',[$id]);
   } else { query('INSERT INTO publicacion (Titulo,Contenido,Fecha_Publicacion,ID_Director,ID_Docente,ID_Grupo) VALUES (?,?,CURDATE(),?,?,?)',[$title,$body,$u['Rol']==='Direccion'?(int)$p['ID_Director']:null,$u['Rol']==='Docente'?(int)$p['ID_Docente']:null,$group]); $id=db()->insert_id; }
   query('INSERT INTO aviso (Tipo_Aviso,ID_Publicacion) VALUES (?,?) ON DUPLICATE KEY UPDATE Tipo_Aviso=VALUES(Tipo_Aviso)',[$type,$id]);
  }); break;
 case 'publication_delete':
  $id=idInput('id'); transaction(function()use($u,$p,$id){ $pub=one('SELECT * FROM publicacion WHERE ID_Publicacion=? FOR UPDATE',[$id]); if(!$pub) throw new DomainException('El anuncio no existe.',404); if($u['Rol']==='Docente'&&(int)$pub['ID_Docente']!==(int)$p['ID_Docente']) throw new DomainException('Solo puedes eliminar tus propios anuncios.',403); query('DELETE FROM publicacion WHERE ID_Publicacion=?',[$id]); }); break;
 }
 reply(['ok'=>true,'message'=>$message]+$extra);
} catch(Throwable $ex) { errorReply($ex); }
