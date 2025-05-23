<?php
require '../config.php';

try {
    // Lire le fichier SQL
    $sql = file_get_contents('update_structure.sql');
    
    // Exécuter les requêtes SQL
    $pdo->exec($sql);
    
    echo "La structure de la base de données a été mise à jour avec succès.";
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour de la structure : " . $e->getMessage());
} 