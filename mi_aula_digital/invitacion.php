<?php
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/ui.php';
$code=is_string($_GET['codigo']??null)?trim($_GET['codigo']):'';
$invite=$code!==''?findInvitation($code):null;
head('Crear mi contraseña'); ?>
<main class="register-shell"><?php brand(); ?><section class="auth-card glass">
<?php if(!$invite||!$invite['activo']): ?>
 <div class="auth-icon"><?=icon('key')?></div>
 <h2>Esta invitación ya no es válida</h2>
 <p>El enlace venció, ya se usó o fue reemplazado por uno nuevo. Pide a Dirección que te envíe otra invitación.</p>
 <div class="auth-links"><a class="auth-link-button single" href="<?=e(url('index.php'))?>"><?=icon('back')?><span>Ir a iniciar sesión</span></a></div>
<?php else: ?>
 <div class="auth-icon"><?=icon('shield')?></div>
 <h2>Hola, <?=e(explode(' ',(string)$invite['Nombre'])[0])?>.</h2>
 <p>Dirección te registró. Crea tu contraseña para entrar. Tu usuario es <strong><?=e($invite['Usuario'])?></strong> (también puedes entrar con tu correo).</p>
 <form action="<?=e(url('process/invitacion.php'))?>" method="post" data-api>
  <?=csrf()?><input type="hidden" name="codigo" value="<?=e($code)?>">
  <?php field('password','Crea tu contraseña','password','',true,72,'',['data-strength'=>'1','data-personal'=>json_encode([$invite['Nombre'],$invite['Usuario'],$invite['Correo']],JSON_UNESCAPED_UNICODE)]); ?>
  <?php field('confirmar','Confirma tu contraseña','password','',true,72,'',['data-match'=>'password']); ?>
  <?php feedback(); ?>
  <button type="submit" class="button primary"><?=icon('check')?>Guardar contraseña y entrar</button>
 </form>
<?php endif; ?>
</section></main><?php foot();
