<?php
require 'config.php';

try {
    // Mise à jour des images des véhicules
    $vehicules_images = [
        'L1H1' => 'van-l1h1.jpg',
        'L2H1' => 'van-l2h1.jpg',
        'L2H2' => 'van-l2h2.jpg',
        'L3H2' => 'van-l3h2.jpg',
        'L3H3' => 'van-l3h3.jpg',
        'L4H4' => 'van-l4h4.jpg'
    ];

    foreach ($vehicules_images as $nom => $image) {
        // Récupérer l'ID du véhicule
        $stmt = $pdo->prepare("SELECT id FROM vehicules WHERE nom = ?");
        $stmt->execute([$nom]);
        $vehicule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vehicule) {
            // Insérer l'image dans vehicle_images
            $stmt = $pdo->prepare("INSERT INTO vehicle_images (id_vehicule, image_path) VALUES (?, ?)");
            $stmt->execute([$vehicule['id'], $image]);
        }
    }

    // Mise à jour des images des options
    $options_images = [
        'Toit Relevable' => 'toit-relevable.jpg',
        'Panneaux solaires' => 'panneaux-solaires.jpg',
        'Batterie auxiliaire' => 'batterie.jpg',
        'Douche extérieure' => 'douche.jpg',
        'Chauffage stationnaire' => 'chauffage.jpg'
    ];

    foreach ($options_images as $nom => $image) {
        // Récupérer l'ID de l'option
        $stmt = $pdo->prepare("SELECT id FROM options WHERE nom = ?");
        $stmt->execute([$nom]);
        $option = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($option) {
            // Insérer l'image dans option_images
            $stmt = $pdo->prepare("INSERT INTO option_images (id_option, image_path) VALUES (?, ?)");
            $stmt->execute([$option['id'], $image]);
        }
    }

    echo "Images mises à jour avec succès !";
} catch (PDOException $e) {
    die("Erreur lors de la mise à jour des images : " . $e->getMessage());
} 