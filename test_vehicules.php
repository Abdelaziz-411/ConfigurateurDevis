<?php
require 'config.php';

try {
    $stmt = $pdo->query('SELECT * FROM vehicules');
    $vehicules = $stmt->fetchAll();
    
    echo "<h2>Liste des véhicules dans la base de données :</h2>";
    if (count($vehicules) > 0) {
        echo "<ul>";
        foreach ($vehicules as $vehicule) {
            echo "<li>" . htmlspecialchars($vehicule['nom']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Aucun véhicule trouvé dans la base de données.</p>";
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 