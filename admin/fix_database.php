<?php
require_once '../config.php';

try {
    // Tables d'authentification
    // 1. Créer la table des rôles
    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        libelle VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insérer les rôles de base s'ils n'existent pas
    $pdo->exec("INSERT IGNORE INTO roles (libelle) VALUES ('admin'), ('user')");

    // 2. Créer la table des statuts
    $pdo->exec("CREATE TABLE IF NOT EXISTS users_statuts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        libelle VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insérer les statuts de base s'ils n'existent pas
    $pdo->exec("INSERT IGNORE INTO users_statuts (libelle) VALUES ('actif'), ('inactif')");

    // 3. Créer la table des utilisateurs
    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        mot_de_passe VARCHAR(255) NOT NULL,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        role_id INT NOT NULL,
        statut_id INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id),
        FOREIGN KEY (statut_id) REFERENCES users_statuts(id)
    )");

    // Tables de données
    // 4. Vérifier/Créer la table des véhicules
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Vérifier/Créer la table des images de véhicules
    $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_vehicule INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE
    )");

    // 6. Vérifier/Créer la table des kits
    $pdo->exec("CREATE TABLE IF NOT EXISTS kits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Vérifier/Créer la table des images de kits
    $pdo->exec("CREATE TABLE IF NOT EXISTS kit_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_kit INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE
    )");

    // 8. Vérifier/Créer la table de compatibilité kit-véhicule
    $pdo->exec("CREATE TABLE IF NOT EXISTS kit_vehicule_compatibilite (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_kit INT NOT NULL,
        id_vehicule INT NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE,
        FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
        UNIQUE KEY unique_kit_vehicule (id_kit, id_vehicule)
    )");

    // 9. Vérifier/Créer la table des options
    $pdo->exec("CREATE TABLE IF NOT EXISTS options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 10. Vérifier/Créer la table des images d'options
    $pdo->exec("CREATE TABLE IF NOT EXISTS option_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_option INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE
    )");

    // 11. Vérifier/Créer la table de compatibilité option-véhicule
    $pdo->exec("CREATE TABLE IF NOT EXISTS option_vehicule_compatibilite (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_option INT NOT NULL,
        id_vehicule INT NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE,
        FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE,
        UNIQUE KEY unique_option_vehicule (id_option, id_vehicule)
    )");

    // Créer les dossiers d'images s'ils n'existent pas
    $directories = ['vehicules', 'kits', 'options'];
    foreach ($directories as $dir) {
        $path = "../images/{$dir}";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    // Créer l'administrateur par défaut s'il n'existe pas
    $admin_email = 'admin@admin.com';
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$admin_email]);
    
    if ($stmt->rowCount() == 0) {
        // Récupérer l'ID du rôle admin
        $stmt = $pdo->query("SELECT id FROM roles WHERE libelle = 'admin'");
        $role_admin_id = $stmt->fetchColumn();

        // Récupérer l'ID du statut actif
        $stmt = $pdo->query("SELECT id FROM users_statuts WHERE libelle = 'actif'");
        $statut_actif_id = $stmt->fetchColumn();

        // Créer l'administrateur
        $admin_password = 'admin123';
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, role_id, statut_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$admin_email, $hashed_password, 'Administrateur', 'Principal', $role_admin_id, $statut_actif_id]);
        
        echo "<div class='alert alert-info'>Compte administrateur créé :<br>";
        echo "Email : " . htmlspecialchars($admin_email) . "<br>";
        echo "Mot de passe : " . htmlspecialchars($admin_password) . "</div>";
    }

    echo "<div class='alert alert-success'>Structure de la base de données vérifiée et corrigée avec succès.</div>";

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
} 