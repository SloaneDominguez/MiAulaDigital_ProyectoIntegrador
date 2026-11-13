<?php
declare(strict_types=1);
$configFile = __DIR__.'/../config/database.local.php';
$config = is_file($configFile) ? require $configFile : [];
// Solo en tu computadora (XAMPP, localhost): si existe config/database.xampp.php se usa esa base local.
// En InfinityFree ese archivo no se sube, así que allá se sigue usando database.local.php.
$madHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
$xamppConfigFile = __DIR__.'/../config/database.xampp.php';
if (in_array($madHost, ['localhost','127.0.0.1','[::1]'], true) && is_file($xamppConfigFile)) {
    $config = array_replace($config, require $xamppConfigFile);
    $madLocalMode = true;
}
// En nube/producción, las variables de entorno pueden sustituir la configuración local
// sin cambiar el código ni guardar secretos dentro del proyecto.
$envConfig = [
    // Nombres usados por docker-compose del equipo (DB_HOST, DB_USER, DB_PASS, DB_NAME).
    // Van primero para que las variables MAD_* tengan prioridad si existen ambas.
    'DB_HOST'=>'host','DB_USER'=>'user','DB_PASS'=>'password','DB_NAME'=>'database',
    'MAD_DB_HOST'=>'host','MAD_DB_USER'=>'user','MAD_DB_PASSWORD'=>'password','MAD_DB_NAME'=>'database',
    'MAD_BASE_URL'=>'base_url','MAD_TIMEZONE'=>'timezone','MAD_GOOGLE_CLIENT_ID'=>'google_client_id',
    'MAD_GOOGLE_CLIENT_SECRET'=>'google_client_secret','MAD_GOOGLE_REDIRECT_URI'=>'google_redirect_uri',
    'MAD_CLOUD_PROVIDER'=>'cloud_provider',
];
if(function_exists('getenv')){foreach($envConfig as $env=>$key){$value=getenv($env);if($value!==false&&$value!=='')$config[$key]=$value;}}

// Si algo falla antes de mostrar la página (por ejemplo, la conexión a MySQL),
// se muestra un mensaje claro en lugar de la pantalla genérica "HTTP ERROR 500".
set_exception_handler(function(Throwable $ex): void {
    error_log('MAD fatal: '.$ex->getMessage().' @ '.$ex->getFile().':'.$ex->getLine());
    if(!headers_sent()){http_response_code(500);header('Content-Type: text/html; charset=utf-8');}
    $msg=($ex instanceof RuntimeException && !($ex instanceof mysqli_sql_exception)) ? $ex->getMessage() : 'Ocurrió un error interno. Revisa la configuración del servidor.';
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Mi Aula Digital</title>'
        .'<body style="font-family:system-ui,sans-serif;max-width:640px;margin:60px auto;padding:0 20px">'
        .'<h1>Mi Aula Digital no pudo iniciar</h1><p>'.htmlspecialchars($msg,ENT_QUOTES,'UTF-8').'</p>'
        .'<p>Verifica los datos de <code>config/database.local.php</code> (host, usuario, contraseña y nombre de la base).</p>'
        .($GLOBALS['madLocalMode'] ?? false ? '<p style="padding:14px;background:#fff6e8;border-radius:12px"><strong>Estás en tu computadora (XAMPP).</strong><br>Abre el XAMPP Control Panel y da clic en <strong>Start</strong> en Apache y en MySQL. Revisa en phpMyAdmin que exista la base <strong>mi_aula_digital_nueva</strong>.</p>' : '')
        .'</body></html>';
});
date_default_timezone_set((string)($config['timezone'] ?? 'America/Mexico_City'));

/**
 * Obtiene la ruta web base de la aplicación.
 * - Si base_url contiene una ruta explícita, la respeta.
 * - Si vale "auto" o está vacío, la calcula desde DOCUMENT_ROOT.
 * Esto evita errores al mover el proyecto entre XAMPP y el hosting.
 */
function resolveBaseUrl(array $config): string {
    $configured = trim((string)($config['base_url'] ?? 'auto'));
    if ($configured !== '' && strcasecmp($configured, 'auto') !== 0) {
        $configured = str_replace('\\', '/', $configured);
        $configured = '/'.trim($configured, '/');
        return $configured === '/' ? '' : rtrim($configured, '/');
    }

    $projectRoot = realpath(__DIR__.'/..') ?: dirname(__DIR__);
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
    if ($documentRoot) {
        $project = str_replace('\\', '/', $projectRoot);
        $docroot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        if (strncasecmp($project, $docroot, strlen($docroot)) === 0) {
            $relative = trim(substr($project, strlen($docroot)), '/');
            return $relative === '' ? '' : '/'.$relative;
        }
    }

    // Respaldo para servidores donde DOCUMENT_ROOT no coincide con la ruta real.
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    foreach (['/admin','/docente','/tutor','/process'] as $suffix) {
        if ($dir !== '' && str_ends_with($dir, $suffix)) {
            $dir = substr($dir, 0, -strlen($suffix));
            break;
        }
    }
    return $dir === '/' ? '' : $dir;
}

define('BASE', resolveBaseUrl($config));
function url(string $path=''): string { return (BASE === '' ? '' : BASE).'/'.ltrim($path,'/'); }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $path): never { header('Location: '.url($path)); exit; }
const MAD_IDLE_TIMEOUT = 1800; // 30 minutos sin actividad real.
const MAD_SESSION_STORAGE_TTL = 7200; // Evita que el recolector del hosting borre la sesión antes que la aplicación.
ini_set('session.use_strict_mode','1');
ini_set('session.gc_maxlifetime',(string)MAD_SESSION_STORAGE_TTL);
session_name('MAD_SESSION_V2');
session_set_cookie_params([
    'lifetime'=>0,
    'path'=>BASE ?: '/',
    'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
    'httponly'=>true,
    'samesite'=>'Lax'
]);
session_start();
if (isset($_SESSION['last_activity']) && time()-(int)$_SESSION['last_activity']>MAD_IDLE_TIMEOUT) {
    $_SESSION=[];
    session_regenerate_id(true);
}
$_SESSION['last_activity']=time();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');
$brandingPreview = isset($_GET['vista']) && is_string($_GET['vista']) && $_GET['vista'] === 'personalizacion';
$styleAttrPolicy = $brandingPreview ? " style-src-attr 'unsafe-inline';" : '';
header("Content-Security-Policy: default-src 'self'; style-src 'self'; style-src-elem 'self';".$styleAttrPolicy." script-src 'self'; img-src 'self' data: blob:; font-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('Cache-Control: no-store');
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function validateCsrf(): void {
    if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') throw new DomainException('Esta operación requiere POST.',405);
    $token=$_POST['csrf']??$_SERVER['HTTP_X_CSRF_TOKEN']??null;
    if (!is_string($token)||!isset($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'],$token)) throw new DomainException('Tu sesión cambió. Recarga la página e inténtalo de nuevo.',403);
}
function db(): mysqli {
    static $db;
    global $config;
    if ($db) return $db;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try { $db=@new mysqli($config['host']??'localhost',$config['user']??'', $config['password']??'', $config['database']??''); $db->set_charset('utf8mb4'); $zone=date('P'); $tz=$db->prepare('SET time_zone=?'); $tz->bind_param('s',$zone); $tz->execute(); }
    catch (Throwable $ex) { error_log('MAD database: '.$ex->getMessage()); throw new RuntimeException('No se pudo conectar con la base de datos. Revisa la configuración del hosting.'); }
    return $db;
}
function query(string $sql, array $params=[]): mysqli_stmt {
    $s=db()->prepare($sql);
    if ($params) { $types=''; foreach($params as $p) $types.=is_int($p)?'i':(is_float($p)?'d':'s'); $s->bind_param($types,...$params); }
    $s->execute(); return $s;
}
function rows(string $sql,array $params=[]): array { return query($sql,$params)->get_result()->fetch_all(MYSQLI_ASSOC); }
function one(string $sql,array $params=[]): ?array { return rows($sql,$params)[0]??null; }
function transaction(callable $fn): mixed { db()->begin_transaction(); try { $r=$fn(); db()->commit(); return $r; } catch(Throwable $ex) { db()->rollback(); throw $ex; } }
function currentUser(): ?array {
    if (!isset($_SESSION['id_usuario'])) return null;
    $u=one('SELECT ID_Usuario,Nombre,Usuario,Correo,Rol,cambiar_clave,sesion_version FROM usuario WHERE ID_Usuario=? AND activo=1',[(int)$_SESSION['id_usuario']]);
    if(!$u || (int)($_SESSION['sesion_version']??0)!==(int)$u['sesion_version']) { unset($_SESSION['id_usuario'],$_SESSION['rol']); return null; }
    return $u;
}
function requireUser(array $roles=[],bool $allowTemporary=false): array {
    $u=currentUser();
    if (!$u) throw new DomainException('Inicia sesión para continuar.',401);
    if($roles && !in_array($u['Rol'],$roles,true)) throw new DomainException('No tienes permiso para esta operación.',403);
    if(!$allowTemporary && $u['cambiar_clave']) throw new DomainException('Debes cambiar tu contraseña temporal desde Mi cuenta.',428);
    return $u;
}
function profile(array $u): array {
    $tables=['Direccion'=>['director','ID_Director'],'Docente'=>['docente','ID_Docente'],'PadreTutor'=>['padre_tutor','ID_Padre']];
    [$table,$key]=$tables[$u['Rol']];
    $p=one("SELECT * FROM $table WHERE ID_Usuario=?",[(int)$u['ID_Usuario']]);
    if(!$p) throw new DomainException('Tu cuenta no tiene un perfil asociado. Solicita ayuda a Dirección.',403);
    return $p;
}
function homeFor(array $u): string { return ['Direccion'=>'admin','Docente'=>'docente','PadreTutor'=>'tutor'][$u['Rol']].'/dashboard.php'; }
function textInput(string $key,int $max,bool $required=true): string {
    $v=$_POST[$key]??'';
    if(!is_string($v)) throw new DomainException('Formato inválido.');
    $v=trim($v);
    if(($required && $v==='')||mb_strlen($v)>$max) throw new DomainException("Revisa el campo $key (máximo $max caracteres).");
    return $v;
}
function passwordInput(string $key,int $min=0,int $max=72): string {
    $v=$_POST[$key]??'';
    if(!is_string($v)||strlen($v)>$max||mb_strlen($v)<$min) throw new DomainException('La contraseña debe tener al menos '.$min.' caracteres y como máximo '.$max.' bytes.');
    return $v;
}
function idInput(string $key,bool $optional=false): ?int {
    $v=$_POST[$key]??null;
    if($optional && ($v===null||$v==='')) return null;
    $id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if($id===false) throw new DomainException('Selecciona una opción válida.');
    return $id;
}
function dateInput(string $key): string {
    $v=textInput($key,10); $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if(!$d||$d->format('Y-m-d')!==$v) throw new DomainException('La fecha no es válida.'); return $v;
}
function studentAllowed(array $u,int $id,bool $lock=false): array {
    $a=studentAccess($u,$id,activeCycleId(),false,$lock);return $a;
}
function studentAccess(array $u,int $id,?int $cycle=null,bool $archived=true,bool $lock=false): array {
    $a=one('SELECT * FROM alumno WHERE ID_Alumno=?'.($archived?'':' AND activo=1').($lock?' FOR UPDATE':''),[$id]);
    if(!$a) throw new DomainException('El alumno no está disponible.',404);
    $p=profile($u);
    if($u['Rol']==='PadreTutor'&&!one('SELECT 1 FROM alumno_tutor WHERE ID_Alumno=? AND ID_Padre=?',[$id,(int)$p['ID_Padre']])) throw new DomainException('Alumno no vinculado a tu cuenta.',403);
    if($u['Rol']==='Docente'&&!one('SELECT 1 FROM inscripcion i JOIN asignacion_ciclo d ON d.id_ciclo=i.id_ciclo AND d.ID_Grupo=i.ID_Grupo WHERE i.ID_Alumno=? AND i.id_ciclo=? AND d.ID_Docente=?',[$id,$cycle??activeCycleId(),(int)$p['ID_Docente']])) throw new DomainException('No tienes asignado este alumno en el ciclo seleccionado.',403);
    return $a;
}
function activeCycleId(): int { return (int)(one("SELECT id_ciclo FROM ciclo_escolar WHERE estado='ACTIVO' ORDER BY id_ciclo DESC LIMIT 1")['id_ciclo']??0); }
function students(array $u,?int $cycle=null,bool $archived=false,?int $limit=null,int $offset=0,string $search='',int $group=0,bool $countOnly=false): array {
    $cycle??=(int)(selectedCycle()['id_ciclo']??0);$p=profile($u);$params=[$cycle];$where=$archived?'1=1':'a.activo=1';
    if($cycle>0)$where.=' AND i.id_ciclo IS NOT NULL';
    if($u['Rol']==='Docente'){ $where.=' AND EXISTS(SELECT 1 FROM asignacion_ciclo ac WHERE ac.id_ciclo=i.id_ciclo AND ac.ID_Grupo=i.ID_Grupo AND ac.ID_Docente=?)';$params[]=(int)$p['ID_Docente']; }
    if($u['Rol']==='PadreTutor'){ $where.=' AND EXISTS(SELECT 1 FROM alumno_tutor at WHERE at.ID_Alumno=a.ID_Alumno AND at.ID_Padre=?)';$params[]=(int)$p['ID_Padre']; }
    if($group>0){$where.=' AND i.ID_Grupo=?';$params[]=$group;}
    if($countOnly)return rows("SELECT COUNT(*) total FROM alumno a LEFT JOIN inscripcion i ON i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? WHERE $where",$params);
    if($search!==''){ $where.=" AND CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido,a.CURP) LIKE ?";$params[]='%'.$search.'%'; }
    return rows("SELECT a.*,CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre_completo,COALESCE(CONCAT(gi.Grado,'° ',gi.Grupo),i.grupo_nombre,CONCAT(g.Grado,'° ',g.Grupo)) grupo,i.ID_Grupo grupo_ciclo,i.verificado FROM alumno a LEFT JOIN inscripcion i ON i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? LEFT JOIN grupo gi ON gi.ID_Grupo=i.ID_Grupo LEFT JOIN grupo g ON g.ID_Grupo=a.ID_Grupo WHERE $where ORDER BY a.Primer_Apellido,a.Nombre,a.ID_Alumno".($limit?' LIMIT '.max(1,min(500,$limit)).' OFFSET '.max(0,$offset):''),$params);
}
function cycles(): array { return rows('SELECT * FROM ciclo_escolar ORDER BY fecha_inicio DESC'); }
function selectedCycle(): ?array {
    $all=cycles(); $id=filter_var($_GET['ciclo']??null,FILTER_VALIDATE_INT);
    if($id) foreach($all as $c) if((int)$c['id_ciclo']===$id) return $c;
    foreach($all as $c) if($c['estado']==='ACTIVO') return $c;
    return $all[0]??null;
}
function activeCycle(int $id,bool $lock=false): array {
    $c=one('SELECT * FROM ciclo_escolar WHERE id_ciclo=?'.($lock?' FOR UPDATE':''),[$id]);
    if(!$c||$c['estado']!=='ACTIVO') throw new DomainException('Solo puedes capturar calificaciones en un ciclo activo.'); return $c;
}
function throttleKey(string $scope,string $account): string {
    return hash('sha256',$scope.'|'.($_SERVER['REMOTE_ADDR']??'unknown').'|'.mb_strtolower($account));
}
function throttle(string $scope,string $account,int $limit): void {
    $key=throttleKey($scope,$account);
    query('INSERT INTO intento_acceso (clave,intentos,ultima_fecha) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE intentos=IF(ultima_fecha < DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,intentos+1),ultima_fecha=NOW()',[$key]);
    $r=one('SELECT intentos FROM intento_acceso WHERE clave=?',[$key]);
    if((int)($r['intentos']??0)>$limit) throw new DomainException('Demasiados intentos. Espera 15 minutos antes de volver a intentar.',429);
    query('DELETE FROM intento_acceso WHERE ultima_fecha < DATE_SUB(NOW(),INTERVAL 1 DAY) LIMIT 100');
}
/** Comprueba el bloqueo de login sin contar un acceso correcto como intento fallido. */
function loginThrottleGuard(string $scope,string $account,int $limit): void {
    $key=throttleKey($scope,$account);
    $r=one('SELECT intentos,ultima_fecha FROM intento_acceso WHERE clave=?',[$key]);
    if(!$r) return;
    if(strtotime((string)$r['ultima_fecha']) < time()-900) {
        query('DELETE FROM intento_acceso WHERE clave=?',[$key]);
        return;
    }
    if((int)$r['intentos'] >= $limit) throw new DomainException('Demasiados intentos. Espera 15 minutos antes de volver a intentar.',429);
}
function loginThrottleFail(string $scope,string $account): void {
    $key=throttleKey($scope,$account);
    query('INSERT INTO intento_acceso (clave,intentos,ultima_fecha) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE intentos=IF(ultima_fecha < DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,intentos+1),ultima_fecha=NOW()',[$key]);
}
function loginThrottleReset(string $scope,string $account): void {
    query('DELETE FROM intento_acceso WHERE clave=?',[throttleKey($scope,$account)]);
}
function reply(array $data,int $status=200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function errorReply(Throwable $ex): never {
    if($ex instanceof DomainException){$code=(int)$ex->getCode();if($code<400||$code>599)$code=422;reply(['ok'=>false,'message'=>$ex->getMessage()],$code);}
    error_log('MAD operation: '.$ex->getMessage());
    if($ex instanceof mysqli_sql_exception && $ex->getCode()===1062) reply(['ok'=>false,'message'=>'Ese usuario, correo o registro ya existe. Revisa los datos.'],409);
    reply(['ok'=>false,'message'=>'No se pudo completar la operación. Revisa la configuración y la migración de la base de datos.'],500);
}

require_once __DIR__.'/academic.php';
require_once __DIR__.'/branding.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/password_strength.php';
require_once __DIR__.'/school_rules.php';
