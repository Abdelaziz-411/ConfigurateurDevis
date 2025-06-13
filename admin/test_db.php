<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=configurateur;charset=utf8mb4",
        "root",
        "root",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // Vérifier la table kit_vehicule_compatibilite
    $stmt = $pdo->query("SHOW COLUMNS FROM kit_vehicule_compatibilite");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colonnes de kit_vehicule_compatibilite : <pre>" . print_r($columns, true) . "</pre>";
    
    // Vérifier les données
    $stmt = $pdo->query("SELECT * FROM kit_vehicule_compatibilite LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Données de kit_vehicule_compatibilite : <pre>" . print_r($data, true) . "</pre>";
    
    // Vérifier la table kits
    $stmt = $pdo->query("SHOW COLUMNS FROM kits");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colonnes de la table kits : <pre>" . print_r($columns, true) . "</pre>";
    
    // Vérifier les données des kits
    $stmt = $pdo->query("SELECT * FROM kits LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Données des kits : <pre>" . print_r($data, true) . "</pre>";
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
