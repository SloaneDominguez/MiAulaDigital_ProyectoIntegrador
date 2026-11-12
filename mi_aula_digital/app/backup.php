<?php
function writeBackup($stream): array {
 $branding=schoolBranding();$system=preg_replace('/[\r\n]+/',' ',(string)$branding['nombre_sistema']);
 $tables=array_map(fn($r)=>array_values($r)[0],rows('SHOW TABLES'));$counts=[];
 db()->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');db()->query('START TRANSACTION WITH CONSISTENT SNAPSHOT');
 try{
  fwrite($stream,"-- ".$system." · respaldo privado\n-- ".gmdate('c')."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
  foreach($tables as $table){$quoted='`'.str_replace('`','``',$table).'`';$ddl=array_values(one('SHOW CREATE TABLE '.$quoted))[1];fwrite($stream,$ddl.";\n");$result=db()->query('SELECT * FROM '.$quoted,MYSQLI_USE_RESULT);$count=0;
   while($row=$result->fetch_assoc()){$columns=implode(',',array_map(fn($k)=>'`'.str_replace('`','``',$k).'`',array_keys($row)));$values=implode(',',array_map(fn($v)=>$v===null?'NULL':"'".db()->real_escape_string((string)$v)."'",array_values($row)));fwrite($stream,'INSERT INTO '.$quoted.' ('.$columns.') VALUES ('.$values.");\n");$count++;}
   $result->free();$counts[$table]=$count;
  }
  fwrite($stream,"SET FOREIGN_KEY_CHECKS=1;\n");db()->commit();return $counts;
 }catch(Throwable $ex){db()->rollback();throw $ex;}
}
