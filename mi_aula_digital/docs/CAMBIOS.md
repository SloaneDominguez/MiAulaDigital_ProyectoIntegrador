# Registro de cambios — Mi Aula Digital

## 2026-11-09 · Diseño unificado (V7)

Capa de componentes en `assets/theme.php` (bloque **V7**), que usa siempre los colores de Personalización.

- **Superficies limpias**: tablas, botones secundarios, campos y recuadros internos dejan el relleno gris; ahora son superficie del tema con borde fino.
- **Tablas**: encabezado suave, filas con resaltado al pasar el cursor y separación clara.
- **Tarjetas (celular y columnas angostas)**: cada registro es una tarjeta separada con sombra; etiqueta y valor alineados en la misma línea; botones de acción del mismo ancho.
- **"Ver expediente"**: dentro del bloque del alumno, alineado con el nombre y con flecha (`person($a, $link)` en `app/ui.php`).
- **Botones**: 3 tamaños (normal 40 px, `small` 34 px, `big` 48 px). En listas, las acciones con tono (Eliminar, Dar de baja, Rechazar, Aprobar…) son suaves y se llenan de color al pasar el cursor; el color sólido queda para la acción principal.
- **Notificaciones**: las acciones de tablas ya no meten el error dentro de la celda; se muestra una notificación flotante con icono, tono (éxito/error) y botón para cerrar.
- **Mensajes de formulario**: con icono y colores de éxito/error del tema.
- **"Nuevo ciclo escolar"**: la nota "primero cierra el ciclo actual" va debajo del botón, con icono de candado.
- **Mi cuenta**: "Cambiar contraseña" y "Volver al panel" con el mismo ancho y alto.
- **Anuncios**: confirmar, lecturas, editar y eliminar en una sola fila.

## 2026-11-09 · Verificación de correo en el registro

Código de 6 dígitos para familias y docentes, límites contra registros masivos, recuperación de contraseña por correo y aviso al docente cuando Dirección revisa su solicitud. Configuración en `instalacion/CONFIGURAR_CORREO.md`.

## Respaldos

Los respaldos están en `_respaldos/` (bloqueado al público con `.htaccess`). **No lo subas al hosting.**

| Carpeta | Contenido |
|---|---|
| `2026-11-07_base` | Versión antes de las correcciones generales |
| `2026-11-08_logo` | Antes del cambio del logo predeterminado |
| `2026-11-09_correo` | Antes de la verificación de correo |
| `2026-11-09_diseno` | Antes del diseño unificado (V7) |
