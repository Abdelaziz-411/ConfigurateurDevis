<?php
require '../config.php';

// Debug: Log les données POST reçues
error_log("Données POST reçues dans delete-marque.php : " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $success_redirect = 'marques.php?success=delete';
    $error_redirect = 'marques.php?error=delete';

    // Vérification basique des données requises
    if (!$id) {
        error_log("Erreur: ID de la marque manquant pour la suppression.");
        header('Location: ' . $error_redirect . '&message=' . urlencode('ID de la marque manquant.'));
        exit;
    }

    try {
        // Démarrer une transaction
        $pdo->beginTransaction();

        // Supprimer les images physiques associées à la marque
        $stmt = $pdo->prepare("SELECT image_path FROM marque_images WHERE id_marque = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $uploadDir = '../images/marques/';
        foreach ($images as $image) {
            $path = $uploadDir . $image;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Supprimer la marque de la base de données
        // Grâce à ON DELETE CASCADE, les entrées dans marque_images seront automatiquement supprimées.
        $stmt = $pdo->prepare("DELETE FROM marques WHERE id = ?");
        $stmt->execute([$id]);
        error_log("Marque ID " . $id . " supprimée.");

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
        error_log("Erreur PDO lors de la suppression de la marque ID " . $id . " : " . $e->getMessage());
        header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur BDD lors de la suppression : ' . $e->getMessage()));
        exit;
    } catch (Exception $e) {
         // Annuler la transaction en cas d'erreur
         if ($pdo->inTransaction()) {
             $pdo->rollBack();
             error_log("Transaction rollback");
         }
         error_log("Erreur générale lors de la suppression de la marque ID " . $id . " : " . $e->getMessage());
         header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur lors de la suppression : ' . $e->getMessage()));
         exit;
    }
}

// Rediriger si la méthode n'est pas POST
header('Location: marques.php');
exit; 