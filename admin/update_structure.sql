-- Supprimer les anciennes tables si elles existent
DROP TABLE IF EXISTS `vehicle_images`;
DROP TABLE IF EXISTS `kit_images`;
DROP TABLE IF EXISTS `option_images`;
DROP TABLE IF EXISTS `kit_options`;

-- Créer la table des images des véhicules
CREATE TABLE `vehicle_images` (
    `id` int NOT NULL AUTO_INCREMENT,
    `id_vehicule` int NOT NULL,
    `image_path` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_vehicule` (`id_vehicule`),
    CONSTRAINT `vehicle_images_ibfk_1` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Créer la table des images des kits
CREATE TABLE `kit_images` (
    `id` int NOT NULL AUTO_INCREMENT,
    `id_kit` int NOT NULL,
    `image_path` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_kit` (`id_kit`),
    CONSTRAINT `kit_images_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Créer la table des images des options
CREATE TABLE `option_images` (
    `id` int NOT NULL AUTO_INCREMENT,
    `id_option` int NOT NULL,
    `image_path` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_option` (`id_option`),
    CONSTRAINT `option_images_ibfk_1` FOREIGN KEY (`id_option`) REFERENCES `options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Créer la table de compatibilité des options avec les kits
CREATE TABLE `kit_options` (
    `id` int NOT NULL AUTO_INCREMENT,
    `id_kit` int NOT NULL,
    `id_option` int NOT NULL,
    `prix` decimal(10,2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `kit_option_unique` (`id_kit`,`id_option`),
    KEY `id_option` (`id_option`),
    CONSTRAINT `kit_options_ibfk_1` FOREIGN KEY (`id_kit`) REFERENCES `kits` (`id`) ON DELETE CASCADE,
    CONSTRAINT `kit_options_ibfk_2` FOREIGN KEY (`id_option`) REFERENCES `options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Mettre à jour les noms de colonnes dans kit_vehicule_prix
ALTER TABLE `kit_vehicule_prix` 
    CHANGE `kit_id` `id_kit` int NOT NULL,
    CHANGE `vehicule_id` `id_vehicule` int NOT NULL;

-- Supprimer les contraintes existantes
ALTER TABLE kits DROP FOREIGN KEY kits_ibfk_1;
ALTER TABLE options DROP FOREIGN KEY options_ibfk_1;

-- Supprimer les colonnes id_vehicule des tables kits et options
ALTER TABLE kits DROP COLUMN id_vehicule;
ALTER TABLE options DROP COLUMN id_vehicule;

-- Créer la table de compatibilité des kits avec les véhicules
CREATE TABLE IF NOT EXISTS kit_vehicule_compatibilite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kit INT NOT NULL,
    id_vehicule INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE,
    FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_kit_vehicule (id_kit, id_vehicule),
    INDEX idx_kit (id_kit),
    INDEX idx_vehicule (id_vehicule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Créer la table de compatibilité des options avec les véhicules
CREATE TABLE IF NOT EXISTS option_vehicule_compatibilite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_option INT NOT NULL,
    id_vehicule INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE,
    FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_option_vehicule (id_option, id_vehicule),
    INDEX idx_option (id_option),
    INDEX idx_vehicule (id_vehicule)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrer les données existantes
INSERT INTO kit_vehicule_compatibilite (id_kit, id_vehicule, prix)
SELECT id, id_vehicule, prix FROM kits;

INSERT INTO option_vehicule_compatibilite (id_option, id_vehicule, prix)
SELECT id, id_vehicule, prix FROM options; 