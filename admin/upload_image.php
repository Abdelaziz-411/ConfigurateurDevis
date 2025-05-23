<?php
require 'check_auth.php';
require '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_POST['type']) || !isset($_POST['id']) || !isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$type = $_POST['type'];
$id = (int)$_POST['id'];
$file = $_FILES['image'];

// Vérification du type de fichier
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']);
    exit;
}

// Vérification de la taille (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 5MB)']);
    exit;
}

// Création des dossiers s'ils n'existent pas
$upload_dirs = [
    'vehicule' => '../images/vehicules',
    'kit' => '../images/kits',
    'option' => '../images/options'
];

if (!isset($upload_dirs[$type])) {
    echo json_encode(['success' => false, 'message' => 'Type invalide']);
    exit;
}

$upload_dir = $upload_dirs[$type];
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Génération d'un nom de fichier unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$filepath = $upload_dir . '/' . $filename;

// Déplacement du fichier
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']);
    exit;
}

try {
    // Détermination de la table et de la colonne en fonction du type
    $tables = [
        'vehicule' => 'vehicle_images',
        'kit' => 'kit_images',
        'option' => 'option_images'
    ];
    
    $id_columns = [
        'vehicule' => 'id_vehicule',
        'kit' => 'id_kit',
        'option' => 'id_option'
    ];
    
    if (!isset($tables[$type]) || !isset($id_columns[$type])) {
        throw new Exception('Type invalide');
    }
    
    $table = $tables[$type];
    $id_column = $id_columns[$type];
    
    // Préparation de la requête d'insertion
    $stmt = $pdo->prepare("INSERT INTO $table ($id_column, image_path) VALUES (?, ?)");
    $result = $stmt->execute([$id, $filename]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Image uploadée avec succès']);
    } else {
        // Si l'insertion échoue, supprimer le fichier uploadé
        unlink($filepath);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement dans la base de données']);
    }
} catch (Exception $e) {
    // En cas d'erreur, supprimer le fichier uploadé
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
    }
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement dans la base de données : ' . $e->getMessage()]);
} 