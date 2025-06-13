<?php
require '../config.php';

// Assurez-vous que la session est démarrée si nécessaire (config.php le fait déjà)
// header('Content-Type: application/json'); // Pas nécessaire pour une redirection

// Debug: Log les données POST reçues
error_log("Données POST reçues dans save-marque.php : " . print_r($_POST, true));
error_log("Données FILES reçues dans save-marque.php : " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? null;
    $success_redirect = 'marques.php?success=add';
    $error_redirect = 'marques.php?error=add';

    // Vérification basique des données requises
    if (!$nom) {
        error_log("Erreur: Nom de la marque manquant.");
        header('Location: ' . $error_redirect . '&message=' . urlencode('Nom de la marque manquant.'));
        exit;
    }

    try {
        // Démarrer une transaction pour garantir l'atomicité
        $pdo->beginTransaction();

        // 1. Enregistrement dans la table marques
        $stmt = $pdo->prepare("INSERT INTO marques (nom) VALUES (?)");
        $stmt->execute([$nom]);
        $marque_id = $pdo->lastInsertId();
        error_log("Marque insérée avec ID : " . $marque_id);

        // 2. Upload du logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../images/marques/'; // Chemin pour les logos de marques
            // Assurez-vous que le répertoire d'upload existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['logo']['name']); // Ajouter un préfixe unique
            $targetPath = $uploadDir . $fileName;

            // Déplacer le fichier uploadé
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                // Enregistrement dans la table marque_images
                $stmt = $pdo->prepare("INSERT INTO marque_images (id_marque, image_path) VALUES (?, ?)");
                $stmt->execute([$marque_id, $fileName]);
                error_log("Logo inséré : " . $fileName);
            } else {
                error_log("Erreur lors du déplacement du logo : " . $_FILES['logo']['tmp_name']);
            }
        }

        // Valider la transaction
        $pdo->commit();
        error_log("Transaction commit");

        // Redirection en cas de succès
        header('Location: ' . $success_redirect);
        exit;

    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("Transaction rollback");
        }
        error_log("Erreur PDO lors de l'ajout de la marque : " . $e->getMessage());
        // Rediriger avec un message d'erreur
        header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur BDD : ' . $e->getMessage()));
        exit;
    } catch (Exception $e) {
         // Annuler la transaction en cas d'erreur
         if ($pdo->inTransaction()) {
             $pdo->rollBack();
             error_log("Transaction rollback");
         }
         error_log("Erreur générale lors de l'ajout de la marque : " . $e->getMessage());
         // Rediriger avec un message d'erreur
         header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur : ' . $e->getMessage()));
         exit;
    }
}

// Rediriger si la méthode n'est pas POST (accès direct au fichier)
header('Location: marques.php');
exit; 