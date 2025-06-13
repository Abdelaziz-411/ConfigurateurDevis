<?php
require 'config.php';

header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log des données reçues
error_log("Méthode de la requête : " . $_SERVER['REQUEST_METHOD']);
error_log("Données brutes reçues : " . file_get_contents('php://input'));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Log des données décodées
    error_log("Données décodées : " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('Données invalides : ' . json_last_error_msg());
    }

    // Validation des données requises
    $required_fields = ['nom', 'prenom', 'email', 'telephone', 'vehicule_id', 'configuration', 'prix_ht', 'prix_ttc', 'type_carrosserie'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Le champ $field est requis");
        }
    }

    // Insertion dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO devis (
            nom, prenom, email, telephone, message,
            id_vehicule, id_kit, type_carrosserie, configuration,
            prix_ht, prix_ttc, statut
        ) VALUES (
            :nom, :prenom, :email, :telephone, :message,
            :vehicule_id, :kit_id, :type_carrosserie, :configuration,
            :prix_ht, :prix_ttc, 'nouveau'
        )
    ");

    $stmt->execute([
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'email' => $data['email'],
        'telephone' => $data['telephone'],
        'message' => $data['message'] ?? '',
        'vehicule_id' => $data['vehicule_id'],
        'kit_id' => $data['kit_id'] ?? null,
        'type_carrosserie' => $data['type_carrosserie'],
        'configuration' => $data['configuration'],
        'prix_ht' => $data['prix_ht'],
        'prix_ttc' => $data['prix_ttc']
    ]);

    $devis_id = $pdo->lastInsertId();

    // Récupérer l'email des administrateurs
    $stmt = $pdo->query("
        SELECT email 
        FROM utilisateurs 
        WHERE role = 'admin' 
        LIMIT 1
    ");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Envoi de l'email à l'administrateur
        $to = $admin['email'];
        $subject = "Nouvelle demande de devis #$devis_id";
        $message = "Une nouvelle demande de devis a été reçue :\n\n";
        $message .= "Nom : {$data['nom']}\n";
        $message .= "Prénom : {$data['prenom']}\n";
        $message .= "Email : {$data['email']}\n";
        $message .= "Téléphone : {$data['telephone']}\n\n";
        $message .= "Type de carrosserie : {$data['type_carrosserie']}\n";
        $message .= "Configuration :\n{$data['configuration']}\n\n";
        $message .= "Prix HT : {$data['prix_ht']} €\n";
        $message .= "Prix TTC : {$data['prix_ttc']} €\n\n";
        $message .= "Message :\n{$data['message']}\n";

        $headers = "From: {$data['email']}\r\n";
        $headers .= "Reply-To: {$data['email']}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        mail($to, $subject, $message, $headers);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Votre demande de devis a été enregistrée avec succès'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi du devis : ' . $e->getMessage()
    ]);
} 