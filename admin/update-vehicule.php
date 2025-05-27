<?php
session_start();
require_once '../config.php';

// Désactiver l'affichage des erreurs PHP
error_reporting(0);
ini_set('display_errors', 0);

// S'assurer que rien n'a été envoyé avant
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    if (!isset($_POST['id']) || !isset($_POST['nom'])) {
        throw new Exception('Données manquantes');
    }

    $id = $_POST['id'];
    $nom = $_POST['nom'];
    $description = $_POST['description'] ?? '';

    // Mise à jour du véhicule
    $stmt = $pdo->prepare("UPDATE vehicules SET nom = ?, description = ? WHERE id = ?");
    $stmt->execute([$nom, $description, $id]);

    // Gestion des images
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file = $_FILES['images']['name'][$key];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $filename = uniqid() . '.' . $ext;
                $path = '../images/vehicules/' . $filename;
                
                if (move_uploaded_file($tmp_name, $path)) {
                    $stmt = $pdo->prepare("INSERT INTO vehicle_images (id_vehicule, image_path) VALUES (?, ?)");
                    $stmt->execute([$id, $filename]);
                }
            }
        }
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 