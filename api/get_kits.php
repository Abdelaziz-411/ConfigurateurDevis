<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['vehicle_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID du véhicule manquant']);
    exit;
}

$vehicle_id = intval($_GET['vehicle_id']);

try {
    $stmt = $pdo->prepare("
        SELECT k.*, GROUP_CONCAT(ki.image_path) as images
        FROM kits k
        LEFT JOIN kit_images ki ON k.id = ki.kit_id
        WHERE k.vehicle_id = ?
        GROUP BY k.id
    ");
    
    $stmt->execute([$vehicle_id]);
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traitement des images
    foreach ($kits as &$kit) {
        $kit['images'] = $kit['images'] ? explode(',', $kit['images']) : [];
    }
    
    echo json_encode($kits);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des kits']);
} 