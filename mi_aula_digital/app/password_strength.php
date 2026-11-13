<?php
declare(strict_types=1);

/*
 * Escala de seguridad de contraseñas.
 * Reglas (observación de la revisión): mínimo 8 caracteres con mayúscula,
 * minúscula, número y símbolo. Además se calcula cuánto tardaría un equipo de
 * ataque (un millón de intentos por segundo contra la contraseña cifrada con
 * bcrypt, como la guarda el sistema) en adivinarla probando combinaciones.
 * La misma fórmula está en assets/password-strength.js para que el padre de
 * familia vea el resultado mientras escribe; aquí se vuelve a comprobar para
 * que nadie pueda saltarse la regla.
 */

const MAD_PASSWORD_GUESSES_PER_SECOND = 1e6;
const MAD_PASSWORD_MIN_LENGTH = 8;
const MAD_PASSWORD_MIN_YEARS = 40;

function madCommonPasswordWords(): array {
    return ['password','passw0rd','contrasena','contraseña','clave','qwerty','asdfgh','zxcvbn','qwertyuiop','123456','1234567890','abc123','admin','administrador','usuario','escuela','maestro','maestra','alumno','profesor','mexico','hidalgo','amor','teamo','iloveyou','familia','hola','futbol','america','chivas','pumas','cruzazul','princesa','dragon','monkey','letmein','welcome','bienvenido','secreto','superman','batman','pokemon','miaula','aula','digital','mama','papa','hijo','hija','dios','jesus','maria','jose','juan','luis','carlos','angel','daniel','miguel','sofia','fernanda','guadalupe','lupita','corazon','estrella','mariposa','tequila','abcdef','abcd','1111','0000','1234','4321','2020','2021','2022','2023','2024','2025','2026','2027'];
}

function madPasswordNormalize(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','@'=>'a','4'=>'a','3'=>'e','1'=>'i','!'=>'i','0'=>'o','$'=>'s','5'=>'s','7'=>'t']);
    return $s;
}

/** @param string[] $personal  nombre, apellidos, usuario, correo, teléfono… */
function passwordStrength(string $pass, array $personal = []): array {
    $chars = preg_split('//u', $pass, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $len = count($chars);
    if ($len === 0) return ['seconds'=>0.0,'years'=>0.0,'level'=>0,'label'=>'Sin contraseña','time'=>'al instante'];

    $pool = 0;
    if (preg_match('/[a-zñáéíóúü]/u', $pass)) $pool += 26;
    if (preg_match('/[A-ZÑÁÉÍÓÚÜ]/u', $pass)) $pool += 26;
    if (preg_match('/[0-9]/', $pass)) $pool += 10;
    if (preg_match('/[^a-zA-Z0-9ñÑáéíóúüÁÉÍÓÚÜ]/u', $pass)) $pool += 33;
    $pool = max($pool, 10);

    // Caracteres repetidos (aaaa) o en secuencia (abcd, 1234, 9876) valen menos.
    $eff = 1.0; $prevD = null;
    for ($i = 1; $i < $len; $i++) {
        $d = mb_ord(mb_strtolower($chars[$i])) - mb_ord(mb_strtolower($chars[$i - 1]));
        $eff += ($d === 0 || (abs($d) === 1 && $d === $prevD)) ? 0.25 : 1.0;
        $prevD = $d;
    }

    // Palabras comunes y datos personales casi no cuentan: se adivinan primero.
    $norm = madPasswordNormalize($pass);
    $plainLower = mb_strtolower($pass, 'UTF-8');
    $words = madCommonPasswordWords();
    foreach ($personal as $p) {
        foreach (preg_split('/[\s@._+-]+/u', (string)$p) ?: [] as $piece) {
            if (mb_strlen($piece) >= 3) $words[] = $piece;
        }
    }
    $seen = [];
    foreach ($words as $w) {
        $w = madPasswordNormalize($w);
        if (mb_strlen($w) < 3 || isset($seen[$w])) continue;
        $seen[$w] = true;
        if (str_contains($norm, $w) || str_contains($plainLower, $w)) $eff -= (mb_strlen($w) - 1.5);
    }

    $stripped = preg_replace('/[^a-zñ]/u', '', $norm) ?? '';
    if (in_array($norm, array_map('madPasswordNormalize', madCommonPasswordWords()), true)
        || ($stripped !== '' && in_array($stripped, madCommonPasswordWords(), true) && mb_strlen($stripped) >= $len - 4)) {
        $eff = min($eff, 2.0);
    }
    $eff = max($eff, 0.0);

    $log10Seconds = $eff * log10($pool) - log10(MAD_PASSWORD_GUESSES_PER_SECOND);
    $seconds = $log10Seconds > 300 ? INF : 10 ** $log10Seconds;
    if ($eff <= 2.0) $seconds = min($seconds, 0.5);
    $years = $seconds / 31557600;

    if ($years >= 10000) { $level = 4; $label = 'Muy fuerte'; }
    elseif ($years >= MAD_PASSWORD_MIN_YEARS) { $level = 3; $label = 'Fuerte'; }
    elseif ($years >= 1) { $level = 2; $label = 'Regular'; }
    elseif ($seconds >= 86400) { $level = 1; $label = 'Débil'; }
    else { $level = 0; $label = 'Muy débil'; }

    return ['seconds'=>$seconds,'years'=>$years,'level'=>$level,'label'=>$label,'time'=>passwordTimeText($seconds)];
}

function passwordTimeText(float $s): string {
    if ($s < 1) return 'al instante';
    $units = [[60,'segundo','segundos',1],[3600,'minuto','minutos',60],[86400,'hora','horas',3600],[2629800,'día','días',86400],[31557600,'mes','meses',2629800]];
    foreach ($units as [$limit,$one,$many,$div]) {
        if ($s < $limit) { $n = (int)floor($s / $div); return $n.' '.($n === 1 ? $one : $many); }
    }
    $y = $s / 31557600;
    if ($y < 1000) { $n = (int)floor($y); return $n.' '.($n === 1 ? 'año' : 'años'); }
    if ($y < 1e6) return 'miles de años';
    if ($y < 1e9) return 'millones de años';
    return 'más de mil millones de años';
}

/** Lanza un error si la contraseña no llega al mínimo de la escala. */
function passwordMissingRules(string $pass): array {
    $missing = [];
    if (mb_strlen($pass) < MAD_PASSWORD_MIN_LENGTH) $missing[] = 'al menos '.MAD_PASSWORD_MIN_LENGTH.' caracteres';
    if (!preg_match('/[A-ZÑÁÉÍÓÚÜ]/u', $pass)) $missing[] = 'una letra mayúscula';
    if (!preg_match('/[a-zñáéíóúü]/u', $pass)) $missing[] = 'una letra minúscula';
    if (!preg_match('/[0-9]/', $pass)) $missing[] = 'un número';
    if (!preg_match('/[^a-zA-Z0-9ñÑáéíóúüÁÉÍÓÚÜ\s]/u', $pass)) $missing[] = 'un símbolo especial (# $ % & * ! ? @ -)';
    return $missing;
}

function requireStrongPassword(string $pass, array $personal = []): void {
    $missing = passwordMissingRules($pass);
    if ($missing) throw new DomainException('A tu contraseña le falta: '.implode(', ', $missing).'.');
    $r = passwordStrength($pass, $personal);
    if ($r['years'] < MAD_PASSWORD_MIN_YEARS) {
        throw new DomainException('Tu contraseña es muy fácil de adivinar. Hazla más larga y evita tu nombre, tu usuario, años o palabras comunes.');
    }
}
