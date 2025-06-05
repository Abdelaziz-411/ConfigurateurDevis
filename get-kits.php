<?php
require_once 'config.php';

header('Content-Type: application/json');

// Modifier pour utiliser le type_carrosserie au lieu de vehicule_id
if (!isset($_GET['type_carrosserie']) || empty($_GET['type_carrosserie'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de carrosserie manquant']);
    exit;
}

try {
    // Le type_carrosserie est maintenant directement reçu
    $type_carrosserie = $_GET['type_carrosserie'];

    // Récupérer les kits avec leurs prix pour le type de carrosserie donné
    $stmt = $pdo->prepare("
        SELECT k.*,
               GROUP_CONCAT(DISTINCT ki.image_path) as images,
               kvc.prix
        FROM kits k
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
        LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit AND kvc.type_carrosserie = ?
        GROUP BY k.id
        ORDER BY k.nom
    ");
    $stmt->execute([$type_carrosserie]);
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transformer les chaînes d'images en tableaux et formater les prix
    foreach ($kits as &$kit) {
        $kit['images'] = $kit['images'] ? array_map(function($image) { return 'images/kits/' . $image; }, explode(',', $kit['images'])) : [];
        $kit['prix'] = floatval($kit['prix'] ?? 0); // Utiliser 0 si prix est null
    }

    echo json_encode(['success' => true, 'kits' => $kits]); // Ajouter une enveloppe de succès

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des kits : ' . $e->getMessage()]);
} catch (Exception $e) {
     http_response_code(500);
     echo json_encode(['success' => false, 'error' => 'Erreur générale lors de la récupération des kits : ' . $e->getMessage()]);
}
?> 