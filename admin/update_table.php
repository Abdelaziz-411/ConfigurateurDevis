<?php
require '../config.php';

try {
    // 1. Afficher la structure actuelle de la table
    echo "<h3>Structure actuelle de la table :</h3>";
    $result = $pdo->query("SHOW CREATE TABLE kit_vehicule_compatibilite");
    $row = $result->fetch();
    echo "<pre>" . print_r($row, true) . "</pre>";
    
    // 2. Supprimer et recréer la table
    $pdo->exec("DROP TABLE IF EXISTS kit_vehicule_compatibilite");
    $pdo->exec("CREATE TABLE kit_vehicule_compatibilite (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_kit INT NOT NULL,
        id_vehicule INT NOT NULL,
        prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE,
        FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
        UNIQUE KEY unique_kit_vehicule (id_kit, id_vehicule)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    
    // 3. Vérifier la nouvelle structure
    echo "<h3>Nouvelle structure de la table :</h3>";
    $result = $pdo->query("SHOW CREATE TABLE kit_vehicule_compatibilite");
    $row = $result->fetch();
    echo "<pre>" . print_r($row, true) . "</pre>";
    
    echo "<p style='color: green;'>Table mise à jour avec succès !</p>";
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la table : " . $e->getMessage());
}
?> 