<?php
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/data.php';

function exportHex(string $hex): string { return 'FF'.strtoupper(ltrim($hex,'#')); }
function exportPdfText(string $s): string { return iconv('UTF-8','Windows-1252//TRANSLIT',$s)?:$s; }
function exportRgb(string $hex): array { $hex=ltrim($hex,'#'); return [hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))]; }
function safeCsvValue(mixed $v): mixed { return is_string($v)&&preg_match('/^[\s]*[=+@-]/u',$v)?"'".$v:$v; }
function xlsxXml(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function writeStyledXlsxFallback(string $file, string $brandTitle, string $title, array $headers, array $data, array $branding, ?array $cycle, ?array $studentSummary, string $report): void {
    if (!class_exists('ZipArchive')) throw new DomainException('Excel no pudo generarse porque la extensión ZIP de PHP no está disponible.',503);
    $zip=new ZipArchive();
    if($zip->open($file,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new DomainException('No se pudo crear el archivo temporal de Excel. Revisa permisos de storage/tmp.',500);
    $accent=exportHex($branding['color_principal']); $dark=exportHex($branding['color_sidebar']); $darkText=exportHex(brandingBestTextColor($branding['color_sidebar']));
    $cols=max(1,count($headers));
    $colName=function(int $n):string{$r='';while($n>0){$n--; $r=chr(65+($n%26)).$r; $n=intdiv($n,26);}return $r;};
    $cell=function(int $c,int $r,mixed $value)use($colName):string{$ref=$colName($c).$r;if(is_int($value)||is_float($value))return '<c r="'.$ref.'"><v>'.xlsxXml((string)$value).'</v></c>';return '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.xlsxXml((string)$value).'</t></is></c>';};
    $rows=[];
    $putRow=function(int $r,array $values)use(&$rows,$cell){$xml='<row r="'.$r.'">';foreach($values as $i=>$v)$xml.=$cell($i+1,$r,$v);$rows[]=$xml.'</row>';};
    if($report==='historial'&&$studentSummary){
        $putRow(1,[$title]);$putRow(2,['Ciclo Escolar '.($cycle['nombre']??'').' | Fecha de Emisión: '.date('d/m/Y')]);$putRow(4,['Alumno: '.($studentSummary['student']['nombre_completo']??'').' | Grupo: '.($studentSummary['student']['grupo_nombre']??'')]);$putRow(5,$headers);$r=6;foreach($data as $line)$putRow($r++,$line);$putRow($r,['PROMEDIO FINAL DEL CICLO:','','','','',$studentSummary['final']??'Pendiente']);$merge=['A1:'.$colName($cols).'1','A2:'.$colName($cols).'2','A4:'.$colName($cols).'4'];$freeze='A6';
    }else{
        $putRow(1,[$brandTitle]);$putRow(2,[$title.' | Fecha de Emisión: '.date('d/m/Y')]);$putRow(4,$headers);$r=5;foreach($data as $line)$putRow($r++,$line);$merge=['A1:'.$colName($cols).'1','A2:'.$colName($cols).'2'];$freeze='A5';
    }
    $maxrow=1;foreach($rows as $row)if(preg_match('/r="(\d+)"/',$row,$m))$maxrow=max($maxrow,(int)$m[1]);
    $widths='';for($i=1;$i<=$cols;$i++){$w=$i===1?36:22;$widths.='<col min="'.$i.'" max="'.$i.'" width="'.$w.'" customWidth="1"/>';}
    $mergeXml='';foreach($merge as $m)$mergeXml.='<mergeCell ref="'.$m.'"/>';
    $sheet='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:'.$colName($cols).$maxrow.'"/><sheetViews><sheetView workbookViewId="0" showGridLines="0"><pane ySplit="5" topLeftCell="'.$freeze.'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>'.$widths.'</cols><sheetData>'.implode('',$rows).'</sheetData><mergeCells count="'.count($merge).'">'.$mergeXml.'</mergeCells><pageMargins left="0.25" right="0.25" top="0.35" bottom="0.45" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/></worksheet>';
    $styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="18"/><color rgb="'.$darkText.'"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="'.$darkText.'"/><name val="Calibri"/></font></fonts><fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="'.$accent.'"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF4F6F9"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF7FAFC"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="'.$dark.'"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="2" fillId="5" borderId="0"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    $workbook='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.xlsxXml($report==='historial'?'Histórico':($report==='tokens'?'Vinculación':'Calificaciones')).'" sheetId="1" r:id="rId1"/></sheets></workbook>';
    $rels='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    $wbRels='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    $types='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    $zip->addFromString('[Content_Types].xml',$types);$zip->addFromString('_rels/.rels',$rels);$zip->addFromString('xl/workbook.xml',$workbook);$zip->addFromString('xl/_rels/workbook.xml.rels',$wbRels);$zip->addFromString('xl/worksheets/sheet1.xml',$sheet);$zip->addFromString('xl/styles.xml',$styles);$zip->close();
}


function exportError(Throwable $ex): never {
    $message=$ex instanceof DomainException ? $ex->getMessage() : 'No se pudo generar el reporte. Revisa la instalación y vuelve a intentarlo.';
    $code=(int)$ex->getCode(); if($code<400||$code>599)$code=500;
    http_response_code($code);
    header('Content-Type: text/html; charset=UTF-8');
    $safe=htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mi Aula Digital · Exportación</title><style>body{margin:0;background:#f5f3f4;font:15px system-ui,-apple-system,Segoe UI,sans-serif;color:#172033;min-height:100vh;display:grid;place-items:center;padding:24px}.box{width:min(720px,100%);background:#fff;border:1px solid #e1e6ee;border-radius:24px;padding:30px;box-shadow:0 18px 60px rgba(20,30,50,.12)}h1{margin:0 0 10px;font-size:24px}.msg{padding:16px 18px;background:#fff5f5;border:1px solid #f2caca;border-radius:14px;color:#8e2635;white-space:pre-wrap}.actions{margin-top:20px;display:flex;gap:10px;flex-wrap:wrap}a{display:inline-flex;align-items:center;padding:11px 16px;border-radius:12px;background:#d00032;color:#fff;text-decoration:none;font-weight:700}a.alt{background:#f2f4f7;color:#172033}</style></head><body><main class="box"><h1>No se pudo generar el archivo</h1><p>Mi Aula Digital detectó exactamente el requisito que falta:</p><div class="msg">'.$safe.'</div><div class="actions"><a href="javascript:history.back()">Volver</a><a class="alt" href="../tools/verificar_instalacion.php">Verificar instalación</a></div></main></body></html>'; exit;
}

try {
    $u=requireUser();profile($u);$branding=schoolBranding();$brandTitle=brandingDisplayName($branding);$brandSlug=brandingSlug($branding);
    $type=$_GET['tipo']??'xlsx';$report=$_GET['reporte']??'calificaciones';
    if(!is_string($type)||!in_array($type,['xlsx','pdf','csv'],true)||!is_string($report)||!in_array($report,['calificaciones','historial','tokens'],true))throw new DomainException('Reporte inválido.');

    $cycle=null;$studentSummary=null;$headers=[];$data=[];$title='';
    if($report==='tokens'){
        requireUser(['Direccion']);$title='Códigos de vinculación';$headers=['Alumno','Código','Expiración'];
        foreach(rows("SELECT CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre,t.Token_String,t.Fecha_Expiracion FROM alumno a JOIN tokens t ON t.ID_Token=a.ID_Token AND t.ID_Alumno=a.ID_Alumno WHERE a.activo=1 AND t.Fecha_Expiracion>NOW() ORDER BY a.Primer_Apellido") as $t)$data[]=[$t['nombre'],$t['Token_String'],$t['Fecha_Expiracion']];
    }elseif($report==='historial'){
        $cycle=selectedCycle();if(!$cycle)throw new DomainException('No hay un ciclo escolar para exportar.');
        $student=filter_var($_GET['alumno']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$student)throw new DomainException('Selecciona un alumno.');
        studentAccess($u,(int)$student,(int)$cycle['id_ciclo'],true);
        $studentSummary=studentCycleSummary((int)$student,(int)$cycle['id_ciclo']);
        $title='Concentrado Histórico de Calificaciones';
        $headers=[$cycle['tipo_periodo']];foreach($studentSummary['fields'] as $f)$headers[]=$f['Nombre_Campo'];$headers[]='Promedio '.($cycle['tipo_periodo']==='Periodo'?'del periodo':$cycle['tipo_periodo']);
        foreach($studentSummary['periods'] as $n=>$period){$line=[$cycle['tipo_periodo'].' '.$n];foreach($studentSummary['fields'] as $f){$v=$period['grades'][$f['ID_Campo']]['Calificacion_Numerica']??null;$line[]=$v===null?'':(float)$v;}$line[]=$period['average']['value']??'';$data[]=$line;}
    }else{
        $cycle=selectedCycle();if(!$cycle)throw new DomainException('No hay un ciclo escolar para exportar.');
        $term=max(1,min((int)$cycle['periodos'],(int)($_GET['bimestre']??1)));$group=max(0,(int)($_GET['grupo']??0));
        $list=students($u,(int)$cycle['id_ciclo'],true,null,0,'',$group);$fields=cycleFields((int)$cycle['id_ciclo']);$map=gradeMap(gradeRows($list,(int)$cycle['id_ciclo'],$term));
        $title='Calificaciones · '.$cycle['nombre'].' · '.$cycle['tipo_periodo'].' '.$term;$headers=['Alumno','Grupo'];foreach($fields as $f)$headers[]=$f['Nombre_Campo'];$headers[]='Promedio';$headers[]='Cobertura';
        foreach($list as $a){$line=[$a['nombre_completo'],$a['grupo']??'Sin grupo'];$count=0;foreach($fields as $f){$v=$map[$a['ID_Alumno']][$f['ID_Campo']]['Calificacion_Numerica']??null;$line[]=$v===null?'':(float)$v;if($v!==null)$count++;}$average=gradeAverage($map[$a['ID_Alumno']]??[],$fields);$line[]=$average['value']??'';$line[]=($count<count($fields)?'Parcial ':'Completo ').$count.'/'.count($fields);$data[]=$line;}
    }

    if($type==='csv'){
        header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="'.$brandSlug.'-'.$report.'.csv"');
        $out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,$headers,',','"','');foreach($data as $line)fputcsv($out,array_map('safeCsvValue',$line),',','"','');fclose($out);exit;
    }

    if($type==='xlsx'){
        $missing=[];foreach(['mbstring','zip','dom','xml','xmlreader','xmlwriter'] as $ext)if(!extension_loaded($ext))$missing[]=$ext;
        if($missing)throw new DomainException('Excel no puede generarse todavía. XAMPP no tiene activas estas extensiones de PHP: '.implode(', ',$missing).'. Habilítalas en el php.ini que usa Apache, reinicia Apache y vuelve a intentar.',503);
        $autoload=__DIR__.'/../vendor/autoload.php';
        if(!is_file($autoload))throw new DomainException('Falta vendor/autoload.php. Ejecuta composer install dentro de Mi Aula Digital antes de generar archivos Excel.',500);
        require $autoload;
        // PhpSpreadsheet puede fallar en XAMPP cuando sys_get_temp_dir() no es escribible.
        // Usamos una carpeta propia de la aplicación y solo caemos al temporal del sistema
        // si el servidor no permite crearla.
        $madTemp=dirname(__DIR__).'/storage/tmp';
        if(!is_dir($madTemp)) @mkdir($madTemp,0755,true);
        if(is_dir($madTemp) && is_writable($madTemp)){
            // XAMPP/Windows usa TEMP/TMP; definimos ambos para que PhpSpreadsheet pueda crear
            // archivos intermedios aun cuando el temporal del sistema tenga permisos limitados.
            if(function_exists('putenv'))@putenv('TMPDIR='.$madTemp);
            if(function_exists('putenv'))@putenv('TMP='.$madTemp);
            if(function_exists('putenv'))@putenv('TEMP='.$madTemp);
        }
        $accent=exportHex($branding['color_principal']);$book=new PhpOffice\PhpSpreadsheet\Spreadsheet();$sheet=$book->getActiveSheet();$sheet->getTabColor()->setARGB($accent);$sheet->getPageMargins()->setTop(.35)->setRight(.25)->setBottom(.45)->setLeft(.25);$sheet->getPageSetup()->setOrientation(PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);$sheet->getHeaderFooter()->setOddFooter('&L'.$brandTitle.'&C'.$title.'&RPágina &P de &N');$sheet->setTitle($report==='historial'?'Histórico':($report==='tokens'?'Vinculación':'Calificaciones'));
        $last=PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(1,count($headers)));
        $dark=exportHex($branding['color_sidebar']);$darkText=exportHex(brandingBestTextColor($branding['color_sidebar']));$success=exportHex($branding['color_exito']);
        $logo=brandingLogoAbsolute($branding);
        if($report==='historial'&&$studentSummary){
            $sheet->setShowGridlines(false);$sheet->getRowDimension(1)->setRowHeight(34);$sheet->getRowDimension(2)->setRowHeight(24);$sheet->getRowDimension(4)->setRowHeight(25);
            if($logo!==''){$drawing=new PhpOffice\PhpSpreadsheet\Worksheet\Drawing();$drawing->setName('Logo');$drawing->setPath($logo);$drawing->setHeight(72);$drawing->setCoordinates('A1');$drawing->setOffsetX(4);$drawing->setOffsetY(4);$drawing->setWorksheet($sheet);}
            $titleStart=$logo!==''?'B1':'A1';$sheet->mergeCells($titleStart.':'.$last.'1');$sheet->setCellValue($titleStart,$title);$sheet->getStyle($titleStart.':'.$last.'1')->getFill()->setFillType('solid')->getStartColor()->setARGB($dark);$sheet->getStyle($titleStart)->getFont()->setBold(true)->setSize(20)->getColor()->setARGB($darkText);
            $subStart=$logo!==''?'B2':'A2';$sheet->mergeCells($subStart.':'.$last.'2');$sheet->setCellValue($subStart,'Ciclo Escolar '.$cycle['nombre'].' | Fecha de Emisión: '.date('d/m/Y'));$sheet->getStyle($subStart)->getFont()->setSize(11)->getColor()->setARGB('FF4F596B');
            $sheet->getStyle('A3:'.$last.'3')->getBorders()->getBottom()->setBorderStyle('medium')->getColor()->setARGB($accent);
            $sheet->mergeCells('A4:'.$last.'4');$sheet->setCellValue('A4','Alumno: '.$studentSummary['student']['nombre_completo'].' | Grupo: '.$studentSummary['student']['grupo_nombre']);$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB($darkText);$sheet->getStyle('A4')->getFill()->setFillType('solid')->getStartColor()->setARGB($dark);
            $headerRow=5;foreach($headers as $i=>$h)$sheet->setCellValue(PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).$headerRow,$h);
            $sheet->getStyle('A'.$headerRow.':'.$last.$headerRow)->getFont()->setBold(true)->getColor()->setARGB('FF394252');$sheet->getStyle('A'.$headerRow.':'.$last.$headerRow)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF4F6F9');$sheet->getStyle('A'.$headerRow.':'.$last.$headerRow)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
            foreach($data as $r=>$line){$row=$headerRow+1+$r;foreach($line as $c=>$value){$cell=PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c+1).$row;if(is_int($value)||is_float($value))$sheet->setCellValueExplicit($cell,$value,PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);else $sheet->setCellValueExplicit($cell,(string)$value,PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);}if($r%2===1)$sheet->getStyle('A'.$row.':'.$last.$row)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF8FAFC');$sheet->getStyle('A'.$row.':'.$last.$row)->getAlignment()->setHorizontal('center')->setVertical('center');}
            $finalRow=$headerRow+1+count($data);$finalLabelCol=max(1,count($headers)-1);$labelStart=PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($finalLabelCol);$sheet->mergeCells('A'.$finalRow.':'.$labelStart.$finalRow);$sheet->setCellValue('A'.$finalRow,'PROMEDIO FINAL DEL CICLO:');$sheet->getStyle('A'.$finalRow.':'.$labelStart.$finalRow)->getAlignment()->setHorizontal('right');$sheet->getStyle('A'.$finalRow.':'.$labelStart.$finalRow)->getFont()->setBold(true);
            $sheet->setCellValue($last.$finalRow,$studentSummary['final']??'Pendiente');$sheet->getStyle($last.$finalRow)->getFont()->setBold(true)->getColor()->setARGB($success);$sheet->getStyle($last.$finalRow)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFEAF8F0');$sheet->getStyle($last.$finalRow)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A5:'.$last.$finalRow)->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setARGB('FFD9DEE7');
            foreach(range(1,count($headers)) as $i){$col=PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);$sheet->getColumnDimension($col)->setWidth($i===1?18:($i===count($headers)?20:24));}
            $sheet->freezePane('B6');
        }else{
            // Reportes generales: reutilizan la misma identidad institucional (logo + colores).
            $sheet->setShowGridlines(false);
            $headerRow=4;
            if($logo!==''){
                $drawing=new PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('Logo de la escuela');$drawing->setPath($logo);$drawing->setHeight(58);$drawing->setCoordinates('A1');$drawing->setOffsetX(4);$drawing->setOffsetY(4);$drawing->setWorksheet($sheet);
                $titleStart=count($headers)>1?'B1':'A1';
            }else{$titleStart='A1';}
            $sheet->mergeCells($titleStart.':'.$last.'1');$sheet->setCellValue($titleStart,$brandTitle);$sheet->getStyle($titleStart.':'.$last.'1')->getFill()->setFillType('solid')->getStartColor()->setARGB($accent);$sheet->getStyle($titleStart)->getFont()->setBold(true)->setSize(17)->getColor()->setARGB(exportHex(brandingBestTextColor($branding['color_principal'])));
            $subStart=$logo!==''&&count($headers)>1?'B2':'A2';$sheet->mergeCells($subStart.':'.$last.'2');$sheet->setCellValue($subStart,$title.' | Fecha de Emisión: '.date('d/m/Y'));$sheet->getStyle($subStart)->getFont()->setSize(10)->getColor()->setARGB('FF4F596B');
            $sheet->getStyle('A3:'.$last.'3')->getBorders()->getBottom()->setBorderStyle('medium')->getColor()->setARGB($accent);
            foreach($headers as $i=>$h)$sheet->setCellValue(PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).$headerRow,$h);
            $sheet->getStyle('A'.$headerRow.':'.$last.$headerRow)->getFont()->setBold(true)->getColor()->setARGB($darkText);$sheet->getStyle('A'.$headerRow.':'.$last.$headerRow)->getFill()->setFillType('solid')->getStartColor()->setARGB($dark);$sheet->getStyle('A'.$headerRow.':'.$last.$headerRow)->getAlignment()->setHorizontal('center')->setVertical('center')->setWrapText(true);
            foreach($data as $r=>$line){$row=$headerRow+1+$r;foreach($line as $c=>$value){$cell=PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c+1).$row;if(is_int($value)||is_float($value))$sheet->setCellValueExplicit($cell,$value,PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);else $sheet->setCellValueExplicit($cell,(string)$value,PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);}if($r%2===1)$sheet->getStyle('A'.$row.':'.$last.$row)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF7FAFC');$sheet->getStyle('A'.$row.':'.$last.$row)->getAlignment()->setVertical('center');}
            $endRow=$headerRow+count($data);$sheet->getStyle('A'.$headerRow.':'.$last.$endRow)->getBorders()->getAllBorders()->setBorderStyle('thin')->getColor()->setARGB('FFD9DEE7');$sheet->freezePane('C'.($headerRow+1));foreach(range(1,count($headers))as $i)$sheet->getColumnDimension(PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setWidth($i===1?36:22);
        }
        $tmpXlsx=$madTemp.'/'.bin2hex(random_bytes(8)).'.xlsx';
        try{(new PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($tmpXlsx);}catch(Throwable $xlsxError){
            try{writeStyledXlsxFallback($tmpXlsx,$brandTitle,$title,$headers,$data,$branding,$cycle,$studentSummary,$report);}catch(Throwable $fallbackError){
                $detail=function_exists('mb_substr')?mb_substr($xlsxError->getMessage(),0,260):substr($xlsxError->getMessage(),0,260);
                throw new DomainException('Excel no pudo generarse. Detalle técnico: '.$detail.' | Alternativa XLSX: '.substr($fallbackError->getMessage(),0,220),500,$fallbackError);
            }
        }
        if(!is_file($tmpXlsx)||filesize($tmpXlsx)<=0)throw new DomainException('Excel no pudo generarse: el archivo temporal quedó vacío.',500);
        // Evita que espacios, avisos o buffers previos corrompan el archivo binario XLSX.
        while(ob_get_level()>0){ @ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Content-Disposition: attachment; filename="'.$brandSlug.'-'.$report.'.xlsx"');
        header('Content-Length: '.filesize($tmpXlsx));
        readfile($tmpXlsx); @unlink($tmpXlsx); exit;
    }

    require __DIR__.'/../fpdf/fpdf.php';
    if(!class_exists('MADExportPdf')){
        class MADExportPdf extends FPDF {
            public string $madFooter='';
            public function Footer(): void {
                $this->SetY(-9);$this->SetFont('Arial','',7);$this->SetTextColor(105,112,122);
                $this->Cell(0,5,exportPdfText($this->madFooter.' · Página '.$this->PageNo()),0,0,'C');
            }
        }
    }
    $pdf=new MADExportPdf('L','mm','A4');$pdf->SetAutoPageBreak(true,14);$pdf->AddPage();$logo=brandingLogoAbsolute($branding);[$ar,$ag,$ab]=exportRgb($branding['color_principal']);[$dr,$dg,$db]=exportRgb($branding['color_sidebar']);[$sr,$sg,$sb]=exportRgb($branding['color_exito']);
    if($report==='historial'&&$studentSummary){
        if($logo!==''){try{[$lw,$lh,$lx,$ly]=brandingFitImage($logo,22,22);$pdf->Image($logo,12+$lx,10+$ly,$lw,$lh);}catch(Throwable){}}
        $pdf->SetXY($logo!==''?40:12,12);$pdf->SetTextColor(24,34,53);$pdf->SetFont('Arial','B',20);$pdf->Cell(0,9,exportPdfText($title),0,1);
        $pdf->SetX($logo!==''?40:12);$pdf->SetFont('Arial','B',10);$pdf->SetTextColor(79,89,107);$pdf->Cell(0,6,exportPdfText('Ciclo Escolar '.$cycle['nombre'].' | Fecha de Emisión: '.date('d/m/Y')),0,1);
        $pdf->SetFillColor($ar,$ag,$ab);$pdf->Rect(12,36,273,2,'F');$pdf->SetDrawColor($ar,$ag,$ab);$pdf->SetLineWidth(.5);$pdf->Line(12,39,285,39);
        $pdf->SetY(45);$pdf->SetFillColor($dr,$dg,$db);$pdf->SetTextColor(...exportRgb(brandingBestTextColor($branding['color_sidebar'])));$pdf->SetFont('Arial','B',11);$pdf->Cell(273,11,exportPdfText('Alumno: '.$studentSummary['student']['nombre_completo'].' | Grupo: '.$studentSummary['student']['grupo_nombre']),0,1,'L',true);
        $pdf->SetTextColor(52,60,74);$pdf->SetFillColor(244,246,249);$pdf->SetDrawColor(218,222,231);$pdf->SetFont('Arial','B',8);
        $count=count($headers);$firstW=46;$lastW=42;$midW=$count>2?(273-$firstW-$lastW)/($count-2):0;$widths=[];for($i=0;$i<$count;$i++)$widths[]=$i===0?$firstW:($i===$count-1?$lastW:$midW);
        foreach($headers as $i=>$h)$pdf->Cell($widths[$i],14,exportPdfText($h),1,0,'C',true);$pdf->Ln();$pdf->SetFont('Arial','',9);$pdf->SetFillColor(255,255,255);
        foreach($data as $line){foreach($line as $i=>$v)$pdf->Cell($widths[$i],10,exportPdfText($v===''?'Pendiente':(string)$v),1,0,'C',true);$pdf->Ln();}
        $pdf->SetFont('Arial','B',10);$pdf->Cell(273-$lastW,11,exportPdfText('PROMEDIO FINAL DEL CICLO:'),1,0,'R');$pdf->SetFillColor(234,248,240);$pdf->SetTextColor($sr,$sg,$sb);$pdf->Cell($lastW,11,exportPdfText($studentSummary['final']===null?'Pendiente':number_format((float)$studentSummary['final'],1)),1,1,'C',true);
        if(!$studentSummary['complete']){$pdf->SetTextColor(100,91,70);$pdf->SetFont('Arial','I',8);$pdf->Ln(3);$pdf->Cell(0,5,exportPdfText('Promedio parcial: existen calificaciones pendientes en este ciclo.'),0,1);}
    }else{
        $pdf->madFooter=$brandTitle.' · '.$title;
        $marginX=12;$tableWidth=273;
        $drawHeader=function()use($pdf,$brandTitle,$title,$cycle,$logo,$ar,$ag,$ab,$marginX,$tableWidth){
            if($logo!==''){try{[$lw,$lh,$lx,$ly]=brandingFitImage($logo,18,18);$pdf->Image($logo,$marginX+$lx,8+$ly,$lw,$lh);}catch(Throwable){}}
            $pdf->SetXY($logo!==''?36:$marginX,9);$pdf->SetTextColor($ar,$ag,$ab);$pdf->SetFont('Arial','B',15);$pdf->Cell(0,7,exportPdfText($brandTitle),0,1);
            $pdf->SetX($logo!==''?36:$marginX);$pdf->SetTextColor(55,64,81);$pdf->SetFont('Arial','B',10);$pdf->Cell(0,6,exportPdfText($title),0,1);
            $sub='Fecha de emisión: '.date('d/m/Y');if($cycle)$sub='Ciclo Escolar '.$cycle['nombre'].' · '.$sub;
            $pdf->SetX($logo!==''?36:$marginX);$pdf->SetTextColor(105,112,122);$pdf->SetFont('Arial','',8);$pdf->Cell(0,5,exportPdfText($sub),0,1);
            $pdf->SetFillColor($ar,$ag,$ab);$pdf->Rect($marginX,32,$tableWidth,2,'F');
        };
        $drawTableHeader=function()use($pdf,$headers,$tableWidth,$branding){
            $count=max(1,count($headers));$widths=[];
            if($count===3)$widths=[78,110,85];else{ $first=$count>=5?48:62;$remaining=$tableWidth-$first;$each=$remaining/max(1,$count-1);for($i=0;$i<$count;$i++)$widths[]=$i===0?$first:$each; }
            $pdf->SetFont('Arial','B',7.5);$pdf->SetFillColor(...exportRgb($branding['color_sidebar']));$pdf->SetTextColor(...exportRgb(brandingBestTextColor($branding['color_sidebar'])));
            foreach($headers as $i=>$h)$pdf->Cell($widths[$i],11,exportPdfText($h),1,0,'C',true);$pdf->Ln();
            return $widths;
        };
        $drawHeader();$pdf->SetY(40);$widths=$drawTableHeader();
        if(!$data){$pdf->SetFont('Arial','',9);$pdf->SetTextColor(80,88,100);$pdf->SetFillColor(248,249,251);$pdf->Cell($tableWidth,14,exportPdfText('No hay registros para exportar.'),1,1,'C',true);}
        else{
            $pdf->SetFont('Arial','',8);$pdf->SetTextColor(45,52,65);
            foreach($data as $r=>$line){
                if($pdf->GetY()>185){$pdf->AddPage();$drawHeader();$pdf->SetY(40);$widths=$drawTableHeader();}
                $pdf->SetFillColor($r%2===0?255:247,$r%2===0?255:249,$r%2===0?255:252);
                foreach($line as $i=>$value){$cell=(string)($value===''?'Pendiente':$value);$maxW=$widths[$i]-3;while(strlen($cell)>2&&$pdf->GetStringWidth(exportPdfText($cell))>$maxW){$cell=substr($cell,0,-2).'…';}$pdf->Cell($widths[$i],9,exportPdfText($cell),1,0,$i===0?'L':'C',true);}
                $pdf->Ln();
            }
        }
        $pdf->SetFont('Arial','I',7);$pdf->SetTextColor(105,112,122);$pdf->Ln(4);$pdf->Cell($tableWidth,4,exportPdfText('Documento generado por '.$brandTitle.'. Conserva este archivo como parte del expediente escolar.'),0,1,'L');
    }
    $pdf->Output('D',$brandSlug.'-'.$report.'.pdf');exit;
}catch(mysqli_sql_exception $ex){
    $code=(int)$ex->getCode();
    if($code===1146){exportError(new DomainException('La base de datos de Mi Aula Digital no está completamente actualizada para este reporte. Falta una tabla requerida; ejecuta las migraciones de la carpeta instalacion y vuelve a intentar.',500,$ex));}
    if($code===1054){exportError(new DomainException('La base de datos de Mi Aula Digital no está completamente actualizada para este reporte. Falta una columna requerida; ejecuta las migraciones de la carpeta instalacion y vuelve a intentar.',500,$ex));}
    exportError(new DomainException('No se pudo consultar la información necesaria para generar el reporte. Revisa que la base de datos esté actualizada y vuelve a intentar.',500,$ex));
}catch(Throwable $ex){exportError($ex);}
