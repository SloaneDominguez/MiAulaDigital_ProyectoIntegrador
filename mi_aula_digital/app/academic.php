<?php
function audit(array $u,string $action,string $entity,?int $id,array $detail=[]): void { query('INSERT INTO bitacora(ID_Usuario,accion,entidad,referencia,detalle) VALUES (?,?,?,?,?)',[(int)$u['ID_Usuario'],$action,$entity,$id,json_encode($detail,JSON_UNESCAPED_UNICODE)]); }
function cycleFields(int $cycle): array { return rows('SELECT f.*,cc.peso FROM ciclo_campo cc JOIN campo_formativo f ON f.ID_Campo=cc.ID_Campo WHERE cc.id_ciclo=? ORDER BY f.ID_Campo',[$cycle]); }
function gradeAverage(array $values,array $fields): array {
 $sum=0;$weight=0;$n=0;
 foreach($fields as $f){$v=$values[$f['ID_Campo']]['Calificacion_Numerica']??null;if($v!==null){$sum+=(float)$v*(float)$f['peso'];$weight+=(float)$f['peso'];$n++;}}
 return ['value'=>$weight>0?round($sum/$weight,1,PHP_ROUND_HALF_UP):null,'count'=>$n,'complete'=>$n===count($fields)&&$n>0];
}
function periodNumber(array $cycle,mixed $input): int { $n=filter_var($input,FILTER_VALIDATE_INT);if(!$n||$n<1||$n>(int)$cycle['periodos'])throw new DomainException('Periodo inválido.');return $n; }
function teacherGroups(array $u,int $cycle): array {
 if($u['Rol']==='Direccion') return rows('SELECT * FROM grupo ORDER BY Grado,Grupo');
 $p=profile($u);return rows('SELECT DISTINCT g.* FROM grupo g JOIN asignacion_ciclo ac ON ac.ID_Grupo=g.ID_Grupo WHERE ac.ID_Docente=? AND ac.id_ciclo=? ORDER BY g.Grado,g.Grupo',[(int)($p['ID_Docente']??0),$cycle]);
}
function mayGrade(array $u,int $cycle,int $group,int $field): bool { if($u['Rol']==='Direccion')return true;if($u['Rol']!=='Docente')return false;$p=profile($u);return (bool)one('SELECT 1 FROM asignacion_ciclo WHERE ID_Docente=? AND id_ciclo=? AND ID_Grupo=? AND ID_Campo=?',[(int)$p['ID_Docente'],$cycle,$group,$field]); }
function saveGradeBatch(array $u,int $cycle,int $term,array $changes): array {
 if(!in_array($u['Rol'],['Direccion','Docente'],true))throw new DomainException('Sin permiso.',403);
 if(!$changes||count($changes)>2000)throw new DomainException('Envía entre 1 y 2000 cambios.');
 return transaction(function()use($u,$cycle,$term,$changes){
  $c=activeCycle($cycle,true);periodNumber($c,$term);$fields=array_column(cycleFields($cycle),null,'ID_Campo');$p=profile($u);$updated=[];$seen=[];
  foreach($changes as $item)if(!is_array($item))throw new DomainException('Formato de captura inválido.');
  usort($changes,fn($a,$b)=>[(int)($a['alumno']??0),(int)($a['campo']??0)]<=>[(int)($b['alumno']??0),(int)($b['campo']??0)]);
  foreach($changes as $item){
   if(!is_array($item))throw new DomainException('Formato de captura inválido.');
   $id=filter_var($item['alumno']??null,FILTER_VALIDATE_INT);$field=filter_var($item['campo']??null,FILTER_VALIDATE_INT);$rev=filter_var($item['revision']??null,FILTER_VALIDATE_INT);
   if(!$id||!isset($fields[$field])||$rev===false||$rev<0)throw new DomainException('Falta el alumno, campo o versión de la nota.');
   $key=$id.':'.$field;if(isset($seen[$key]))throw new DomainException('Hay una celda repetida.');$seen[$key]=true;
   studentAccess($u,$id,$cycle,false,true);$enroll=one('SELECT * FROM inscripcion WHERE ID_Alumno=? AND id_ciclo=? AND activo=1',[$id,$cycle]);
   if(!$enroll||!$enroll['ID_Grupo']||!$enroll['verificado'])throw new DomainException('Revisa la inscripción y el grupo del alumno '.$id.'.');
   if(!mayGrade($u,$cycle,(int)$enroll['ID_Grupo'],$field))throw new DomainException('No puedes capturar ese campo o grupo.',403);
   $raw=$item['valor']??null;if(!is_string($raw)&&!is_numeric($raw))throw new DomainException('Valor inválido.');$raw=str_replace(',','.',trim((string)$raw));
   if($raw==='')continue;
   if(!preg_match('/^(10(?:\.0)?|[5-9](?:\.[0-9])?)$/',$raw))throw new DomainException('Alumno '.$id.': usa notas de 5 a 10 con un decimal.');
   $old=one('SELECT * FROM calificacion WHERE ID_Alumno=? AND ID_Campo=? AND Bimestre=? AND id_ciclo=? FOR UPDATE',[$id,$field,(string)$term,$cycle]);
   if((int)($old['revision']??0)!==$rev)throw new DomainException('Otra persona cambió una nota. No se guardó el lote. Recarga y revisa tus cambios.',409);
   $value=(float)$raw;
   if($old&&(float)$old['Calificacion_Numerica']===$value){$updated[]=['alumno'=>$id,'campo'=>$field,'revision'=>$rev,'valor'=>$value];continue;}
   if($old){$cid=(int)$old['ID_Calificacion'];query('UPDATE calificacion SET Calificacion_Numerica=?,revision=revision+1,ID_Docente=COALESCE(?,ID_Docente) WHERE ID_Calificacion=?',[$value,$u['Rol']==='Docente'?(int)$p['ID_Docente']:null,$cid]);}
   else{query('INSERT INTO calificacion(ID_Alumno,ID_Campo,Bimestre,id_ciclo,Calificacion_Numerica,ID_Docente,revision) VALUES (?,?,?,?,?,?,1)',[$id,$field,(string)$term,$cycle,$value,$u['Rol']==='Docente'?(int)$p['ID_Docente']:null]);$cid=db()->insert_id;}
   query('INSERT INTO auditoria_calificacion(ID_Calificacion,ID_Usuario,accion,valor_anterior,valor_nuevo,fecha) VALUES (?,?,?,?,?,NOW())',[$cid,(int)$u['ID_Usuario'],$old?'ACTUALIZAR':'CREAR',$old['Calificacion_Numerica']??null,$value]);
   $updated[]=['alumno'=>$id,'campo'=>$field,'revision'=>$rev+1,'valor'=>$value];
  }
  return $updated;
 });
}
function cycleReview(int $cycle): array {
 $c=one('SELECT * FROM ciclo_escolar WHERE id_ciclo=?',[$cycle]);if(!$c)throw new DomainException('Ciclo no encontrado.',404);
 $fields=cycleFields($cycle);$expected=count($fields)*(int)$c['periodos'];
 // Una sola consulta agrupada (antes era una subconsulta por alumno; con grupos grandes era lenta).
 $students=rows('SELECT i.*,COALESCE(CONCAT(gr.Grado,"° ",gr.Grupo),i.grupo_nombre) grupo_nombre,CONCAT_WS(" ",a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre_completo,COALESCE(cap.capturadas,0) capturadas FROM inscripcion i JOIN alumno a ON a.ID_Alumno=i.ID_Alumno LEFT JOIN grupo gr ON gr.ID_Grupo=i.ID_Grupo LEFT JOIN (SELECT ca.ID_Alumno,COUNT(*) capturadas FROM calificacion ca JOIN ciclo_campo cc ON cc.id_ciclo=ca.id_ciclo AND cc.ID_Campo=ca.ID_Campo WHERE ca.id_ciclo=? AND CAST(ca.Bimestre AS UNSIGNED)<=? GROUP BY ca.ID_Alumno) cap ON cap.ID_Alumno=i.ID_Alumno WHERE i.id_ciclo=? AND i.activo=1 AND a.activo=1 ORDER BY gr.Grado,gr.Grupo,i.grupo_nombre,a.Primer_Apellido',[$cycle,(int)$c['periodos'],$cycle]);
 $pending=0;$unknown=0;foreach($students as &$s){$s['faltantes']=max(0,$expected-(int)$s['capturadas']);$pending+=$s['faltantes'];if(!$s['verificado']||!$s['ID_Grupo'])$unknown++;}unset($s);
 return ['cycle'=>$c,'students'=>$students,'pending'=>$pending,'unknown'=>$unknown,'expected'=>$expected];
}

/** Resumen único usado por pantalla, Excel y PDF para evitar promedios diferentes. */
function studentCycleSummary(int $student,int $cycle): array {
    $c=one('SELECT * FROM ciclo_escolar WHERE id_ciclo=?',[$cycle]);
    if(!$c)throw new DomainException('Ciclo no encontrado.',404);
    $enroll=one('SELECT i.*,CONCAT_WS(" ",a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre_completo,a.CURP FROM inscripcion i JOIN alumno a ON a.ID_Alumno=i.ID_Alumno WHERE i.ID_Alumno=? AND i.id_ciclo=?',[$student,$cycle]);
    if(!$enroll)throw new DomainException('El alumno no está inscrito en ese ciclo.',404);
    $fields=cycleFields($cycle);
    $marks=rows('SELECT * FROM calificacion WHERE ID_Alumno=? AND id_ciclo=? ORDER BY CAST(Bimestre AS UNSIGNED),ID_Campo',[$student,$cycle]);
    $by=[];foreach($marks as $m)$by[(int)$m['Bimestre']][(int)$m['ID_Campo']]=$m;
    $periods=[];$sum=0.0;$count=0;$complete=true;
    for($n=1;$n<=(int)$c['periodos'];$n++){
        $avg=gradeAverage($by[$n]??[],$fields);
        $periods[$n]=['grades'=>$by[$n]??[],'average'=>$avg];
        if($avg['value']!==null){$sum+=(float)$avg['value'];$count++;}
        if(!$avg['complete'])$complete=false;
    }
    return [
        'cycle'=>$c,'student'=>$enroll,'fields'=>$fields,'periods'=>$periods,
        'final'=>$count?round($sum/$count,1,PHP_ROUND_HALF_UP):null,
        'complete'=>$complete&&$count===(int)$c['periodos'],'period_count'=>$count,
    ];
}
