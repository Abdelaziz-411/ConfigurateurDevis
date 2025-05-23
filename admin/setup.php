<?php
require '../config.php';

// Fonction pour créer un dossier s'il n'existe pas
function createDirectory($path) {
    if (!file_exists($path)) {
        if (mkdir($path, 0777, true)) {
            echo "✓ Dossier créé : $path<br>";
        } else {
            echo "✗ Erreur lors de la création du dossier : $path<br>";
        }
    } else {
        echo "ℹ Dossier existe déjà : $path<br>";
    }
}

// Fonction pour créer une image par défaut
function createDefaultImage($path) {
    if (!file_exists($path)) {
        // Créer une image noire de 800x600 pixels
        $image = imagecreatetruecolor(800, 600);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $black);
        
        // Ajouter du texte
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = "Image par défaut";
        $font = 5; // Police système par défaut
        
        // Centrer le texte
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = (800 - $textWidth) / 2;
        $y = (600 - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $text, $white);
        
        // Sauvegarder l'image
        imagejpeg($image, $path, 90);
        imagedestroy($image);
        
        echo "✓ Image par défaut créée : $path<br>";
    } else {
        echo "ℹ Image par défaut existe déjà : $path<br>";
    }
}

// Créer les dossiers nécessaires
$directories = [
    '../images/vehicules',
    '../images/kits',
    '../images/options'
];

foreach ($directories as $dir) {
    createDirectory($dir);
}

// Créer les images par défaut
$defaultImages = [
    '../images/vehicules/default.jpg',
    '../images/kits/default.jpg',
    '../images/options/default.jpg'
];

foreach ($defaultImages as $image) {
    createDefaultImage($image);
}

// Lire et exécuter le script SQL
$sql = file_get_contents('create_database.sql');
try {
    $pdo->exec($sql);
    echo "<br>✓ Base de données et tables créées avec succès !<br>";
} catch (PDOException $e) {
    echo "<br>✗ Erreur lors de la création de la base de données : " . $e->getMessage() . "<br>";
}

echo "<br><a href='index.php'>Retour à l'administration</a>";
?> 