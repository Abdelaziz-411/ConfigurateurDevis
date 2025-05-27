-- Structure de la base de données
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Création de la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `configurateur` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `configurateur`;

-- Suppression des tables existantes (dans l'ordre inverse des dépendances)
DROP TABLE IF EXISTS `devis`;
DROP TABLE IF EXISTS `kit_images`;
DROP TABLE IF EXISTS `kit_options`;
DROP TABLE IF EXISTS `kit_vehicule_compatibilite`;
DROP TABLE IF EXISTS `kits`;
DROP TABLE IF EXISTS `option_images`;
DROP TABLE IF EXISTS `option_vehicule_compatibilite`;
DROP TABLE IF EXISTS `options`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `utilisateurs`;
DROP TABLE IF EXISTS `vehicle_images`;
DROP TABLE IF EXISTS `vehicules`;
DROP TABLE IF EXISTS `categories_options`;

-- Table `categories_options` (table indépendante)
CREATE TABLE `categories_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `ordre` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `vehicules` (table indépendante)
CREATE TABLE `vehicules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `longueur` decimal(10,2) DEFAULT NULL,
  `hauteur` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `vehicle_images` (dépend de vehicules)
CREATE TABLE `vehicle_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_vehicule` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_vehicule` (`id_vehicule`),
  CONSTRAINT `vehicle_images_ibfk_1` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `kits` (table indépendante)
CREATE TABLE `kits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `kit_images` (dépend de kits)
CREATE TABLE `kit_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_kit` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_kit` (`id_kit`),
  CONSTRAINT `kit_images_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `kit_vehicule_compatibilite` (table de liaison)
CREATE TABLE `kit_vehicule_compatibilite` (
  `id_kit` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_kit`,`id_vehicule`),
  KEY `id_vehicule` (`id_vehicule`),
  CONSTRAINT `kit_vehicule_compatibilite_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kit_vehicule_compatibilite_ibfk_2` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `options` (dépend de categories_options)
CREATE TABLE `options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_categorie` int DEFAULT NULL,
  `nom` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unite` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiable` tinyint(1) DEFAULT '1',
  `dimensions` json DEFAULT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_categorie` (`id_categorie`),
  CONSTRAINT `options_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories_options` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `option_images` (dépend de options)
CREATE TABLE `option_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_option` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_option` (`id_option`),
  CONSTRAINT `option_images_ibfk_1` FOREIGN KEY (`id_option`) REFERENCES `options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `option_vehicule_compatibilite` (table de liaison)
CREATE TABLE `option_vehicule_compatibilite` (
  `id_option` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_option`,`id_vehicule`),
  KEY `id_vehicule` (`id_vehicule`),
  CONSTRAINT `option_vehicule_compatibilite_ibfk_1` FOREIGN KEY (`id_option`) REFERENCES `options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `option_vehicule_compatibilite_ibfk_2` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `utilisateurs` (table indépendante)
CREATE TABLE `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `sessions` (table indépendante)
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` text NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table `devis` (dépend de vehicules et kits)
CREATE TABLE `devis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `has_vehicle` tinyint(1) DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `id_vehicule` int NOT NULL,
  `id_kit` int DEFAULT NULL,
  `configuration` text COLLATE utf8mb4_general_ci NOT NULL,
  `prix_ht` decimal(10,2) NOT NULL,
  `prix_ttc` decimal(10,2) NOT NULL,
  `statut` enum('nouveau','en_cours','traite') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'nouveau',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_vehicule` (`id_vehicule`),
  KEY `id_kit` (`id_kit`),
  CONSTRAINT `devis_ibfk_1` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`),
  CONSTRAINT `devis_ibfk_2` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données de base
INSERT INTO `utilisateurs` (`email`, `mot_de_passe`, `nom`, `role`) VALUES
('admin@example.com', '$2y$10$KnGh0Ji4l2FUgUfCDkZQ/epr4oIUpJim8gPC/yquDpH75GS1rF9Gq', 'Administrateur', 'admin');

INSERT INTO `categories_options` (`nom`, `description`, `ordre`) VALUES
('Ouvertures', 'Options liées aux ouvertures du véhicule', 1),
('Isolation', 'Options d''isolation du véhicule', 2),
('Habillage', 'Options d''habillage intérieur', 3),
('Finitions', 'Options de finition', 4),
('Marchandises', 'Équipements et marchandises', 5);

INSERT INTO `vehicules` (`nom`, `description`) VALUES
('L1H1', ''),
('L2H1', ''),
('L2H2', ''),
('L3H2', ''),
('L3H3', ''),
('L4H3', '');

COMMIT; 