-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-07-2025 a las 01:36:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `hospital`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cita`
--

CREATE TABLE `cita` (
  `cita_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `tipo_cita_id` int(11) DEFAULT NULL,
  `especialidad_id` int(11) DEFAULT NULL,
  `prioridad_id` int(11) DEFAULT NULL,
  `origen_id` int(11) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `estado_id` int(11) DEFAULT NULL,
  `turno_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cita`
--

INSERT INTO `cita` (`cita_id`, `paciente_id`, `medico_id`, `fecha`, `tipo_cita_id`, `especialidad_id`, `prioridad_id`, `origen_id`, `motivo`, `estado_id`, `turno_id`) VALUES
(11, 11, 14, '2025-06-23 08:00:00', 1, 1, 1, 1, 'Consulta', 5, 1),
(12, 11, 14, '2025-06-23 08:30:00', 1, 1, 1, 1, 'Holaa', 5, 1),
(13, 11, 14, '2025-06-25 15:00:00', 1, 1, 1, 1, 'Consulta de examenes', 5, 2),
(14, 11, 15, '2025-06-24 11:30:00', 1, 2, 2, 2, 'Cita para Diana Lopez', 5, 3),
(15, 11, 14, '2025-06-24 06:30:00', 1, 1, 1, 1, 'Consulta nueva 24/06', 5, 13),
(16, 11, 14, '2025-06-24 07:00:00', 1, 1, 1, 1, 'Segunda consulta del dia', 5, 13),
(17, 11, 14, '2025-07-01 05:00:00', 1, 1, 1, 1, 'rñktnoirlmtg', 5, 29),
(18, 11, 14, '2025-07-01 05:30:00', 1, 1, 1, 1, 'Cita por administrador', 5, 29),
(19, 21, 14, '2025-07-01 06:00:00', 1, 1, 1, 1, 'Cita creada por Karla', 5, 29),
(20, 11, 15, '2025-06-30 08:15:00', 2, 2, 1, 1, 'nueva cita por Adminisitrador', 5, 19),
(21, 21, 15, '2025-06-30 08:45:00', 1, 2, 1, 3, 'Nueva consulta por Karla', 5, 19),
(22, 21, 15, '2025-06-30 09:15:00', 3, 2, 1, 3, 'Consulta sin origen', 5, 19),
(23, 11, 14, '2025-06-30 06:00:00', 1, 1, 1, 1, 'HolAAaaaaa', 5, 27),
(24, 21, 14, '2025-07-01 07:00:00', 1, 1, 2, 3, 'sñljfnvdlekqñfkeln', 5, 29),
(25, 11, 14, '2025-07-04 08:00:00', 1, 1, 1, 1, 'examenes', 1, 35),
(26, 21, 14, '2025-07-01 06:30:00', 1, 1, 1, 3, 'Cita sin perdida', 5, 29),
(27, 21, 14, '2025-07-01 18:30:00', 1, 1, 1, 3, 'Cita sin perdida', 1, 29);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consulta`
--

CREATE TABLE `consulta` (
  `consulta_id` int(11) NOT NULL,
  `cita_id` int(11) NOT NULL,
  `diagnostico` text DEFAULT NULL,
  `tratamiento` text DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `consulta`
--

INSERT INTO `consulta` (`consulta_id`, `cita_id`, `diagnostico`, `tratamiento`, `fecha`) VALUES
(1, 21, 'Faringitis aguda', 'Antibióticos por 7 días', '2025-04-02 23:25:32'),
(2, 22, 'Control general', 'Revisión de signos vitales y análisis de sangre', '2025-05-17 23:25:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad`
--

CREATE TABLE `especialidad` (
  `especialidad_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidad`
--

INSERT INTO `especialidad` (`especialidad_id`, `nombre`) VALUES
(1, 'Medicina General'),
(2, 'Pediatría'),
(3, 'Ginecología'),
(4, 'Traumatología');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_cita`
--

CREATE TABLE `estado_cita` (
  `estado_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado_cita`
--

INSERT INTO `estado_cita` (`estado_id`, `nombre`) VALUES
(1, 'Pendiente'),
(2, 'Confirmada'),
(3, 'Atendida'),
(4, 'Cancelada'),
(5, 'Perdida'),
(6, 'Reprogramada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evolucion_medica`
--

CREATE TABLE `evolucion_medica` (
  `evolucion_id` int(11) NOT NULL,
  `hospitalizacion_id` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `signos` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen`
--

CREATE TABLE `examen` (
  `examen_id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `examen`
--

INSERT INTO `examen` (`examen_id`, `consulta_id`, `tipo`, `fecha`) VALUES
(1, 1, 'Hemograma completo', '2025-04-22 23:25:32'),
(2, 2, 'Perfil lipídico', '2025-05-27 23:25:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `factura_id` int(11) NOT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `consulta_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitacion`
--

CREATE TABLE `habitacion` (
  `habitacion_id` int(11) NOT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_clinico`
--

CREATE TABLE `historial_clinico` (
  `historial_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `antecedentes` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `enfermedades_cronicas` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_clinico`
--

INSERT INTO `historial_clinico` (`historial_id`, `usuario_id`, `antecedentes`, `alergias`, `enfermedades_cronicas`, `observaciones`) VALUES
(1, 21, 'Asma infantil', 'Penicilina', 'Hipertensión', 'Requiere seguimiento mensual.'),
(2, 22, 'Apendicitis en 2021', 'Ninguna', 'Ninguna', 'Paciente en buen estado general.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hospitalizacion`
--

CREATE TABLE `hospitalizacion` (
  `hospitalizacion_id` int(11) NOT NULL,
  `paciente_id` int(11) DEFAULT NULL,
  `habitacion_id` int(11) DEFAULT NULL,
  `fecha_ingreso` datetime DEFAULT NULL,
  `fecha_egreso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicamento`
--

CREATE TABLE `medicamento` (
  `medicamento_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `medicamento`
--

INSERT INTO `medicamento` (`medicamento_id`, `nombre`, `descripcion`) VALUES
(1, 'Amoxicilina', 'Antibiótico de amplio espectro');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

CREATE TABLE `menus` (
  `menu_id` int(11) NOT NULL,
  `menu_nombre` varchar(100) NOT NULL,
  `menu_icono` varchar(100) DEFAULT NULL,
  `roles` tinyint(4) NOT NULL DEFAULT 1,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`menu_id`, `menu_nombre`, `menu_icono`, `roles`, `estado`) VALUES
(1, 'Sistema', 'fa-cogs', 1, 1),
(2, 'Citas', 'fa fa-id-card', 1, 0),
(3, 'Datos', 'fa fa-id-card', 1, 0),
(4, 'Citas', '', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `origen_cita`
--

CREATE TABLE `origen_cita` (
  `origen_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `origen_cita`
--

INSERT INTO `origen_cita` (`origen_id`, `nombre`) VALUES
(1, 'Presencial'),
(2, 'Llamada Telefónica'),
(3, 'Plataforma Web');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `permiso_id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `tipo` enum('menu','submenu','accion') NOT NULL,
  `objeto_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`permiso_id`, `rol_id`, `tipo`, `objeto_id`) VALUES
(49, 1, 'menu', 1),
(50, 1, 'menu', 4),
(51, 1, 'submenu', 1),
(52, 1, 'submenu', 2),
(53, 1, 'submenu', 4),
(54, 1, 'accion', 1),
(55, 1, 'accion', 2),
(56, 1, 'accion', 3),
(57, 1, 'accion', 4),
(58, 1, 'accion', 5),
(59, 1, 'accion', 7),
(60, 1, 'accion', 8),
(61, 1, 'accion', 9),
(65, 31, 'menu', 4),
(66, 31, 'submenu', 3),
(67, 31, 'accion', 10),
(68, 31, 'accion', 11),
(75, 30, 'menu', 4),
(76, 30, 'submenu', 4),
(77, 30, 'accion', 8),
(78, 30, 'accion', 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prioridad`
--

CREATE TABLE `prioridad` (
  `prioridad_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prioridad`
--

INSERT INTO `prioridad` (`prioridad_id`, `nombre`) VALUES
(1, 'Alta'),
(2, 'Media'),
(3, 'Baja');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta`
--

CREATE TABLE `receta` (
  `receta_id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `indicaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `receta`
--

INSERT INTO `receta` (`receta_id`, `consulta_id`, `fecha`, `indicaciones`) VALUES
(1, 1, '2025-05-17', 'Amoxicilina 500mg cada 8h por 7 días');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta_medicamento`
--

CREATE TABLE `receta_medicamento` (
  `receta_id` int(11) DEFAULT NULL,
  `medicamento_id` int(11) DEFAULT NULL,
  `dosis` text DEFAULT NULL,
  `frecuencia` text DEFAULT NULL,
  `duracion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `receta_medicamento`
--

INSERT INTO `receta_medicamento` (`receta_id`, `medicamento_id`, `dosis`, `frecuencia`, `duracion`) VALUES
(1, 1, '500mg', 'Cada 8h', '7 días');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultado`
--

CREATE TABLE `resultado` (
  `resultado_id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resultado`
--

INSERT INTO `resultado` (`resultado_id`, `examen_id`, `descripcion`, `fecha`) VALUES
(1, 1, 'Leucocitos normales, sin signos de infección', '2025-05-02 23:25:32'),
(2, 2, 'Colesterol total ligeramente elevado, se recomienda dieta', '2025-05-29 23:25:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `rol_id` int(11) NOT NULL,
  `rol_nombre` varchar(50) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`rol_id`, `rol_nombre`, `estado`) VALUES
(1, 'Super Administrador', 1),
(30, 'Paciente', 1),
(31, 'Medico', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `signos_vitales`
--

CREATE TABLE `signos_vitales` (
  `signo_id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `presion_arterial` varchar(20) DEFAULT NULL,
  `temperatura` decimal(4,2) DEFAULT NULL,
  `frecuencia_cardiaca` int(11) DEFAULT NULL,
  `saturacion_oxigeno` int(11) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `signos_vitales`
--

INSERT INTO `signos_vitales` (`signo_id`, `consulta_id`, `presion_arterial`, `temperatura`, `frecuencia_cardiaca`, `saturacion_oxigeno`, `peso`, `altura`) VALUES
(1, 1, '120/80', 37.20, 78, 98, 60.50, 1.65),
(2, 2, '110/70', 36.80, 75, 99, 58.20, 1.60);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `submenus`
--

CREATE TABLE `submenus` (
  `submenu_id` int(11) NOT NULL,
  `submenu_nombre` varchar(100) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `submenus`
--

INSERT INTO `submenus` (`submenu_id`, `submenu_nombre`, `menu_id`, `estado`) VALUES
(1, 'Usuarios', 1, 1),
(2, 'Tablas', 1, 1),
(3, 'Calendario', 4, 1),
(4, 'Agendamiento de citas', 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sub_submenus`
--

CREATE TABLE `sub_submenus` (
  `subsubmenu_id` int(11) NOT NULL,
  `subsubmenu_nombre` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `submenu_id` int(11) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sub_submenus`
--

INSERT INTO `sub_submenus` (`subsubmenu_id`, `subsubmenu_nombre`, `url`, `submenu_id`, `estado`) VALUES
(1, 'Crear Usuarios', 'usuarios/crear.php', 1, 1),
(2, 'Listar Roles', 'roles/listar.php', 2, 1),
(3, 'Listar menus', 'menus/listar.php', 2, 1),
(4, 'Listar Submenus', 'submenus/listar.php', 2, 1),
(5, 'Listar acciones', 'acciones/listar.php', 2, 1),
(6, 'Pendientes', 'productos/listar.php', 3, 0),
(7, 'Listar permisos', 'permisos/listar.php', 2, 1),
(8, 'Crear cita', 'citas/crear.php', 4, 1),
(9, 'Listar citas', 'citas/listar.php', 4, 1),
(10, 'Mis Citas', 'citas/calendario.php', 3, 1),
(11, 'Mi horario', 'citas/mihorario.php', 3, 1),
(12, 'Agendar cita', 'citas/cita_usuario.php', 4, 0),
(13, 'Mis Citas', 'citas/mis_citas.php', 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_cita`
--

CREATE TABLE `tipo_cita` (
  `tipo_cita_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_cita`
--

INSERT INTO `tipo_cita` (`tipo_cita_id`, `nombre`) VALUES
(1, 'Consulta general'),
(2, 'Emergencia'),
(3, 'Control mensual');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turno`
--

CREATE TABLE `turno` (
  `turno_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `dia_semana` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turno`
--

INSERT INTO `turno` (`turno_id`, `medico_id`, `dia_semana`, `hora_inicio`, `hora_fin`) VALUES
(17, 15, 'Martes', '08:30:00', '12:00:00'),
(18, 15, 'Martes', '15:00:00', '21:30:00'),
(19, 15, 'Lunes', '08:15:00', '12:30:00'),
(20, 15, 'Lunes', '14:25:00', '18:28:00'),
(27, 14, 'Lunes', '06:00:00', '12:00:00'),
(28, 14, 'Lunes', '14:00:00', '17:00:00'),
(29, 14, 'Martes', '05:00:00', '12:00:00'),
(30, 14, 'Martes', '14:00:00', '20:00:00'),
(31, 14, 'Miércoles', '04:00:00', '11:00:00'),
(32, 14, 'Miércoles', '13:00:00', '20:00:00'),
(33, 14, 'Jueves', '05:00:00', '12:00:00'),
(34, 14, 'Jueves', '13:00:00', '19:00:00'),
(35, 14, 'Viernes', '08:00:00', '12:00:00'),
(36, 14, 'Viernes', '13:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `usu_id` int(11) NOT NULL,
  `usu_fecha_registro` datetime DEFAULT current_timestamp(),
  `usu_nombre` varchar(100) NOT NULL,
  `usu_apellido` varchar(100) NOT NULL,
  `usu_correo` varchar(150) NOT NULL,
  `usu_contrasena` varchar(255) NOT NULL,
  `usu_cedula` varchar(10) NOT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `especialidad_id` int(11) DEFAULT NULL,
  `usu_primera_vez` tinyint(1) NOT NULL DEFAULT 1,
  `usu_estado` tinyint(4) NOT NULL DEFAULT 1,
  `usu_token_recuperacion` varchar(100) DEFAULT NULL,
  `usu_token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`usu_id`, `usu_fecha_registro`, `usu_nombre`, `usu_apellido`, `usu_correo`, `usu_contrasena`, `usu_cedula`, `rol_id`, `especialidad_id`, `usu_primera_vez`, `usu_estado`, `usu_token_recuperacion`, `usu_token_expira`) VALUES
(1, '2025-05-27 11:32:03', 'Jefferson', 'Casillas', 'jeffersoncasillas611@gmail.com', 'f99bc9050b3f95d8e81cccdf4869f23a2f16b297ddc52317ef7cff94ad216263', '1234567890', 1, NULL, 0, 1, NULL, NULL),
(11, '2025-06-05 15:58:48', 'JOSELYN TATIANA', 'CASILLAS TIPAN', 'usuario1@menu.com', 'cf5395db6fe4f72906a398fa78cb7aaeb03b047888be17382f752df6159ba6c7', '1723296701', 30, NULL, 0, 1, NULL, NULL),
(12, '2025-06-06 09:55:32', 'Carlos Esteban', 'Bastidas Mesa', 'carlosbastidas0304@outlook.com', '66f0914a90b08ae9797228d79121bd4e3856f5681e2d4bb5f4bd31eca6ee8dc1', '1754450607', 30, NULL, 1, 1, NULL, NULL),
(13, '2025-06-06 10:37:23', 'Moises', 'Betancurd', 'davidmoibm@gmail.com', 'c420dc54bb888542936f9b6e3e04c3f4a04125bc0a83311710611e91057004d0', '1755414446', 30, NULL, 1, 1, NULL, NULL),
(14, '2025-06-06 11:51:13', 'Carlos', 'Ramírez', 'medico1@hospital.com', 'f5608d49f1d1ee9b0b55cb54c55f6e24be0be9cf56d4c2e0efc478db59f9e507', '1100110011', 31, 1, 0, 1, NULL, NULL),
(15, '2025-06-06 11:51:13', 'Diana', 'López', 'medico2@hospital.com', '18973529a8e0bb0264786a8c92d6b9b143e826ed0003e535aab92028b80d7cae', '1100110022', 31, 2, 0, 1, NULL, NULL),
(19, '2025-06-25 08:19:01', 'Damian', 'Tipan', 'medico3@hospital.com', 'f1d2f0ee84b48781cd84d4d702cce37d9b2685801ae3c8cdb52e0d42ead32af0', '1754336236', 31, 2, 0, 1, NULL, NULL),
(21, '2025-06-25 08:31:49', 'Karla', 'Mozo', 'paciente1@hospital.com', 'bc06cea58849a10fea88c1d9f64bde4af829cbea81ad5e281aadff945b445f44', '1254258962', 30, NULL, 0, 1, NULL, NULL),
(22, '2025-06-25 08:34:14', 'Jennifer', 'Caisaguano', 'paciente2@hospital.com', '80d61ac438a0baf23f59b664ffc7796cfd17671a22e4459adc438e8aad115588', '0987568525', 30, NULL, 0, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vacuna`
--

CREATE TABLE `vacuna` (
  `vacuna_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `fecha_aplicacion` date DEFAULT NULL,
  `dosis` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vacuna`
--

INSERT INTO `vacuna` (`vacuna_id`, `usuario_id`, `nombre`, `fecha_aplicacion`, `dosis`) VALUES
(1, 21, 'Hepatitis B', '2025-01-02', '1ra dosis'),
(2, 21, 'Tétanos', '2024-07-01', 'Refuerzo'),
(3, 22, 'COVID-19', '2025-01-02', '2da dosis'),
(4, 22, 'Influenza', '2024-07-01', 'Anual');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cita`
--
ALTER TABLE `cita`
  ADD PRIMARY KEY (`cita_id`);

--
-- Indices de la tabla `consulta`
--
ALTER TABLE `consulta`
  ADD PRIMARY KEY (`consulta_id`);

--
-- Indices de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  ADD PRIMARY KEY (`especialidad_id`);

--
-- Indices de la tabla `estado_cita`
--
ALTER TABLE `estado_cita`
  ADD PRIMARY KEY (`estado_id`);

--
-- Indices de la tabla `evolucion_medica`
--
ALTER TABLE `evolucion_medica`
  ADD PRIMARY KEY (`evolucion_id`);

--
-- Indices de la tabla `examen`
--
ALTER TABLE `examen`
  ADD PRIMARY KEY (`examen_id`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`factura_id`);

--
-- Indices de la tabla `habitacion`
--
ALTER TABLE `habitacion`
  ADD PRIMARY KEY (`habitacion_id`);

--
-- Indices de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  ADD PRIMARY KEY (`historial_id`);

--
-- Indices de la tabla `hospitalizacion`
--
ALTER TABLE `hospitalizacion`
  ADD PRIMARY KEY (`hospitalizacion_id`);

--
-- Indices de la tabla `medicamento`
--
ALTER TABLE `medicamento`
  ADD PRIMARY KEY (`medicamento_id`);

--
-- Indices de la tabla `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indices de la tabla `origen_cita`
--
ALTER TABLE `origen_cita`
  ADD PRIMARY KEY (`origen_id`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`permiso_id`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indices de la tabla `prioridad`
--
ALTER TABLE `prioridad`
  ADD PRIMARY KEY (`prioridad_id`);

--
-- Indices de la tabla `receta`
--
ALTER TABLE `receta`
  ADD PRIMARY KEY (`receta_id`);

--
-- Indices de la tabla `resultado`
--
ALTER TABLE `resultado`
  ADD PRIMARY KEY (`resultado_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`rol_id`);

--
-- Indices de la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  ADD PRIMARY KEY (`signo_id`);

--
-- Indices de la tabla `submenus`
--
ALTER TABLE `submenus`
  ADD PRIMARY KEY (`submenu_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indices de la tabla `sub_submenus`
--
ALTER TABLE `sub_submenus`
  ADD PRIMARY KEY (`subsubmenu_id`),
  ADD KEY `submenu_id` (`submenu_id`);

--
-- Indices de la tabla `tipo_cita`
--
ALTER TABLE `tipo_cita`
  ADD PRIMARY KEY (`tipo_cita_id`);

--
-- Indices de la tabla `turno`
--
ALTER TABLE `turno`
  ADD PRIMARY KEY (`turno_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`usu_id`),
  ADD UNIQUE KEY `usu_correo` (`usu_correo`),
  ADD UNIQUE KEY `usu_cedula` (`usu_cedula`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `fk_usuarios_especialidad` (`especialidad_id`);

--
-- Indices de la tabla `vacuna`
--
ALTER TABLE `vacuna`
  ADD PRIMARY KEY (`vacuna_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cita`
--
ALTER TABLE `cita`
  MODIFY `cita_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `consulta`
--
ALTER TABLE `consulta`
  MODIFY `consulta_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  MODIFY `especialidad_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estado_cita`
--
ALTER TABLE `estado_cita`
  MODIFY `estado_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `evolucion_medica`
--
ALTER TABLE `evolucion_medica`
  MODIFY `evolucion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `examen`
--
ALTER TABLE `examen`
  MODIFY `examen_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `factura_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `habitacion`
--
ALTER TABLE `habitacion`
  MODIFY `habitacion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_clinico`
--
ALTER TABLE `historial_clinico`
  MODIFY `historial_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `hospitalizacion`
--
ALTER TABLE `hospitalizacion`
  MODIFY `hospitalizacion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `medicamento`
--
ALTER TABLE `medicamento`
  MODIFY `medicamento_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `menus`
--
ALTER TABLE `menus`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `origen_cita`
--
ALTER TABLE `origen_cita`
  MODIFY `origen_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `permiso_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT de la tabla `prioridad`
--
ALTER TABLE `prioridad`
  MODIFY `prioridad_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `receta`
--
ALTER TABLE `receta`
  MODIFY `receta_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `resultado`
--
ALTER TABLE `resultado`
  MODIFY `resultado_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `rol_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `signos_vitales`
--
ALTER TABLE `signos_vitales`
  MODIFY `signo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `submenus`
--
ALTER TABLE `submenus`
  MODIFY `submenu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `sub_submenus`
--
ALTER TABLE `sub_submenus`
  MODIFY `subsubmenu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `tipo_cita`
--
ALTER TABLE `tipo_cita`
  MODIFY `tipo_cita_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `turno`
--
ALTER TABLE `turno`
  MODIFY `turno_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `usu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `vacuna`
--
ALTER TABLE `vacuna`
  MODIFY `vacuna_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD CONSTRAINT `permisos_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`rol_id`);

--
-- Filtros para la tabla `submenus`
--
ALTER TABLE `submenus`
  ADD CONSTRAINT `submenus_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`menu_id`);

--
-- Filtros para la tabla `sub_submenus`
--
ALTER TABLE `sub_submenus`
  ADD CONSTRAINT `sub_submenus_ibfk_1` FOREIGN KEY (`submenu_id`) REFERENCES `submenus` (`submenu_id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_especialidad` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidad` (`especialidad_id`),
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`rol_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
