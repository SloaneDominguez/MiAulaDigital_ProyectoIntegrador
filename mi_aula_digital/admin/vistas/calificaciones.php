<?php
require __DIR__."/../../app/bootstrap.php";
try { requireUser(["Direccion"]); throw new DomainException("Esta ruta fue reemplazada. Usa el nuevo panel.", 404); } catch(Throwable $ex) { errorReply($ex); }
