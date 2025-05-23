<?php
require 'check_auth.php';
require '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$image_id = $_POST['image_id'] ?? null;
$type = $_POST['type'] ?? null;

if (!$image_id || !$type) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

try {
    if ($type === 'kit') {
        // Récupérer le chemin de l'image
        $stmt = $pdo->prepare("SELECT image_path FROM kit_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image_path = $stmt->fetchColumn();
        
        if ($image_path) {
            // Supprimer le fichier physique
            $file_path = '../images/kits/' . $image_path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Supprimer l'enregistrement de la base de données
            $stmt = $pdo->prepare("DELETE FROM kit_images WHERE id = ?");
            $stmt->execute([$image_id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Image non trouvée']);
        }
    } elseif ($type === 'option') {
        // Récupérer le chemin de l'image
        $stmt = $pdo->prepare("SELECT image_path FROM option_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $image_path = $stmt->fetchColumn();
        
        if ($image_path) {
            // Supprimer le fichier physique
            $file_path = '../images/options/' . $image_path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Supprimer l'enregistrement de la base de données
            $stmt = $pdo->prepare("DELETE FROM option_images WHERE id = ?");
            $stmt->execute([$image_id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Image non trouvée']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Type d\'image invalide']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'image']);
} 