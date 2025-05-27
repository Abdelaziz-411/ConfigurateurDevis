<?php
require '../config.php';
require 'check_auth.php';

// Désactiver l'affichage des erreurs PHP
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $nom = $_POST['nom'] ?? '';
    $description = $_POST['description'] ?? '';
    $vehicules = $_POST['vehicules'] ?? [];

    if (!$id || !$nom) {
        throw new Exception('Données manquantes');
    }

    // Mise à jour du kit
    $stmt = $pdo->prepare("UPDATE kits SET nom = ?, description = ? WHERE id = ?");
    $stmt->execute([$nom, $description, $id]);

    // Suppression des anciennes compatibilités
    $stmt = $pdo->prepare("DELETE FROM kit_vehicule_compatibilite WHERE id_kit = ?");
    $stmt->execute([$id]);

    // Ajout des nouvelles compatibilités
    if (!empty($vehicules)) {
        foreach ($vehicules as $vehicule_id) {
            $prix_key = 'prix_' . $vehicule_id;
            $prix = isset($_POST[$prix_key]) ? floatval(str_replace(',', '.', $_POST[$prix_key])) : 0.00;

            $stmt = $pdo->prepare("INSERT INTO kit_vehicule_compatibilite (id_kit, id_vehicule, prix) VALUES (?, ?, ?)");
            $stmt->execute([$id, $vehicule_id, $prix]);
        }
    }

    // Gestion des images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file = $_FILES['images']['name'][$key];
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $filename = uniqid() . '.' . $ext;
                    $path = '../images/kits/' . $filename;
                    
                    if (move_uploaded_file($tmp_name, $path)) {
                        $stmt = $pdo->prepare("INSERT INTO kit_images (id_kit, image_path) VALUES (?, ?)");
                        $stmt->execute([$id, $filename]);
                    }
                }
            }
        }
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Erreur PDO dans update-kit.php : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur dans update-kit.php : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 