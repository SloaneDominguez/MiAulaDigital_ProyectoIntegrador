-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Servidor: sql302.infinityfree.com
-- Tiempo de generación: 15-07-2026 a las 13:59:37
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `if0_42409630_miauladigital`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno`
--

CREATE TABLE `alumno` (
  `ID_Alumno` int(11) NOT NULL,
  `CURP` varchar(18) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `Primer_Apellido` varchar(50) NOT NULL,
  `Segundo_Apellido` varchar(50) DEFAULT NULL,
  `Fecha_Registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ID_Grupo` int(11) DEFAULT NULL,
  `ID_Padre` int(11) DEFAULT NULL,
  `ID_Token` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumno`
--

INSERT INTO `alumno` (`ID_Alumno`, `CURP`, `Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Fecha_Registro`, `ID_Grupo`, `ID_Padre`, `ID_Token`) VALUES
(1, 'HELA100101HDFRRN01', 'Luis Ángel', 'Hernández', 'López', '2026-07-15 01:38:42', 1, 1, 1),
(2, 'ROGM110202HDFRRL02', 'Carlos Eduardo', 'Rosales', 'García', '2026-07-15 01:38:42', 2, 2, 2),
(3, 'PELR120303HDFRZN03', 'Sofía', 'Pérez', 'Sánchez', '2026-07-15 01:38:42', 3, 3, 3),
(4, 'LOPG850615MNLRRS08', 'Evelin', 'Romero', 'Bautista', '2026-07-15 15:28:37', 1, 1, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aviso`
--

CREATE TABLE `aviso` (
  `ID_Aviso` int(11) NOT NULL,
  `Tipo_Aviso` enum('Institucional','Urgente') NOT NULL,
  `ID_Publicacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aviso`
--

INSERT INTO `aviso` (`ID_Aviso`, `Tipo_Aviso`, `ID_Publicacion`) VALUES
(1, 'Institucional', 1),
(3, 'Institucional', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificacion`
--

CREATE TABLE `calificacion` (
  `ID_Calificacion` int(11) NOT NULL,
  `ID_Campo` int(11) NOT NULL,
  `Bimestre` enum('1','2','3','4','5') NOT NULL,
  `Calificacion_Numerica` decimal(3,1) NOT NULL,
  `ID_Alumno` int(11) NOT NULL,
  `ID_Docente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificacion`
--

INSERT INTO `calificacion` (`ID_Calificacion`, `ID_Campo`, `Bimestre`, `Calificacion_Numerica`, `ID_Alumno`, `ID_Docente`) VALUES
(1, 1, '1', '10.0', 4, NULL),
(2, 1, '1', '10.0', 1, 4),
(3, 2, '1', '9.0', 4, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campo_formativo`
--

CREATE TABLE `campo_formativo` (
  `ID_Campo` int(11) NOT NULL,
  `Nombre_Campo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `campo_formativo`
--

INSERT INTO `campo_formativo` (`ID_Campo`, `Nombre_Campo`) VALUES
(4, 'De lo humano a lo comunitario'),
(3, 'Ética, Naturaleza y sociedades'),
(1, 'Lenguajes'),
(2, 'Saberes y pensamiento científico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `director`
--

CREATE TABLE `director` (
  `ID_Director` int(11) NOT NULL,
  `ID_Usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `director`
--

INSERT INTO `director` (`ID_Director`, `ID_Usuario`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docente`
--

CREATE TABLE `docente` (
  `ID_Docente` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `Primer_Apellido` varchar(50) NOT NULL,
  `Segundo_Apellido` varchar(50) DEFAULT NULL,
  `ID_Usuario` int(11) NOT NULL,
  `ID_Grupo_Asignado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docente`
--

INSERT INTO `docente` (`ID_Docente`, `Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `ID_Usuario`, `ID_Grupo_Asignado`) VALUES
(4, 'Ana Sofía', 'Martínez', 'López', 2, 1),
(5, 'Pedro', 'García', 'Ruiz', 3, 2),
(6, 'Laura', 'Hernández', 'Pérez', 4, 3),
(7, 'Adriana', 'Trejo', 'Palma', 8, 1),
(8, 'Brayan', 'Santos', 'Martinez', 9, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupo`
--

CREATE TABLE `grupo` (
  `ID_Grupo` int(11) NOT NULL,
  `Grado` enum('1','2','3','4','5','6') NOT NULL,
  `Grupo` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupo`
--

INSERT INTO `grupo` (`ID_Grupo`, `Grado`, `Grupo`) VALUES
(1, '1', 'A'),
(2, '2', 'A'),
(3, '3', 'A');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencia`
--

CREATE TABLE `incidencia` (
  `ID_Incidencia` int(11) NOT NULL,
  `Tipo_Incidencia` varchar(100) NOT NULL,
  `Descripcion` text NOT NULL,
  `Fecha` date NOT NULL,
  `Estado` enum('Pendiente','Resuelta') DEFAULT 'Pendiente',
  `ID_Alumno` int(11) NOT NULL,
  `ID_Docente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incidencia`
--

INSERT INTO `incidencia` (`ID_Incidencia`, `Tipo_Incidencia`, `Descripcion`, `Fecha`, `Estado`, `ID_Alumno`, `ID_Docente`) VALUES
(1, 'Llegada tarde', 'Llego tarde 30 minutos depues', '2026-07-01', 'Pendiente', 1, NULL),
(2, 'Conducta', 'Durante la clase del día de hoy, el alumno interrumpió en repetidas ocasiones la explicación del docente y conversó con otros compañeros, a pesar de haber recibido varios llamados de atención. Se le recordó el reglamento del salón y se le solicitó mantener una conducta adecuada para no afectar el desarrollo de la clase.', '2026-07-15', 'Pendiente', 4, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `padre_tutor`
--

CREATE TABLE `padre_tutor` (
  `ID_Padre` int(11) NOT NULL,
  `Nombre_Completo` varchar(150) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  `ID_Usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `padre_tutor`
--

INSERT INTO `padre_tutor` (`ID_Padre`, `Nombre_Completo`, `Correo`, `Telefono`, `ID_Usuario`) VALUES
(1, 'Juan Carlos Hernández', 'juan.padre@miauladigital.com', '7711234567', 5),
(2, 'María López García', 'maria.padre@miauladigital.com', '7712345678', 6),
(3, 'Roberto Pérez Sánchez', 'roberto.padre@miauladigital.com', '7713456789', 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicacion`
--

CREATE TABLE `publicacion` (
  `ID_Publicacion` int(11) NOT NULL,
  `Titulo` varchar(150) NOT NULL,
  `Contenido` text NOT NULL,
  `Fecha_Publicacion` date NOT NULL,
  `ID_Director` int(11) DEFAULT NULL,
  `ID_Docente` int(11) DEFAULT NULL,
  `ID_Grupo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `publicacion`
--

INSERT INTO `publicacion` (`ID_Publicacion`, `Titulo`, `Contenido`, `Fecha_Publicacion`, `ID_Director`, `ID_Docente`, `ID_Grupo`) VALUES
(1, 'Honores', 'El dia lunes 23 de Junio 2026', '2026-07-14', NULL, NULL, 1),
(3, 'Suspensión de clases por mantenimiento', 'Se informa a toda la comunidad escolar que el día viernes 17 de julio se suspenderán las actividades académicas debido a trabajos de mantenimiento en las instalaciones. Las clases se reanudarán de manera normal el lunes 20 de julio.', '2026-07-15', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_acceso`
--

CREATE TABLE `solicitud_acceso` (
  `ID_Solicitud` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Primer_Apellido` varchar(100) NOT NULL,
  `Segundo_Apellido` varchar(100) DEFAULT NULL,
  `Usuario` varchar(50) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `Rol` enum('Docente') DEFAULT 'Docente',
  `Estado` enum('Pendiente','Aprobada','Rechazada') DEFAULT 'Pendiente',
  `Fecha_Solicitud` datetime DEFAULT current_timestamp(),
  `Fecha_Revision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitud_acceso`
--

INSERT INTO `solicitud_acceso` (`ID_Solicitud`, `Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Usuario`, `Correo`, `Contrasena`, `Rol`, `Estado`, `Fecha_Solicitud`, `Fecha_Revision`) VALUES
(1, 'Brayan', 'Santos', 'Martinez', 'brayan1323', 'byanmartinezsantos@gmail.com', '$2y$10$EaOPOMx.iVUYons4ppbUOOZtbDROI392H7AXy49HRs0VGwsewcQQ2', 'Docente', 'Aprobada', '2026-07-14 19:23:02', '2026-07-15 09:58:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tokens`
--

CREATE TABLE `tokens` (
  `ID_Token` int(11) NOT NULL,
  `Token_String` varchar(500) NOT NULL,
  `Fecha_Inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `Fecha_Expiracion` datetime NOT NULL,
  `Dispositivo` varchar(100) DEFAULT NULL,
  `ID_Alumno` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tokens`
--

INSERT INTO `tokens` (`ID_Token`, `Token_String`, `Fecha_Inicio`, `Fecha_Expiracion`, `Dispositivo`, `ID_Alumno`) VALUES
(1, 'TOKEN-ALUMNO-001', '2026-07-15 01:40:20', '2027-12-31 23:59:59', 'Android', 1),
(2, 'TOKEN-ALUMNO-002', '2026-07-15 01:40:20', '2027-12-31 23:59:59', 'Android', 2),
(3, 'TOKEN-ALUMNO-003', '2026-07-15 01:40:20', '2027-12-31 23:59:59', 'Android', 3),
(4, '1-ERB-20260715-adb3', '2026-07-15 16:36:23', '2027-07-15 12:36:23', NULL, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `ID_Usuario` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `Usuario` varchar(50) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `Rol` enum('Direccion','Docente','PadreTutor') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`ID_Usuario`, `Nombre`, `Usuario`, `Correo`, `Contrasena`, `Rol`) VALUES
(1, 'Carlos Hernández López', 'direccion1', 'direccion1@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'Direccion'),
(2, 'Ana Sofía Martínez', 'docente1', 'ana.docente@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'Docente'),
(3, 'Pedro García Ruiz', 'docente2', 'pedro.docente@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'Docente'),
(4, 'Laura Hernández Pérez', 'docente3', 'laura.docente@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'Docente'),
(5, 'Juan Carlos Hernández', 'padre1', 'juan.padre@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'PadreTutor'),
(6, 'María López García', 'padre2', 'maria.padre@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'PadreTutor'),
(7, 'Roberto Pérez Sánchez', 'padre3', 'roberto.padre@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'PadreTutor'),
(8, 'Adriana Trejo Palma', 'adriana', 'adrianatrejo@gmail.com', '$2y$10$yTsdUB/Tr4K3lhCetcKKB.OhNy4PS09VEFzq/vvE2YYzuWZS21sVO', 'Docente'),
(9, 'Brayan', 'brayan1323', 'byanmartinezsantos@gmail.com', '$2y$10$EaOPOMx.iVUYons4ppbUOOZtbDROI392H7AXy49HRs0VGwsewcQQ2', 'Docente');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumno`
--
ALTER TABLE `alumno`
  ADD PRIMARY KEY (`ID_Alumno`),
  ADD UNIQUE KEY `CURP` (`CURP`),
  ADD KEY `fk_alumno_grupo` (`ID_Grupo`),
  ADD KEY `fk_alumno_padre` (`ID_Padre`),
  ADD KEY `fk_alumno_token` (`ID_Token`);

--
-- Indices de la tabla `aviso`
--
ALTER TABLE `aviso`
  ADD PRIMARY KEY (`ID_Aviso`),
  ADD UNIQUE KEY `ID_Publicacion` (`ID_Publicacion`);

--
-- Indices de la tabla `calificacion`
--
ALTER TABLE `calificacion`
  ADD PRIMARY KEY (`ID_Calificacion`),
  ADD UNIQUE KEY `ID_Alumno` (`ID_Alumno`,`ID_Campo`,`Bimestre`),
  ADD KEY `ID_Campo` (`ID_Campo`),
  ADD KEY `ID_Docente` (`ID_Docente`);

--
-- Indices de la tabla `campo_formativo`
--
ALTER TABLE `campo_formativo`
  ADD PRIMARY KEY (`ID_Campo`),
  ADD UNIQUE KEY `Nombre_Campo` (`Nombre_Campo`);

--
-- Indices de la tabla `director`
--
ALTER TABLE `director`
  ADD PRIMARY KEY (`ID_Director`),
  ADD UNIQUE KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indices de la tabla `docente`
--
ALTER TABLE `docente`
  ADD PRIMARY KEY (`ID_Docente`),
  ADD UNIQUE KEY `ID_Usuario` (`ID_Usuario`),
  ADD KEY `ID_Grupo_Asignado` (`ID_Grupo_Asignado`);

--
-- Indices de la tabla `grupo`
--
ALTER TABLE `grupo`
  ADD PRIMARY KEY (`ID_Grupo`);

--
-- Indices de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD PRIMARY KEY (`ID_Incidencia`),
  ADD KEY `ID_Alumno` (`ID_Alumno`),
  ADD KEY `ID_Docente` (`ID_Docente`);

--
-- Indices de la tabla `padre_tutor`
--
ALTER TABLE `padre_tutor`
  ADD PRIMARY KEY (`ID_Padre`),
  ADD UNIQUE KEY `Correo` (`Correo`),
  ADD UNIQUE KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indices de la tabla `publicacion`
--
ALTER TABLE `publicacion`
  ADD PRIMARY KEY (`ID_Publicacion`),
  ADD KEY `ID_Director` (`ID_Director`),
  ADD KEY `ID_Docente` (`ID_Docente`),
  ADD KEY `ID_Grupo` (`ID_Grupo`);

--
-- Indices de la tabla `solicitud_acceso`
--
ALTER TABLE `solicitud_acceso`
  ADD PRIMARY KEY (`ID_Solicitud`),
  ADD UNIQUE KEY `Usuario` (`Usuario`),
  ADD UNIQUE KEY `Correo` (`Correo`);

--
-- Indices de la tabla `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`ID_Token`),
  ADD KEY `fk_tokens_alumno` (`ID_Alumno`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_Usuario`),
  ADD UNIQUE KEY `Usuario` (`Usuario`),
  ADD UNIQUE KEY `Correo` (`Correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumno`
--
ALTER TABLE `alumno`
  MODIFY `ID_Alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `aviso`
--
ALTER TABLE `aviso`
  MODIFY `ID_Aviso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `calificacion`
--
ALTER TABLE `calificacion`
  MODIFY `ID_Calificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `campo_formativo`
--
ALTER TABLE `campo_formativo`
  MODIFY `ID_Campo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `director`
--
ALTER TABLE `director`
  MODIFY `ID_Director` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `docente`
--
ALTER TABLE `docente`
  MODIFY `ID_Docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `grupo`
--
ALTER TABLE `grupo`
  MODIFY `ID_Grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `incidencia`
--
ALTER TABLE `incidencia`
  MODIFY `ID_Incidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `padre_tutor`
--
ALTER TABLE `padre_tutor`
  MODIFY `ID_Padre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `publicacion`
--
ALTER TABLE `publicacion`
  MODIFY `ID_Publicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `solicitud_acceso`
--
ALTER TABLE `solicitud_acceso`
  MODIFY `ID_Solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tokens`
--
ALTER TABLE `tokens`
  MODIFY `ID_Token` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_Usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumno`
--
ALTER TABLE `alumno`
  ADD CONSTRAINT `alumno_ibfk_1` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL,
  ADD CONSTRAINT `alumno_ibfk_2` FOREIGN KEY (`ID_Padre`) REFERENCES `padre_tutor` (`ID_Padre`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alumno_grupo` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alumno_padre` FOREIGN KEY (`ID_Padre`) REFERENCES `padre_tutor` (`ID_Padre`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alumno_token` FOREIGN KEY (`ID_Token`) REFERENCES `tokens` (`ID_Token`) ON DELETE SET NULL;

--
-- Filtros para la tabla `aviso`
--
ALTER TABLE `aviso`
  ADD CONSTRAINT `aviso_ibfk_1` FOREIGN KEY (`ID_Publicacion`) REFERENCES `publicacion` (`ID_Publicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificacion`
--
ALTER TABLE `calificacion`
  ADD CONSTRAINT `calificacion_ibfk_1` FOREIGN KEY (`ID_Campo`) REFERENCES `campo_formativo` (`ID_Campo`),
  ADD CONSTRAINT `calificacion_ibfk_2` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificacion_ibfk_3` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL;

--
-- Filtros para la tabla `director`
--
ALTER TABLE `director`
  ADD CONSTRAINT `director_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `docente`
--
ALTER TABLE `docente`
  ADD CONSTRAINT `docente_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `docente_ibfk_2` FOREIGN KEY (`ID_Grupo_Asignado`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL;

--
-- Filtros para la tabla `incidencia`
--
ALTER TABLE `incidencia`
  ADD CONSTRAINT `incidencia_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidencia_ibfk_2` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL;

--
-- Filtros para la tabla `padre_tutor`
--
ALTER TABLE `padre_tutor`
  ADD CONSTRAINT `padre_tutor_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicacion`
--
ALTER TABLE `publicacion`
  ADD CONSTRAINT `publicacion_ibfk_1` FOREIGN KEY (`ID_Director`) REFERENCES `director` (`ID_Director`) ON DELETE SET NULL,
  ADD CONSTRAINT `publicacion_ibfk_2` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL,
  ADD CONSTRAINT `publicacion_ibfk_3` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `fk_tokens_alumno` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

