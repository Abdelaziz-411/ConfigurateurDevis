<?php
require_once '../config/database.php';
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fonction pour envoyer un email
function sendDevisEmail($devis_id) {
    global $conn;
    
    // Récupérer les informations du devis
    $stmt = $conn->prepare("
        SELECT d.*, c.nom, c.prenom, c.email, c.telephone, v.nom as vehicule_nom, k.nom as kit_nom
        FROM devis d
        JOIN clients c ON d.id_client = c.id
        JOIN vehicules v ON d.id_vehicule = v.id
        JOIN kits k ON d.id_kit = k.id
        WHERE d.id = ?
    ");
    $stmt->execute([$devis_id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$devis) {
        return false;
    }
    
    // Récupérer les options sélectionnées
    $stmt = $conn->prepare("
        SELECT o.nom, o.prix
        FROM devis_options do
        JOIN options o ON do.id_option = o.id
        WHERE do.id_devis = ?
    ");
    $stmt->execute([$devis_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construire le contenu de l'email
    $to = $devis['email'];
    $subject = "Votre devis #" . $devis_id;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: rgb(88, 0, 189); color: white; padding: 20px; text-align: center; }
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
                    Kit : " . htmlspecialchars($devis['kit_nom']) . "
                </p>
                
                <h2>Options sélectionnées</h2>
                <table>
                    <tr>
                        <th>Option</th>
                        <th>Prix</th>
                    </tr>";
    
    foreach ($options as $option) {
        $message .= "
                    <tr>
                        <td>" . htmlspecialchars($option['nom']) . "</td>
                        <td>" . number_format($option['prix'], 2, ',', ' ') . " €</td>
                    </tr>";
    }
    
    $message .= "
                </table>
                
                <h2>Prix total</h2>
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

// Traiter la demande d'envoi d'email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devis_id'])) {
    $devis_id = $_POST['devis_id'];
    
    if (sendDevisEmail($devis_id)) {
        // Mettre à jour le statut du devis
        $stmt = $conn->prepare("UPDATE devis SET email_envoye = 1 WHERE id = ?");
        $stmt->execute([$devis_id]);
        
        header('Location: devis.php?success=email');
    } else {
        header('Location: devis.php?error=email');
    }
    exit();
}

// Rediriger si accès direct
header('Location: devis.php');
exit(); 