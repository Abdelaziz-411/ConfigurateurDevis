<?php
header('Content-Type: application/json');
require 'config.php';

if (!isset($_GET['vehicule_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du véhicule manquant']);
    exit;
}

$vehicule_id = (int)$_GET['vehicule_id'];

try {
    // Récupérer uniquement les kits compatibles avec le véhicule sélectionné
    $stmt = $pdo->prepare("
        SELECT k.id, k.nom, k.description, kvc.prix, 
        GROUP_CONCAT(DISTINCT CONCAT('images/kits/', ki.image_path)) as images
        FROM kits k
        INNER JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit AND kvc.id_vehicule = ?
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
        GROUP BY k.id, k.nom, k.description, kvc.prix
        ORDER BY k.nom
    ");
    
    $stmt->execute([$vehicule_id]);
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformer la chaîne d'images en tableau
    foreach ($kits as &$kit) {
        $kit['images'] = $kit['images'] ? explode(',', $kit['images']) : [];
        // Le prix ne peut pas être null car on utilise INNER JOIN
        $kit['prix'] = floatval($kit['prix']);
    }
    
    echo json_encode([
        'success' => true,
        'kits' => $kits
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des kits : ' . $e->getMessage()
    ]);
}
?> 