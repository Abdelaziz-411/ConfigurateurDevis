<?php
// Créer les dossiers s'ils n'existent pas
$directories = ['images/marques', 'images/modeles'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Fonction pour créer une image par défaut
function createDefaultImage($text, $filename, $bgColor, $textColor) {
    $width = 400;
    $height = 300;
    
    // Créer l'image
    $image = imagecreatetruecolor($width, $height);
    
    // Convertir les couleurs hex en RGB
    $bgRGB = sscanf($bgColor, "#%02x%02x%02x");
    $textRGB = sscanf($textColor, "#%02x%02x%02x");
    
    // Allouer les couleurs
    $bgColor = imagecolorallocate($image, $bgRGB[0], $bgRGB[1], $bgRGB[2]);
    $textColor = imagecolorallocate($image, $textRGB[0], $textRGB[1], $textRGB[2]);
    
    // Remplir le fond
    imagefill($image, 0, 0, $bgColor);
    
    // Ajouter le texte
    $fontSize = 24;
    $font = 5; // Police système par défaut
    
    // Centrer le texte
    $textBox = imagettfbbox($fontSize, 0, $font, $text);
    $textWidth = abs($textBox[4] - $textBox[0]);
    $textHeight = abs($textBox[5] - $textBox[1]);
    $x = ($width - $textWidth) / 2;
    $y = ($height + $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $text, $textColor);
    
    // Sauvegarder l'image
    imagepng($image, $filename);
    imagedestroy($image);
}

// Créer les images par défaut
createDefaultImage('Image de marque par défaut', 'images/marques/default-marque.png', '#f0f0f0', '#646464');
createDefaultImage('Image de modèle par défaut', 'images/modeles/default-modele.png', '#f5f5f5', '#787878');
createDefaultImage('Autre marque', 'images/marques/autre-marque.png', '#e6e6e6', '#505050');
createDefaultImage('Autre modèle', 'images/modeles/autre-modele.png', '#ebebeb', '#5a5a5a');

echo "Images par défaut créées avec succès !\n";
?> 