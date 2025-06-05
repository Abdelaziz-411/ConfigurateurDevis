<?php
require '../config.php';

// Debug: Log les données POST reçues
error_log("Données POST reçues dans update-marque.php : " . print_r($_POST, true));
error_log("Données FILES reçues dans update-marque.php : " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nom = $_POST['nom'] ?? null;
    $success_redirect = 'marques.php?success=edit';
    $error_redirect = 'marques.php?error=edit';

    // Vérification basique des données requises
    if (!$id || !$nom) {
        error_log("Erreur: ID ou nom de la marque manquant pour la mise à jour.");
        header('Location: ' . $error_redirect . '&message=' . urlencode('ID ou nom de la marque manquant.'));
        exit;
    }

    try {
        // Démarrer une transaction
        $pdo->beginTransaction();

        // 1. Mettre à jour le nom de la marque
        $stmt = $pdo->prepare("UPDATE marques SET nom = ? WHERE id = ?");
        $stmt->execute([$nom, $id]);
        error_log("Marque ID " . $id . " mise à jour.");

        // 2. Gérer l'upload d'un nouveau logo (si un fichier est sélectionné)
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../images/marques/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['logo']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                // Supprimer l'ancien logo (s'il existe)
                $stmt = $pdo->prepare("SELECT image_path FROM marque_images WHERE id_marque = ?");
                $stmt->execute([$id]);
                $old_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($old_images as $old_image_path) {
                    $old_file_path = $uploadDir . $old_image_path;
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }

                // Supprimer les anciennes références dans la base de données
                $stmt = $pdo->prepare("DELETE FROM marque_images WHERE id_marque = ?");
                $stmt->execute([$id]);

                // Insérer la référence au nouveau logo
                $stmt = $pdo->prepare("INSERT INTO marque_images (id_marque, image_path) VALUES (?, ?)");
                $stmt->execute([$id, $fileName]);
                error_log("Nouveau logo inséré : " . $fileName);

            } else {
                error_log("Erreur lors du déplacement du nouveau logo : " . $_FILES['logo']['tmp_name']);
                // Ne pas arrêter la transaction pour une erreur d'upload non bloquante
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
        error_log("Erreur PDO lors de la mise à jour de la marque ID " . $id . " : " . $e->getMessage());
        header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur BDD lors de la mise à jour : ' . $e->getMessage()));
        exit;
    } catch (Exception $e) {
         // Annuler la transaction en cas d'erreur
         if ($pdo->inTransaction()) {
             $pdo->rollBack();
             error_log("Transaction rollback");
         }
         error_log("Erreur générale lors de la mise à jour de la marque ID " . $id . " : " . $e->getMessage());
         header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur lors de la mise à jour : ' . $e->getMessage()));
         exit;
    }
}

// Rediriger si la méthode n'est pas POST
header('Location: marques.php');
exit; 