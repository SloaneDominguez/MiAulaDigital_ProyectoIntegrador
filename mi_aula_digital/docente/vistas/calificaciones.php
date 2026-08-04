<?php
// calificaciones.php

// =======================================================
// PROCESAMIENTO DE ACCIONES (REGISTRAR, EDITAR)
// =======================================================

if (!isset($conexion)) {
    die("Error: No existe conexión con la base de datos.");
}

if (!isset($id_docente_sesion)) {
    die("Error: No se encontró el ID del docente en sesión.");
}

// 1. ACCIÓN: GUARDAR NUEVA CALIFICACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
    
    $id_alumno = intval($_POST['id_alumno'] ?? 0);
    $id_campo = intval($_POST['id_campo'] ?? 0);
    $nota = floatval($_POST['calificacion'] ?? 0);
    $bimestre = intval($_POST['bimestre'] ?? 1);

    if ($id_alumno > 0 && $id_campo > 0 && $nota >= 0 && $nota <= 10) {
        
        $stmt = $conexion->prepare("
            INSERT INTO calificacion 
            (id_campo, calificacion_numerica, bimestre, id_alumno, id_docente) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param("idiii", $id_campo, $nota, $bimestre, $id_alumno, $id_docente_sesion);
            
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?view=calificaciones&status=success_reg");
                exit();
            }
            $stmt->close();
        }
    }
    header("Location: dashboard.php?view=calificaciones&status=error_reg");
    exit();
}

// 2. ACCIÓN: EDITAR CALIFICACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    
    $id_calificacion = intval($_POST['id_calificacion'] ?? 0);
    $id_campo = intval($_POST['id_campo'] ?? 0);
    $nota = floatval($_POST['calificacion'] ?? 0);
    $bimestre = intval($_POST['bimestre'] ?? 1);

    if ($id_calificacion > 0 && $id_campo > 0 && $nota >= 0 && $nota <= 10) {
        
        $stmt = $conexion->prepare("
            UPDATE calificacion 
            SET id_campo = ?, calificacion_numerica = ?, bimestre = ? 
            WHERE id_calificacion = ? AND id_docente = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("idiii", $id_campo, $nota, $bimestre, $id_calificacion, $id_docente_sesion);
            
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?view=calificaciones&status=success_edit");
                exit();
            }
            $stmt->close();
        }
    }
    header("Location: dashboard.php?view=calificaciones&status=error_edit");
    exit();
}

// =======================================================
// CONSULTAS AJUSTADAS A TU BASE DE DATOS
// =======================================================

// Consulta para obtener el grupo asignado al Docente actual
$sql_grupo_docente = "
    SELECT g.id_grupo, g.grado, g.grupo 
    FROM grupo g 
    INNER JOIN docente d ON d.id_grupo_asignado = g.id_grupo 
    WHERE d.id_docente = ?
";
$stmt_g = $conexion->prepare($sql_grupo_docente);
if ($stmt_g) {
    $stmt_g->bind_param("i", $id_docente_sesion);
    $stmt_g->execute();
    $res_grupos = $stmt_g->get_result();
    $stmt_g->close();
} else {
    $res_grupos = null;
}

// Consulta para obtener el catálogo de Campos Formativos
$sql_campos = "SELECT id_campo, nombre_campo FROM campo_formativo ORDER BY id_campo ASC";
$res_campos = $conexion->query($sql_campos);

// Consulta para la tabla principal: Lista lineal incluyendo id_calificacion e id_campo para la edición
$sql_tabla_lineal = "
    SELECT 
        c.id_calificacion,
        c.id_alumno,
        c.id_campo,
        a.nombre, 
        a.primer_apellido, 
        a.segundo_apellido, 
        g.grado, 
        g.grupo,
        cf.nombre_campo,
        c.calificacion_numerica,
        c.bimestre
    FROM calificacion c
    INNER JOIN alumno a ON c.id_alumno = a.id_alumno
    INNER JOIN grupo g ON a.id_grupo = g.id_grupo
    INNER JOIN campo_formativo cf ON c.id_campo = cf.id_campo
    WHERE c.id_docente = ?
    ORDER BY c.bimestre ASC, g.grado, g.grupo, a.primer_apellido, a.nombre
";
$stmt_tab = $conexion->prepare($sql_tabla_lineal);
if ($stmt_tab) {
    $stmt_tab->bind_param("i", $id_docente_sesion);
    $stmt_tab->execute();
    $res_calificaciones = $stmt_tab->get_result();
    $stmt_tab->close();
} else {
    $res_calificaciones = null;
}

// Consulta para el selector de Alumnos incluyendo obligatoriamente id_grupo
$sql_todos_alumnos = "
    SELECT a.id_alumno, a.id_grupo, a.nombre, a.primer_apellido, a.segundo_apellido, g.grado, g.grupo
    FROM alumno a
    INNER JOIN docente d ON a.id_grupo = d.id_grupo_asignado
    INNER JOIN grupo g ON a.id_grupo = g.id_grupo
    WHERE d.id_docente = ?
    ORDER BY a.primer_apellido, a.nombre
";
$stmt_al = $conexion->prepare($sql_todos_alumnos);
if ($stmt_al) {
    $stmt_al->bind_param("i", $id_docente_sesion);
    $stmt_al->execute();
    $res_alumnos_modal = $stmt_al->get_result();
    $stmt_al->close();
} else {
    $res_alumnos_modal = null;
}
?>

<link rel="stylesheet" href="css/calificaciones.css">

<div class="calificaciones-container">
    
    <header class="calif-header">
        <div>
            <h1>Calificaciones</h1>
            <p class="subtitle">Gestión de calificaciones por Campos Formativos</p>
        </div>
        <div class="header-actions">
            <button class="btn-secondary" id="btnAbrirVisor">
                <i class="fa-regular fa-clipboard"></i> Ver resumen grupal
            </button>
            <button class="btn-primary" id="btnAbrirModal">
                <i class="fa-solid fa-plus"></i> Agregar calificación
            </button>
        </div>
    </header>

    <div class="filters-container">
        <select class="select-grupo-filter">
            <option value="">Grupo</option>
            <?php 
            if ($res_grupos && $res_grupos->num_rows > 0) {
                $res_grupos->data_seek(0);
                while ($g = $res_grupos->fetch_assoc()) {
                    echo '<option value="'.$g['grado'].$g['grupo'].'">'.$g['grado'].'°'.$g['grupo'].'</option>';
                }
            }
            ?>
        </select>
        
        <div class="bimestre-nav">
            <button class="btn-bimestre active" data-bimestre="1">Bim 1</button>
            <button class="btn-bimestre" data-bimestre="2">Bim 2</button>
            <button class="btn-bimestre" data-bimestre="3">Bim 3</button>
            <button class="btn-bimestre" data-bimestre="4">Bim 4</button>
            <button class="btn-bimestre" data-bimestre="5">Bim 5</button>
        </div>
    </div>

    <!-- MENSAJE DE ALERTA AUTOMÁTICO -->
    <?php if (isset($_GET['status'])): ?>
        <?php 
            $mensajes = [
                'success_reg'  => ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'Calificación guardada correctamente.'],
                'error_reg'    => ['bg' => '#fee2e2', 'color' => '#991b1b', 'txt' => 'Ocurrió un error al procesar el registro.'],
                'success_edit' => ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'Calificación actualizada correctamente.'],
                'error_edit'   => ['bg' => '#fee2e2', 'color' => '#991b1b', 'txt' => 'Ocurrió un error al actualizar la calificación.']
            ];
            $st = $_GET['status'];
            if (isset($mensajes[$st])):
        ?>
            <div id="alerta-mensaje" style="padding:15px; margin-bottom:20px; border-radius:8px; font-weight:500; background:<?= $mensajes[$st]['bg']; ?>; color:<?= $mensajes[$st]['color']; ?>;">
                <?= $mensajes[$st]['txt']; ?>
            </div>
        <?php endif; endif; ?>

    <div class="table-container">
        <table class="calif-table">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Grupo</th>
                    <th>Campo Formativo</th>
                    <th>Calificación</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_calificaciones && $res_calificaciones->num_rows > 0): ?>
                    <?php while ($row = $res_calificaciones->fetch_assoc()): 
                        // Determinar el id del grupo del alumno actual mediante los datos del modal de alumnos si fuera necesario, 
                        // o lo inferimos buscando en el row si se provee. Como el row ya trae grado y grupo, podemos guardar el texto.
                        // Para simplificar el botón de edición, pasaremos los datos vía atributos data-*
                        $nombre_completo = $row['nombre'] . ' ' . $row['primer_apellido'] . ' ' . $row['segundo_apellido'];
                        $nota_formateada = number_format($row['calificacion_numerica'], ($row['calificacion_numerica'] == intval($row['calificacion_numerica']) ? 0 : 1));
                    ?>
                        <tr class="fila-calificacion" data-bimestre="<?= $row['bimestre']; ?>" data-grupo-tabla="<?= $row['grado'] . $row['grupo']; ?>">
                            <td class="student-name"><?= htmlspecialchars($nombre_completo); ?></td>
                            <td>
                                <span class="badge-group"><?= htmlspecialchars($row['grado'] . $row['grupo']); ?></span>
                            </td>
                            <td class="campo-text"><?= htmlspecialchars($row['nombre_campo']); ?></td>
                            <td class="cell-nota"><?= $nota_formateada; ?></td>
                            <td style="text-align: center;">
                                <button class="btn-editar-calif" 
                                    style="background:#3b82f6; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;"
                                    data-id="<?= $row['id_calificacion']; ?>"
                                    data-alumno-nombre="<?= htmlspecialchars($nombre_completo, ENT_QUOTES); ?>"
                                    data-campo="<?= $row['id_campo']; ?>"
                                    data-bimestre="<?= $row['bimestre']; ?>"
                                    data-calificacion="<?= $row['calificacion_numerica']; ?>"
                                    title="Editar calificación">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">
                            No hay calificaciones registradas para este periodo.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL ÚNICO (REGISTRAR Y EDITAR) -->
<div class="modal-overlay" id="modalCalificacion">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTituloCalif">Agregar calificación</h2>
            <button type="button" class="close-x" id="btnXModal">&times;</button>
        </div>
        
        <form method="POST" action="dashboard.php?view=calificaciones" id="formCalificacionModal">
            <input type="hidden" name="accion" id="inputAccionCalif" value="registrar">
            <input type="hidden" name="id_calificacion" id="inputIdCalificacion" value="">

            <!-- Selección de Grupo (Solo para Nuevo Registro) -->
            <div class="form-group" id="contenedorGrupoRegistro">
                <label>Grupo</label>
                <select id="modal_select_grupo" required>
                    <option value="">Seleccionar grupo</option>
                    <?php 
                    if ($res_grupos && $res_grupos->num_rows > 0) {
                        $res_grupos->data_seek(0);
                        while ($g = $res_grupos->fetch_assoc()) {
                            echo '<option value="' . $g['id_grupo'] . '">' . htmlspecialchars($g['grado'] . '°' . $g['grupo']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Selección de Alumno (Select dinámico para Nuevo Registro) -->
            <div class="form-group" id="contenedorAlumnoRegistro">
                <label>Alumno</label>
                <select name="id_alumno" id="modal_select_alumno" required disabled>
                    <option value="">Selecciona primero un grupo</option>
                    <?php 
                    if ($res_alumnos_modal && $res_alumnos_modal->num_rows > 0) {
                        $res_alumnos_modal->data_seek(0);
                        while ($al = $res_alumnos_modal->fetch_assoc()) {
                            $nom = $al['nombre'] . ' ' . $al['primer_apellido'] . ' ' . $al['segundo_apellido'];
                            echo '<option value="' . $al['id_alumno'] . '" data-grupo="' . $al['id_grupo'] . '">' . htmlspecialchars($nom) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Campo de Texto Fijo para Alumno (Solo visible al Editar) -->
            <div class="form-group" id="contenedorAlumnoTexto" style="display: none;">
                <label>Alumno</label>
                <input type="text" id="inputAlumnoTextoFijo" readonly style="background: #f1f5f9; cursor: not-allowed;">
            </div>

            <div class="form-group">
                <label>Campo Formativo</label>
                <select name="id_campo" id="modal_select_campo" required>
                    <option value="">Seleccionar campo</option>
                    <?php 
                    if ($res_campos && $res_campos->num_rows > 0) {
                        $res_campos->data_seek(0);
                        while ($cp = $res_campos->fetch_assoc()) {
                            echo '<option value="' . $cp['id_campo'] . '">' . htmlspecialchars($cp['nombre_campo']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Bimestre</label>
                <select name="bimestre" id="modal_select_bimestre" required>
                    <option value="1" selected>Bimestre 1</option>
                    <option value="2">Bimestre 2</option>
                    <option value="3">Bimestre 3</option>
                    <option value="4">Bimestre 4</option>
                    <option value="5">Bimestre 5</option>
                </select>
            </div>

            <div class="form-group">
                <label>Calificación</label>
                <input type="number" name="calificacion" id="modal_input_calificacion" step="0.1" min="0" max="10" required placeholder="0.0">
            </div>

            <button type="submit" class="btn-submit-block" id="btnSubmitCalifText">Guardar calificación</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalVisor">
    <div class="modal-content visor-modal">
        <div class="modal-header">
            <h2 class="visor-title">
                <i class="fa-regular fa-clipboard" style="color: #b91c1c;"></i> 
                Calificaciones &mdash; Bimestre <span id="visorBimestreTxt">1</span> &bull; Todos los grupos
            </h2>
            <button type="button" class="close-x" id="btnCerrarVisor">&times;</button>
        </div>
        
        <table class="visor-table">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Alumno</th>
                    <th>Campos Formativos</th>
                    <th>Promedio</th>
                </tr>
            </thead>
            <tbody id="visorReporteCuerpo"></tbody>
        </table>
    </div>
</div>

<script>
    const todasLasCalificaciones = <?php 
        if($res_calificaciones && $res_calificaciones->num_rows > 0) {
            $res_calificaciones->data_seek(0);
            $rows = [];
            while($r = $res_calificaciones->fetch_assoc()) { $rows[] = $r; }
            echo json_encode($rows);
        } else { echo "[]"; }
    ?>;
</script>
<script src="js/calificaciones.js"></script>