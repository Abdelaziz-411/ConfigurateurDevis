<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT v.*, GROUP_CONCAT(vi.image_path) as images
        FROM vehicles v
        LEFT JOIN vehicle_images vi ON v.id = vi.vehicle_id
        GROUP BY v.id
    ");
    
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Traitement des images
    foreach ($vehicles as &$vehicle) {
        $vehicle['images'] = $vehicle['images'] ? explode(',', $vehicle['images']) : [];
    }
    
    echo json_encode($vehicles);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des véhicules']);
} 