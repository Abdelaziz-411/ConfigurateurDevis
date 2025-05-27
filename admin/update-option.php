<?php
require '../config.php';
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Log des données reçues
    error_log("Données POST reçues : " . print_r($_POST, true));
    error_log("Données FILES reçues : " . print_r($_FILES, true));

    // Validation des données
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("L'ID de l'option est requis");
    }
    if (!isset($_POST['nom']) || empty($_POST['nom'])) {
        throw new Exception("Le nom de l'option est requis");
    }

    $id = $_POST['id'];
    $nom = $_POST['nom'];
    $description = $_POST['description'] ?? '';

    error_log("Mise à jour de l'option ID: $id, Nom: $nom, Description: $description");

    // Mise à jour de l'option
    $stmt = $pdo->prepare("UPDATE options SET nom = ?, description = ? WHERE id = ?");
    $stmt->execute([$nom, $description, $id]);
    error_log("Option mise à jour dans la base de données");

    // Mise à jour des compatibilités avec les véhicules
    // D'abord supprimer les anciennes
    $stmt = $pdo->prepare("DELETE FROM option_vehicule_compatibilite WHERE id_option = ?");
    $stmt->execute([$id]);
    error_log("Anciennes compatibilités supprimées");

    // Puis ajouter les nouvelles
    if (isset($_POST['vehicules']) && is_array($_POST['vehicules'])) {
        error_log("Véhicules à mettre à jour : " . print_r($_POST['vehicules'], true));
        
        foreach ($_POST['vehicules'] as $vehicule_id) {
            $prix_key = 'prix_' . $vehicule_id;
            $prix = 0.00; // Valeur par défaut
            
            if (isset($_POST[$prix_key]) && $_POST[$prix_key] !== '') {
                // Remplacer la virgule par un point et convertir en nombre
                $prix = str_replace(',', '.', $_POST[$prix_key]);
                $prix = floatval($prix);
            }

            error_log("Mise à jour du prix pour le véhicule $vehicule_id : $prix");

            $stmt = $pdo->prepare("INSERT INTO option_vehicule_compatibilite (id_option, id_vehicule, prix) VALUES (?, ?, ?)");
            $stmt->execute([$id, $vehicule_id, $prix]);
        }
    }

    // Gestion des images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file = $_FILES['images']['name'][$key];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $filename = uniqid() . '.' . $ext;
                $path = '../images/options/' . $filename;
                
                if (move_uploaded_file($tmp_name, $path)) {
                    $stmt = $pdo->prepare("INSERT INTO option_images (id_option, image_path) VALUES (?, ?)");
                    $stmt->execute([$id, $filename]);
                }
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Option modifiée avec succès']);

} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour de l'option : " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 