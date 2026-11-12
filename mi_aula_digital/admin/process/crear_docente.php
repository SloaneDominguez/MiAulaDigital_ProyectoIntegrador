<?php
require __DIR__.'/../../app/bootstrap.php';
try {
    requireUser(['Direccion']);
    throw new DomainException('Esta ruta pertenece a una versión anterior de Mi Aula Digital. Usa el panel actual.',404);
} catch (Throwable $ex) {
    errorReply($ex);
}
