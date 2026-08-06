-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql302.infinityfree.com
-- Generation Time: Aug 05, 2026 at 08:37 PM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_42409630_miauladigital`
--

-- --------------------------------------------------------

--
-- Table structure for table `alumno`
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
-- Dumping data for table `alumno`
--

INSERT INTO `alumno` (`ID_Alumno`, `CURP`, `Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Fecha_Registro`, `ID_Grupo`, `ID_Padre`, `ID_Token`) VALUES
(1, 'HELA100101HDFRRN01', 'Luis Ángel', 'Hernández', 'López', '2026-07-15 01:38:42', 1, 1, 1),
(2, 'ROGM110202HDFRRL02', 'Carlos Eduardo', 'Rosales', 'García', '2026-07-15 01:38:42', 2, 2, 2),
(3, 'PELR120303HDFRZN03', 'Sofía', 'Pérez', 'Sánchez', '2026-07-15 01:38:42', 3, 3, 3),
(4, 'LOPG850615MNLRRS08', 'Evelin', 'Romero', 'Bautista', '2026-07-15 15:28:37', 1, 1, 6),
(6, 'GALC041724HHGRRNA9', 'Carlos', 'Garcia', 'Lopez', '2026-07-22 05:19:43', 1, 7, 9),
(7, 'MAGN150325HDFRRM04', 'Noé', 'Maqueda', 'González', '2026-07-22 17:00:00', 1, 8, 8),
(9, 'GAJD041707HHGRRNA9', 'Daniel ', 'Garcia ', 'Jeronimo', '2026-07-24 17:03:48', 5, NULL, 10),
(10, 'GARC200101HDFRRM10', 'Mateo', 'García', 'Ruiz', '2026-07-24 19:00:00', 1, 8, 11),
(11, 'MART200202HDFRRM20', 'Valeria', 'Martínez', 'Cruz', '2026-07-24 19:00:00', 2, 4, 12),
(12, 'SANC200303HDFRRM30', 'Diego', 'Sánchez', 'Morales', '2026-07-24 19:00:00', 3, NULL, 13),
(13, 'ROML140325HDFRRM88', 'Leonardo', 'Romero', 'López', '2026-07-22 19:00:00', 1, 8, 14);

-- --------------------------------------------------------

--
-- Table structure for table `aviso`
--

CREATE TABLE `aviso` (
  `ID_Aviso` int(11) NOT NULL,
  `Tipo_Aviso` enum('Institucional','Urgente') NOT NULL,
  `ID_Publicacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aviso`
--

INSERT INTO `aviso` (`ID_Aviso`, `Tipo_Aviso`, `ID_Publicacion`) VALUES
(3, 'Institucional', 3),
(9, 'Institucional', 10),
(11, 'Urgente', 12);

-- --------------------------------------------------------

--
-- Table structure for table `calificacion`
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
-- Dumping data for table `calificacion`
--

INSERT INTO `calificacion` (`ID_Calificacion`, `ID_Campo`, `Bimestre`, `Calificacion_Numerica`, `ID_Alumno`, `ID_Docente`) VALUES
(1, 1, '1', '10.0', 4, NULL),
(2, 1, '1', '10.0', 1, NULL),
(3, 2, '1', '9.0', 4, NULL),
(5, 3, '1', '10.0', 2, 9),
(6, 1, '1', '9.0', 4, NULL),
(10, 1, '1', '10.0', 6, 9),
(11, 1, '1', '9.5', 7, 8);

-- --------------------------------------------------------

--
-- Table structure for table `campo_formativo`
--

CREATE TABLE `campo_formativo` (
  `ID_Campo` int(11) NOT NULL,
  `Nombre_Campo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campo_formativo`
--

INSERT INTO `campo_formativo` (`ID_Campo`, `Nombre_Campo`) VALUES
(4, 'De lo humano a lo comunitario'),
(3, 'Ética, Naturaleza y sociedades'),
(1, 'Lenguajes'),
(2, 'Saberes y pensamiento científico');

-- --------------------------------------------------------

--
-- Table structure for table `director`
--

CREATE TABLE `director` (
  `ID_Director` int(11) NOT NULL,
  `ID_Usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `director`
--

INSERT INTO `director` (`ID_Director`, `ID_Usuario`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `docente`
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
-- Dumping data for table `docente`
--

INSERT INTO `docente` (`ID_Docente`, `Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `ID_Usuario`, `ID_Grupo_Asignado`) VALUES
(6, 'Laura', 'Hernández', 'Pérez', 4, 2),
(8, 'Brayan', 'Santos', 'Martinez', 9, 3),
(9, 'Daniel', 'perez', 'Jeronimo', 15, 1),
(12, 'Maria', 'Gomez', 'Hernadez', 22, 5),
(13, 'JuanP', 'Morales', 'Mails', 23, 5);

-- --------------------------------------------------------

--
-- Table structure for table `grupo`
--

CREATE TABLE `grupo` (
  `ID_Grupo` int(11) NOT NULL,
  `Grado` enum('1','2','3','4','5','6') NOT NULL,
  `Grupo` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grupo`
--

INSERT INTO `grupo` (`ID_Grupo`, `Grado`, `Grupo`) VALUES
(1, '1', 'A'),
(2, '2', 'A'),
(3, '3', 'A'),
(5, '4', 'B');

-- --------------------------------------------------------

--
-- Table structure for table `incidencia`
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
-- Dumping data for table `incidencia`
--

INSERT INTO `incidencia` (`ID_Incidencia`, `Tipo_Incidencia`, `Descripcion`, `Fecha`, `Estado`, `ID_Alumno`, `ID_Docente`) VALUES
(1, 'Llegada tarde', 'Llego tarde 30 minutos depues', '2026-07-01', 'Pendiente', 1, NULL),
(2, 'Conducta inapropiada', 'Durante la clase del día de hoy, el alumno interrumpió en repetidas ocasiones la explicación del docente y conversó con otros compañeros, a pesar de haber recibido varios llamados de atención. Se le recordó el reglamento del salón y se le solicitó mantener una conducta adecuada para no afectar el desarrollo de la clase.', '2026-07-21', 'Pendiente', 4, NULL),
(5, 'Falta injustificada', 'El alumno no se presentó a clases y no se notificó justificación previa por parte del tutor.', '2026-07-22', 'Pendiente', 7, 8),
(7, 'Académica', 'No entrega tareas y no respeta al docente', '2026-07-22', 'Pendiente', 6, 9);

-- --------------------------------------------------------

--
-- Table structure for table `padre_tutor`
--

CREATE TABLE `padre_tutor` (
  `ID_Padre` int(11) NOT NULL,
  `Nombre_Completo` varchar(150) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  `ID_Usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `padre_tutor`
--

INSERT INTO `padre_tutor` (`ID_Padre`, `Nombre_Completo`, `Correo`, `Telefono`, `ID_Usuario`) VALUES
(1, 'Juan Carlos Hernández', 'juan.padre@miauladigital.com', '7711234567', 5),
(2, 'María López García', 'maria.padre@miauladigital.com', '7712345678', 6),
(3, 'Roberto Pérez Sánchez', 'roberto.padre@miauladigital.com', '7713456789', 7),
(4, 'Daniel Jeronimo', 'jeronimod338@gmail.com', '7721090100', 10),
(5, 'Brayan Santos Martinez', 'byanmartinez@gmail.com', '7745653268', 16),
(6, 'Sebastian Cruz Rello', 'sebafbhr@gmail.com', '7722598325', 17),
(7, 'Carlos Ramírez Torres', 'carlos@correo.com', '7733245512', 19),
(8, 'Julio Montoya', 'julio.messi.montoya@gmail.com', '6565754344', 21);

-- --------------------------------------------------------

--
-- Table structure for table `publicacion`
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
-- Dumping data for table `publicacion`
--

INSERT INTO `publicacion` (`ID_Publicacion`, `Titulo`, `Contenido`, `Fecha_Publicacion`, `ID_Director`, `ID_Docente`, `ID_Grupo`) VALUES
(3, 'Suspensión de clases por mantenimiento', 'Se informa a toda la comunidad escolar que el día viernes 17 de julio se suspenderán las actividades académicas debido a trabajos de mantenimiento en las instalaciones. Las clases se reanudarán de manera normal el lunes 20 de julio.', '2026-07-15', NULL, NULL, 1),
(10, 'Bienvenida al ciclo escolar', 'Se les da la bienvenida a todos los alumnos de nuevo ingreso al ciclo escolar.', '2026-07-22', 1, NULL, 1),
(12, 'Reunion General', 'fdgsfd', '2026-07-24', NULL, 13, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_acceso`
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
-- Dumping data for table `solicitud_acceso`
--

INSERT INTO `solicitud_acceso` (`ID_Solicitud`, `Nombre`, `Primer_Apellido`, `Segundo_Apellido`, `Usuario`, `Correo`, `Contrasena`, `Rol`, `Estado`, `Fecha_Solicitud`, `Fecha_Revision`) VALUES
(1, 'Brayan', 'Santos', 'Martinez', 'brayan1323', 'byanmartinezsantos@gmail.com', '$2y$10$EaOPOMx.iVUYons4ppbUOOZtbDROI392H7AXy49HRs0VGwsewcQQ2', 'Docente', 'Aprobada', '2026-07-14 19:23:02', '2026-07-15 09:58:11'),
(2, 'Daniel', 'Garcia', 'Jeronimo', 'Dangj', 'jeronimod338@gmail.com', '$2y$10$ck1Q76UVyr3b.J8jafmnl.KE6cm4BlcWQI8Y/A6DMjvpwnFKtt6o.', 'Docente', 'Rechazada', '2026-07-16 09:02:17', '2026-07-16 09:16:59'),
(3, 'Daniel', 'perez', 'Jeronimo', 'Dan', 'dan@gmail.com', '$2y$10$IjdhuOJgQXYi9/SAFKN27.byJ9cA9/sPcBIxUqd8cJ1uGO2GGtBDa', 'Docente', 'Aprobada', '2026-07-16 09:16:27', '2026-07-16 09:17:06'),
(4, 'Maria', 'Gomez', 'Hernandez', 'mgomez', 'maria@correo.com', '$2y$10$vJpHEnXiGZ.9rp3bKJsP2ej/gKb.wJ.z97CchzvKwOy2r5Q/zwOEq', 'Docente', 'Pendiente', '2026-07-18 16:17:37', NULL),
(5, 'JuanP', 'Morales', 'Mails', 'Juan', 'juan.morales@gmail.com', '$2y$10$ePh43Fo117EanXIQ.7Pfo.15NO72F9VMhTawwNIjln.xrhuuvzrPu', 'Docente', 'Aprobada', '2026-07-24 16:47:37', '2026-07-24 16:49:03');

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
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
-- Dumping data for table `tokens`
--

INSERT INTO `tokens` (`ID_Token`, `Token_String`, `Fecha_Inicio`, `Fecha_Expiracion`, `Dispositivo`, `ID_Alumno`) VALUES
(1, 'TOKEN-ALUMNO-001', '2026-07-15 01:40:20', '2027-12-31 23:59:59', 'Android', 1),
(2, 'TOKEN-ALUMNO-002', '2026-07-15 01:40:20', '2027-12-31 23:59:59', 'Android', 2),
(3, '3-SPS-20260721-E9E6', '2026-07-15 01:40:20', '2027-07-21 22:17:28', 'Android', 3),
(6, '1-ERB-20260721-520c', '2026-07-22 02:17:18', '2027-07-21 22:17:18', NULL, 4),
(8, 'TOKEN-ALUMNO-NOE', '2026-07-22 17:00:00', '2027-12-31 23:59:59', 'Android', 7),
(9, '1-CGL-20260731-61D4', '2026-07-22 15:19:03', '2027-07-31 12:23:01', NULL, 6),
(10, '5-DGJ-20260731-42F0', '2026-07-24 17:04:03', '2027-07-31 12:22:47', NULL, 9),
(11, 'TOKEN-ALUMNO-MATEO', '2026-07-24 19:00:00', '2027-12-31 23:59:59', 'Android', 10),
(12, 'TOKEN-ALUMNO-VALERIA', '2026-07-24 19:00:00', '2027-12-31 23:59:59', 'Android', 11),
(13, 'TOKEN-ALUMNO-DIEGO', '2026-07-24 19:00:00', '2027-12-31 23:59:59', 'Android', 12),
(14, '1-LRL-20260731-4E06', '2026-07-22 19:00:00', '2027-07-31 12:22:20', 'Android', 13);

-- --------------------------------------------------------

--
-- Table structure for table `usuario`
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
-- Dumping data for table `usuario`
--

INSERT INTO `usuario` (`ID_Usuario`, `Nombre`, `Usuario`, `Correo`, `Contrasena`, `Rol`) VALUES
(1, 'Carlos Hernández López', 'direccion1', 'direccion1@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'Direccion'),
(4, 'Laura Hernández Pérez', 'docente3', 'laura.docente@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'Docente'),
(5, 'Juan Carlos Hernández', 'padre1', 'juan.padre@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'PadreTutor'),
(6, 'María López García', 'padre2', 'maria.padre@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'PadreTutor'),
(7, 'Roberto Pérez Sánchez', 'padre3', 'roberto.padre@miauladigital.com', '$2y$10$9qTVmuEawtfxhuhBScPCRO3dkwYxgtfXaphHxp4oQ/zekU9LSRuhC', 'PadreTutor'),
(9, 'Brayan', 'brayan1323', 'byanmartinezsantos@gmail.com', '$2y$10$EaOPOMx.iVUYons4ppbUOOZtbDROI392H7AXy49HRs0VGwsewcQQ2', 'Docente'),
(10, 'Daniel Jeronimo', 'Danielgj', 'jeronimod338@gmail.com', '$2y$10$6HgZdcurjDZwHiy9u.hxqu/UuedvDkp1fMY.6DbrtKiXmj6iUAIqG', 'PadreTutor'),
(15, 'Daniel perez Jeronimo', 'Dan', 'dan@gmail.com', '$2y$10$IjdhuOJgQXYi9/SAFKN27.byJ9cA9/sPcBIxUqd8cJ1uGO2GGtBDa', 'Docente'),
(16, 'Brayan Santos Martinez', 'brayand443te', 'byanmartinez@gmail.com', '$2y$10$NJqHGNM2nIo9suN0jcB.0eot3oMElrxnBnWL7vqFHF3UK9ggPDBw.', 'PadreTutor'),
(17, 'Sebastian Cruz Rello', 'sebas1234', 'sebafbhr@gmail.com', '$2y$10$Szzk/7XI7Kubqn6K2R8Apuxn3YXbQrodUQegu3W4U8TEjmjJwIQe6', 'PadreTutor'),
(19, 'Carlos Ramírez Torres', 'cramirez', 'carlos@correo.com', '$2y$10$tWWCyTpwmNXdgVWc5BuYpObhpaBiq.At7oGwUXv/9iSWBYjE3tg5m', 'PadreTutor'),
(21, 'Julio Montoya', 'Julio1', 'julio.messi.montoya@gmail.com', '$2y$10$dQJRLh8sqa.9uO.l82mx2uhFAEr9x.XKzCM2gSuo2wsY5KYC8SH1q', 'PadreTutor'),
(22, 'Maria Gomez Hernadez', 'mgomez', 'mgomez123@gmail.com', '$2y$10$GR0/iG9JO1oQKuqrPO6Z/.zmQtdlqGIwiDi9.M859CTZOkdpIwxwm', 'Docente'),
(23, 'JuanP Morales Mails', 'Juan', 'juan.morales@gmail.com', '$2y$10$ePh43Fo117EanXIQ.7Pfo.15NO72F9VMhTawwNIjln.xrhuuvzrPu', 'Docente');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alumno`
--
ALTER TABLE `alumno`
  ADD PRIMARY KEY (`ID_Alumno`),
  ADD UNIQUE KEY `CURP` (`CURP`),
  ADD KEY `fk_alumno_grupo` (`ID_Grupo`),
  ADD KEY `fk_alumno_padre` (`ID_Padre`),
  ADD KEY `fk_alumno_token` (`ID_Token`);

--
-- Indexes for table `aviso`
--
ALTER TABLE `aviso`
  ADD PRIMARY KEY (`ID_Aviso`),
  ADD UNIQUE KEY `ID_Publicacion` (`ID_Publicacion`);

--
-- Indexes for table `calificacion`
--
ALTER TABLE `calificacion`
  ADD PRIMARY KEY (`ID_Calificacion`),
  ADD KEY `ID_Campo` (`ID_Campo`),
  ADD KEY `ID_Docente` (`ID_Docente`),
  ADD KEY `calificacion_ibfk_2` (`ID_Alumno`);

--
-- Indexes for table `campo_formativo`
--
ALTER TABLE `campo_formativo`
  ADD PRIMARY KEY (`ID_Campo`),
  ADD UNIQUE KEY `Nombre_Campo` (`Nombre_Campo`);

--
-- Indexes for table `director`
--
ALTER TABLE `director`
  ADD PRIMARY KEY (`ID_Director`),
  ADD UNIQUE KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indexes for table `docente`
--
ALTER TABLE `docente`
  ADD PRIMARY KEY (`ID_Docente`),
  ADD UNIQUE KEY `ID_Usuario` (`ID_Usuario`),
  ADD KEY `ID_Grupo_Asignado` (`ID_Grupo_Asignado`);

--
-- Indexes for table `grupo`
--
ALTER TABLE `grupo`
  ADD PRIMARY KEY (`ID_Grupo`);

--
-- Indexes for table `incidencia`
--
ALTER TABLE `incidencia`
  ADD PRIMARY KEY (`ID_Incidencia`),
  ADD KEY `ID_Alumno` (`ID_Alumno`),
  ADD KEY `ID_Docente` (`ID_Docente`);

--
-- Indexes for table `padre_tutor`
--
ALTER TABLE `padre_tutor`
  ADD PRIMARY KEY (`ID_Padre`),
  ADD UNIQUE KEY `Correo` (`Correo`),
  ADD UNIQUE KEY `ID_Usuario` (`ID_Usuario`);

--
-- Indexes for table `publicacion`
--
ALTER TABLE `publicacion`
  ADD PRIMARY KEY (`ID_Publicacion`),
  ADD KEY `ID_Director` (`ID_Director`),
  ADD KEY `ID_Docente` (`ID_Docente`),
  ADD KEY `ID_Grupo` (`ID_Grupo`);

--
-- Indexes for table `solicitud_acceso`
--
ALTER TABLE `solicitud_acceso`
  ADD PRIMARY KEY (`ID_Solicitud`),
  ADD UNIQUE KEY `Usuario` (`Usuario`),
  ADD UNIQUE KEY `Correo` (`Correo`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`ID_Token`),
  ADD KEY `fk_tokens_alumno` (`ID_Alumno`);

--
-- Indexes for table `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`ID_Usuario`),
  ADD UNIQUE KEY `Usuario` (`Usuario`),
  ADD UNIQUE KEY `Correo` (`Correo`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alumno`
--
ALTER TABLE `alumno`
  MODIFY `ID_Alumno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `aviso`
--
ALTER TABLE `aviso`
  MODIFY `ID_Aviso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `calificacion`
--
ALTER TABLE `calificacion`
  MODIFY `ID_Calificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `campo_formativo`
--
ALTER TABLE `campo_formativo`
  MODIFY `ID_Campo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `director`
--
ALTER TABLE `director`
  MODIFY `ID_Director` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `docente`
--
ALTER TABLE `docente`
  MODIFY `ID_Docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `grupo`
--
ALTER TABLE `grupo`
  MODIFY `ID_Grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `incidencia`
--
ALTER TABLE `incidencia`
  MODIFY `ID_Incidencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `padre_tutor`
--
ALTER TABLE `padre_tutor`
  MODIFY `ID_Padre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `publicacion`
--
ALTER TABLE `publicacion`
  MODIFY `ID_Publicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `solicitud_acceso`
--
ALTER TABLE `solicitud_acceso`
  MODIFY `ID_Solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `ID_Token` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `usuario`
--
ALTER TABLE `usuario`
  MODIFY `ID_Usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alumno`
--
ALTER TABLE `alumno`
  ADD CONSTRAINT `alumno_ibfk_1` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL,
  ADD CONSTRAINT `alumno_ibfk_2` FOREIGN KEY (`ID_Padre`) REFERENCES `padre_tutor` (`ID_Padre`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alumno_grupo` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alumno_padre` FOREIGN KEY (`ID_Padre`) REFERENCES `padre_tutor` (`ID_Padre`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_alumno_token` FOREIGN KEY (`ID_Token`) REFERENCES `tokens` (`ID_Token`) ON DELETE SET NULL;

--
-- Constraints for table `aviso`
--
ALTER TABLE `aviso`
  ADD CONSTRAINT `aviso_ibfk_1` FOREIGN KEY (`ID_Publicacion`) REFERENCES `publicacion` (`ID_Publicacion`) ON DELETE CASCADE;

--
-- Constraints for table `calificacion`
--
ALTER TABLE `calificacion`
  ADD CONSTRAINT `calificacion_ibfk_1` FOREIGN KEY (`ID_Campo`) REFERENCES `campo_formativo` (`ID_Campo`),
  ADD CONSTRAINT `calificacion_ibfk_2` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificacion_ibfk_3` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL;

--
-- Constraints for table `director`
--
ALTER TABLE `director`
  ADD CONSTRAINT `director_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE;

--
-- Constraints for table `docente`
--
ALTER TABLE `docente`
  ADD CONSTRAINT `docente_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE,
  ADD CONSTRAINT `docente_ibfk_2` FOREIGN KEY (`ID_Grupo_Asignado`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL;

--
-- Constraints for table `incidencia`
--
ALTER TABLE `incidencia`
  ADD CONSTRAINT `incidencia_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidencia_ibfk_2` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL;

--
-- Constraints for table `padre_tutor`
--
ALTER TABLE `padre_tutor`
  ADD CONSTRAINT `padre_tutor_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE;

--
-- Constraints for table `publicacion`
--
ALTER TABLE `publicacion`
  ADD CONSTRAINT `publicacion_ibfk_1` FOREIGN KEY (`ID_Director`) REFERENCES `director` (`ID_Director`) ON DELETE SET NULL,
  ADD CONSTRAINT `publicacion_ibfk_2` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL,
  ADD CONSTRAINT `publicacion_ibfk_3` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE CASCADE;

--
-- Constraints for table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `fk_tokens_alumno` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
