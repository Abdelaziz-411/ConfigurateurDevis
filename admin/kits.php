<?php
require 'header.php';

// Récupérer les kits avec leurs images et véhicules associés
try {
    $sql = "
        SELECT k.id, k.nom, k.description, k.prix, k.created_at,
               GROUP_CONCAT(DISTINCT CONCAT(v.id, ':', CAST(kvc.prix AS CHAR))) as vehicules_prix,
               GROUP_CONCAT(DISTINCT ki.image_path) as images
        FROM kits k
        LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
        LEFT JOIN vehicules v ON kvc.id_vehicule = v.id
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
        GROUP BY k.id, k.nom, k.description, k.prix, k.created_at
        ORDER BY k.nom
    ";
    error_log("Requête SQL : " . $sql);
    
    // Exécuter la requête en plusieurs étapes pour mieux identifier les problèmes
    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        error_log("Erreur de préparation de la requête : " . print_r($pdo->errorInfo(), true));
        die("Erreur de préparation de la requête");
    }
    
    if (!$stmt->execute()) {
        error_log("Erreur d'exécution de la requête : " . print_r($stmt->errorInfo(), true));
        die("Erreur d'exécution de la requête");
    }
    
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Nombre de kits récupérés : " . count($kits));
    error_log("Contenu des kits : " . print_r($kits, true));
} catch (PDOException $e) {
    error_log("Erreur SQL : " . $e->getMessage());
    error_log("Code erreur : " . $e->getCode());
    error_log("Détails de l'erreur : " . print_r($pdo->errorInfo(), true));
    die("Erreur lors de la requête SQL : " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erreur générale : " . $e->getMessage());
    die("Erreur : " . $e->getMessage());
}

// Transformer les chaînes en tableaux
foreach ($kits as &$kit) {
    if ($kit['vehicules_prix']) {
        $vehicules_prix = explode(',', $kit['vehicules_prix']);
        $kit['vehicules_prix'] = [];
        foreach ($vehicules_prix as $vp) {
            list($id, $prix) = explode(':', $vp);
            $kit['vehicules_prix'][$id] = $prix;
        }
    } else {
        $kit['vehicules_prix'] = [];
    }
    if ($kit['images']) {
        $kit['images'] = explode(',', $kit['images']);
    } else {
        $kit['images'] = [];
    }
}

// Gestion des actions
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        error_log("Action reçue : " . $_POST['action']);
        
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $nom = $_POST['nom'];
            $description = $_POST['description'];
            error_log("Nom : " . $nom);
            error_log("Description : " . $description);
            
            if ($_POST['action'] === 'add') {
                try {
                    if (empty($_POST['vehicules']) || !is_array($_POST['vehicules'])) {
                        throw new Exception("Veuillez sélectionner au moins un véhicule compatible");
                    }

                    // Insérer le kit avec un prix par défaut de 0
                    $stmt = $pdo->prepare("INSERT INTO kits (nom, description, prix) VALUES (?, ?, 0.00)");
                    $stmt->execute([$nom, $description]);
                    $id = $pdo->lastInsertId();
                    
                    // Ajouter les compatibilités avec les véhicules
                    foreach ($_POST['vehicules'] as $vehicule_id) {
                        $prix_key = 'prix_' . $vehicule_id;
                        
                        // Vérifier si le prix est défini et le convertir en nombre
                        $prix = 0.00; // Valeur par défaut
                        if (isset($_POST[$prix_key]) && $_POST[$prix_key] !== '') {
                            $prix = str_replace(',', '.', $_POST[$prix_key]); // Remplacer la virgule par un point
                            $prix = floatval($prix);
                        }

                        // Insérer avec une requête préparée
                        $stmt = $pdo->prepare("INSERT INTO kit_vehicule_compatibilite (id_kit, id_vehicule, prix) VALUES (:id_kit, :id_vehicule, :prix)");
                        $stmt->execute([
                            ':id_kit' => $id,
                            ':id_vehicule' => $vehicule_id,
                            ':prix' => $prix
                        ]);
                    }
                    
                    // Gestion des images
                    if (!empty($_FILES['images']['name'][0])) {
                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            $file = $_FILES['images']['name'][$key];
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                $filename = uniqid() . '.' . $ext;
                                $path = '../images/kits/' . $filename;
                                
                                if (move_uploaded_file($tmp_name, $path)) {
                                    $stmt = $pdo->prepare("INSERT INTO kit_images (id_kit, image_path) VALUES (?, ?)");
                                    $stmt->execute([$id, $filename]);
                                }
                            }
                        }
                    }
                    
                    header('Location: kits.php?success=add');
                    exit;
                } catch (PDOException $e) {
                    die("Une erreur est survenue lors de l'ajout du kit : " . $e->getMessage());
                } catch (Exception $e) {
                    die("Une erreur est survenue : " . $e->getMessage());
                }
            } else {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE kits SET nom = ?, description = ? WHERE id = ?");
                $stmt->execute([$nom, $description, $id]);
                
                // Mettre à jour les compatibilités avec les véhicules
                // D'abord supprimer les anciennes
                $stmt = $pdo->prepare("DELETE FROM kit_vehicule_compatibilite WHERE id_kit = ?");
                $stmt->execute([$id]);
                
                // Puis ajouter les nouvelles
                foreach ($_POST['vehicules'] as $vehicule_id) {
                    $prix_key = 'prix_' . $vehicule_id;
                    error_log("Recherche du prix pour la clé : " . $prix_key);
                    
                    // Vérifier si le prix est défini et le convertir en nombre
                    if (isset($_POST[$prix_key])) {
                        $prix = str_replace(',', '.', $_POST[$prix_key]); // Remplacer la virgule par un point
                        $prix = floatval($prix);
                        error_log("Prix trouvé et converti : " . $prix);
                    } else {
                        $prix = 0.00;
                        error_log("Prix non trouvé, utilisation de la valeur par défaut : 0.00");
                    }

                    // Insérer avec une requête préparée
                    $stmt = $pdo->prepare("INSERT INTO kit_vehicule_compatibilite (id_kit, id_vehicule, prix) VALUES (:id_kit, :id_vehicule, :prix)");
                    $stmt->execute([
                        ':id_kit' => $id,
                        ':id_vehicule' => $vehicule_id,
                        ':prix' => $prix
                    ]);
                    error_log("Insertion réussie pour le véhicule " . $vehicule_id);
                }
                
                // Gestion des images
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        $file = $_FILES['images']['name'][$key];
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            $filename = uniqid() . '.' . $ext;
                            $path = '../images/kits/' . $filename;
                            
                            if (move_uploaded_file($tmp_name, $path)) {
                                $stmt = $pdo->prepare("INSERT INTO kit_images (id_kit, image_path) VALUES (?, ?)");
                                $stmt->execute([$id, $filename]);
                            }
                        }
                    }
                }
                
                header('Location: kits.php?success=edit');
                exit;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            
            // Supprimer les images physiques
            $stmt = $pdo->prepare("SELECT image_path FROM kit_images WHERE id_kit = ?");
            $stmt->execute([$id]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($images as $image) {
                $path = '../images/kits/' . $image;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            
            // Supprimer le kit (les images et compatibilités seront supprimées automatiquement grâce à ON DELETE CASCADE)
            $stmt = $pdo->prepare("DELETE FROM kits WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: kits.php?success=delete');
            exit;
        }
    }
}

// Récupérer les kits avec leurs images et véhicules associés
try {
    // Vérifier d'abord la structure des tables
    $stmt = $pdo->query("SHOW COLUMNS FROM kits");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Colonnes de la table kits : " . print_r($columns, true));

    $stmt = $pdo->query("SHOW COLUMNS FROM kit_vehicule_compatibilite");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Colonnes de kit_vehicule_compatibilite : " . print_r($columns, true));

    $stmt = $pdo->query("SHOW COLUMNS FROM vehicules");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Colonnes de vehicules : " . print_r($columns, true));

    $stmt = $pdo->query("SHOW COLUMNS FROM kit_images");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Colonnes de kit_images : " . print_r($columns, true));

    // Vérifier les relations
    $stmt = $pdo->query("SELECT COUNT(*) FROM kits");
    $count = $stmt->fetchColumn();
    error_log("Nombre total de kits : " . $count);

    $stmt = $pdo->query("SELECT COUNT(*) FROM kit_vehicule_compatibilite");
    $count = $stmt->fetchColumn();
    error_log("Nombre d'entrées dans kit_vehicule_compatibilite : " . $count);

    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicules");
    $sql = "
        SELECT k.id, k.nom, k.description, k.prix, k.created_at,
               GROUP_CONCAT(DISTINCT CONCAT(v.id, ':', CAST(kvc.prix AS CHAR))) as vehicules_prix,
               GROUP_CONCAT(DISTINCT ki.image_path) as images
        FROM kits k
        LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
        LEFT JOIN vehicules v ON kvc.id_vehicule = v.id
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
        GROUP BY k.id, k.nom, k.description, k.prix, k.created_at
        ORDER BY k.nom
    ";
    error_log("Requête SQL : " . $sql);
    
    // Exécuter la requête en plusieurs étapes pour mieux identifier les problèmes
    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        error_log("Erreur de préparation de la requête : " . print_r($pdo->errorInfo(), true));
        die("Erreur de préparation de la requête");
    }
    
    if (!$stmt->execute()) {
        error_log("Erreur d'exécution de la requête : " . print_r($stmt->errorInfo(), true));
        die("Erreur d'exécution de la requête");
    }
    
    $kits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Nombre de kits récupérés : " . count($kits));
    error_log("Contenu des kits : " . print_r($kits, true));
} catch (PDOException $e) {
    error_log("Erreur SQL : " . $e->getMessage());
    error_log("Code erreur : " . $e->getCode());
    error_log("Détails de l'erreur : " . print_r($pdo->errorInfo(), true));
    die("Erreur lors de la requête SQL : " . $e->getMessage());
} catch (Exception $e) {
    error_log("Erreur générale : " . $e->getMessage());
    die("Erreur : " . $e->getMessage());
}

// Transformer les chaînes en tableaux
foreach ($kits as &$kit) {
    $kit['images'] = $kit['images'] ? explode(',', $kit['images']) : [];
    $kit['vehicules_prix'] = $kit['vehicules_prix'] ? array_reduce(
        explode(',', $kit['vehicules_prix']),
        function($carry, $item) {
            list($id, $prix) = explode(':', $item);
            $carry[$id] = $prix;
            return $carry;
        },
        []
    ) : [];
}

// Récupérer la liste des véhicules pour le formulaire
$vehicules = $pdo->query("SELECT id, nom FROM vehicules ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Kits</h2>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter un kit
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 'add':
                echo 'Kit ajouté avec succès.';
                break;
            case 'edit':
                echo 'Kit modifié avec succès.';
                break;
            case 'delete':
                echo 'Kit supprimé avec succès.';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter un kit</h3>
        </div>
        <div class="card-body">
            <form action="save-kit.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Véhicules compatibles</label>
                    <div class="vehicules-compatibles">
                        <?php foreach ($vehicules as $vehicule): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="vehicule<?= $vehicule['id'] ?>" name="vehicules[]" value="<?= $vehicule['id'] ?>">
                                <label class="form-check-label" for="vehicule<?= $vehicule['id'] ?>">
                                    <?= htmlspecialchars($vehicule['nom']) ?>
                                </label>
                                <div class="price-input mt-2" style="display: none;">
                                    <label for="prix_<?= $vehicule['id'] ?>" class="form-label">Prix pour ce véhicule (€)</label>
                                    <input type="number" class="form-control" id="prix_<?= $vehicule['id'] ?>" name="prix_<?= $vehicule['id'] ?>" step="0.01" min="0">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="images" class="form-label">Images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="kits.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
    <?php
    $stmt = $pdo->prepare("
        SELECT k.*, 
               GROUP_CONCAT(DISTINCT CONCAT(v.id, ':', kvc.prix)) as vehicules_prix,
               GROUP_CONCAT(kvc.id_vehicule) as vehicules_ids
        FROM kits k
        LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
        LEFT JOIN vehicules v ON kvc.id_vehicule = v.id
        WHERE k.id = ?
        GROUP BY k.id
    ");
    $stmt->execute([$_GET['id']]);
    $kit = $stmt->fetch(PDO::FETCH_ASSOC);
    $vehicules_ids = $kit['vehicules_ids'] ? explode(',', $kit['vehicules_ids']) : [];
    $vehicules_prix = $kit['vehicules_prix'] ? array_reduce(
        explode(',', $kit['vehicules_prix']),
        function($carry, $item) {
            list($id, $prix) = explode(':', $item);
            $carry[$id] = $prix;
            return $carry;
        },
        []
    ) : [];
    ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifier le kit</h3>
        </div>
        <div class="card-body">
            <form id="editKitForm<?= $kit['id'] ?>" onsubmit="return updateKit(event, <?= $kit['id'] ?>)" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $kit['id'] ?>">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($kit['nom']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($kit['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Véhicules compatibles</label>
                    <div class="vehicules-compatibles">
                        <?php foreach ($vehicules as $vehicule): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="vehicule<?= $vehicule['id'] ?>" name="vehicules[]" value="<?= $vehicule['id'] ?>" 
                                       <?php if (in_array($vehicule['id'], $vehicules_ids)): ?>checked<?php endif; ?>>
                                <label class="form-check-label" for="vehicule<?= $vehicule['id'] ?>">
                                    <?= htmlspecialchars($vehicule['nom']) ?>
                                </label>
                                <div class="price-input mt-2" style="display: <?php echo in_array($vehicule['id'], $vehicules_ids) ? 'block' : 'none'; ?>;">
                                    <label for="prix_<?= $vehicule['id'] ?>" class="form-label">Prix pour ce véhicule (€)</label>
                                    <input type="number" class="form-control" id="prix_<?= $vehicule['id'] ?>" name="prix_<?= $vehicule['id'] ?>" 
                                           value="<?= isset($vehicules_prix[$vehicule['id']]) ? htmlspecialchars($vehicules_prix[$vehicule['id']]) : '' ?>" 
                                           step="0.01" min="0">
                                </div>
                                    <span class="input-group-text">€ TTC</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="images" class="form-label">Ajouter des images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                </div>
                
                <?php
                $stmt = $pdo->prepare("SELECT * FROM kit_images WHERE id_kit = ?");
                $stmt->execute([$kit['id']]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($images):
                ?>
                <div class="mb-3">
                    <label class="form-label">Images actuelles</label>
                    <div class="row g-2">
                        <?php foreach ($images as $image): ?>
                        <div class="col-auto">
                            <div class="position-relative">
                                <img src="../images/kits/<?= $image['image_path'] ?>" class="img-thumbnail" style="height: 100px;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image" 
                                        data-id="<?= $image['id'] ?>" data-type="kit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="kits.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th style="width: 15%">Nom</th>
                    <th style="width: 35%">Description</th>
                    <th style="width: 25%">Véhicules compatibles</th>
                    <th style="width: 15%">Images</th>
                    <th style="width: 10%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kits as $kit): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($kit['nom']) ?></td>
                    <td>
                        <div class="description-cell" style="max-height: 100px; overflow-y: auto;">
                            <?php 
                            $description = htmlspecialchars($kit['description']);
                            if (strlen($description) > 200) {
                                echo substr($description, 0, 200) . '... ';
                                echo '<a href="#" class="text-primary show-more" data-bs-toggle="modal" data-bs-target="#descriptionModal' . $kit['id'] . '">Voir plus</a>';
                            } else {
                                echo $description;
                            }
                            ?>
                        </div>
                        <?php if (strlen($description) > 200): ?>
                        <!-- Modal pour la description complète -->
                        <div class="modal fade" id="descriptionModal<?= $kit['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Description complète - <?= htmlspecialchars($kit['nom']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?= nl2br($description) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($kit['vehicules_prix'])): ?>
                            <div class="vehicules-list" style="max-height: 100px; overflow-y: auto;">
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($kit['vehicules_prix'] as $vehicule => $prix): ?>
                                        <li>
                                            <span class="fw-medium"><?= htmlspecialchars($vehicule) ?></span>
                                            <span class="text-success"><?= number_format($prix, 2, ',', ' ') ?> €</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Aucun véhicule</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($kit['images'])): ?>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php foreach (array_slice($kit['images'], 0, 3) as $image): ?>
                                    <a href="../images/kits/<?= $image ?>" target="_blank">
                                        <img src="../images/kits/<?= $image ?>" class="img-thumbnail" style="height: 50px; width: 50px; object-fit: cover;">
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($kit['images']) > 3): ?>
                                    <span class="badge bg-secondary align-self-center">+<?= count($kit['images']) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Aucune image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?action=edit&id=<?= $kit['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce kit ?')) { 
                                        document.getElementById('delete-form-<?= $kit['id'] ?>').submit(); 
                                    }">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <form id="delete-form-<?= $kit['id'] ?>" method="POST" style="display: none;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $kit['id'] ?>">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<style>
.description-cell {
    line-height: 1.4;
    text-align: justify;
}

.vehicules-list {
    font-size: 0.9rem;
}

.vehicules-list li {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    padding: 0.25rem;
    border-bottom: 1px solid #eee;
}

.vehicules-list li:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.table td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
}

.img-thumbnail {
    transition: transform 0.2s;
}

.img-thumbnail:hover {
    transform: scale(1.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour gérer l'affichage des champs de prix
    function togglePrixInput(checkbox) {
        const vehiculeId = checkbox.value;
        const prixGroup = document.getElementById('prix_group_' + vehiculeId);
        const prixInput = document.getElementById('prix_' + vehiculeId);
        
        if (prixGroup && prixInput) {
            prixInput.disabled = !checkbox.checked;
            prixGroup.style.display = checkbox.checked ? 'flex' : 'none';
        }
    }

    // Ajouter les écouteurs d'événements aux checkboxes
    const checkboxes = document.querySelectorAll('.vehicule-check');
    checkboxes.forEach(checkbox => {
        // Initialiser l'état des champs de prix pour les checkboxes cochées
        if (checkbox.checked) {
            togglePrixInput(checkbox);
        }
        
        // Ajouter l'écouteur d'événement pour les changements futurs
        checkbox.addEventListener('change', function() {
            togglePrixInput(this);
        });
    });
});

function updateKit(event, kitId) {
    event.preventDefault();
    const form = document.getElementById(`editKitForm${kitId}`);
    const formData = new FormData(form);

    fetch('update-kit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'kits.php?success=edit';
        } else {
            alert('Erreur lors de la modification : ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur lors de la modification : ' + error);
    });

    return false;
}

// Gestion des modals
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    let lastFocusedElement = null;

    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            lastFocusedElement = document.activeElement;
            
            const focusableElements = document.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            
            focusableElements.forEach(element => {
                if (!modal.contains(element)) {
                    element.setAttribute('tabindex', '-1');
                }
            });
        });

        modal.addEventListener('hidden.bs.modal', function() {
            if (lastFocusedElement) {
                lastFocusedElement.focus();
            }
            
            const focusableElements = document.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="-1"]'
            );
            
            focusableElements.forEach(element => {
                if (!modal.contains(element)) {
                    element.removeAttribute('tabindex');
                }
            });
        });

        modal.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });

        modal.addEventListener('shown.bs.modal', function() {
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        });
    });
});

// Gestion de la suppression des images
document.addEventListener('DOMContentLoaded', function() {
    // Gérer l'affichage des champs de prix
    document.querySelectorAll('.form-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const priceInput = this.closest('.form-check').querySelector('.price-input');
            if (this.checked) {
                priceInput.style.display = 'block';
            } else {
                priceInput.style.display = 'none';
            }
        });
    });

    // Gérer la suppression des images
    document.querySelectorAll('.delete-image').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.dataset.id;
            const type = this.dataset.type;
            const image = this.dataset.image;
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                fetch(`delete_image.php?type=${type}&id=${imageId}&image=${image}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.image-preview-item').remove();
                        } else {
                            alert('Erreur lors de la suppression de l\'image');
                        }
                    });
            }
        });
    });
});
</script>

<?php require 'footer.php'; ?>