<?php
header('Content-Type: application/json');
require 'config.php';

if (!isset($_GET['vehicule_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du véhicule manquant']);
    exit;
}

$vehicule_id = (int)$_GET['vehicule_id'];

try {
    // Récupérer uniquement les options compatibles avec le véhicule sélectionné
    $stmt = $pdo->prepare("
        SELECT o.id, o.nom, o.description, ovc.prix, 
        GROUP_CONCAT(DISTINCT CONCAT('images/options/', oi.image_path)) as images
        FROM options o
        INNER JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option AND ovc.id_vehicule = ?
        LEFT JOIN option_images oi ON o.id = oi.id_option
        GROUP BY o.id, o.nom, o.description, ovc.prix
        ORDER BY o.nom
    ");
    
    $stmt->execute([$vehicule_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformer la chaîne d'images en tableau
    foreach ($options as &$option) {
        $option['images'] = $option['images'] ? explode(',', $option['images']) : [];
        // Le prix ne peut pas être null car on utilise INNER JOIN
        $option['prix'] = floatval($option['prix']);
    }
    
    echo json_encode([
        'success' => true,
        'options' => $options
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des options : ' . $e->getMessage()
    ]);
}
?> 