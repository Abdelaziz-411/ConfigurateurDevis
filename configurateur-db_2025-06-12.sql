-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 12 juin 2025 à 10:05
-- Version du serveur : 8.2.0
-- Version de PHP : 8.3.0

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

-- --------------------------------------------------------

--
-- Structure de la table `categories_options`
--

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

CREATE TABLE `devis` (
  `id` int NOT NULL,
  `has_vehicle` tinyint(1) DEFAULT NULL,
  `nom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `prenom` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `id_vehicule` int NOT NULL,
  `type_carrosserie` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kit` int DEFAULT NULL,
  `configuration` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `prix_ht` decimal(10,2) NOT NULL,
  `prix_ttc` decimal(10,2) NOT NULL,
  `statut` enum('nouveau','en_cours','traite') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'nouveau',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `devis`
--

INSERT INTO `devis` (`id`, `has_vehicle`, `nom`, `prenom`, `email`, `telephone`, `message`, `id_vehicule`, `type_carrosserie`, `id_kit`, `configuration`, `prix_ht`, `prix_ttc`, `statut`, `date_creation`) VALUES
(9, NULL, 'Khalifa', 'Abdelaziz', 'mtzm72727@gmail.com', '+33760682806', '', 9, 'L1H2', 1, 'Véhicule: Berlingo (Type: L1H2, Année: 2020)\nKit: Trafic (Prix HT: 4166,67 € HT)\nOptions:\n- Chauffage (Prix HT: 1250,00 € HT)\n', 5416.67, 6500.00, 'nouveau', '2025-06-12 06:37:11');

-- --------------------------------------------------------

--
-- Structure de la table `kits`
--

CREATE TABLE `kits` (
  `id` int NOT NULL,
  `nom` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kits`
--

INSERT INTO `kits` (`id`, `nom`, `description`, `prix`, `created_at`) VALUES
(1, 'Trafic', '', 0.00, '2025-05-27 08:14:22'),
(2, 'Kit Week-end', '', 0.00, '2025-05-27 12:18:15'),
(3, 'test', 'tft', 0.00, '2025-06-03 08:00:15'),
(4, 'Domaine de pommoran', 'FRF', 0.00, '2025-06-03 08:33:59');

-- --------------------------------------------------------

--
-- Structure de la table `kit_images`
--

CREATE TABLE `kit_images` (
  `id` int NOT NULL,
  `id_kit` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `kit_images`
--

INSERT INTO `kit_images` (`id`, `id_kit`, `image_path`, `ordre`) VALUES
(1, 1, '6835745ea57f5.jpg', 0),
(2, 1, '6835745ea7b8d.jpg', 0),
(4, 1, '6835745eab502.jpg', 0),
(5, 2, '6835ad878294c.jpg', 0),
(6, 3, '683eb3008a938.jpg', 0),
(7, 3, '683eb3008c44b.jpg', 0),
(8, 3, '683eb3008d988.jpg', 0),
(9, 4, '683eb377f267c_6835745ea57f5.jpg', 0);

-- --------------------------------------------------------

--
-- Structure de la table `kit_vehicule_compatibilite`
--

CREATE TABLE `kit_vehicule_compatibilite` (
  `id_kit` int NOT NULL,
  `type_carrosserie` varchar(10) NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `kit_vehicule_compatibilite`
--

INSERT INTO `kit_vehicule_compatibilite` (`id_kit`, `type_carrosserie`, `prix`) VALUES
(1, 'L2H1', 5000.00),
(2, 'L1H1', 1800.00),
(2, 'L2H1', 1000.00),
(3, 'L2H1', 2929.00),
(4, 'L1H1', 2000.00),
(4, 'L1H2', 0.00),
(4, 'L2H1', 0.00),
(4, 'L2H2', 0.00),
(4, 'L2H3', 0.00),
(4, 'L3H2', 0.00),
(4, 'L3H3', 0.00),
(4, 'L4H3', 0.00);

-- --------------------------------------------------------

--
-- Structure de la table `marques`
--

CREATE TABLE `marques` (
  `id` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `marques`
--

INSERT INTO `marques` (`id`, `nom`, `created_at`) VALUES
(1, 'Renault', '2025-06-04 09:04:41'),
(2, 'Peugeot', '2025-06-04 09:04:41'),
(3, 'Citroën', '2025-06-04 09:04:41'),
(4, 'Fiat', '2025-06-04 09:04:41'),
(5, 'Mercedes-Benz', '2025-06-04 09:04:41'),
(6, 'Volkswagen', '2025-06-04 09:04:41'),
(7, 'Ford', '2025-06-04 09:04:41'),
(8, 'Toyota', '2025-06-04 09:04:41'),
(9, 'Nissan', '2025-06-04 09:04:41'),
(10, 'Opel', '2025-06-04 09:04:41');

-- --------------------------------------------------------

--
-- Structure de la table `marque_images`
--

CREATE TABLE `marque_images` (
  `id` int NOT NULL,
  `id_marque` int DEFAULT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `marque_images`
--

INSERT INTO `marque_images` (`id`, `id_marque`, `image_path`) VALUES
(1, 3, '684022c66e88e_citroen-logo-0-1.png'),
(2, 2, '6840231a81d45_peugeot-logo-0-1.png');

-- --------------------------------------------------------

--
-- Structure de la table `modeles`
--

CREATE TABLE `modeles` (
  `id` int NOT NULL,
  `id_marque` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(10) DEFAULT NULL,
  `type_carrosserie` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `modeles`
--

INSERT INTO `modeles` (`id`, `id_marque`, `nom`, `created_at`, `status`, `type_carrosserie`) VALUES
(1, 1, 'Master', '2025-06-04 09:04:41', NULL, NULL),
(2, 1, 'Trafic', '2025-06-04 09:04:41', NULL, NULL),
(3, 1, 'Kangoo', '2025-06-04 09:04:41', NULL, NULL),
(4, 2, 'Boxer', '2025-06-04 09:04:41', NULL, NULL),
(5, 2, 'Expert', '2025-06-04 09:04:41', NULL, NULL),
(6, 2, 'Partner', '2025-06-04 09:04:41', NULL, NULL),
(7, 3, 'Jumper', '2025-06-04 09:04:41', NULL, NULL),
(8, 3, 'Dispatch', '2025-06-04 09:04:41', NULL, NULL),
(9, 3, 'Berlingo', '2025-06-04 09:04:41', 'L1H1', 'L1H1'),
(10, 5, 'Sprinter Fourgon', '2025-06-04 12:41:31', 'L4H3', 'L3H3');

-- --------------------------------------------------------

--
-- Structure de la table `modele_images`
--

CREATE TABLE `modele_images` (
  `id` int NOT NULL,
  `id_modele` int DEFAULT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `modele_images`
--

INSERT INTO `modele_images` (`id`, `id_modele`, `image_path`) VALUES
(1, 10, '68403efb3e18a_682efb2ecbe14.jpg'),
(2, 9, '68417557749bb_68359fe0c7f04.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `modele_statuts`
--

CREATE TABLE `modele_statuts` (
  `id` int NOT NULL,
  `id_modele` int NOT NULL,
  `statut` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `modele_statuts`
--

INSERT INTO `modele_statuts` (`id`, `id_modele`, `statut`) VALUES
(5, 9, 'L1H1'),
(6, 9, 'L2H1'),
(7, 10, 'L3H3'),
(8, 10, 'L4H3');

-- --------------------------------------------------------

--
-- Structure de la table `modele_type_carrosserie_compatibilite`
--

CREATE TABLE `modele_type_carrosserie_compatibilite` (
  `id` int NOT NULL,
  `id_modele` int NOT NULL,
  `type_carrosserie` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `options`
--

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

--
-- Déchargement des données de la table `options`
--

INSERT INTO `options` (`id`, `id_categorie`, `nom`, `description`, `prix`, `unite`, `created_at`, `modifiable`, `dimensions`, `ordre`) VALUES
(2, 5, 'Chauffage', '', 0.00, NULL, '2025-05-27 12:01:16', 1, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `option_categories`
--

CREATE TABLE `option_categories` (
  `id` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `option_images`
--

CREATE TABLE `option_images` (
  `id` int NOT NULL,
  `id_option` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `option_images`
--

INSERT INTO `option_images` (`id`, `id_option`, `image_path`, `ordre`) VALUES
(2, 2, '6835a98c84181.png', 0);

-- --------------------------------------------------------

--
-- Structure de la table `option_vehicule_compatibilite`
--

CREATE TABLE `option_vehicule_compatibilite` (
  `id_option` int NOT NULL,
  `type_carrosserie` varchar(10) NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `option_vehicule_compatibilite`
--

INSERT INTO `option_vehicule_compatibilite` (`id_option`, `type_carrosserie`, `prix`) VALUES
(2, 'L1H1', 0.00),
(2, 'L2H1', 1500.00),
(2, 'L2H2', 0.00),
(2, 'L3H2', 0.00),
(2, 'L3H3', 0.00),
(2, 'L4H3', 0.00);

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

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

CREATE TABLE `vehicle_images` (
  `id` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `ordre` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `vehicle_images`
--

INSERT INTO `vehicle_images` (`id`, `id_vehicule`, `image_path`, `ordre`) VALUES
(2, 1, '683585fee62f6.jpg', 0),
(3, 2, '68359fe0c7f04.jpg', 0);

-- --------------------------------------------------------

--
-- Structure de la table `vehicules`
--

CREATE TABLE `vehicules` (
  `id` int NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `type_carrosserie` varchar(10) DEFAULT NULL,
  `longueur` decimal(10,2) DEFAULT NULL,
  `hauteur` decimal(10,2) DEFAULT NULL,
  `id_marque` int DEFAULT NULL,
  `id_modele` int DEFAULT NULL,
  `annee` varchar(4) DEFAULT NULL,
  `marque_personnalisee` varchar(100) DEFAULT NULL,
  `modele_personnalise` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `vehicules`
--

INSERT INTO `vehicules` (`id`, `nom`, `description`, `type_carrosserie`, `longueur`, `hauteur`, `id_marque`, `id_modele`, `annee`, `marque_personnalisee`, `modele_personnalise`) VALUES
(1, 'L1H1', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'L2H1', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'L2H2', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'L3H2', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'L3H3', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'L4H3', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
  ADD PRIMARY KEY (`id_kit`,`type_carrosserie`),
  ADD KEY `idx_kit_type_carrosserie` (`type_carrosserie`);

--
-- Index pour la table `marques`
--
ALTER TABLE `marques`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `marque_images`
--
ALTER TABLE `marque_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_marque` (`id_marque`);

--
-- Index pour la table `modeles`
--
ALTER TABLE `modeles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_marque` (`id_marque`);

--
-- Index pour la table `modele_images`
--
ALTER TABLE `modele_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_modele` (`id_modele`);

--
-- Index pour la table `modele_statuts`
--
ALTER TABLE `modele_statuts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_modele` (`id_modele`,`statut`);

--
-- Index pour la table `modele_type_carrosserie_compatibilite`
--
ALTER TABLE `modele_type_carrosserie_compatibilite`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_modele` (`id_modele`,`type_carrosserie`);

--
-- Index pour la table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_categorie` (`id_categorie`);

--
-- Index pour la table `option_categories`
--
ALTER TABLE `option_categories`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id_option`,`type_carrosserie`),
  ADD KEY `idx_option_type_carrosserie` (`type_carrosserie`);

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
  ADD KEY `idx_nom` (`nom`),
  ADD KEY `id_marque` (`id_marque`),
  ADD KEY `id_modele` (`id_modele`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `kits`
--
ALTER TABLE `kits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `kit_images`
--
ALTER TABLE `kit_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `marques`
--
ALTER TABLE `marques`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `marque_images`
--
ALTER TABLE `marque_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `modeles`
--
ALTER TABLE `modeles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `modele_images`
--
ALTER TABLE `modele_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `modele_statuts`
--
ALTER TABLE `modele_statuts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `modele_type_carrosserie_compatibilite`
--
ALTER TABLE `modele_type_carrosserie_compatibilite`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `options`
--
ALTER TABLE `options`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `option_categories`
--
ALTER TABLE `option_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `option_images`
--
ALTER TABLE `option_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `devis_ibfk_2` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`),
  ADD CONSTRAINT `fk_devis_modele` FOREIGN KEY (`id_vehicule`) REFERENCES `modeles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `kit_images`
--
ALTER TABLE `kit_images`
  ADD CONSTRAINT `kit_images_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `kit_vehicule_compatibilite`
--
ALTER TABLE `kit_vehicule_compatibilite`
  ADD CONSTRAINT `kit_vehicule_compatibilite_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `marque_images`
--
ALTER TABLE `marque_images`
  ADD CONSTRAINT `marque_images_ibfk_1` FOREIGN KEY (`id_marque`) REFERENCES `marques` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modeles`
--
ALTER TABLE `modeles`
  ADD CONSTRAINT `modeles_ibfk_1` FOREIGN KEY (`id_marque`) REFERENCES `marques` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modele_images`
--
ALTER TABLE `modele_images`
  ADD CONSTRAINT `modele_images_ibfk_1` FOREIGN KEY (`id_modele`) REFERENCES `modeles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modele_statuts`
--
ALTER TABLE `modele_statuts`
  ADD CONSTRAINT `modele_statuts_ibfk_1` FOREIGN KEY (`id_modele`) REFERENCES `modeles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `modele_type_carrosserie_compatibilite`
--
ALTER TABLE `modele_type_carrosserie_compatibilite`
  ADD CONSTRAINT `modele_type_carrosserie_compatibilite_ibfk_1` FOREIGN KEY (`id_modele`) REFERENCES `modeles` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `option_vehicule_compatibilite_ibfk_1` FOREIGN KEY (`id_option`) REFERENCES `options` (`id`) ON DELETE CASCADE;

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

--
-- Contraintes pour la table `vehicules`
--
ALTER TABLE `vehicules`
  ADD CONSTRAINT `vehicules_ibfk_1` FOREIGN KEY (`id_marque`) REFERENCES `marques` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vehicules_ibfk_2` FOREIGN KEY (`id_modele`) REFERENCES `modeles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
