<?php
require __DIR__.'/bootstrap.php'; require __DIR__.'/ui.php';
require_once __DIR__.'/mailer.php'; require_once __DIR__.'/email_verification.php';
$type=$registrationType; $teacher=$type==='docente';
registrationFormStamp($type);
$mailReady=mailAvailable();
$pending=$mailReady?verificationState($type):[];
head($teacher?'Registro docente':'Registro de familia'); ?>
<main class="register-shell"><?php brand(); ?>
<section class="auth-card glass register-card" data-register>
 <ol class="register-progress" aria-label="Pasos del registro">
  <li class="<?=$pending?'done':'current'?>" data-progress="datos"><span>1</span>Tus datos</li>
  <li class="<?=$pending?'current':''?>" data-progress="codigo"><span>2</span>Confirma tu correo</li>
  <li data-progress="final"><span>3</span><?=$teacher?'Revisión de Dirección':'Inicia sesión'?></li>
 </ol>

 <div data-register-step="form"<?=$pending?' hidden':''?>>
  <h2><?=$teacher?'Enseñar empieza aquí.':'Acompaña cada paso.'?></h2>
  <p><?=$teacher?'Envía tu solicitud. Primero confirmaremos tu correo y después Dirección aprobará tu acceso.':'Crea tu cuenta y vincula a tu hijo con el código de la escuela. Primero confirmaremos tu correo.'?></p>
  <?php if(!$mailReady): ?><div class="notice" role="alert"><strong>El registro no está disponible por ahora.</strong><br>La escuela todavía no configura el envío de correos de verificación. Avisa a Dirección.</div><?php endif; ?>
  <form action="<?=e(url('process/registro.php'))?>" method="post" data-api data-stay="true" data-register-form><?=csrf()?>
   <input type="hidden" name="tipo" value="<?=e($type)?>"><input type="hidden" name="paso" value="solicitar">
   <div class="hp-field" aria-hidden="true"><label>No llenes este campo<input type="text" name="sitio_web" tabindex="-1" autocomplete="off"></label></div>
   <div class="form-grid">
   <?php field('nombre','Nombre(s)','text','',true,50); field('primer_apellido','Primer apellido','text','',true,50); field('segundo_apellido','Segundo apellido','text','',false,50); field('correo','Correo electrónico','email','',true,100,'Usa un correo real: te enviaremos un código.',['autocomplete'=>'email']); field('usuario','Usuario','text','',true,50,'Sin espacios ni acentos. Puedes usar punto o guion bajo, p. ej. miauladigital',['autocomplete'=>'username','pattern'=>'[A-Za-z0-9_.\-]{4,50}','title'=>'De 4 a 50 caracteres: letras sin acento, números, punto, guion o guion bajo. Sin espacios.','autocapitalize'=>'none','spellcheck'=>'false','data-username'=>'1']); if(!$teacher)field('telefono','Teléfono celular','tel','',true,20,'10 dígitos. Cada familia usa un teléfono diferente.',['autocomplete'=>'tel']); echo '<div class="full">'; field('password','Contraseña','password','',true,72,'',['data-strength'=>'1']); echo '</div><div class="full">'; field('confirmar_password','Confirma tu contraseña','password','',true,72,'',['data-match'=>'password']); echo '</div>'; ?>
   </div>
   <?php feedback(); ?>
   <button type="submit" class="button primary"<?=$mailReady?'':' disabled'?>><?=icon('mail')?><?=$teacher?'Enviar solicitud':'Crear mi cuenta'?></button>
  </form>
 </div>

 <div data-register-step="code"<?=$pending?'':' hidden'?> data-expires="<?=(int)($pending['expires_in']??0)?>" data-resend="<?=(int)($pending['resend_in']??0)?>" data-sends-left="<?=(int)($pending['sends_left']??0)?>">
  <div class="auth-icon"><?=icon('mail')?></div>
  <h2 tabindex="-1" data-code-title>Revisa tu correo</h2>
  <p>Escribe el código de 6 números que enviamos a <strong data-verify-email><?=e($pending['email']??'')?></strong>. <span data-verify-expires-text>Vence en <span data-verify-expires>15:00</span>.</span></p>
  <div class="notice dev-code" data-dev-code hidden><strong>Modo de pruebas (solo en tu computadora).</strong><br>No hay correo configurado, así que el código es: <code data-dev-code-value></code></div>
  <form action="<?=e(url('process/registro.php'))?>" method="post" data-api data-stay="true" data-verify-form><?=csrf()?>
   <input type="hidden" name="tipo" value="<?=e($type)?>"><input type="hidden" name="paso" value="verificar">
   <label class="field code-field"><span>Código de verificación</span><input name="codigo" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required placeholder="000000" title="Escribe los 6 números del código"></label>
   <?php feedback(); ?>
   <button type="submit" class="button primary"><?=icon('check')?><?=$teacher?'Confirmar y enviar solicitud':'Confirmar y crear mi cuenta'?></button>
  </form>
  <div class="verify-actions">
   <form action="<?=e(url('process/registro.php'))?>" method="post" data-api data-stay="true" data-resend-form><?=csrf()?>
    <input type="hidden" name="tipo" value="<?=e($type)?>"><input type="hidden" name="paso" value="reenviar">
    <button type="submit" class="button" data-resend-button>Reenviar código<span data-resend-timer></span></button>
    <?php feedback(); ?>
   </form>
   <button type="button" class="button" data-register-edit><?=icon('edit')?>Corregir mis datos</button>
  </div>
  <p class="help verify-help">¿No llega? Revisa las carpetas de spam y promociones y espera un minuto. Si escribiste mal tu correo, usa “Corregir mis datos”.</p>
 </div>

 <div class="auth-links"><a class="auth-link-button single" href="<?=e(url('index.php'))?>"><?=icon('back')?><span>Volver a iniciar sesión</span></a></div>
</section></main>
<script src="<?=e(assetUrl('assets/registro.js'))?>" defer></script>
<?php foot(); ?>
