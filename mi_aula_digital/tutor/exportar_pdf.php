<?php
// tutor/exportar_pdf.php
include('../config/conexion.php'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_padre_sesion = $_SESSION['id_padre'] ?? null;
$id_alumno_sesion = $_SESSION['id_alumno'] ?? null;

$id_alumno_activo = null;

if ($id_alumno_sesion && $id_padre_sesion) {
    // Validar estrictamente que el alumno pertenezca al tutor en sesión
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

// Definimos los bimestres que queremos mostrar (del 1 al 5)
$bimestres_lista = [1, 2, 3, 4, 5];

$campos_formativos = [
    'Lenguajes', 
    'Saberes y pensamiento científico', 
    'Ética, Naturaleza y sociedades', 
    'De lo humano a lo comunitario'
];

$alumno_datos = null;

if (isset($conexion)) {
    // Consulta segura dirigida a un solo alumno mediante prepared statements
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Descargando Historial de Calificaciones...</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: #f8fafc; 
            margin: 0; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .loader-container {
            text-align: center;
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
        }
        .spinner {
            width: 50px; height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #b61a1a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .status-text { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 8px;}
        .sub-text { font-size: 14px; color: #64748b; }

        /* Contenedor PDF oculto */
        .pdf-wrapper { position: absolute; left: -9999px; top: -9999px; overflow: hidden; }
        #boleta-pdf { background: #ffffff; padding: 20mm; width: 297mm; box-sizing: border-box; }
        
        .header-container { width: 100%; margin-bottom: 25px; border-bottom: 3px solid #b61a1a; padding-bottom: 15px; display: table; }
        .logo-box { display: table-cell; width: 90px; vertical-align: middle; }
        .title-box { display: table-cell; vertical-align: middle; padding-left: 20px; }
        .logo { width: 85px; height: auto; display: block; }
        h1 { margin: 0; color: #111827; font-size: 26px; font-weight: bold; }
        p { margin: 6px 0 0 0; color: #4b5563; font-size: 14px; }
        
        .alumno-seccion { margin-bottom: 40px; page-break-inside: avoid; }
        .alumno-title { background: #1e293b; color: white; padding: 10px 15px; font-size: 16px; font-weight: bold; border-radius: 6px 6px 0 0; margin: 0; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 10px; font-size: 13px; }
        th { background-color: #f8fafc; color: #334155; font-weight: 700; text-align: center; }
        
        .text-center { text-align: center; }
        .promedio-final { font-weight: bold; color: #16a34a; font-size: 15px; background-color: #f0fdf4; }
    </style>
</head>
<body>

    <div class="loader-container">
        <div class="spinner" id="loader-icon"></div>
        <div class="status-text" id="status-title">Generando reporte de calificaciones</div>
        <div class="sub-text" id="status-desc">Procesando todos los bimestres...</div>
    </div>

    <div class="pdf-wrapper">
        <div id="boleta-pdf">
            <div class="header-container">
                <div class="logo-box">
                    <img src="../assets/img/escudo.jpeg" class="logo" alt="Escudo Primaria">
                </div>
                <div class="title-box">
                    <h1>Concentrado Histórico de Calificaciones</h1>
                    <p><strong>Ciclo Escolar Activo</strong> | <strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y'); ?></p>
                </div>
            </div>

            <?php if ($alumno_datos): 
                $id_al = $alumno_datos['ID_Alumno'];
                $grupo = ($alumno_datos['Grado'] && $alumno_datos['Grupo']) ? $alumno_datos['Grado'] . "°" . $alumno_datos['Grupo'] : 'S/G';
                $suma_general = 0;
                $conteo_general = 0;
            ?>
                <div class="alumno-seccion">
                    <div class="alumno-title">
                        Alumno: <?php echo htmlspecialchars($alumno_datos['Nombre']." ".$alumno_datos['Primer_Apellido']." ".($alumno_datos['Segundo_Apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> | Grupo: <?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 20%;">Bimestre</th>
                                <?php foreach ($campos_formativos as $cf): ?>
                                    <th><?php echo $cf; ?></th>
                                <?php endforeach; ?>
                                <th style="width: 15%;">Promedio Bimestral</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bimestres_lista as $bim): 
                                $notas_campos = array_fill_keys($campos_formativos, '-');
                                
                                // Buscamos las calificaciones mediante prepared statements
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
                                <tr>
                                    <td class="text-center"><strong>Bimestre <?php echo $bim; ?></strong></td>
                                    <?php foreach ($campos_formativos as $cf): ?>
                                        <td class="text-center"><?php echo $notas_campos[$cf]; ?></td>
                                    <?php endforeach; ?>
                                    <td class="text-center" style="font-weight: bold;"><?php echo $promedio_bimestral; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="5" style="text-align: right; font-weight: bold; background: #f8fafc;">PROMEDIO FINAL DEL CICLO:</td>
                                <td class="text-center promedio-final">
                                    <?php echo ($conteo_general > 0) ? number_format($suma_general / $conteo_general, 1) : '-'; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding: 20px; color: #64748b;">No se pudo encontrar la información del alumno.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            const elemento = document.getElementById('boleta-pdf');
            const opciones = {
                margin:       0,
                filename:     'Boleta_Calificaciones.pdf',
                image:        { type: 'jpeg', quality: 1.0 },
                html2canvas:  { scale: 3, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            html2pdf().set(opciones).from(elemento).save().then(() => {
                document.getElementById('loader-icon').style.display = "none";
                document.getElementById('status-title').innerText = "¡Descarga Completada!";
                document.getElementById('status-title').style.color = "#16a34a";
                document.getElementById('status-desc').innerText = "Esta ventana se cerrará automáticamente.";
                setTimeout(() => { window.close(); }, 1500);
            }).catch(err => {
                console.error(err);
                document.getElementById('status-title').innerText = "Hubo un error al generar el PDF";
            });
        }
    </script>
</body>
</html>