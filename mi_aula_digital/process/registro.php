<?php
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/mailer.php';
require_once __DIR__.'/../app/email_verification.php';

/*
 * Registro de familias y docentes en dos pasos:
 *  1) "solicitar": valida todo el formulario y envía un código al correo.
 *  2) "verificar": con el código correcto se crea la cuenta (familia) o la
 *     solicitud para Dirección (docente) y se manda a la persona al inicio de sesión.
 * También: "reenviar" (otro código) y "cancelar" (volver a editar los datos).
 */
try {
 validateCsrf();
 $type=textInput('tipo',20); if(!in_array($type,['docente','tutor'],true)) throw new DomainException('Tipo de registro inválido.');
 $action=textInput('paso',20,false)?:'solicitar';

 if($action==='reenviar'){
   throttle('registro_reenvio','',10);
   $state=verificationResend($type);
   reply(['ok'=>true,'message'=>'Te enviamos un código nuevo. El anterior ya no funciona.','verify'=>$state]);
 }
 if($action==='cancelar'){
   verificationCancel($type);
   reply(['ok'=>true,'message'=>'Puedes corregir tus datos.']);
 }
 if($action==='verificar'){
   throttle('registro_codigo','',20);
   $row=verificationCheck($type,textInput('codigo',20));
   $d=$row['data']; $verificationId=(int)$row['id'];
   authEnsureSchema(); verificationEnsureSchema();
   if($type==='docente'){
     transaction(function()use($d,$verificationId){
       verificationMarkUsed($verificationId);
       requireFreeAccount($d['usuario'],$d['correo']);
       $old=one('SELECT * FROM solicitud_acceso WHERE Usuario=? OR Correo=? FOR UPDATE',[$d['usuario'],$d['correo']]);
       if($old && $old['Estado']!=='Rechazada') throw new DomainException('Ya existe una solicitud con ese usuario o correo.',409);
       if($old) query("UPDATE solicitud_acceso SET Nombre=?,Primer_Apellido=?,Segundo_Apellido=?,Usuario=?,Correo=?,Contrasena=?,Estado='Pendiente',Fecha_Solicitud=NOW(),Fecha_Revision=NULL,correo_verificado=1 WHERE ID_Solicitud=?",[$d['nombre'],$d['primer_apellido'],$d['segundo_apellido'],$d['usuario'],$d['correo'],$d['hash'],(int)$old['ID_Solicitud']]);
       else query("INSERT INTO solicitud_acceso (Nombre,Primer_Apellido,Segundo_Apellido,Usuario,Correo,Contrasena,Rol,Estado,Fecha_Solicitud,correo_verificado) VALUES (?,?,?,?,?,?,'Docente','Pendiente',NOW(),1)",[$d['nombre'],$d['primer_apellido'],$d['segundo_apellido'],$d['usuario'],$d['correo'],$d['hash']]);
     });
     unset($_SESSION['verificacion'][$type]);
     reply(['ok'=>true,'message'=>'Correo confirmado. Tu solicitud se envió a Dirección.','redirect'=>url('index.php?registro=docente')]);
   }
   transaction(function()use($d,$verificationId){
     verificationMarkUsed($verificationId);
     requireFreePhone($d['telefono']); requireFreeAccount($d['usuario'],$d['correo']);
     query("INSERT INTO usuario (Nombre,Usuario,Correo,Contrasena,Rol,correo_verificado) VALUES (?,?,?,?,'PadreTutor',1)",[$d['nombre_completo'],$d['usuario'],$d['correo'],$d['hash']]); $id=db()->insert_id;
     query('INSERT INTO padre_tutor (Nombre_Completo,Correo,Telefono,ID_Usuario) VALUES (?,?,?,?)',[$d['nombre_completo'],$d['correo'],$d['telefono'],$id]);
   });
   unset($_SESSION['verificacion'][$type]);
   reply(['ok'=>true,'message'=>'Correo confirmado. Tu cuenta está lista.','redirect'=>url('index.php?registro=familia')]);
 }
 if($action!=='solicitar') throw new DomainException('Operación inválida.');

 // Paso 1: validar todo antes de gastar un envío de correo.
 registrationBotGuard($type);
 $name=validPersonName(textInput('nombre',50),'el nombre'); $first=validPersonName(textInput('primer_apellido',50),'el primer apellido'); $last=validPersonName(textInput('segundo_apellido',50,false),'el segundo apellido',false);
 $full=trim("$name $first $last"); if(mb_strlen($full)>100) throw new DomainException('El nombre completo debe tener como máximo 100 caracteres.');
 $email=validEmail(textInput('correo',100));
 $username=validUsername(textInput('usuario',50));
 $pass=passwordInput('password',8);
 requireStrongPassword($pass,[$name,$first,$last,$username,$email,$_POST['telefono']??'']);
 if($pass!==passwordInput('confirmar_password',1)) throw new DomainException('Las contraseñas no coinciden.');
 $phone=$type==='tutor'?validPhone(textInput('telefono',20)):'';
 // Todos los datos se revisan antes de contar el intento: un error al escribir no gasta intentos.
 throttle('registro','',8);
 requireFreeAccount($username,$email);
 if($type==='tutor') requireFreePhone($phone);
 if($type==='docente'){
   verificationEnsureSchema();
   $old=one('SELECT Estado FROM solicitud_acceso WHERE Usuario=? OR Correo=?',[$username,$email]);
   if($old && $old['Estado']!=='Rechazada') throw new DomainException('Ya existe una solicitud con ese usuario o correo.',409);
 }
 $state=verificationStart($type,$email,$name,[
   'nombre'=>$name,'primer_apellido'=>$first,'segundo_apellido'=>$last,'nombre_completo'=>$full,
   'correo'=>$email,'usuario'=>$username,'telefono'=>$phone,
   // Solo se guarda el hash; se borra en cuanto se usa, se cancela o vence.
   'hash'=>password_hash($pass,PASSWORD_DEFAULT),
 ]);
 reply(['ok'=>true,'message'=>'Te enviamos un código de 6 números a '.$state['email'].'.','verify'=>$state]);
} catch(Throwable $ex) { errorReply($ex); }
