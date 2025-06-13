<?php
require '../config.php';

// Assurez-vous que la session est démarrée si nécessaire (config.php le fait déjà)
// header('Content-Type: application/json'); // Pas nécessaire pour une redirection

// Debug: Log les données POST reçues
error_log("Données POST reçues dans save-kit.php : " . print_r($_POST, true));
error_log("Données FILES reçues dans save-kit.php : " . print_r($_FILES, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? null;
    $description = $_POST['description'] ?? null;
    $vehicules_selectionnes = $_POST['vehicules'] ?? [];
    $success_redirect = 'kits.php?success=add';
    $error_redirect = 'kits.php?error=add'; // Ajoutez une gestion des erreurs pour la redirection

    // Vérification basique des données requises
    if (!$nom || empty($vehicules_selectionnes)) {
        error_log("Erreur: Nom du kit manquant ou aucun véhicule sélectionné.");
        // Rediriger avec un message d'erreur ou afficher un message
        header('Location: ' . $error_redirect . '&message=' . urlencode('Nom du kit manquant ou aucun véhicule sélectionné.'));
        exit;
    }

    try {
        // Démarrer une transaction pour garantir l'atomicité
        $pdo->beginTransaction();

        // 1. Enregistrement dans la table kits
        $stmt = $pdo->prepare("INSERT INTO kits (nom, description) VALUES (?, ?)");
        $stmt->execute([$nom, $description]);
        $kit_id = $pdo->lastInsertId();
        error_log("Kit inséré avec ID : " . $kit_id);

        // 2. Enregistrement des compatibilités avec les véhicules et leurs prix
        foreach ($vehicules_selectionnes as $vehicule_id) {
            $prix_key = 'prix_' . $vehicule_id;
            $prix = $_POST[$prix_key] ?? 0.00; // Récupérer le prix pour ce véhicule
            $prix = floatval(str_replace(',', '.', $prix)); // Convertir en float (gérer les virgules)
            
            error_log("Ajout de compatibilité pour Véhicule ID " . $vehicule_id . " avec Prix : " . $prix);
            
            // Utiliser la table correcte : kit_vehicule_compatibilite
            $stmt = $pdo->prepare("INSERT INTO kit_vehicule_compatibilite (id_kit, id_vehicule, prix) VALUES (?, ?, ?)");
            $stmt->execute([$kit_id, $vehicule_id, $prix]);
            error_log("Compatibilité insérée pour kit " . $kit_id . " et vehicule " . $vehicule_id);
        }

        // 3. Upload des images
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../images/kits/'; // Chemin correct pour les images de kits
            // Assurez-vous que le répertoire d'upload existe
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                // Vérifier qu'il n'y a pas d'erreur d'upload pour ce fichier
                if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$index]); // Ajouter un préfixe unique pour éviter les conflits
                    $targetPath = $uploadDir . $fileName;

                    // Déplacer le fichier uploadé
                    if (move_uploaded_file($tmpName, $targetPath)) {
                        // Enregistrement dans la table kit_images
                        $stmt = $pdo->prepare("INSERT INTO kit_images (id_kit, image_path) VALUES (?, ?)");
                        $stmt->execute([$kit_id, $fileName]);
                        error_log("Image insérée : " . $fileName);
                    } else {
                        error_log("Erreur lors du déplacement de l'image : " . $tmpName);
                    }
                } else {
                    error_log("Erreur d'upload pour le fichier " . $_FILES['images']['name'][$index] . ": Code " . $_FILES['images']['error'][$index]);
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
        error_log("Erreur PDO lors de l'ajout du kit : " . $e->getMessage());
        // Rediriger avec un message d'erreur
        header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur BDD : ' . $e->getMessage()));
        exit;
    } catch (Exception $e) {
         // Annuler la transaction en cas d'erreur
         if ($pdo->inTransaction()) {
             $pdo->rollBack();
             error_log("Transaction rollback");
         }
         error_log("Erreur générale lors de l'ajout du kit : " . $e->getMessage());
         // Rediriger avec un message d'erreur
         header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur : ' . $e->getMessage()));
         exit;
    }
}

// Rediriger si la méthode n'est pas POST (accès direct au fichier)
header('Location: kits.php');
exit;
