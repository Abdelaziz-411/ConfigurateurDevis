<?php
require '../config.php';

// Création de la table des rôles
$pdo->exec("CREATE TABLE IF NOT EXISTS roles (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
)");

// Création de la table des statuts
$pdo->exec("CREATE TABLE IF NOT EXISTS users_statuts (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE
)");

// Création de la table des utilisateurs
$pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut_id INT NOT NULL DEFAULT 1,
    role_id INT NOT NULL DEFAULT 2,
    FOREIGN KEY (statut_id) REFERENCES users_statuts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
)");

// Insertion des rôles par défaut
$roles = [
    ['libelle' => 'admin'],
    ['libelle' => 'utilisateur'],
    ['libelle' => 'modérateur']
];

$stmt = $pdo->prepare("INSERT IGNORE INTO roles (libelle) VALUES (:libelle)");
foreach ($roles as $role) {
    $stmt->execute($role);
}

// Insertion des statuts par défaut
$statuts = [
    ['libelle' => 'actif'],
    ['libelle' => 'inactif'],
    ['libelle' => 'en cours']
];

$stmt = $pdo->prepare("INSERT IGNORE INTO users_statuts (libelle) VALUES (:libelle)");
foreach ($statuts as $statut) {
    $stmt->execute($statut);
}

// Création d'un compte admin par défaut
$admin = [
    'nom' => 'admin',
    'email' => 'admin@admin.com',
    'mot_de_passe' => password_hash('admin123', PASSWORD_DEFAULT),
    'statut_id' => 1, // actif
    'role_id' => 1 // admin
];

try {
    $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, statut_id, role_id) 
                          VALUES (:nom, :email, :mot_de_passe, :statut_id, :role_id)");
    $stmt->execute($admin);
    echo "Base de données initialisée avec succès !<br>";
    echo "Compte admin créé :<br>";
    echo "Email : admin@admin.com<br>";
    echo "Mot de passe : admin123";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Code d'erreur pour duplicate entry
        echo "Le compte admin existe déjà.";
    } else {
        echo "Erreur : " . $e->getMessage();
    }
} 