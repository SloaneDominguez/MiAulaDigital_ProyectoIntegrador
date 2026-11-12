<?php
require __DIR__.'/../app/bootstrap.php';
try { validateCsrf(); $_SESSION=[]; session_destroy(); setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>BASE?:'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']); go('index.php'); }
catch(Throwable $ex) { errorReply($ex); }
