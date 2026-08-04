<?php
// calificaciones.php (Vista del Padre/Tutor - Filtrada estrictamente por el alumno y permisos de sesión)

// =======================================================
// CONTROL DE SEGURIDAD Y CONFIGURACIÓN INICIAL
// =======================================================
if (!isset($conexion)) {
    die("Error: No existe conexión con la base de datos.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_padre_sesion = $_SESSION['id_padre'] ?? null;
$id_alumno_sesion = $_SESSION['id_alumno'] ?? null;

// =======================================================
// OBTENER INFORMACIÓN DEL ALUMNO VALIDANDO AL PADRE
// =======================================================
$alumno_activo = null;

if ($id_alumno_sesion && $id_padre_sesion) {
    // Consulta segura validando que el alumno pertenezca al tutor en sesión
    $sql_validar_alumno = "SELECT a.ID_Alumno, a.Nombre, a.Primer_Apellido, a.Segundo_Apellido, g.Grado, g.Grupo 
                           FROM alumno a
                           LEFT JOIN grupo g ON a.ID_Grupo = g.ID_Grupo 
                           WHERE a.ID_Alumno = ? AND a.ID_Padre = ? LIMIT 1";

    $stmt_val = $conexion->prepare($sql_validar_alumno);
    if ($stmt_val) {
        $stmt_val->bind_param("ii", $id_alumno_sesion, $id_padre_sesion);
        $stmt_val->execute();
        $res_validar_alumno = $stmt_val->get_result();
        
        if ($res_validar_alumno && $res_validar_alumno->num_rows > 0) {
            $alumno_activo = $res_validar_alumno->fetch_assoc();
        }
        $stmt_val->close();
    }
}

// =======================================================
// LÓGICA DE CALIFICACIONES
// =======================================================
$bimestre_activo = isset($_GET['bimestre']) ? intval($_GET['bimestre']) : 1;
if ($bimestre_activo < 1 || $bimestre_activo > 5) {
    $bimestre_activo = 1;
}

$campos_formativos = [
    'Lenguajes', 
    'Saberes y pensamiento científico', 
    'Ética, Naturaleza y sociedades', 
    'De lo humano a lo comunitario'
];

$fila_alumno_vista = [];

if ($alumno_activo) {
    $id_al = $alumno_activo['ID_Alumno'];
    
    $notas_campos = [];
    foreach ($campos_formativos as $cf) {
        $notas_campos[$cf] = '-'; 
    }
    
    // Consulta de calificaciones segura con prepared statements
    $sql_calif = "SELECT cf.Nombre_Campo, c.Calificacion_Numerica 
                  FROM calificacion c
                  INNER JOIN campo_formativo cf ON c.ID_Campo = cf.ID_Campo
                  WHERE c.ID_Alumno = ? AND c.Bimestre = ?";
                  
    $stmt_calif = $conexion->prepare($sql_calif);
    if ($stmt_calif) {
        $stmt_calif->bind_param("ii", $id_al, $bimestre_activo);
        $stmt_calif->execute();
        $res_calif = $stmt_calif->get_result();
        
        $suma_notas = 0;
        $conteo_notas = 0;
        
        if ($res_calif && $res_calif->num_rows > 0) {
            while ($row = $res_calif->fetch_assoc()) {
                $campo_name = trim($row['Nombre_Campo']);
                $val_nota = floatval($row['Calificacion_Numerica']);
                
                if (array_key_exists($campo_name, $notas_campos)) {
                    $notas_campos[$campo_name] = $val_nota;
                    $suma_notas += $val_nota;
                    $conteo_notas++;
                }
            }
        }
        $stmt_calif->close();
        
        $promedio_final = ($conteo_notas > 0) ? round($suma_notas / $conteo_notas, 1) : '-';
        
        $fila_alumno_vista[] = [
            'grupo' => ($alumno_activo['Grado'] && $alumno_activo['Grupo']) ? $alumno_activo['Grado'] . "°" . $alumno_activo['Grupo'] : 'S/G',
            'nombre' => $alumno_activo['Nombre'] . " " . $alumno_activo['Primer_Apellido'] . " " . ($alumno_activo['Segundo_Apellido'] ?? ''),
            'notas' => $notas_campos,
            'promedio' => $promedio_final
        ];
    }
}
?>

<link rel="stylesheet" href="css/calificaciones.css">

<div class="calificaciones-container">
    
    <div class="calif-header-box">
        <div>
            <h1>Boleta de Calificaciones</h1>
            <p>
                <?php if($alumno_activo): ?>
                    Campos Formativos para: <strong style="color:#0284c7;"><?php echo htmlspecialchars($fila_alumno_vista[0]['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php else: ?>
                    Selecciona un alumno activo en la sección "Mis Hijos"
                <?php endif; ?>
                &bull; Generado: <?php echo date('d/m/Y H:i'); ?>
            </p>
        </div>
        
        <div class="dropdown-container">
            <button id="btnDownloadToggle" onclick="toggleDropdown()" class="btn-download" <?php echo !$alumno_activo ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                <i class="fa-solid fa-download"></i> Descargar boleta
            </button>
            <div id="downloadDropdown" class="download-dropdown-menu">
                <?php if($alumno_activo): ?>
                    <a href="exportar_pdf.php?bimestre=<?php echo $bimestre_activo; ?>" target="_blank">
                        <i class="fa-regular fa-file-pdf" style="color:#ef4444; margin-right:8px;"></i> Formato PDF
                    </a>
                    <a href="exportar_excel.php?bimestre=<?php echo $bimestre_activo; ?>">
                        <i class="fa-regular fa-file-excel" style="color:#10b981; margin-right:8px;"></i> Formato Excel
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bimestres-nav">
        <?php 
        $url_base = "dashboard.php?view=calificaciones";
        ?>

        <?php for ($b = 1; $b <= 5; $b++): ?>
            <?php $clase_activa = ($b === $bimestre_activo) ? 'active' : 'inactive'; ?>
            <a href="<?php echo $url_base . '&bimestre=' . $b; ?>" class="btn-bimestre <?php echo $clase_activa; ?>">
                Bimestre <?php echo $b; ?>
            </a>
        <?php endfor; ?>
    </div>

    <div class="table-calif-wrapper">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Grupo</th>
                    <th style="min-width: 200px;">Nombre del Alumno</th>
                    <?php foreach ($campos_formativos as $cf): ?>
                        <th style="text-align: center; max-width: 150px;"><?php echo $cf; ?></th>
                    <?php endforeach; ?>
                    <th style="text-align: center;">Promedio Final</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($fila_alumno_vista)): ?>
                    <?php foreach ($fila_alumno_vista as $fila): ?>
                        <tr>
                            <td>
                                <span class="badge-grupo">
                                    <?php echo htmlspecialchars($fila['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="student-name-cell">
                                <?php echo htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <?php foreach ($campos_formativos as $cf): 
                                $nota = $fila['notas'][$cf];
                                $color_nota = ($nota !== '-') ? '#10b981' : '#9ca3af';
                            ?>
                                <td class="score-cell" style="color: <?php echo $color_nota; ?>; font-weight: bold;">
                                    <?php echo ($nota !== '-') ? number_format($nota, 1) : '-'; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="average-cell" style="font-weight: bold;">
                                <?php echo ($fila['promedio'] !== '-') ? number_format($fila['promedio'], 1) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo count($campos_formativos) + 3; ?>" class="empty-table-cell" style="text-align: center; padding: 30px; color: #64748b;">
                            No se encontraron registros evaluados para este alumno en el Bimestre <?php echo $bimestre_activo; ?>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="js/calificaciones.js"></script>