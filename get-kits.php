<?php
require_once 'config.php';

header('Content-Type: application/json');

// Log le contenu de $_GET pour un débogage détaillé
error_log("get-kits.php: Contenu de \$_GET: " . var_export($_GET, true));

try {
    // $modele_id = $_GET['modele_id'] ?? null; // No longer needed for compatibility logic
    $type_carrosserie = $_GET['type_carrosserie'] ?? null;

    // Nettoyer la valeur du type de carrosserie
    $type_carrosserie = trim($type_carrosserie);

    // Ajout de logs pour débogage après nettoyage
    error_log("get-kits.php: Reçu (après trim) type_carrosserie = [" . $type_carrosserie . "]");
    error_log("get-kits.php: empty(type_carrosserie) est : " . (empty($type_carrosserie) ? 'true' : 'false'));

    if (empty($type_carrosserie)) { // Utiliser empty() pour une vérification plus robuste
        error_log("get-kits.php: Erreur - type_carrosserie est vide ou null.");
        throw new Exception('Paramètre type_carrosserie manquant ou vide');
    }

    $sql = "SELECT k.*,
                   kvc.prix,
                   GROUP_CONCAT(DISTINCT ki.image_path) as images
        FROM kits k
            INNER JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
            WHERE kvc.type_carrosserie = ?
            GROUP BY k.id, kvc.prix
            ORDER BY k.nom";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$type_carrosserie]);
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transformer les images en tableau et ajouter le préfixe du chemin
    foreach ($kits as &$kit) {
        $kit['images'] = $kit['images'] ? explode(',', $kit['images']) : [];
        $kit['prix'] = floatval($kit['prix']);
        $kit['images'] = array_map(function($path) {
            return 'images/kits/' . $path;
        }, $kit['images']);
    }

    echo json_encode($kits);
} catch (Exception $e) {
    error_log("Erreur dans get-kits.php: " . $e->getMessage()); // Log the actual exception message
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 