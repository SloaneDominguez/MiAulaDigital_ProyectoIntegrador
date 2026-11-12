<?php
require __DIR__.'/../app/bootstrap.php';
try {
    $u=requireUser([],true);
    reply([
        'ok'=>true,
        'user'=>(int)$u['ID_Usuario'],
        'role'=>$u['Rol'],
    ]);
} catch(Throwable $ex) {
    errorReply($ex);
}
