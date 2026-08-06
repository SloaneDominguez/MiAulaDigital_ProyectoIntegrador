-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mi_aula_digital
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `alumno`
--

DROP TABLE IF EXISTS `alumno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alumno` (
  `ID_Alumno` int(11) NOT NULL AUTO_INCREMENT,
  `CURP` varchar(18) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `Primer_Apellido` varchar(50) NOT NULL,
  `Segundo_Apellido` varchar(50) DEFAULT NULL,
  `Fecha_Registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ID_Grupo` int(11) DEFAULT NULL,
  `ID_Padre` int(11) DEFAULT NULL,
  `ID_Token` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_Alumno`),
  UNIQUE KEY `CURP` (`CURP`),
  KEY `fk_alumno_grupo` (`ID_Grupo`),
  KEY `fk_alumno_padre` (`ID_Padre`),
  KEY `fk_alumno_token` (`ID_Token`),
  CONSTRAINT `alumno_ibfk_1` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL,
  CONSTRAINT `alumno_ibfk_2` FOREIGN KEY (`ID_Padre`) REFERENCES `padre_tutor` (`ID_Padre`) ON DELETE SET NULL,
  CONSTRAINT `fk_alumno_grupo` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL,
  CONSTRAINT `fk_alumno_padre` FOREIGN KEY (`ID_Padre`) REFERENCES `padre_tutor` (`ID_Padre`) ON DELETE SET NULL,
  CONSTRAINT `fk_alumno_token` FOREIGN KEY (`ID_Token`) REFERENCES `tokens` (`ID_Token`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aviso`
--

DROP TABLE IF EXISTS `aviso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aviso` (
  `ID_Aviso` int(11) NOT NULL AUTO_INCREMENT,
  `Tipo_Aviso` enum('Institucional','Urgente') NOT NULL,
  `ID_Publicacion` int(11) NOT NULL,
  PRIMARY KEY (`ID_Aviso`),
  UNIQUE KEY `ID_Publicacion` (`ID_Publicacion`),
  CONSTRAINT `aviso_ibfk_1` FOREIGN KEY (`ID_Publicacion`) REFERENCES `publicacion` (`ID_Publicacion`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calificacion`
--

DROP TABLE IF EXISTS `calificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calificacion` (
  `ID_Calificacion` int(11) NOT NULL AUTO_INCREMENT,
  `ID_Campo` int(11) NOT NULL,
  `Bimestre` enum('1','2','3','4','5') NOT NULL,
  `Calificacion_Numerica` decimal(3,1) NOT NULL,
  `ID_Alumno` int(11) NOT NULL,
  `ID_Docente` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_Calificacion`),
  UNIQUE KEY `ID_Alumno` (`ID_Alumno`,`ID_Campo`,`Bimestre`),
  KEY `ID_Campo` (`ID_Campo`),
  KEY `ID_Docente` (`ID_Docente`),
  CONSTRAINT `calificacion_ibfk_1` FOREIGN KEY (`ID_Campo`) REFERENCES `campo_formativo` (`ID_Campo`),
  CONSTRAINT `calificacion_ibfk_2` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE,
  CONSTRAINT `calificacion_ibfk_3` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campo_formativo`
--

DROP TABLE IF EXISTS `campo_formativo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campo_formativo` (
  `ID_Campo` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre_Campo` varchar(100) NOT NULL,
  PRIMARY KEY (`ID_Campo`),
  UNIQUE KEY `Nombre_Campo` (`Nombre_Campo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `director`
--

DROP TABLE IF EXISTS `director`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `director` (
  `ID_Director` int(11) NOT NULL AUTO_INCREMENT,
  `ID_Usuario` int(11) NOT NULL,
  PRIMARY KEY (`ID_Director`),
  UNIQUE KEY `ID_Usuario` (`ID_Usuario`),
  CONSTRAINT `director_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `docente`
--

DROP TABLE IF EXISTS `docente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `docente` (
  `ID_Docente` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) NOT NULL,
  `Primer_Apellido` varchar(50) NOT NULL,
  `Segundo_Apellido` varchar(50) DEFAULT NULL,
  `ID_Usuario` int(11) NOT NULL,
  `ID_Grupo_Asignado` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_Docente`),
  UNIQUE KEY `ID_Usuario` (`ID_Usuario`),
  KEY `ID_Grupo_Asignado` (`ID_Grupo_Asignado`),
  CONSTRAINT `docente_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE,
  CONSTRAINT `docente_ibfk_2` FOREIGN KEY (`ID_Grupo_Asignado`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grupo`
--

DROP TABLE IF EXISTS `grupo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grupo` (
  `ID_Grupo` int(11) NOT NULL AUTO_INCREMENT,
  `Grado` enum('1','2','3','4','5','6') NOT NULL,
  `Grupo` varchar(2) NOT NULL,
  PRIMARY KEY (`ID_Grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `incidencia`
--

DROP TABLE IF EXISTS `incidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidencia` (
  `ID_Incidencia` int(11) NOT NULL AUTO_INCREMENT,
  `Tipo_Incidencia` varchar(100) NOT NULL,
  `Descripcion` text NOT NULL,
  `Fecha` date NOT NULL,
  `Estado` enum('Pendiente','Resuelta') DEFAULT 'Pendiente',
  `ID_Alumno` int(11) NOT NULL,
  `ID_Docente` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_Incidencia`),
  KEY `ID_Alumno` (`ID_Alumno`),
  KEY `ID_Docente` (`ID_Docente`),
  CONSTRAINT `incidencia_ibfk_1` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE,
  CONSTRAINT `incidencia_ibfk_2` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `padre_tutor`
--

DROP TABLE IF EXISTS `padre_tutor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `padre_tutor` (
  `ID_Padre` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre_Completo` varchar(150) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Telefono` varchar(15) DEFAULT NULL,
  `ID_Usuario` int(11) NOT NULL,
  PRIMARY KEY (`ID_Padre`),
  UNIQUE KEY `Correo` (`Correo`),
  UNIQUE KEY `ID_Usuario` (`ID_Usuario`),
  CONSTRAINT `padre_tutor_ibfk_1` FOREIGN KEY (`ID_Usuario`) REFERENCES `usuario` (`ID_Usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `publicacion`
--

DROP TABLE IF EXISTS `publicacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publicacion` (
  `ID_Publicacion` int(11) NOT NULL AUTO_INCREMENT,
  `Titulo` varchar(150) NOT NULL,
  `Contenido` text NOT NULL,
  `Fecha_Publicacion` date NOT NULL,
  `ID_Director` int(11) DEFAULT NULL,
  `ID_Docente` int(11) DEFAULT NULL,
  `ID_Grupo` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID_Publicacion`),
  KEY `ID_Director` (`ID_Director`),
  KEY `ID_Docente` (`ID_Docente`),
  KEY `ID_Grupo` (`ID_Grupo`),
  CONSTRAINT `publicacion_ibfk_1` FOREIGN KEY (`ID_Director`) REFERENCES `director` (`ID_Director`) ON DELETE SET NULL,
  CONSTRAINT `publicacion_ibfk_2` FOREIGN KEY (`ID_Docente`) REFERENCES `docente` (`ID_Docente`) ON DELETE SET NULL,
  CONSTRAINT `publicacion_ibfk_3` FOREIGN KEY (`ID_Grupo`) REFERENCES `grupo` (`ID_Grupo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solicitud_acceso`
--

DROP TABLE IF EXISTS `solicitud_acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitud_acceso` (
  `ID_Solicitud` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) NOT NULL,
  `Primer_Apellido` varchar(100) NOT NULL,
  `Segundo_Apellido` varchar(100) DEFAULT NULL,
  `Usuario` varchar(50) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `Rol` enum('Docente') DEFAULT 'Docente',
  `Estado` enum('Pendiente','Aprobada','Rechazada') DEFAULT 'Pendiente',
  `Fecha_Solicitud` datetime DEFAULT current_timestamp(),
  `Fecha_Revision` datetime DEFAULT NULL,
  PRIMARY KEY (`ID_Solicitud`),
  UNIQUE KEY `Usuario` (`Usuario`),
  UNIQUE KEY `Correo` (`Correo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tokens`
--

DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tokens` (
  `ID_Token` int(11) NOT NULL AUTO_INCREMENT,
  `Token_String` varchar(500) NOT NULL,
  `Fecha_Inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `Fecha_Expiracion` datetime NOT NULL,
  `Dispositivo` varchar(100) DEFAULT NULL,
  `ID_Alumno` int(11) NOT NULL,
  PRIMARY KEY (`ID_Token`),
  KEY `fk_tokens_alumno` (`ID_Alumno`),
  CONSTRAINT `fk_tokens_alumno` FOREIGN KEY (`ID_Alumno`) REFERENCES `alumno` (`ID_Alumno`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `ID_Usuario` int(11) NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) NOT NULL,
  `Usuario` varchar(50) NOT NULL,
  `Correo` varchar(100) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `Rol` enum('Direccion','Docente','PadreTutor') NOT NULL,
  PRIMARY KEY (`ID_Usuario`),
  UNIQUE KEY `Usuario` (`Usuario`),
  UNIQUE KEY `Correo` (`Correo`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-13  0:10:47

-- ==========================================
-- SEEDERS - USUARIOS
-- ==========================================

INSERT INTO usuario (ID_Usuario, Nombre, Usuario, Correo, Contrasena, Rol) VALUES
(1, 'Director General', 'direccion1', 'direccion@escuela.local', 'Docente123', 'Direccion'),
(2, 'Docente Principal', 'docente1', 'docente@escuela.local', 'Docente123', 'Docente'),
(3, 'Padre/Tutor', 'LOPG850615MNLRRS08', 'tutor@escuela.local', '1-ERB-20260715-adb3', 'PadreTutor');

-- ==========================================
-- DIRECTOR
-- ==========================================

INSERT INTO director (ID_Director, ID_Usuario)
VALUES
(1,1);

-- ==========================================
-- DOCENTE
-- ==========================================

INSERT INTO docente (
    ID_Docente,
    Nombre,
    Primer_Apellido,
    Segundo_Apellido,
    ID_Usuario,
    ID_Grupo_Asignado
) VALUES (
    1,
    'Docente',
    'Principal',
    '',
    2,
    NULL
);

-- ==========================================
-- PADRE / TUTOR
-- ==========================================

INSERT INTO padre_tutor (
    ID_Padre,
    Nombre_Completo,
    Correo,
    Telefono,
    ID_Usuario
) VALUES (
    1,
    'Padre Tutor',
    'tutor@escuela.local',
    '0000000000',
    3
);