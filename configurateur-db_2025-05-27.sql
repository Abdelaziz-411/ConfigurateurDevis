-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 27 mai 2025 à 09:11
-- Version du serveur : 8.2.0
-- Version de PHP : 8.3.0

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `configurateur`
--
CREATE DATABASE IF NOT EXISTS `configurateur` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `configurateur`;

-- --------------------------------------------------------

--
-- Structure de la table `categories_options`
--

DROP TABLE IF EXISTS `categories_options`;
CREATE TABLE `categories_options` (
  `id` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text,
  `ordre` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `categories_options`
--

INSERT INTO `categories_options` (`id`, `nom`, `description`, `ordre`, `created_at`, `updated_at`) VALUES
(1, 'Ouvertures', 'Options liées aux ouvertures du véhicule', 1, '2025-05-27 07:31:57', '2025-05-27 07:31:57'),
(2, 'Isolation', 'Options d\'isolation du véhicule', 2, '2025-05-27 07:31:57', '2025-05-27 07:31:57'),
(3, 'Habillage', 'Options d\'habillage intérieur', 3, '2025-05-27 07:31:57', '2025-05-27 07:31:57'),
(4, 'Finitions', 'Options de finition', 4, '2025-05-27 07:31:57', '2025-05-27 07:31:57'),
(5, 'Marchandises', 'Équipements et marchandises', 5, '2025-05-27 07:31:57', '2025-05-27 07:31:57');

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

DROP TABLE IF EXISTS `devis`;
CREATE TABLE `devis` (
  `id` int NOT NULL,
  `has_vehicle` tinyint(1) DEFAULT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `id_vehicule` int NOT NULL,
  `id_kit` int DEFAULT NULL,
  `configuration` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `prix_ht` decimal(10,2) NOT NULL,
  `prix_ttc` decimal(10,2) NOT NULL,
  `statut` enum('nouveau','en_cours','traite') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'nouveau',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `kits`
--

DROP TABLE IF EXISTS `kits`;
CREATE TABLE `kits` (
  `id` int NOT NULL,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `kit_images`
--

DROP TABLE IF EXISTS `kit_images`;
CREATE TABLE `kit_images` (
  `id` int NOT NULL,
  `id_kit` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `kit_vehicule_compatibilite`
--

DROP TABLE IF EXISTS `kit_vehicule_compatibilite`;
CREATE TABLE `kit_vehicule_compatibilite` (
  `id_kit` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `options`
--

DROP TABLE IF EXISTS `options`;
CREATE TABLE `options` (
  `id` int NOT NULL,
  `id_categorie` int DEFAULT NULL,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unite` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modifiable` tinyint(1) DEFAULT '1',
  `dimensions` json DEFAULT NULL,
  `ordre` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `option_images`
--

DROP TABLE IF EXISTS `option_images`;
CREATE TABLE `option_images` (
  `id` int NOT NULL,
  `id_option` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `option_vehicule_compatibilite`
--

DROP TABLE IF EXISTS `option_vehicule_compatibilite`;
CREATE TABLE `option_vehicule_compatibilite` (
  `id_option` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `libelle`, `created_at`) VALUES
(1, 'admin', '2025-05-23 07:13:03'),
(2, 'user', '2025-05-23 07:13:03');

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` text NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users_statuts`
--

DROP TABLE IF EXISTS `users_statuts`;
CREATE TABLE `users_statuts` (
  `id` int NOT NULL,
  `libelle` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users_statuts`
--

INSERT INTO `users_statuts` (`id`, `libelle`, `created_at`) VALUES
(1, 'actif', '2025-05-23 07:13:03'),
(2, 'inactif', '2025-05-23 07:13:03');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
  `id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `derniere_connexion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `email`, `mot_de_passe`, `nom`, `role`, `date_creation`, `derniere_connexion`) VALUES
(1, 'admin@example.com', '$2y$10$9w0P9Z8vvNu1HGF5pQZKqOUzykJoEK5cL0msIqvzdvIqrotoKpnai', 'Administrateur', 'admin', '2025-05-27 07:31:57', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `vehicle_images`
--

DROP TABLE IF EXISTS `vehicle_images`;
CREATE TABLE `vehicle_images` (
  `id` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `vehicules`
--

DROP TABLE IF EXISTS `vehicules`;
CREATE TABLE `vehicules` (
  `id` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `longueur` decimal(10,2) DEFAULT NULL,
  `hauteur` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `vehicules`
--

INSERT INTO `vehicules` (`id`, `nom`, `description`, `longueur`, `hauteur`) VALUES
(1, 'L1H1', '', NULL, NULL),
(2, 'L2H1', '', NULL, NULL),
(3, 'L2H2', '', NULL, NULL),
(4, 'L3H2', '', NULL, NULL),
(5, 'L3H3', '', NULL, NULL),
(6, 'L4H3', '', NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categories_options`
--
ALTER TABLE `categories_options`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_vehicule` (`id_vehicule`),
  ADD KEY `id_kit` (`id_kit`);

--
-- Index pour la table `kits`
--
ALTER TABLE `kits`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `kit_images`
--
ALTER TABLE `kit_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kit` (`id_kit`);

--
-- Index pour la table `kit_vehicule_compatibilite`
--
ALTER TABLE `kit_vehicule_compatibilite`
  ADD PRIMARY KEY (`id_kit`,`id_vehicule`),
  ADD KEY `id_vehicule` (`id_vehicule`);

--
-- Index pour la table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categorie` (`id_categorie`);

--
-- Index pour la table `option_images`
--
ALTER TABLE `option_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_option` (`id_option`);

--
-- Index pour la table `option_vehicule_compatibilite`
--
ALTER TABLE `option_vehicule_compatibilite`
  ADD PRIMARY KEY (`id_option`,`id_vehicule`),
  ADD KEY `id_vehicule` (`id_vehicule`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libelle` (`libelle`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users_statuts`
--
ALTER TABLE `users_statuts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libelle` (`libelle`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_vehicule` (`id_vehicule`);

--
-- Index pour la table `vehicules`
--
ALTER TABLE `vehicules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nom` (`nom`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categories_options`
--
ALTER TABLE `categories_options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `kits`
--
ALTER TABLE `kits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `kit_images`
--
ALTER TABLE `kit_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `option_images`
--
ALTER TABLE `option_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `users_statuts`
--
ALTER TABLE `users_statuts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `vehicules`
--
ALTER TABLE `vehicules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `devis_ibfk_1` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`),
  ADD CONSTRAINT `devis_ibfk_2` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`);

--
-- Contraintes pour la table `kit_images`
--
ALTER TABLE `kit_images`
  ADD CONSTRAINT `kit_images_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `kit_vehicule_compatibilite`
--
ALTER TABLE `kit_vehicule_compatibilite`
  ADD CONSTRAINT `kit_vehicule_compatibilite_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kit_vehicule_compatibilite_ibfk_2` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_ibfk_1` FOREIGN KEY (`id_categorie`) REFERENCES `categories_options` (`id`);

--
-- Contraintes pour la table `option_images`
--
ALTER TABLE `option_images`
  ADD CONSTRAINT `option_images_ibfk_1` FOREIGN KEY (`id_option`) REFERENCES `options` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `option_vehicule_compatibilite`
--
ALTER TABLE `option_vehicule_compatibilite`
  ADD CONSTRAINT `option_vehicule_compatibilite_ibfk_1` FOREIGN KEY (`id_option`) REFERENCES `options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `option_vehicule_compatibilite_ibfk_2` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `vehicle_images`
--
ALTER TABLE `vehicle_images`
  ADD CONSTRAINT `vehicle_images_ibfk_1` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
