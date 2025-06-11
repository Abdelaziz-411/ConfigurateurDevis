<?php
require_once 'config.php';

header('Content-Type: application/json');

// Accepter le paramètre 'statuts' qui peut être un tableau
$statuts = $_GET['statuts'] ?? [];

// Vérifier si des statuts ont été fournis et qu'il s'agit bien d'un tableau non vide
if (!is_array($statuts) || empty($statuts)) {
    // Si aucun statut n'est fourni ou si le paramètre n'est pas un tableau, retourner une liste vide d'options.
    echo json_encode(['success' => true, 'options' => []]);
    exit;
}

// Créer une chaîne de placeholders pour la clause IN
$placeholders = implode(', ', array_fill(0, count($statuts), '?'));

try {
    // Récupérer les options avec leurs prix pour les types de carrosserie donnés
    // Utiliser WHERE IN pour les statuts et passer les statuts comme paramètres à execute
    $sql = "
        SELECT o.*,
               c.nom as categorie_nom,
               ovc.prix,
               GROUP_CONCAT(DISTINCT oi.image_path) as images
        FROM options o
        LEFT JOIN categories_options c ON o.id_categorie = c.id
        JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option
        LEFT JOIN option_images oi ON o.id = oi.id_option
        WHERE ovc.type_carrosserie IN ($placeholders)
        GROUP BY o.id
        ORDER BY c.nom, o.nom
    ";
    
    $stmt = $pdo->prepare($sql);
    // Passer le tableau de statuts directement à execute
    $stmt->execute($statuts);

    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformer la chaîne d'images en tableaux et formater les prix
    $optionsWithImages = []; // Nouveau tableau pour stocker les options avec images traitées
    foreach ($options as $option) {
        $option['images'] = $option['images'] ? array_map(function($image) { return 'images/options/' . htmlspecialchars($image); }, explode(',', $option['images'])) : [];
        $option['prix'] = floatval($option['prix'] ?? 0); // Utiliser 0 si prix est null
        $optionsWithImages[] = $option; // Ajouter l'option traitée au nouveau tableau
    }
    
    echo json_encode(['success' => true, 'options' => $optionsWithImages]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des options : ' . $e->getMessage()]);
} catch (Exception $e) {
     http_response_code(500);
     echo json_encode(['success' => false, 'error' => 'Erreur générale lors de la récupération des options : ' . $e->getMessage()]);
}
?> 