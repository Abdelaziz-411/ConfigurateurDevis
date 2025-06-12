<?php
require_once 'config.php';

header('Content-Type: application/json');

// Log le contenu de $_GET pour un débogage détaillé
error_log("get-options.php: Contenu de \$_GET: " . var_export($_GET, true));

try {
    // $modele_id = $_GET['modele_id'] ?? null; // No longer needed for compatibility logic
    $type_carrosserie = $_GET['type_carrosserie'] ?? null;

    // Nettoyer la valeur du type de carrosserie
    $type_carrosserie = trim($type_carrosserie);

    // Ajout de logs pour débogage après nettoyage
    error_log("get-options.php: Reçu (après trim) type_carrosserie = [" . $type_carrosserie . "]");
    error_log("get-options.php: empty(type_carrosserie) est : " . (empty($type_carrosserie) ? 'true' : 'false'));

    if (empty($type_carrosserie)) { // Utiliser empty() pour une vérification plus robuste
        error_log("get-options.php: Erreur - type_carrosserie est vide ou null.");
        throw new Exception('Paramètre type_carrosserie manquant ou vide');
    }

    // Modified SQL query
    $sql = "SELECT o.id, o.nom, o.description, o.id_categorie,
                   ovc.prix AS compatible_prix,
               GROUP_CONCAT(DISTINCT oi.image_path) as images
        FROM options o
            INNER JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option
        LEFT JOIN option_images oi ON o.id = oi.id_option
            WHERE ovc.type_carrosserie = ?
            GROUP BY o.id, o.nom, o.description, o.id_categorie, compatible_prix
            ORDER BY o.nom";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$type_carrosserie]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("get-options.php: Contenu des options récupérées : " . print_r($options, true));

    // Transformer les images en tableau et ajouter le préfixe du chemin
    foreach ($options as &$option) {
        $option['images'] = $option['images'] ? explode(',', $option['images']) : [];
        // Use 'compatible_prix' here instead of 'prix'
        $option['prix'] = floatval($option['compatible_prix']);
        $option['images'] = array_map(function($path) {
            return 'images/options/' . $path;
        }, $option['images']);
    }

    echo json_encode($options);
} catch (Exception $e) {
    error_log("Erreur dans get-options.php: " . $e->getMessage()); // Log the actual exception message
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 