<?php
require '../config.php';

// Debug: Log les données POST reçues
error_log("Données POST reçues dans update-modele.php : " . print_r($_POST, true));
error_log("Données FILES reçues dans update-modele.php : " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nom = $_POST['nom'] ?? null;
    $id_marque = $_POST['id_marque'] ?? null;
    $status = $_POST['status'] ?? null; // Statut est optionnel

    $success_redirect = 'modeles.php?success=edit';
    $error_redirect = 'modeles.php?error=edit';

    // Vérification basique des données requises
    if (!$id || !$nom || !$id_marque) {
        error_log("Erreur: ID, nom du modèle ou ID de la marque manquant pour la mise à jour.");
        header('Location: ' . $error_redirect . '&message=' . urlencode('ID, nom du modèle ou marque manquante.'));
        exit;
    }

    try {
        // Démarrer une transaction
        $pdo->beginTransaction();

        // 1. Mettre à jour les informations du modèle
        $stmt = $pdo->prepare("UPDATE modeles SET nom = ?, id_marque = ?, status = ? WHERE id = ?");
        $stmt->execute([$nom, $id_marque, $status, $id]);
        error_log("Modèle ID " . $id . " mis à jour.");

        // 2. Gérer l'upload de nouvelles photos (si des fichiers sont sélectionnés)
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $uploadDir = '../images/modeles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Supprimer les anciennes images physiques associées à ce modèle
            $stmt = $pdo->prepare("SELECT image_path FROM modele_images WHERE id_modele = ?");
            $stmt->execute([$id]);
            $old_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($old_images as $old_image_path) {
                $old_file_path = $uploadDir . $old_image_path;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }

            // Supprimer les anciennes références dans la base de données
            $stmt = $pdo->prepare("DELETE FROM modele_images WHERE id_modele = ?");
            $stmt->execute([$id]);

            foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                 if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$index]);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        // Insérer la référence à la nouvelle photo
                        $stmt = $pdo->prepare("INSERT INTO modele_images (id_modele, image_path) VALUES (?, ?)");
                        $stmt->execute([$id, $fileName]);
                        error_log("Nouvelle photo de modèle insérée : " . $fileName);
                    } else {
                        error_log("Erreur lors du déplacement de la nouvelle photo de modèle : " . $tmpName);
                        // Ne pas arrêter la transaction pour une erreur d'upload non bloquante
                    }
                 } else {
                    error_log("Erreur d'upload pour le fichier " . ($_FILES['images']['name'][$index] ?? 'inconnu') . ": Code " . $_FILES['images']['error'][$index]);
                }
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
        error_log("Erreur PDO lors de la mise à jour du modèle ID " . $id . " : " . $e->getMessage());
        header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur BDD lors de la mise à jour : ' . $e->getMessage()));
        exit;
    } catch (Exception $e) {
         // Annuler la transaction en cas d'erreur
         if ($pdo->inTransaction()) {
             $pdo->rollBack();
             error_log("Transaction rollback");
         }
         error_log("Erreur générale lors de la mise à jour du modèle ID " . $id . " : " . $e->getMessage());
         header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur lors de la mise à jour : ' . $e->getMessage()));
         exit;
    }
}

// Rediriger si la méthode n'est pas POST
header('Location: modeles.php');
exit; 