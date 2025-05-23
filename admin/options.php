<?php
require 'header.php';
require 'check_auth.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $nom = $_POST['nom'];
            $description = $_POST['description'];
            
            if ($_POST['action'] === 'add') {
                try {
                    if (empty($_POST['vehicules']) || !is_array($_POST['vehicules'])) {
                        throw new Exception("Veuillez sélectionner au moins un véhicule compatible");
                    }

                    // Insérer l'option avec un prix par défaut de 0
                    $stmt = $pdo->prepare("INSERT INTO options (nom, description, prix) VALUES (?, ?, 0.00)");
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
                        $stmt = $pdo->prepare("INSERT INTO option_vehicule_compatibilite (id_option, id_vehicule, prix) VALUES (:id_option, :id_vehicule, :prix)");
                        $stmt->execute([
                            ':id_option' => $id,
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
                                $path = '../images/options/' . $filename;
                                
                                if (move_uploaded_file($tmp_name, $path)) {
                                    $stmt = $pdo->prepare("INSERT INTO option_images (id_option, image_path) VALUES (?, ?)");
                                    $stmt->execute([$id, $filename]);
                                }
                            }
                        }
                    }
                    
                    header('Location: options.php?success=add');
                    exit;
                } catch (PDOException $e) {
                    die("Une erreur est survenue lors de l'ajout de l'option : " . $e->getMessage());
                } catch (Exception $e) {
                    die("Une erreur est survenue : " . $e->getMessage());
                }
            } else {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE options SET nom = ?, description = ? WHERE id = ?");
                $stmt->execute([$nom, $description, $id]);
                
                // Mettre à jour les compatibilités avec les véhicules
                // D'abord supprimer les anciennes
                $stmt = $pdo->prepare("DELETE FROM option_vehicule_compatibilite WHERE id_option = ?");
                $stmt->execute([$id]);
                
                // Puis ajouter les nouvelles
                foreach ($_POST['vehicules'] as $vehicule_id) {
                    $prix = $_POST['prix_' . $vehicule_id];
                    $stmt = $pdo->prepare("INSERT INTO option_vehicule_compatibilite (id_option, id_vehicule, prix) VALUES (?, ?, ?)");
                    $stmt->execute([$id, $vehicule_id, $prix]);
                }
                
                // Gestion des images
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        $file = $_FILES['images']['name'][$key];
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            $filename = uniqid() . '.' . $ext;
                            $path = '../images/options/' . $filename;
                            
                            if (move_uploaded_file($tmp_name, $path)) {
                                $stmt = $pdo->prepare("INSERT INTO option_images (id_option, image_path) VALUES (?, ?)");
                                $stmt->execute([$id, $filename]);
                            }
                        }
                    }
                }
                
                header('Location: options.php?success=edit');
                exit;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            
            // Supprimer les images physiques
            $stmt = $pdo->prepare("SELECT image_path FROM option_images WHERE id_option = ?");
            $stmt->execute([$id]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($images as $image) {
                $path = '../images/options/' . $image;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            
            // Supprimer l'option (les images et compatibilités seront supprimées automatiquement grâce à ON DELETE CASCADE)
            $stmt = $pdo->prepare("DELETE FROM options WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: options.php?success=delete');
            exit;
        }
    }
}

// Récupérer les options avec leurs images et véhicules associés
$options = $pdo->query("
    SELECT o.*, 
           GROUP_CONCAT(DISTINCT CONCAT(v.nom, ':', ovc.prix)) as vehicules_prix,
           GROUP_CONCAT(oi.image_path) as images
    FROM options o
    LEFT JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option
    LEFT JOIN vehicules v ON ovc.id_vehicule = v.id
    LEFT JOIN option_images oi ON o.id = oi.id_option
    GROUP BY o.id
    ORDER BY o.nom
")->fetchAll(PDO::FETCH_ASSOC);

// Transformer les chaînes en tableaux
foreach ($options as &$option) {
    $option['images'] = $option['images'] ? explode(',', $option['images']) : [];
    $option['vehicules_prix'] = $option['vehicules_prix'] ? array_reduce(
        explode(',', $option['vehicules_prix']),
        function($carry, $item) {
            list($nom, $prix) = explode(':', $item);
            $carry[$nom] = $prix;
            return $carry;
        },
        []
    ) : [];
}

// Récupérer la liste des véhicules pour le formulaire
$vehicules = $pdo->query("SELECT id, nom FROM vehicules ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Options</h2>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter une option
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 'add':
                echo 'Option ajoutée avec succès.';
                break;
            case 'edit':
                echo 'Option modifiée avec succès.';
                break;
            case 'delete':
                echo 'Option supprimée avec succès.';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter une option</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Véhicules compatibles</label>
                    <div class="row">
                        <?php foreach ($vehicules as $vehicule): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input vehicule-check" 
                                           id="vehicule_<?= $vehicule['id'] ?>" 
                                           name="vehicules[]" 
                                           value="<?= $vehicule['id'] ?>"
                                           onchange="togglePrixInput(this)">
                                    <label class="form-check-label" for="vehicule_<?= $vehicule['id'] ?>">
                                        <?= htmlspecialchars($vehicule['nom']) ?>
                                    </label>
                                </div>
                                <div class="input-group mt-1 prix-input" style="display: none;">
                                    <input type="number" class="form-control" 
                                           name="prix_<?= $vehicule['id'] ?>" 
                                           placeholder="Prix" 
                                           step="0.01" 
                                           disabled>
                                    <span class="input-group-text">€</span>
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
                    <a href="options.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
    <?php
    $stmt = $pdo->prepare("
        SELECT o.*, GROUP_CONCAT(ovc.id_vehicule) as vehicules_ids
        FROM options o
        LEFT JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$_GET['id']]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($option):
        $option['vehicules_ids'] = $option['vehicules_ids'] ? explode(',', $option['vehicules_ids']) : [];
    ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifier l'option</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $option['id'] ?>">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($option['nom']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($option['description']) ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Véhicules compatibles</label>
                    <div class="row">
                        <?php foreach ($vehicules as $vehicule): 
                            $stmt = $pdo->prepare("SELECT prix FROM option_vehicule_compatibilite WHERE id_option = ? AND id_vehicule = ?");
                            $stmt->execute([$option['id'], $vehicule['id']]);
                            $prix = $stmt->fetchColumn();
                        ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input vehicule-check" 
                                           id="vehicule_<?= $vehicule['id'] ?>" 
                                           name="vehicules[]" 
                                           value="<?= $vehicule['id'] ?>"
                                           <?= in_array($vehicule['id'], $option['vehicules_ids']) ? 'checked' : '' ?>
                                           onchange="togglePrixInput(this)">
                                    <label class="form-check-label" for="vehicule_<?= $vehicule['id'] ?>">
                                        <?= htmlspecialchars($vehicule['nom']) ?>
                                    </label>
                                </div>
                                <div class="input-group mt-1 prix-input" style="display: <?= in_array($vehicule['id'], $option['vehicules_ids']) ? 'flex' : 'none' ?>;">
                                    <input type="number" class="form-control" 
                                           name="prix_<?= $vehicule['id'] ?>" 
                                           placeholder="Prix" 
                                           step="0.01" 
                                           value="<?= $prix ?>"
                                           <?= in_array($vehicule['id'], $option['vehicules_ids']) ? '' : 'disabled' ?>>
                                    <span class="input-group-text">€</span>
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
                $stmt = $pdo->prepare("SELECT * FROM option_images WHERE id_option = ?");
                $stmt->execute([$option['id']]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($images):
                ?>
                <div class="mb-3">
                    <label class="form-label">Images actuelles</label>
                    <div class="row g-2">
                        <?php foreach ($images as $image): ?>
                        <div class="col-auto">
                            <div class="position-relative">
                                <img src="../images/options/<?= $image['image_path'] ?>" class="img-thumbnail" style="height: 100px;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image" 
                                        data-id="<?= $image['id'] ?>" data-type="option">
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
                    <a href="options.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
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
                <?php foreach ($options as $option): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($option['nom']) ?></td>
                    <td>
                        <div class="description-cell" style="max-height: 100px; overflow-y: auto;">
                            <?php 
                            $description = htmlspecialchars($option['description']);
                            if (strlen($description) > 200) {
                                echo substr($description, 0, 200) . '... ';
                                echo '<a href="#" class="text-primary show-more" data-bs-toggle="modal" data-bs-target="#descriptionModal' . $option['id'] . '">Voir plus</a>';
                            } else {
                                echo $description;
                            }
                            ?>
                        </div>
                        <?php if (strlen($description) > 200): ?>
                        <!-- Modal pour la description complète -->
                        <div class="modal fade" id="descriptionModal<?= $option['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Description complète - <?= htmlspecialchars($option['nom']) ?></h5>
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
                        <?php if (!empty($option['vehicules_prix'])): ?>
                            <div class="vehicules-list" style="max-height: 100px; overflow-y: auto;">
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($option['vehicules_prix'] as $vehicule => $prix): ?>
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
                        <?php if (!empty($option['images'])): ?>
                            <div class="d-flex gap-1 flex-wrap">
                                <?php foreach (array_slice($option['images'], 0, 3) as $image): ?>
                                    <a href="../images/options/<?= $image ?>" target="_blank">
                                        <img src="../images/options/<?= $image ?>" class="img-thumbnail" style="height: 50px; width: 50px; object-fit: cover;">
                                    </a>
                                <?php endforeach; ?>
                                <?php if (count($option['images']) > 3): ?>
                                    <span class="badge bg-secondary align-self-center">+<?= count($option['images']) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Aucune image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?action=edit&id=<?= $option['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette option ?')) { 
                                        document.getElementById('delete-form-<?= $option['id'] ?>').submit(); 
                                    }">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <form id="delete-form-<?= $option['id'] ?>" method="POST" style="display: none;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $option['id'] ?>">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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
function togglePrixInput(checkbox) {
    const prixInput = checkbox.closest('.col-md-6').querySelector('.prix-input');
    const prixInputField = prixInput.querySelector('input');
    
    if (checkbox.checked) {
        prixInput.style.display = 'flex';
        prixInputField.disabled = false;
        prixInputField.required = true;
    } else {
        prixInput.style.display = 'none';
        prixInputField.disabled = true;
        prixInputField.required = false;
        prixInputField.value = '';
    }
}

document.querySelectorAll('.delete-image').forEach(button => {
    button.addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
            const id = this.dataset.id;
            const type = this.dataset.type;
            
            fetch('delete_image.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `image_id=${id}&type=${type}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('.col-auto').remove();
                } else {
                    alert('Erreur lors de la suppression de l\'image');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression de l\'image');
            });
        }
    });
});
</script>

<?php endif; ?>
<?php require 'footer.php'; ?> 