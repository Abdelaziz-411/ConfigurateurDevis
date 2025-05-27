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
    $type = $data['type'] ?? null;

    if (!$id || !$type) {
        throw new Exception('Données manquantes');
    }

    // Récupérer le chemin de l'image
    $table = $type === 'kit' ? 'kit_images' : 'option_images';
    $stmt = $pdo->prepare("SELECT image_path FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $image_path = $stmt->fetchColumn();

    if ($image_path) {
        // Supprimer le fichier physique
        $path = "../images/$type" . "s/" . $image_path;
        if (file_exists($path)) {
            unlink($path);
        }

        // Supprimer l'entrée de la base de données
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Image non trouvée');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 