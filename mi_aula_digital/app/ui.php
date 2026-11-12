<?php

function icon(string $name): string
{
    $paths = [
        'school'   => '<path d="m3 9 9-6 9 6v11H3Z"/><path d="M9 20v-7h6v7M7 10h1m8 0h1"/>',
        'home'     => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'users'    => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 5a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 5"/>',
        'book'     => '<path d="M12 5C8 2 4 3 2 4v15c3-2 7-1 10 1 3-2 7-3 10-1V4c-3-1-7-2-10 1Zm0 0v15"/>',
        'chart'    => '<path d="M4 3v18h17M8 16v-5m5 5V7m5 9V4"/>',
        'bell'     => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>',
        'shield'   => '<path d="m12 2 8 3v7c0 5-8 10-8 10S4 17 4 12V5Z"/><path d="m8 12 3 3 5-6"/>',
        'key'      => '<circle cx="8" cy="9" r="5"/><path d="m12 13 8 8 2-2-2-2 1-1-2-2-1 1"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 2v6m10-6v6M3 11h18M8 15h2m4 0h2"/>',
        'search'   => '<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
        'plus'     => '<path d="M12 4v16M4 12h16"/>',
        'arrow'    => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'back'     => '<path d="M20 12H4m6-6-6 6 6 6"/>',
        'logout'   => '<path d="M10 3H4v18h6m5-15 6 6-6 6M8 12h13"/>',
        'close'    => '<path d="m6 6 12 12M6 18 18 6"/>',
        'menu'     => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 17v4h16v-4"/>',
        'check'    => '<path d="m5 12 4 4L19 6"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'lock'     => '<rect x="5" y="11" width="14" height="10" rx="2.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/>',
        'edit'     => '<path d="m15 4 5 5-11 11-6 1 1-6Zm-2 2 5 5"/>',
        'palette'  => '<path d="M12 3a9 9 0 1 0 0 18h1.2a2.3 2.3 0 0 0 1.8-3.7 1.7 1.7 0 0 1 1.3-2.8H18a3 3 0 0 0 3-3A8.5 8.5 0 0 0 12 3Z"/><circle cx="7.5" cy="10" r=".8"/><circle cx="10" cy="6.8" r=".8"/><circle cx="14" cy="6.5" r=".8"/><circle cx="17" cy="9.5" r=".8"/>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true">'
        . ($paths[$name] ?? $paths['home'])
        . '</svg>';
}


/*
|--------------------------------------------------------------------------
| Logo / enlace principal
|--------------------------------------------------------------------------
| Si existe una sesión:
| - Dirección -> panel de Dirección
| - Docente   -> panel de Docente
| - Tutor     -> panel de Tutor
|
| Si no existe sesión:
| - manda al inicio/login.
|--------------------------------------------------------------------------
*/
function brand(?string $href = null): void
{
    $branding = schoolBranding();
    $logo = brandingLogoUrl($branding);
    $mark = $logo !== ''
        ? '<img src="'.e($logo).'" alt="" width="44" height="44" decoding="async" data-logo-fallback>'
        : icon('school');
    $markClass = $logo !== '' ? 'brand-mark has-logo' : 'brand-mark';
    $subtitle = $branding['nombre_escuela'] !== '' ? $branding['nombre_escuela'] : $branding['lema'];
    // La marca es identidad visual, no un botón. El regreso a Inicio se hace desde la navegación.
    echo '<div class="brand" aria-label="'.e($branding['nombre_sistema']).'">
        <span class="'.$markClass.'">'.$mark.'</span>
        <span>
            <strong>'.e($branding['nombre_sistema']).'</strong>
            <small>'.e($subtitle).'</small>
        </span>
    </div>';
}



/**
 * URL de un archivo estático con su fecha de modificación (?v=...).
 * Así, al subir una versión nueva al hosting, el navegador la descarga
 * en lugar de seguir usando la copia vieja guardada en caché.
 */
function assetUrl(string $path): string
{
    $absolute = dirname(__DIR__).'/'.ltrim($path, '/');
    $version = is_file($absolute) ? (string)@filemtime($absolute) : '1';
    return url($path).'?v='.rawurlencode($version);
}

function head(string $title): void
{
    $branding = schoolBranding();
    $logo = brandingLogoUrl($branding);
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="theme-color" content="<?= e($branding['color_principal']) ?>">

        <title><?= e($title) ?> · <?= e($branding['nombre_sistema']) ?></title>

        <?php if ($logo !== ''): ?>
        <link rel="icon" href="<?= e($logo) ?>">
        <link rel="apple-touch-icon" href="<?= e($logo) ?>">
        <?php else: ?>
        <link rel="icon" type="image/svg+xml" href="<?= e(brandingDefaultFaviconUrl($branding)) ?>">
        <?php endif; ?>

        <link rel="stylesheet" href="<?= e(assetUrl('assets/design.css')) ?>">
        <?php $themeVersion=substr(hash('sha256',json_encode($branding,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?:''),0,12); ?>
        <link rel="stylesheet" href="<?= e(url('assets/theme.php?v='.$themeVersion.'-'.(string)@filemtime(dirname(__DIR__).'/assets/theme.php'))) ?>">

        <script src="<?= e(assetUrl('assets/app.js')) ?>" defer></script>
        <script src="<?= e(assetUrl('assets/grades.js')) ?>" defer></script>
        <script src="<?= e(assetUrl('assets/password-strength.js')) ?>" defer></script>
        <script src="<?= e(assetUrl('assets/listas.js')) ?>" defer></script>
    </head>
    <body>
    <?php
}


function foot(): void
{
    echo '
        <div
            id="toast"
            class="toast"
            role="status"
            aria-live="polite"
            hidden
        ></div>
    </body>
    </html>';
}


function formStart(string $action, array $attrs = []): void
{
    echo '<form
        action="' . e(url('process/action.php')) . '"
        method="post"
        data-api';

    foreach ($attrs as $key => $value) {
        echo ' ' . e($key) . '="' . e($value) . '"';
    }

    echo '>';

    echo csrf();

    echo '<input
        type="hidden"
        name="action"
        value="' . e($action) . '"
    >';
}


function feedback(): void
{
    echo '<div
        class="feedback"
        data-feedback
        role="status"
        aria-live="polite"
        hidden
    ></div>';
}


function field(
    string $name,
    string $label,
    string $type = 'text',
    string $value = '',
    bool $required = true,
    int $max = 0,
    string $help = '',
    array $attrs = []
): void {
    echo '<label class="field">';

    echo '<span>' . e($label) . '</span>';

    echo '<input
        name="' . e($name) . '"
        type="' . e($type) . '"
        value="' . e($value) . '"';

    if ($required) {
        echo ' required';
    }

    if ($max > 0) {
        echo ' maxlength="' . $max . '"';
    }

    if ($type === 'password') {
        echo ' autocomplete="' . ($name === 'actual' ? 'current-password' : 'new-password') . '"';
    }

    foreach ($attrs as $attr => $attrValue) {
        echo ' ' . e($attr) . '="' . e($attrValue) . '"';
    }

    echo '>';

    if ($help !== '') {
        echo '<small>' . e($help) . '</small>';
    }

    echo '</label>';
}


function selectField(
    string $name,
    string $label,
    array $options,
    bool $required = true
): void {
    echo '<label class="field">';

    echo '<span>' . e($label) . '</span>';

    echo '<select name="' . e($name) . '"';

    if ($required) {
        echo ' required';
    }

    echo '>';

    foreach ($options as $value => $title) {
        echo '<option value="' . e($value) . '">'
            . e($title)
            . '</option>';
    }

    echo '</select>';

    echo '</label>';
}


function textareaField(
    string $name,
    string $label,
    int $max = 10000
): void {
    echo '<label class="field">';

    echo '<span>' . e($label) . '</span>';

    echo '<textarea
        name="' . e($name) . '"
        maxlength="' . $max . '"
        required
        rows="4"
    ></textarea>';

    echo '</label>';
}


function modalStart(
    string $id,
    string $title,
    string $action,
    array $attrs = []
): void {
    echo '<dialog
        class="dialog"
        id="' . e($id) . '"
        data-title="' . e($title) . '"
    >';

    echo '<div class="dialog-head">';

    echo '<h2 data-dialog-title>'
        . e($title)
        . '</h2>';

    echo '<button
        type="button"
        class="icon-button"
        data-close
        aria-label="Cerrar"
    >'
        . icon('close')
        . '</button>';

    echo '</div>';

    formStart($action, $attrs);
}


function modalEnd(string $label = 'Guardar cambios'): void
{
    feedback();

    echo '<div class="form-footer">';

    echo '<button
        type="button"
        class="button"
        data-close
    >
        Cancelar
    </button>';

    echo '<button
        type="submit"
        class="button primary"
    >'
        . e($label)
        . '</button>';

    echo '</div>';

    echo '</form>';

    echo '</dialog>';
}


function openButton(
    string $modal,
    string $label,
    array $data = [],
    string $style = 'small',
    string $title = ''
): string {
    return '<button
        type="button"
        class="button ' . e($style) . '"
        data-open="' . e($modal) . '"
        data-values="' . e(json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
        )) . '"'
        . (
            $title !== ''
                ? ' data-title="' . e($title) . '"'
                : ''
        )
        . '>'
        . e($label)
        . '</button>';
}


function actionButton(string $action,string $label,array $data,string $confirm=''): void {
    $dangerWords=['delete','archive','remove','reject','cancel','revocar','baja','eliminar','cerrar','rechazar','desactivar'];
    $successWords=['confirm','approve','aprobar','activar','reactivar','confirmar'];
    $warningWords=['reset','restaurar','renovar'];
    $needle=mb_strtolower($action.' '.$label);
    $style='small';
    foreach($dangerWords as $word)if(str_contains($needle,$word)){$style.=' danger';break;}
    if(!str_contains($style,'danger'))foreach($successWords as $word)if(str_contains($needle,$word)){$style.=' success';break;}
    if($style==='small')foreach($warningWords as $word)if(str_contains($needle,$word)){$style.=' warning';break;}
    // Los botones de acción en tablas y tarjetas avisan con una notificación flotante,
    // en lugar de meter el mensaje dentro de la celda.
    formStart($action,['data-confirm'=>$confirm,'data-toast'=>'true','class'=>'inline-action']);
    foreach($data as $k=>$v)echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">';
    echo '<button type="submit" class="button '.e($style).'">'.e($label).'</button></form>';
}

function emptyState(
    string $title,
    string $body = ''
): void {
    echo '<div class="empty">';

    echo '<strong>'
        . e($title)
        . '</strong>';

    if ($body !== '') {
        echo e($body);
    }

    echo '</div>';
}


function badge(
    string $text,
    string $color = ''
): string {
    return '<span class="badge '
        . e($color)
        . '">'
        . e($text)
        . '</span>';
}


function groupOptions(
    array $u,
    bool $all = true
): array {
    $out = $all
        ? ['' => 'Sin grupo']
        : [];

    foreach (
        rows(
            'SELECT *
             FROM grupo
             ORDER BY Grado, Grupo'
        ) as $group
    ) {
        $out[$group['ID_Grupo']]
            = $group['Grado']
            . '° '
            . $group['Grupo'];
    }

    return $out;
}


function studentOptions(array $list): array
{
    $out = [
        '' => 'Selecciona un alumno'
    ];

    foreach ($list as $student) {
        $out[$student['ID_Alumno']]
            = $student['nombre_completo']
            . ' · '
            . ($student['grupo'] ?? 'Sin grupo');
    }

    return $out;
}


function person(array $a, string $link = ''): string
{
    $name = $a['nombre_completo']
        ?? $a['Nombre'];
    $detail = $a['CURP']
        ?? $a['Correo']
        ?? '';

    // El enlace (por ejemplo "Ver expediente") va dentro del bloque de texto,
    // alineado con el nombre y no debajo del avatar.
    return '<span class="person">'
        . '<span class="avatar" aria-hidden="true">' . e(mb_substr($name, 0, 1)) . '</span>'
        . '<span class="person-text">'
        . '<strong>' . e($name) . '</strong>'
        . ($detail !== '' ? '<small>' . e($detail) . '</small>' : '')
        . ($link !== '' ? '<a class="person-link" href="' . e($link) . '">Ver expediente' . icon('arrow') . '</a>' : '')
        . '</span>'
        . '</span>';
}


function filterBar(
    string $view,
    ?array $cycle = null,
    bool $term = false
): void {
    echo '<form class="filters" method="get">';
    echo '<input type="hidden" name="vista" value="' . e($view) . '">';

    if ($cycle) {
        echo '<label>Ciclo escolar<select name="ciclo" data-filter>';
        foreach (cycles() as $c) {
            echo '<option value="' . e($c['id_ciclo']) . '"'
                . ((int)$c['id_ciclo'] === (int)$cycle['id_ciclo'] ? ' selected' : '')
                . '>' . e($c['nombre']) . '</option>';
        }
        echo '</select></label>';
    }

    if ($term) {
        $maxPeriods = max(1, (int)($cycle['periodos'] ?? 5));
        $periodName = (string)($cycle['tipo_periodo'] ?? 'Bimestre');
        $selected = max(1, min($maxPeriods, (int)($_GET['bimestre'] ?? 1)));
        echo '<label>' . e($periodName) . '<select name="bimestre" data-filter>';
        for ($i = 1; $i <= $maxPeriods; $i++) {
            echo '<option value="' . $i . '"' . ($i === $selected ? ' selected' : '') . '>'
                . e($periodName . ' ' . $i) . '</option>';
        }
        echo '</select></label>';
    }

    echo '</form>';
}

function searchInput(string $table): void
{
    echo '<label class="search">'
        . icon('search')
        . '<input
            type="search"
            placeholder="Buscar en esta lista…"
            aria-label="Buscar en esta lista"
            data-search="' . e($table) . '"
        >
    </label>';
}


function pager(
    string $view,
    int $page,
    int $n,
    int $size = 50
): void {
    $base = viewUrl($view);

    foreach (
        [
            'ciclo',
            'buscar'
        ] as $key
    ) {
        if (
            isset($_GET[$key])
            &&
            is_scalar($_GET[$key])
        ) {
            $base .= '&'
                . $key
                . '='
                . rawurlencode(
                    (string)$_GET[$key]
                );
        }
    }

    echo '<div class="buttons">';

    if ($page > 1) {
        echo '<a
            class="button"
            href="'
            . e(
                $base
                . '&pagina='
                . ($page - 1)
            )
            . '"
        >
            Anterior
        </a>';
    }

    echo '<span class="help">
        Página ' . $page . '
    </span>';

    if ($n === $size) {
        echo '<a
            class="button"
            href="'
            . e(
                $base
                . '&pagina='
                . ($page + 1)
            )
            . '"
        >
            Siguiente
        </a>';
    }

    echo '</div>';
}

/*
|--------------------------------------------------------------------------
| Filtros automáticos de listas
|--------------------------------------------------------------------------
| listFilter('tabla-id', ['grupo'=>['Grupo', [valor=>texto]]]) dibuja un buscador
| que filtra mientras se escribe. Cada fila lleva sus datos con filterAttrs().
*/
function listFilter(string $target, array $selects = [], string $placeholder = 'Buscar por nombre…'): void
{
    echo '<div class="list-filter" data-list-filter="'.e($target).'">';
    echo '<label class="search">'.icon('search').'<input type="search" data-q placeholder="'.e($placeholder).'" aria-label="'.e($placeholder).'" autocomplete="off"></label>';
    foreach ($selects as $key => [$label, $options]) {
        echo '<label class="lf-select"><span>'.e($label).'</span><select data-key="'.e($key).'"><option value="">Todos</option>';
        foreach ($options as $value => $text) echo '<option value="'.e($value).'">'.e($text).'</option>';
        echo '</select></label>';
    }
    echo '<button type="button" class="button small lf-clear" data-clear>Limpiar</button>';
    echo '<span class="lf-count help" data-count role="status" aria-live="polite"></span>';
    echo '</div>';
}

function filterAttrs(array $values): string
{
    $out = '';
    foreach ($values as $key => $value) $out .= ' data-f-'.e($key).'="'.e((string)$value).'"';
    return $out;
}

/** Grupos para los filtros: [ID_Grupo => "1° A"]. */
function groupFilterOptions(): array
{
    $out = [];
    foreach (rows('SELECT ID_Grupo,Grado,Grupo FROM grupo ORDER BY Grado,Grupo') as $g) $out[(string)$g['ID_Grupo']] = $g['Grado'].'° '.$g['Grupo'];
    return $out;
}

/** Alumnos activos con su grupo actual, para listas con buscador. */
function studentsWithGroup(): array
{
    return rows("SELECT a.ID_Alumno,CONCAT_WS(' ',a.Nombre,a.Primer_Apellido,a.Segundo_Apellido) nombre,COALESCE(i.ID_Grupo,a.ID_Grupo) grupo_id,COALESCE(CONCAT(gi.Grado,'° ',gi.Grupo),i.grupo_nombre,CONCAT(g.Grado,'° ',g.Grupo),'Sin grupo') grupo
        FROM alumno a LEFT JOIN inscripcion i ON i.ID_Alumno=a.ID_Alumno AND i.id_ciclo=? AND i.activo=1 LEFT JOIN grupo gi ON gi.ID_Grupo=i.ID_Grupo LEFT JOIN grupo g ON g.ID_Grupo=a.ID_Grupo
        WHERE a.activo=1 GROUP BY a.ID_Alumno,a.Nombre,a.Primer_Apellido,a.Segundo_Apellido,i.ID_Grupo,a.ID_Grupo,i.grupo_nombre,gi.Grado,gi.Grupo,g.Grado,g.Grupo ORDER BY a.Primer_Apellido,a.Segundo_Apellido,a.Nombre", [activeCycleId()]);
}

/**
 * Selector de alumno con buscador: primero el grupo y después el nombre
 * (sugerencia de la revisión). Funciona igual en computadora y celular.
 */
function studentPicker(string $id, string $name = 'alumno', string $label = 'Alumno', bool $required = true, ?array $students = null): void
{
    $students ??= studentsWithGroup();
    echo '<div class="student-picker">';
    echo '<label class="field"><span>1. Grupo</span><select data-options-group="'.e($id).'"><option value="">Todos los grupos</option>';
    foreach (groupFilterOptions() as $gid => $text) echo '<option value="'.e($gid).'">'.e($text).'</option>';
    echo '</select></label>';
    echo '<label class="field"><span>2. Buscar por nombre</span><input type="search" data-options-text="'.e($id).'" placeholder="Escribe el nombre o apellido" autocomplete="off"></label>';
    echo '<label class="field picker-select"><span>'.e($label).' <small class="help" data-options-count="'.e($id).'"></small></span><select id="'.e($id).'" name="'.e($name).'" data-options-filterable'.($required ? ' required' : '').'>';
    echo '<option value="">'.($required ? 'Selecciona un alumno' : 'Ninguno por ahora').'</option>';
    foreach ($students as $s) echo '<option value="'.e($s['ID_Alumno']).'" data-grupo="'.e((string)($s['grupo_id'] ?? '')).'">'.e($s['nombre'].' · '.$s['grupo']).'</option>';
    echo '</select></label></div>';
}
