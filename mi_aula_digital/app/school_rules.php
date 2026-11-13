<?php
declare(strict_types=1);

/*
 * Reglas escolares compartidas:
 * - Teléfonos de padres/tutores: 10 dígitos y sin repetirse.
 * - Invitaciones: Dirección registra a un padre/tutor y le envía un enlace
 *   para que él mismo cree su contraseña.
 */

/** Deja solo los 10 dígitos de un teléfono de México (acepta +52, espacios, guiones y paréntesis). */
function normalizePhone(string $raw): string {
    $digits = preg_replace('/\D+/', '', $raw) ?? '';
    if (strlen($digits) === 12 && str_starts_with($digits, '52')) $digits = substr($digits, 2);
    if (strlen($digits) === 13 && str_starts_with($digits, '521')) $digits = substr($digits, 3);
    if (strlen($digits) !== 10) throw new DomainException('Escribe un teléfono de 10 dígitos (por ejemplo 771 123 4567).');
    return $digits;
}

/** Devuelve el nombre del tutor que ya usa ese teléfono, o null si está libre. */
function phoneOwner(string $digits, ?int $exceptPadre = null): ?string {
    foreach (rows('SELECT ID_Padre,Nombre_Completo,Telefono FROM padre_tutor WHERE Telefono IS NOT NULL AND Telefono<>""') as $p) {
        if ($exceptPadre !== null && (int)$p['ID_Padre'] === $exceptPadre) continue;
        $other = preg_replace('/\D+/', '', (string)$p['Telefono']) ?? '';
        if (strlen($other) === 12 && str_starts_with($other, '52')) $other = substr($other, 2);
        if ($other === $digits) return (string)$p['Nombre_Completo'];
    }
    return null;
}

function requireFreePhone(string $digits, ?int $exceptPadre = null): void {
    $owner = phoneOwner($digits, $exceptPadre);
    if ($owner !== null) throw new DomainException('Ese número de teléfono ya está registrado con otro padre o tutor. Cada familia debe usar un teléfono diferente.', 409);
}

function requireFreeAccount(string $username, string $email, ?int $exceptUser = null): void {
    $u = one('SELECT ID_Usuario,Usuario,Correo FROM usuario WHERE (Usuario=? OR Correo=?)'.($exceptUser ? ' AND ID_Usuario<>?' : ''), $exceptUser ? [$username, $email, $exceptUser] : [$username, $email]);
    if (!$u) return;
    if (mb_strtolower((string)$u['Correo']) === mb_strtolower($email)) throw new DomainException('Ese correo ya está registrado en otra cuenta.', 409);
    throw new DomainException('Ese nombre de usuario ya existe. Elige otro.', 409);
}

/** Sugiere un usuario libre a partir del nombre: nombre.apellido, nombre.apellido2, … */
function suggestUsername(string $name, string $lastName): string {
    $clean = static function (string $s): string {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $s = preg_replace('/[^a-z0-9]+/', '', explode(' ', $s)[0]) ?? '';
        return $s;
    };
    $base = trim($clean($name).'.'.$clean($lastName), '.');
    if (strlen($base) < 4) $base = 'familia.'.$base;
    $base = substr($base, 0, 44);
    $candidate = $base; $n = 1;
    while (one('SELECT 1 FROM usuario WHERE Usuario=?', [$candidate])) { $n++; $candidate = $base.$n; }
    return $candidate;
}

function invitationEnsureSchema(): void {
    static $ready = false;
    if ($ready) return;
    query("CREATE TABLE IF NOT EXISTS invitacion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ID_Usuario INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        vence DATETIME NOT NULL,
        usado DATETIME NULL,
        creado_por INT NULL,
        KEY idx_invitacion_token (token_hash),
        KEY idx_invitacion_usuario (ID_Usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready = true;
}

/** Crea una invitación nueva (anula las anteriores) y devuelve los datos para enviarla. */
function issueInvitation(int $userId, array $by, int $days = 7): array {
    invitationEnsureSchema();
    $user = one('SELECT u.ID_Usuario,u.Nombre,u.Usuario,u.Correo,p.Telefono FROM usuario u LEFT JOIN padre_tutor p ON p.ID_Usuario=u.ID_Usuario WHERE u.ID_Usuario=?', [$userId]);
    if (!$user) throw new DomainException('La cuenta no existe.', 404);
    $token = bin2hex(random_bytes(24));
    query('UPDATE invitacion SET vence=NOW() WHERE ID_Usuario=? AND usado IS NULL AND vence>NOW()', [$userId]);
    query('INSERT INTO invitacion(ID_Usuario,token_hash,vence,creado_por) VALUES (?,?,DATE_ADD(NOW(),INTERVAL '.max(1, min(30, $days)).' DAY),?)', [$userId, hash('sha256', $token), (int)$by['ID_Usuario']]);
    $expires = (new DateTimeImmutable('+'.$days.' days'))->format('d/m/Y');
    return invitationMessage($user, $token, $expires);
}

function absoluteUrl(string $path): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^a-zA-Z0-9.:\-\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
    return ($https ? 'https' : 'http').'://'.$host.url($path);
}

function invitationMessage(array $user, string $token, string $expires): array {
    $branding = schoolBranding();
    $school = $branding['nombre_escuela'] !== '' ? $branding['nombre_escuela'] : 'la escuela';
    $link = absoluteUrl('invitacion.php?codigo='.$token);
    $first = explode(' ', (string)$user['Nombre'])[0];
    $text = "Hola {$first}. Dirección de {$school} te registró en {$branding['nombre_sistema']} para que consultes la información escolar de tu hijo o hija.\n\n"
        ."Tu usuario es: {$user['Usuario']}\n"
        ."Crea tu contraseña en este enlace (vence el {$expires}):\n{$link}";
    $phone = preg_replace('/\D+/', '', (string)($user['Telefono'] ?? '')) ?? '';
    $subject = 'Invitación a '.$branding['nombre_sistema'];
    return [
        'id' => (int)$user['ID_Usuario'],
        'codigo' => $token,
        'asunto' => $subject,
        'nombre' => $user['Nombre'],
        'usuario' => $user['Usuario'],
        'correo' => $user['Correo'],
        'telefono' => $phone,
        'vence' => $expires,
        'enlace' => $link,
        'texto' => $text,
    ];
}

/** Busca una invitación vigente por el código recibido en el enlace. */
function findInvitation(string $token, bool $lock = false): ?array {
    if (!preg_match('/^[a-f0-9]{48}$/', $token)) return null;
    invitationEnsureSchema();
    return one('SELECT i.*,u.Nombre,u.Usuario,u.Correo,u.activo FROM invitacion i JOIN usuario u ON u.ID_Usuario=i.ID_Usuario WHERE i.token_hash=? AND i.usado IS NULL AND i.vence>NOW()'.($lock ? ' FOR UPDATE' : ''), [hash('sha256', $token)]);
}

/** Alumnos inscritos en más de un grupo dentro del mismo ciclo (no debería pasar). */
function duplicateEnrollments(int $cycle): array {
    return rows("SELECT i.ID_Alumno,CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre,GROUP_CONCAT(i.grupo_nombre ORDER BY i.grupo_nombre SEPARATOR ', ') grupos,COUNT(*) total FROM inscripcion i JOIN alumno a ON a.ID_Alumno=i.ID_Alumno WHERE i.id_ciclo=? AND i.activo=1 GROUP BY i.ID_Alumno,a.Nombre,a.Primer_Apellido,a.Segundo_Apellido HAVING COUNT(*)>1", [$cycle]);
}

/** Inscribe (o cambia de grupo) a un alumno en un ciclo, garantizando un solo grupo por ciclo. */
function setEnrollment(int $student, int $cycle, int $group, string $groupName): string {
    $existing = rows('SELECT * FROM inscripcion WHERE ID_Alumno=? AND id_ciclo=? ORDER BY activo DESC,verificado DESC FOR UPDATE', [$student, $cycle]);
    if (!$existing) {
        query('INSERT INTO inscripcion(ID_Alumno,id_ciclo,ID_Grupo,grupo_nombre,activo,verificado) VALUES (?,?,?,?,1,1)', [$student, $cycle, $group, $groupName]);
        return 'nuevo';
    }
    $keep = $existing[0];
    // Si por algún error había más de una inscripción en el mismo ciclo, se deja solo una.
    foreach (array_slice($existing, 1) as $extra) query('UPDATE inscripcion SET activo=0 WHERE ID_Alumno=? AND id_ciclo=? AND ID_Grupo<=>? AND grupo_nombre=?', [$student, $cycle, $extra['ID_Grupo'], $extra['grupo_nombre']]);
    query('UPDATE inscripcion SET ID_Grupo=?,grupo_nombre=?,activo=1,verificado=1 WHERE ID_Alumno=? AND id_ciclo=? AND ID_Grupo<=>? AND grupo_nombre=?', [$group, $groupName, $student, $cycle, $keep['ID_Grupo'], $keep['grupo_nombre']]);
    return ((int)$keep['ID_Grupo'] === $group) ? 'igual' : 'cambio';
}

/*
 * ===== Promoción automática al siguiente grado =====
 * Al crear el ciclo nuevo, cada alumno del ciclo anterior:
 *  - Aprueba todos los campos formativos (promedio final ≥ 6.0 en cada uno) → pasa al grado siguiente, misma letra (1° A → 2° A).
 *  - Reprueba algún campo → repite el grado en su mismo grupo.
 *  - Termina 6° aprobado → egresa (no se inscribe; opcionalmente se da de baja conservando su historial).
 *  - Le faltan calificaciones → se queda en su grupo marcado "por confirmar".
 */
const MAD_CALIFICACION_APROBATORIA = 6.0;

/** Último ciclo cerrado (el que sirve de base para la promoción). */
function lastClosedCycle(): ?array {
    return one("SELECT * FROM ciclo_escolar WHERE estado='CERRADO' ORDER BY fecha_fin DESC,id_ciclo DESC LIMIT 1");
}

function promotionPlan(int $fromCycle): array {
    $cycle = one('SELECT * FROM ciclo_escolar WHERE id_ciclo=?', [$fromCycle]);
    if (!$cycle) return [];
    $fields = cycleFields($fromCycle);
    $periods = (int)$cycle['periodos'];
    $groups = [];
    foreach (rows('SELECT ID_Grupo,Grado,Grupo FROM grupo') as $g) $groups[(int)$g['Grado'].'|'.mb_strtoupper((string)$g['Grupo'])] = $g;
    $students = rows("SELECT i.ID_Alumno,i.ID_Grupo,COALESCE(CONCAT(g.Grado,'° ',g.Grupo),i.grupo_nombre) grupo_nombre,g.Grado,g.Grupo,CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre
        FROM inscripcion i JOIN alumno a ON a.ID_Alumno=i.ID_Alumno LEFT JOIN grupo g ON g.ID_Grupo=i.ID_Grupo
        WHERE i.id_ciclo=? AND i.activo=1 AND a.activo=1 ORDER BY g.Grado,g.Grupo,a.Primer_Apellido,a.Segundo_Apellido,a.Nombre", [$fromCycle]);
    $marks = [];
    foreach (rows('SELECT ID_Alumno,ID_Campo,Bimestre,Calificacion_Numerica FROM calificacion WHERE id_ciclo=?', [$fromCycle]) as $m) {
        if ((int)$m['Bimestre'] <= $periods) $marks[(int)$m['ID_Alumno']][(int)$m['ID_Campo']][] = (float)$m['Calificacion_Numerica'];
    }
    $plan = [];
    foreach ($students as $s) {
        $sid = (int)$s['ID_Alumno']; $complete = true; $failed = []; $sum = 0.0; $n = 0;
        foreach ($fields as $f) {
            $vals = $marks[$sid][(int)$f['ID_Campo']] ?? [];
            if (count($vals) < $periods) { $complete = false; continue; }
            $avg = round(array_sum($vals) / count($vals), 1, PHP_ROUND_HALF_UP); $sum += $avg; $n++;
            if ($avg < MAD_CALIFICACION_APROBATORIA) $failed[] = $f['Nombre_Campo'];
        }
        $grade = (int)($s['Grado'] ?? 0); $letter = mb_strtoupper((string)($s['Grupo'] ?? ''));
        $item = ['ID_Alumno'=>$sid,'nombre'=>$s['nombre'],'grupo_id'=>$s['ID_Grupo'] ? (int)$s['ID_Grupo'] : null,'grupo'=>$s['grupo_nombre'],
            'promedio'=>$n ? round($sum / $n, 1) : null,'reprobados'=>$failed,'destino_id'=>null,'destino'=>'','crear'=>null];
        if (!$s['ID_Grupo'] || !$grade) { $item['resultado'] = 'sin_grupo'; $item['destino'] = 'Grupo por confirmar'; }
        elseif (!$complete || !$fields) { $item['resultado'] = 'incompleto'; $item['destino_id'] = (int)$s['ID_Grupo']; $item['destino'] = $s['grupo_nombre'].' (por confirmar)'; }
        elseif ($failed) { $item['resultado'] = 'repite'; $item['destino_id'] = (int)$s['ID_Grupo']; $item['destino'] = $s['grupo_nombre']; }
        elseif ($grade >= 6) { $item['resultado'] = 'egresa'; $item['destino'] = 'Egresa de primaria'; }
        else {
            $item['resultado'] = 'promueve'; $next = $groups[($grade + 1).'|'.$letter] ?? null;
            if ($next) { $item['destino_id'] = (int)$next['ID_Grupo']; $item['destino'] = ($grade + 1).'° '.$next['Grupo']; }
            else { $item['crear'] = [$grade + 1, $letter]; $item['destino'] = ($grade + 1).'° '.$letter.' (se creará)'; }
        }
        $plan[] = $item;
    }
    return $plan;
}

function promotionSummary(array $plan): array {
    $c = ['promueve'=>0,'repite'=>0,'egresa'=>0,'incompleto'=>0,'sin_grupo'=>0];
    foreach ($plan as $p) $c[$p['resultado']]++;
    return $c;
}

/** Aplica la promoción al ciclo nuevo (debe estar PLANEADO). Devuelve el resumen. */
function applyPromotion(array $u, int $fromCycle, int $toCycle, bool $graduateInactive): array {
    $plan = promotionPlan($fromCycle);
    $created = [];
    foreach ($plan as $p) {
        if ($p['crear']) {
            [$g, $l] = $p['crear']; $key = $g.'|'.$l;
            if (!isset($created[$key])) {
                $found = one('SELECT ID_Grupo FROM grupo WHERE Grado=? AND Grupo=?', [(string)$g, $l]);
                $created[$key] = $found ? (int)$found['ID_Grupo'] : (function () use ($g, $l) { query('INSERT INTO grupo (Grado,Grupo) VALUES (?,?)', [(string)$g, $l]); return (int)db()->insert_id; })();
            }
            $p['destino_id'] = $created[$key]; $p['destino'] = $g.'° '.$l;
        }
        switch ($p['resultado']) {
            case 'promueve': case 'repite':
                setEnrollment($p['ID_Alumno'], $toCycle, (int)$p['destino_id'], preg_replace('/ \(.*\)$/', '', $p['destino']));
                break;
            case 'incompleto':
                setEnrollment($p['ID_Alumno'], $toCycle, (int)$p['destino_id'], (string)$p['grupo']);
                query('UPDATE inscripcion SET verificado=0 WHERE ID_Alumno=? AND id_ciclo=?', [$p['ID_Alumno'], $toCycle]);
                break;
            case 'egresa':
                query('DELETE FROM inscripcion WHERE ID_Alumno=? AND id_ciclo=? AND NOT EXISTS(SELECT 1 FROM calificacion c WHERE c.ID_Alumno=inscripcion.ID_Alumno AND c.id_ciclo=inscripcion.id_ciclo)', [$p['ID_Alumno'], $toCycle]);
                if ($graduateInactive) { query('UPDATE alumno SET activo=0 WHERE ID_Alumno=?', [$p['ID_Alumno']]); audit($u, 'EGRESO', 'alumno', $p['ID_Alumno']); }
                break;
        }
    }
    $summary = promotionSummary($plan);
    audit($u, 'PROMOCION', 'ciclo', $toCycle, ['ciclo' => $fromCycle, 'promovidos' => $summary['promueve'], 'repiten' => $summary['repite'], 'egresados' => $summary['egresa']]);
    return $summary + ['grupos_creados' => count(array_filter($created))];
}

/*
 * ===== Revisión de datos =====
 * Detecta inconsistencias heredadas de versiones anteriores y corrige las que son seguras.
 */
function dataHealth(): array {
    $issues = [];
    $open = rows("SELECT id_ciclo,nombre,estado,(SELECT COUNT(*) FROM calificacion c WHERE c.id_ciclo=ce.id_ciclo) notas,(SELECT COUNT(*) FROM asignacion_ciclo a WHERE a.id_ciclo=ce.id_ciclo) asignaciones FROM ciclo_escolar ce WHERE estado<>'CERRADO' ORDER BY estado='ACTIVO' DESC,id_ciclo");
    if (count($open) > 1) $issues['ciclos'] = $open;
    $names = (int)(one("SELECT COUNT(*) n FROM inscripcion i JOIN grupo g ON g.ID_Grupo=i.ID_Grupo WHERE i.grupo_nombre<>CONCAT(g.Grado,'° ',g.Grupo)")['n'] ?? 0);
    if ($names) $issues['nombres'] = $names;
    $active = activeCycleId();
    if ($active) {
        $sync = (int)(one('SELECT COUNT(*) n FROM alumno a JOIN inscripcion i ON i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? AND i.activo=1 AND i.verificado=1 WHERE i.ID_Grupo IS NOT NULL AND NOT (a.ID_Grupo<=>i.ID_Grupo) AND a.activo=1', [$active])['n'] ?? 0);
        if ($sync) $issues['sincronizar'] = $sync;
        $missing = rows("SELECT a.ID_Alumno,CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre FROM alumno a WHERE a.activo=1 AND NOT EXISTS(SELECT 1 FROM inscripcion i WHERE i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? AND i.activo=1) ORDER BY a.Primer_Apellido LIMIT 200", [$active]);
        if ($missing) $issues['sin_inscripcion'] = $missing;
    }
    $phones = [];
    foreach (rows("SELECT Nombre_Completo,Telefono FROM padre_tutor WHERE Telefono IS NOT NULL AND Telefono<>''") as $p) {
        $d = preg_replace('/\D+/', '', (string)$p['Telefono']) ?? '';
        if (strlen($d) === 12 && str_starts_with($d, '52')) $d = substr($d, 2);
        $phones[$d][] = $p['Nombre_Completo'];
    }
    $dupPhones = array_filter($phones, fn($v, $k) => count($v) > 1 && $k !== '', ARRAY_FILTER_USE_BOTH);
    if ($dupPhones) $issues['telefonos'] = $dupPhones;
    $badPhones = array_filter($phones, fn($v, $k) => strlen((string)$k) !== 10, ARRAY_FILTER_USE_BOTH);
    if ($badPhones) $issues['telefonos_formato'] = $badPhones;
    $fake = fakeLookingRecords();
    if ($fake) $issues['prueba'] = $fake;
    return $issues;
}

/** Corrige lo seguro: nombres de grupo de las inscripciones y grupo actual del alumno. */
function repairData(array $u): array {
    $names = query("UPDATE inscripcion i JOIN grupo g ON g.ID_Grupo=i.ID_Grupo SET i.grupo_nombre=CONCAT(g.Grado,'° ',g.Grupo) WHERE i.grupo_nombre<>CONCAT(g.Grado,'° ',g.Grupo)")->affected_rows;
    $sync = 0;
    if ($active = activeCycleId()) {
        $sync = query('UPDATE alumno a JOIN inscripcion i ON i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? AND i.activo=1 AND i.verificado=1 SET a.ID_Grupo=i.ID_Grupo WHERE i.ID_Grupo IS NOT NULL AND NOT (a.ID_Grupo<=>i.ID_Grupo) AND a.activo=1', [$active])->affected_rows;
    }
    audit($u, 'REPARAR_DATOS', 'sistema', null, ['nombres' => $names, 'sincronizados' => $sync]);
    return ['nombres' => $names, 'sincronizados' => $sync];
}

/*
 * ===== Calidad de datos: detectar datos basura o imposibles antes de guardarlos =====
 */
function madPlaceholderWords(): array {
    return ['prueba','pruebas','test','tests','testing','tester','demo','ejemplo','example','asdf','asd','qwerty','qwe','xxx','xx','abc','abcd','nombre','nombres','apellido','apellidos','alumno','alumna','hijo','hija','padre','madre','mama','papa','tutor','tutora','maestro','maestra','docente','profesor','profesora','sin','ninguno','ninguna','null','none','undefined','admin','usuario','user','fulano','mengano','zutano','perengano','lorem','ipsum','hola','foo','bar','dummy','fake','falso','sample','jaja','jeje','xd','no tiene','na','n/a','sn','s/n','nn'];
}
function madFold(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    return strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}
/** Devuelve el motivo por el que un texto parece de prueba, o null si se ve normal. */
function madLooksFake(string $value): ?string {
    $f = madFold($value);
    if ($f === '') return null;
    $tokens = preg_split('/[^a-z0-9]+/', $f, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $words = madPlaceholderWords();
    if (in_array($f, $words, true)) return 'parece un dato de prueba';
    foreach ($tokens as $t) if (in_array($t, ['prueba','pruebas','test','testing','demo','asdf','qwerty','lorem','ipsum','dummy','fake','xxx'], true)) return 'parece un dato de prueba';
    if (preg_match('/(\p{L})\1\1/u', $f)) return 'tiene letras repetidas sin sentido';
    return null;
}
/** Valida y normaliza un nombre o apellido de persona. */
function validPersonName(string $value, string $label, bool $required = true): string {
    $v = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    if ($v === '') { if ($required) throw new DomainException('Escribe '.$label.'.'); return ''; }
    if (mb_strlen($v) < 2) throw new DomainException(ucfirst($label).' es demasiado corto.');
    if (mb_strlen($v) > 50) throw new DomainException(ucfirst($label).' es demasiado largo (máximo 50 letras).');
    if (preg_match('/\d/u', $v)) throw new DomainException(ucfirst($label).' no puede llevar números.');
    if (!preg_match("/^[\\p{L}][\\p{L}' .-]*[\\p{L}.]$/u", $v)) throw new DomainException(ucfirst($label).' solo puede llevar letras, espacios, guion o apóstrofo.');
    if (!preg_match('/[aeiouáéíóúüy]/iu', $v)) throw new DomainException('Revisa '.$label.': no parece un nombre real.');
    $fold = madFold($v);
    $words = madPlaceholderWords();
    foreach (preg_split('/[\s.\'-]+/', $fold, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $t) {
        if (in_array($t, $words, true) && !in_array($t, ['sin','na'], true)) throw new DomainException('“'.$v.'” no parece '.$label.' real (parece un dato de prueba). Escribe el nombre verdadero.');
    }
    if (in_array($fold, $words, true) || madLooksFake($v)) throw new DomainException('“'.$v.'” no parece '.$label.' real. Escribe el dato verdadero.');
    // Presentación uniforme: "PABLO de la cruz" → "Pablo de la Cruz".
    $out = mb_convert_case(mb_strtolower($v, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $out = preg_replace_callback("/(['’])(\p{Ll})/u", fn($m) => $m[1].mb_strtoupper($m[2]), $out) ?? $out;
    return preg_replace_callback('/\b(De|Del|La|Las|Los|Y|Van|Von)\b/u', fn($m) => mb_strtolower($m[1]), preg_replace('/^(de|del|la|las|los)\b/u', '$1', $out)) ?? $out;
}
/** Valida un correo electrónico real (no de prueba). */
function validEmail(string $value): string {
    $v = mb_strtolower(trim($value));
    if (!filter_var($v, FILTER_VALIDATE_EMAIL)) throw new DomainException('Escribe un correo válido (por ejemplo nombre@gmail.com).');
    [$local, $domain] = explode('@', $v, 2);
    $tld = substr(strrchr($domain, '.') ?: '', 1);
    if (strlen($tld) < 2 || in_array($tld, ['test','invalid','example','localhost','local','prueba'], true)) throw new DomainException('El correo '.$v.' no es de un dominio real.');
    if (in_array($domain, ['example.com','example.org','example.net','test.com','prueba.com','correo.com','email.com','ejemplo.com','dominio.com','mail.test','asdf.com','abc.com','xxx.com'], true)) throw new DomainException('El correo '.$v.' parece de prueba. Escribe el correo real.');
    if (in_array($local, ['test','prueba','asdf','correo','email','ejemplo','example','nombre','usuario','user','a','aa','abc','xxx','qwerty','admin','no','noreply','nada','ninguno'], true) || madLooksFake($local)) throw new DomainException('El correo '.$v.' parece de prueba. Escribe el correo real.');
    return $v;
}
/** Usuario de acceso razonable. */
function validUsername(string $value): string {
    $v = trim($value);
    // Mensajes específicos: la persona sabe exactamente qué corregir.
    if (str_contains($v, '@')) {
        $suggest = substr(preg_replace('/[^a-zA-Z0-9_.-]/', '', explode('@', $v)[0]) ?? '', 0, 50);
        throw new DomainException('En “Usuario” no va tu correo. Elige un nombre corto'.(strlen($suggest) >= 4 ? ', por ejemplo: '.$suggest : ', por ejemplo: nombre.apellido').'. Después podrás entrar con tu usuario o con tu correo.');
    }
    if (preg_match('/\s/u', $v)) throw new DomainException('El usuario no puede llevar espacios. Usa un punto o guion bajo, por ejemplo: '.(preg_replace('/\s+/u', '.', $v) ?? 'nombre.apellido').'.');
    if (preg_match('/[áéíóúüñÁÉÍÓÚÜÑ]/u', $v)) throw new DomainException('El usuario no puede llevar acentos ni ñ. Por ejemplo, escribe “n” en lugar de “ñ” y “e” en lugar de “é”.');
    $len = mb_strlen($v);
    if ($len < 4) throw new DomainException('El usuario es muy corto: necesita al menos 4 caracteres.');
    if ($len > 50) throw new DomainException('El usuario es muy largo: máximo 50 caracteres.');
    if (!preg_match('/^[a-zA-Z0-9_.-]{4,50}$/', $v)) throw new DomainException('El usuario solo puede llevar letras sin acento, números, punto (.), guion (-) o guion bajo (_). Quita los demás símbolos.');
    if (ctype_digit($v)) throw new DomainException('El usuario no puede ser solo números.');
    if (in_array(madFold($v), ['test','prueba','admin','administrador','usuario','user','asdf','qwerty','root','demo','1234','12345'], true) || madLooksFake($v)) throw new DomainException('Ese usuario parece de prueba. Usa algo como nombre.apellido.');
    return $v;
}
/** Valida la CURP: formato, fecha real, estado real y edad posible para primaria. */
function validCurp(string $value, int $minAge = 4, int $maxAge = 16): string {
    $c = mb_strtoupper(trim($value));
    if (!preg_match('/^[A-Z][AEIOUX][A-Z]{2}(\d{2})(\d{2})(\d{2})[HMX](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}([A-Z0-9])(\d)$/', $c, $m)) {
        if (strlen($c) !== 18) throw new DomainException('La CURP debe tener exactamente 18 caracteres (tiene '.strlen($c).').');
        throw new DomainException('La CURP no tiene un formato válido. Revisa las letras, la fecha de nacimiento y el estado.');
    }
    $year = (ctype_digit($m[4]) ? 1900 : 2000) + (int)$m[1];
    if (!checkdate((int)$m[2], (int)$m[3], $year)) throw new DomainException('La fecha de nacimiento de la CURP no existe ('.$m[3].'/'.$m[2].'/'.$year.').');
    $birth = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, (int)$m[2], (int)$m[3]));
    $age = $birth->diff(new DateTimeImmutable('today'))->y;
    if ($birth > new DateTimeImmutable('today')) throw new DomainException('La CURP indica una fecha de nacimiento en el futuro.');
    if ($age < $minAge || $age > $maxAge) throw new DomainException('Según la CURP el alumno tiene '.$age.' años. Para primaria debe tener entre '.$minAge.' y '.$maxAge.' años. Revisa la CURP.');
    return $c;
}
/** Teléfono real de 10 dígitos (sin 0000000000, 1234567890, etc.). */
function validPhone(string $raw): string {
    $d = normalizePhone($raw);
    if (preg_match('/^(\d)\1{9}$/', $d)) throw new DomainException('El teléfono '.$d.' no es real (todos los dígitos son iguales).');
    if (in_array($d, ['1234567890','0123456789','9876543210','0987654321'], true)) throw new DomainException('El teléfono '.$d.' no es real.');
    if ($d[0] === '0' || $d[0] === '1') throw new DomainException('Un teléfono de México no empieza con '.$d[0].'. Revisa el número.');
    if (preg_match('/^(\d{2})\1{4}$|^(\d{5})\2$/', $d)) throw new DomainException('El teléfono '.$d.' parece inventado.');
    return $d;
}
/** Título o texto libre con sentido (anuncios, incidencias). */
function validText(string $value, string $label, int $min = 3): string {
    $v = trim(preg_replace('/[ \t]+/u', ' ', $value) ?? '');
    if (mb_strlen($v) < $min) throw new DomainException(ucfirst($label).' es demasiado corto (mínimo '.$min.' caracteres).');
    if (!preg_match('/\p{L}/u', $v)) throw new DomainException(ucfirst($label).' debe tener palabras, no solo números o símbolos.');
    if (in_array(madFold($v), madPlaceholderWords(), true) || preg_match('/^(prueba|test|asdf|qwerty|xxx|hola|aaa+)\s*\d*$/u', madFold($v))) throw new DomainException('“'.$v.'” parece texto de prueba. Escribe '.$label.' real.');
    if (preg_match('/(.)\1{5,}/u', $v)) throw new DomainException(ucfirst($label).' tiene caracteres repetidos sin sentido.');
    return $v;
}
/** Fecha de un suceso escolar: no futura y no absurda. */
function validEventDate(string $date, string $label = 'La fecha'): string {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $today = new DateTimeImmutable('today');
    if (!$d) throw new DomainException($label.' no es válida.');
    if ($d > $today) throw new DomainException($label.' no puede ser en el futuro.');
    if ($d < $today->modify('-2 years')) throw new DomainException($label.' es de hace más de 2 años. Revisa el año.');
    return $date;
}
/** Ciclo escolar con nombre y fechas lógicas: "2026-2027", inicia en 2026 y termina en 2027, de 6 a 13 meses. */
function validCycle(string $name, string $start, string $end): void {
    if (!preg_match('/^(\d{4})-(\d{4})$/', $name, $m)) throw new DomainException('El nombre del ciclo debe ser como 2026-2027.');
    [$y1, $y2] = [(int)$m[1], (int)$m[2]];
    if ($y2 !== $y1 + 1) throw new DomainException('Un ciclo escolar abarca dos años seguidos (por ejemplo 2026-2027), no '.$name.'.');
    $thisYear = (int)date('Y');
    $lastStart = (int)(one("SELECT MAX(CAST(SUBSTRING(nombre,1,4) AS UNSIGNED)) y FROM ciclo_escolar WHERE nombre REGEXP '^[0-9]{4}-[0-9]{4}$'")['y'] ?? 0);
    if ($y1 < 2000 || ($y1 > $thisYear + 2 && $y1 !== $lastStart + 1)) throw new DomainException('El año '.$y1.' no es razonable para un ciclo escolar.');
    $s = new DateTimeImmutable($start); $e = new DateTimeImmutable($end);
    if ((int)$s->format('Y') !== $y1 || (int)$e->format('Y') !== $y2) throw new DomainException('Las fechas no coinciden con el nombre: el ciclo '.$name.' debe iniciar en '.$y1.' y terminar en '.$y2.'.');
    $months = $s->diff($e)->m + 12 * $s->diff($e)->y;
    if ($months < 6 || $months > 13) throw new DomainException('Un ciclo escolar dura entre 6 y 13 meses (este dura '.$months.').');
}
/** Registros ya guardados que parecen de prueba (para "Revisión de datos"). */
function fakeLookingRecords(): array {
    $out = [];
    foreach (rows("SELECT 'Alumno' tipo,ID_Alumno id,CONCAT_WS(' ',Nombre,Primer_Apellido,Segundo_Apellido) nombre FROM alumno WHERE activo=1 UNION ALL SELECT 'Docente',d.ID_Docente,CONCAT_WS(' ',d.Nombre,d.Primer_Apellido,d.Segundo_Apellido) FROM docente d JOIN usuario u ON u.ID_Usuario=d.ID_Usuario WHERE u.activo=1 UNION ALL SELECT 'Padre/tutor',p.ID_Padre,p.Nombre_Completo FROM padre_tutor p JOIN usuario u ON u.ID_Usuario=p.ID_Usuario WHERE u.activo=1") as $r) {
        $why = madLooksFake((string)$r['nombre']);
        if (!$why) foreach (preg_split('/\s+/', madFold((string)$r['nombre'])) ?: [] as $t) if (in_array($t, ['prueba','test','demo','asdf','alumno','padre','madre','mama','papa','tutor','nombre','apellido','fulano','ejemplo'], true) || preg_match('/\d/', $t)) { $why = 'parece un dato de prueba'; break; }
        if ($why) $out[] = $r + ['motivo' => $why];
    }
    foreach (rows("SELECT 'Anuncio' tipo,ID_Publicacion id,Titulo nombre FROM publicacion UNION ALL SELECT 'Incidencia',ID_Incidencia,Tipo_Incidencia FROM incidencia") as $r) {
        if (preg_match('/^(prueba|test|asdf|qwerty|xxx|hola|aaa+)\s*\d*$/u', madFold((string)$r['nombre']))) $out[] = $r + ['motivo' => 'parece un dato de prueba'];
    }
    return array_slice($out, 0, 200);
}
