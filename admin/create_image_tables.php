<?php
require '../config.php';

try {
    // Création de la table vehicle_images
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_images (
        id INT NOT NULL AUTO_INCREMENT,
        id_vehicule INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Création de la table kit_images
    $pdo->exec("CREATE TABLE IF NOT EXISTS kit_images (
        id INT NOT NULL AUTO_INCREMENT,
        id_kit INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Création de la table option_images
    $pdo->exec("CREATE TABLE IF NOT EXISTS option_images (
        id INT NOT NULL AUTO_INCREMENT,
        id_option INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    echo "Les tables d'images ont été créées avec succès !";
} catch (PDOException $e) {
    die("Erreur lors de la création des tables : " . $e->getMessage());
} 