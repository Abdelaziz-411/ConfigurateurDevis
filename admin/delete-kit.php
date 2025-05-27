<?php
require 'header.php';
require 'check_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;

    if (!$id) {
        throw new Exception('ID du kit manquant');
    }

    // Supprimer les images physiques
    $stmt = $pdo->prepare("SELECT image_path FROM kit_images WHERE id_kit = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($images as $image) {
        $path = '../images/kits/' . $image;
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    // Supprimer le kit (les images et compatibilités seront supprimées automatiquement grâce à ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM kits WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 