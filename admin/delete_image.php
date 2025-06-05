<?php
require '../config.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !isset($_POST['image']) || !isset($_POST['type'])) {
        throw new Exception('Paramètres manquants');
    }

    $id = $_POST['id'];
    $image = $_POST['image'];
    $type = $_POST['type'];

    // Vérifier que le type est valide
    if (!in_array($type, ['kit', 'option'])) {
        throw new Exception('Type invalide');
    }

    // Supprimer l'image de la base de données
    $table = $type . '_images';
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id_" . $type . " = ? AND chemin = ?");
    $stmt->execute([$id, $image]);

    // Supprimer le fichier physique
    $path = "../images/{$type}s/" . $image;
    if (file_exists($path)) {
        unlink($path);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Erreur lors de la suppression de l'image : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 