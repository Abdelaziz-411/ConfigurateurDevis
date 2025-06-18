<?php
// require_once '../config/database.php'; // Supprimé car la configuration est dans config.php
require_once '../config.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fonction pour envoyer un email
function sendDevisEmail($devis_id) {
    global $pdo;
    
    // Récupérer les informations du devis
    $stmt = $pdo->prepare("
        SELECT d.*, v.nom as vehicule_nom, k.nom as kit_nom
        FROM devis d
        LEFT JOIN vehicules v ON d.vehicule_id = v.id
        LEFT JOIN kits k ON d.kit_id = k.id
        WHERE d.id = ?
    ");
    $stmt->execute([$devis_id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$devis) {
        return false;
    }
    
    // Construire le contenu de l'email
    $to = $devis['email'];
    $subject = "Votre devis #" . $devis_id;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #c6864a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; border: 1px solid #ddd; }
            th { background-color: #f5f5f5; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Votre devis #" . $devis_id . "</h1>
            </div>
            <div class='content'>
                <p>Bonjour " . htmlspecialchars($devis['prenom']) . " " . htmlspecialchars($devis['nom']) . ",</p>
                <p>Nous vous remercions pour votre demande de devis. Voici le détail de votre configuration :</p>
                
                <h2>Informations client</h2>
                <p>
                    Nom : " . htmlspecialchars($devis['prenom']) . " " . htmlspecialchars($devis['nom']) . "<br>
                    Email : " . htmlspecialchars($devis['email']) . "<br>
                    Téléphone : " . htmlspecialchars($devis['telephone']) . "
                </p>
                
                <h2>Configuration</h2>
                <p>
                    Véhicule : " . htmlspecialchars($devis['vehicule_nom']) . "<br>
                    Type de carrosserie : " . htmlspecialchars($devis['type_carrosserie']) . "<br>
                    " . ($devis['kit_nom'] ? "Kit : " . htmlspecialchars($devis['kit_nom']) : "") . "
                </p>
                
                <h2>Détails de la configuration</h2>
                <pre style='white-space: pre-wrap;'>" . htmlspecialchars($devis['configuration']) . "</pre>
                
                <h2>Prix total</h2>
                <p>Prix HT : " . number_format($devis['prix_ht'], 2, ',', ' ') . " €</p>
                <p>Prix TTC : " . number_format($devis['prix_ttc'], 2, ',', ' ') . " €</p>
                
                <p>Pour toute question concernant ce devis, n'hésitez pas à nous contacter.</p>
            </div>
            <div class='footer'>
                <p>Ce message est généré automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // En-têtes de l'email
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . SITE_EMAIL,
        'Reply-To: ' . SITE_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    );
    
    // Envoyer l'email
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// Traiter la demande d'envoi d'email depuis l'interface d'administration
if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devis_id'])) {
    $devis_id = $_POST['devis_id'];
    
    if (sendDevisEmail($devis_id)) {
        // Mettre à jour le statut du devis
        $stmt = $pdo->prepare("UPDATE devis SET email_envoye = 1 WHERE id = ?");
        $stmt->execute([$devis_id]);
        
        header('Location: devis.php?success=email');
    } else {
        header('Location: devis.php?error=email');
    }
    exit();
}

// Si accès direct sans session admin, rediriger
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Rediriger si accès direct
header('Location: devis.php');
exit(); 