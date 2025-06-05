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

        // Récupérer les modèles pour une marque donnée avec leurs photos et leur statut
        $stmt = $pdo->prepare("SELECT m.*, GROUP_CONCAT(CONCAT('images/modeles/', mi.image_path)) as images FROM modeles m LEFT JOIN modele_images mi ON m.id = mi.id_modele WHERE m.id_marque = ? GROUP BY m.id ORDER BY m.nom");
        $stmt->execute([$id_marque]);
        $modeles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transformer la chaîne d'images en tableau
        foreach ($modeles as &$modele) {
            $modele['images'] = $modele['images'] ? explode(',', $modele['images']) : [];
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