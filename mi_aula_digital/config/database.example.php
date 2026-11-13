<?php
return [
 'host'=>'localhost',
 'user'=>'root',
 'password'=>'',
 'database'=>'mi_aula_digital_nueva',
 // Usa "auto" normalmente. En un hosting especial puedes indicar '/subcarpeta'.
 'base_url'=>'auto',
 'timezone'=>'America/Mexico_City',
 // Google OAuth: déjalos vacíos hasta crear las credenciales de esta instalación.
 'google_client_id'=>'',
 'google_client_secret'=>'',
 // 'auto' genera http(s)://dominio/ruta/auth/google_callback.php
 'google_redirect_uri'=>'auto',
 // Correo (códigos de verificación del registro, recuperación de contraseña y avisos).
 // InfinityFree bloquea mail(): usa un SMTP externo. Con Gmail: activa la verificación en 2 pasos
 // y crea una "contraseña de aplicación" (16 letras) en myaccount.google.com/apppasswords.
 // Mientras esté vacío, el registro en el hosting queda deshabilitado (en localhost se muestra el código en pantalla).
 'mail'=>[
  'host'=>'smtp.gmail.com',
  'port'=>587,
  'secure'=>'tls',          // 'tls' (587) o 'ssl' (465)
  'user'=>'',               // correo Gmail que envía, p. ej. miauladigital.escuela@gmail.com
  'password'=>'',           // contraseña de aplicación (NO tu contraseña normal de Gmail)
  'from'=>'',               // con Gmail debe ser el mismo correo de 'user'
  'from_name'=>'Mi Aula Digital',
 ],
 // Referencia de despliegue: local, aws, gcp o azure. No cambia la lógica académica.
 'cloud_provider'=>'local',
];
