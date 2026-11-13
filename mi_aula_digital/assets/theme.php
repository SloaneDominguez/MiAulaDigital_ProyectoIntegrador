<?php
require __DIR__.'/../app/bootstrap.php';
$b = array_replace(brandingDefaults(), schoolBranding());
header('Content-Type: text/css; charset=UTF-8');
header('Cache-Control: private, max-age=0, must-revalidate');

$accent = $b['color_principal'];
$secondary = $b['color_secundario'];
$background = $b['color_fondo'];
$surface = $b['color_superficie'];
$text = $b['color_texto'];
$textMode = 'manual';
$globalTextMode = false;
$sidebar = $b['color_sidebar'];
$sidebarText = $b['color_sidebar_texto'];
$hero = $b['color_hero'];
$heroText = $b['color_hero_texto'];
$notice = $b['color_anuncio'];
$noticeText = $b['color_anuncio_texto'];
$success = $b['color_exito'];
$warning = $b['color_advertencia'];
$danger = $b['color_peligro'];
$info = $b['color_info'];
// El editor ya no sustituye colores de texto por blanco/negro automáticamente.
// El color elegido por Dirección es el que se usa. El contraste solo se ADVIERTE en el editor.
// Texto sobre botones y elementos de color: blanco siempre que se lea bien (rojo, verde, azul, morado, negro);
// solo en colores muy claros (amarillo, pastel) se usa texto oscuro. Así nunca queda "rojo con letras negras".
$onColor = static function(string $bg): string { return brandingContrast($bg,'#ffffff') >= 3.0 ? '#ffffff' : '#111111'; };
$onAccent = $onColor($accent);
$onDanger = $onColor($danger);
$onSuccess = $onColor($success);
$onWarning = $onColor($warning);
$onInfo = $onColor($info);
$accentText = $onAccent;
// Mezcla dos colores (t=0 → $a, t=1 → $b).
$mix = static function(string $a, string $b, float $t): string {
    [$r1,$g1,$b1] = brandingHexToRgb($a); [$r2,$g2,$b2] = brandingHexToRgb($b);
    return sprintf('#%02x%02x%02x', (int)round($r1+($r2-$r1)*$t), (int)round($g1+($g2-$g1)*$t), (int)round($b1+($b2-$b1)*$t));
};
// Ajusta un color (oscureciéndolo o aclarándolo) hasta que se lea sobre el fondo indicado.
$ensure = static function(string $fg, string $bg, float $min = 4.5) use ($mix): string {
    if (brandingContrast($fg, $bg) >= $min) return $fg;
    $toward = brandingContrast($bg,'#ffffff') >= brandingContrast($bg,'#000000') ? '#ffffff' : '#000000';
    for ($t = .1; $t <= 1.0001; $t += .1) { $c = $mix($fg, $toward, $t); if (brandingContrast($c, $bg) >= $min) return $c; }
    return $toward;
};
// Letra legible automática: se usa el color elegido si se lee bien sobre su fondo;
// si no (por ejemplo, letra negra sobre tarjetas negras), se cambia sola a blanco o negro.
$readable = static function(string $preferred, string $bg): string {
    return brandingContrast($preferred, $bg) >= 4.5 ? $preferred : (brandingContrast($bg,'#ffffff') >= brandingContrast($bg,'#111111') ? '#ffffff' : '#111111');
};
// Las tarjetas son "cristal": su color real es la mezcla de la tarjeta con el fondo según la opacidad.
$effSurface = $mix($background, $surface, max(.38, min(1, (int)$b['opacidad_cristal']/100)));
$surfaceText = $readable($readable($text, $surface), $effSurface);
$backgroundText = $readable($text, $background);
$sidebarTextEffective = $readable($sidebarText, $sidebar);
$heroTextEffective = $readable($heroText, $hero);
$noticeTextEffective = $readable($noticeText, $notice);
$onBackgroundAccent = $ensure($accent, $background);
$onSurfaceAccent = $ensure($ensure($accent, $surface), $effSurface);
// Etiquetas de estado (verde, naranja, rojo) y avatares: letra ajustada a su fondo teñido.
$badgeFg = static function(string $color) use ($mix, $ensure, $effSurface): array { $bg = $mix($effSurface, $color, .14); return [$bg, $ensure($color, $bg)]; };
[$badgeGreenBg, $badgeGreenFg] = $badgeFg($success);
[$badgeOrangeBg, $badgeOrangeFg] = $badgeFg($warning);
[$badgeRedBg, $badgeRedFg] = $badgeFg($danger);
[$badgeBg, $badgeFgc] = $badgeFg($accent);
$pillBg = $mix($background, $accent, .14); $pillFg = $ensure($secondary, $pillBg);
$sidebarAvatarBg = $mix($sidebar, $sidebarTextEffective, .12);
// Fondo de cajas internas (tablas, campos, recuadros): se deriva de la tarjeta para que la letra siempre se lea.
$softBg = $mix($effSurface, $surfaceText, .06);
$softLine = brandingHexToRgba($surfaceText, .16);
$surfaceMuted = brandingHexToRgba($surfaceText, brandingContrast($surfaceText,$effSurface) >= 7 ? .72 : .86);
$sidebarTextSecondary = brandingHexToRgba($sidebarTextEffective,.82);
$heroTextSecondary = brandingHexToRgba($heroTextEffective,.82);
$noticeTextSecondary = brandingHexToRgba($noticeTextEffective,.82);
$surfaceMuted = brandingHexToRgba($surfaceText,.68);

$radius=(int)$b['radio_tarjetas'];
$buttonRadius=(int)$b['radio_botones'];
$blur=(int)$b['desenfoque'];
$opacity=(int)$b['opacidad_cristal']/100;
$shadow=(int)$b['intensidad_sombra'];
$fontScale=(int)$b['escala_fuente']/100;
$sidebarWidth=(int)$b['ancho_sidebar'];

$fontMap=[
 'sistema'=>"system-ui,-apple-system,'Segoe UI',sans-serif",
 'moderna'=>"Verdana,Geneva,sans-serif",
 'redondeada'=>"'Trebuchet MS','Segoe UI',sans-serif",
 'serif'=>"Georgia,'Times New Roman',serif",
];
$font=$fontMap[$b['tipografia']]??$fontMap['sistema'];
$density=$b['densidad'];
$cardPadding=$density==='compacta'?18:($density==='amplia'?30:24);
$tablePadding=$density==='compacta'?11:($density==='amplia'?19:16);
$sectionGap=$density==='compacta'?12:($density==='amplia'?24:18);
$announcementPadding=$density==='compacta'?'13px 15px':($density==='amplia'?'20px 20px':'16px 18px');

$accent08 = brandingHexToRgba($accent,.08);
$accent12 = brandingHexToRgba($accent,.12);
$accent18 = brandingHexToRgba($accent,.18);
$accent24 = brandingHexToRgba($accent,.24);
$accent55 = brandingHexToRgba($accent,.55);
$secondary18 = brandingHexToRgba($secondary,.18);
$surfaceAlpha = brandingHexToRgba($surface,$opacity);
$surface94 = brandingHexToRgba($surface,.94);
$sidebar94 = brandingHexToRgba($sidebar,.94);
$sidebar98 = brandingHexToRgba($sidebar,.98);
$hero98 = brandingHexToRgba($hero,.98);
$loginBg = brandingLoginBackgroundUrl($b);
$loginBgCss = $loginBg !== '' ? json_encode($loginBg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) : 'none';
$loginPosition = $b['fondo_login_posicion'];
?>
:root{
 --ink:<?=$backgroundText?>;
 --background-text:<?=$backgroundText?>;
 --theme-text-choice:<?=$text?>;
 --surface-text:<?=$surfaceText?>;
 --surface-muted:<?=$surfaceMuted?>;
 --muted:<?=$surfaceMuted?>;
 --line:<?=brandingHexToRgba($surfaceText,.14)?>;
 --accent:<?=$accent?>;
 --theme-secondary:<?=$secondary?>;
 --theme-bg:<?=$background?>;
 --theme-surface:<?=$surface?>;
 --theme-sidebar:<?=$sidebar?>;
 --theme-sidebar-text:<?=$sidebarTextEffective?>;
 --theme-hero:<?=$hero?>;
 --theme-hero-text:<?=$heroTextEffective?>;
 --theme-announcement:<?=$notice?>;
 --theme-announcement-text:<?=$noticeTextEffective?>;
 --theme-success:<?=$success?>;
 --theme-warning:<?=$warning?>;
 --theme-danger:<?=$danger?>;
 --theme-info:<?=$info?>;
 --theme-font:<?=$font?>;
 --glass:<?=$surfaceAlpha?>;
 --text-mode:<?=$textMode?>;
 --radius:<?=$radius?>px;
 --button-radius:<?=$buttonRadius?>px;
 --glass-blur:<?=$blur?>px;
 --sidebar-width:<?=$sidebarWidth?>px;
 --shadow:0 <?=max(6,(int)round($shadow*.9))?>px <?=max(18,$shadow*4)?>px <?=brandingHexToRgba($text,min(.22,$shadow/100))?>;
 font-family:<?=$font?>;
}
html{font-size:<?=round($fontScale*100,2)?>%;}
body{font-family:<?=$font?>;font-size:<?=round(14*$fontScale,2)?>px;color:<?=$backgroundText?>;background:<?=$background?>;background-color:<?=$background?>;min-height:100dvh;}
a{color:<?=$accent?>}.app{grid-template-columns:var(--sidebar-width) minmax(0,1fr)}
.glass{background:<?=$surfaceAlpha?>;border-color:<?=brandingHexToRgba($surface,.72)?>;box-shadow:var(--shadow),inset 0 1px 1px <?=brandingHexToRgba($surface,.96)?>;backdrop-filter:blur(var(--glass-blur)) saturate(165%);-webkit-backdrop-filter:blur(var(--glass-blur)) saturate(165%)}
.card{padding:<?=$cardPadding?>px}.card,.hero,.stat,.branding-preview,.auth-card,.dialog{border-radius:var(--radius)}.dialog{color:<?=$surfaceText?>;background:<?=$surfaceAlpha?>;border-color:<?=brandingHexToRgba($surfaceText,.14)?>;backdrop-filter:blur(var(--glass-blur));-webkit-backdrop-filter:blur(var(--glass-blur))}.icon-button{background:<?=brandingHexToRgba($surfaceText,.08)?>;color:<?=brandingHexToRgba($surfaceText,.68)?>}
td{padding:<?=$tablePadding?>px 14px}.button{border-radius:var(--button-radius)}
.button:not(.primary):not(.danger):not(.success):not(.warning):not(.info){background:<?=brandingHexToRgba($surface,.92)?>;color:<?=$surfaceText?>;border-color:<?=brandingHexToRgba($surfaceText,.14)?>}.button:not(.primary):not(.danger):not(.success):not(.warning):not(.info):hover{background:color-mix(in srgb, <?=$surface?> 93%, <?=$accent?> 7%);border-color:color-mix(in srgb, <?=$surfaceText?> 22%, <?=$accent?> 20%)}.button.primary{color:<?=$onAccent?>;background:<?=$accent?>;border-color:<?=brandingHexToRgba($surface,.35)?>;box-shadow:0 7px 22px <?=$accent24?>,inset 0 1px 1px <?=brandingHexToRgba($surface,.40)?>}.button.primary:hover{background:color-mix(in srgb, <?=$accent?> 88%, white 12%);filter:none}.button.primary:active{background:color-mix(in srgb, <?=$accent?> 88%, black 12%)}.button.success{color:<?=$onSuccess?>;background:<?=$success?>;border-color:<?=brandingHexToRgba($surface,.28)?>}.button.success:hover{background:color-mix(in srgb, <?=$success?> 88%, white 12%)}.button.success:active{background:color-mix(in srgb, <?=$success?> 88%, black 12%)}.button.warning{color:<?=$onWarning?>;background:<?=$warning?>;border-color:<?=brandingHexToRgba($surface,.28)?>}.button.warning:hover{background:color-mix(in srgb, <?=$warning?> 88%, white 12%)}.button.warning:active{background:color-mix(in srgb, <?=$warning?> 88%, black 12%)}.button.info{color:<?=$onInfo?>;background:<?=$info?>;border-color:<?=brandingHexToRgba($surface,.28)?>}.button.info:hover{background:color-mix(in srgb, <?=$info?> 88%, white 12%)}.button.info:active{background:color-mix(in srgb, <?=$info?> 88%, black 12%)}.button.danger{color:<?=$onDanger?>;background:<?=$danger?>;border-color:<?=brandingHexToRgba($surface,.30)?>}.button.danger:hover{background:color-mix(in srgb, <?=$danger?> 88%, white 12%)}.button.danger:active{background:color-mix(in srgb, <?=$danger?> 88%, black 12%)}
.brand-mark{color:<?=$accent?>;background:<?=$surface94?>}.brand-mark img{width:100%;height:100%;object-fit:contain;border-radius:calc(var(--button-radius) - 2px);padding:4px}
.sidebar{background:linear-gradient(160deg,<?=$sidebar98?>,<?=brandingHexToRgba($secondary,.18)?>),<?=$sidebar?>;color:<?=$sidebarTextEffective?>;border-right-color:<?=brandingHexToRgba($sidebarText,.14)?>;backdrop-filter:blur(var(--glass-blur));-webkit-backdrop-filter:blur(var(--glass-blur))}
.sidebar .brand,.sidebar .nav a:hover{color:<?=$sidebarTextEffective?>}.sidebar .logout:hover{color:<?=$sidebarTextEffective?>}.sidebar .nav a.active{color:<?=$accentText?>}.sidebar .brand small,.sidebar .overline,.sidebar .nav a,.sidebar .user-row small{color:<?=$sidebarTextSecondary?>}.sidebar .logout{color:<?=$sidebarTextEffective?>;background:linear-gradient(145deg,<?=brandingHexToRgba($danger,.92)?>,<?=brandingHexToRgba($danger,.72)?>);border:1px solid <?=brandingHexToRgba($sidebarText,.24)?>;box-shadow:0 7px 20px <?=brandingHexToRgba($danger,.24)?>,inset 0 1px 1px <?=brandingHexToRgba($sidebarText,.28)?>}.sidebar .nav a:hover{background:<?=brandingHexToRgba($sidebarText,.08)?>}.sidebar .nav a.active{background:linear-gradient(135deg,<?=brandingHexToRgba($accent,.96)?>,<?=brandingHexToRgba($secondary,.82)?>);border-color:<?=brandingHexToRgba($sidebarText,.24)?>;box-shadow:0 7px 22px <?=brandingHexToRgba($sidebar,.32)?>,inset 0 1px 1px <?=brandingHexToRgba($sidebarText,.24)?>}.sidebar .brand-mark{background:linear-gradient(145deg,<?=$accent?>,<?=$secondary?>);color:<?=$sidebarTextEffective?>;border-color:<?=brandingHexToRgba($sidebarText,.28)?>}
.sidebar .avatar{background:<?=$sidebarAvatarBg?>;color:<?=$sidebarTextEffective?>;border-color:<?=brandingHexToRgba($sidebarTextEffective,.16)?>}.sidebar a:focus-visible,.sidebar button:focus-visible{outline-color:<?=$sidebarTextEffective?>}
.hero{background:radial-gradient(ellipse at 100% 0%,<?=$accent55?>,transparent 62%),linear-gradient(125deg,<?=brandingHexToRgba($hero,min(.92,max(.64,$opacity*.92)))?>,<?=brandingHexToRgba($secondary,min(.42,max(.16,$opacity*.30)))?>),<?=$hero?>;backdrop-filter:blur(var(--glass-blur)) saturate(165%);-webkit-backdrop-filter:blur(var(--glass-blur)) saturate(165%);color:<?=$heroTextEffective?>;border-color:<?=brandingHexToRgba($heroTextEffective,.22)?>}.hero h2{color:<?=$heroTextEffective?>}.hero p{color:<?=$heroTextSecondary?>}.hero .eyebrow{color:<?=$heroTextSecondary?>}.hero-icon{color:<?=$heroTextEffective?>;background:linear-gradient(145deg,<?=brandingHexToRgba($heroTextEffective,.20)?>,<?=brandingHexToRgba($heroTextEffective,.05)?>);border-color:<?=brandingHexToRgba($heroTextEffective,.30)?>}
.auth-story h1 span,.auth-icon,.shortcut>svg:first-child,.stat-top svg,.quick-action svg{color:<?=$accent?>}.auth-icon{background:linear-gradient(140deg,<?=brandingHexToRgba($surface,.78)?>,<?=$accent12?>);border-color:<?=brandingHexToRgba($surface,.90)?>}
.avatar,.pill,.badge{background:<?=$accent12?>;color:<?=$secondary?>}.badge.green{background:<?=brandingHexToRgba($success,.12)?>;color:<?=$success?>}.badge.orange{background:<?=brandingHexToRgba($warning,.14)?>;color:<?=$warning?>}.badge.red{background:<?=brandingHexToRgba($danger,.12)?>;color:<?=$danger?>}.quick-action{background:<?=$accent08?>;border-color:<?=$accent12?>;color:<?=$surfaceText?>}.stat::after{background:linear-gradient(90deg,<?=$accent?>,<?=brandingHexToRgba($accent,.18)?>)}
.grid,.stats,.shortcuts{gap:<?=$sectionGap?>px}.page-heading{margin-bottom:<?=$sectionGap?>px}.announcement{background:<?=brandingHexToRgba($notice,.84)?>;color:<?=$noticeTextEffective?>;border:1px solid <?=brandingHexToRgba($accent,.11)?>;border-left:3px solid <?=$accent?>;border-radius:calc(var(--radius) * .62);padding:<?=$announcementPadding?>;margin-bottom:<?=$sectionGap?>px}.announcement:last-child{border:1px solid <?=brandingHexToRgba($accent,.11)?>;border-left:3px solid <?=$accent?>;padding-bottom:15px;margin-bottom:0}.announcement h3{color:<?=$noticeTextEffective?>;margin-top:8px}.announcement p,.announcement .meta{color:<?=$noticeTextSecondary?>}
.field input,.field select,.field textarea,select,.grade-cell{color:<?=$surfaceText?>;background:<?=brandingHexToRgba($surface,.92)?>;border-color:<?=brandingHexToRgba($surfaceText,.16)?>}th{color:<?=brandingHexToRgba($surfaceText,.72)?>;background:<?=brandingHexToRgba($surface,.92)?>}.empty{background:<?=brandingHexToRgba($surface,.66)?>}.empty strong,.breadcrumb strong,.shortcut,.button:not(.primary):not(.danger){color:<?=$surfaceText?>}
button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline-color:<?=$accent?>}
.bottom-nav{background:linear-gradient(145deg,<?=$sidebar94?>,<?=brandingHexToRgba($secondary,.14)?>);border-color:<?=brandingHexToRgba($sidebarText,.24)?>}.bottom-nav a{color:<?=brandingHexToRgba($sidebarText,.76)?>}.bottom-nav a.active{color:<?=$sidebarTextEffective?>;background:linear-gradient(140deg,<?=brandingHexToRgba($accent,.94)?>,<?=brandingHexToRgba($secondary,.84)?>);border-color:<?=brandingHexToRgba($sidebarText,.22)?>}
.auth-shell{position:relative;isolation:isolate}.auth-shell::before{content:'';position:fixed;inset:0;z-index:-2;pointer-events:none;<?php if($loginBg !== ''): ?>background-image:linear-gradient(110deg,<?=brandingHexToRgba($background,.88)?>,<?=brandingHexToRgba($background,.66)?>),url(<?=$loginBgCss?>);background-size:cover;background-position:<?=$loginPosition?>;background-repeat:no-repeat;<?php else: ?>background:radial-gradient(ellipse at 8% 4%,<?=$accent18?>,transparent 46%),radial-gradient(ellipse at 90% 80%,<?=$secondary18?>,transparent 44%),<?=$background?>;<?php endif; ?>}
.branding-preview,.branding-preview-window{border-color:<?=$accent18?>;background:linear-gradient(145deg,<?=brandingHexToRgba($surface,.92)?>,<?=$accent08?>)}.branding-logo-preview{background:<?=$surface?>;border-color:<?=$accent12?>}.branding-color-grid input[type=color]{border-color:<?=$accent18?>}

/* Corrección de legibilidad: el editor de personalización nunca adopta texto ilegible del tema que está editando. */
.branding-editor-main,.branding-editor-main .branding-section,.branding-editor-main .branding-intro,.branding-editor-main .branding-savebar,.branding-editor-main .branding-reset{color:#18212f!important;background:rgba(255,255,255,.96)!important;border-color:#d9dfe8!important}
.branding-editor-main .field>span,.branding-editor-main .branding-section-title h2,.branding-editor-main .branding-intro h2,.branding-editor-main .branding-savebar strong{color:#18212f!important}
.branding-editor-main .field small,.branding-editor-main .help,.branding-editor-main .branding-section-title p,.branding-editor-main .branding-intro p,.branding-editor-main .branding-savebar small{color:#647084!important}
.branding-editor-main .field input,.branding-editor-main .field select,.branding-editor-main .field textarea,.branding-editor-main select{color:#18212f!important;background:#fff!important;border-color:#d8dee8!important}
.branding-editor-main .theme-preset,.branding-editor-main .icon-picker-current{color:#18212f!important;background:#fff!important;border-color:#d8dee8!important}
.branding-editor-main .color-field code{color:#526071!important;background:#f7f9fb!important;border-color:#d8dee8!important}
.branding-editor-main .branding-upload{color:#18212f!important;background:#f8fafc!important;border-color:#ccd5e2!important}
.branding-editor-main .branding-upload input[type=file]{color:#18212f!important;background:#fff!important;border-color:#d8dee8!important}
.branding-editor-main .contrast-status{color:#526071!important;background:#f8fafc!important;border-color:#d8dee8!important}
.branding-editor-main .branding-step{background:#eef2f7!important;color:#334155!important}

/* El redondeo configurado se aplica a las superficies principales, no solo a las tarjetas. */
.shortcut,.quick-action,.table-wrap,.search,.notice,.auth-link-button,.recovery-card,.recovery-intro,.recovery-help,.branding-upload,.branding-sample,.branding-security-note{border-radius:var(--radius)!important}
@media(max-width:1100px){.app{grid-template-columns:<?=max(205,$sidebarWidth-30)?>px minmax(0,1fr)}}
@media(max-width:760px){.sidebar{background:linear-gradient(160deg,<?=$sidebar98?>,<?=brandingHexToRgba($secondary,.12)?>),<?=$sidebar?>}.bottom-nav{background:linear-gradient(145deg,<?=$sidebar98?>,<?=brandingHexToRgba($secondary,.10)?>)}}
@supports not ((backdrop-filter:blur(1px)) or (-webkit-backdrop-filter:blur(1px))){.glass{background:<?=$surface?>}.sidebar,.bottom-nav{background:<?=$sidebar?>}}
@media(prefers-reduced-transparency:reduce){.glass{background:<?=$surface?>;backdrop-filter:none;-webkit-backdrop-filter:none}.sidebar,.bottom-nav{background:<?=$sidebar?>;backdrop-filter:none;-webkit-backdrop-filter:none}.hero{background:<?=$hero?>}.hero-icon{backdrop-filter:none;-webkit-backdrop-filter:none}}


/* V5.1 final theme hardening */
.sidebar .nav{flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;scrollbar-gutter:stable;overscroll-behavior:contain;}
.sidebar .nav a,.sidebar .nav a.active{border-radius:var(--button-radius)!important;}
.sidebar .nav a{color:<?=$sidebarTextSecondary?>!important;}
.sidebar .nav a.active{color:<?=$sidebarTextEffective?>!important;}
.hero h2,.hero p,.hero .eyebrow{color:<?=$heroTextEffective?>!important;}
.hero p,.hero .eyebrow{color:<?=$heroTextSecondary?>!important;}
.announcement h3,.announcement p,.announcement .meta{color:<?=$noticeTextEffective?>!important;}
.announcement p,.announcement .meta{color:<?=$noticeTextSecondary?>!important;}
.button{border-radius:var(--button-radius)!important;}

/* V6 — motor visual unificado */
html,body{background-color:<?=$background?>;}
body{background-image:none!important;background-attachment:initial!important;}
/* Barra de desplazamiento delgada y visible: así se nota que el menú tiene más opciones. */
.sidebar .nav{scrollbar-width:thin;scrollbar-color:<?=brandingHexToRgba($sidebarText,.35)?> transparent;}
.sidebar .nav::-webkit-scrollbar{width:6px;}
.sidebar .nav::-webkit-scrollbar-thumb{background:<?=brandingHexToRgba($sidebarText,.35)?>;border-radius:6px;}
.brand-mark img{object-fit:contain!important;object-position:center!important;max-width:100%;max-height:100%;}
.button.info.cycle-link,.cycle-overview .button.info{background:<?=$accent?>!important;color:<?=$accentText?>!important;border-color:<?=brandingHexToRgba($surface,.35)?>!important;}.button.info.cycle-link:hover,.cycle-overview .button.info:hover{background:color-mix(in srgb, <?=$accent?> 88%, white 12%)!important;}
.cycle-overview .button{border-radius:var(--button-radius)!important;}
.preview-nav,.preview-action,.preview-logout{border-radius:var(--preview-button-radius,var(--button-radius))!important;}
.preview-logout{color:<?=$surfaceText?>!important;}
<?php if($globalTextMode): ?>
body,.workspace,.topbar,.page-heading,.card,.card h1,.card h2,.card h3,.card p,.card small,.table-wrap,table,th,td,.hero,.hero h2,.hero p,.announcement,.announcement h3,.announcement p,.announcement .meta,.shortcut,.quick-action,.breadcrumb,.breadcrumb strong,.field,.field small,.help,.footer-note,.cycle-overview,.cycle-overview *,.dialog,.dialog h2,.dialog p,a{color:<?=$text?>!important;}
.sidebar,.sidebar *,.bottom-nav,.bottom-nav *{color:<?=$text?>;}
.button,.button *,.logout,.logout *,.brand-mark,.brand-mark *{color:<?=$text?>!important;}
<?php endif; ?>
/* Contraste garantizado por zona: cada fondo usa una letra que sí se lee. */
:root{--surface-soft:<?=$softBg?>;--surface-text:<?=$surfaceText?>;--bg-text:<?=$backgroundText?>}
th,.field input,.field select,.field textarea,select,.search,.grade-cell,.empty,.promo-stats div,.invite-data div,.mm-list,.student-picker,.health-row,.pw-meter,.recovery-flow>div,.table-wrap,.auth-link-button,.auth-link-button.single,.auth-link-button.recovery,.recovery-back,.recovery-back-bottom,.button:not(.primary):not(.danger):not(.success):not(.warning):not(.info){background:<?=$softBg?>!important;border-color:<?=$softLine?>!important;color:<?=$surfaceText?>!important}
.promo-stats div *,.invite-data div *,.mm-item,.mm-item span,.health-row,.health-row *:not(.button),.student-picker .field>span,.pw-checks li,.pw-label,.recovery-flow>div *,.empty *{color:<?=$surfaceText?>}
.promo-stats span,.invite-data dt,.pw-checks li:not(.ok){color:<?=$surfaceMuted?>!important}
@media(max-width:760px){table.rtable tr{background:<?=$softBg?>!important;border-color:<?=$softLine?>!important}table.rtable td{color:<?=$surfaceText?>}table.rtable td::before{color:<?=$surfaceMuted?>!important}}
@container tw (max-width:700px){table.rtable tr{background:<?=$softBg?>!important;border-color:<?=$softLine?>!important}table.rtable td{color:<?=$surfaceText?>}table.rtable td::before{color:<?=$surfaceMuted?>!important}}
.notice,.notice *:not(a):not(.button):not(.button *){color:#4a3410!important}
.notice a:not(.button){color:#8a1730!important}
.workspace>.buttons .help,.workspace>.buttons>span,.page-heading .help,.cycle-create-hint{color:<?=brandingHexToRgba($backgroundText,.82)?>!important}
.bottom-nav a{color:<?=brandingHexToRgba($sidebarTextEffective,.82)?>!important}
.bottom-nav a.active,.bottom-nav a.active *{color:<?=$onAccent?>!important}
.mad-confirm-card{background:<?=$effSurface?>!important;color:<?=$surfaceText?>!important}
.mad-confirm-card *:not(.button){color:<?=$surfaceText?>}
.badge,.avatar{background:<?=$badgeBg?>!important;color:<?=$badgeFgc?>!important}
.badge.green{background:<?=$badgeGreenBg?>!important;color:<?=$badgeGreenFg?>!important}
.badge.orange{background:<?=$badgeOrangeBg?>!important;color:<?=$badgeOrangeFg?>!important}
.badge.red{background:<?=$badgeRedBg?>!important;color:<?=$badgeRedFg?>!important}
.sidebar .avatar{background:<?=$sidebarAvatarBg?>!important;color:<?=$sidebarTextEffective?>!important}
.pill,.cycle-pill{background:<?=$pillBg?>!important;color:<?=$pillFg?>!important}
.role-tag{color:<?=$backgroundText?>!important;border-color:<?=brandingHexToRgba($backgroundText,.25)?>!important;background:transparent!important}
.quiet-link,.card .quiet-link,.auth-links a,.auth-link-button,.auth-link-button span,.auth-link-button svg,.quick-action span,.quick-action svg,.shortcut svg{color:<?=$onSurfaceAccent?>!important}
.sidebar .quiet-link{color:<?=$sidebarTextSecondary?>!important}
.auth-story .brand small,.register-shell>.brand small,.recovery-topbar .brand small{color:<?=brandingHexToRgba($backgroundText,.78)?>!important}
.auth-story .brand strong,.register-shell>.brand strong,.recovery-topbar .brand strong{color:<?=$backgroundText?>!important}
.glass,.card,.stat,.shortcut,.cycle-overview,.auth-card,.dialog,.cta-band,.list-filter,.table-wrap{color:<?=$surfaceText?>}
.glass h1,.glass h2,.glass h3,.glass strong,.card h1,.card h2,.card h3,.card strong,.card td,.card th,.card label,.card .field>span,.stat strong,.dialog h2,.dialog .field>span,.auth-card h2,.auth-card .field>span,.shortcut span,.cycle-overview strong,.cta-band strong{color:<?=$surfaceText?>}
.glass p,.glass small,.card p,.card small,.card .help,.stat small,.stat-top,.dialog p,.dialog .help,.auth-card>p,.auth-card .field small,.cta-band p,.lf-select,.lf-count,.cycle-overview small,.cycle-overview span,.card .overline,.glass .overline{color:<?=$surfaceMuted?>}
.card a:not(.button),.glass a:not(.button),.dialog a:not(.button){color:<?=$onSurfaceAccent?>}
.page-heading h1,.breadcrumb,.breadcrumb strong,.top-actions,.footer-note,.auth-story h1,.auth-story p,.auth-foot,.auth-story .overline,.register-shell>.brand,.recovery-shell>.recovery-topbar .brand{color:<?=$backgroundText?>}
.page-heading p,.top-actions .date{color:<?=brandingHexToRgba($backgroundText,.72)?>}
.auth-story h1 span{color:<?=$onBackgroundAccent?>}
.sidebar .brand,.sidebar .brand strong,.sidebar .user-row strong{color:<?=$sidebarTextEffective?>!important}
.sidebar .brand small,.sidebar .overline,.sidebar .user-row small{color:<?=$sidebarTextSecondary?>!important}
.empty,.empty strong{color:<?=$surfaceText?>}
.field input,.field select,.field textarea,select,.search input,.grade-cell{color:<?=$surfaceText?>!important}
.search{background:<?=brandingHexToRgba($surfaceText,.06)?>;color:<?=$surfaceMuted?>}
th{color:<?=brandingHexToRgba($surfaceText,.74)?>!important}
td{border-bottom-color:<?=brandingHexToRgba($surfaceText,.10)?>}
.notice{color:#5f4313}
/* Contraste garantizado: texto legible sobre botones de color, aunque otras reglas digan lo contrario. */
.button.primary,.button.primary *{color:<?=$onAccent?>!important}
.button.danger,.button.danger *{color:<?=$onDanger?>!important}
.button.success,.button.success *{color:<?=$onSuccess?>!important}
.button.warning,.button.warning *{color:<?=$onWarning?>!important}
.button.info,.button.info *{color:<?=$onInfo?>!important}
.sidebar .logout,.sidebar .logout *{color:<?=$onDanger?>!important}
.sidebar .quiet-link{color:<?=$sidebarTextSecondary?>!important;text-decoration:underline;text-underline-offset:3px}
.brand-mark,.brand-mark *{color:<?=$onAccent?>!important}
/* Sin logo propio: la casita predeterminada va sobre el color principal y cambia con el tema. */
.brand-mark:not(.has-logo){background:linear-gradient(145deg,<?=$accent?>,<?=$secondary?>)!important;border:1px solid <?=brandingHexToRgba('#ffffff',.35)?>!important}
/* El borde de la tarjeta de acceso sigue el color principal (antes quedaba rosa con cualquier tema). */
.auth-card.glass{border-color:<?=brandingHexToRgba($accent,.30)?>}
/* Las cajas de carga de Personalización alinean su contenido arriba aunque tengan distinta altura. */
.branding-upload{align-content:start}
/* Con logo propio: fondo blanco limpio para que la imagen conserve sus colores reales. */
.brand-mark.has-logo{background:#fff!important;border:1px solid <?=brandingHexToRgba($text,.08)?>!important;overflow:hidden;padding:3px}
.brand-mark.has-logo img{display:block;width:100%;height:100%;object-fit:contain!important;padding:0!important;border-radius:0!important}
.sidebar .nav a.active,.sidebar .nav a.active *,.bottom-nav a.active,.bottom-nav a.active *{color:<?=$onAccent?>!important}
/* El propio editor siempre conserva controles legibles, aunque se esté probando un tema extremo. */
.branding-editor-main .branding-step{background:#172033!important;color:#fff!important}
.branding-editor-main .branding-savebar .button.primary{background:#172033!important;color:#fff!important;border-color:#172033!important;box-shadow:none!important}
.branding-editor-main .branding-reset .button{background:#fff5f7!important;color:#9c273b!important;border-color:#efd6dd!important}
@media(max-width:760px){body{min-width:320px;overflow-x:hidden}.workspace{padding:0 16px 34px}.app{min-height:100dvh}.sidebar{height:100dvh;max-height:100dvh;}}
<?php
/* ============================================================================
 * V7 — Sistema de componentes unificado
 * Superficies limpias (sin gris de relleno), botones en 3 tamaños con jerarquía
 * clara, tablas/tarjetas separadas, notificaciones y mensajes consistentes.
 * Todos los colores salen del tema para respetar la personalización.
 * ========================================================================== */
$lineSoft = brandingHexToRgba($surfaceText, .12);
$lineMid = brandingHexToRgba($surfaceText, .20);
$rowHover = $mix($effSurface, $accent, .035);
$listPanel = $mix($effSurface, $surfaceText, .035);
$toastBg = '#1d1a20';
$toastOk = $ensure($success, $toastBg, 3.2);
$toastErr = $ensure($danger, $toastBg, 3.2);
$accentRing = brandingHexToRgba($accent, .16);
?>
:root{--surface-card:<?=$effSurface?>;--line-soft:<?=$lineSoft?>;--line-mid:<?=$lineMid?>}

/* ---------- Botones: tamaños consistentes ---------- */
.button{min-height:40px;padding:0 16px;font-size:13px;font-weight:650;line-height:1.2;gap:8px;transition:background-color .15s,border-color .15s,box-shadow .15s,transform .15s,color .15s}
.button.small{min-height:34px;padding:0 13px;font-size:12px}
.button.big{min-height:48px;padding:0 22px;font-size:14px}
.button svg{width:16px;height:16px}
.button:active{transform:translateY(0)!important}
/* Secundario: superficie limpia con borde, no gris */
body .button:not(.primary):not(.danger):not(.success):not(.warning):not(.info){background:<?=$effSurface?>!important;border:1px solid <?=$lineMid?>!important;color:<?=$surfaceText?>!important;box-shadow:0 1px 2px rgba(0,0,0,.04)!important}
body .button:not(.primary):not(.danger):not(.success):not(.warning):not(.info):hover{background:<?=$softBg?>!important;border-color:<?=brandingHexToRgba($surfaceText,.30)?>!important}
/* Acciones con tono dentro de listas: suaves; el color sólido queda para la acción principal */
body .button.small.danger{background:<?=$badgeRedBg?>!important;border:1px solid <?=brandingHexToRgba($danger,.26)?>!important;box-shadow:none!important}
body .button.small.danger,body .button.small.danger *{color:<?=$badgeRedFg?>!important}
body .button.small.danger:hover{background:<?=$danger?>!important;border-color:<?=$danger?>!important}
body .button.small.danger:hover,body .button.small.danger:hover *{color:<?=$onDanger?>!important}
body .button.small.success{background:<?=$badgeGreenBg?>!important;border:1px solid <?=brandingHexToRgba($success,.28)?>!important;box-shadow:none!important}
body .button.small.success,body .button.small.success *{color:<?=$badgeGreenFg?>!important}
body .button.small.success:hover{background:<?=$success?>!important;border-color:<?=$success?>!important}
body .button.small.success:hover,body .button.small.success:hover *{color:<?=$onSuccess?>!important}
body .button.small.warning{background:<?=$badgeOrangeBg?>!important;border:1px solid <?=brandingHexToRgba($warning,.30)?>!important;box-shadow:none!important}
body .button.small.warning,body .button.small.warning *{color:<?=$badgeOrangeFg?>!important}
body .button.small.warning:hover{background:<?=$warning?>!important;border-color:<?=$warning?>!important}
body .button.small.warning:hover,body .button.small.warning:hover *{color:<?=$onWarning?>!important}
.buttons{gap:8px}
.buttons>form,form.inline-action{margin:0;display:inline-flex}
.list-filter .button,.lf-clear{min-height:44px!important}

/* ---------- Campos: blancos con borde y foco visible ---------- */
body .field input:not([type=checkbox]):not([type=radio]):not([type=color]):not([type=file]):not([type=range]),body .field select,body .field textarea,body select,body .search{background:<?=$effSurface?>!important;border:1px solid <?=$lineMid?>!important}
body .field input:focus,body .field select:focus,body .field textarea:focus,body select:focus,body .search:focus-within{border-color:<?=$accent?>!important;box-shadow:0 0 0 3px <?=$accentRing?>!important;outline:none}
body .search input{background:transparent!important;border:0!important;box-shadow:none!important}

/* ---------- Tablas ---------- */
body .table-wrap{background:<?=$effSurface?>!important;border:1px solid <?=$lineSoft?>!important;border-radius:calc(var(--radius) * .6)!important;box-shadow:none!important}
.table-wrap table{border-collapse:separate;border-spacing:0}
body .table-wrap th{background:<?=$listPanel?>!important;border-bottom:1px solid <?=$lineSoft?>;padding:12px 16px;font-size:11px;letter-spacing:.06em;border-radius:0!important}
.table-wrap td{padding:14px 16px;vertical-align:middle;background:transparent}
.table-wrap tbody tr:last-child td{border-bottom:0}
.table-wrap tbody tr{transition:background-color .12s}
.table-wrap tbody tr:hover>td{background:<?=$rowHover?>}
.table-wrap td .buttons{flex-wrap:wrap}

/* ---------- Persona (avatar + nombre + dato + enlace) ---------- */
.person{display:flex;gap:12px;align-items:flex-start;min-width:0}
.person .avatar{width:36px;height:36px;flex:0 0 36px;font-size:13px;font-weight:700;margin-top:1px}
.person-text{display:flex;flex-direction:column;gap:2px;min-width:0}
.person-text strong{font-size:14px;line-height:1.3;overflow-wrap:anywhere}
.person-text small{display:block;margin:0;font-size:11.5px;letter-spacing:.02em;color:<?=$surfaceMuted?>!important;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}
body .person-link{display:inline-flex;align-items:center;gap:4px;margin-top:5px;width:max-content;font-size:12px;font-weight:650;line-height:1.2;color:<?=$onSurfaceAccent?>!important;border-radius:6px;text-decoration:none}
.person-link svg{width:13px;height:13px;transition:transform .15s}
.person-link:hover{text-decoration:underline;text-underline-offset:3px}
.person-link:hover svg{transform:translateX(2px)}

/* ---------- Tablas como tarjetas (celular y columnas angostas) ---------- */
@media(max-width:760px){
 body .table-wrap:not(:has(.grade-table)){background:<?=$listPanel?>!important;border:1px solid <?=$lineSoft?>!important;padding:10px}
}
@container tw (max-width:700px){
 table.rtable{background:<?=$listPanel?>;padding:10px}
}
@media(max-width:760px){
 table.rtable tr{background:<?=$effSurface?>!important;border:1px solid <?=$lineSoft?>!important;border-radius:14px;padding:2px 16px;margin:0 0 10px;box-shadow:0 1px 2px rgba(0,0,0,.04),0 6px 16px rgba(0,0,0,.05)}
 table.rtable tr:last-child{margin-bottom:0}
 table.rtable tbody tr:hover>td{background:transparent}
 table.rtable td{padding:11px 0!important;align-items:center;border-bottom:1px solid <?=$lineSoft?>!important}
 table.rtable td:first-child{padding:14px 0 12px!important}
 table.rtable td:last-child{border-bottom:0!important}
 table.rtable td[data-label="Acciones"],table.rtable td[data-label="Acción"]{padding:12px 0 14px!important}
 table.rtable td[data-label="Acciones"]::before,table.rtable td[data-label="Acción"]::before{display:none}
 table.rtable td[data-label="Acciones"] .buttons,table.rtable td[data-label="Acción"] .buttons{display:grid;grid-template-columns:repeat(auto-fit,minmax(0,1fr));gap:8px;width:100%}
 table.rtable td[data-label="Acciones"] .buttons>*,table.rtable td[data-label="Acción"] .buttons>*{width:100%}
 table.rtable td[data-label="Acciones"] .button,table.rtable td[data-label="Acción"] .button{width:100%;min-height:40px}
 table.rtable td[data-label]:not([data-label=""]):not(:first-child):not([data-label="Acciones"]):not([data-label="Acción"]):not([data-label="Configurar"]){display:flex!important;justify-content:space-between;align-items:center;gap:12px;min-height:44px}
 table.rtable td[data-label]:not([data-label=""]):not(:first-child)::before{grid-row:auto;align-self:center;padding-top:0;flex:0 0 auto;max-width:45%}
 table.rtable td[data-label]:not([data-label=""]):not(:first-child)>*{margin-left:auto}
}
@container tw (max-width:700px){
 table.rtable tr{background:<?=$effSurface?>!important;border:1px solid <?=$lineSoft?>!important;border-radius:14px;padding:2px 16px;margin:0 0 10px;box-shadow:0 1px 2px rgba(0,0,0,.04),0 6px 16px rgba(0,0,0,.05)}
 table.rtable tr:last-child{margin-bottom:0}
 table.rtable tbody tr:hover>td{background:transparent}
 table.rtable td{padding:11px 0!important;align-items:center;border-bottom:1px solid <?=$lineSoft?>!important}
 table.rtable td:first-child{padding:14px 0 12px!important}
 table.rtable td:last-child{border-bottom:0!important}
 table.rtable td[data-label="Acciones"],table.rtable td[data-label="Acción"]{padding:12px 0 14px!important}
 table.rtable td[data-label="Acciones"]::before,table.rtable td[data-label="Acción"]::before{display:none}
 table.rtable td[data-label="Acciones"] .buttons,table.rtable td[data-label="Acción"] .buttons{display:grid;grid-template-columns:repeat(auto-fit,minmax(0,1fr));gap:8px;width:100%}
 table.rtable td[data-label="Acciones"] .buttons>*,table.rtable td[data-label="Acción"] .buttons>*{width:100%}
 table.rtable td[data-label="Acciones"] .button,table.rtable td[data-label="Acción"] .button{width:100%;min-height:40px}
 table.rtable td[data-label]:not([data-label=""]):not(:first-child):not([data-label="Acciones"]):not([data-label="Acción"]):not([data-label="Configurar"]){display:flex!important;justify-content:space-between;align-items:center;gap:12px;min-height:44px}
 table.rtable td[data-label]:not([data-label=""]):not(:first-child)::before{grid-row:auto;align-self:center;padding-top:0;flex:0 0 auto;max-width:45%}
 table.rtable td[data-label]:not([data-label=""]):not(:first-child)>*{margin-left:auto}
}

/* ---------- Mensajes dentro de formularios ---------- */
body .feedback{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:12px;font-size:13px;line-height:1.45;background:<?=$badgeRedBg?>;color:<?=$badgeRedFg?>;border:1px solid <?=brandingHexToRgba($danger,.22)?>}
body .feedback::before{content:'';flex:0 0 18px;width:18px;height:18px;margin-top:1px;background:currentColor;-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7.5v5.5M12 16.5h.01'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7.5v5.5M12 16.5h.01'/%3E%3C/svg%3E") center/contain no-repeat}
body .feedback.success{background:<?=$badgeGreenBg?>;color:<?=$badgeGreenFg?>;border-color:<?=brandingHexToRgba($success,.24)?>}
body .feedback.success::before{-webkit-mask-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='m8 12.5 2.8 2.8L16.5 9.5'/%3E%3C/svg%3E");mask-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='m8 12.5 2.8 2.8L16.5 9.5'/%3E%3C/svg%3E")}
body .feedback strong{color:inherit!important}
.feedback[hidden]{display:none!important}
.notice{border-radius:12px!important;border-left:3px solid <?=$warning?>!important;padding:13px 16px;line-height:1.5}

/* ---------- Notificación flotante ---------- */
.toast{display:flex;align-items:flex-start;gap:10px;left:auto;right:24px;bottom:24px;transform:none;width:max-content;max-width:min(420px,calc(100vw - 32px));padding:13px 12px 13px 16px;border-radius:14px;background:<?=$toastBg?>;color:#fff;font-size:13px;line-height:1.45;text-align:left;border-left:4px solid <?=$toastOk?>;box-shadow:0 18px 50px rgba(0,0,0,.24);animation:mad-toast-in .22s ease-out;z-index:60}
.toast>svg{width:18px;height:18px;flex:0 0 18px;margin-top:1px;color:<?=$toastOk?>}
.toast--error{border-left-color:<?=$toastErr?>}.toast--error>svg{color:<?=$toastErr?>}
.toast--info{border-left-color:#8fb4ff}.toast--info>svg{color:#8fb4ff}
.toast>span{flex:1;min-width:0;overflow-wrap:anywhere;padding-top:1px}
.toast-close{display:grid;place-items:center;width:26px;height:26px;flex:0 0 26px;margin:-3px -2px 0 2px;border:0;border-radius:8px;background:transparent;color:rgba(255,255,255,.7)}
.toast-close:hover{background:rgba(255,255,255,.1);color:#fff}
.toast-close svg{width:15px;height:15px}
@keyframes mad-toast-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
@media(max-width:760px){.toast{left:16px;right:16px;width:auto;max-width:none;bottom:calc(92px + env(safe-area-inset-bottom))}}
@media(prefers-reduced-motion:reduce){.toast{animation:none}.button,.person-link svg{transition:none}}

/* ---------- Encabezado: acción con nota debajo (no a un lado) ---------- */
.heading-action{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
.heading-hint{display:flex;align-items:center;gap:6px;margin:0;max-width:300px;font-size:12px;line-height:1.35;text-align:right;color:<?=brandingHexToRgba($backgroundText,.72)?>!important}
.heading-hint svg{width:14px;height:14px;flex:none}
@media(max-width:760px){.page-heading>.buttons>.heading-action{width:100%;align-items:stretch}.heading-action .button{width:100%}.heading-hint{max-width:none;text-align:left}}

/* ---------- Acciones de formulario apiladas (mismo ancho) ---------- */
.form-actions-stack{display:grid;gap:10px;margin-top:6px}
.form-actions-stack .button{width:100%;min-height:46px}

/* ---------- Anuncios: acciones en una sola fila ---------- */
.announcement-actions{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 16px;margin-top:14px;padding-top:12px;border-top:1px solid <?=brandingHexToRgba($noticeTextEffective,.12)?>}
.announcement-status{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px}
.announcement-status .help{margin:0}
.announcement-actions .buttons{margin:0}
.heading-hint{justify-content:flex-end;max-width:280px}
.heading-hint span{text-align:left}
/* Recuadros internos: superficie limpia con borde (sin gris de relleno) */
body .promo-stats div,body .health-row,body .invite-data div,body .mm-list,body .student-picker{background:<?=$effSurface?>!important;border:1px solid <?=$lineSoft?>!important;box-shadow:0 1px 2px rgba(0,0,0,.03)}
body .empty{background:<?=$listPanel?>!important;border:1px dashed <?=$lineMid?>!important}
.card .table-wrap+*:not(.table-wrap){margin-top:18px}
.toast{border-left:0!important;box-shadow:inset 4px 0 0 <?=$toastOk?>,0 18px 50px rgba(0,0,0,.24)}
.toast--error{box-shadow:inset 4px 0 0 <?=$toastErr?>,0 18px 50px rgba(0,0,0,.24)}
.toast--info{box-shadow:inset 4px 0 0 #8fb4ff,0 18px 50px rgba(0,0,0,.24)}
@media(max-width:760px){.heading-hint{max-width:none;justify-content:flex-start}}
.field small.field-error{color:<?=$badgeRedFg?>!important;font-weight:600}
body .field input[aria-invalid="true"]{border-color:<?=$danger?>!important;box-shadow:0 0 0 3px <?=brandingHexToRgba($danger,.14)?>!important}
/* Enlaces-botón de acceso y registro: superficie limpia, no gris */
body .auth-link-button,body .auth-link-button.single,body .recovery-back{background:<?=$effSurface?>!important;border:1px solid <?=$lineMid?>!important}
body .auth-link-button:hover,body .recovery-back:hover{background:<?=$softBg?>!important}
/* Selector de alumno: alumno elegido en lugar de filtros */
.picker-chosen{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:14px;background:<?=$effSurface?>;border:1px solid <?=$lineSoft?>}
.picker-chosen[hidden],.picker-filters[hidden]{display:none!important}
.picker-chosen .avatar{width:38px;height:38px;flex:0 0 38px;display:grid;place-items:center;border-radius:12px;font-weight:700}
.picker-chosen-text{display:flex;flex-direction:column;gap:2px;flex:1;min-width:0}
.picker-chosen-text small{font-size:11px;color:<?=$surfaceMuted?>!important;text-transform:uppercase;letter-spacing:.05em}
.picker-chosen-text strong{font-size:14px;overflow-wrap:anywhere}
.picker-filters{display:contents}
body .student-picker:has(> .picker-chosen:not([hidden])){padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}
/* ---------- Notificación arriba (capa superior, encima de ventanas) ---------- */
.toast,.toast:popover-open{position:fixed;inset:auto;top:20px;left:50%;right:auto;bottom:auto;transform:translateX(-50%);margin:0;border:0;width:max-content;max-width:min(460px,calc(100vw - 32px));padding:14px 12px 14px 16px;border-radius:14px;background:<?=$toastBg?>;color:#fff;overflow:visible;z-index:2147483000;animation:none}
.toast.toast--in{animation:mad-toast-top .26s cubic-bezier(.2,.8,.2,1)}
.toast[hidden]{display:none!important}
@keyframes mad-toast-top{from{opacity:0;transform:translate(-50%,-14px)}to{opacity:1;transform:translate(-50%,0)}}
@media(max-width:760px){.toast,.toast:popover-open{top:calc(12px + env(safe-area-inset-top));left:12px;right:12px;bottom:auto;transform:none;width:auto;max-width:none}@keyframes mad-toast-top{from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:none}}}
@media(prefers-reduced-motion:reduce){.toast.toast--in{animation:none}}
/* ---------- Selector de alumno v2 ---------- */
body .student-picker{display:block!important;padding:14px!important;border:1px solid <?=$lineSoft?>!important;border-radius:16px!important;background:<?=$listPanel?>!important;box-shadow:none!important}
body .student-picker.is-chosen{padding:0!important;border:0!important;background:transparent!important}
body .student-picker.has-error{border-color:<?=$danger?>!important;box-shadow:0 0 0 3px <?=brandingHexToRgba($danger,.12)?>!important}
.picker-filters{display:block}
.picker-bar{display:grid;grid-template-columns:minmax(150px,200px) 1fr;gap:12px;align-items:end}
.picker-bar .field{margin:0}
.picker-search-box{position:relative;display:block}
.picker-search-box>svg{position:absolute;left:13px;top:50%;width:17px;height:17px;transform:translateY(-50%);color:<?=$surfaceMuted?>;pointer-events:none}
body .picker-search-box input{padding-left:40px!important;width:100%}
.student-picker.is-enhanced .picker-select{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;margin:0;padding:0}
.picker-results{display:grid;gap:6px;margin-top:12px;max-height:264px;overflow-y:auto;overscroll-behavior:contain;padding:2px;scrollbar-width:thin}
.picker-results[hidden]{display:none!important}
.picker-item{display:flex;align-items:center;gap:12px;width:100%;min-height:52px;padding:8px 12px;border:1px solid <?=$lineSoft?>;border-radius:12px;background:<?=$effSurface?>;color:<?=$surfaceText?>;font:inherit;text-align:left;cursor:pointer;transition:border-color .12s,background-color .12s,box-shadow .12s}
.picker-item:hover{border-color:<?=brandingHexToRgba($accent,.45)?>;background:<?=$rowHover?>}
.picker-item:focus-visible{outline:none;border-color:<?=$accent?>;box-shadow:0 0 0 3px <?=$accentRing?>}
.picker-item[aria-selected="true"]{border-color:<?=$accent?>}
.picker-avatar{display:grid;place-items:center;width:34px;height:34px;flex:0 0 34px;border-radius:50%;background:<?=$badgeRedBg?>;color:<?=$onSurfaceAccent?>;font-size:13px;font-weight:700}
.picker-item-name{flex:1;min-width:0;font-size:14px;font-weight:600;overflow-wrap:anywhere}
.picker-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;background:<?=$softBg?>;color:<?=$surfaceText?>;font-size:12px;font-weight:650;white-space:nowrap}
.picker-chip[hidden]{display:none}
.picker-count{margin:10px 2px 0;font-size:12px;color:<?=$surfaceMuted?>}
.picker-count.is-error{color:<?=$badgeRedFg?>;font-weight:650}
.student-picker:not(.is-enhanced) .picker-count{display:none}
.picker-chosen{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:16px;background:<?=$effSurface?>;border:1px solid <?=brandingHexToRgba($accent,.35)?>;box-shadow:0 1px 2px rgba(0,0,0,.04),0 0 0 3px <?=brandingHexToRgba($accent,.06)?>}
.picker-chosen .picker-avatar{width:42px;height:42px;flex-basis:42px;font-size:16px}
.picker-chosen-text small{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:<?=$surfaceMuted?>!important;font-weight:650}
.picker-chosen-text strong{font-size:15px}
.picker-chosen .button svg{width:14px;height:14px}
@media(max-width:560px){.picker-bar{grid-template-columns:1fr}.picker-chosen{flex-wrap:wrap}.picker-chosen .button{margin-left:auto}}
@media(prefers-reduced-motion:reduce){.picker-item{transition:none}}
/* Botón de envío de invitación: cargando y enviado */
.btn-spinner{width:15px;height:15px;border-radius:50%;border:2px solid currentColor;border-right-color:transparent;animation:mad-spin .7s linear infinite}
@keyframes mad-spin{to{transform:rotate(360deg)}}
body .button.primary.is-done{background:<?=$success?>!important;border-color:<?=$success?>!important;opacity:1!important}
body .button.primary.is-done,body .button.primary.is-done *{color:<?=$onSuccess?>!important}
.button.is-done svg{width:16px;height:16px}
@media(prefers-reduced-motion:reduce){.btn-spinner{animation-duration:2s}}
@media(max-width:560px){
 .picker-chosen{flex-wrap:wrap;row-gap:10px}
 .picker-chosen-text{flex:1 1 calc(100% - 60px)}
 .picker-chosen .picker-chip{margin-left:56px}
 .picker-chosen .button{margin-left:auto}
}
