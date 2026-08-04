<?php
// grupos.php
$id_docente_sesion = isset($id_docente_sesion) ? $id_docente_sesion : 1;

$grupos_docente = []; // Array para almacenar los grupos únicos dinámicamente

if (isset($conexion)) {
    $sql_tabla_grupos = "SELECT a.id_alumno, a.nombre, a.primer_apellido, a.segundo_apellido, a.curp, g.grado, g.grupo 
                        FROM alumno a 
                        INNER JOIN docente d ON a.id_grupo = d.id_grupo_asignado 
                        INNER JOIN grupo g ON a.id_grupo = g.id_grupo
                        WHERE d.id_docente = $id_docente_sesion 
                        ORDER BY a.nombre ASC"; 
    $res_tabla_grupos = $conexion->query($sql_tabla_grupos);
}
?>

<link rel="stylesheet" href="css/grupos.css">

<div class="grupos-container">
    <header class="content-header">
        <h1>Mis grupos</h1>
        <p class="subtitle">Alumnos asignados a tus grupos</p>
    </header>

    <select class="group-selector">
        <?php
        // Guardamos los datos en un array temporal para poder leer los grupos antes de renderizar la tabla
        $alumnos_array = [];
        if (isset($res_tabla_grupos) && $res_tabla_grupos->num_rows > 0) {
            while ($row = $res_tabla_grupos->fetch_assoc()) {
                $alumnos_array[] = $row;
                $nombre_grupo = $row['grado'] . $row['grupo'];
                if (!in_array($nombre_grupo, $grupos_docente)) {
                    $grupos_docente[] = $nombre_grupo;
                }
            }
        }
        
        // Renderizar las opciones del selector
        if (!empty($grupos_docente)) {
            foreach ($grupos_docente as $g) {
                echo '<option value="' . htmlspecialchars($g) . '">' . htmlspecialchars($g) . '</option>';
            }
        } else {
            echo '<option value="">Sin grupos</option>';
        }
        ?>
    </select>

    <div class="section-box">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Nombre</th>
                    <th style="width: 35%;">CURP</th>
                    <th style="width: 15%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($alumnos_array)): 
                    $consecutivo = 1; 
                    foreach ($alumnos_array as $alumno): 
                        $grupo_alumno = $alumno['grado'] . $alumno['grupo'];
                ?>
                        <tr data-grupo="<?= htmlspecialchars($grupo_alumno); ?>">
                            <td class="student-id"><?php echo $consecutivo++; ?></td>
                            <td class="student-name">
                                <?php echo htmlspecialchars($alumno['nombre'] . " " . $alumno['primer_apellido'] . " " . $alumno['segundo_apellido']); ?>
                            </td>
                            <td class="curp-text">
                                <?php echo htmlspecialchars($alumno['curp'] ?? 'TOMD160918HDFRRG03'); ?>
                            </td>
                            <td>
                                <span class="status-badge">Activo</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 30px; text-align: center; color: #94a3b8;">
                            No tienes alumnos asignados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="js/grupos.js"></script>