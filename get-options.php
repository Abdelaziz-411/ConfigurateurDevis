<?php
require_once 'config.php';

header('Content-Type: application/json');

// Modifier pour utiliser le type_carrosserie au lieu de vehicule_id
if (!isset($_GET['type_carrosserie']) || empty($_GET['type_carrosserie'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Type de carrosserie manquant']);
    exit;
}

try {
    // Le type_carrosserie est maintenant directement reçu
    $type_carrosserie = $_GET['type_carrosserie'];

    // Récupérer les options avec leurs prix pour le type de carrosserie donné
    $stmt = $pdo->prepare("
        SELECT o.*,
               c.nom as categorie_nom,
               ovc.prix,
               GROUP_CONCAT(DISTINCT oi.image_path) as images
        FROM options o
        LEFT JOIN categories_options c ON o.id_categorie = c.id
        LEFT JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option AND ovc.type_carrosserie = ?
        LEFT JOIN option_images oi ON o.id = oi.id_option
        GROUP BY o.id
        ORDER BY c.nom, o.nom
    ");
    $stmt->execute([$type_carrosserie]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transformer la chaîne d'images en tableaux et formater les prix
    $optionsWithImages = []; // Nouveau tableau pour stocker les options avec images traitées
    foreach ($options as $option) {
        $option['images'] = $option['images'] ? array_map(function($image) { return 'images/options/' . $image; }, explode(',', $option['images'])) : [];
        $option['prix'] = floatval($option['prix'] ?? 0); // Utiliser 0 si prix est null
        $optionsWithImages[] = $option; // Ajouter l'option traitée au nouveau tableau
    }

    echo json_encode(['success' => true, 'options' => $optionsWithImages]); // Retourner le nouveau tableau

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des options : ' . $e->getMessage()]);
} catch (Exception $e) {
     http_response_code(500);
     echo json_encode(['success' => false, 'error' => 'Erreur générale lors de la récupération des options : ' . $e->getMessage()]);
}
?> 