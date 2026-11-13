<?php

declare(strict_types=1);

/**
 * Configuración visual por instalación/escuela.
 * Se guarda en una tabla clave/valor para poder ampliar el editor sin migraciones
 * destructivas ni tocar los datos académicos.
 */
function brandingDefaults(): array {
    return [
        // Identidad
        'nombre_sistema'        => 'Mi Aula Digital',
        'nombre_escuela'        => '',
        'lema'                  => 'Un espacio para aprender.',
        'pie'                   => 'Cada avance cuenta.',
        'logo'                  => '',
        'fondo_login'           => '',
        'version_tema'          => '6',

        // Inicio de sesión
        'login_titulo'          => "Tu comunidad.\nMás cerca.\nMás simple.",
        'login_texto'           => 'Un lugar claro y seguro para acompañar el aprendizaje, conectar con la escuela y seguir cada avance.',
        'login_tarjeta_titulo'  => 'Qué gusto verte.',
        'login_tarjeta_texto'   => 'Entra a tu espacio con tu cuenta de la escuela.',
        'login_roles'           => 'DIRECCIÓN · DOCENTES · FAMILIAS',

        // Panel / bienvenida
        'menu_etiqueta'         => 'Tu espacio',
        'panel_titulo'          => 'Todo empieza aquí.',
        'panel_subtitulo'       => 'Lo esencial de tu comunidad, en un solo lugar.',
        'hero_etiqueta'         => 'Tu comunidad, conectada',
        'hero_titulo'           => "Hola, {nombre}.\nHoy también cuenta.",
        'hero_direccion'        => 'Una mirada tranquila a lo que sucede en tu escuela. Organiza, acompaña y mantén a todos conectados.',
        'hero_docente'          => 'Cada pequeño avance construye algo grande. Aquí tienes todo para acompañar a tu grupo.',
        'hero_familia'          => 'La escuela y la familia, en el mismo equipo. Aquí puedes seguir sus avances y novedades.',
        'panel_lista_titulo'    => 'Tu comunidad escolar',
        'panel_lista_texto'     => 'Información a la mano, sin complicaciones.',
        'panel_avisos_titulo'   => 'Lo más reciente',
        'panel_avisos_vacio'    => 'Todo está al día',
        'panel_avisos_vacio_txt'=> 'Los anuncios de la escuela aparecerán aquí.',

        // Colores
        'color_principal'       => '#d00032',
        'color_secundario'      => '#9e1634',
        'color_fondo'           => '#f5f3f4',
        'color_superficie'      => '#ffffff',
        'color_texto'           => '#050505',
        'color_sidebar'         => '#050002',
        'color_sidebar_texto'   => '#ffffff',
        'color_hero'            => '#050002',
        'color_hero_texto'      => '#ffffff',
        'color_anuncio'         => '#fff7f9',
        'color_anuncio_texto'   => '#2d2025',
        'color_exito'            => '#168a58',
        'color_advertencia'      => '#c87900',
        'color_peligro'          => '#d00032',
        'color_info'             => '#2c63c7',

        // Iconos del menú (catálogo cerrado por seguridad)
        'icon_panel' => 'home',
        'icon_alumnos' => 'users',
        'icon_docentes' => 'book',
        'icon_solicitud' => 'shield',
        'icon_asignaciones' => 'school',
        'icon_calificaciones' => 'chart',
        'icon_incidencias' => 'shield',
        'icon_avisos' => 'bell',
        'icon_matriculas' => 'book',
        'icon_familias' => 'users',
        'icon_auditoria' => 'shield',
        'icon_accesos' => 'key',
        'icon_respaldo' => 'download',
        'icon_tokens' => 'key',
        'icon_ciclos' => 'calendar',
        'icon_personalizacion' => 'palette',
        // Acabado visual (rangos seguros)
        'radio_tarjetas'        => '24',
        'radio_botones'         => '12',
        'desenfoque'            => '30',
        'opacidad_cristal'      => '82',
        'intensidad_sombra'     => '12',
        'escala_fuente'         => '100',
        'ancho_sidebar'         => '248',
        'tipografia'            => 'sistema',
        'tipografia_personalizada' => '',
        'modo_texto'            => 'manual',
        'densidad'              => 'comoda',
        'fondo_login_posicion'  => 'center',
    ];
}

function brandingTextKeys(): array {
    return [
        'version_tema','nombre_sistema','nombre_escuela','lema','pie',
        'login_titulo','login_texto','login_tarjeta_titulo','login_tarjeta_texto','login_roles',
        'menu_etiqueta','panel_titulo','panel_subtitulo','hero_etiqueta','hero_titulo',
        'hero_direccion','hero_docente','hero_familia','panel_lista_titulo','panel_lista_texto',
        'panel_avisos_titulo','panel_avisos_vacio','panel_avisos_vacio_txt'
    ];
}

function brandingColorKeys(): array {
    return [
        'color_principal','color_secundario','color_fondo','color_superficie','color_texto',
        'color_sidebar','color_sidebar_texto','color_hero','color_hero_texto',
        'color_anuncio','color_anuncio_texto','color_exito','color_advertencia','color_peligro','color_info'
    ];
}

function brandingIconKeys(): array {
    return ['icon_panel','icon_alumnos','icon_docentes','icon_solicitud','icon_asignaciones','icon_calificaciones','icon_incidencias','icon_avisos','icon_matriculas','icon_familias','icon_auditoria','icon_accesos','icon_respaldo','icon_tokens','icon_ciclos','icon_personalizacion'];
}

function brandingIconRules(): array {
    return array_fill_keys(brandingIconKeys(), ['home','users','book','chart','shield','bell','key','calendar','download','school','palette']);
}

function brandingIconFor(array $branding, string $key, string $fallback): string {
    $value = (string)($branding[$key] ?? $fallback);
    $allowed = brandingIconRules()[$key] ?? [];
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function brandingNumericRules(): array {
    return [
        'radio_tarjetas'    => [12,40,24],
        'radio_botones'     => [6,28,12],
        'desenfoque'        => [0,48,30],
        'opacidad_cristal'  => [38,100,82],
        'intensidad_sombra' => [0,30,12],
        'escala_fuente'     => [90,108,100],
        'ancho_sidebar'     => [220,300,248],
    ];
}

function brandingEnumRules(): array {
    return [
        'tipografia'           => ['sistema','moderna','redondeada','serif'],
        'modo_texto'           => ['global','manual'],
        'densidad'             => ['compacta','comoda','amplia'],
        'fondo_login_posicion' => ['center','top','bottom','left','right'],
    ];
}

function brandingEnsureSchema(): void {
    static $ready = false;
    if ($ready) return;
    query("CREATE TABLE IF NOT EXISTS configuracion_escuela (
        clave VARCHAR(64) NOT NULL PRIMARY KEY,
        valor TEXT NOT NULL,
        actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready = true;
}

function brandingNormalizeColor(mixed $value, string $fallback): string {
    $value = is_string($value) ? trim($value) : '';
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtolower($value) : strtolower($fallback);
}

function brandingClampInt(mixed $value, int $min, int $max, int $fallback): int {
    if (is_int($value)) $number = $value;
    elseif (is_string($value) && preg_match('/^-?\d+$/', trim($value))) $number = (int)$value;
    else return $fallback;
    return max($min, min($max, $number));
}

function brandingNormalizeEnum(mixed $value, array $allowed, string $fallback): string {
    $value = is_string($value) ? trim($value) : '';
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function brandingNormalizeFontFamily(mixed $value): string {
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') return '';
    // Solo permitimos nombres de familia CSS seguros; nunca se aceptan ; { } < > ni URLs.
    $value = preg_replace('/[^A-Za-z0-9\s,\"\'._-]/u', '', $value) ?? '';
    $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
    return substr($value, 0, 160);
}

function schoolBranding(bool $createSchema = false): array {
    static $cache = null;
    static $loadedFromDb = false;
    $defaults = brandingDefaults();
    if (is_array($cache) && (!$createSchema || $loadedFromDb)) return $cache;

    try {
        if ($createSchema) {
            brandingEnsureSchema();
        } else {
            $exists = one("SELECT 1 ok FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='configuracion_escuela' LIMIT 1");
            if (!$exists) { $cache = $defaults; $loadedFromDb = false; return $cache; }
        }

        $data = $defaults;
        foreach (rows('SELECT clave,valor FROM configuracion_escuela') as $row) {
            $key = (string)$row['clave'];
            if (array_key_exists($key, $data)) $data[$key] = (string)$row['valor'];
        }
        $legacyGlobalText = in_array((string)($data['modo_texto'] ?? ''), ['auto','global'], true);

        foreach (brandingColorKeys() as $key) {
            $data[$key] = brandingNormalizeColor($data[$key], $defaults[$key]);
        }
        // Las instalaciones que todavía guardaban el antiguo modo global pasan
        // automáticamente al modo por zonas. Si el color global quedó ilegible
        // sobre fondo/tarjetas, elegimos solo para esa migración el negro o blanco
        // que conserve mejor contraste; después Dirección vuelve a decidirlo manualmente.
        if ($legacyGlobalText) {
            $currentText = $data['color_texto'];
            $currentScore = min(brandingContrast($currentText, $data['color_fondo']), brandingContrast($currentText, $data['color_superficie']));
            if ($currentScore < 3.0) {
                $black = '#111111'; $white = '#ffffff';
                $blackScore = min(brandingContrast($black, $data['color_fondo']), brandingContrast($black, $data['color_superficie']));
                $whiteScore = min(brandingContrast($white, $data['color_fondo']), brandingContrast($white, $data['color_superficie']));
                $data['color_texto'] = $blackScore >= $whiteScore ? $black : $white;
            }
            $data['modo_texto'] = 'manual';
        }
        $data = array_replace($defaults, $data);
        foreach (brandingTextKeys() as $key) {
            $data[$key] = trim((string)($data[$key] ?? $defaults[$key] ?? ''));
        }
        foreach (brandingNumericRules() as $key => [$min,$max,$fallback]) {
            $data[$key] = (string)brandingClampInt($data[$key] ?? null, $min, $max, $fallback);
        }
        foreach (brandingEnumRules() as $key => $allowed) {
            if ($key === 'modo_texto' && in_array((string)($data[$key] ?? ''), ['auto','global'], true)) $data[$key] = 'manual';
            $data[$key] = brandingNormalizeEnum($data[$key] ?? null, $allowed, (string)($defaults[$key] ?? $allowed[0]));
        }
        foreach (brandingIconRules() as $key => $allowed) {
            $data[$key] = brandingNormalizeEnum($data[$key] ?? null, $allowed, (string)($defaults[$key] ?? $allowed[0]));
        }

        if ($data['nombre_sistema'] === '') $data['nombre_sistema'] = $defaults['nombre_sistema'];
        if ($data['lema'] === '') $data['lema'] = $defaults['lema'];
        if ($data['panel_titulo'] === '') $data['panel_titulo'] = $defaults['panel_titulo'];
        if ($data['hero_titulo'] === '') $data['hero_titulo'] = $defaults['hero_titulo'];

        $data['logo'] = brandingSafeImagePath((string)$data['logo'], ['png','jpg','jpeg']);
        $data['fondo_login'] = brandingSafeImagePath((string)$data['fondo_login'], ['png','jpg','jpeg','webp']);

        $cache = $data;
        $loadedFromDb = true;
        return $cache;
    } catch (Throwable $ex) {
        error_log('MAD branding: '.$ex->getMessage());
        $cache = $defaults;
        $loadedFromDb = false;
        return $cache;
    }
}

function brandingSafeImagePath(string $path, array $extensions = ['png','jpg','jpeg','webp']): string {
    $path = str_replace('\\','/',$path);
    if ($path === '') return '';
    $allowed = implode('|', array_map(static fn($x)=>preg_quote($x,'#'), $extensions));
    if (!preg_match('#^uploads/branding/[A-Za-z0-9._-]+\.('.$allowed.')$#i', $path)) return '';
    $absolute = dirname(__DIR__).'/'.$path;
    return is_file($absolute) ? $path : '';
}

// Compatibilidad con la primera versión del editor.
function brandingSafeLogoPath(string $path): string { return brandingSafeImagePath($path); }

function brandingAssetUrl(string $path): string {
    $safe = brandingSafeImagePath($path);
    if ($safe === '') return '';
    $absolute = dirname(__DIR__).'/'.$safe;
    $version = is_file($absolute) ? (string)@filemtime($absolute) : '1';
    return url($safe).'?v='.rawurlencode($version);
}

/**
 * Logo predeterminado: la "casita" (icono `school`), dibujada en SVG con el color principal.
 * No es un archivo: así siempre aparece aunque el hosting no tenga la carpeta assets/img
 * y cambia de color automáticamente cuando Dirección cambia el color principal.
 */
const BRANDING_DEFAULT_LOGO_PATHS = '<path d="m3 9 9-6 9 6v11H3Z"/><path d="M9 20v-7h6v7M7 10h1m8 0h1"/>';

/** SVG completo de la casita con el color indicado (para favicon y vistas previas). */
function brandingDefaultLogoSvg(string $color): string {
    $color = brandingNormalizeColor($color, '#d00032');
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="'.$color.'" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">'.BRANDING_DEFAULT_LOGO_PATHS.'</svg>';
}

/** Favicon de la casita como data URI (permitido por la CSP: img-src data:). */
function brandingDefaultFaviconUrl(array $branding): string {
    return 'data:image/svg+xml,'.rawurlencode(brandingDefaultLogoSvg((string)($branding['color_principal'] ?? '#d00032')));
}

/** Logo subido por Dirección (vacío si no hay uno propio). */
function brandingCustomLogoUrl(array $branding): string { return brandingAssetUrl((string)($branding['logo'] ?? '')); }

/** Logo de imagen que se muestra. Vacío = se dibuja la casita predeterminada con el color principal. */
function brandingLogoUrl(array $branding): string { return brandingCustomLogoUrl($branding); }
function brandingLoginBackgroundUrl(array $branding): string { return brandingAssetUrl((string)($branding['fondo_login'] ?? '')); }

function brandingAssetAbsolute(string $path): string {
    $safe = brandingSafeImagePath($path);
    return $safe === '' ? '' : dirname(__DIR__).'/'.$safe;
}
function brandingLogoAbsolute(array $branding): string {
    $custom = brandingAssetAbsolute((string)($branding['logo'] ?? ''));
    return $custom !== '' ? $custom : brandingDefaultLogoPng((string)($branding['color_principal'] ?? '#d00032'));
}

/**
 * Versión PNG de la casita para los reportes PDF/Excel (FPDF y PhpSpreadsheet no leen SVG).
 * Se genera con GD una sola vez por color en storage/tmp. Si GD o la carpeta no están
 * disponibles, devuelve '' y el reporte simplemente sale sin logo (no falla).
 */
function brandingDefaultLogoPng(string $color): string {
    if (!function_exists('imagecreatetruecolor')) return '';
    $color = brandingNormalizeColor($color, '#d00032');
    $dir = dirname(__DIR__).'/storage/tmp';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    if (!is_dir($dir) || !is_writable($dir)) return '';
    $file = $dir.'/logo_casita_'.substr($color, 1).'.png';
    if (is_file($file) && filesize($file) > 0) return $file;

    $size = 256; $s = $size / 24; // misma geometría que el SVG (viewBox 24x24)
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false); imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 127));
    imagealphablending($img, true);
    [$r, $g, $b] = sscanf($color, '#%02x%02x%02x');
    $ink = imagecolorallocate($img, (int)$r, (int)$g, (int)$b);
    $stroke = (int)round(1.9 * $s);
    $line = static function (float $x1, float $y1, float $x2, float $y2) use ($img, $ink, $s, $stroke): void {
        // Trazo grueso con extremos redondeados (polígono + círculos en las puntas).
        $ax = $x1*$s; $ay = $y1*$s; $bx = $x2*$s; $by = $y2*$s;
        $len = hypot($bx-$ax, $by-$ay) ?: 1; $nx = -($by-$ay)/$len*$stroke/2; $ny = ($bx-$ax)/$len*$stroke/2;
        imagefilledpolygon($img, [(int)round($ax+$nx),(int)round($ay+$ny),(int)round($bx+$nx),(int)round($by+$ny),(int)round($bx-$nx),(int)round($by-$ny),(int)round($ax-$nx),(int)round($ay-$ny)], $ink);
        imagefilledellipse($img, (int)round($ax), (int)round($ay), $stroke, $stroke, $ink);
        imagefilledellipse($img, (int)round($bx), (int)round($by), $stroke, $stroke, $ink);
    };
    // Contorno de la casa: (3,9)->(12,3)->(21,9)->(21,20)->(3,20)->(3,9)
    foreach ([[3,9,12,3],[12,3,21,9],[21,9,21,20],[21,20,3,20],[3,20,3,9]] as [$a,$b2,$c,$d]) $line($a,$b2,$c,$d);
    // Puerta y ventanas
    foreach ([[9,20,9,13],[9,13,15,13],[15,13,15,20],[7,10,8,10],[16,10,17,10]] as [$a,$b2,$c,$d]) $line($a,$b2,$c,$d);
    $ok = @imagepng($img, $file);
    imagedestroy($img);
    return $ok ? $file : '';
}

/**
 * Calcula ancho/alto para que una imagen quepa en una caja sin deformarse.
 * Devuelve [ancho, alto, desplazamientoX, desplazamientoY] en las mismas unidades de la caja.
 */
function brandingFitImage(string $absolute, float $boxW, float $boxH): array {
    $size = @getimagesize($absolute);
    $w = (float)($size[0] ?? 0); $h = (float)($size[1] ?? 0);
    if ($w <= 0 || $h <= 0) return [$boxW, $boxH, 0.0, 0.0];
    $scale = min($boxW / $w, $boxH / $h);
    $fitW = $w * $scale; $fitH = $h * $scale;
    return [$fitW, $fitH, ($boxW - $fitW) / 2, ($boxH - $fitH) / 2];
}

function brandingDisplayName(array $branding): string {
    return trim((string)($branding['nombre_escuela'] ?? '')) !== ''
        ? (string)$branding['nombre_escuela']
        : (string)$branding['nombre_sistema'];
}

function brandingSlug(array $branding): string {
    $text = brandingDisplayName($branding);
    $ascii = iconv('UTF-8','ASCII//TRANSLIT',$text) ?: $text;
    $slug = strtolower((string)preg_replace('/[^A-Za-z0-9]+/','-',$ascii));
    $slug = trim($slug,'-');
    return $slug !== '' ? $slug : 'mi-aula-digital';
}

function brandingHexToRgb(string $hex): array {
    $hex = ltrim(brandingNormalizeColor($hex, '#000000'),'#');
    return [hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))];
}

function brandingHexToRgba(string $hex, float $alpha): string {
    [$r,$g,$b] = brandingHexToRgb($hex);
    $alpha = max(0,min(1,$alpha));
    return "rgba($r,$g,$b,$alpha)";
}

function brandingRelativeLuminance(string $hex): float {
    $channels = array_map(static function(int $v): float {
        $c = $v / 255;
        return $c <= .03928 ? $c / 12.92 : (($c + .055) / 1.055) ** 2.4;
    }, brandingHexToRgb($hex));
    return .2126*$channels[0] + .7152*$channels[1] + .0722*$channels[2];
}

function brandingContrast(string $a, string $b): float {
    $l1 = brandingRelativeLuminance($a); $l2 = brandingRelativeLuminance($b);
    $lighter = max($l1,$l2); $darker = min($l1,$l2);
    return ($lighter + .05) / ($darker + .05);
}

function brandingBestTextColor(string $background): string {
    return brandingContrast($background, '#ffffff') >= brandingContrast($background, '#111111') ? '#ffffff' : '#111111';
}

function brandingUpsert(array $values): void {
    brandingEnsureSchema();
    $allowed = brandingDefaults();
    foreach ($values as $key => $value) {
        if (!array_key_exists((string)$key, $allowed) || $key === 'version_tema') continue;
        query('INSERT INTO configuracion_escuela(clave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)',[(string)$key,(string)$value]);
    }
    $current = one("SELECT valor FROM configuracion_escuela WHERE clave='version_tema' LIMIT 1");
    $version = max(0, (int)($current['valor'] ?? 0)) + 1;
    query("INSERT INTO configuracion_escuela(clave,valor) VALUES ('version_tema',?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)",[(string)$version]);
}

function brandingDeleteStoredAsset(string $path): void {
    $safe = brandingSafeImagePath($path);
    if ($safe === '') return;
    $absolute = dirname(__DIR__).'/'.$safe;
    if (is_file($absolute)) @unlink($absolute);
}
function brandingDeleteStoredLogo(string $path): void { brandingDeleteStoredAsset($path); }

function brandingHandleImageUpload(string $field, string $prefix, int $maxBytes = 4194304, bool $allowWebp = true): ?string {
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) return null;
    $file = $_FILES[$field];
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) throw new DomainException('No se pudo cargar la imagen “'.$field.'”. Inténtalo de nuevo.');

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) throw new DomainException('La imagen debe pesar menos de '.number_format($maxBytes/1048576,0).' MB.');
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) throw new DomainException('La carga de la imagen no es válida.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $allowed = ['image/png'=>'png','image/jpeg'=>'jpg'];
    if ($allowWebp) $allowed['image/webp']='webp';
    if (!isset($allowed[$mime])) throw new DomainException($allowWebp ? 'La imagen debe ser PNG, JPG/JPEG o WEBP.' : 'El logo debe ser PNG o JPG/JPEG para conservar compatibilidad con los reportes PDF.');

    $image = @getimagesize($tmp);
    if (!$image || ($image[0] ?? 0) < 40 || ($image[1] ?? 0) < 40 || ($image[0] ?? 0) > 6000 || ($image[1] ?? 0) > 6000) {
        throw new DomainException('La imagen debe tener entre 40×40 y 6000×6000 píxeles.');
    }

    $directory = dirname(__DIR__).'/uploads/branding';
    if (!is_dir($directory) && !mkdir($directory,0755,true) && !is_dir($directory)) throw new RuntimeException('No se pudo crear la carpeta de personalización.');
    $safePrefix = preg_replace('/[^a-z0-9_-]/i','',$prefix) ?: 'imagen';
    $name = $safePrefix.'-'.bin2hex(random_bytes(12)).'.'.$allowed[$mime];
    $destination = $directory.'/'.$name;
    if (!move_uploaded_file($tmp,$destination)) throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
    return 'uploads/branding/'.$name;
}

function brandingHandleLogoUpload(string $currentLogo = ''): ?string {
    return brandingHandleImageUpload('logo','logo',2*1024*1024,false);
}

