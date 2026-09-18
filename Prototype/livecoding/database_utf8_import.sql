-- MySQL dump 10.13  Distrib 8.4.9, for Win64 (x86_64)
--
-- Host: localhost    Database: cooking_app
-- ------------------------------------------------------
-- Server version	8.4.9

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `id_category` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  PRIMARY KEY (`id_category`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Entrée','Plats servis avant le plat principal'),(2,'Plat principal','Plats principaux'),(3,'Dessert','Plats sucrés servis après le repas');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chef`
--

DROP TABLE IF EXISTS `chef`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chef` (
  `id_chef` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_chef`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chef`
--

LOCK TABLES `chef` WRITE;
/*!40000 ALTER TABLE `chef` DISABLE KEYS */;
INSERT INTO `chef` VALUES (1,'Ahmed Alami','ahmed@gmail.com','123456','ahmed.jpg','2026-09-16 15:23:01'),(2,'Sara Amrani','sara@gmail.com','123456','sara.jpg','2026-09-16 15:23:01'),(3,'Yassine Bennani','yassine@gmail.com','123456','yassine.jpg','2026-09-16 15:23:01');
/*!40000 ALTER TABLE `chef` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment` (
  `id_comment` int NOT NULL AUTO_INCREMENT,
  `contenu` text NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_chef` int NOT NULL,
  `id_recipe` int NOT NULL,
  PRIMARY KEY (`id_comment`),
  KEY `id_chef` (`id_chef`),
  KEY `id_recipe` (`id_recipe`),
  CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`id_chef`) REFERENCES `chef` (`id_chef`),
  CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`id_recipe`) REFERENCES `recipe` (`id_recipe`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comment`
--

LOCK TABLES `comment` WRITE;
/*!40000 ALTER TABLE `comment` DISABLE KEYS */;
INSERT INTO `comment` VALUES (1,'Très bonne recette, facile à préparer.','2026-09-16 15:23:40',2,1),(2,'La recette est excellente.','2026-09-16 15:23:40',3,1),(3,'J’ai beaucoup aimé cette pizza.','2026-09-16 15:23:40',1,2);
/*!40000 ALTER TABLE `comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuisine`
--

DROP TABLE IF EXISTS `cuisine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuisine` (
  `id_cuisine` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_cuisine`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuisine`
--

LOCK TABLES `cuisine` WRITE;
/*!40000 ALTER TABLE `cuisine` DISABLE KEYS */;
INSERT INTO `cuisine` VALUES (1,'Marocaine','Cuisine traditionnelle marocaine','maroc.jpg'),(2,'Italienne','Cuisine italienne traditionnelle','italie.jpg'),(3,'Française','Cuisine française','france.jpg');
/*!40000 ALTER TABLE `cuisine` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorite`
--

DROP TABLE IF EXISTS `favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorite` (
  `id_favorite` int NOT NULL AUTO_INCREMENT,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_chef` int NOT NULL,
  `id_recipe` int NOT NULL,
  PRIMARY KEY (`id_favorite`),
  UNIQUE KEY `id_chef` (`id_chef`,`id_recipe`),
  KEY `id_recipe` (`id_recipe`),
  CONSTRAINT `favorite_ibfk_1` FOREIGN KEY (`id_chef`) REFERENCES `chef` (`id_chef`),
  CONSTRAINT `favorite_ibfk_2` FOREIGN KEY (`id_recipe`) REFERENCES `recipe` (`id_recipe`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorite`
--

LOCK TABLES `favorite` WRITE;
/*!40000 ALTER TABLE `favorite` DISABLE KEYS */;
INSERT INTO `favorite` VALUES (1,'2026-09-16 15:23:45',2,1),(2,'2026-09-16 15:23:45',3,1),(3,'2026-09-16 15:23:45',1,2),(4,'2026-09-16 15:23:45',3,3);
/*!40000 ALTER TABLE `favorite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rating`
--

DROP TABLE IF EXISTS `rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rating` (
  `id_rating` int NOT NULL AUTO_INCREMENT,
  `note` tinyint NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_chef` int NOT NULL,
  `id_recipe` int NOT NULL,
  PRIMARY KEY (`id_rating`),
  UNIQUE KEY `id_chef` (`id_chef`,`id_recipe`),
  KEY `id_recipe` (`id_recipe`),
  CONSTRAINT `rating_ibfk_1` FOREIGN KEY (`id_chef`) REFERENCES `chef` (`id_chef`),
  CONSTRAINT `rating_ibfk_2` FOREIGN KEY (`id_recipe`) REFERENCES `recipe` (`id_recipe`),
  CONSTRAINT `rating_chk_1` CHECK ((`note` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rating`
--

LOCK TABLES `rating` WRITE;
/*!40000 ALTER TABLE `rating` DISABLE KEYS */;
INSERT INTO `rating` VALUES (1,5,'2026-09-16 15:23:53',2,1),(2,4,'2026-09-16 15:23:53',3,1),(3,5,'2026-09-16 15:23:53',1,2),(4,4,'2026-09-16 15:23:53',3,3);
/*!40000 ALTER TABLE `rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe`
--

DROP TABLE IF EXISTS `recipe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe` (
  `id_recipe` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(150) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `ingredients` text NOT NULL,
  `instructions` text NOT NULL,
  `temps_preparation` int NOT NULL,
  `difficulte` enum('Facile','Moyenne','Difficile') NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_chef` int NOT NULL,
  `id_cuisine` int NOT NULL,
  `id_category` int NOT NULL,
  PRIMARY KEY (`id_recipe`),
  KEY `id_chef` (`id_chef`),
  KEY `id_cuisine` (`id_cuisine`),
  KEY `id_category` (`id_category`),
  CONSTRAINT `recipe_ibfk_1` FOREIGN KEY (`id_chef`) REFERENCES `chef` (`id_chef`),
  CONSTRAINT `recipe_ibfk_2` FOREIGN KEY (`id_cuisine`) REFERENCES `cuisine` (`id_cuisine`),
  CONSTRAINT `recipe_ibfk_3` FOREIGN KEY (`id_category`) REFERENCES `category` (`id_category`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe`
--

LOCK TABLES `recipe` WRITE;
/*!40000 ALTER TABLE `recipe` DISABLE KEYS */;
INSERT INTO `recipe` VALUES (1,'Tajine de poulet','Tajine marocain traditionnel au poulet','images/tajine.svg','Poulet, oignon, citron, olives, épices','Faire revenir les oignons, ajouter le poulet et les épices, puis cuire avec le citron et les olives.',60,'Moyenne','2026-09-16 15:23:34',1,1,2),(2,'Pizza Margherita','Pizza italienne classique','images/pizza.svg','Farine, tomate, mozzarella, basilic','Préparer la pâte, ajouter la sauce tomate et la mozzarella puis cuire au four.',40,'Facile','2026-09-16 15:23:34',2,2,2),(3,'Tiramisu','Dessert italien au café','images/Tiramisu.svg','Mascarpone, café, biscuits, cacao','Préparer la crème, tremper les biscuits dans le café et assembler les couches.',30,'Moyenne','2026-09-16 15:23:34',3,2,3);
/*!40000 ALTER TABLE `recipe` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-17  9:48:59
USE cooking_app;

ALTER TABLE recipe
DROP COLUMN image;

