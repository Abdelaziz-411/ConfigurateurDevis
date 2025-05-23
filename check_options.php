<?php
require 'config.php';

try {
    // Vérifier si la table options existe
    $tables = $pdo->query("SHOW TABLES LIKE 'options'")->fetchAll();
    
    if (empty($tables)) {
        // Créer la table si elle n'existe pas
        $pdo->exec("CREATE TABLE options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            prix DECIMAL(10,2) NOT NULL
        )");
        echo "Table 'options' créée avec succès.<br>";
        
        // Ajouter quelques options par défaut
        $defaultOptions = [
            ['Isolation renforcée', 450],
            ['Panneau solaire 100W', 299],
            ['Kit éclairage LED', 150],
            ['Convertisseur 220V', 200],
            ['Réservoir d\'eau 50L', 180],
            ['Batterie auxiliaire', 350]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO options (nom, prix) VALUES (?, ?)");
        foreach ($defaultOptions as $option) {
            $stmt->execute($option);
        }
        echo "Options par défaut ajoutées.<br>";
    } else {
        // Afficher les options existantes
        echo "<h2>Options existantes :</h2>";
        $options = $pdo->query("SELECT * FROM options ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($options);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
} 