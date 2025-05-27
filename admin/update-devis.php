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
    // Validation des données
    $required_fields = ['devis_id', 'nom', 'prenom', 'email', 'telephone', 'prix_ht', 'prix_ttc', 'statut', 'configuration'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Le champ $field est requis");
        }
    }

    // Mise à jour du devis
    $stmt = $pdo->prepare("
        UPDATE devis SET
            nom = :nom,
            prenom = :prenom,
            email = :email,
            telephone = :telephone,
            message = :message,
            prix_ht = :prix_ht,
            prix_ttc = :prix_ttc,
            statut = :statut,
            configuration = :configuration
        WHERE id = :devis_id
    ");

    $stmt->execute([
        'nom' => $_POST['nom'],
        'prenom' => $_POST['prenom'],
        'email' => $_POST['email'],
        'telephone' => $_POST['telephone'],
        'message' => $_POST['message'] ?? '',
        'prix_ht' => $_POST['prix_ht'],
        'prix_ttc' => $_POST['prix_ttc'],
        'statut' => $_POST['statut'],
        'configuration' => $_POST['configuration'],
        'devis_id' => $_POST['devis_id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Devis modifié avec succès']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 