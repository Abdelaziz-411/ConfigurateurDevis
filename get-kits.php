<?php
require_once 'config.php';

header('Content-Type: application/json');

// Accepter le paramètre 'statuts' qui peut être un tableau
$statuts = $_GET['statuts'] ?? [];

// Vérifier si des statuts ont été fournis et qu'il s'agit bien d'un tableau non vide
if (!is_array($statuts) || empty($statuts)) {
    // Si aucun statut n'est fourni ou si le paramètre n'est pas un tableau, retourner une liste vide de kits.
    echo json_encode(['success' => true, 'kits' => []]);
    exit;
}

// Créer une chaîne de placeholders pour la clause IN
$placeholders = implode(', ', array_fill(0, count($statuts), '?'));

try {
    // Récupérer les kits avec leurs prix pour les types de carrosserie donnés
    // Utiliser WHERE IN pour les statuts et passer les statuts comme paramètres à execute
    $sql = "
        SELECT k.*,
               GROUP_CONCAT(DISTINCT ki.image_path) as images,
               kvc.prix
        FROM kits k
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
        JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
        WHERE kvc.type_carrosserie IN ($placeholders)
        GROUP BY k.id
        ORDER BY k.nom
    ";
    
    $stmt = $pdo->prepare($sql);
    // Passer le tableau de statuts directement à execute
    $stmt->execute($statuts);

    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformer les chaînes d'images en tableaux et formater les prix
    foreach ($kits as &$kit) {
        $kit['images'] = $kit['images'] ? array_map(function($image) { return 'images/kits/' . htmlspecialchars($image); }, explode(',', $kit['images'])) : [];
        $kit['prix'] = floatval($kit['prix'] ?? 0); // Utiliser 0 si prix est null
    }
    
    echo json_encode(['success' => true, 'kits' => $kits]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des kits : ' . $e->getMessage()]);
} catch (Exception $e) {
     http_response_code(500);
     echo json_encode(['success' => false, 'error' => 'Erreur générale lors de la récupération des kits : ' . $e->getMessage()]);
}
?> 