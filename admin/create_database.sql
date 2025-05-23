-- Création de la base de données
CREATE DATABASE IF NOT EXISTS configurateur;
USE configurateur;

-- Table des véhicules
CREATE TABLE IF NOT EXISTS vehicules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    INDEX idx_nom (nom)
);

-- Table des images des véhicules
CREATE TABLE IF NOT EXISTS vehicle_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_vehicule INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
    INDEX idx_vehicule (id_vehicule)
);

-- Table des kits
CREATE TABLE IF NOT EXISTS kits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    id_vehicule INT NOT NULL,
    FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
    INDEX idx_nom (nom),
    INDEX idx_vehicule (id_vehicule)
);

-- Table des images des kits
CREATE TABLE IF NOT EXISTS kit_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kit INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE,
    INDEX idx_kit (id_kit)
);

-- Table des options
CREATE TABLE IF NOT EXISTS options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    id_vehicule INT NOT NULL,
    FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
    INDEX idx_nom (nom),
    INDEX idx_vehicule (id_vehicule)
);

-- Table des images des options
CREATE TABLE IF NOT EXISTS option_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_option INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE,
    INDEX idx_option (id_option)
);

-- Table des prix des kits par véhicule
CREATE TABLE IF NOT EXISTS kit_vehicule_prix (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kit INT NOT NULL,
    id_vehicule INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE,
    FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_kit_vehicule (id_kit, id_vehicule),
    INDEX idx_kit (id_kit),
    INDEX idx_vehicule (id_vehicule)
);

-- Table des options par kit
CREATE TABLE IF NOT EXISTS kit_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kit INT NOT NULL,
    id_option INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE,
    FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE,
    UNIQUE KEY unique_kit_option (id_kit, id_option),
    INDEX idx_kit (id_kit),
    INDEX idx_option (id_option)
);

-- Table des administrateurs
CREATE TABLE IF NOT EXISTS administrateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    INDEX idx_email (email)
);

-- Insertion d'un administrateur par défaut (mot de passe: admin123)
INSERT INTO administrateurs (nom, email, mot_de_passe) 
VALUES ('Administrateur', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE id=id;

-- Créer la table des devis
CREATE TABLE IF NOT EXISTS devis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    message TEXT,
    id_vehicule INT NOT NULL,
    id_kit INT,
    configuration TEXT NOT NULL,
    prix_ht DECIMAL(10,2) NOT NULL,
    prix_ttc DECIMAL(10,2) NOT NULL,
    statut ENUM('nouveau', 'en_cours', 'traite') NOT NULL DEFAULT 'nouveau',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vehicule) REFERENCES vehicules(id),
    FOREIGN KEY (id_kit) REFERENCES kits(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 