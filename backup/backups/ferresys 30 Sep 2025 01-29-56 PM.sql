-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ferresys
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
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categoria` (
  `id_Categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(75) DEFAULT NULL,
  `descripcion` varchar(75) DEFAULT NULL,
  PRIMARY KEY (`id_Categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id_Cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(75) DEFAULT NULL,
  `apellido` varchar(75) DEFAULT NULL,
  `dui` varchar(10) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_Cliente`),
  UNIQUE KEY `dui` (`dui`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Pedro Mario','Guardado Melendez','00265265-5','San Salvador, San Jacinto, Calle, San Martin','pm2025@gmail.com'),(2,'Kevin','Santos','16921692-6','Sssd','kevin@gmail.com');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra`
--

DROP TABLE IF EXISTS `compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compra` (
  `id_Compra` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `id_Proveedor` int(11) DEFAULT NULL,
  `id_Empleado` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_Compra`),
  KEY `id_Proveedor` (`id_Proveedor`),
  KEY `id_Empleado` (`id_Empleado`),
  CONSTRAINT `compra_ibfk_1` FOREIGN KEY (`id_Proveedor`) REFERENCES `proveedores` (`id_Proveedor`),
  CONSTRAINT `compra_ibfk_2` FOREIGN KEY (`id_Empleado`) REFERENCES `empleados` (`id_Empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra`
--

LOCK TABLES `compra` WRITE;
/*!40000 ALTER TABLE `compra` DISABLE KEYS */;
/*!40000 ALTER TABLE `compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_compra`
--

DROP TABLE IF EXISTS `detalle_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_compra` (
  `id_Detallecompra` int(11) NOT NULL AUTO_INCREMENT,
  `id_Compra` int(11) DEFAULT NULL,
  `id_Producto` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `existencia` int(11) DEFAULT NULL,
  `precio_unitario` double DEFAULT NULL,
  PRIMARY KEY (`id_Detallecompra`),
  KEY `id_Compra` (`id_Compra`),
  KEY `id_Producto` (`id_Producto`),
  CONSTRAINT `detalle_compra_ibfk_1` FOREIGN KEY (`id_Compra`) REFERENCES `compra` (`id_Compra`),
  CONSTRAINT `detalle_compra_ibfk_2` FOREIGN KEY (`id_Producto`) REFERENCES `producto` (`id_Producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_compra`
--

LOCK TABLES `detalle_compra` WRITE;
/*!40000 ALTER TABLE `detalle_compra` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_venta` (
  `id_detalleventa` int(11) NOT NULL AUTO_INCREMENT,
  `id_Detallecompra` int(11) DEFAULT NULL,
  `id_Venta` int(11) DEFAULT NULL,
  `precio_venta` double DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `total` double DEFAULT NULL,
  PRIMARY KEY (`id_detalleventa`),
  KEY `id_Detallecompra` (`id_Detallecompra`),
  KEY `id_Venta` (`id_Venta`),
  CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_Detallecompra`) REFERENCES `detalle_compra` (`id_Detallecompra`),
  CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_Venta`) REFERENCES `ventas` (`id_Venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
/*!40000 ALTER TABLE `detalle_venta` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados` (
  `id_Empleado` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(75) DEFAULT NULL,
  `apellido` varchar(45) DEFAULT NULL,
  `dui` varchar(10) DEFAULT NULL,
  `telefono` varchar(9) DEFAULT NULL,
  `direccion` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `clave` varchar(200) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL,
  `id_Usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_Empleado`),
  UNIQUE KEY `dui` (`dui`),
  KEY `id_Usuario` (`id_Usuario`),
  CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`id_Usuario`) REFERENCES `usuarios` (`id_Usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleados`
--

LOCK TABLES `empleados` WRITE;
/*!40000 ALTER TABLE `empleados` DISABLE KEYS */;
INSERT INTO `empleados` VALUES (6,'Kevin Alexander','Santos','26526262-6','7670-0965','Col Santa Gertrudis Av San Martin Pol 35','sl22001@ues.edu.sv','$2y$10$CmoC/YGoc7HpSQW33hhVBuMCRkJjV59NNJzaL59vuM6484QpnFuqO',1,9);
/*!40000 ALTER TABLE `empleados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto`
--

DROP TABLE IF EXISTS `producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto` (
  `id_Producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(75) DEFAULT NULL,
  `imagen` blob DEFAULT NULL,
  `descripcion` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL,
  `id_Categoria` int(11) DEFAULT NULL,
  `id_Medida` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_Producto`),
  KEY `id_Categoria` (`id_Categoria`),
  KEY `id_Medida` (`id_Medida`),
  CONSTRAINT `producto_ibfk_1` FOREIGN KEY (`id_Categoria`) REFERENCES `categoria` (`id_Categoria`),
  CONSTRAINT `producto_ibfk_2` FOREIGN KEY (`id_Medida`) REFERENCES `unidad_medida` (`id_Medida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto`
--

LOCK TABLES `producto` WRITE;
/*!40000 ALTER TABLE `producto` DISABLE KEYS */;
/*!40000 ALTER TABLE `producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id_Proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(75) DEFAULT NULL,
  `contac_referencia` varchar(100) DEFAULT NULL,
  `telefono` varchar(9) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_Proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recuperacion_clave`
--

DROP TABLE IF EXISTS `recuperacion_clave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recuperacion_clave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_Empleado` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `id_Empleado` (`id_Empleado`),
  CONSTRAINT `recuperacion_clave_ibfk_1` FOREIGN KEY (`id_Empleado`) REFERENCES `empleados` (`id_Empleado`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recuperacion_clave`
--

LOCK TABLES `recuperacion_clave` WRITE;
/*!40000 ALTER TABLE `recuperacion_clave` DISABLE KEYS */;
/*!40000 ALTER TABLE `recuperacion_clave` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidad_medida`
--

DROP TABLE IF EXISTS `unidad_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidad_medida` (
  `id_Medida` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(75) DEFAULT NULL,
  `simbolo` varchar(4) DEFAULT NULL,
  PRIMARY KEY (`id_Medida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidad_medida`
--

LOCK TABLES `unidad_medida` WRITE;
/*!40000 ALTER TABLE `unidad_medida` DISABLE KEYS */;
/*!40000 ALTER TABLE `unidad_medida` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_Usuario` int(11) NOT NULL AUTO_INCREMENT,
  `rol` varchar(20) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_Usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (9,'Administrador',1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `id_Venta` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date DEFAULT NULL,
  `id_Cliente` int(11) DEFAULT NULL,
  `id_Empleado` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_Venta`),
  KEY `id_Cliente` (`id_Cliente`),
  KEY `id_Empleado` (`id_Empleado`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_Cliente`) REFERENCES `clientes` (`id_Cliente`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_Empleado`) REFERENCES `empleados` (`id_Empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-30 13:29:57
