<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['vehicle_id']) || !isset($_GET['kit_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$vehicle_id = intval($_GET['vehicle_id']);
$kit_id = intval($_GET['kit_id']);

try {
    $stmt = $pdo->prepare("
        SELECT o.*, oi.image_path
        FROM options o
        LEFT JOIN option_images oi ON o.id = oi.option_id
        WHERE o.vehicle_id = ? AND o.kit_id = ?
        GROUP BY o.id
    ");
    
    $stmt->execute([$vehicle_id, $kit_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des options']);
} 