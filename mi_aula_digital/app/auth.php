<?php

declare(strict_types=1);

/**
 * Acceso local / Google.
 * Google se activa solamente cuando Dirección configura credenciales válidas.
 */
function authEnsureSchema(): void {
    static $done=false;
    if($done)return;
    $cols=rows("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuario' AND COLUMN_NAME IN ('metodo_acceso','google_sub','correo_verificado')");
    $have=array_fill_keys(array_column($cols,'COLUMN_NAME'),true);
    if(!isset($have['metodo_acceso']))query("ALTER TABLE usuario ADD COLUMN metodo_acceso ENUM('local','google','ambos') NOT NULL DEFAULT 'local'");
    if(!isset($have['google_sub']))query("ALTER TABLE usuario ADD COLUMN google_sub VARCHAR(255) NULL");
    if(!isset($have['correo_verificado']))query("ALTER TABLE usuario ADD COLUMN correo_verificado TINYINT(1) NOT NULL DEFAULT 0");
    $index=one("SELECT 1 ok FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuario' AND index_name='uk_usuario_google_sub' LIMIT 1");
    if(!$index)query('ALTER TABLE usuario ADD UNIQUE KEY uk_usuario_google_sub (google_sub)');
    $done=true;
}

function authAccessMethod(array $u): string {
    $method=(string)($u['metodo_acceso']??'local');
    return in_array($method,['local','google','ambos'],true)?$method:'local';
}
function authAllowsLocal(array $u): bool { return in_array(authAccessMethod($u),['local','ambos'],true); }
function authAllowsGoogle(array $u): bool { return in_array(authAccessMethod($u),['google','ambos'],true); }
function authMethodLabel(string $method): string { return ['local'=>'Local','google'=>'Google','ambos'=>'Local + Google'][$method]??'Local'; }

function googleAuthConfigured(): bool {
    global $config;
    return trim((string)($config['google_client_id']??''))!=='' && trim((string)($config['google_client_secret']??''))!=='';
}
function googleRedirectUri(): string {
    global $config;
    $configured=trim((string)($config['google_redirect_uri']??'auto'));
    if($configured!==''&&strcasecmp($configured,'auto')!==0)return $configured;
    $https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
    $scheme=$https?'https':'http';
    $host=(string)($_SERVER['HTTP_HOST']??'localhost');
    return $scheme.'://'.$host.url('auth/google_callback.php');
}
function googleAuthUrl(): string {
    global $config;
    if(!googleAuthConfigured())throw new DomainException('El acceso con Google todavía no está configurado por la escuela.',503);
    $_SESSION['google_oauth_state']=bin2hex(random_bytes(24));
    $_SESSION['google_oauth_started']=time();
    return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
        'client_id'=>(string)$config['google_client_id'],
        'redirect_uri'=>googleRedirectUri(),
        'response_type'=>'code',
        'scope'=>'openid email profile',
        'state'=>$_SESSION['google_oauth_state'],
        'prompt'=>'select_account',
        'access_type'=>'online',
    ],'', '&', PHP_QUERY_RFC3986);
}

function authHttpJson(string $url, array $post=[]): array {
    $headers=['Accept: application/json'];
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true]);
        if($post){curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($post,'','&',PHP_QUERY_RFC3986));}
        $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
        if($raw===false||$status<200||$status>=300)throw new RuntimeException('No se pudo validar el acceso con Google. '.($error?:'Respuesta HTTP '.$status));
    }else{
        $context=stream_context_create(['http'=>['method'=>$post?'POST':'GET','timeout'=>15,'header'=>"Accept: application/json\r\n".($post?"Content-Type: application/x-www-form-urlencoded\r\n":''),'content'=>$post?http_build_query($post,'','&',PHP_QUERY_RFC3986):''],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
        $raw=@file_get_contents($url,false,$context);
        if($raw===false)throw new RuntimeException('No se pudo validar el acceso con Google.');
    }
    $data=json_decode((string)$raw,true);
    if(!is_array($data))throw new RuntimeException('Google devolvió una respuesta inesperada.');
    return $data;
}

function googleExchangeCode(string $code): array {
    global $config;
    $token=authHttpJson('https://oauth2.googleapis.com/token',[
        'code'=>$code,
        'client_id'=>(string)$config['google_client_id'],
        'client_secret'=>(string)$config['google_client_secret'],
        'redirect_uri'=>googleRedirectUri(),
        'grant_type'=>'authorization_code',
    ]);
    $idToken=(string)($token['id_token']??'');
    if($idToken==='')throw new DomainException('Google no devolvió una identidad verificable.',401);
    $claims=authHttpJson('https://oauth2.googleapis.com/tokeninfo?id_token='.rawurlencode($idToken));
    if((string)($claims['aud']??'')!==(string)$config['google_client_id'])throw new DomainException('La respuesta de Google no corresponde a esta aplicación.',401);
    $issuer=(string)($claims['iss']??'');
    if(!in_array($issuer,['accounts.google.com','https://accounts.google.com'],true))throw new DomainException('Proveedor de identidad no válido.',401);
    if(!in_array((string)($claims['email_verified']??''),['true','1'],true))throw new DomainException('Tu correo de Google debe estar verificado.',403);
    $sub=trim((string)($claims['sub']??''));$email=mb_strtolower(trim((string)($claims['email']??'')));
    if($sub===''||$email===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new DomainException('Google no devolvió un correo válido.',401);
    return ['sub'=>$sub,'email'=>$email,'name'=>(string)($claims['name']??'')];
}

function authStartSession(array $u): never {
    profile($u);
    $_SESSION=[];
    session_regenerate_id(true);
    $_SESSION['id_usuario']=(int)$u['ID_Usuario'];
    $_SESSION['sesion_version']=(int)$u['sesion_version'];
    $_SESSION['rol']=$u['Rol'];
    $_SESSION['csrf']=bin2hex(random_bytes(32));
    $_SESSION['last_activity']=time();
    go($u['cambiar_clave']?'cuenta.php':homeFor($u));
}

function googleAuthenticate(array $identity): array {
    authEnsureSchema();
    $u=one('SELECT * FROM usuario WHERE google_sub=? AND activo=1 LIMIT 1',[$identity['sub']]);
    if(!$u){
        $u=one('SELECT * FROM usuario WHERE LOWER(Correo)=? AND activo=1 LIMIT 1',[$identity['email']]);
        if(!$u)throw new DomainException('Tu cuenta de Google aún no está autorizada por esta escuela.',403);
        if(!authAllowsGoogle($u))throw new DomainException('Esta cuenta está configurada para iniciar con usuario y contraseña. Dirección puede habilitar Google.',403);
        if(!empty($u['google_sub'])&&$u['google_sub']!==$identity['sub'])throw new DomainException('Esta cuenta ya está vinculada a otro acceso de Google.',409);
        query('UPDATE usuario SET google_sub=?,correo_verificado=1 WHERE ID_Usuario=?',[$identity['sub'],(int)$u['ID_Usuario']]);
        $u=one('SELECT * FROM usuario WHERE ID_Usuario=?',[(int)$u['ID_Usuario']]);
    }
    if(!authAllowsGoogle($u))throw new DomainException('El acceso con Google está deshabilitado para esta cuenta.',403);
    if((string)$u['Correo']!==''&&mb_strtolower((string)$u['Correo'])!==$identity['email'])throw new DomainException('El correo de Google ya no coincide con la cuenta autorizada. Solicita ayuda a Dirección.',403);
    query('UPDATE usuario SET correo_verificado=1 WHERE ID_Usuario=?',[(int)$u['ID_Usuario']]);
    return $u;
}
