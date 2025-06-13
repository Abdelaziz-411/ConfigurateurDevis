<?php
require_once 'config.php';

try {
    // Lire le contenu du fichier SQL
    $sql = file_get_contents('update_db_step2_marques_modeles.sql');
    
    // Exécuter les requêtes SQL
    $pdo->exec($sql);
    
    echo "Les tables ont été créées avec succès !";
} catch (PDOException $e) {
    echo "Erreur lors de l'exécution du fichier SQL : " . $e->getMessage();
} 