<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendDevisEmail($devis_id) {
    global $pdo;
    
    // Récupérer les informations du devis
    $stmt = $pdo->prepare("
        SELECT d.*, v.nom as vehicule_nom, k.nom as kit_nom
        FROM devis d
        LEFT JOIN vehicules v ON d.id_vehicule = v.id
        LEFT JOIN kits k ON d.id_kit = k.id
        WHERE d.id = ?
    ");
    $stmt->execute([$devis_id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$devis) {
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Destinataires
        $mail->setFrom(SITE_EMAIL, 'Configurateur de véhicules');
        $mail->addAddress(ADMIN_EMAIL);
        $mail->addReplyTo($devis['email'], $devis['prenom'] . ' ' . $devis['nom']);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = "Nouvelle demande de devis #" . $devis_id;
        
        // Corps du message
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
                    <h1>Nouvelle demande de devis #" . $devis_id . "</h1>
                </div>
                <div class='content'>
                    <h2>Informations client</h2>
                    <p>
                        Nom : " . safe($devis['prenom'] . ' ' . $devis['nom']) . "<br>
                        Email : " . safe($devis['email']) . "<br>
                        Téléphone : " . safe($devis['telephone']) . "
                    </p>
                    
                    <h2>Configuration</h2>
                    <p>
                        Véhicule : " . safe($devis['vehicule_nom']) . "<br>
                        Type de carrosserie : " . safe($devis['type_carrosserie']) . "<br>
                        " . ($devis['kit_nom'] ? "Kit : " . safe($devis['kit_nom']) : "") . "
                    </p>
                    
                    <h2>Détails de la configuration</h2>
                    <pre style='white-space: pre-wrap;'>" . safe($devis['configuration']) . "</pre>
                    
                    <h2>Prix total</h2>
                    <p>Prix HT : " . number_format($devis['prix_ht'], 2, ',', ' ') . " €</p>
                    <p>Prix TTC : " . number_format($devis['prix_ttc'], 2, ',', ' ') . " €</p>
                    
                    " . ($devis['message'] ? "<h2>Message du client</h2><p>" . nl2br(safe($devis['message'])) . "</p>" : "") . "
                </div>
                <div class='footer'>
                    <p>Ce message est généré automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $message));

        $mail->send();
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo);
        return false;
    }
}

// Sécuriser les appels à htmlspecialchars dans le message HTML
function safe($val) {
    return $val !== null ? htmlspecialchars($val) : '';
}   