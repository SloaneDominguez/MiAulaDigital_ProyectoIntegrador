<?php
function announcementEnsureSchema(): void {
 static $done=false;if($done)return;
 $col=one("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aviso' AND COLUMN_NAME='Tipo_Aviso' LIMIT 1");
 $type=(string)($col['COLUMN_TYPE']??'');
 if(!str_contains($type,"'Anuncio'")||!str_contains($type,"'Tarea'")||!str_contains($type,"'Reunión'")){
  query("ALTER TABLE aviso MODIFY Tipo_Aviso ENUM('Institucional','Urgente','Anuncio','Tarea','Reunión') NOT NULL DEFAULT 'Anuncio'");
  query("UPDATE aviso SET Tipo_Aviso='Anuncio' WHERE Tipo_Aviso IN ('Institucional','Urgente')");
  query("ALTER TABLE aviso MODIFY Tipo_Aviso ENUM('Anuncio','Tarea','Reunión') NOT NULL DEFAULT 'Anuncio'");
 }
 $done=true;
}
function announcements(array $u,int $limit=50,int $offset=0,?int $id=null): array {
 announcementEnsureSchema();
 $where='1=1';$params=[(int)$u['ID_Usuario']];$p=profile($u);$cycle=activeCycleId();
 if($u['Rol']==='Docente'){$where='(pub.ID_Grupo IS NULL OR pub.ID_Grupo IN (SELECT ID_Grupo FROM asignacion_ciclo WHERE ID_Docente=? AND id_ciclo=?))';$params[]=(int)$p['ID_Docente'];$params[]=$cycle;}
 if($u['Rol']==='PadreTutor'){$where='(pub.ID_Grupo IS NULL OR pub.ID_Grupo IN (SELECT i.ID_Grupo FROM inscripcion i JOIN alumno_tutor at ON at.ID_Alumno=i.ID_Alumno JOIN alumno a ON a.ID_Alumno=i.ID_Alumno WHERE at.ID_Padre=? AND i.id_ciclo=? AND i.activo=1 AND a.activo=1))';$params[]=(int)$p['ID_Padre'];$params[]=$cycle;}
 if($id){$where.=' AND pub.ID_Publicacion=?';$params[]=$id;}
 return rows("SELECT pub.*,av.Tipo_Aviso,CONCAT(g.Grado,'° ',g.Grupo) grupo,COALESCE(ud.Nombre,ut.Nombre,'Dirección') autor,pl.leido,pl.confirmado,(SELECT COUNT(*) FROM publicacion_lectura x WHERE x.ID_Publicacion=pub.ID_Publicacion) lectores,(SELECT COUNT(*) FROM publicacion_lectura x WHERE x.ID_Publicacion=pub.ID_Publicacion AND x.confirmado IS NOT NULL) confirmaciones FROM publicacion pub LEFT JOIN aviso av ON av.ID_Publicacion=pub.ID_Publicacion LEFT JOIN grupo g ON g.ID_Grupo=pub.ID_Grupo LEFT JOIN director d ON d.ID_Director=pub.ID_Director LEFT JOIN usuario ud ON ud.ID_Usuario=d.ID_Usuario LEFT JOIN docente t ON t.ID_Docente=pub.ID_Docente LEFT JOIN usuario ut ON ut.ID_Usuario=t.ID_Usuario LEFT JOIN publicacion_lectura pl ON pl.ID_Publicacion=pub.ID_Publicacion AND pl.ID_Usuario=? WHERE $where ORDER BY pub.Fecha_Publicacion DESC,pub.ID_Publicacion DESC LIMIT ".max(1,min(100,$limit)).' OFFSET '.max(0,$offset),$params);
}
function announcementById(array $u,int $id): ?array {return announcements($u,1,0,$id)[0]??null;}
function incidents(array $u,int $limit=50,int $offset=0): array {
 $p=profile($u);$where='1=1';$params=[];
 if($u['Rol']==='Docente'){$where='EXISTS(SELECT 1 FROM inscripcion i JOIN asignacion_ciclo ac ON ac.id_ciclo=i.id_ciclo AND ac.ID_Grupo=i.ID_Grupo WHERE i.ID_Alumno=a.ID_Alumno AND ac.ID_Docente=? AND ac.id_ciclo=?)';$params[]=(int)$p['ID_Docente'];$params[]=activeCycleId();}
 if($u['Rol']==='PadreTutor'){$where='EXISTS(SELECT 1 FROM alumno_tutor at WHERE at.ID_Alumno=a.ID_Alumno AND at.ID_Padre=?)';$params[]=(int)$p['ID_Padre'];}
 return rows("SELECT i.*,CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre_completo,CONCAT(g.Grado,'° ',g.Grupo) grupo FROM incidencia i JOIN alumno a ON a.ID_Alumno=i.ID_Alumno LEFT JOIN grupo g ON g.ID_Grupo=a.ID_Grupo WHERE $where ORDER BY i.Fecha DESC,i.ID_Incidencia DESC LIMIT ".max(1,min(100,$limit)).' OFFSET '.max(0,$offset),$params);
}
function gradeRows(array $list,int $cycle,int $term): array {
 if(!$list)return [];
 $ids=array_map(fn($a)=>(int)$a['ID_Alumno'],$list);
 $marks=implode(',',array_fill(0,count($ids),'?'));
 return rows("SELECT c.*,f.Nombre_Campo FROM calificacion c JOIN campo_formativo f ON f.ID_Campo=c.ID_Campo WHERE c.id_ciclo=? AND c.Bimestre=? AND c.ID_Alumno IN ($marks) ORDER BY c.ID_Alumno,c.ID_Campo",array_merge([$cycle,(string)$term],$ids));
}
function gradeMap(array $grades): array { $map=[]; foreach($grades as $c)$map[$c['ID_Alumno']][$c['ID_Campo']]=$c; return $map; }
function renderAnnouncement(array $a,bool $manage=false): void { ?>
<article class="announcement announcement--<?=e(strtolower(strtr((string)($a['Tipo_Aviso']??'Anuncio'), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',' '=>'-'])))?>" data-item data-f-tipo="<?=e($a['Tipo_Aviso']??'Anuncio')?>" data-f-leido="<?=$a['confirmado']?'si':'no'?>" data-notice="<?=e($a['ID_Publicacion'])?>" data-read-url="<?=e(url('process/action.php'))?>" data-csrf="<?=e($_SESSION['csrf'])?>"><div class="buttons"><?=badge($a['Tipo_Aviso']??'Anuncio',($a['Tipo_Aviso']??'')==='Tarea'?'orange':(($a['Tipo_Aviso']??'')==='Reunión'?'green':''))?> <span class="help"><?=e($a['grupo']??'Toda la comunidad')?></span></div><h3><?=e($a['Titulo'])?></h3><p><?=e($a['Contenido'])?></p><div class="meta"><span><?=e($a['autor'])?></span><time><?=e($a['Fecha_Publicacion'])?></time></div>
<div class="announcement-actions"><div class="announcement-status"><?php if(!$a['confirmado'])actionButton('notice_confirm','Confirmar recibido',['id'=>$a['ID_Publicacion']]);else echo badge('Recepción confirmada','green'); ?><?php if($manage): ?><span class="help"><?=e($a['lectores'])?> lecturas · <?=e($a['confirmaciones'])?> confirmaciones</span><?php endif; ?></div>
<?php if($manage): ?><div class="buttons"><?php echo openButton('publication-modal','Editar',['id'=>$a['ID_Publicacion'],'titulo'=>$a['Titulo'],'contenido'=>$a['Contenido'],'grupo'=>$a['ID_Grupo'],'tipo'=>$a['Tipo_Aviso']??'Anuncio'],'small','Editar anuncio'); actionButton('publication_delete','Eliminar',['id'=>$a['ID_Publicacion']],'¿Eliminar este anuncio?'); ?></div><?php endif; ?></div></article><?php }
