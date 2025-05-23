<?php
header('Content-Type: application/json');
require 'config.php';

$type = $_GET['type'] ?? '';

try {
    if ($type === 'vehicules') {
        // Récupérer les véhicules avec leurs images
        $stmt = $pdo->query("
            SELECT v.*, GROUP_CONCAT(DISTINCT CONCAT('images/vehicules/', vi.image_path)) as images
            FROM vehicules v
            LEFT JOIN vehicle_images vi ON v.id = vi.id_vehicule
            GROUP BY v.id, v.nom, v.description
            ORDER BY v.nom
        ");
        
        $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transformer la chaîne d'images en tableau
        foreach ($vehicules as &$vehicule) {
            $vehicule['images'] = $vehicule['images'] ? explode(',', $vehicule['images']) : [];
        }
        
        echo json_encode([
            'success' => true,
            'vehicules' => $vehicules
        ]);

    } elseif ($type === 'kits' && isset($_GET['vehicule_id'])) {
        $vehicule_id = intval($_GET['vehicule_id']);

        // Charger kits liés à ce véhicule avec la bonne table
        $stmt = $pdo->prepare("
            SELECT k.*, kvc.prix, GROUP_CONCAT(ki.image_path) as images
            FROM kits k
            LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit AND kvc.id_vehicule = ?
            LEFT JOIN kit_images ki ON k.id = ki.id_kit
            GROUP BY k.id
            ORDER BY k.nom
        ");
        $stmt->execute([$vehicule_id]);
        $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transformer la chaîne d'images en tableau pour chaque kit
        foreach ($kits as &$kit) {
            $kit['images'] = $kit['images'] ? explode(',', $kit['images']) : [];
            $kit['prix'] = $kit['prix'] ?? 0.00; // Prix par défaut si null
        }

        echo json_encode([
            'success' => true,
            'kits' => $kits
        ]);

    } elseif ($type === 'options') {
        if (!isset($_GET['vehicule_id'])) {
            echo json_encode(['success' => false, 'message' => 'ID du véhicule manquant']);
            exit;
        }

        $vehicule_id = (int)$_GET['vehicule_id'];

        // Récupérer toutes les options avec leurs prix pour ce véhicule et leurs images
        $stmt = $pdo->prepare("
            SELECT o.*, ovc.prix, GROUP_CONCAT(oi.image_path) as images
            FROM options o
            LEFT JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option AND ovc.id_vehicule = ?
            LEFT JOIN option_images oi ON o.id = oi.id_option
            GROUP BY o.id
            ORDER BY o.nom
        ");
        
        $stmt->execute([$vehicule_id]);
        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transformer la chaîne d'images en tableau et gérer les prix nuls
        foreach ($options as &$option) {
            $option['images'] = $option['images'] ? explode(',', $option['images']) : [];
            $option['prix'] = $option['prix'] ?? 0.00; // Prix par défaut si null
        }
        
        echo json_encode([
            'success' => true,
            'options' => $options
        ]);

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Type de données non valide'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
?>