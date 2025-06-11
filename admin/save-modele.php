<?php
require '../config.php';

// Assurez-vous que la session est démarrée si nécessaire (config.php le fait déjà)
// header('Content-Type: application/json'); // Pas nécessaire pour une redirection

// Debug: Log les données POST reçues
// error_log("Données POST reçues dans save-modele.php : " . print_r($_POST, true));
// error_log("Données FILES reçues dans save-modele.php : " . print_r($_FILES, true));

header('Content-Type: application/json'); // Ajouter cet en-tête pour la réponse JSON

$response = ['success' => false, 'message' => 'Méthode non autorisée'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? null;
    $id_marque = $_POST['id_marque'] ?? null;
    // Récupérer le tableau des types de carrosserie sélectionnés
    $types_carrosserie_selectionnes = $_POST['types_carrosserie'] ?? []; 

    // $success_redirect = 'modeles.php?success=add';
    // $error_redirect = 'modeles.php?error=add';

    // Vérification basique des données requises
    if (!$nom || !$id_marque) {
        // error_log("Erreur: Nom du modèle ou ID de la marque manquant.");
        // header('Location: ' . $error_redirect . '&message=' . urlencode('Nom du modèle ou marque manquante.'));
        $response = ['success' => false, 'message' => 'Nom du modèle ou marque manquante.'];
    } else {
        try {
            // Démarrer une transaction pour garantir l'atomicité
            $pdo->beginTransaction();

            // 1. Enregistrement dans la table modeles (sans le champ status)
            $stmt = $pdo->prepare("INSERT INTO modeles (nom, id_marque) VALUES (?, ?)");
            $stmt->execute([$nom, $id_marque]);
            $modele_id = $pdo->lastInsertId();
            // error_log("Modèle inséré avec ID : " . $modele_id);

            // 2. Enregistrement des types de carrosserie compatibles dans la nouvelle table de liaison
            if (!empty($types_carrosserie_selectionnes)) {
                $stmt_compat = $pdo->prepare("INSERT INTO modele_type_carrosserie_compatibilite (id_modele, type_carrosserie) VALUES (?, ?)");
                foreach ($types_carrosserie_selectionnes as $type_carrosserie) {
                     // Assurez-vous que le type de carrosserie n'est pas vide avant d'insérer
                    if (!empty($type_carrosserie)) {
                       $stmt_compat->execute([$modele_id, $type_carrosserie]);
                       // error_log("Compatibilité ajoutée pour modèle " . $modele_id . " avec type " . $type_carrosserie);
                    }
                }
            }

            $uploaded_images = [];
            $upload_errors = [];

            // 3. Upload des photos
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $uploadDir = '../images/modeles/'; // Chemin pour les photos de modèles
                // Assurez-vous que le répertoire d'upload existe
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
                    $fileName = $_FILES['images']['name'][$index];
                    $fileError = $_FILES['images']['error'][$index];

                    // Vérifier qu'il n'y a pas d'erreur d'upload pour ce fichier
                    if ($fileError === UPLOAD_ERR_OK) {
                        $uniqueFileName = uniqid() . '_' . basename($fileName); // Ajouter un préfixe unique
                        $targetPath = $uploadDir . $uniqueFileName;

                        // Déplacer le fichier uploadé
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            // Enregistrement dans la table modele_images
                            $stmt = $pdo->prepare("INSERT INTO modele_images (id_modele, image_path) VALUES (?, ?)");
                            $stmt->execute([$modele_id, $uniqueFileName]);
                            $uploaded_images[] = $uniqueFileName;
                            // error_log("Photo de modèle insérée : " . $uniqueFileName);
                        } else {
                            $upload_errors[] = "Erreur lors du déplacement de la photo {$fileName}.";
                            // error_log("Erreur lors du déplacement de la photo de modèle : " . $tmpName);
                        }
                    } else {
                        $upload_errors[] = "Erreur d'upload pour le fichier {$fileName}: Code {$fileError}.";
                    }
                }
            }

            // Valider la transaction si tout s'est bien passé pour le modèle et les compatibilités.
             // Si des images étaient attendues et qu'il y a des erreurs, on reporte les erreurs d'upload mais on ne rollback pas tout
             // sauf si AUCUNE image n'a été uploadée du tout malgré la tentative.
             $commit_transaction = true;
             if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0]) && empty($uploaded_images) && !empty($upload_errors)) {
                  // Tentative d'upload d'images a échoué complètement
                 $pdo->rollBack(); // Annuler l'insertion du modèle et des compatibilités
                 $response = [
                     'success' => false, 
                     'message' => 'Erreur(s) lors de l\'upload des images. Modèle non ajouté.', 
                     'upload_errors' => $upload_errors
                 ];
                 $commit_transaction = false;
             }

            if ($commit_transaction) {
                $pdo->commit();
                 $response = ['success' => true, 'message' => 'Modèle ajouté avec succès.', 'modele_id' => $modele_id, 'uploaded_images' => $uploaded_images, 'upload_errors' => $upload_errors];
                // error_log("Transaction commit");
            }

            // Redirection en cas de succès
            // header('Location: ' . $success_redirect);
            // exit;

        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                // error_log("Transaction rollback");
            }
            // error_log("Erreur PDO lors de l'ajout du modèle : " . $e->getMessage());
            // Rediriger avec un message d'erreur
            // header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur BDD : ' . $e->getMessage()));
            $response = ['success' => false, 'message' => 'Erreur BDD lors de l\'ajout du modèle.', 'error' => $e->getMessage()];
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                // error_log("Transaction rollback");
            }
            // error_log("Erreur générale lors de l'ajout du modèle : " . $e->getMessage());
            // Rediriger avec un message d'erreur
            // header('Location: ' . $error_redirect . '&message=' . urlencode('Erreur : ' . $e->getMessage()));
            $response = ['success' => false, 'message' => 'Erreur générale lors de l\'ajout du modèle.', 'error' => $e->getMessage()];
        }
    }
}

// Rediriger si la méthode n'est pas POST (accès direct au fichier)
// header('Location: modeles.php');
// exit;

echo json_encode($response);
?> 