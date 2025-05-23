<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Test de connexion à la base de données<br>";
echo "PHP version: " . phpversion() . "<br>";

try {
    require 'config.php';
    echo "Fichier config.php chargé avec succès<br>";
    
    // Afficher les paramètres de connexion (attention à ne pas faire cela en production)
    echo "Host: $host<br>";
    echo "Database: $db<br>";
    echo "User: $user<br>";
    
    // Test de la connexion
    $query = $pdo->query('SELECT 1');
    echo "Connexion à la base de données réussie !<br>";
    
    // Test des tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables dans la base de données:<br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "<br>";
    echo "Trace:<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} 