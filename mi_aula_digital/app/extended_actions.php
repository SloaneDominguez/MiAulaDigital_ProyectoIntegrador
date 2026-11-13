<?php
function extendedAction(array $u,array $p,string $action): ?array {
 $admin=['cycle_save','cycle_delete','cycle_transition','enroll_save','enroll_fix','mass_move','parent_register','invite_resend','invite_email','data_repair','assignment_save','assignment_remove','family_add','family_remove','recovery_issue','recovery_reject','access_method_save','branding_save','branding_reset'];
 $staff=['grade_batch','grade_save','incident_follow','incident_resolve'];
 if(!in_array($action,array_merge($admin,$staff,['notice_read','notice_confirm']),true))return null;
 if(in_array($action,$admin,true)&&$u['Rol']!=='Direccion')throw new DomainException('Solo Dirección puede realizar esta operación.',403);
 if(in_array($action,$staff,true)&&!in_array($u['Rol'],['Direccion','Docente'],true))throw new DomainException('Sin permiso.',403);
 $message='Cambios guardados.';$extra=[];
 switch($action){
 case 'branding_save':
  $defaults=brandingDefaults();
  $textLimits=[
   'nombre_sistema'=>60,'nombre_escuela'=>120,'lema'=>120,'pie'=>180,
   'login_titulo'=>180,'login_texto'=>420,'login_tarjeta_titulo'=>100,'login_tarjeta_texto'=>220,'login_roles'=>120,
   'menu_etiqueta'=>40,'panel_titulo'=>100,'panel_subtitulo'=>220,'hero_etiqueta'=>80,'hero_titulo'=>180,
   'hero_direccion'=>420,'hero_docente'=>420,'hero_familia'=>420,'panel_lista_titulo'=>100,'panel_lista_texto'=>180,
   'panel_avisos_titulo'=>100,'panel_avisos_vacio'=>100,'panel_avisos_vacio_txt'=>220,
  ];
  $values=[];
  foreach($textLimits as $key=>$max){$values[$key]=textInput($key,$max,false);}
  if($values['nombre_sistema']==='')$values['nombre_sistema']=$defaults['nombre_sistema'];
  foreach(['lema','pie','login_titulo','login_texto','login_tarjeta_titulo','login_tarjeta_texto','login_roles','menu_etiqueta','panel_titulo','panel_subtitulo','hero_etiqueta','hero_titulo','hero_direccion','hero_docente','hero_familia','panel_lista_titulo','panel_lista_texto','panel_avisos_titulo','panel_avisos_vacio','panel_avisos_vacio_txt'] as $key){if($values[$key]==='')$values[$key]=$defaults[$key];}

  foreach(brandingColorKeys() as $key){$raw=$_POST[$key]??'';if(!is_string($raw)||!preg_match('/^#[0-9A-Fa-f]{6}$/',$raw))throw new DomainException('Revisa los colores del editor visual.');$values[$key]=strtolower($raw);}
  foreach(brandingNumericRules() as $key=>[$min,$max,$fallback]){$raw=$_POST[$key]??null;if(!is_scalar($raw)||!preg_match('/^\d+$/',(string)$raw))throw new DomainException('Revisa los controles de tamaño y efecto.');$number=(int)$raw;if($number<$min||$number>$max)throw new DomainException('Uno de los ajustes visuales está fuera del rango permitido.');$values[$key]=(string)$number;}
  foreach(brandingEnumRules() as $key=>$allowed){$raw=$_POST[$key]??'';if(!is_string($raw)||!in_array($raw,$allowed,true))throw new DomainException('Revisa las opciones de estilo seleccionadas.');$values[$key]=$raw;}
  foreach(brandingIconRules() as $key=>$allowed){$raw=$_POST[$key]??'';if(!is_string($raw)||!in_array($raw,$allowed,true))throw new DomainException('Revisa los iconos seleccionados.');$values[$key]=$raw;}

  brandingEnsureSchema();$current=schoolBranding(true);
  $oldLogo=(string)$current['logo'];$oldBackground=(string)$current['fondo_login'];
  $removeLogo=isset($_POST['quitar_logo'])&&$_POST['quitar_logo']==='1';
  $removeBackground=isset($_POST['quitar_fondo_login'])&&$_POST['quitar_fondo_login']==='1';
  $hasLogo=isset($_FILES['logo'])&&is_array($_FILES['logo'])&&(int)($_FILES['logo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
  $hasBackground=isset($_FILES['fondo_login'])&&is_array($_FILES['fondo_login'])&&(int)($_FILES['fondo_login']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
  if($removeLogo&&$hasLogo)throw new DomainException('Elige entre subir un logo nuevo o quitar el actual, no ambas opciones.');
  if($removeBackground&&$hasBackground)throw new DomainException('Elige entre subir un fondo nuevo o quitar el actual, no ambas opciones.');

  $newLogo=null;$newBackground=null;
  try{
   if(!$removeLogo)$newLogo=brandingHandleLogoUpload($oldLogo);
   if(!$removeBackground)$newBackground=brandingHandleImageUpload('fondo_login','login',4*1024*1024);
   $values['logo']=$removeLogo?'':($newLogo??$oldLogo);
   $values['fondo_login']=$removeBackground?'':($newBackground??$oldBackground);
   transaction(function()use($values){brandingUpsert($values);});
  }catch(Throwable $ex){if($newLogo)brandingDeleteStoredAsset($newLogo);if($newBackground)brandingDeleteStoredAsset($newBackground);throw $ex;}
  if($oldLogo!==''&&$oldLogo!==$values['logo'])brandingDeleteStoredAsset($oldLogo);
  if($oldBackground!==''&&$oldBackground!==$values['fondo_login'])brandingDeleteStoredAsset($oldBackground);
  audit($u,'PERSONALIZAR','sistema',null,['escuela'=>$values['nombre_escuela'],'sistema'=>$values['nombre_sistema'],'tema'=>$values['color_principal'],'tipografia'=>$values['tipografia']]);
  $message='Diseño guardado. Los cambios ya se aplican al inicio, Dirección, Docentes y Familias.';break;
 case 'branding_reset':
  brandingEnsureSchema();$current=schoolBranding(true);
  transaction(function()use($u){query('DELETE FROM configuracion_escuela');audit($u,'RESTAURAR_TEMA','sistema',null);});
  if($current['logo']!=='')brandingDeleteStoredAsset((string)$current['logo']);
  if($current['fondo_login']!=='')brandingDeleteStoredAsset((string)$current['fondo_login']);
  $message='Se restauró el diseño original de Mi Aula Digital.';break;
 case 'cycle_save':
  $id=idInput('id',true);$name=textInput('nombre',20);$start=dateInput('fecha_inicio');$end=dateInput('fecha_fin');$periods=idInput('periodos');$type=textInput('tipo_periodo',20);
  if($end<=$start||$periods>5||!in_array($type,['Bimestre','Trimestre','Semestre','Periodo'],true))throw new DomainException('Revisa las fechas y los periodos (1 a 5).');
  validCycle($name,$start,$end);
  $promoteOn=($_POST['promocion']??'1')!=='0';$copyAssign=($_POST['copiar_asignaciones']??'1')!=='0';$graduateOff=($_POST['baja_egresados']??'1')!=='0';$promo=null;$wasNew=!$id;
  if(!$id&&one('SELECT 1 FROM ciclo_escolar WHERE nombre=?',[$name]))throw new DomainException('Ya existe un ciclo llamado '.$name.'. Usa otro nombre (por ejemplo 2026-2027).',409);
  transaction(function()use($u,$id,$name,$start,$end,$periods,$type,$promoteOn,$graduateOff,$copyAssign,&$promo){
   $old=$id?one('SELECT * FROM ciclo_escolar WHERE id_ciclo=? FOR UPDATE',[$id]):null;
   if($id&&!$old)throw new DomainException('Ciclo no encontrado.',404);
   if($old&&$old['estado']==='CERRADO')throw new DomainException('Un ciclo cerrado conserva su historial y no se edita.');
   if(!$id&&one("SELECT id_ciclo,nombre,estado FROM ciclo_escolar WHERE estado<>'CERRADO' ORDER BY id_ciclo DESC LIMIT 1 FOR UPDATE"))throw new DomainException('Cierra el ciclo anterior antes de crear uno nuevo.');
   $fields=rows('SELECT * FROM campo_formativo ORDER BY ID_Campo');$weights=[];$weightTotal=0.0;
   foreach($fields as $f){$raw=$_POST['peso_'.$f['ID_Campo']]??'25';if(!is_scalar($raw)||!preg_match('/^\d{1,3}(\.\d{1,2})?$/',(string)$raw)||(float)$raw<=0)throw new DomainException('Las ponderaciones deben ser mayores que cero y tener como máximo dos decimales.');$weights[$f['ID_Campo']]=(float)$raw;$weightTotal+=(float)$raw;}
   if(abs($weightTotal-100.0)>0.05)throw new DomainException('Las ponderaciones de los campos formativos deben sumar exactamente 100%.');
   if($id&&one('SELECT 1 FROM calificacion WHERE id_ciclo=? LIMIT 1',[$id])){
    if((int)$old['periodos']!==$periods||$old['tipo_periodo']!==$type)throw new DomainException('No se cambian periodos de un ciclo con notas.');
    foreach(cycleFields($id) as $f)if((float)$f['peso']!==$weights[$f['ID_Campo']])throw new DomainException('No se cambian pesos después de capturar notas.');
   }
   $created=false;
   if($id)query('UPDATE ciclo_escolar SET nombre=?,fecha_inicio=?,fecha_fin=?,periodos=?,tipo_periodo=? WHERE id_ciclo=?',[$name,$start,$end,$periods,$type,$id]);
   else{query("INSERT INTO ciclo_escolar(nombre,fecha_inicio,fecha_fin,estado,periodos,tipo_periodo) VALUES (?,?,?,'PLANEADO',?,?)",[$name,$start,$end,$periods,$type]);$id=db()->insert_id;$created=true;}
   foreach($weights as $f=>$weight)query('INSERT INTO ciclo_campo(id_ciclo,ID_Campo,peso) VALUES (?,?,?) ON DUPLICATE KEY UPDATE peso=VALUES(peso)',[$id,(int)$f,$weight]);
   if($created){
    // 1) Promoción automática según las calificaciones del último ciclo cerrado.
    $prev=lastClosedCycle();
    if($prev&&$promoteOn)$promo=applyPromotion($u,(int)$prev['id_ciclo'],$id,$graduateOff);
    // Copia las asignaciones de docentes del ciclo anterior (el docente de 1° A sigue en 1° A).
    if($prev&&$copyAssign){$copied=query('INSERT INTO asignacion_ciclo(ID_Docente,id_ciclo,ID_Grupo,ID_Campo) SELECT ac.ID_Docente,?,ac.ID_Grupo,ac.ID_Campo FROM asignacion_ciclo ac JOIN docente d ON d.ID_Docente=ac.ID_Docente JOIN usuario u ON u.ID_Usuario=d.ID_Usuario JOIN ciclo_campo cc ON cc.id_ciclo=? AND cc.ID_Campo=ac.ID_Campo WHERE ac.id_ciclo=? AND u.activo=1',[$id,$id,(int)$prev['id_ciclo']])->affected_rows;$promo=($promo??[])+['asignaciones'=>max(0,$copied)];}
    // 2) El resto de alumnos activos (nuevos o sin ciclo anterior) se preparan en su grupo actual. Jamás se crean ceros.
    $skipPrev=($prev&&$promoteOn)?' AND NOT EXISTS(SELECT 1 FROM inscripcion p WHERE p.ID_Alumno=a.ID_Alumno AND p.id_ciclo='.(int)$prev['id_ciclo'].' AND p.activo=1)':'';
    query("INSERT INTO inscripcion(ID_Alumno,id_ciclo,ID_Grupo,grupo_nombre,activo,verificado)
      SELECT a.ID_Alumno,?,a.ID_Grupo,COALESCE(CONCAT(g.Grado,'° ',g.Grupo),'Grupo por confirmar'),1,IF(a.ID_Grupo IS NULL,0,1)
      FROM alumno a LEFT JOIN grupo g ON g.ID_Grupo=a.ID_Grupo WHERE a.activo=1
      AND NOT EXISTS(SELECT 1 FROM inscripcion x WHERE x.ID_Alumno=a.ID_Alumno AND x.id_ciclo=?)".$skipPrev,[$id,$id]);
   }
   audit($u,'CONFIGURAR','ciclo',$id,['nuevo'=>$created]);
  });
  if(!$wasNew)$message='Ciclo guardado.';
  elseif($promo&&isset($promo['promueve']))$message='Ciclo creado. Promoción automática: '.$promo['promueve'].' pasan al siguiente grado, '.$promo['repite'].' repiten grado, '.$promo['egresa'].' egresan de 6°'.($promo['incompleto']?', '.$promo['incompleto'].' con calificaciones incompletas (por confirmar)':'').(!empty($promo['grupos_creados'])?'. Se crearon '.$promo['grupos_creados'].' grupo(s) nuevo(s)':'').(!empty($promo['asignaciones'])?'. Se copiaron las asignaciones de docentes del ciclo anterior':'').'. Revisa y ajusta en Inscripciones antes de aperturar el ciclo.';
  else $message='Ciclo creado y alumnos preparados con calificaciones pendientes.';break;
 case 'cycle_delete':
  $id=idInput('ciclo');
  transaction(function()use($u,$id){
   $c=one('SELECT * FROM ciclo_escolar WHERE id_ciclo=? FOR UPDATE',[$id]);
   if(!$c)throw new DomainException('Ciclo no encontrado.',404);
   if($c['estado']!=='PLANEADO')throw new DomainException('Solo puedes eliminar un ciclo que todavía está planeado. Los ciclos activos o cerrados se conservan para proteger el historial.');
   if(one('SELECT 1 FROM calificacion WHERE id_ciclo=? LIMIT 1',[$id])||one('SELECT 1 FROM asignacion_ciclo WHERE id_ciclo=? LIMIT 1',[$id]))throw new DomainException('Este ciclo ya tiene información académica o de trabajo asociada y no puede eliminarse.');
   query('DELETE FROM inscripcion WHERE id_ciclo=?',[$id]);
   query('DELETE FROM ciclo_campo WHERE id_ciclo=?',[$id]);
   query('DELETE FROM ciclo_escolar WHERE id_ciclo=?',[$id]);
   audit($u,'ELIMINAR','ciclo',$id,['nombre'=>$c['nombre']]);
  });$message='Ciclo planeado eliminado. El historial de ciclos activos o cerrados permanece protegido.';break;
 case 'cycle_transition':
  $id=idInput('ciclo');$target=textInput('destino',10);$reason=textInput('motivo',2000,false);if(!in_array($target,['ACTIVO','CERRADO'],true))throw new DomainException('Estado inválido.');
  transaction(function()use($u,$id,$target,$reason){
   $all=rows('SELECT * FROM ciclo_escolar ORDER BY fecha_inicio,id_ciclo FOR UPDATE');$c=null;
   foreach($all as $item)if((int)$item['id_ciclo']===$id)$c=$item;
   if(!$c)throw new DomainException('Ciclo no encontrado.',404);if($c['estado']===$target)throw new DomainException('El ciclo ya está en ese estado.',409);
   if($target==='ACTIVO'){
    foreach($all as $item)if((int)$item['id_ciclo']!==$id&&$item['estado']!=='CERRADO')throw new DomainException('Cierra primero el ciclo anterior. No puede haber dos ciclos abiertos o planeados al mismo tiempo.');
    if($c['estado']==='CERRADO'&&trim($reason)==='')throw new DomainException('Indica el motivo para reabrir un ciclo cerrado.');
   }
   if($target==='CERRADO'){
    if($c['estado']!=='ACTIVO')throw new DomainException('Solo se cierra un ciclo activo.');$review=cycleReview($id);
    if(!$review['students']||!$review['expected']||$review['pending']||$review['unknown'])throw new DomainException('No se puede cerrar: revisa inscripciones, grupos por confirmar y calificaciones pendientes.');
   }
   if($target==='ACTIVO'&&duplicateEnrollments($id))throw new DomainException('Hay alumnos inscritos en más de un grupo en este ciclo. Corrígelos en Inscripciones antes de aperturarlo.');
   query('UPDATE ciclo_escolar SET estado=? WHERE id_ciclo=?',[$target,$id]);
   // Al aperturar, el grupo actual de cada alumno pasa a ser el de su inscripción en este ciclo.
   if($target==='ACTIVO')query('UPDATE alumno a JOIN inscripcion i ON i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? AND i.activo=1 AND i.verificado=1 SET a.ID_Grupo=i.ID_Grupo WHERE i.ID_Grupo IS NOT NULL',[$id]);audit($u,$target==='CERRADO'?'CERRAR':($c['estado']==='CERRADO'?'REABRIR':'ACTIVAR'),'ciclo',$id,['motivo'=>$reason]);
  });$message=$target==='CERRADO'?'Ciclo cerrado. Su historial quedó protegido.':'Ciclo aperturado correctamente.';break;
 case 'enroll_save':
  $id=idInput('alumno');$cycle=idInput('ciclo');$group=idInput('grupo');
  transaction(function()use($u,$id,$cycle,$group){
   $c=one('SELECT * FROM ciclo_escolar WHERE id_ciclo=? FOR UPDATE',[$cycle]);if(!$c||$c['estado']==='CERRADO')throw new DomainException('No se cambia la inscripción de un ciclo cerrado.');studentAccess($u,$id,null,false,true);
   $g=one('SELECT * FROM grupo WHERE ID_Grupo=?',[$group]);if(!$g)throw new DomainException('Grupo inexistente.');
   $old=one('SELECT * FROM inscripcion WHERE ID_Alumno=? AND id_ciclo=? AND activo=1 ORDER BY verificado DESC LIMIT 1',[$id,$cycle]);
   if($old&&(int)$old['ID_Grupo']===$group&&$old['verificado'])throw new DomainException('El alumno ya está inscrito en '.$old['grupo_nombre'].' en ese ciclo.',409);
   // Regla de la revisión: con el ciclo ACTIVO no se cambia a un alumno de grupo.
   if($c['estado']==='ACTIVO'&&$old&&$old['ID_Grupo']&&$old['verificado']&&(int)$old['ID_Grupo']!==$group)throw new DomainException('El ciclo '.$c['nombre'].' está activo: no se puede cambiar al alumno de '.$old['grupo_nombre'].' a otro grupo. Haz el cambio en el siguiente ciclo (planeado), por ejemplo con “Mover grupo completo”.',409);
   if($old&&$old['verificado']&&(int)$old['ID_Grupo']!==$group&&one('SELECT 1 FROM calificacion WHERE ID_Alumno=? AND id_ciclo=? LIMIT 1',[$id,$cycle]))throw new DomainException('El alumno ya tiene notas en ese grupo y ciclo. Conserva ese expediente; inscríbelo en el nuevo ciclo.');
   setEnrollment($id,$cycle,$group,$g['Grado'].'° '.$g['Grupo']);
   if($c['estado']==='ACTIVO')query('UPDATE alumno SET ID_Grupo=? WHERE ID_Alumno=?',[$group,$id]);audit($u,'INSCRIBIR','alumno',$id,['ciclo'=>$cycle,'grupo'=>$group,'anterior'=>$old]);
  });break;
 case 'assignment_save':case 'assignment_remove':
  $teacher=idInput('docente');$cycle=idInput('ciclo');$group=idInput('grupo');
  $fieldRaw=$_POST['campo']??null;
  if(!is_scalar($fieldRaw)||!preg_match('/^\d+$/',(string)$fieldRaw))throw new DomainException('Selecciona un campo formativo válido.');
  $field=(int)$fieldRaw;if($action==='assignment_remove'&&$field<1)throw new DomainException('Selecciona la asignación que deseas retirar.');
  transaction(function()use($u,$teacher,$cycle,$group,$field,$action){
   $c=one('SELECT * FROM ciclo_escolar WHERE id_ciclo=? FOR UPDATE',[$cycle]);if(!$c||$c['estado']==='CERRADO')throw new DomainException('El ciclo está cerrado o no existe.');
   if(!one('SELECT 1 FROM docente d JOIN usuario u ON u.ID_Usuario=d.ID_Usuario WHERE d.ID_Docente=? AND u.activo=1',[$teacher])||!one('SELECT 1 FROM grupo WHERE ID_Grupo=?',[$group]))throw new DomainException('Revisa docente y grupo.');
   if($action==='assignment_save'&&$field===0){
    $fields=rows('SELECT ID_Campo FROM ciclo_campo WHERE id_ciclo=? ORDER BY ID_Campo',[$cycle]);if(!$fields)throw new DomainException('El ciclo no tiene campos formativos configurados.');
    $added=0;foreach($fields as $f){if(one('SELECT 1 FROM asignacion_ciclo WHERE ID_Docente=? AND id_ciclo=? AND ID_Grupo=? AND ID_Campo=?',[$teacher,$cycle,$group,(int)$f['ID_Campo']]))continue;query('INSERT INTO asignacion_ciclo(ID_Docente,id_ciclo,ID_Grupo,ID_Campo) VALUES (?,?,?,?)',[$teacher,$cycle,$group,(int)$f['ID_Campo']]);$added++;}
    if(!$added)throw new DomainException('Ese docente ya tenía asignados todos los campos de ese grupo en ese ciclo.',409);
    audit($u,'assignment_save_all','docente',$teacher,['ciclo'=>$cycle,'grupo'=>$group,'campos'=>count($fields)]);
   }else{
    if(!one('SELECT 1 FROM ciclo_campo WHERE id_ciclo=? AND ID_Campo=?',[$cycle,$field]))throw new DomainException('El campo no pertenece al ciclo.');
    if($action==='assignment_save'){if(one('SELECT 1 FROM asignacion_ciclo WHERE ID_Docente=? AND id_ciclo=? AND ID_Grupo=? AND ID_Campo=?',[$teacher,$cycle,$group,$field]))throw new DomainException('Ese docente ya tiene asignado ese campo en ese grupo y ciclo.',409);query('INSERT INTO asignacion_ciclo(ID_Docente,id_ciclo,ID_Grupo,ID_Campo) VALUES (?,?,?,?)',[$teacher,$cycle,$group,$field]);}
    else query('DELETE FROM asignacion_ciclo WHERE ID_Docente=? AND id_ciclo=? AND ID_Grupo=? AND ID_Campo=?',[$teacher,$cycle,$group,$field]);
    audit($u,$action,'docente',$teacher,['ciclo'=>$cycle,'grupo'=>$group,'campo'=>$field]);
   }
  });$message=$action==='assignment_save'&&$field===0?'Docente asignado a todos los campos formativos.':'Asignación actualizada.';break;
 case 'family_add':case 'family_remove':
  $id=idInput('alumno');$family=idInput('padre');
  transaction(function()use($u,$id,$family,$action){studentAccess($u,$id,null,true,true);if(!one('SELECT 1 FROM padre_tutor WHERE ID_Padre=?',[$family]))throw new DomainException('Tutor inexistente.');
   $linked=(bool)one('SELECT 1 FROM alumno_tutor WHERE ID_Alumno=? AND ID_Padre=?',[$id,$family]);
   if($action==='family_add'&&$linked)throw new DomainException('Ese tutor ya está vinculado con este alumno.',409);
   if($action==='family_remove'&&!$linked)throw new DomainException('Ese vínculo ya no existe.',409);
   if($action==='family_add')query('INSERT INTO alumno_tutor(ID_Alumno,ID_Padre) VALUES (?,?)',[$id,$family]);else query('DELETE FROM alumno_tutor WHERE ID_Alumno=? AND ID_Padre=?',[$id,$family]);
   query('UPDATE alumno SET ID_Padre=(SELECT MIN(ID_Padre) FROM alumno_tutor WHERE ID_Alumno=?),ID_Token=NULL WHERE ID_Alumno=?',[$id,$id]);query('DELETE FROM tokens WHERE ID_Alumno=?',[$id]);audit($u,$action,'alumno',$id,['tutor'=>$family]);
  });break;
 case 'data_repair':
  $r=transaction(fn()=>repairData($u));
  $message='Datos corregidos: '.$r['nombres'].' nombre(s) de grupo unificados y '.$r['sincronizados'].' alumno(s) con su grupo actualizado.';break;
 case 'enroll_fix':
  $id=idInput('alumno');$cycle=idInput('ciclo');$group=idInput('grupo');
  transaction(function()use($u,$id,$cycle,$group){
   $g=one('SELECT * FROM grupo WHERE ID_Grupo=?',[$group]);if(!$g)throw new DomainException('Grupo inexistente.');
   if(!one('SELECT 1 FROM inscripcion WHERE ID_Alumno=? AND id_ciclo=? AND ID_Grupo=? AND activo=1',[$id,$cycle,$group]))throw new DomainException('El alumno no está inscrito en ese grupo.');
   query('UPDATE inscripcion SET activo=0 WHERE ID_Alumno=? AND id_ciclo=? AND (ID_Grupo<>? OR ID_Grupo IS NULL)',[$id,$cycle,$group]);
   $c=one('SELECT estado FROM ciclo_escolar WHERE id_ciclo=?',[$cycle]);if(($c['estado']??'')==='ACTIVO')query('UPDATE alumno SET ID_Grupo=? WHERE ID_Alumno=?',[$group,$id]);
   audit($u,'CORREGIR_INSCRIPCION','alumno',$id,['ciclo'=>$cycle,'grupo'=>$group]);
  });$message='Listo: el alumno quedó solo en un grupo.';break;
 case 'mass_move':
  $fromCycle=idInput('ciclo_origen');$fromGroup=idInput('grupo_origen');$toCycle=idInput('ciclo_destino');$toGroup=idInput('grupo_destino');
  $ids=$_POST['alumnos']??[];if(!is_array($ids)||!$ids)throw new DomainException('Selecciona al menos un alumno para mover.');
  $ids=array_values(array_unique(array_map(static fn($v)=>filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]),$ids)));
  if(in_array(false,$ids,true)||count($ids)>500)throw new DomainException('La lista de alumnos no es válida.');
  if($fromCycle===$toCycle&&$fromGroup===$toGroup)throw new DomainException('El grupo de origen y el de destino son el mismo.');
  $moved=transaction(function()use($u,$fromCycle,$fromGroup,$toCycle,$toGroup,$ids){
   $dest=one('SELECT * FROM ciclo_escolar WHERE id_ciclo=? FOR UPDATE',[$toCycle]);if(!$dest)throw new DomainException('El ciclo de destino no existe.');
   if($dest['estado']!=='PLANEADO')throw new DomainException('Solo se puede mover alumnos a un ciclo PLANEADO. El ciclo '.$dest['nombre'].' está '.mb_strtolower($dest['estado']).': con el ciclo activo no se mueven alumnos de grupo.',409);
   $g=one('SELECT * FROM grupo WHERE ID_Grupo=?',[$toGroup]);if(!$g)throw new DomainException('El grupo de destino no existe.');
   $label=$g['Grado'].'° '.$g['Grupo'];$n=0;
   foreach($ids as $sid){
    $ok=one('SELECT 1 FROM inscripcion i JOIN alumno a ON a.ID_Alumno=i.ID_Alumno WHERE i.ID_Alumno=? AND i.id_ciclo=? AND i.ID_Grupo=? AND i.activo=1 AND a.activo=1',[$sid,$fromCycle,$fromGroup]);
    if(!$ok)throw new DomainException('Uno de los alumnos seleccionados ya no pertenece al grupo de origen. Recarga la página.');
    setEnrollment($sid,$toCycle,$toGroup,$label);$n++;
   }
   audit($u,'MOVER_GRUPO','ciclo',$toCycle,['ciclo'=>$toCycle,'grupo'=>$label,'campos'=>$n]);
   return $n;
  });
  $message=$moved.' '.($moved===1?'alumno movido':'alumnos movidos').' al grupo de destino. Cada alumno queda en un solo grupo por ciclo.';break;
 case 'parent_register':
  $name=validPersonName(textInput('nombre',50),'el nombre');$first=validPersonName(textInput('primer_apellido',50),'el primer apellido');$last=validPersonName(textInput('segundo_apellido',50,false),'el segundo apellido',false);$full=trim("$name $first $last");
  if(mb_strlen($full)>100)throw new DomainException('El nombre completo debe tener como máximo 100 caracteres.');
  $email=validEmail(textInput('correo',100));
  $phone=validPhone(textInput('telefono',20));
  $username=textInput('usuario',50,false);if($username!=='')$username=validUsername($username);
  $student=idInput('alumno',true);
  $invite=transaction(function()use($u,$name,$first,$full,$email,$phone,$username,$student){
   requireFreePhone($phone);if($username==='')$username=suggestUsername($name,$first);requireFreeAccount($username,$email);
   query("INSERT INTO usuario (Nombre,Usuario,Correo,Contrasena,Rol) VALUES (?,?,?,?,'PadreTutor')",[$full,$username,$email,password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT)]);$uid=db()->insert_id;
   query('INSERT INTO padre_tutor (Nombre_Completo,Correo,Telefono,ID_Usuario) VALUES (?,?,?,?)',[$full,$email,$phone,$uid]);$pid=db()->insert_id;
   if($student){if(!one('SELECT 1 FROM alumno WHERE ID_Alumno=? AND activo=1',[$student]))throw new DomainException('El alumno elegido no está disponible.');query('INSERT INTO alumno_tutor(ID_Alumno,ID_Padre) VALUES (?,?)',[$student,$pid]);query('UPDATE alumno SET ID_Padre=COALESCE(ID_Padre,?) WHERE ID_Alumno=?',[$pid,$student]);}
   audit($u,'REGISTRAR_TUTOR','usuario',$uid,['nombre'=>$full]);
   return issueInvitation($uid,$u);
  });
  $extra['invite']=$invite;$message='Padre/tutor registrado. Envíale la invitación para que cree su contraseña.';break;
 case 'invite_email':
  // Envía la invitación desde el servidor (Gmail SMTP). Antes era un enlace mailto:
  // que solo abría el programa de correo de la computadora y no enviaba nada.
  require_once __DIR__.'/mailer.php';
  $userId=idInput('usuario');$token=textInput('codigo',64);
  throttle('invite_email',(string)$userId,6);
  $inv=findInvitation($token);
  if(!$inv||(int)$inv['ID_Usuario']!==$userId||!(int)$inv['activo'])throw new DomainException('Esta invitación ya no es válida. Genera una nueva con “Reenviar invitación”.',410);
  $target=one("SELECT u.ID_Usuario,u.Nombre,u.Usuario,u.Correo,u.Rol,p.Telefono FROM usuario u LEFT JOIN padre_tutor p ON p.ID_Usuario=u.ID_Usuario WHERE u.ID_Usuario=?",[$userId]);
  if(!$target||$target['Rol']!=='PadreTutor')throw new DomainException('Solo se envían invitaciones a cuentas familiares.');
  if(!filter_var((string)$target['Correo'],FILTER_VALIDATE_EMAIL))throw new DomainException('Esta cuenta no tiene un correo válido. Copia el mensaje y envíalo por otro medio.');
  if(!mailAvailable())throw new DomainException('El envío de correos no está configurado. Usa “Copiar mensaje” y envíalo por WhatsApp o desde tu correo.',503);
  $invite=invitationMessage($target,$token,(new DateTimeImmutable((string)$inv['vence']))->format('d/m/Y'));
  $sent=sendInvitationEmail($invite);
  audit($u,'ENVIAR_INVITACION','usuario',$userId,['correo'=>$target['Correo']]);
  $message=$sent?'Invitación enviada a '.maskEmail((string)$target['Correo']).'.':'Modo de pruebas: no hay correo configurado en esta computadora; no se envió.';
  $extra['sent']=$sent;break;
 case 'invite_resend':
  $userId=idInput('usuario');
  $target=one("SELECT Rol FROM usuario WHERE ID_Usuario=? AND activo=1",[$userId]);if(!$target||$target['Rol']!=='PadreTutor')throw new DomainException('Solo se envían invitaciones a cuentas familiares activas.');
  $extra['invite']=transaction(fn()=>issueInvitation($userId,$u));audit($u,'REENVIAR_INVITACION','usuario',$userId);
  $message='Invitación nueva generada. La anterior dejó de funcionar.';break;
 case 'grade_batch':
  $raw=$_POST['cambios']??'';if(!is_string($raw)||strlen($raw)>500000)throw new DomainException('Lote inválido.');$changes=json_decode($raw,true);if(!is_array($changes))throw new DomainException('Lote inválido.');
  $extra['actualizadas']=saveGradeBatch($u,idInput('ciclo'),idInput('bimestre'),$changes);$message='Cambios guardados. El resto de las notas se conservó.';break;
 case 'grade_save':throw new DomainException('Usa la nueva tabla de captura para conservar el control de versiones.',409);
 case 'incident_follow':case 'incident_resolve':
  $id=idInput('id');$comment=validText(textInput('comentario',5000),'el comentario',3);$responsible=idInput('responsable',true);
  transaction(function()use($u,$id,$comment,$responsible,$action){
   $i=one('SELECT * FROM incidencia WHERE ID_Incidencia=? FOR UPDATE',[$id]);if(!$i)throw new DomainException('Incidencia inexistente.',404);studentAccess($u,(int)$i['ID_Alumno'],activeCycleId(),true);
   if($responsible&&!one("SELECT 1 FROM usuario WHERE ID_Usuario=? AND activo=1 AND Rol IN ('Direccion','Docente')",[$responsible]))throw new DomainException('Responsable inválido.');
   query('INSERT INTO incidencia_seguimiento(ID_Incidencia,ID_Usuario,comentario) VALUES (?,?,?)',[$id,(int)$u['ID_Usuario'],$comment]);
   query('UPDATE incidencia SET responsable=COALESCE(?,responsable),Estado=?,fecha_resolucion=? WHERE ID_Incidencia=?',[$responsible,$action==='incident_resolve'?'Resuelta':$i['Estado'],$action==='incident_resolve'?date('Y-m-d H:i:s'):$i['fecha_resolucion'],$id]);audit($u,$action,'incidencia',$id);
  });break;
 case 'notice_read':case 'notice_confirm':
  require_once __DIR__.'/data.php';$id=idInput('id');if(!announcementById($u,$id))throw new DomainException('Anuncio no disponible.',403);
  query('INSERT INTO publicacion_lectura(ID_Publicacion,ID_Usuario,confirmado) VALUES (?,?,?) ON DUPLICATE KEY UPDATE confirmado=COALESCE(confirmado,VALUES(confirmado))',[$id,(int)$u['ID_Usuario'],$action==='notice_confirm'?date('Y-m-d H:i:s'):null]);$message=$action==='notice_confirm'?'Recepción confirmada.':'Lectura registrada.';break;
 case 'access_method_save':
  authEnsureSchema();$userId=idInput('usuario');$method=textInput('metodo',10);$unlink=isset($_POST['desvincular_google'])&&$_POST['desvincular_google']==='1';
  if(!in_array($method,['local','google','ambos'],true))throw new DomainException('Método de acceso inválido.');
  if($method==='google'&&!googleAuthConfigured())throw new DomainException('Configura primero las credenciales de Google antes de dejar una cuenta solo con Google.');
  if($unlink&&$method!=='local')throw new DomainException('Para desvincular Google, cambia el método a Local en la misma operación.');
  transaction(function()use($u,$userId,$method,$unlink){
   $target=one('SELECT * FROM usuario WHERE ID_Usuario=? FOR UPDATE',[$userId]);if(!$target)throw new DomainException('Usuario no encontrado.',404);
   if((int)$target['ID_Usuario']===(int)$u['ID_Usuario']&&$method==='google'&&!$target['google_sub'])throw new DomainException('No dejes tu propia cuenta solo con Google antes de vincularla.');
   query('UPDATE usuario SET metodo_acceso=?,google_sub=IF(?,NULL,google_sub),correo_verificado=IF(?,0,correo_verificado),sesion_version=sesion_version+1 WHERE ID_Usuario=?',[$method,$unlink?1:0,$unlink?1:0,$userId]);
   audit($u,'METODO_ACCESO','usuario',$userId,['metodo'=>$method,'google_desvinculado'=>$unlink]);
  });$message='Método de acceso actualizado. Las sesiones anteriores de esa cuenta se cerraron.';break;
 case 'recovery_reject':
  $id=idInput('id');query("UPDATE recuperacion SET estado='Cancelada',token_hash=NULL WHERE id=?",[$id]);audit($u,'CANCELAR_RECUPERACION','recuperacion',$id);break;
 case 'recovery_issue':
  $id=idInput('id');$reason=validText(textInput('motivo',500),'cómo verificaste la identidad',5);$token=bin2hex(random_bytes(32));
  transaction(function()use($u,$id,$reason,$token){$r=one('SELECT * FROM recuperacion WHERE id=? FOR UPDATE',[$id]);if(!$r||$r['estado']!=='Pendiente')throw new DomainException('Solicitud ya atendida.',409);
   query("UPDATE recuperacion SET estado='Cancelada',token_hash=NULL WHERE ID_Usuario=? AND id<>? AND usado IS NULL",[(int)$r['ID_Usuario'],$id]);query("UPDATE recuperacion SET estado='Emitida',token_hash=?,vence=DATE_ADD(NOW(),INTERVAL 30 MINUTE) WHERE id=?",[hash('sha256',$token),$id]);audit($u,'AUTORIZAR_RECUPERACION','recuperacion',$id,['verificacion'=>$reason]);
  });$extra['secret']=$token;$message='Entrega este código por un canal privado a la persona cuya identidad verificaste. Vence en 30 minutos y solo se muestra ahora.';break;
 }
 return ['ok'=>true,'message'=>$message]+$extra;
}
