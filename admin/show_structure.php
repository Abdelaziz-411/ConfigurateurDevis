<?php
require '../config.php';

try {
    // Récupérer la liste des tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>Structure de la base de données</h1>";
    
    foreach ($tables as $table) {
        echo "<h2>Table : $table</h2>";
        
        // Structure de la table
        echo "<h3>Structure :</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Clés étrangères
        echo "<h3>Clés étrangères :</h3>";
        $foreignKeys = $pdo->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$table'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($foreignKeys)) {
            echo "<pre>";
            print_r($foreignKeys);
            echo "</pre>";
        } else {
            echo "<p>Aucune clé étrangère</p>";
        }
        
        // Index
        echo "<h3>Index :</h3>";
        $indexes = $pdo->query("SHOW INDEX FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($indexes);
        echo "</pre>";
        
        echo "<hr>";
    }
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
} 