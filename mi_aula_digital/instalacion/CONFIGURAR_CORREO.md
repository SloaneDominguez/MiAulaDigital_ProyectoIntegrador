# Verificación de correo en el registro — configuración

Desde esta versión, **familias y docentes deben confirmar su correo con un código de 6 números**
antes de que se cree la cuenta (familia) o la solicitud llegue a Dirección (docente).
Así se evitan correos inexistentes y registros masivos.

## Qué hace el sistema

| Paso | Familia (padre/tutor) | Docente |
|---|---|---|
| 1 | Llena el formulario y da **Crear mi cuenta** | Llena el formulario y da **Enviar solicitud** |
| 2 | Recibe un código en su correo y lo escribe | Igual |
| 3 | Se crea la cuenta y pasa al **inicio de sesión** | Se crea la solicitud; Dirección la aprueba |
| 4 | — | Recibe un correo de “acceso aprobado” con su **usuario** (nunca la contraseña) |

Protecciones contra abuso:

- Código de 6 dígitos, vence en **15 min**, máximo **5 intentos**.
- Máximo **3 envíos** por registro (1 + 2 reenvíos), con **60 s** de espera entre envíos.
- Máximo **3 códigos por correo por hora** y **10 registros por conexión (IP) por hora**.
- Tope global de **80 correos por hora** (protege la cuota de Gmail: ~500 al día).
- El código solo sirve en el navegador donde se inició el registro.
- Campo trampa invisible y tiempo mínimo de llenado (3 s) contra bots.
- La contraseña se guarda **cifrada** mientras se verifica y se borra de la tabla temporal al terminar.

**Recuperar contraseña** ahora envía un enlace al correo de la cuenta (30 min, un solo uso).
Si el correo falla, la solicitud queda para Dirección como antes.

## Configurar Gmail (una sola vez)

InfinityFree **no permite** la función `mail()` de PHP; se usa Gmail por SMTP.

1. Usa (o crea) una cuenta de Gmail para la escuela, por ejemplo `miauladigital.escuela@gmail.com`.
2. Activa la **verificación en 2 pasos** en esa cuenta.
3. Entra a <https://myaccount.google.com/apppasswords>, crea una contraseña de aplicación
   llamada “Mi Aula Digital” y copia las 16 letras.
4. En `config/database.local.php` llena el bloque `mail`:

```php
'mail'=>[
  'host'=>'smtp.gmail.com',
  'port'=>587,
  'secure'=>'tls',
  'user'=>'miauladigital.escuela@gmail.com',
  'password'=>'abcd efgh ijkl mnop',   // contraseña de aplicación
  'from'=>'miauladigital.escuela@gmail.com',
  'from_name'=>'Mi Aula Digital',
],
```

5. Sube a InfinityFree los archivos modificados (lista abajo) y abre **Registro de familia** para probar.

> Si el puerto 587 no conecta en el hosting, prueba `'port'=>465` y `'secure'=>'ssl'`.

Mientras el bloque `mail` esté vacío:

- **En el hosting**: el registro muestra “no disponible” (no se crean cuentas sin verificar).
- **En tu computadora (localhost)**: el código aparece en pantalla como “Modo de pruebas”, para probar sin enviar correos.

## Base de datos

No hay que ejecutar SQL: al primer uso se crea la tabla `verificacion_correo` y se agrega la
columna `correo_verificado` a `solicitud_acceso` automáticamente.

## Archivos nuevos o modificados

Nuevos: `app/mailer.php`, `app/email_verification.php`, `assets/registro.js`,
`lib/PHPMailer/*` (PHPMailer 6.10.0, licencia LGPL), `lib/.htaccess`, `instalacion/CONFIGURAR_CORREO.md`.

Modificados: `process/registro.php`, `app/register.php`, `process/recovery.php`, `recuperar.php`,
`process/action.php`, `app/views.php`, `app/ui.php`, `assets/app.js`, `assets/design.css`,
`index.php`, `config/database.example.php`, `config/database.local.php`.
