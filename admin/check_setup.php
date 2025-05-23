<?php
require '../config.php';

function createDirectory($path) {
    if (!file_exists($path)) {
        if (mkdir($path, 0777, true)) {
            echo "<div style='color: green;'>✓ Dossier créé : $path</div>";
        } else {
            echo "<div style='color: red;'>✗ Erreur lors de la création du dossier : $path</div>";
        }
    } else {
        echo "<div style='color: blue;'>ℹ Dossier existe déjà : $path</div>";
    }
}

function createDefaultImage($path) {
    if (!file_exists($path)) {
        // Créer une image par défaut simple
        $image = imagecreatetruecolor(800, 600);
        $bg = imagecolorallocate($image, 240, 240, 240);
        $textcolor = imagecolorallocate($image, 100, 100, 100);
        
        imagefill($image, 0, 0, $bg);
        imagestring($image, 5, 300, 280, 'Image par défaut', $textcolor);
        
        imagejpeg($image, $path, 90);
        imagedestroy($image);
        
        echo "<div style='color: green;'>✓ Image par défaut créée : $path</div>";
    } else {
        echo "<div style='color: blue;'>ℹ Image par défaut existe déjà : $path</div>";
    }
}

function createImageTables($pdo) {
    try {
        // Table vehicle_images
        $pdo->exec("CREATE TABLE IF NOT EXISTS vehicle_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_vehicule INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_vehicule) REFERENCES vehicules(id) ON DELETE CASCADE
        )");
        echo "<div style='color: green;'>✓ Table vehicle_images créée/vérifiée</div>";

        // Table kit_images
        $pdo->exec("CREATE TABLE IF NOT EXISTS kit_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_kit INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE
        )");
        echo "<div style='color: green;'>✓ Table kit_images créée/vérifiée</div>";

        // Table option_images
        $pdo->exec("CREATE TABLE IF NOT EXISTS option_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_option INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE
        )");
        echo "<div style='color: green;'>✓ Table option_images créée/vérifiée</div>";

    } catch (PDOException $e) {
        echo "<div style='color: red;'>✗ Erreur lors de la création des tables : " . $e->getMessage() . "</div>";
    }
}

// Vérification et création des dossiers
$baseDir = __DIR__ . '/../images';
createDirectory($baseDir);
createDirectory($baseDir . '/vehicules');
createDirectory($baseDir . '/kits');
createDirectory($baseDir . '/options');

// Création des images par défaut
createDefaultImage($baseDir . '/vehicules/default.jpg');
createDefaultImage($baseDir . '/kits/default.jpg');
createDefaultImage($baseDir . '/options/default.jpg');

// Création des tables
createImageTables($pdo);

echo "<div style='margin-top: 20px; color: green;'>✓ Vérification terminée !</div>";
?> 