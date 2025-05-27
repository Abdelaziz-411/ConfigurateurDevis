<?php
require 'header.php';

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
$kits = $pdo->query("
    SELECT k.*, 
           GROUP_CONCAT(DISTINCT CONCAT(v.nom, ':', kvc.prix)) as vehicules_prix,
           GROUP_CONCAT(ki.image_path) as images
    FROM kits k
    LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
    LEFT JOIN vehicules v ON kvc.id_vehicule = v.id
    LEFT JOIN kit_images ki ON k.id = ki.id_kit
    GROUP BY k.id
    ORDER BY k.nom
")->fetchAll(PDO::FETCH_ASSOC);

// Transformer les chaînes en tableaux
foreach ($kits as &$kit) {
    $kit['images'] = $kit['images'] ? explode(',', $kit['images']) : [];
    $kit['vehicules_prix'] = $kit['vehicules_prix'] ? array_reduce(
        explode(',', $kit['vehicules_prix']),
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
                    <a href="kits.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
    <?php
    $stmt = $pdo->prepare("
        SELECT k.*, GROUP_CONCAT(kvc.id_vehicule) as vehicules_ids
        FROM kits k
        LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
        WHERE k.id = ?
        GROUP BY k.id
    ");
    $stmt->execute([$_GET['id']]);
    $kit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($kit):
        $kit['vehicules_ids'] = $kit['vehicules_ids'] ? explode(',', $kit['vehicules_ids']) : [];
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
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($kit['description']) ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Véhicules compatibles</label>
                    <div class="row">
                        <?php foreach ($vehicules as $vehicule): 
                            $stmt = $pdo->prepare("SELECT prix FROM kit_vehicule_compatibilite WHERE id_kit = ? AND id_vehicule = ?");
                            $stmt->execute([$kit['id'], $vehicule['id']]);
                            $prix = $stmt->fetchColumn();
                        ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input vehicule-check" 
                                           id="vehicule_<?= $vehicule['id'] ?>" 
                                           name="vehicules[]" 
                                           value="<?= $vehicule['id'] ?>"
                                           <?= in_array($vehicule['id'], $kit['vehicules_ids']) ? 'checked' : '' ?>
                                           onchange="togglePrixInput(this)">
                                    <label class="form-check-label" for="vehicule_<?= $vehicule['id'] ?>">
                                        <?= htmlspecialchars($vehicule['nom']) ?>
                                    </label>
                                </div>
                                <div class="input-group mt-1 prix-input" style="display: <?= in_array($vehicule['id'], $kit['vehicules_ids']) ? 'flex' : 'none' ?>;">
                                    <input type="number" class="form-control" 
                                           name="prix_<?= $vehicule['id'] ?>" 
                                           placeholder="Prix" 
                                           step="0.01" 
                                           value="<?= $prix ?>"
                                           <?= in_array($vehicule['id'], $kit['vehicules_ids']) ? '' : 'disabled' ?>>
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
<?php endif; ?>

<script>
function togglePrixInput(checkbox) {
    const prixInput = checkbox.parentElement.nextElementSibling.querySelector('input');
    prixInput.disabled = !checkbox.checked;
}

function deleteKit(kitId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce kit ?')) {
        fetch('delete-kit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: kitId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression : ' + data.message);
            }
        })
        .catch(error => {
            alert('Erreur lors de la suppression : ' + error);
        });
    }
}

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
            // Fermer le modal
            const modalElement = document.getElementById(`editKitModal${kitId}`);
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            // Rediriger vers kits.php
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
    document.querySelectorAll('.delete-image').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.dataset.id;
            const type = this.dataset.type;
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                fetch('delete-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: imageId, type: type })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer l'élément de l'interface
                        this.closest('.col-auto').remove();
                    } else {
                        alert('Erreur lors de la suppression : ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erreur lors de la suppression : ' + error);
                });
            }
        });
    });
});
</script>

<?php require 'footer.php'; ?> 