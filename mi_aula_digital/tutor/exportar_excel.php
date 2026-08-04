<?php
// tutor/exportar_excel.php
include('../config/conexion.php'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_padre_sesion = $_SESSION['id_padre'] ?? null;
$id_alumno_sesion = $_SESSION['id_alumno'] ?? null;

$id_alumno_activo = null;

if ($id_alumno_sesion && $id_padre_sesion) {
    // Validar de forma estricta que el alumno pertenezca al tutor en sesión
    $sql_val = "SELECT ID_Alumno FROM alumno WHERE ID_Alumno = ? AND ID_Padre = ? LIMIT 1";
    $stmt_val = $conexion->prepare($sql_val);
    if ($stmt_val) {
        $stmt_val->bind_param("ii", $id_alumno_sesion, $id_padre_sesion);
        $stmt_val->execute();
        $res_val = $stmt_val->get_result();
        if ($res_val && $res_val->num_rows > 0) {
            $id_alumno_activo = $id_alumno_sesion;
        }
        $stmt_val->close();
    }
}

if (!$id_alumno_activo) {
    die("Error: No se ha identificado un alumno activo válido en la sesión.");
}

// Ajustado a los 5 bimestres
$bimestres_lista = [1, 2, 3, 4, 5];
$campos_formativos = [
    'Lenguajes', 
    'Saberes y pensamiento científico', 
    'Ética, Naturaleza y sociedades', 
    'De lo humano a lo comunitario'
];

$alumno_datos = null;
if (isset($conexion)) {
    // Consulta segura utilizando prepared statements
    $sql_alumno_sel = "SELECT a.ID_Alumno, a.Nombre, a.Primer_Apellido, a.Segundo_Apellido, g.Grado, g.Grupo 
                      FROM alumno a
                      LEFT JOIN grupo g ON a.ID_Grupo = g.ID_Grupo 
                      WHERE a.ID_Alumno = ?";
                      
    $stmt_alumno = $conexion->prepare($sql_alumno_sel);
    if ($stmt_alumno) {
        $stmt_alumno->bind_param("i", $id_alumno_activo);
        $stmt_alumno->execute();
        $res_alumno_sel = $stmt_alumno->get_result();
        if ($res_alumno_sel && $res_alumno_sel->num_rows > 0) {
            $alumno_datos = $res_alumno_sel->fetch_assoc();
        }
        $stmt_alumno->close();
    }
}

// Forzamos la descarga asegurando compatibilidad XML-Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Concentrado_Calificaciones.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        .title-main { font-family: 'Segoe UI', Arial; font-size: 18px; font-weight: bold; color: #111827; text-align: left; }
        .title-sub { font-family: 'Segoe UI', Arial; font-size: 11px; color: #4b5563; text-align: left; }
        
        /* Cabecera del Alumno */
        .row-alumno-header { background-color: #1e2538; color: #ffffff; font-family: 'Segoe UI', Arial; font-size: 12px; font-weight: bold; text-align: left; }
        
        /* Encabezados de columnas */
        .th-col { background-color: #ffffff; color: #374151; font-family: 'Segoe UI', Arial; font-size: 11px; font-weight: bold; text-align: center; border: 1px solid #d1d5db; }
        
        /* Celdas de datos */
        .td-data { font-family: 'Segoe UI', Arial; font-size: 11px; text-align: center; border: 1px solid #e5e7eb; color: #1f2937; }
        .td-bimestre { font-family: 'Segoe UI', Arial; font-size: 11px; font-weight: bold; text-align: center; border: 1px solid #e5e7eb; }
        
        /* Pie de Promedio Ciclo */
        .lbl-final { font-family: 'Segoe UI', Arial; font-size: 11px; font-weight: bold; text-align: right; background-color: #ffffff; border: 1px solid #e5e7eb; }
        .val-final { font-family: 'Segoe UI', Arial; font-size: 12px; font-weight: bold; text-align: center; background-color: #f0fdf4; color: #16a34a; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>

<table>
    <tbody>
        <tr>
            <td colspan="2" rowspan="3" style="vertical-align: middle; text-align: center;">
                <img src="http://localhost/mi_aula_digital/assets/img/escudo.jpeg" width="70" height="70" alt="Logo">
            </td>
            <td colspan="5" class="title-main">Concentrado Histórico de Calificaciones</td>
        </tr>
        <tr>
            <td colspan="5" class="title-sub">Ciclo Escolar Activo | Fecha de Emisión: <?php echo date('d/m/Y'); ?></td>
        </tr>
        <tr>
            <td colspan="5"></td>
        </tr>
        
        <tr style="height: 4px;"><td colspan="7" style="background-color: #b61a1a;"></td></tr>
        <tr style="height: 15px;"><td colspan="7"></td></tr>

        <?php if($alumno_datos): 
            $id_al = $alumno_datos['ID_Alumno'];
            $grupo = ($alumno_datos['Grado'] && $alumno_datos['Grupo']) ? $alumno_datos['Grado'] . "°" . $alumno_datos['Grupo'] : 'S/G';
            $nombre_completo = htmlspecialchars($alumno_datos['Nombre']." ".$alumno_datos['Primer_Apellido']." ".($alumno_datos['Segundo_Apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
            
            $suma_general = 0;
            $conteo_general = 0;
        ?>
            <tr>
                <td colspan="7" class="row-alumno-header" style="padding: 8px;">
                    &nbsp;Alumno: <?php echo $nombre_completo; ?> | Grupo: <?php echo $grupo; ?>
                </td>
            </tr>

            <tr style="height: 25px;">
                <th class="th-col" style="width: 120px;">Bimestre</th>
                <?php foreach ($campos_formativos as $cf): ?>
                    <th class="th-col" style="width: 140px;"><?php echo $cf; ?></th>
                <?php endforeach; ?>
                <th class="th-col" style="width: 130px;">Promedio Bimestral</th>
            </tr>

            <?php foreach ($bimestres_lista as $bim): 
                $notas_campos = array_fill_keys($campos_formativos, '-');
                
                // Consulta segura de calificaciones por bimestre mediante prepared statements
                $sql_calif = "SELECT cf.Nombre_Campo, c.Calificacion_Numerica 
                              FROM calificacion c
                              INNER JOIN campo_formativo cf ON c.ID_Campo = cf.ID_Campo
                              WHERE c.ID_Alumno = ? AND c.Bimestre = ?";
                              
                $stmt_calif = $conexion->prepare($sql_calif);
                if ($stmt_calif) {
                    $stmt_calif->bind_param("ii", $id_al, $bim);
                    $stmt_calif->execute();
                    $res_calif = $stmt_calif->get_result();
                    
                    $suma_notas = 0; $conteo_notas = 0;
                    if ($res_calif && $res_calif->num_rows > 0) {
                        while ($row = $res_calif->fetch_assoc()) {
                            $campo_name = trim($row['Nombre_Campo']);
                            $val_nota = floatval($row['Calificacion_Numerica']);
                            if (array_key_exists($campo_name, $notas_campos)) {
                                $notas_campos[$campo_name] = number_format($val_nota, 1);
                                $suma_notas += $val_nota; 
                                $conteo_notas++;
                                
                                $suma_general += $val_nota;
                                $conteo_general++;
                            }
                        }
                    }
                    $stmt_calif->close();
                }
                
                $promedio_bimestral = ($conteo_notas > 0) ? number_format($suma_notas / $conteo_notas, 1) : '-';
            ?>
                <tr style="height: 22px;">
                    <td class="td-bimestre">Bimestre <?php echo $bim; ?></td>
                    <?php foreach ($campos_formativos as $cf): ?>
                        <td class="td-data"><?php echo $notas_campos[$cf]; ?></td>
                    <?php endforeach; ?>
                    <td class="td-data" style="font-weight: bold;"><?php echo $promedio_bimestral; ?></td>
                </tr>
            <?php endforeach; ?>

            <tr style="height: 25px;">
                <td colspan="6" class="lbl-final">PROMEDIO FINAL DEL CICLO:&nbsp;</td>
                <td class="val-final">
                    <?php echo ($conteo_general > 0) ? number_format($suma_general / $conteo_general, 1) : '-'; ?>
                </td>
            </tr>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 15px; font-family: Arial; font-size: 11px;">
                    No se encontró la información del alumno activo.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>