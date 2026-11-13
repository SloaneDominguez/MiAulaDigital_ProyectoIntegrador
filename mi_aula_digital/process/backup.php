<?php
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/../app/backup.php';
try{validateCsrf();$u=requireUser(['Direccion']);throttle('backup',(string)$u['ID_Usuario'],4);$pass=passwordInput('password',1,200);$r=one('SELECT Contrasena FROM usuario WHERE ID_Usuario=?',[(int)$u['ID_Usuario']]);if(!password_verify($pass,$r['Contrasena']))throw new DomainException('Contraseña incorrecta.');
// Se termina el respaldo antes de enviar cabeceras para no entregar archivos a medias.
$tmp=tmpfile();if(!$tmp)throw new RuntimeException('No se pudo crear el respaldo.');$counts=writeBackup($tmp);audit($u,'DESCARGAR_RESPALDO','base',null,['tablas'=>count($counts)]);rewind($tmp);header('Content-Type: application/sql; charset=utf-8');header('Content-Disposition: attachment; filename="miauladigital-'.date('Ymd-His').'.sql"');fpassthru($tmp);fclose($tmp);exit;
}catch(Throwable $ex){errorReply($ex);}
