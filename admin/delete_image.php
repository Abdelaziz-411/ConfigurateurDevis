<?php
require '../config.php';
require 'header.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $id = $_POST['id'];
    
    // Récupérer le chemin de l'image
        $stmt = $pdo->prepare("SELECT image_path FROM modele_images WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($image) {
        // Supprimer le fichier physique
            $path = "../images/modeles/" . $image['image_path'];
            if (file_exists($path)) {
                unlink($path);
        }
        
        // Supprimer l'enregistrement de la base de données
            $stmt = $pdo->prepare("DELETE FROM modele_images WHERE id = ?");
            $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Image non trouvée']);
    }
    } catch (Exception $e) {
        error_log("Erreur lors de la suppression de l'image : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
} 