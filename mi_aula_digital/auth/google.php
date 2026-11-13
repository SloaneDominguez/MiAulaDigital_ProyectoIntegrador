<?php
require __DIR__.'/../app/bootstrap.php';
try{
    if(currentUser())go(homeFor(currentUser()));
    authEnsureSchema();
    header('Location: '.googleAuthUrl());exit;
}catch(Throwable $ex){
    error_log('MAD Google start: '.$ex->getMessage());
    go('index.php?google=config');
}
