<?php
header('Content-Type: application/json');
require 'config.php';

if (!isset($_GET['vehicule_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du véhicule manquant']);
    exit;
}

$vehicule_id = (int)$_GET['vehicule_id'];

try {
    // Récupérer les options compatibles avec le véhicule
    $sql = "
        SELECT DISTINCT o.id, o.nom, o.description, COALESCE(ovc.prix, 0.00) as prix, 
        GROUP_CONCAT(DISTINCT CONCAT('images/options/', oi.image_path)) as images,
        c.id as categorie_id, c.nom as categorie_nom, c.ordre as categorie_ordre
        FROM options o
        LEFT JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option AND ovc.id_vehicule = ?
        LEFT JOIN option_images oi ON o.id = oi.id_option
        LEFT JOIN categories_options c ON o.id_categorie = c.id
        GROUP BY o.id, o.nom, o.description, ovc.prix, c.id, c.nom, c.ordre
        ORDER BY c.ordre, c.nom, o.nom
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$vehicule_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformer la chaîne d'images en tableau et formater les données
    foreach ($options as &$option) {
        $option['images'] = $option['images'] ? explode(',', $option['images']) : [];
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