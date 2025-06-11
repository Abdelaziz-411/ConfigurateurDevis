<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    if ($action === 'get_marques') {
        // Récupérer toutes les marques avec leurs logos
        $stmt = $pdo->query("SELECT m.*, GROUP_CONCAT(CONCAT('images/marques/', mi.image_path)) as images FROM marques m LEFT JOIN marque_images mi ON m.id = mi.id_marque GROUP BY m.id ORDER BY m.nom");
        $marques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transformer la chaîne d'images en tableau
        foreach ($marques as &$marque) {
            $marque['images'] = $marque['images'] ? explode(',', $marque['images']) : [];
        }

        echo json_encode($marques);

    } elseif ($action === 'get_modeles' && isset($_GET['id_marque'])) {
        $id_marque = (int)$_GET['id_marque'];

        // Récupérer les modèles avec leurs types de carrosserie
        $sql = "
            SELECT m.*, 
                   GROUP_CONCAT(DISTINCT mi.image_path) as images,
                   GROUP_CONCAT(DISTINCT ms.statut) as types_carrosserie
            FROM modeles m
            LEFT JOIN modele_images mi ON m.id = mi.id_modele
            LEFT JOIN modele_statuts ms ON m.id = ms.id_modele
            WHERE m.id_marque = ?
            GROUP BY m.id
            ORDER BY m.nom
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_marque]);
        $modeles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Transformer les chaînes en tableaux
        foreach ($modeles as &$modele) {
            $modele['images'] = $modele['images'] ? array_map(function($image) { 
                return 'images/modeles/' . htmlspecialchars($image); 
            }, explode(',', $modele['images'])) : [];
            
            // Utiliser directement les types de carrosserie récupérés de modele_statuts
            $modele['types_carrosserie'] = $modele['types_carrosserie'] ? array_filter(explode(',', $modele['types_carrosserie'])) : [];
        }
        
        echo json_encode($modeles);

    } else {
        echo json_encode([]); // Retourner un tableau vide pour une action inconnue ou manquante
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erreur de base de données : ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
} 