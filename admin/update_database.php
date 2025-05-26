<?php
require '../config.php';

try {
    // Ajouter la colonne has_vehicle à la table devis
    $pdo->exec("ALTER TABLE devis ADD COLUMN has_vehicle BOOLEAN DEFAULT NULL AFTER id");
    
    echo "La colonne has_vehicle a été ajoutée avec succès à la table devis.";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "La colonne has_vehicle existe déjà.";
    } else {
        echo "Erreur : " . $e->getMessage();
    }
} 