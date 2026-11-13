<?php
require __DIR__.'/app/bootstrap.php';
require __DIR__.'/app/ui.php';
require_once __DIR__.'/app/mailer.php';
$byEmail=mailAvailable();
$linkCode=is_string($_GET['codigo']??null)&&preg_match('/^[a-f0-9]{32,64}$/',$_GET['codigo'])?$_GET['codigo']:'';
$branding=schoolBranding();
head('Recuperar acceso');
?>
<main class="recovery-shell">
  <div class="recovery-topbar">
    <?php brand(); ?>
  </div>

  <section class="recovery-intro glass">
    <div class="auth-icon"><?=icon('key')?></div>
    <div>
      <div class="overline">Recuperación segura</div>
      <h1>Recupera tu acceso sin perder tu información.</h1>
      <p><?php if($byEmail): ?>Te enviamos un enlace privado al correo de tu cuenta. Dura 30 minutos, funciona una sola vez y nunca muestra tu contraseña anterior.<?php else: ?>La escuela verifica tu identidad antes de emitir un código privado. El código dura 30 minutos, funciona una sola vez y nunca muestra tu contraseña anterior.<?php endif; ?></p>
    </div>
  </section>

  <div class="recovery-grid">
    <section class="recovery-card glass" aria-labelledby="recovery-request-title">
      <span class="recovery-step">Paso 1</span>
      <h2 id="recovery-request-title">Solicitar recuperación</h2>
      <p><?php if($byEmail): ?>Escribe tu usuario o correo. Si corresponde a una cuenta activa, te enviaremos un enlace al correo registrado.<?php else: ?>Escribe tu usuario o correo. Si corresponde a una cuenta activa, Dirección verá la solicitud.<?php endif; ?></p>
      <form action="<?=e(url('process/recovery.php'))?>" method="post" data-api data-stay="true">
        <?=csrf()?>
        <input type="hidden" name="action" value="solicitar">
        <?php field('cuenta','Usuario o correo','text','',true,100,'Por privacidad, la respuesta será la misma exista o no la cuenta.'); ?>
        <?php feedback(); ?>
        <button class="button primary" type="submit"><?=icon('shield')?>Solicitar recuperación</button>
      </form>
    </section>

    <section class="recovery-card glass" id="paso2" aria-labelledby="recovery-code-title">
      <span class="recovery-step">Paso 2</span>
      <h2 id="recovery-code-title">Usar el código privado</h2>
      <p><?php if($linkCode!==''): ?>Abriste el enlace de tu correo: el código ya está escrito. Crea tu nueva contraseña.<?php elseif($byEmail): ?>Abre el enlace que llegó a tu correo, o pega aquí el código que te entregó Dirección.<?php else: ?>Después de que Dirección confirme tu identidad, introduce aquí el código que te entregó.<?php endif; ?></p>
      <form action="<?=e(url('process/recovery.php'))?>" method="post" data-api data-stay="true">
        <?=csrf()?>
        <input type="hidden" name="action" value="restablecer">
        <?php field('codigo','Código privado','text',$linkCode,true,64,'',['autocomplete'=>'off','spellcheck'=>'false']); ?>
        <?php field('password','Nueva contraseña','password','',true,72,'',['data-strength'=>'1']); ?>
        <?php field('confirmar','Confirmar contraseña','password','',true,72,'',['data-match'=>'password']); ?>
        <?php feedback(); ?>
        <button class="button success" type="submit"><?=icon('check')?>Guardar nueva contraseña</button>
      </form>
    </section>
  </div>

  <div class="auth-links recovery-login-link"><a class="auth-link-button single" href="<?=e(url('index.php'))?>"><?=icon('back')?><span>Volver a iniciar sesión</span></a></div>

  <section class="recovery-help glass">
    <h2>¿Cómo funciona?</h2>
    <div class="recovery-flow">
      <div><span>1</span><strong>Solicitas</strong><small>Usuario o correo.</small></div>
      <?php if($byEmail): ?><div><span>2</span><strong>Revisas tu correo</strong><small>Llega un enlace privado.</small></div>
      <div><span>3</span><strong>Abres el enlace</strong><small>30 minutos y un solo uso.</small></div><?php else: ?><div><span>2</span><strong>Dirección verifica</strong><small>Por el canal habitual de la escuela.</small></div>
      <div><span>3</span><strong>Recibes código</strong><small>Privado, 30 minutos y un solo uso.</small></div><?php endif; ?>
      <div><span>4</span><strong>Creas contraseña</strong><small>Las sesiones anteriores se invalidan.</small></div>
    </div>
    <div class="notice recovery-google-note"><strong>¿Tu cuenta usa solo Google?</strong><br>Usa “Continuar con Google” en el inicio. Si también necesitas contraseña local, Dirección puede habilitar el método Local + Google desde Accesos.</div>
  </section>
</main>
<?php foot(); ?>
