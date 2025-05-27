<?php
require '../config.php';
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception("L'ID de l'option est requis");
    }

    $id = $data['id'];

    // Supprimer les images physiques
    $stmt = $pdo->prepare("SELECT image_path FROM option_images WHERE id_option = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($images as $image) {
        $path = '../images/options/' . $image;
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    // Supprimer l'option (les images et compatibilités seront supprimées automatiquement grâce à ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM options WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Option supprimée avec succès']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 