<?php
require '../config.php';

$nom = $_POST['nom'];
$vehicule_id = intval($_POST['vehicule_id']);
$prix = floatval($_POST['prix']);

// 1. Enregistrement dans la table kits
$stmt = $pdo->prepare("INSERT INTO kits (nom) VALUES (?)");
$stmt->execute([$nom]);
$kit_id = $pdo->lastInsertId();

// 2. Prix spécifique pour le véhicule
$stmt = $pdo->prepare("INSERT INTO kit_vehicule_prix (kit_id, vehicule_id, prix) VALUES (?, ?, ?)");
$stmt->execute([$kit_id, $vehicule_id, $prix]);

// 3. Upload des images
$uploadDir = '../images/';
foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
    if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES['images']['name'][$index]);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            // Enregistrement dans la table kit_images
            $stmt = $pdo->prepare("INSERT INTO kit_images (kit_id, fichier) VALUES (?, ?)");
            $stmt->execute([$kit_id, $fileName]);
        }
    }
}

header("Location: index.php?success=1");
