<?php
declare(strict_types=1);

/*
 * Envío de correos del sistema (códigos de verificación, recuperación y avisos).
 *
 * InfinityFree bloquea la función mail() de PHP, así que el envío se hace por SMTP
 * externo (Gmail con "contraseña de aplicación", Brevo, SMTP2GO, etc.).
 * Configuración: bloque 'mail' en config/database.local.php o variables de entorno
 * MAD_SMTP_HOST, MAD_SMTP_PORT, MAD_SMTP_SECURE, MAD_SMTP_USER, MAD_SMTP_PASSWORD,
 * MAD_MAIL_FROM y MAD_MAIL_FROM_NAME.
 */

require_once __DIR__.'/../lib/PHPMailer/Exception.php';
require_once __DIR__.'/../lib/PHPMailer/SMTP.php';
require_once __DIR__.'/../lib/PHPMailer/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;

function mailConfig(): array {
    global $config;
    $mail = is_array($config['mail'] ?? null) ? $config['mail'] : [];
    $env = [
        'MAD_SMTP_HOST' => 'host', 'MAD_SMTP_PORT' => 'port', 'MAD_SMTP_SECURE' => 'secure',
        'MAD_SMTP_USER' => 'user', 'MAD_SMTP_PASSWORD' => 'password',
        'MAD_MAIL_FROM' => 'from', 'MAD_MAIL_FROM_NAME' => 'from_name',
    ];
    if (function_exists('getenv')) {
        foreach ($env as $name => $key) {
            $value = getenv($name);
            if ($value !== false && $value !== '') $mail[$key] = $value;
        }
    }
    $secure = strtolower(trim((string)($mail['secure'] ?? 'tls')));
    return [
        'host' => trim((string)($mail['host'] ?? '')),
        'port' => (int)($mail['port'] ?? ($secure === 'ssl' ? 465 : 587)),
        'secure' => in_array($secure, ['tls', 'ssl', 'none'], true) ? $secure : 'tls',
        'user' => trim((string)($mail['user'] ?? '')),
        // Las contraseñas de aplicación de Gmail se muestran con espacios; se quitan aquí.
        'password' => str_replace(' ', '', (string)($mail['password'] ?? '')),
        'from' => trim((string)($mail['from'] ?? ($mail['user'] ?? ''))),
        'from_name' => trim((string)($mail['from_name'] ?? '')),
    ];
}

/** El servidor puede enviar correos reales. */
function mailConfigured(): bool {
    $c = mailConfig();
    return $c['host'] !== '' && $c['user'] !== '' && $c['password'] !== '' && filter_var($c['from'], FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Modo de pruebas: SOLO en tu computadora (localhost) y SOLO si no hay SMTP configurado.
 * El código se muestra en pantalla para poder probar el flujo sin enviar correos.
 * En el hosting nunca se activa.
 */
function mailDevMode(): bool {
    // Se exige host Y dirección IP de loopback: un encabezado Host falso no basta.
    return !mailConfigured() && madIsLocalhost();
}

function madIsLocalhost(): bool {
    $host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')) ?? '');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true) && in_array($ip, ['127.0.0.1', '::1'], true);
}

/** Se puede usar verificación por correo (real o, en local, de pruebas). */
function mailAvailable(): bool { return mailConfigured() || mailDevMode(); }

// absoluteUrl() ya existe en app/school_rules.php y se reutiliza para los enlaces de los correos.

/** Oculta parte del correo: miauladigital.escuela@gmail.com → mi•••••••••••••••••••@gmail.com */
function maskEmail(string $email): string {
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    $keep = mb_strlen($local) <= 2 ? 1 : 2;
    return mb_substr($local, 0, $keep).str_repeat('•', max(3, mb_strlen($local) - $keep)).'@'.$domain;
}

/**
 * Plantilla HTML sencilla y compatible con clientes de correo.
 * $blocks: lista de párrafos (texto plano, se escapa). $code: código destacado opcional.
 */
function mailTemplate(string $title, array $blocks, ?string $code = null, ?array $action = null): array {
    $branding = function_exists('schoolBranding') ? schoolBranding() : [];
    $system = (string)($branding['nombre_sistema'] ?? 'Mi Aula Digital');
    $school = (string)($branding['nombre_escuela'] ?? '');
    $color = preg_match('/^#[0-9A-Fa-f]{6}$/', (string)($branding['color_principal'] ?? '')) ? (string)$branding['color_principal'] : '#bb1e35';

    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).'</title></head>'
        .'<body style="margin:0;padding:0;background:#f5f3f4;font-family:Segoe UI,Arial,sans-serif;color:#19191d">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3f4;padding:28px 12px"><tr><td align="center">'
        .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:18px;border:1px solid #ece4e7">'
        .'<tr><td style="padding:26px 30px 6px"><div style="font-size:13px;font-weight:700;color:'.e($color).'">'.e($system).'</div>'
        .($school !== '' ? '<div style="font-size:12px;color:#6b6670">'.e($school).'</div>' : '').'</td></tr>'
        .'<tr><td style="padding:10px 30px 4px"><h1 style="margin:0 0 12px;font-size:21px;line-height:1.3">'.e($title).'</h1>';
    foreach ($blocks as $p) $html .= '<p style="margin:0 0 12px;font-size:14px;line-height:1.55;color:#3d3740">'.nl2br(e($p)).'</p>';
    if ($code !== null) {
        $html .= '<div style="margin:18px 0;padding:16px;border-radius:14px;background:#f8eef1;text-align:center">'
            .'<div style="font-size:11px;color:#6b6670;letter-spacing:1px;text-transform:uppercase">Tu código</div>'
            .'<div style="font:700 32px/1.2 Consolas,Menlo,monospace;letter-spacing:8px;color:'.e($color).'">'.e($code).'</div></div>';
    }
    if ($action !== null) {
        $html .= '<p style="margin:18px 0"><a href="'.e($action['url']).'" style="display:inline-block;padding:12px 20px;border-radius:12px;background:'.e($color).';color:#ffffff;font-weight:700;text-decoration:none;font-size:14px">'.e($action['label']).'</a></p>'
            .'<p style="margin:0 0 12px;font-size:11px;color:#8b7f88;word-break:break-all">Si el botón no funciona, copia este enlace: '.e($action['url']).'</p>';
    }
    $html .= '</td></tr><tr><td style="padding:8px 30px 26px;font-size:11px;color:#8b7f88;line-height:1.5">Mensaje automático de '.e($system).'. No respondas a este correo. Nunca te pediremos tu contraseña.</td></tr>'
        .'</table></td></tr></table></body></html>';

    $text = $title."\n\n".implode("\n\n", $blocks);
    if ($code !== null) $text .= "\n\nTu código: ".$code;
    if ($action !== null) $text .= "\n\n".$action['label'].': '.$action['url'];
    $text .= "\n\n— Mensaje automático de ".$system.'. No respondas a este correo.';
    return ['html' => $html, 'text' => $text, 'system' => $system];
}

/**
 * Envía un correo. Lanza DomainException con un mensaje claro si falla.
 * Devuelve false (sin enviar) en modo de pruebas local.
 */
function sendMail(string $to, string $toName, string $subject, array $body): bool {
    if (!mailConfigured()) {
        if (mailDevMode()) { error_log('MAD mail (modo pruebas, no enviado) a '.$to.': '.$subject); return false; }
        throw new DomainException('El envío de correos todavía no está configurado. Avisa a Dirección.', 503);
    }
    $c = mailConfig();
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $c['host'];
        $mail->Port = $c['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $c['user'];
        $mail->Password = $c['password'];
        if ($c['secure'] === 'ssl') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        elseif ($c['secure'] === 'tls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        else { $mail->SMTPSecure = ''; $mail->SMTPAutoTLS = false; }
        $mail->Timeout = 12;
        // Tope total por respuesta del servidor SMTP (por defecto PHPMailer espera hasta 300 s).
        $mail->getSMTPInstance()->Timelimit = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_BASE64;
        $mail->setFrom($c['from'], $c['from_name'] !== '' ? $c['from_name'] : $body['system']);
        $mail->addAddress($to, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body['html'];
        $mail->AltBody = $body['text'];
        $mail->send();
        return true;
    } catch (Throwable $ex) {
        // El detalle técnico (que puede incluir datos del servidor SMTP) solo va al log.
        error_log('MAD mail error: '.$mail->ErrorInfo.' | '.$ex->getMessage());
        throw new DomainException('No pudimos enviar el correo en este momento. Revisa que tu correo esté bien escrito e inténtalo de nuevo en unos minutos.', 502);
    }
}

/**
 * Aviso al docente cuando Dirección revisa su solicitud.
 * Por seguridad NUNCA se envía la contraseña (además se guarda cifrada y no se puede leer):
 * se recuerda el usuario y se ofrece "Recuperar acceso" por si la olvidó.
 */
function notifyTeacherReview(array $request, bool $approved): void {
    $name = trim((string)$request['Nombre']);
    $email = (string)$request['Correo'];
    if ($approved) {
        $body = mailTemplate('Tu acceso fue aprobado', [
            'Hola '.$name.':',
            'Dirección aprobó tu solicitud de acceso como docente. Ya puedes iniciar sesión.',
            "Tu usuario: ".$request['Usuario']."\nTu correo: ".$email,
            'Usa la contraseña que creaste al registrarte. Si no la recuerdas, entra a “Recuperar acceso” desde la página de inicio y te enviaremos un enlace a este correo.',
        ], null, ['label' => 'Iniciar sesión', 'url' => absoluteUrl('index.php')]);
        sendMail($email, $name, 'Tu acceso a '.$body['system'].' fue aprobado', $body);
        return;
    }
    $body = mailTemplate('Revisión de tu solicitud', [
        'Hola '.$name.':',
        'Dirección revisó tu solicitud de acceso como docente y por ahora no fue aprobada.',
        'Si crees que se trata de un error, comunícate con la dirección de la escuela.',
    ]);
    sendMail($email, $name, 'Revisión de tu solicitud en '.$body['system'], $body);
}

/**
 * Envía la invitación de una cuenta familiar desde el servidor (Gmail SMTP).
 * Devuelve true si se envió y false en modo de pruebas local (sin SMTP configurado).
 * Lanza DomainException si el envío falla.
 */
function sendInvitationEmail(array $invite): bool {
    $first = explode(' ', (string)$invite['nombre'])[0];
    $body = mailTemplate('Crea tu contraseña', [
        'Hola '.$first.':',
        'Dirección de la escuela te registró para que consultes la información escolar de tu hijo o hija.',
        'Tu usuario es: '.$invite['usuario'],
        'Usa el botón para crear tu contraseña. El enlace funciona una sola vez y vence el '.$invite['vence'].'. Si no esperabas este mensaje, ignóralo.',
    ], null, ['label' => 'Crear mi contraseña', 'url' => (string)$invite['enlace']]);
    return sendMail((string)$invite['correo'], (string)$invite['nombre'], (string)($invite['asunto'] ?? 'Invitación a '.$body['system']), $body);
}
