<?php
function cycleOptions(bool $editableOnly=false): array {$out=[];foreach(cycles() as $c){if($editableOnly&&$c['estado']==='CERRADO')continue;$out[$c['id_ciclo']]=$c['nombre'].' · '.$c['estado'];}return $out;}
function allStudentsOptions(): array {$out=[''=>'Selecciona un alumno'];foreach(rows('SELECT ID_Alumno,CONCAT_WS(" ",Nombre,Primer_Apellido,Segundo_Apellido) nombre FROM alumno WHERE activo=1 ORDER BY Primer_Apellido') as $a)$out[$a['ID_Alumno']]=$a['nombre'];return $out;}
function saveFooter(string $label='Guardar',string $style='primary'): void {feedback();echo '<button type="submit" class="button '.e($style).'">'.e($label).'</button></form>';}

function auditActionPresentation(string $action): array {
 $map=[
  'PERSONALIZAR'=>['Personalización actualizada',''],
  'RESTAURAR_TEMA'=>['Diseño restaurado','green'],
  'CONFIGURAR'=>['Ciclo configurado',''],
  'ELIMINAR'=>['Elemento eliminado','red'],
  'CERRAR'=>['Ciclo cerrado','orange'],
  'REABRIR'=>['Ciclo reabierto','orange'],
  'ACTIVAR'=>['Ciclo activado','green'],
  'INSCRIBIR'=>['Alumno inscrito','green'],
  'assignment_save_all'=>['Docente asignado','green'],
  'assignment_save'=>['Docente asignado','green'],
  'assignment_remove'=>['Asignación retirada','orange'],
  'family_add'=>['Vínculo familiar autorizado','green'],
  'family_remove'=>['Vínculo familiar revocado','orange'],
  'incident_follow'=>['Seguimiento de incidencia',''],
  'incident_resolve'=>['Incidencia resuelta','green'],
  'METODO_ACCESO'=>['Método de acceso actualizado',''],
  'CANCELAR_RECUPERACION'=>['Recuperación cancelada','orange'],
  'AUTORIZAR_RECUPERACION'=>['Recuperación autorizada','green'],
  'BAJA'=>['Alumno dado de baja','red'],
  'REACTIVAR'=>['Alumno reactivado','green'],
  'CAMBIAR_CLAVE'=>['Contraseña actualizada',''],
  'DESCARGAR_RESPALDO'=>['Respaldo descargado','green'],
  'MOVER_GRUPO'=>['Grupo movido','green'],
  'REGISTRAR_TUTOR'=>['Padre/tutor registrado','green'],
  'REENVIAR_INVITACION'=>['Invitación reenviada',''],
  'ACEPTAR_INVITACION'=>['Invitación aceptada','green'],
  'CORREGIR_INSCRIPCION'=>['Inscripción corregida','orange'],
  'PROMOCION'=>['Promoción automática','green'],
  'REPARAR_DATOS'=>['Datos corregidos','green'],
  'EGRESO'=>['Alumno egresado',''],
  'RECUPERAR_CLAVE'=>['Contraseña recuperada',''],
 ];
 return $map[$action]??[ucfirst(strtolower(str_replace('_',' ',$action))),''];
}
function auditEntityLabel(string $entity, mixed $reference): string {
 $labels=['sistema'=>'Sistema','ciclo'=>'Ciclo escolar','alumno'=>'Alumno','docente'=>'Docente','incidencia'=>'Incidencia','usuario'=>'Usuario','recuperacion'=>'Recuperación','base'=>'Base de datos'];
 $label=$labels[$entity]??ucfirst($entity);
 return ($reference!==null&&$reference!=='')?$label.' #'.$reference:$label;
}
function auditDetailText(array $event): string {
 $action=(string)($event['accion']??'');
 $raw=trim((string)($event['detalle']??''));
 $data=$raw!==''?json_decode($raw,true):null;
 if($action==='RESTAURAR_TEMA')return 'Se restauró la apariencia original del sistema.';
 if($action==='PERSONALIZAR'&&is_array($data)){
  $parts=['Se actualizó el diseño general.'];
  if(!empty($data['tema']))$parts[]='Color principal: '.$data['tema'].'.';
  if(!empty($data['tipografia']))$parts[]='Tipografía: '.($data['tipografia']==='sistema'?'Sistema':$data['tipografia']).'.';
  return implode(' ',$parts);
 }
 if(!is_array($data))return $raw!==''?$raw:'Sin detalles adicionales.';
 $parts=[];
 $labels=['motivo'=>'Motivo','nombre'=>'Nombre','metodo'=>'Acceso','verificacion'=>'Verificación','tablas'=>'Tablas incluidas','campos'=>'Cantidad','promovidos'=>'Pasan de grado','nombres'=>'Nombres de grupo unificados','sincronizados'=>'Alumnos actualizados','repiten'=>'Repiten','egresados'=>'Egresan','ciclo'=>'Ciclo','grupo'=>'Grupo','campo'=>'Campo','tutor'=>'Tutor'];
 foreach($labels as $key=>$label){
  if(!array_key_exists($key,$data)||$data[$key]===null||$data[$key]==='')continue;
  $value=$data[$key];
  if(is_bool($value))$value=$value?'Sí':'No';
  if(is_scalar($value))$parts[]=$label.': '.$value.'.';
 }
 if(array_key_exists('google_desvinculado',$data)&&$data['google_desvinculado'])$parts[]='Google fue desvinculado.';
 if(array_key_exists('nuevo',$data))$parts[]=$data['nuevo']?'Se creó un nuevo registro.':'Se actualizó un registro existente.';
 return $parts?implode(' ',$parts):'Operación registrada correctamente.';
}

$page=max(1,(int)($_GET['pagina']??1));
if($view==='ciclos'):
 $selected=selectedCycle(); ?>
<section class="card glass cycle-manager"><div><div class="overline">Administración académica</div><h2>Ciclos escolares</h2><p>Crea, apertura y cierra ciclos desde un solo lugar. El ciclo actual es visible para Dirección, Docentes y Familias. Usa el botón <strong>Nuevo ciclo escolar</strong> de la parte superior cuando el ciclo anterior ya esté cerrado.</p></div></section>
<?php
 $openCycleNow=one("SELECT * FROM ciclo_escolar WHERE estado<>'CERRADO' ORDER BY estado='ACTIVO' DESC,id_ciclo DESC LIMIT 1");
 $promoBase=$openCycleNow?($openCycleNow['estado']==='ACTIVO'?$openCycleNow:null):lastClosedCycle();
 if($promoBase): $plan=promotionPlan((int)$promoBase['id_ciclo']);$ps=promotionSummary($plan);$isProjection=$promoBase['estado']==='ACTIVO';
  $labels=['promueve'=>['Pasa de grado','green'],'repite'=>['Repite grado','orange'],'egresa'=>['Egresa','green'],'incompleto'=>['Faltan calificaciones',''],'sin_grupo'=>['Sin grupo','red']]; ?>
<section class="card glass promo-card"><div class="card-header"><div><div class="overline">Promoción automática</div><h2><?=$isProjection?'Así quedarían los grupos si el ciclo terminara hoy':'Vista previa del siguiente ciclo'?></h2><p><?=$isProjection?'Se calcula con las calificaciones capturadas hasta ahora en '.e($promoBase['nombre']).'. Al cerrar el ciclo y crear el nuevo, se aplica automáticamente.':'Al crear el ciclo nuevo, cada alumno de '.e($promoBase['nombre']).' quedará así. Después puedes ajustar casos en Inscripciones.'?></p></div></div>
<div class="promo-stats"><div><strong><?=$ps['promueve']?></strong><span>pasan al siguiente grado</span></div><div><strong><?=$ps['repite']?></strong><span>repiten grado</span></div><div><strong><?=$ps['egresa']?></strong><span>egresan de 6°</span></div><?php if($ps['incompleto']+$ps['sin_grupo']): ?><div><strong><?=$ps['incompleto']+$ps['sin_grupo']?></strong><span>por confirmar</span></div><?php endif; ?></div>
<p class="help">Regla: aprueba quien tiene promedio final de <?=number_format(MAD_CALIFICACION_APROBATORIA,1)?> o más en <strong>todos</strong> los campos formativos. Pasa al grado siguiente con la misma letra (1° A → 2° A).</p>
<details class="promo-details"><summary class="button">Ver alumno por alumno (<?=count($plan)?>)</summary><?php if($plan){$pg=[];foreach($plan as $p)if($p['grupo'])$pg[$p['grupo']]=$p['grupo'];ksort($pg,SORT_NATURAL);listFilter('promo-table',['resultado'=>['Resultado',array_map(fn($l)=>$l[0],$labels)],'grupo'=>['Grupo actual',$pg]],'Buscar alumno…');} ?>
<div class="table-wrap"><table id="promo-table"><thead><tr><th>Alumno</th><th>Grupo actual</th><th>Promedio</th><th>Resultado</th><th>Siguiente ciclo</th></tr></thead><tbody><?php foreach($plan as $p): ?><tr<?=filterAttrs(['resultado'=>$p['resultado'],'grupo'=>(string)$p['grupo']])?>><td><strong><?=e($p['nombre'])?></strong><?php if($p['reprobados']): ?><small>Reprobó: <?=e(implode(', ',$p['reprobados']))?></small><?php endif; ?></td><td><?=e($p['grupo'])?></td><td><?=e($p['promedio']===null?'—':number_format($p['promedio'],1))?></td><td><?=badge($labels[$p['resultado']][0],$labels[$p['resultado']][1])?></td><td><?=e($p['destino'])?></td></tr><?php endforeach; ?></tbody></table></div></details></section>
<?php endif; ?>
<?php $health=dataHealth(); if($health): ?>
<section class="card glass health-card" id="revision-datos"><div class="card-header"><div><div class="overline">Revisión de datos</div><h2>Encontramos cosas por corregir</h2><p>Vienen de datos anteriores. Lo que se puede corregir sin riesgo se arregla con el botón; lo demás te decimos cómo resolverlo.</p></div></div>
<ul class="health-list">
<?php if(isset($health['ciclos'])): ?><li class="bad"><strong>Hay <?=count($health['ciclos'])?> ciclos abiertos al mismo tiempo.</strong> Solo debe haber uno. <?php foreach($health['ciclos'] as $c): ?><div class="health-row"><span><?=e($c['nombre'])?> · <?=e($c['estado'])?><?=$c['notas']?' · '.e($c['notas']).' notas':''?></span><?php if($c['estado']==='PLANEADO'&&!$c['notas']&&!$c['asignaciones'])actionButton('cycle_delete','Eliminar este ciclo planeado',['ciclo'=>$c['id_ciclo']],'¿Eliminar el ciclo planeado '.$c['nombre'].'? No tiene calificaciones ni asignaciones.');elseif($c['estado']==='PLANEADO')echo '<span class="help">Tiene información asociada; revísalo antes de eliminarlo.</span>'; ?></div><?php endforeach; ?></li><?php endif; ?>
<?php if(isset($health['nombres'])): ?><li><strong><?=e($health['nombres'])?> inscripciones tienen el nombre del grupo escrito distinto</strong> (por ejemplo "1 A" en lugar de "1° A"). Se corrige con el botón de abajo.</li><?php endif; ?>
<?php if(isset($health['sincronizar'])): ?><li><strong><?=e($health['sincronizar'])?> alumnos tienen un grupo distinto al de su inscripción del ciclo activo.</strong> Se corrige con el botón de abajo.</li><?php endif; ?>
<?php if(isset($health['sin_inscripcion'])): ?><li><strong><?=count($health['sin_inscripcion'])?> alumnos activos no están inscritos en el ciclo activo:</strong> <?=e(implode(', ',array_slice(array_column($health['sin_inscripcion'],'nombre'),0,8)))?><?=count($health['sin_inscripcion'])>8?'…':''?>. Inscríbelos en <a href="<?=e(viewUrl('matriculas'))?>">Inscripciones</a> o dalos de baja si ya no asisten.</li><?php endif; ?>
<?php if(isset($health['telefonos'])): ?><li><strong>Teléfonos repetidos entre familias:</strong> <?php foreach($health['telefonos'] as $tel=>$who): ?><span class="health-chip"><?=e($tel)?>: <?=e(implode(' y ',$who))?></span><?php endforeach; ?> Pide a esas familias su número correcto.</li><?php endif; ?>
<?php if(isset($health['prueba'])): ?><li class="bad"><strong><?=count($health['prueba'])?> registros parecen datos de prueba</strong> (por ejemplo “Prueba 1”, “Mamá”, “test”). Revísalos y corrígelos o dalos de baja: <?php foreach(array_slice($health['prueba'],0,30) as $f): ?><span class="health-chip"><?=e($f['tipo'])?>: <?=e($f['nombre'])?></span><?php endforeach; ?><?=count($health['prueba'])>30?' …':''?></li><?php endif; ?>
<?php if(isset($health['telefonos_formato'])): ?><li><strong>Teléfonos que no tienen 10 dígitos:</strong> <?php foreach($health['telefonos_formato'] as $tel=>$who): ?><span class="health-chip"><?=e($tel?:'(vacío)')?>: <?=e(implode(', ',$who))?></span><?php endforeach; ?></li><?php endif; ?>
</ul>
<?php if(isset($health['nombres'])||isset($health['sincronizar'])){formStart('data_repair',['data-confirm'=>'¿Corregir los nombres de grupo y el grupo actual de los alumnos? No se borra nada.']);saveFooter('Corregir automáticamente','primary');} ?></section>
<?php endif; ?>
<?php if(!$canCreateCycle): ?><div class="notice"><strong>Un ciclo sigue abierto o planeado.</strong> Ciérralo antes de crear el siguiente. Mi Aula Digital no crea ni apertura dos ciclos al mismo tiempo.</div><?php endif; ?>
<?php if($selected):$review=cycleReview((int)$selected['id_ciclo']); ?>
<section class="card glass"><div class="card-header"><h2>Revisión del ciclo</h2></div><?php filterBar('ciclos',$selected); ?><p><strong><?=e($selected['nombre'])?></strong> · <?=e($selected['estado'])?> · <?=e($selected['periodos'].' '.$selected['tipo_periodo'].'s')?></p><div class="notice"><?=count($review['students'])?> alumnos activos inscritos · <?=$review['pending']?> notas pendientes · <?=$review['unknown']?> grupos por confirmar.</div><?php if($review['students']){$rg=[];foreach($review['students'] as $a)$rg[$a['grupo_nombre']]=$a['grupo_nombre'];ksort($rg,SORT_NATURAL);listFilter('review-table',['grupo'=>['Grupo',$rg],'pend'=>['Notas',['si'=>'Con pendientes','no'=>'Completas']]],'Buscar alumno…');} ?><div class="table-wrap"><table id="review-table"><thead><tr><th>Alumno</th><th>Grupo</th><th>Notas pendientes</th></tr></thead><tbody><?php foreach($review['students'] as $a): ?><tr<?=filterAttrs(['grupo'=>$a['grupo_nombre'],'pend'=>$a['faltantes']>0?'si':'no'])?>><td><?=e($a['nombre_completo'])?></td><td><?=e($a['grupo_nombre'])?></td><td><?=e($a['faltantes'])?></td></tr><?php endforeach; ?></tbody></table></div><?php formStart('cycle_transition',['data-confirm'=>'¿Confirmar el cambio de estado de este ciclo?']);echo '<input type="hidden" name="ciclo" value="'.e($selected['id_ciclo']).'">';selectField('destino','Cambiar estado',$selected['estado']==='ACTIVO'?['CERRADO'=>'Cerrar ciclo']:['ACTIVO'=>$selected['estado']==='CERRADO'?'Reabrir ciclo':'Aperturar ciclo']); ?><label class="field"><span>Motivo de la operación <?=$selected['estado']==='CERRADO'?'(obligatorio para reabrir)':'(opcional)'?></span><textarea name="motivo" maxlength="2000" rows="3" <?=$selected['estado']==='CERRADO'?'required':''?>></textarea></label><?php saveFooter($selected['estado']==='ACTIVO'?'Cerrar ciclo':'Confirmar apertura',$selected['estado']==='ACTIVO'?'danger':'success'); ?></section>
<?php endif; ?><section class="card glass"><h2>Catálogo de ciclos</h2><p class="help">Un ciclo <strong>PLANEADO</strong> puede configurarse o eliminarse mientras todavía no tenga información asociada. Un ciclo <strong>ACTIVO</strong> puede cerrarse incluso antes de su fecha final cuando Dirección lo decida y la revisión esté completa. Un ciclo <strong>CERRADO</strong> queda protegido.</p><div class="table-wrap"><table><thead><tr><th>Ciclo</th><th>Periodos</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach(cycles() as $c):$data=['id'=>$c['id_ciclo'],'nombre'=>$c['nombre'],'fecha_inicio'=>$c['fecha_inicio'],'fecha_fin'=>$c['fecha_fin'],'periodos'=>$c['periodos'],'tipo_periodo'=>$c['tipo_periodo']];foreach(cycleFields((int)$c['id_ciclo']) as $f)$data['peso_'.$f['ID_Campo']]=$f['peso']; ?><tr><td><strong><?=e($c['nombre'])?></strong><small class="table-subtext"><?=e($c['fecha_inicio'].' → '.$c['fecha_fin'])?></small></td><td><?=e($c['periodos'].' '.$c['tipo_periodo'].'s')?></td><td><?=e($c['estado'])?></td><td class="buttons"><?php if($c['estado']!=='CERRADO') echo openButton('cycle-modal','Configurar',$data); ?><?php if($c['estado']==='PLANEADO'){ actionButton('cycle_delete','Eliminar',['ciclo'=>$c['id_ciclo']],'¿Eliminar este ciclo planeado? Solo se permite si todavía no tiene inscripciones ni calificaciones.'); } elseif($c['estado']==='CERRADO'){echo '<span class="help">Historial protegido</span>';} ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php elseif($view==='matriculas'):
 $teachersOpt=[];foreach(rows('SELECT d.ID_Docente,u.Nombre FROM docente d JOIN usuario u ON u.ID_Usuario=d.ID_Usuario WHERE u.activo=1 ORDER BY u.Nombre') as $d)$teachersOpt[$d['ID_Docente']]=$d['Nombre'];
 $fieldOpt=[0=>'Todos los campos'];foreach(rows('SELECT * FROM campo_formativo ORDER BY ID_Campo')as $f)$fieldOpt[$f['ID_Campo']]=$f['Nombre_Campo'];
 $allCycles=cycles();$plannedCycles=array_values(array_filter($allCycles,fn($c)=>$c['estado']==='PLANEADO'));
 $dupes=$cycle?duplicateEnrollments((int)$cycle['id_ciclo']):[]; ?>
<?php if($dupes): ?><section class="card glass"><div class="notice"><strong>Atención: <?=count($dupes)?> alumno(s) aparecen en más de un grupo en el ciclo <?=e($cycle['nombre'])?>.</strong><br>Un alumno solo puede estar en un grupo por ciclo. Elige con cuál grupo se queda.</div><div class="table-wrap"><table><thead><tr><th>Alumno</th><th>Grupos</th><th>Dejar solo en</th></tr></thead><tbody><?php foreach($dupes as $d): ?><tr><td><?=e($d['nombre'])?></td><td><?=e($d['grupos'])?></td><td><div class="buttons"><?php foreach(rows('SELECT ID_Grupo,grupo_nombre FROM inscripcion WHERE ID_Alumno=? AND id_ciclo=? AND activo=1 AND ID_Grupo IS NOT NULL',[(int)$d['ID_Alumno'],(int)$cycle['id_ciclo']]) as $opt)actionButton('enroll_fix',$opt['grupo_nombre'],['alumno'=>$d['ID_Alumno'],'ciclo'=>$cycle['id_ciclo'],'grupo'=>$opt['ID_Grupo']],'¿Dejar al alumno solo en '.$opt['grupo_nombre'].'?'); ?></div></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<div class="detail-grid"><section class="card glass"><h2>Inscribir a un alumno</h2><p class="help">Para un alumno nuevo o que todavía no tiene grupo. <strong>Con el ciclo activo no se puede cambiar a un alumno de grupo</strong>; los cambios de grupo se hacen en el ciclo siguiente (planeado).</p><?php formStart('enroll_save');studentPicker('enroll-student');selectField('ciclo','Ciclo',cycleOptions(true));selectField('grupo','Grupo',groupOptions($u,false));saveFooter('Guardar inscripción'); ?></section>
<section class="card glass" id="asignar-docente"><h2>Asignar docente por campo</h2><p class="help">Usa <strong>Todos los campos</strong> cuando el mismo docente se hace cargo de toda la formación del grupo.</p><?php formStart('assignment_save');selectField('docente','Docente',$teachersOpt);selectField('ciclo','Ciclo',cycleOptions(true));selectField('grupo','Grupo',groupOptions($u,false));selectField('campo','Campo formativo',$fieldOpt);saveFooter('Asignar docente'); ?></section></div>
<section class="card glass" id="mover-grupo"><div class="card-header"><div><h2>Mover grupo completo</h2><p>Pasa a todos los alumnos de un grupo a otro en el ciclo siguiente (por ejemplo, de 1° A a 2° A, o de 4° a 5°).</p></div></div>
<?php if(!$plannedCycles): ?><div class="notice"><strong>Primero crea el ciclo siguiente.</strong><br>Solo se puede mover alumnos hacia un ciclo <strong>planeado</strong>. Con el ciclo activo no se permite mover alumnos de grupo. Ve a <a href="<?=e(viewUrl('ciclos'))?>">Ciclos escolares</a> y crea el nuevo ciclo; después regresa aquí.</div>
<?php else:
 $mmGroups=[];foreach(rows('SELECT * FROM grupo ORDER BY Grado,Grupo') as $g)$mmGroups[]=['id'=>(int)$g['ID_Grupo'],'grado'=>(int)$g['Grado'],'letra'=>$g['Grupo'],'label'=>$g['Grado'].'° '.$g['Grupo']];
 $mmEnroll=[];foreach(rows("SELECT i.id_ciclo,i.ID_Grupo,a.ID_Alumno,CONCAT_WS(' ',a.Primer_Apellido,a.Segundo_Apellido,a.Nombre) nombre FROM inscripcion i JOIN alumno a ON a.ID_Alumno=i.ID_Alumno JOIN ciclo_escolar c ON c.id_ciclo=i.id_ciclo WHERE i.activo=1 AND a.activo=1 AND i.ID_Grupo IS NOT NULL ORDER BY a.Primer_Apellido,a.Segundo_Apellido,a.Nombre") as $r)$mmEnroll[$r['id_ciclo'].':'.$r['ID_Grupo']][]=[(int)$r['ID_Alumno'],$r['nombre']];
 $originCycles=[];foreach(array_merge(array_filter($allCycles,fn($c)=>$c['estado']!=='PLANEADO'),$plannedCycles) as $c)$originCycles[$c['id_ciclo']]=$c['nombre'].' · '.$c['estado'];
 $destCycles=[];foreach($plannedCycles as $c)$destCycles[$c['id_ciclo']]=$c['nombre'].' · PLANEADO';
 $groupOpts=groupOptions($u,false);
 formStart('mass_move',['data-mass-move'=>'true','data-confirm'=>'¿Mover a los alumnos seleccionados al grupo de destino?']); ?>
 <script type="application/json"><?=json_encode(['groups'=>$mmGroups,'enroll'=>$mmEnroll],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP)?></script>
 <div class="mm-grid"><?php selectField('ciclo_origen','1. Ciclo de origen',$originCycles);selectField('grupo_origen','2. Grupo de origen',$groupOpts);selectField('ciclo_destino','3. Ciclo de destino',$destCycles);selectField('grupo_destino','4. Grupo de destino',$groupOpts); ?></div>
 <label class="mm-all"><input type="checkbox" data-mm-all checked> Seleccionar a todos</label>
 <div class="mm-list" data-mm-list></div>
 <p class="help mm-summary" data-mm-summary role="status"></p>
 <?php saveFooter('Mover alumnos seleccionados'); endif; ?></section>
<section class="card glass"><h2>Asignaciones por ciclo</h2><?php if($cycle)filterBar('matriculas',$cycle);$assignments=rows('SELECT ac.*,u.Nombre,g.Grado,g.Grupo,f.Nombre_Campo FROM asignacion_ciclo ac JOIN docente d ON d.ID_Docente=ac.ID_Docente JOIN usuario u ON u.ID_Usuario=d.ID_Usuario JOIN grupo g ON g.ID_Grupo=ac.ID_Grupo JOIN campo_formativo f ON f.ID_Campo=ac.ID_Campo WHERE ac.id_ciclo=? ORDER BY u.Nombre,g.Grado,g.Grupo,f.ID_Campo LIMIT 2000',[(int)($cycle['id_ciclo']??0)]);
 if($assignments){$aTeachers=[];$aGroups=[];foreach($assignments as $a){$aTeachers[(string)$a['ID_Docente']]=$a['Nombre'];$aGroups[(string)$a['ID_Grupo']]=$a['Grado'].'° '.$a['Grupo'];}asort($aGroups,SORT_NATURAL);listFilter('assignments-table',['docente'=>['Docente',$aTeachers],'grupo'=>['Grupo',$aGroups]],'Buscar docente, grupo o campo…');} ?><?php if(!$assignments): emptyState('Sin asignaciones en este ciclo','Usa “Asignar docente por campo” para empezar.'); else: ?><div class="table-wrap"><table id="assignments-table"><thead><tr><th>Docente</th><th>Grupo</th><th>Campo</th><th>Acción</th></tr></thead><tbody><?php foreach($assignments as $a): ?><tr<?=filterAttrs(['docente'=>(string)$a['ID_Docente'],'grupo'=>(string)$a['ID_Grupo']])?>><td><?=e($a['Nombre'])?></td><td><?=e($a['Grado'].'° '.$a['Grupo'])?></td><td><?=e($a['Nombre_Campo'])?></td><td><?php actionButton('assignment_remove','Retirar',['docente'=>$a['ID_Docente'],'ciclo'=>$a['id_ciclo'],'grupo'=>$a['ID_Grupo'],'campo'=>$a['ID_Campo']],'¿Retirar esta asignación?'); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php elseif($view==='familias'):
 invitationEnsureSchema();
 $students=studentsWithGroup();
 $tutors=rows("SELECT p.*,u.Usuario,u.activo,(SELECT COUNT(*) FROM alumno_tutor at WHERE at.ID_Padre=p.ID_Padre) hijos,(SELECT MAX(i.usado) FROM invitacion i WHERE i.ID_Usuario=u.ID_Usuario) aceptada,(SELECT MAX(i.vence) FROM invitacion i WHERE i.ID_Usuario=u.ID_Usuario AND i.usado IS NULL) invitada FROM padre_tutor p JOIN usuario u ON u.ID_Usuario=p.ID_Usuario ORDER BY p.Nombre_Completo"); ?>
<div class="detail-grid">
<section class="card glass"><h2>Registrar padre o tutor</h2><p class="help">Dirección da de alta a la familia y le envía una invitación por correo electrónico. La persona abre el enlace y crea su propia contraseña; Dirección nunca la conoce.</p>
<?php formStart('parent_register',['data-confirm'=>'¿Registrar a esta persona y generar su invitación?']); ?><div class="form-grid"><?php field('nombre','Nombre(s)','text','',true,50);field('primer_apellido','Primer apellido','text','',true,50);field('segundo_apellido','Segundo apellido','text','',false,50);field('telefono','Teléfono celular','tel','',true,20,'10 dígitos. No puede repetirse con otra familia.');field('correo','Correo electrónico','email','',true,100);field('usuario','Usuario (opcional)','text','',false,50,'Si lo dejas vacío, se crea uno con su nombre.'); ?></div>
<p class="help"><strong>Opcional:</strong> vincula de una vez a su hijo o hija.</p><?php studentPicker('register-student','alumno','Hijo o hija',false,$students);saveFooter('Registrar y crear invitación'); ?></section>
<section class="card glass"><h2>Autorizar una cuenta familiar</h2><p class="help">Un alumno puede tener varios tutores autorizados. Busca primero el grupo y después el nombre.</p><?php formStart('family_add',['data-confirm'=>'¿Autorizar a esta persona para consultar al alumno?']);studentPicker('link-student','alumno','Alumno',true,$students); ?><label class="field"><span>Buscar tutor <small class="help" data-options-count="link-tutor"></small></span><input type="search" data-options-text="link-tutor" placeholder="Nombre, correo o teléfono" autocomplete="off"></label><label class="field"><span>Tutor</span><select id="link-tutor" name="padre" required data-options-filterable><option value="">Selecciona un tutor</option><?php foreach($tutors as $t): ?><option value="<?=e($t['ID_Padre'])?>"><?=e($t['Nombre_Completo'].' · '.$t['Correo'].($t['Telefono']?' · '.$t['Telefono']:''))?></option><?php endforeach; ?></select></label><?php saveFooter('Autorizar vínculo'); ?></section>
</div>
<section class="card glass"><h2>Padres y tutores</h2><?php listFilter('tutors-table',['estado'=>['Cuenta',['activa'=>'Aceptó la invitación','invitada'=>'Invitación pendiente','vencida'=>'Invitación vencida','propia'=>'Se registró solo']],'hijos'=>['Hijos',['0'=>'Sin hijos vinculados','1'=>'Con hijos vinculados']]],'Buscar por nombre, correo o teléfono…'); ?><div class="table-wrap"><table id="tutors-table"><thead><tr><th>Padre / tutor</th><th>Teléfono</th><th>Hijos</th><th>Cuenta</th><th>Acciones</th></tr></thead><tbody><?php foreach($tutors as $t):$state=$t['aceptada']?'activa':($t['invitada']?(strtotime((string)$t['invitada'])>time()?'invitada':'vencida'):'propia'); ?><tr<?=filterAttrs(['estado'=>$state,'hijos'=>(int)$t['hijos']>0?'1':'0'])?>><td><strong><?=e($t['Nombre_Completo'])?></strong><small><?=e($t['Correo'].' · '.$t['Usuario'])?></small></td><td><?=e($t['Telefono']?:'—')?></td><td><?=e($t['hijos'])?></td><td><?=$state==='activa'?badge('Contraseña creada','green'):($state==='invitada'?badge('Invitación pendiente','orange'):($state==='vencida'?badge('Invitación vencida','red'):badge('Cuenta activa','green')))?></td><td><?php if($t['activo']&&in_array($state,['invitada','vencida'],true))actionButton('invite_resend','Reenviar invitación',['usuario'=>$t['ID_Usuario']],'Se generará un enlace nuevo y el anterior dejará de funcionar. ¿Continuar?');else echo '<span class="help">Sin pendientes</span>'; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="card glass"><h2>Vínculos autorizados</h2><?php $links=rows("SELECT at.*,CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) alumno,p.Nombre_Completo,COALESCE(i.ID_Grupo,a.ID_Grupo) grupo_id,COALESCE(CONCAT(gi.Grado,'° ',gi.Grupo),i.grupo_nombre,CONCAT(g.Grado,'° ',g.Grupo),'Sin grupo') grupo FROM alumno_tutor at JOIN alumno a ON a.ID_Alumno=at.ID_Alumno JOIN padre_tutor p ON p.ID_Padre=at.ID_Padre LEFT JOIN inscripcion i ON i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? AND i.activo=1 LEFT JOIN grupo gi ON gi.ID_Grupo=i.ID_Grupo LEFT JOIN grupo g ON g.ID_Grupo=a.ID_Grupo ORDER BY a.Primer_Apellido,a.Segundo_Apellido,a.Nombre LIMIT 3000",[activeCycleId()]);
 if($links)listFilter('links-table',['grupo'=>['Grupo del alumno',groupFilterOptions()]],'Buscar alumno o tutor…'); ?><?php if(!$links): emptyState('Todavía no hay vínculos','Autoriza el primer vínculo con el formulario de arriba.'); else: ?><div class="table-wrap"><table id="links-table"><thead><tr><th>Alumno</th><th>Grupo</th><th>Tutor</th><th>Acción</th></tr></thead><tbody><?php foreach($links as $l): ?><tr<?=filterAttrs(['grupo'=>(string)$l['grupo_id']])?>><td><?=e($l['alumno'])?></td><td><?=e($l['grupo'])?></td><td><?=e($l['Nombre_Completo'])?></td><td><?php actionButton('family_remove','Revocar',['alumno'=>$l['ID_Alumno'],'padre'=>$l['ID_Padre']],'¿Revocar este acceso familiar?'); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php elseif($view==='expediente'):
try{$id=(int)($_GET['alumno']??0);$a=studentAccess($u,$id,$cycle?(int)$cycle['id_ciclo']:null,true);$history=rows('SELECT i.*,c.nombre,c.estado,c.periodos FROM inscripcion i JOIN ciclo_escolar c ON c.id_ciclo=i.id_ciclo WHERE i.ID_Alumno=? ORDER BY c.fecha_inicio DESC',[$id]);}catch(Throwable $ex){emptyState('Expediente no disponible.');return;} ?>
<section class="card glass"><h2><?=e(trim($a['Nombre'].' '.$a['Primer_Apellido'].' '.$a['Segundo_Apellido']))?></h2><p><?=e($a['CURP'])?> · <?=$a['activo']?'Activo':'Baja · expediente conservado'?></p></section>
<?php foreach($history as $h):try{studentAccess($u,$id,(int)$h['id_ciclo'],true);$summary=studentCycleSummary($id,(int)$h['id_ciclo']);}catch(DomainException){continue;}$fs=$summary['fields']; ?><section class="card glass"><div class="card-header"><div><h2><?=e($h['nombre'].' · '.$h['grupo_nombre'])?></h2><p class="help"><?=e($h['estado'])?> · <?=$h['verificado']?'Grupo registrado':'Grupo heredado pendiente de confirmar'?></p></div><div class="buttons"><a class="button small" href="<?=e(url('process/export.php?reporte=historial&tipo=xlsx&ciclo='.$h['id_ciclo'].'&alumno='.$id))?>">Excel histórico</a><a class="button small" href="<?=e(url('process/export.php?reporte=historial&tipo=pdf&ciclo='.$h['id_ciclo'].'&alumno='.$id))?>">PDF histórico</a></div></div><div class="table-wrap"><table><thead><tr><th><?=e($summary['cycle']['tipo_periodo'])?></th><?php foreach($fs as $f): ?><th><?=e($f['Nombre_Campo'])?></th><?php endforeach; ?><th>Promedio</th></tr></thead><tbody><?php foreach($summary['periods'] as $n=>$period): ?><tr><td><?=e($summary['cycle']['tipo_periodo'].' '.$n)?></td><?php foreach($fs as $f): ?><td><?=e($period['grades'][$f['ID_Campo']]['Calificacion_Numerica']??'—')?></td><?php endforeach; ?><td><?=e($period['average']['value']===null?'—':number_format((float)$period['average']['value'],1))?><?=$period['average']['complete']?'':' · Parcial'?></td></tr><?php endforeach; ?><tr><td colspan="<?=count($fs)+1?>"><strong>PROMEDIO FINAL DEL CICLO</strong></td><td><strong><?=e($summary['final']===null?'—':number_format((float)$summary['final'],1))?></strong><?=$summary['complete']?'':' · Parcial'?></td></tr></tbody></table></div></section><?php endforeach; ?>
<?php elseif($view==='seguimiento'):
$id=(int)($_GET['id']??0);$incident=one('SELECT * FROM incidencia WHERE ID_Incidencia=?',[$id]);try{if(!$incident)throw new DomainException();studentAccess($u,(int)$incident['ID_Alumno'],activeCycleId(),true);}catch(Throwable){emptyState('Incidencia no disponible.');return;} ?>
<section class="card glass"><h2><?=e($incident['Tipo_Incidencia'])?></h2><p><?=e($incident['Descripcion'])?></p><p><?=e($incident['Estado'])?> · <?=e($incident['Fecha'])?></p><?php $responsible=$incident['responsable']?one('SELECT Nombre FROM usuario WHERE ID_Usuario=?',[(int)$incident['responsable']]):null; ?><p class="help">Responsable: <?=e($responsible['Nombre']??'Por asignar')?> · Resolución: <?=e($incident['fecha_resolucion']??'Pendiente')?></p><?php foreach(rows('SELECT s.*,u.Nombre FROM incidencia_seguimiento s JOIN usuario u ON u.ID_Usuario=s.ID_Usuario WHERE ID_Incidencia=? ORDER BY s.fecha,s.id',[$id])as $item): ?><article class="timeline-item"><strong><?=e($item['Nombre'])?></strong><small><?=e($item['fecha'])?></small><p><?=e($item['comentario'])?></p></article><?php endforeach; ?>
<?php if(!$isTutor){formStart('incident_follow');echo '<input type="hidden" name="id" value="'.$id.'">';selectField('action','Acción',['incident_follow'=>'Agregar seguimiento','incident_resolve'=>'Resolver con explicación']);textareaField('comentario','Acciones realizadas / explicación',5000);$options=[''=>'Conservar responsable'];foreach(rows("SELECT ID_Usuario,Nombre FROM usuario WHERE activo=1 AND Rol IN ('Direccion','Docente') ORDER BY Nombre")as $r)$options[$r['ID_Usuario']]=$r['Nombre'];selectField('responsable','Responsable',$options,false);saveFooter('Guardar seguimiento');} ?></section>
<?php elseif($view==='auditoria'):
$events=rows('SELECT b.*,u.Nombre FROM bitacora b LEFT JOIN usuario u ON u.ID_Usuario=b.ID_Usuario ORDER BY b.id DESC LIMIT 50 OFFSET '.(($page-1)*50));$gradesAudit=rows('SELECT au.*,u.Nombre,c.ID_Alumno,c.Bimestre,ce.nombre ciclo,f.Nombre_Campo FROM auditoria_calificacion au LEFT JOIN usuario u ON u.ID_Usuario=au.ID_Usuario LEFT JOIN calificacion c ON c.ID_Calificacion=au.ID_Calificacion LEFT JOIN ciclo_escolar ce ON ce.id_ciclo=c.id_ciclo LEFT JOIN campo_formativo f ON f.ID_Campo=c.ID_Campo ORDER BY au.id_auditoria DESC LIMIT 50 OFFSET '.(($page-1)*50)); ?>
<section class="card glass"><h2>Cambios de calificaciones</h2><?php if($gradesAudit)listFilter('grades-audit-table',[],'Buscar usuario, ciclo o campo…'); ?><div class="table-wrap"><table id="grades-audit-table"><thead><tr><th>Fecha / usuario</th><th>Alumno / ciclo</th><th>Campo</th><th>Antes → después</th></tr></thead><tbody><?php foreach($gradesAudit as $a): ?><tr><td><?=e($a['fecha'])?><small><?=e($a['Nombre']??'Usuario histórico')?></small></td><td><?=e($a['ID_Alumno'])?><small><?=e($a['ciclo'].' · Periodo '.$a['Bimestre'])?></small></td><td><?=e($a['Nombre_Campo'])?></td><td><?=e($a['valor_anterior']??'Sin captura')?> → <?=e($a['valor_nuevo'])?></td></tr><?php endforeach; ?></tbody></table></div></section><section class="card glass"><div class="card-header"><div><h2>Operaciones administrativas</h2><p>Historial de cambios importantes realizados desde Dirección.</p></div></div><?php if($events)listFilter('events-table',[],'Buscar usuario, acción o detalle…'); ?><div class="table-wrap"><table id="events-table"><thead><tr><th>Fecha / usuario</th><th>Acción</th><th>Resumen</th></tr></thead><tbody><?php foreach($events as $a): $actionUi=auditActionPresentation((string)$a['accion']); ?><tr><td><strong><?=e($a['fecha'])?></strong><small><?=e($a['Nombre']??'Usuario histórico')?></small></td><td><?=badge($actionUi[0],$actionUi[1])?><small><?=e(auditEntityLabel((string)$a['entidad'],$a['referencia']??null))?></small></td><td><?=e(auditDetailText($a))?></td></tr><?php endforeach; ?></tbody></table></div><?php pager('auditoria',$page,max(count($events),count($gradesAudit))); ?></section>
<?php elseif($view==='accesos'):
authEnsureSchema();
$accounts=rows('SELECT ID_Usuario,Nombre,Usuario,Correo,Rol,activo,metodo_acceso,google_sub,correo_verificado FROM usuario ORDER BY Rol,Nombre'); ?>
<section class="card glass"><div class="card-header"><div><h2>Métodos de acceso</h2><p>Define si cada cuenta entra de forma local, con Google o con ambos métodos.</p></div><?=googleAuthConfigured()?badge('Google configurado','green'):badge('Google pendiente','orange')?></div><p class="help">Google solo autentica la identidad. El rol y los permisos siguen siendo los registrados por la escuela. Si habilitas Google en una cuenta aún no vinculada, el primer acceso con el mismo correo verificado realizará la vinculación.</p><?php if(!googleAuthConfigured()): ?><div class="notice"><strong>Para activar Google:</strong> registra las credenciales OAuth en <code>config/database.local.php</code> y usa como URI autorizado <code><?=e(googleRedirectUri())?></code>.</div><?php endif; ?><?php listFilter('accounts-table',['rol'=>['Rol',['Direccion'=>'Dirección','Docente'=>'Docente','PadreTutor'=>'Familia']],'metodo'=>['Acceso',['local'=>'Local','google'=>'Google','ambos'=>'Local + Google']]],'Buscar persona, correo o usuario…'); ?><div class="table-wrap"><table id="accounts-table"><thead><tr><th>Persona</th><th>Rol</th><th>Acceso actual</th><th>Google</th><th>Configurar</th></tr></thead><tbody><?php foreach($accounts as $account): ?><tr<?=filterAttrs(['rol'=>$account['Rol'],'metodo'=>$account['metodo_acceso']])?>><td><strong><?=e($account['Nombre'])?></strong><small><?=e($account['Correo'].' · '.$account['Usuario'])?></small></td><td><?=e(['Direccion'=>'Dirección','Docente'=>'Docente','PadreTutor'=>'Familia'][$account['Rol']]??$account['Rol'])?></td><td><?=badge(authMethodLabel((string)$account['metodo_acceso']))?></td><td><?=!empty($account['google_sub'])?badge('Vinculado','green'):badge('Sin vincular','orange')?></td><td><?php formStart('access_method_save',['data-preserve-scroll'=>'true']);echo '<input type="hidden" name="usuario" value="'.e($account['ID_Usuario']).'">'; ?><div class="inline-access-form"><select name="metodo" aria-label="Método de acceso"><option value="local" <?=$account['metodo_acceso']==='local'?'selected':''?>>Local</option><option value="google" <?=$account['metodo_acceso']==='google'?'selected':''?>>Google</option><option value="ambos" <?=$account['metodo_acceso']==='ambos'?'selected':''?>>Local + Google</option></select><?php if(!empty($account['google_sub'])): ?><label class="check-row compact"><input type="checkbox" name="desvincular_google" value="1"> Desvincular Google</label><?php endif; ?><button type="submit" class="button small primary">Guardar</button></div></form></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php $requests=rows('SELECT r.id,r.solicitado,r.estado,u.Nombre,u.Correo,u.Usuario FROM recuperacion r JOIN usuario u ON u.ID_Usuario=r.ID_Usuario ORDER BY r.id DESC LIMIT 50 OFFSET '.(($page-1)*50)); ?><section class="card glass"><h2>Solicitudes de recuperación</h2><p class="help">Verifica la identidad por el canal habitual de la escuela antes de autorizar. El código se entrega de forma privada, vence en 30 minutos y es de un solo uso.</p><?php foreach($requests as $r): ?><article class="announcement"><h3><?=e($r['Nombre'])?></h3><p><?=e($r['Usuario'].' · '.$r['Correo'].' · '.$r['estado'])?></p><?php if($r['estado']==='Pendiente'){formStart('recovery_issue',['data-stay'=>'true']);echo '<input type="hidden" name="id" value="'.e($r['id']).'">';field('motivo','Cómo verificaste la identidad','text','',true,500);saveFooter('Generar código privado');actionButton('recovery_reject','Cancelar',['id'=>$r['id']],'¿Cancelar solicitud?');} ?></article><?php endforeach;pager('accesos',$page,count($requests)); ?></section>
<?php elseif($view==='respaldo'): ?>
<section class="card glass"><h2>Descargar respaldo de la base de datos</h2><p>Guarda el archivo fuera del directorio público del sitio. Incluye datos escolares privados.</p><form method="post" action="<?=e(url('process/backup.php'))?>"><?=csrf()?><?php field('password','Confirma tu contraseña','password','',true,200); ?><button type="submit" class="button primary">Descargar respaldo SQL</button></form><p class="help">La guía incluye el comando para programar respaldos y la restauración en una base vacía. Dirección debe comprobar periódicamente que el respaldo puede restaurarse.</p></section>

<?php elseif($view==='personalizacion'):
$b=array_replace(brandingDefaults(), schoolBranding(true));$logo=brandingLogoUrl($b);$customLogo=brandingCustomLogoUrl($b);$loginBg=brandingLoginBackgroundUrl($b); ?>
<div class="branding-editor" data-branding-editor>
<div class="branding-editor-main">
<section class="card glass branding-intro branding-intro-v7">
 <div class="branding-intro-copy">
  <div class="overline">Centro de personalización</div>
  <h2>Personaliza tu escuela paso a paso</h2>
  <p>No necesitas saber diseño ni tocar código. Elige una sección, cambia lo que quieras y revisa el resultado en la vista previa.</p>
 </div>
 <div class="branding-howto" aria-label="Cómo usar el editor">
  <span><b>1</b> Elige una sección</span><span><b>2</b> Haz tus cambios</span><span><b>3</b> Guarda el diseño</span>
 </div>
 <div class="branding-presets-block">
  <div><strong>¿Quieres empezar rápido?</strong><small>Aplica una base y después cambia solo lo que necesites.</small></div>
  <div class="theme-presets" aria-label="Temas rápidos">
   <button type="button" class="theme-preset" data-theme-preset="rojo">Rojo institucional</button>
   <button type="button" class="theme-preset" data-theme-preset="azul">Azul académico</button>
   <button type="button" class="theme-preset" data-theme-preset="verde">Verde moderno</button>
   <button type="button" class="theme-preset" data-theme-preset="morado">Morado creativo</button>
   <button type="button" class="theme-preset" data-theme-preset="negro">Negro + dorado</button>
   <button type="button" class="theme-preset" data-theme-preset="claro">Claro minimalista</button>
  </div>
 </div>
</section>

<nav class="branding-workflow" data-branding-workflow aria-label="Secciones de personalización">
 <button type="button" class="branding-workflow-item is-active" data-branding-go="branding-01"><span>01</span><b>Identidad</b><small>Nombre, logo y fondo</small></button>
 <button type="button" class="branding-workflow-item" data-branding-go="branding-02"><span>02</span><b>Acceso</b><small>Portada e inicio de sesión</small></button>
 <button type="button" class="branding-workflow-item" data-branding-go="branding-03"><span>03</span><b>Paneles</b><small>Bienvenida y mensajes</small></button>
 <button type="button" class="branding-workflow-item" data-branding-go="branding-04"><span>04</span><b>Iconos</b><small>Menú de navegación</small></button>
 <button type="button" class="branding-workflow-item" data-branding-go="branding-05"><span>05</span><b>Colores</b><small>Fondos, letras y botones</small></button>
 <button type="button" class="branding-workflow-item" data-branding-go="branding-06"><span>06</span><b>Estilo</b><small>Cristal, tamaños y espacios</small></button>
</nav>

<?php formStart('branding_save',['enctype'=>'multipart/form-data','data-branding-form'=>'true']); ?>
<section id="branding-01" class="card glass branding-section is-active" data-branding-section>
 <div class="branding-section-title"><span class="branding-step">01</span><div><h2>Marca e identidad</h2><p>Configura el nombre, el logo y la imagen que representa a tu escuela.</p></div></div>
 <div class="branding-section-note"><strong>Lo esencial</strong><span>Estos datos aparecen en el acceso, paneles y reportes.</span></div>
 <div class="form-grid">
  <?php field('nombre_sistema','Nombre visible del sistema','text',$b['nombre_sistema'],true,60,'Ejemplo: Mi Aula Digital, Campus Conecta o el nombre comercial.'); ?>
  <?php field('nombre_escuela','Nombre de la escuela','text',$b['nombre_escuela'],false,120,'Aparece en accesos, paneles y reportes.'); ?>
  <?php field('lema','Lema institucional','text',$b['lema'],false,120); ?>
  <?php field('pie','Texto del pie de página','text',$b['pie'],false,180); ?>
 </div>
 <div class="branding-upload-grid">
  <div class="field branding-upload"><span>Logo de la escuela</span><input type="file" name="logo" accept="image/png,image/jpeg,.png,.jpg,.jpeg"><small>PNG o JPG/JPEG · máximo 2 MB. Si no subes uno, se usa el logo predeterminado, que toma el color principal.</small><?php if($customLogo!==''): ?><span class="current-asset"><img src="<?=e($customLogo)?>" alt="Logo actual de la escuela">Logo actual</span><label class="check-row"><input type="checkbox" name="quitar_logo" value="1"> Quitar logo y volver al logo predeterminado</label><?php endif; ?></div>
  <div class="field branding-upload"><span>Imagen de fondo del acceso</span><input type="file" name="fondo_login" accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"><small>Opcional · máximo 4 MB.</small><?php if($loginBg!==''): ?><span class="current-asset wide"><img src="<?=e($loginBg)?>" alt="Fondo actual">Fondo actual</span><label class="check-row"><input type="checkbox" name="quitar_fondo_login" value="1"> Quitar fondo actual</label><?php endif; ?></div>
 </div>
 <label class="field"><span>Posición del fondo de acceso</span><select name="fondo_login_posicion"><option value="center" <?=$b['fondo_login_posicion']==='center'?'selected':''?>>Centro</option><option value="top" <?=$b['fondo_login_posicion']==='top'?'selected':''?>>Arriba</option><option value="bottom" <?=$b['fondo_login_posicion']==='bottom'?'selected':''?>>Abajo</option><option value="left" <?=$b['fondo_login_posicion']==='left'?'selected':''?>>Izquierda</option><option value="right" <?=$b['fondo_login_posicion']==='right'?'selected':''?>>Derecha</option></select></label>
</section>

<section id="branding-02" class="card glass branding-section" data-branding-section>
 <div class="branding-section-title"><span class="branding-step">02</span><div><h2>Pantalla de acceso</h2><p>Cambia los textos que se ven antes de iniciar sesión.</p></div></div>
 <div class="branding-section-note"><strong>Vista previa a la derecha</strong><span>Los cambios aparecen al instante; todavía no se publican hasta guardar.</span></div>
 <div class="form-grid">
  <label class="field full"><span>Título grande</span><textarea name="login_titulo" maxlength="180" rows="3" required><?=e($b['login_titulo'])?></textarea><small>Usa saltos de línea si quieres separar frases.</small></label>
  <label class="field full"><span>Descripción principal</span><textarea name="login_texto" maxlength="420" rows="3" required><?=e($b['login_texto'])?></textarea></label>
  <?php field('login_tarjeta_titulo','Título del formulario','text',$b['login_tarjeta_titulo'],true,100); ?>
  <?php field('login_tarjeta_texto','Texto del formulario','text',$b['login_tarjeta_texto'],true,220); ?>
  <?php field('login_roles','Texto inferior','text',$b['login_roles'],true,120,'Ejemplo: DIRECCIÓN · DOCENTES · FAMILIAS'); ?>
 </div>
</section>

<section id="branding-03" class="card glass branding-section" data-branding-section>
 <div class="branding-section-title"><span class="branding-step">03</span><div><h2>Bienvenida de los paneles</h2><p>Personaliza lo que ven Dirección, Docentes y Familias al entrar.</p></div></div>
 <div class="form-grid">
  <?php field('menu_etiqueta','Etiqueta del menú','text',$b['menu_etiqueta'],true,40); ?>
  <?php field('panel_titulo','Título del inicio','text',$b['panel_titulo'],true,100); ?>
  <?php field('panel_subtitulo','Subtítulo del inicio','text',$b['panel_subtitulo'],true,220); ?>
  <?php field('hero_etiqueta','Etiqueta de bienvenida','text',$b['hero_etiqueta'],true,80); ?>
  <label class="field full"><span>Título de bienvenida</span><textarea name="hero_titulo" maxlength="180" rows="2" required><?=e($b['hero_titulo'])?></textarea><small>Usa <strong>{nombre}</strong> para mostrar el nombre de la persona.</small></label>
  <label class="field full"><span>Mensaje para Dirección</span><textarea name="hero_direccion" maxlength="420" rows="3" required><?=e($b['hero_direccion'])?></textarea></label>
  <label class="field full"><span>Mensaje para Docentes</span><textarea name="hero_docente" maxlength="420" rows="3" required><?=e($b['hero_docente'])?></textarea></label>
  <label class="field full"><span>Mensaje para Familias</span><textarea name="hero_familia" maxlength="420" rows="3" required><?=e($b['hero_familia'])?></textarea></label>
  <?php field('panel_lista_titulo','Título de la lista principal','text',$b['panel_lista_titulo'],true,100); ?>
  <?php field('panel_lista_texto','Texto de la lista principal','text',$b['panel_lista_texto'],true,180); ?>
  <?php field('panel_avisos_titulo','Título de anuncios recientes','text',$b['panel_avisos_titulo'],true,100); ?>
  <?php field('panel_avisos_vacio','Título cuando no hay anuncios','text',$b['panel_avisos_vacio'],true,100); ?>
  <?php field('panel_avisos_vacio_txt','Texto cuando no hay anuncios','text',$b['panel_avisos_vacio_txt'],true,220); ?>
 </div>
</section>

<section id="branding-04" class="card glass branding-section" data-branding-section>
 <div class="branding-section-title"><span class="branding-step">04</span><div><h2>Iconos del menú</h2><p>Escoge un icono para cada opción sin escribir nombres ni código manualmente.</p></div></div>
 <div class="form-grid icon-picker-grid">
  <?php foreach([
   'icon_panel'=>'Inicio','icon_alumnos'=>'Alumnos / Mis hijos','icon_docentes'=>'Docentes','icon_solicitud'=>'Solicitudes','icon_asignaciones'=>'Grupos','icon_calificaciones'=>'Calificaciones','icon_incidencias'=>'Incidencias','icon_avisos'=>'Anuncios','icon_matriculas'=>'Inscripciones','icon_familias'=>'Familias','icon_auditoria'=>'Auditoría','icon_accesos'=>'Accesos','icon_respaldo'=>'Respaldos','icon_tokens'=>'Vinculación','icon_ciclos'=>'Ciclos escolares','icon_personalizacion'=>'Personalización'
  ] as $key=>$label): ?>
  <label class="field icon-picker"><span><?=e($label)?></span><div class="icon-picker-control"><span class="icon-picker-current"><?=icon((string)$b[$key])?></span><select name="<?=e($key)?>"><?php foreach(['home'=>'Inicio','users'=>'Personas','book'=>'Libro','chart'=>'Gráfica','shield'=>'Escudo','bell'=>'Campana','key'=>'Llave','calendar'=>'Calendario','download'=>'Descarga','school'=>'Escuela','palette'=>'Paleta'] as $value=>$title): ?><option value="<?=e($value)?>" <?=$b[$key]===$value?'selected':''?>><?=e($title)?></option><?php endforeach; ?></select></div></label>
  <?php endforeach; ?>
 </div>
</section>

<section id="branding-05" class="card glass branding-section" data-branding-section>
 <div class="branding-section-title"><span class="branding-step">05</span><div><h2>Colores y letras</h2><p>Ajusta cada zona por separado para evitar letras invisibles sobre fondos claros u oscuros.</p></div></div>
 <input type="hidden" name="modo_texto" value="manual">

 <div class="branding-color-group branding-text-control">
  <div class="branding-group-head"><div><h3>Colores de texto por zona</h3><p>Ya no existe el modo “un solo color para todas las letras”. Así cada fondo conserva un texto legible y tú sigues eligiendo los colores.</p></div></div>
  <div class="branding-color-grid extended branding-zone-text-grid">
   <?php foreach(['color_texto'=>'Texto general y tarjetas','color_sidebar_texto'=>'Texto del menú','color_hero_texto'=>'Texto de bienvenida','color_anuncio_texto'=>'Texto de anuncios'] as $key=>$label): ?><label class="field color-field"><span><?=e($label)?></span><div><input type="color" name="<?=e($key)?>" value="<?=e((string)$b[$key])?>"><input class="color-hex-input" type="text" value="<?=e((string)$b[$key])?>" data-color-text="<?=e($key)?>" maxlength="7" inputmode="text" aria-label="Código hexadecimal de <?=e($label)?>" pattern="^#[0-9A-Fa-f]{6}$"></div></label><?php endforeach; ?>
  </div>
 </div>

 <div class="branding-color-group">
  <div class="branding-group-head"><div><h3>Fondos principales</h3><p>Cambia el fondo general, tarjetas, menú, bienvenida y anuncios.</p></div></div>
  <div class="branding-color-grid extended">
   <?php foreach(['color_fondo'=>'Fondo general','color_superficie'=>'Tarjetas / cristal','color_sidebar'=>'Fondo del menú','color_hero'=>'Fondo de bienvenida','color_anuncio'=>'Fondo de anuncios'] as $key=>$label): ?><label class="field color-field"><span><?=e($label)?></span><div><input type="color" name="<?=e($key)?>" value="<?=e((string)$b[$key])?>"><input class="color-hex-input" type="text" value="<?=e((string)$b[$key])?>" data-color-text="<?=e($key)?>" maxlength="7" inputmode="text" aria-label="Código hexadecimal de <?=e($label)?>" pattern="^#[0-9A-Fa-f]{6}$"></div></label><?php endforeach; ?>
  </div>
 </div>

 <div class="branding-color-group">
  <div class="branding-group-head"><div><h3>Marca y botones</h3><p>Define el color principal y los estados. Los efectos al pasar el cursor conservarán este mismo color.</p></div></div>
  <div class="branding-color-grid extended">
   <?php foreach(['color_principal'=>'Principal / acciones','color_secundario'=>'Secundario / detalles','color_exito'=>'Guardar / éxito','color_advertencia'=>'Advertencias / tareas','color_peligro'=>'Eliminar / cerrar sesión','color_info'=>'Información'] as $key=>$label): ?><label class="field color-field"><span><?=e($label)?></span><div><input type="color" name="<?=e($key)?>" value="<?=e((string)$b[$key])?>"><input class="color-hex-input" type="text" value="<?=e((string)$b[$key])?>" data-color-text="<?=e($key)?>" maxlength="7" inputmode="text" aria-label="Código hexadecimal de <?=e($label)?>" pattern="^#[0-9A-Fa-f]{6}$"></div></label><?php endforeach; ?>
  </div>
 </div>
 <div class="contrast-status" data-contrast-status role="status">El editor te avisará si una combinación cuesta leer; nunca cambiará el color que elegiste.</div>
</section>

<section id="branding-06" class="card glass branding-section" data-branding-section>
 <div class="branding-section-title"><span class="branding-step">06</span><div><h2>Estilo, tamaños y Liquid Glass</h2><p>Ajusta la apariencia general con controles simples. Puedes ver cada cambio a la derecha.</p></div></div>
 <div class="branding-control-grid">
  <label class="field"><span>Redondeo de tarjetas</span><input type="range" name="radio_tarjetas" min="12" max="40" value="<?=e($b['radio_tarjetas'])?>" data-range-output="radio_tarjetas"><small><output data-range-value="radio_tarjetas"><?=e($b['radio_tarjetas'])?></output> px</small></label>
  <label class="field"><span>Redondeo de botones</span><input type="range" name="radio_botones" min="6" max="28" value="<?=e($b['radio_botones'])?>" data-range-output="radio_botones"><small><output data-range-value="radio_botones"><?=e($b['radio_botones'])?></output> px</small></label>
  <label class="field"><span>Desenfoque del cristal</span><input type="range" name="desenfoque" min="0" max="48" value="<?=e($b['desenfoque'])?>" data-range-output="desenfoque"><small><output data-range-value="desenfoque"><?=e($b['desenfoque'])?></output> px</small></label>
  <label class="field"><span>Opacidad del cristal</span><input type="range" name="opacidad_cristal" min="38" max="100" value="<?=e($b['opacidad_cristal'])?>" data-range-output="opacidad_cristal"><small><output data-range-value="opacidad_cristal"><?=e($b['opacidad_cristal'])?></output>%</small></label>
  <label class="field"><span>Intensidad de sombra</span><input type="range" name="intensidad_sombra" min="0" max="30" value="<?=e($b['intensidad_sombra'])?>" data-range-output="intensidad_sombra"><small><output data-range-value="intensidad_sombra"><?=e($b['intensidad_sombra'])?></output>/30</small></label>
  <label class="field"><span>Tamaño general de letra</span><input type="range" name="escala_fuente" min="90" max="108" value="<?=e($b['escala_fuente'])?>" data-range-output="escala_fuente"><small><output data-range-value="escala_fuente"><?=e($b['escala_fuente'])?></output>%</small></label>
  <label class="field"><span>Ancho del menú lateral</span><input type="range" name="ancho_sidebar" min="220" max="300" value="<?=e($b['ancho_sidebar'])?>" data-range-output="ancho_sidebar"><small><output data-range-value="ancho_sidebar"><?=e($b['ancho_sidebar'])?></output> px</small></label>
  <label class="field"><span>Tipografía</span><select name="tipografia"><option value="sistema" <?=$b['tipografia']==='sistema'?'selected':''?>>Sistema / limpia</option><option value="moderna" <?=$b['tipografia']==='moderna'?'selected':''?>>Moderna</option><option value="redondeada" <?=$b['tipografia']==='redondeada'?'selected':''?>>Redondeada</option><option value="serif" <?=$b['tipografia']==='serif'?'selected':''?>>Serif / académica</option></select><small>Familias seguras para que la interfaz no se rompa.</small></label>
  <label class="field"><span>Densidad</span><select name="densidad"><option value="compacta" <?=$b['densidad']==='compacta'?'selected':''?>>Compacta</option><option value="comoda" <?=$b['densidad']==='comoda'?'selected':''?>>Cómoda</option><option value="amplia" <?=$b['densidad']==='amplia'?'selected':''?>>Amplia</option></select><small>Controla cuánto espacio hay entre elementos.</small></label>
 </div>
</section>

<?php feedback(); ?>
<div class="branding-savebar glass"><div><strong>Cuando termines, guarda una sola vez</strong><small>Los cambios se aplican a Dirección, Docentes, Familias, acceso y reportes sin borrar datos.</small></div><div class="branding-save-actions"><button type="submit" class="button primary">Guardar todos mis cambios</button></div></div>
</form>
<div class="branding-reset"><div><strong>¿Quieres volver al inicio?</strong><small>Restaura textos, colores e imágenes originales.</small></div><?php actionButton('branding_reset','Restaurar diseño original',[], '¿Restaurar todos los textos, imágenes y colores originales de Mi Aula Digital?'); ?></div>
</div>

<aside class="branding-preview-panel">
 <div class="branding-preview-switcher" role="tablist" aria-label="Tipo de vista previa"><button type="button" class="is-active" data-preview-mode="panel">Panel</button><button type="button" data-preview-mode="login">Acceso</button></div>
 <div class="branding-preview glass" data-brand-preview>
  <div class="branding-preview-toolbar"><span>Vista previa en vivo</span><span class="badge">Dirección</span></div>
  <div class="preview-shell">
   <div class="preview-sidebar" data-preview-sidebar>
    <div class="preview-brand"><span class="branding-logo-preview" data-preview-logo><?php if($logo!==''): ?><img src="<?=e($logo)?>" alt="Logo actual"><?php else: ?><?=icon('school')?><?php endif; ?></span><span><strong data-preview-name><?=e($b['nombre_sistema'])?></strong><small data-preview-school><?=e($b['nombre_escuela']!==''?$b['nombre_escuela']:$b['lema'])?></small></span></div>
    <div class="preview-menu-label" data-preview-menu-label>Tu espacio</div><span class="preview-nav active" data-preview-nav="icon_panel"><span data-preview-nav-icon><?=icon((string)$b['icon_panel'])?></span>Inicio</span><span class="preview-nav" data-preview-nav="icon_alumnos"><span data-preview-nav-icon><?=icon((string)$b['icon_alumnos'])?></span>Alumnos</span><span class="preview-nav" data-preview-nav="icon_calificaciones"><span data-preview-nav-icon><?=icon((string)$b['icon_calificaciones'])?></span>Calificaciones</span><span class="preview-nav" data-preview-nav="icon_avisos"><span data-preview-nav-icon><?=icon((string)$b['icon_avisos'])?></span>Anuncios</span><div class="preview-sidebar-footer"><span class="preview-user"><span class="preview-avatar">A</span><span>Dirección</span></span><span class="preview-logout" data-preview-logout>↪ Cerrar sesión</span></div>
   </div>
   <div class="preview-main">
    <div class="preview-hero" data-preview-hero><small data-preview-eyebrow><?=e($b['hero_etiqueta'])?></small><strong data-preview-hero-title><?=e(str_replace('{nombre}','Andrea',$b['hero_titulo']))?></strong><p data-preview-hero-text><?=e($b['hero_direccion'])?></p></div>
    <div class="preview-cards"><div class="preview-card"><strong data-preview-card-1-title>Calificaciones</strong><small data-preview-card-1-text>Promedios y periodos</small></div><div class="preview-card"><strong data-preview-card-2-title>Comunidad</strong><small data-preview-card-2-text>Alumnos y familias</small></div><div class="preview-card"><strong data-preview-card-3-title>Anuncios</strong><small data-preview-card-3-text>Información reciente</small></div></div>
    <div class="preview-announcement" data-preview-announcement><strong data-preview-notices-title><?=e($b['panel_avisos_titulo'])?></strong><p data-preview-notice-text><?=e($b['panel_avisos_vacio_txt'])?></p></div>
    <div class="preview-actions" aria-label="Vista previa individual de botones"><span class="preview-action primary" data-preview-action="primary">Principal</span><span class="preview-action success" data-preview-action="success">Guardar / éxito</span><span class="preview-action warning" data-preview-action="warning">Tarea</span><span class="preview-action danger" data-preview-action="danger">Cerrar sesión</span><span class="preview-action info" data-preview-action="info">Información</span></div>
    <div class="preview-statuses"><span data-preview-status="success">Guardado</span><span data-preview-status="warning">Pendiente</span><span data-preview-status="danger">Bloqueado</span><span data-preview-status="info">Información</span></div>
    <div class="preview-table" aria-label="Ejemplo de tabla"><div class="preview-table-head"><span>Periodo</span><span>Lenguajes</span><span>Promedio</span></div><div><span>Periodo 1</span><strong>9.5</strong><strong>9.2</strong></div><div><span>Periodo 2</span><strong>9.0</strong><strong>9.0</strong></div></div>
    <div class="preview-role-messages"><span data-preview-role="direccion">Dirección</span><span data-preview-role="docente">Docente</span><span data-preview-role="familia">Familias</span></div>
   </div>
  </div>
 </div>
 <div class="branding-preview glass login-mini-preview" data-login-preview>
  <img class="login-preview-background" data-preview-login-background <?=$loginBg!==''?'src="'.e($loginBg).'"':'hidden'?> alt="">
  <div class="login-preview-content"><div class="overline">Acceso</div><strong data-preview-login-title><?=e($b['login_titulo'])?></strong><p data-preview-login-text><?=e($b['login_texto'])?></p><span class="preview-login-card"><b data-preview-login-card-title><?=e($b['login_tarjeta_titulo'])?></b><small data-preview-login-card-text><?=e($b['login_tarjeta_texto'])?></small></span></div>
 </div>
</aside>
</div>
<?php endif; ?>
