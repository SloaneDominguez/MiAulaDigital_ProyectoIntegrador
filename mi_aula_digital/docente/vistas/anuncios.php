<?php
// anuncios.php

// =======================================================
// CONTROL DE SEGURIDAD Y CONFIGURACIÓN INICIAL
// =======================================================
if (!isset($conexion)) {
    die("Error: No existe conexión con la base de datos.");
}

if (!isset($id_docente_sesion)) {
    die("Error: No se encontró el ID del docente en sesión.");
}

// =======================================================
// ACCIÓN 1: CREAR NUEVA PUBLICACIÓN / AVISO
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {

    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $grupo = !empty($_POST['grupo']) ? intval($_POST['grupo']) : null;
    $tipo_aviso = trim($_POST['tipo_aviso'] ?? '');
    $fecha_actual = date('Y-m-d');

    if (!empty($titulo) && !empty($contenido)) {
        
        $conexion->begin_transaction();

        try {
            $stmt = $conexion->prepare("
                INSERT INTO publicacion 
                (titulo, contenido, fecha_publicacion, id_docente, id_grupo) 
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de publicación.");
            }
            $stmt->bind_param("sssii", $titulo, $contenido, $fecha_actual, $id_docente_sesion, $grupo);
            $stmt->execute();
            
            $id_publicacion = $conexion->insert_id;
            $stmt->close();

            if (!empty($tipo_aviso)) {
                $stmt_aviso = $conexion->prepare("
                    INSERT INTO aviso (tipo_aviso, id_publicacion) 
                    VALUES (?, ?)
                ");
                if ($stmt_aviso) {
                    $stmt_aviso->bind_param("si", $tipo_aviso, $id_publicacion);
                    $stmt_aviso->execute();
                    $stmt_aviso->close();
                }
            }

            $conexion->commit();
            header("Location: dashboard.php?view=anuncios&status=success_create");
            exit();

        } catch (Exception $e) {
            $conexion->rollback();
            header("Location: dashboard.php?view=anuncios&status=error");
            exit();
        }
    }
    header("Location: dashboard.php?view=anuncios&status=error");
    exit();
}

// =======================================================
// ACCIÓN 2: EDITAR PUBLICACIÓN EXISTENTE
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {

    $id_publicacion = intval($_POST['id_publicacion'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = trim($_POST['contenido'] ?? '');
    $grupo = !empty($_POST['grupo']) ? intval($_POST['grupo']) : null;
    $tipo_aviso = trim($_POST['tipo_aviso'] ?? '');

    if ($id_publicacion > 0 && !empty($titulo) && !empty($contenido)) {
        
        $conexion->begin_transaction();

        try {
            // Validar que pertenezca al docente en sesión
            $stmt = $conexion->prepare("
                UPDATE publicacion 
                SET titulo = ?, contenido = ?, id_grupo = ? 
                WHERE id_publicacion = ? AND id_docente = ?
            ");
            if (!$stmt) {
                throw new Exception("Error al preparar la actualización.");
            }
            $stmt->bind_param("ssiii", $titulo, $contenido, $grupo, $id_publicacion, $id_docente_sesion);
            $stmt->execute();
            $stmt->close();

            // Actualizar o insertar el tipo de aviso
            $stmt_check = $conexion->prepare("SELECT id_aviso FROM aviso WHERE id_publicacion = ?");
            $stmt_check->bind_param("i", $id_publicacion);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            $stmt_check->close();

            if (!empty($tipo_aviso)) {
                if ($res_check->num_rows > 0) {
                    $stmt_aviso = $conexion->prepare("UPDATE aviso SET tipo_aviso = ? WHERE id_publicacion = ?");
                    $stmt_aviso->bind_param("si", $tipo_aviso, $id_publicacion);
                } else {
                    $stmt_aviso = $conexion->prepare("INSERT INTO aviso (tipo_aviso, id_publicacion) VALUES (?, ?)");
                    $stmt_aviso->bind_param("si", $tipo_aviso, $id_publicacion);
                }
                $stmt_aviso->execute();
                $stmt_aviso->close();
            } else {
                // Si seleccionó "Ninguno", eliminamos el registro en aviso si existía
                $stmt_del_aviso = $conexion->prepare("DELETE FROM aviso WHERE id_publicacion = ?");
                $stmt_del_aviso->bind_param("i", $id_publicacion);
                $stmt_del_aviso->execute();
                $stmt_del_aviso->close();
            }

            $conexion->commit();
            header("Location: dashboard.php?view=anuncios&status=success_edit");
            exit();

        } catch (Exception $e) {
            $conexion->rollback();
            header("Location: dashboard.php?view=anuncios&status=error");
            exit();
        }
    }
    header("Location: dashboard.php?view=anuncios&status=error");
    exit();
}

// =======================================================
// ACCIÓN 3: ELIMINAR PUBLICACIÓN EXISTENTE
// =======================================================
if (isset($_GET['delete_id'])) {
    $id_publicacion = intval($_GET['delete_id']);

    if ($id_publicacion > 0) {
        $stmt = $conexion->prepare("
            DELETE FROM publicacion 
            WHERE id_publicacion = ? 
            AND id_docente = ?
        ");
        if ($stmt) {
            $stmt->bind_param("ii", $id_publicacion, $id_docente_sesion);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?view=anuncios&status=deleted");
                exit();
            }
            $stmt->close();
        }
    }
    header("Location: dashboard.php?view=anuncios&status=error");
    exit();
}

// =======================================================
// CONSULTAS DE EXTRACCIÓN DE DATOS (READ)
// =======================================================
$stmt_anuncios = $conexion->prepare("
    SELECT 
        p.id_publicacion, 
        p.titulo, 
        p.contenido, 
        p.fecha_publicacion, 
        p.id_grupo,
        p.id_docente,
        a.tipo_aviso
    FROM publicacion p
    LEFT JOIN aviso a ON p.id_publicacion = a.id_publicacion
    WHERE p.id_docente = ? 
       OR p.id_docente IS NULL
    ORDER BY p.id_publicacion DESC
");

if ($stmt_anuncios) {
    $stmt_anuncios->bind_param("i", $id_docente_sesion);
    $stmt_anuncios->execute();
    $res_mis_anuncios = $stmt_anuncios->get_result();
    $stmt_anuncios->close();
} else {
    $res_mis_anuncios = null;
}

$sql_grupos = "SELECT id_grupo, grado, grupo FROM grupo ORDER BY grado, grupo";
$res_grupos = $conexion->query($sql_grupos);
?>

<link rel="stylesheet" href="css/anuncios.css">

<div class="anuncios-container">

    <header class="anuncios-header">
        <div>
            <h1>Anuncios</h1>
            <p class="subtitle">Comunicados y tareas escolares para los grupos</p>
        </div>
        <button class="btn-nueva-tarea" id="btnAbrirModal">
            <i class="fa-solid fa-plus"></i> Nueva publicación
        </button>
    </header>

    <!-- MENSAJE DE ALERTA AUTOMÁTICO -->
    <?php if (isset($_GET['status'])): ?>
        <?php 
            $mensajes = [
                'success_create' => ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'La publicación fue creada de manera exitosa.'],
                'success_edit'   => ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'La publicación ha sido actualizada correctamente.'],
                'deleted'        => ['bg' => '#dcfce7', 'color' => '#166534', 'txt' => 'La publicación ha sido removida del sistema.'],
                'error'          => ['bg' => '#fee2e2', 'color' => '#991b1b', 'txt' => 'Ocurrió un error inesperado al procesar la solicitud.']
            ];
            $st = $_GET['status'];
            if (isset($mensajes[$st])):
        ?>
            <div id="alerta-mensaje" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; background-color: <?= $mensajes[$st]['bg']; ?>; color: <?= $mensajes[$st]['color']; ?>;">
                <?= $mensajes[$st]['txt']; ?>
            </div>
        <?php endif; endif; ?>

    <div class="anuncios-grid">
        <?php if ($res_mis_anuncios && $res_mis_anuncios->num_rows > 0): ?>
            <?php while ($anuncio = $res_mis_anuncios->fetch_assoc()): ?>
                <div class="anuncio-card">
                    <div>
                        <div class="card-header">
                            <?php if (is_null($anuncio['id_grupo'])): ?>
                                <span class="badge-institucional">Institucional</span>
                            <?php else: ?>
                                <span class="badge-tarea">Tarea</span>
                            <?php endif; ?>

                            <!-- Botones de acciones (Solo si la publicación pertenece al docente en sesión) -->
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <?php if (!is_null($anuncio['id_docente']) && intval($anuncio['id_docente']) === intval($id_docente_sesion)): ?>
                                    <button class="btn-editar-anuncio" 
                                        style="background: none; border: none; cursor: pointer; color: #3b82f6; font-size: 15px; padding: 0;"
                                        data-id="<?= $anuncio['id_publicacion']; ?>"
                                        data-titulo="<?= htmlspecialchars($anuncio['titulo'], ENT_QUOTES); ?>"
                                        data-contenido="<?= htmlspecialchars($anuncio['contenido'], ENT_QUOTES); ?>"
                                        data-grupo="<?= $anuncio['id_grupo']; ?>"
                                        data-tipo="<?= htmlspecialchars($anuncio['tipo_aviso'] ?? ''); ?>"
                                        title="Editar publicación">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                <?php endif; ?>

                                <a class="btn-delete-anuncio" 
                                   href="dashboard.php?view=anuncios&delete_id=<?= $anuncio['id_publicacion']; ?>" 
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar permanentemente esta publicación?')"
                                   title="Eliminar publicación">
                                   <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </div>

                        <h3><?= htmlspecialchars($anuncio['titulo']); ?></h3>
                        <p><?= nl2br(htmlspecialchars($anuncio['contenido'])); ?></p>
                    </div>

                    <div class="card-date">
                        <i class="fa-regular fa-calendar" style="margin-right: 4px;"></i> 
                        <?= date('d/m/Y', strtotime($anuncio['fecha_publicacion'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-bullhorn" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                No se han registrado publicaciones hasta el momento.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL ÚNICO PARA CREAR Y EDITAR -->
<div class="modal-overlay" id="modalAnuncio">
    <div class="modal-content">
        <h2 id="modalTituloAnuncio">Nueva Publicación</h2>
        
        <form method="POST" action="dashboard.php?view=anuncios" id="formAnuncio">
            <input type="hidden" name="accion" id="inputAccionAnuncio" value="crear">
            <input type="hidden" name="id_publicacion" id="inputIdPublicacion" value="">

            <div class="form-group">
                <label for="titulo">Título de la publicación</label>
                <input type="text" id="titulo" name="titulo" placeholder="Ej. Tarea de Matemáticas, Aviso urgente..." required>
            </div>

            <div class="form-group">
                <label for="contenido">Contenido / Descripción</label>
                <textarea id="contenido" name="contenido" placeholder="Escribe el cuerpo del mensaje aquí..." required></textarea>
            </div>

            <div class="form-group">
                <label for="grupo">Grupo dirigido</label>
                <select id="grupo" name="grupo">
                    <option value="">General (Todos los grupos / Institucional)</option>
                    <?php if ($res_grupos && $res_grupos->num_rows > 0): ?>
                        <?php while ($g = $res_grupos->fetch_assoc()): ?>
                            <option value="<?= $g['id_grupo']; ?>">
                                <?= htmlspecialchars($g['grado'] . '° ' . $g['grupo']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tipo_aviso">Tipo de etiqueta de aviso</label>
                <select id="tipo_aviso" name="tipo_aviso">
                    <option value="">Ninguno (Por defecto)</option>
                    <option value="Institucional">Institucional</option>
                    <option value="Urgente">Urgente</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancelar" id="btnCancelarModal">Cancelar</button>
                <button type="submit" class="btn-guardar" id="btnSubmitAnuncioText">Publicar anuncio</button>
            </div>
        </form>
    </div>
</div>

<script src="js/anuncios.js"></script>