<?php
// Diagnóstico seguro de Mi Aula Digital. Solo lee la base de datos y crea/elimina un XLSX temporal de prueba.
require __DIR__.'/../app/bootstrap.php';

$isText = PHP_SAPI === 'cli' || (isset($_GET['format']) && $_GET['format'] === 'text');
$checks=[];$hasFailure=false;$warningCount=0;
function addCheck(string $group,string $name,string $status='ok',string $detail=''): void {
    global $checks,$hasFailure,$warningCount;
    if(!in_array($status,['ok','fail','warn','info'],true))$status='info';
    if($status==='fail')$hasFailure=true;
    if($status==='warn')$warningCount++;
    $checks[]=['group'=>$group,'name'=>$name,'status'=>$status,'detail'=>$detail];
}
function hasColumn(string $table,string $column): bool {
    return one('SELECT 1 ok FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1',[$table,$column])!==null;
}
function hasTable(string $table): bool {
    return one('SELECT 1 ok FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1',[$table])!==null;
}

addCheck('Servidor','PHP 8.1 o superior',version_compare(PHP_VERSION,'8.1.0','>=')?'ok':'fail','Versión detectada: '.PHP_VERSION);
$excelExtensions=['mbstring','zip','dom','xml','xmlreader','xmlwriter'];
foreach(['mysqli','mbstring','fileinfo','gd','zip','dom','simplexml','xml','xmlreader','xmlwriter','ctype','iconv','zlib'] as $ext){
    addCheck(in_array($ext,$excelExtensions,true)?'Excel':'Servidor','Extensión '.$ext,extension_loaded($ext)?'ok':'fail',extension_loaded($ext)?'Activa':'No está activa en el PHP que usa Apache');
}
addCheck('Excel','Funciones mbstring',function_exists('mb_strlen')&&function_exists('mb_substr')?'ok':'fail',(function_exists('mb_strlen')&&function_exists('mb_substr'))?'mb_strlen y mb_substr disponibles':'mbstring está cargada, pero faltan funciones');
addCheck('Excel','ZipArchive',class_exists('ZipArchive')?'ok':'fail',class_exists('ZipArchive')?'Disponible':'No disponible');

$brandingDir=__DIR__.'/../uploads/branding';
$tempDir=__DIR__.'/../storage/tmp';
if(!is_dir($tempDir)) @mkdir($tempDir,0755,true);
addCheck('Archivos','Carpeta uploads/branding',is_dir($brandingDir)&&is_writable($brandingDir)?'ok':'fail',is_dir($brandingDir)?(is_writable($brandingDir)?'Escribible':'Existe, pero no permite escritura'):'No existe');
addCheck('Excel','Carpeta temporal storage/tmp',is_dir($tempDir)&&is_writable($tempDir)?'ok':'fail',is_dir($tempDir)?(is_writable($tempDir)?'Escribible':'Existe, pero no permite escritura'):'No existe');

// Prueba REAL del motor XLSX. Detecta errores que una lista de extensiones no encuentra.
$autoload=__DIR__.'/../vendor/autoload.php';
if(!is_file($autoload)){
    addCheck('Excel','PhpSpreadsheet','fail','Falta vendor/autoload.php');
}else{
    try{
        require_once $autoload;
        if(!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')){
            addCheck('Excel','PhpSpreadsheet','fail','La clase Spreadsheet no pudo cargarse');
        }else{
            addCheck('Excel','PhpSpreadsheet','ok','Biblioteca cargada desde vendor/');
            $probe=$tempDir.'/diagnostico_excel_'.bin2hex(random_bytes(5)).'.xlsx';
            try{
                $book=new PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet=$book->getActiveSheet();
                $sheet->setCellValue('A1','Mi Aula Digital');
                $sheet->setCellValue('A2','Prueba XLSX');
                // Forma correcta de configurar el color de la pestaña en PhpSpreadsheet.
                $sheet->getTabColor()->setARGB('FFD00032');
                (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($probe);
                $valid=is_file($probe)&&filesize($probe)>500;
                addCheck('Excel','Prueba real de creación XLSX',$valid?'ok':'fail',$valid?'Archivo XLSX temporal creado correctamente':'El archivo temporal quedó vacío');
                $book->disconnectWorksheets();unset($book);
            }catch(Throwable $excelEx){
                addCheck('Excel','Prueba real de creación XLSX','fail',substr($excelEx->getMessage(),0,320));
            }finally{
                if(isset($probe)&&is_file($probe))@unlink($probe);
            }
        }
    }catch(Throwable $autoloadEx){
        addCheck('Excel','PhpSpreadsheet','fail','No pudo cargarse: '.substr($autoloadEx->getMessage(),0,280));
    }
}

try{
    db();
    addCheck('Base de datos','Conexión MySQL / MariaDB','ok','Conexión correcta');
    $requiredTables=['usuario','alumno','docente','padre_tutor','grupo','ciclo_escolar','calificacion','inscripcion','asignacion_ciclo','alumno_tutor','ciclo_campo','bitacora','publicacion','aviso'];
    foreach($requiredTables as $table){
        $exists=hasTable($table);$detail='No existe';
        if($exists){$r=one('SELECT COUNT(*) total FROM `'.$table.'`');$detail=(string)($r['total']??0).' registros';}
        addCheck('Base de datos','Tabla '.$table,$exists?'ok':'fail',$detail);
    }

    $accessSchema=hasColumn('usuario','metodo_acceso')&&hasColumn('usuario','google_sub')&&hasColumn('usuario','correo_verificado');
    addCheck('Base de datos','Esquema Local / Google / Ambos',$accessSchema?'ok':'fail',$accessSchema?'Completo':'Ejecuta instalacion/04_acceso_google_ciclos_anuncios.sql');

    $noticeColumn=one("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aviso' AND COLUMN_NAME='Tipo_Aviso' LIMIT 1");
    $noticeType=(string)($noticeColumn['COLUMN_TYPE']??'');
    $noticesOk=str_contains($noticeType,"'Anuncio'")&&str_contains($noticeType,"'Tarea'")&&str_contains($noticeType,"'Reunión'");
    addCheck('Base de datos','Categorías de anuncios',$noticesOk?'ok':'fail',$noticesOk?'Anuncio, Tarea y Reunión disponibles':'Ejecuta la migración 04');

    $open=hasTable('ciclo_escolar')?rows("SELECT id_ciclo,nombre,estado FROM ciclo_escolar WHERE estado<>'CERRADO' ORDER BY id_ciclo DESC"):[];
    if(count($open)<=1){
        addCheck('Datos escolares','Ciclos sin cerrar','ok',count($open).' abierto/planeado');
    }else{
        $names=array_map(static fn($c)=>(string)$c['nombre'].' ('.$c['estado'].')',$open);
        addCheck('Datos escolares','Hay más de un ciclo sin cerrar','warn',count($open).' detectados: '.implode(' · ',$names).'. Esto NO es un fallo de Excel. En Dirección > Ciclos escolares, cierra o elimina el ciclo que ya no corresponda.');
    }

    $brandingOk=hasTable('configuracion_escuela');
    addCheck('Personalización','Tabla de personalización',$brandingOk?'ok':'info',$brandingOk?'Disponible':'Se crea automáticamente al guardar Personalización');

    global $config;
    $googleConfigured=trim((string)($config['google_client_id']??''))!==''&&trim((string)($config['google_client_secret']??''))!=='';
    addCheck('Opcional','Google OAuth','info',$googleConfigured?'Credenciales configuradas':'Pendiente de credenciales; el acceso local sigue funcionando');
    if($googleConfigured){
        $httpOk=function_exists('curl_init')||filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN);
        addCheck('Opcional','HTTP saliente para Google',$httpOk?'ok':'warn',$httpOk?'cURL o allow_url_fopen disponible':'Google podría no conectarse desde este servidor');
    }
}catch(Throwable $ex){
    addCheck('Base de datos','Conexión / estructura','fail',$ex->getMessage());
}

if($isText){
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Mi Aula Digital · diagnóstico de instalación\n".str_repeat('=',58)."\n";
    $last='';foreach($checks as $c){if($c['group']!==$last){$last=$c['group'];echo "\n[{$last}]\n";} $tag=['ok'=>'OK','fail'=>'FALLO','warn'=>'AVISO','info'=>'INFO'][$c['status']];echo '['.$tag.'] '.$c['name'].($c['detail']!==''?' · '.$c['detail']:'')."\n";}
    echo "\n".str_repeat('-',58)."\n";
    echo $hasFailure?'HAY FALLOS TÉCNICOS QUE CORREGIR\n':($warningCount?'INSTALACIÓN TÉCNICA LISTA · HAY '.$warningCount." AVISO(S) DE DATOS\n":"VERIFICACIÓN COMPLETA: TODO LISTO\n");
    exit($hasFailure?1:0);
}

$excelFails=array_values(array_filter($checks,static fn($c)=>$c['group']==='Excel'&&$c['status']==='fail'));
$excelReady=count($excelFails)===0;
$groups=[];foreach($checks as $c)$groups[$c['group']][]=$c;
http_response_code($hasFailure?500:200);
header('Content-Type: text/html; charset=UTF-8');
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mi Aula Digital · Diagnóstico</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f6f9;color:#172033;font:14px/1.45 system-ui,-apple-system,"Segoe UI",sans-serif;padding:28px}.wrap{max-width:1180px;margin:auto}.hero{display:flex;justify-content:space-between;gap:22px;align-items:flex-start;background:#fff;border:1px solid #dfe5ed;border-radius:24px;padding:24px;box-shadow:0 18px 60px rgba(15,23,42,.08)}h1{margin:3px 0 7px;font-size:27px}.eyebrow{text-transform:uppercase;letter-spacing:.11em;font-weight:800;font-size:10px;color:#667085}.hero p{margin:0;color:#667085;max-width:690px}.excel{min-width:270px;border-radius:18px;padding:16px;border:1px solid}.excel.ok{background:#eef9f3;border-color:#cfe9da}.excel.fail{background:#fff2f3;border-color:#efcbd0}.excel strong{display:block;font-size:15px}.excel small{display:block;margin-top:4px;color:#5f6b7b}.summary{margin:17px 0;padding:13px 16px;border-radius:15px;background:#fff;border:1px solid #dfe5ed;font-weight:750}.summary.warn{border-color:#efd49e;background:#fff8e9}.summary.fail{border-color:#efcbd0;background:#fff2f3}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.group{background:#fff;border:1px solid #dfe5ed;border-radius:20px;padding:18px;box-shadow:0 10px 30px rgba(15,23,42,.05)}.group h2{font-size:15px;margin:0 0 12px}.row{display:grid;grid-template-columns:28px minmax(0,1fr);gap:10px;padding:10px 0;border-top:1px solid #edf0f4}.row:first-of-type{border-top:0}.icon{width:24px;height:24px;border-radius:8px;display:grid;place-items:center;font-weight:900;font-size:11px}.ok .icon,.icon.ok{background:#e8f7ef;color:#16744c}.fail .icon,.icon.fail{background:#fdebed;color:#a5293e}.warn .icon,.icon.warn{background:#fff0cc;color:#8a5a00}.info .icon,.icon.info{background:#edf3ff;color:#315fb4}.row strong{display:block;font-size:12px}.row small{display:block;color:#6d7787;margin-top:2px;word-break:break-word}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.actions a{display:inline-flex;padding:10px 14px;border-radius:12px;text-decoration:none;font-weight:750;background:#172033;color:#fff}.actions a.alt{background:#fff;border:1px solid #d8e0ea;color:#172033}@media(max-width:800px){body{padding:14px}.hero{flex-direction:column}.excel{width:100%;min-width:0}.grid{grid-template-columns:1fr}}
</style></head><body><main class="wrap">
<section class="hero"><div><div class="eyebrow">Diagnóstico de instalación</div><h1>Mi Aula Digital</h1><p>Esta pantalla separa los problemas técnicos de los avisos de datos. Así puedes saber si Excel realmente está listo.</p></div><div class="excel <?=$excelReady?'ok':'fail'?>"><strong><?=$excelReady?'✓ Excel está listo':'✕ Excel todavía tiene un problema'?></strong><small><?=$excelReady?'PhpSpreadsheet creó un archivo XLSX real correctamente.':'Revisa la sección Excel; ahí aparece el error exacto.'?></small></div></section>
<div class="summary <?=$hasFailure?'fail':($warningCount?'warn':'')?>"><?php if($hasFailure): ?>Hay fallos técnicos que deben corregirse.<?php elseif($warningCount): ?>La instalación técnica está lista. Hay <?=$warningCount?> aviso(s) de datos que conviene revisar.<?php else: ?>Todo está listo para usar y publicar.<?php endif; ?></div>
<div class="grid">
<?php foreach($groups as $group=>$items): ?><section class="group"><h2><?=htmlspecialchars($group,ENT_QUOTES,'UTF-8')?></h2><?php foreach($items as $c): $symbol=['ok'=>'✓','fail'=>'!','warn'=>'△','info'=>'i'][$c['status']]; ?><div class="row <?=$c['status']?>"><span class="icon <?=$c['status']?>"><?=$symbol?></span><div><strong><?=htmlspecialchars($c['name'],ENT_QUOTES,'UTF-8')?></strong><?php if($c['detail']!==''): ?><small><?=htmlspecialchars($c['detail'],ENT_QUOTES,'UTF-8')?></small><?php endif; ?></div></div><?php endforeach; ?></section><?php endforeach; ?>
</div><div class="actions"><a href="../index.php">Volver a Mi Aula Digital</a><a class="alt" href="?format=text">Ver diagnóstico en texto</a></div>
</main></body></html>
