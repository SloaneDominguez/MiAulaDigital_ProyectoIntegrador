<?php
declare(strict_types=1);

/*
 * Verificación de correo por código antes de crear cuentas.
 *
 * Flujo: el formulario de registro se valida completo → se envía un código de 6 dígitos
 * al correo → la cuenta (familia) o la solicitud (docente) se crea SOLO cuando la persona
 * escribe el código correcto. Así no se pueden registrar correos inexistentes ni saturar
 * el sistema con cientos de registros falsos.
 *
 * Controles contra abuso:
 *  - Código de 6 dígitos, vence en 15 minutos, máximo 5 intentos.
 *  - Máximo 3 envíos por registro (1 + 2 reenvíos) con espera de 60 s entre envíos.
 *  - Máximo 3 códigos por correo por hora y 10 por conexión (IP) por hora.
 *  - Tope global de correos por hora para no agotar la cuota del SMTP.
 *  - El registro queda ligado a la sesión del navegador que lo inició.
 *  - Campo trampa (honeypot) y tiempo mínimo de llenado contra bots.
 */

const MAD_CODE_TTL_MINUTES = 15;
const MAD_CODE_MAX_ATTEMPTS = 5;
const MAD_CODE_MAX_SENDS = 3;
const MAD_CODE_RESEND_SECONDS = 60;
const MAD_CODE_PER_EMAIL_HOUR = 3;
const MAD_CODE_PER_IP_HOUR = 10;
const MAD_CODE_GLOBAL_HOUR = 80;
const MAD_FORM_MIN_SECONDS = 3;

function verificationEnsureSchema(): void {
    static $ready = false;
    if ($ready) return;
    query("CREATE TABLE IF NOT EXISTS verificacion_correo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        proposito VARCHAR(20) NOT NULL,
        correo VARCHAR(100) NOT NULL,
        datos TEXT NOT NULL,
        codigo_hash CHAR(64) NOT NULL,
        sal CHAR(32) NOT NULL,
        sesion_hash CHAR(64) NOT NULL,
        ip_hash CHAR(64) NOT NULL,
        intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
        envios TINYINT UNSIGNED NOT NULL DEFAULT 1,
        creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ultimo_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        vence DATETIME NOT NULL,
        estado ENUM('Pendiente','Usada','Cancelada','Bloqueada') NOT NULL DEFAULT 'Pendiente',
        KEY idx_vc_correo (correo, creado),
        KEY idx_vc_ip (ip_hash, creado),
        KEY idx_vc_creado (creado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Las solicitudes docentes guardan si el correo fue comprobado.
    if (!one("SELECT 1 ok FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitud_acceso' AND COLUMN_NAME='correo_verificado' LIMIT 1")) {
        query("ALTER TABLE solicitud_acceso ADD COLUMN correo_verificado TINYINT(1) NOT NULL DEFAULT 0");
    }
    $ready = true;
}

function verificationIpHash(): string {
    return hash('sha256', 'mad-ip|'.($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function verificationCodeHash(string $code, string $salt): string {
    return hash_hmac('sha256', $code, $salt);
}

/** Limpieza oportunista: borra datos temporales (incluido el hash de contraseña) que ya no se usan. */
function verificationCleanup(): void {
    query("UPDATE verificacion_correo SET datos='' WHERE datos<>'' AND (estado<>'Pendiente' OR vence < NOW()) LIMIT 200");
    query('DELETE FROM verificacion_correo WHERE creado < DATE_SUB(NOW(), INTERVAL 2 DAY) LIMIT 200');
}

/** Marca de tiempo para el control de "llenado demasiado rápido". Llamar al mostrar el formulario. */
function registrationFormStamp(string $purpose): void {
    $_SESSION['registro_form_ts'][$purpose] = time();
}

/** Detiene bots simples: campo trampa lleno o formulario enviado en menos de 3 segundos. */
function registrationBotGuard(string $purpose): void {
    $trap = $_POST['sitio_web'] ?? '';
    if (!is_string($trap) || $trap !== '') throw new DomainException('No se pudo procesar el registro. Recarga la página e inténtalo de nuevo.', 400);
    $ts = (int)($_SESSION['registro_form_ts'][$purpose] ?? 0);
    if ($ts === 0) throw new DomainException('Tu sesión cambió. Recarga la página e inténtalo de nuevo.', 403);
    if (time() - $ts < MAD_FORM_MIN_SECONDS) throw new DomainException('Revisa tus datos con calma y vuelve a enviar el formulario en unos segundos.', 429);
}

/** Revisa los topes por correo, por conexión y global antes de enviar un código. */
function verificationRateLimits(string $email): void {
    $byEmail = (int)(one("SELECT COUNT(*) n FROM verificacion_correo WHERE correo=? AND creado > DATE_SUB(NOW(), INTERVAL 1 HOUR)", [$email])['n'] ?? 0);
    if ($byEmail >= MAD_CODE_PER_EMAIL_HOUR) throw new DomainException('Ya enviamos varios códigos a este correo. Revisa tu bandeja de entrada y la carpeta de spam, o inténtalo de nuevo en una hora.', 429);
    $byIp = (int)(one("SELECT COUNT(*) n FROM verificacion_correo WHERE ip_hash=? AND creado > DATE_SUB(NOW(), INTERVAL 1 HOUR)", [verificationIpHash()])['n'] ?? 0);
    if ($byIp >= MAD_CODE_PER_IP_HOUR) throw new DomainException('Se hicieron demasiados registros desde esta conexión. Inténtalo de nuevo en una hora.', 429);
    $global = (int)(one("SELECT COALESCE(SUM(envios),0) n FROM verificacion_correo WHERE ultimo_envio > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['n'] ?? 0);
    if ($global >= MAD_CODE_GLOBAL_HOUR) throw new DomainException('El registro está recibiendo muchas solicitudes en este momento. Inténtalo de nuevo más tarde.', 429);
}

function verificationNewCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function verificationSendCode(string $purpose, string $email, string $name, string $code): bool {
    $teacher = $purpose === 'docente';
    $body = mailTemplate(
        'Confirma tu correo',
        [
            'Hola '.$name.':',
            $teacher
                ? 'Recibimos tu solicitud de acceso como docente. Escribe este código en la página de registro para confirmar que este correo es tuyo. Después, Dirección revisará tu solicitud.'
                : 'Estás creando tu cuenta de familia. Escribe este código en la página de registro para confirmar que este correo es tuyo y terminar.',
            'El código vence en '.MAD_CODE_TTL_MINUTES.' minutos y solo funciona una vez. Si no fuiste tú, ignora este mensaje: no se creará ninguna cuenta.',
        ],
        $code
    );
    return sendMail($email, $name, 'Tu código de verificación: '.$code, $body);
}

/**
 * Inicia la verificación: guarda los datos ya validados, envía el código y liga
 * el proceso a esta sesión. Devuelve los datos que necesita la pantalla del código.
 */
function verificationStart(string $purpose, string $email, string $name, array $data): array {
    verificationEnsureSchema();
    verificationCleanup();
    if (!mailAvailable()) throw new DomainException('El registro no está disponible: la escuela todavía no configura el envío de correos. Avisa a Dirección.', 503);
    verificationRateLimits($email);

    // Un registro nuevo en este navegador reemplaza al anterior.
    verificationCancel($purpose);

    $code = verificationNewCode();
    $salt = bin2hex(random_bytes(16));
    $token = bin2hex(random_bytes(32));
    query(
        'INSERT INTO verificacion_correo (proposito,correo,datos,codigo_hash,sal,sesion_hash,ip_hash,vence) VALUES (?,?,?,?,?,?,?,DATE_ADD(NOW(), INTERVAL '.MAD_CODE_TTL_MINUTES.' MINUTE))',
        [$purpose, $email, json_encode($data, JSON_UNESCAPED_UNICODE), verificationCodeHash($code, $salt), $salt, hash('sha256', $token), verificationIpHash()]
    );
    $id = (int)db()->insert_id;
    try {
        $sent = verificationSendCode($purpose, $email, $name, $code);
    } catch (Throwable $ex) {
        query("UPDATE verificacion_correo SET estado='Cancelada', datos='' WHERE id=?", [$id]);
        throw $ex;
    }
    $_SESSION['verificacion'][$purpose] = ['id' => $id, 'token' => $token];
    return verificationState($purpose) + ($sent ? [] : ['dev_code' => $code]);
}

/** Fila pendiente ligada a esta sesión (o null). */
function verificationRow(string $purpose, bool $lock = false): ?array {
    $s = $_SESSION['verificacion'][$purpose] ?? null;
    if (!is_array($s) || empty($s['id']) || empty($s['token'])) return null;
    verificationEnsureSchema();
    $r = one('SELECT *, TIMESTAMPDIFF(SECOND, NOW(), vence) restante, TIMESTAMPDIFF(SECOND, ultimo_envio, NOW()) desde_envio FROM verificacion_correo WHERE id=? AND proposito=?'.($lock ? ' FOR UPDATE' : ''), [(int)$s['id'], $purpose]);
    if (!$r || !hash_equals((string)$r['sesion_hash'], hash('sha256', (string)$s['token']))) return null;
    return $r;
}

/** Datos para mostrar la pantalla del código; [] si no hay verificación activa. */
function verificationState(string $purpose): array {
    $r = verificationRow($purpose);
    if (!$r || $r['estado'] !== 'Pendiente' || (int)$r['restante'] <= 0) return [];
    return [
        'email' => maskEmail((string)$r['correo']),
        'expires_in' => (int)$r['restante'],
        'resend_in' => max(0, MAD_CODE_RESEND_SECONDS - (int)$r['desde_envio']),
        'sends_left' => max(0, MAD_CODE_MAX_SENDS - (int)$r['envios']),
        'attempts_left' => max(0, MAD_CODE_MAX_ATTEMPTS - (int)$r['intentos']),
    ];
}

function verificationResend(string $purpose): array {
    $code = verificationNewCode();
    $r = transaction(function () use ($purpose, $code) {
        $r = verificationRow($purpose, true);
        if (!$r || $r['estado'] !== 'Pendiente' || (int)$r['restante'] <= 0) throw new DomainException('Tu código ya no está activo. Vuelve a llenar el registro.', 410);
        if ((int)$r['envios'] >= MAD_CODE_MAX_SENDS) throw new DomainException('Ya no se pueden enviar más códigos para este registro. Revisa tu carpeta de spam o vuelve a llenar el registro más tarde.', 429);
        $wait = MAD_CODE_RESEND_SECONDS - (int)$r['desde_envio'];
        if ($wait > 0) throw new DomainException('Espera '.$wait.' segundos para pedir otro código.', 429);
        $global = (int)(one("SELECT COALESCE(SUM(envios),0) n FROM verificacion_correo WHERE ultimo_envio > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['n'] ?? 0);
        if ($global >= MAD_CODE_GLOBAL_HOUR) throw new DomainException('El registro está recibiendo muchas solicitudes en este momento. Inténtalo de nuevo más tarde.', 429);
        // El código anterior deja de servir; el plazo se renueva y los intentos se reinician.
        query('UPDATE verificacion_correo SET codigo_hash=?, envios=envios+1, intentos=0, ultimo_envio=NOW(), vence=DATE_ADD(NOW(), INTERVAL '.MAD_CODE_TTL_MINUTES.' MINUTE) WHERE id=?', [verificationCodeHash($code, (string)$r['sal']), (int)$r['id']]);
        return $r;
    });
    $data = json_decode((string)$r['datos'], true) ?: [];
    $sent = verificationSendCode($purpose, (string)$r['correo'], (string)($data['nombre'] ?? ''), $code);
    return verificationState($purpose) + ($sent ? [] : ['dev_code' => $code]);
}

/**
 * Comprueba el código. Cada intento fallido se guarda aunque la operación falle.
 * Devuelve la fila con los datos del registro; NO la marca como usada
 * (eso lo hace verificationMarkUsed dentro de la transacción que crea la cuenta).
 */
function verificationCheck(string $purpose, string $code): array {
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6) throw new DomainException('Escribe los 6 números del código que llegó a tu correo.');
    $result = transaction(function () use ($purpose, $code) {
        $r = verificationRow($purpose, true);
        if (!$r) return ['error' => 'Tu registro ya no está activo en este navegador. Vuelve a llenar el formulario.', 'status' => 410];
        if ($r['estado'] === 'Bloqueada') return ['error' => 'Se agotaron los intentos. Pide un código nuevo o vuelve a llenar el registro.', 'status' => 429];
        if ($r['estado'] !== 'Pendiente') return ['error' => 'Este código ya se usó o fue reemplazado.', 'status' => 410];
        if ((int)$r['restante'] <= 0) return ['error' => 'El código venció. Pide uno nuevo.', 'status' => 410];
        if (!hash_equals((string)$r['codigo_hash'], verificationCodeHash($code, (string)$r['sal']))) {
            $left = MAD_CODE_MAX_ATTEMPTS - ((int)$r['intentos'] + 1);
            query('UPDATE verificacion_correo SET intentos=intentos+1'.($left <= 0 ? ", estado='Bloqueada'" : '').' WHERE id=?', [(int)$r['id']]);
            return $left <= 0
                ? ['error' => 'Código incorrecto. Se agotaron los intentos: vuelve a llenar el registro para recibir un código nuevo.', 'status' => 429, 'blocked' => true]
                : ['error' => 'Código incorrecto. Te '.($left === 1 ? 'queda 1 intento' : 'quedan '.$left.' intentos').'.', 'status' => 422];
        }
        return ['row' => $r];
    });
    if (isset($result['error'])) {
        if (!empty($result['blocked'])) unset($_SESSION['verificacion'][$purpose]);
        throw new DomainException($result['error'], $result['status']);
    }
    $row = $result['row'];
    $row['data'] = json_decode((string)$row['datos'], true) ?: [];
    if (!$row['data']) throw new DomainException('Tu registro ya no está activo. Vuelve a llenar el formulario.', 410);
    return $row;
}

/** Marca la verificación como usada; evita que dos pestañas creen dos cuentas con el mismo código. */
function verificationMarkUsed(int $id): void {
    $s = query("UPDATE verificacion_correo SET estado='Usada', datos='' WHERE id=? AND estado='Pendiente'", [$id]);
    if ($s->affected_rows !== 1) throw new DomainException('Este código ya se usó.', 409);
}

function verificationCancel(string $purpose): void {
    $s = $_SESSION['verificacion'][$purpose] ?? null;
    if (is_array($s) && !empty($s['id'])) {
        verificationEnsureSchema();
        query("UPDATE verificacion_correo SET estado='Cancelada', datos='' WHERE id=? AND sesion_hash=? AND estado='Pendiente'", [(int)$s['id'], hash('sha256', (string)($s['token'] ?? ''))]);
    }
    unset($_SESSION['verificacion'][$purpose]);
}
