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
    $table = '';
    $folder = '';
    
    switch ($type) {
        case 'vehicule':
            $table = 'vehicle_images';
            $folder = 'vehicules';
            break;
        case 'kit':
            $table = 'kit_images';
            $folder = 'kits';
            break;
        case 'option':
            $table = 'option_images';
            $folder = 'options';
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Type d\'image invalide']);
            exit;
    }
    
    // Récupérer le chemin de l'image
    $stmt = $pdo->prepare("SELECT image_path FROM $table WHERE id = ?");
    $stmt->execute([$image_id]);
    $image_path = $stmt->fetchColumn();
    
    if ($image_path) {
        // Supprimer le fichier physique
        $file_path = "../images/$folder/" . $image_path;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Supprimer l'enregistrement de la base de données
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$image_id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Image non trouvée']);
    }
} catch (PDOException $e) {
    error_log("Erreur SQL dans delete_image.php : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'image']);
} 