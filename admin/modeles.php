<?php
require 'header.php';

// Récupérer les modèles avec leurs images, marques et statuts associés
try {
    // Vérifier si la table modeles existe
    $checkTable = $pdo->query("SHOW TABLES LIKE 'modeles'");
    if ($checkTable->rowCount() === 0) {
        // Créer la table modeles
        $pdo->exec("CREATE TABLE IF NOT EXISTS modeles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_marque INT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            type_carrosserie VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_marque) REFERENCES marques(id) ON DELETE CASCADE
        )");
    }
    
    // Vérifier si la table modele_images existe
    $checkImageTable = $pdo->query("SHOW TABLES LIKE 'modele_images'");
     if ($checkImageTable->rowCount() === 0) {
         // Créer la table modele_images
         $pdo->exec("CREATE TABLE IF NOT EXISTS modele_images (
             id INT AUTO_INCREMENT PRIMARY KEY,
             id_modele INT NOT NULL,
             image_path VARCHAR(255) NOT NULL,
             FOREIGN KEY (id_modele) REFERENCES modeles(id) ON DELETE CASCADE
         )");
     }

    // Vérifier si la table modele_statuts existe
    $checkStatutTable = $pdo->query("SHOW TABLES LIKE 'modele_statuts'");
    if ($checkStatutTable->rowCount() === 0) {
        // Créer la table modele_statuts
        $pdo->exec("CREATE TABLE IF NOT EXISTS modele_statuts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_modele INT NOT NULL,
            statut VARCHAR(50) NOT NULL,
            FOREIGN KEY (id_modele) REFERENCES modeles(id) ON DELETE CASCADE,
            UNIQUE (id_modele, statut) -- Empêche les doublons
        )");
    }

    // Récupérer les modèles de base avec leur marque
    $sql = "SELECT m.*, ma.nom as marque_nom
            FROM modeles m
            LEFT JOIN marques ma ON m.id_marque = ma.id
            ORDER BY ma.nom, m.nom";
    
    $stmt = $pdo->query($sql);
    $modeles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque modèle, récupérer ses images et statuts
    foreach ($modeles as &$modele) {
        // Récupérer les images
        $stmt = $pdo->prepare("SELECT image_path FROM modele_images WHERE id_modele = ?");
        $stmt->execute([$modele['id']]);
        $modele['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Récupérer les statuts
        $stmt = $pdo->prepare("SELECT statut FROM modele_statuts WHERE id_modele = ?");
        $stmt->execute([$modele['id']]);
        $modele['statuts'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($modele); // Important : détacher la référence
} catch (Exception $e) {
    die("Une erreur est survenue : " . $e->getMessage());
}

// Récupérer la liste des marques pour le formulaire
$marques = $pdo->query("SELECT * FROM marques ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Liste des statuts de carrosserie possibles
$statuts_possibles = [
    'L1H1',
    'L2H1',
    'L2H2',
    'L3H2',
    'L3H3',
    'L4H3'
];

// Gestion des actions
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $nom = $_POST['nom'];
            $id_marque = $_POST['id_marque'];
            // La colonne type_carrosserie dans la table modeles pourrait stocker un statut principal ou être ignorée si tous les statuts sont dans modele_statuts.
            // Pour l'instant, on peut la laisser et potentiellement la rendre nullable ou stocker une valeur par défaut.
            // Si on la garde, on pourrait prendre le premier statut sélectionné ou une valeur générique.
            // Pour la gestion de la compatibilité multi-statuts, on va se fier à la nouvelle table modele_statuts.
            // On peut stocker le premier statut sélectionné ici pour la rétrocompatibilité ou l'ignorer.
            $premier_statut = !empty($_POST['statuts']) ? $_POST['statuts'][0] : ''; 
            
            if ($_POST['action'] === 'add') {
                try {
                    // Insérer le modèle dans la table modeles
                    $stmt = $pdo->prepare("INSERT INTO modeles (nom, id_marque, type_carrosserie) VALUES (?, ?, ?)"); 
                    $stmt->execute([$nom, $id_marque, $premier_statut]); // Utilisation du premier statut ou vide
                    $id = $pdo->lastInsertId();
                    
                    // Insérer les statuts sélectionnés dans la table modele_statuts
                    if (!empty($_POST['statuts'])) {
                        $stmt_statut = $pdo->prepare("INSERT INTO modele_statuts (id_modele, statut) VALUES (?, ?)");
                        foreach ($_POST['statuts'] as $statut) {
                            $stmt_statut->execute([$id, $statut]);
                        }
                    }
                    
                    // Gestion des images
                    if (!empty($_FILES['images']['name'][0])) {
                        // Créer le dossier s'il n'existe pas
                        $upload_dir = '../images/modeles/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            $file = $_FILES['images']['name'][$key];
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $filename = uniqid() . '.' . $ext;
                                $path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($tmp_name, $path)) {
                                    $stmt = $pdo->prepare("INSERT INTO modele_images (id_modele, image_path) VALUES (?, ?)");
                                    $stmt->execute([$id, $filename]);
                                }
                            }
                        }
                    }
                    
                    header('Location: modeles.php?success=add');
                    exit;
                } catch (Exception $e) {
                    $message = "Erreur lors de l'ajout du modèle : " . $e->getMessage();
                }
            } else { // action === 'edit'
                try {
                    $id = $_POST['id'];
                    
                    // Mettre à jour le modèle dans la table modeles
                    $stmt = $pdo->prepare("UPDATE modeles SET nom = ?, id_marque = ?, type_carrosserie = ? WHERE id = ?"); // Utilisation du premier statut ou vide
                    $stmt->execute([$nom, $id_marque, $premier_statut, $id]);
                    
                    // Supprimer les anciens statuts du modèle
                    $stmt_delete_statuts = $pdo->prepare("DELETE FROM modele_statuts WHERE id_modele = ?");
                    $stmt_delete_statuts->execute([$id]);

                    // Insérer les nouveaux statuts sélectionnés
                    if (!empty($_POST['statuts'])) {
                        $stmt_statut = $pdo->prepare("INSERT INTO modele_statuts (id_modele, statut) VALUES (?, ?)");
                        foreach ($_POST['statuts'] as $statut) {
                             // Utiliser INSERT IGNORE pour éviter les erreurs si un statut existe déjà (bien que le DELETE avant l'empêche normalement) ou gérer l'exception UNIQUE.
                             // Pour plus de robustesse, un INSERT IGNORE ou un try/catch sur execute est préférable.
                             try {
                                $stmt_statut->execute([$id, $statut]);
                             } catch (PDOException $e) {
                                 // Ignorer l'erreur de duplicata si la contrainte UNIQUE est violée
                                 if ($e->getCode() != '23000') { // SQLSTATE 23000 est pour l'intégrité contrainte violation
                                     throw $e; // Relancer si ce n'est pas une erreur de duplicata
                                 }
                             }
                        }
                    }
                    
                    // Gestion des images
                    if (!empty($_FILES['images']['name'][0])) {
                        // Créer le dossier s'il n'existe pas
                        $upload_dir = '../images/modeles/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            $file = $_FILES['images']['name'][$key];
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $filename = uniqid() . '.' . $ext;
                                $path = $upload_dir . $filename;
                                
                                if (move_uploaded_file($tmp_name, $path)) {
                                    $stmt = $pdo->prepare("INSERT INTO modele_images (id_modele, image_path) VALUES (?, ?)");
                                    $stmt->execute([$id, $filename]);
                                }
                            }
                        }
                    }
                    
                    header('Location: modeles.php?success=edit');
                    exit;
                } catch (Exception $e) {
                    $message = "Erreur lors de la modification du modèle : " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            try {
                $id = $_POST['id'];
                
                // Récupérer les images du modèle
                $stmt = $pdo->prepare("SELECT image_path FROM modele_images WHERE id_modele = ?");
                $stmt->execute([$id]);
                $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Supprimer les fichiers physiques
                foreach ($images as $image) {
                    $path = "../images/modeles/" . $image;
                    if (file_exists($path)) {
                        unlink($path);
                    }
                }
                
                // Supprimer les enregistrements de la base de données
                // Les suppressions dans modele_images et modele_statuts sont gérées par ON DELETE CASCADE
                $pdo->beginTransaction();
                
                // Supprimer le modèle (ce qui cascade la suppression des images et statuts)
                $stmt = $pdo->prepare("DELETE FROM modeles WHERE id = ?");
                $stmt->execute([$id]);
                
                $pdo->commit();
                
                header('Location: modeles.php?success=delete');
                exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = "Erreur lors de la suppression du modèle : " . $e->getMessage();
            }
        }
    }
}

// Affichage de la page
if ($action === 'list') {
    include 'templates/modeles/list.php';
} elseif ($action === 'edit' && isset($_GET['id'])) {
    // Récupérer les données du modèle pour le formulaire d'édition
    $stmt = $pdo->prepare("SELECT m.*, 
                          GROUP_CONCAT(mi.image_path) as images,
                          GROUP_CONCAT(ms.statut) as statuts -- Récupérer les statuts associés
                          FROM modeles m
                          LEFT JOIN modele_images mi ON m.id = mi.id_modele
                          LEFT JOIN modele_statuts ms ON m.id = ms.id_modele
                          WHERE m.id = ?
                          GROUP BY m.id");
    $stmt->execute([$id]);
    $modele_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$modele_edit) {
        // Rediriger si le modèle n'est pas trouvé
        header('Location: modeles.php');
        exit;
    }
    
    // Transformer les données (images et statuts)
    $modele_edit['images'] = $modele_edit['images'] ? explode(',', $modele_edit['images']) : [];
    $modele_edit['statuts'] = $modele_edit['statuts'] ? explode(',', $modele_edit['statuts']) : [];
    
    include 'templates/modeles/edit.php';
} elseif ($action === 'add') {
    include 'templates/modeles/add.php';
}

// Styles et scripts communs
?>
<style>
.preview-image {
    display: inline-block;
    margin: 5px;
    text-align: center;
}

.preview-image img {
    max-width: 100px;
    height: auto;
}

.img-thumbnail {
    object-fit: cover;
    width: 100px;
    height: 100px;
}

.btn-group {
    gap: 0.25rem;
}

.alert {
    margin-bottom: 1rem;
}

.table-responsive {
    margin-bottom: 1rem;
}

.table th {
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
}

.statut-tags span {
    display: inline-block;
    background-color: #e9ecef;
    color: #495057;
    border-radius: 0.25rem;
    padding: 0.25rem 0.5rem;
    margin-right: 0.5rem;
    margin-bottom: 0.25rem;
}
</style>

<script>
// Fonction pour afficher les images en prévisualisation
function previewImages(input) {
    const previewContainer = document.getElementById('imagePreview');
    previewContainer.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-image';
                div.innerHTML = `
                    <img src="${e.target.result}" class="img-thumbnail" style="height: 100px;">
                    <button type="button" class="btn btn-sm btn-danger mt-1" onclick="this.parentElement.remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                previewContainer.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }
}
</script>

<?php require 'footer.php'; ?> 