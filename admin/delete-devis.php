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
    
    if (!isset($data['devis_id'])) {
        throw new Exception('ID du devis non spécifié');
    }

    // Suppression du devis
    $stmt = $pdo->prepare("DELETE FROM devis WHERE id = ?");
    $stmt->execute([$data['devis_id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Devis non trouvé');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 