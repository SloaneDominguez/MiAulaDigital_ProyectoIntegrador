<?php
require __DIR__.'/../app/bootstrap.php';
try{
    if(currentUser())go(homeFor(currentUser()));
    authEnsureSchema();
    $state=$_GET['state']??'';$saved=$_SESSION['google_oauth_state']??'';$started=(int)($_SESSION['google_oauth_started']??0);
    unset($_SESSION['google_oauth_state'],$_SESSION['google_oauth_started']);
    if(!is_string($state)||!is_string($saved)||$state===''||!hash_equals($saved,$state)||$started<time()-600)throw new DomainException('La solicitud de Google venció. Inténtalo nuevamente.',401);
    if(isset($_GET['error']))throw new DomainException('El acceso con Google fue cancelado.',401);
    $code=$_GET['code']??'';if(!is_string($code)||$code==='')throw new DomainException('Google no devolvió el código de acceso.',401);
    $identity=googleExchangeCode($code);$u=googleAuthenticate($identity);authStartSession($u);
}catch(DomainException $ex){
    error_log('MAD Google auth: '.$ex->getMessage());
    $reason=$ex->getCode()===403?'no_autorizado':'error';
    go('index.php?google='.$reason);
}catch(Throwable $ex){
    error_log('MAD Google auth: '.$ex->getMessage());
    go('index.php?google=error');
}
