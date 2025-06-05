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
                    if (empty($_POST['types']) || !is_array($_POST['types'])) {
                        throw new Exception("Veuillez sélectionner au moins un type de carrosserie compatible");
                    }

                    // Insérer l'option avec un prix par défaut de 0
                    $stmt = $pdo->prepare("INSERT INTO options (nom, description, prix, id_categorie) VALUES (?, ?, 0.00, ?)");
                    $stmt->execute([$nom, $description, $_POST['id_categorie'] ?: null]);
                    $id = $pdo->lastInsertId();
                    
                    // Ajouter les compatibilités avec les types de carrosserie
                    foreach ($_POST['types'] as $type) {
                        $prix_key = 'prix_' . md5($type);
                        
                        // Vérifier si le prix est défini et le convertir en nombre
                        $prix = 0.00; // Valeur par défaut
                        if (isset($_POST[$prix_key]) && $_POST[$prix_key] !== '') {
                            $prix = str_replace(',', '.', $_POST[$prix_key]);
                            $prix = floatval($prix);
                        }

                        // Insérer avec une requête préparée
                        $stmt = $pdo->prepare("INSERT INTO option_vehicule_compatibilite (id_option, type_carrosserie, prix) VALUES (:id_option, :type_carrosserie, :prix)");
                        $stmt->execute([
                            ':id_option' => $id,
                            ':type_carrosserie' => $type,
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
                $stmt = $pdo->prepare("UPDATE options SET nom = ?, description = ?, id_categorie = ? WHERE id = ?");
                $stmt->execute([$nom, $description, $_POST['id_categorie'] ?: null, $id]);
                
                // Mettre à jour les compatibilités avec les types de carrosserie
                // D'abord supprimer les anciennes
                $stmt = $pdo->prepare("DELETE FROM option_vehicule_compatibilite WHERE id_option = ?");
                $stmt->execute([$id]);
                
                // Puis ajouter les nouvelles
                foreach ($_POST['types'] as $type) {
                    $prix_key = 'prix_' . md5($type);
                    
                    // Vérifier si le prix est défini et le convertir en nombre
                    if (isset($_POST[$prix_key])) {
                        $prix = str_replace(',', '.', $_POST[$prix_key]);
                        $prix = floatval($prix);
                    } else {
                        $prix = 0.00;
                    }

                    // Insérer avec une requête préparée
                    $stmt = $pdo->prepare("INSERT INTO option_vehicule_compatibilite (id_option, type_carrosserie, prix) VALUES (:id_option, :type_carrosserie, :prix)");
                    $stmt->execute([
                        ':id_option' => $id,
                        ':type_carrosserie' => $type,
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

// Récupérer les options avec leurs images et types de carrosserie associés
$options = $pdo->query("
    SELECT o.*, 
           GROUP_CONCAT(DISTINCT CONCAT(ovc.type_carrosserie, ':', ovc.prix)) as types_prix,
           GROUP_CONCAT(oi.image_path) as images,
           c.nom as categorie_nom
    FROM options o
    LEFT JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option
    LEFT JOIN option_images oi ON o.id = oi.id_option
    LEFT JOIN categories_options c ON o.id_categorie = c.id
    GROUP BY o.id
    ORDER BY c.ordre, c.nom, o.nom
")->fetchAll(PDO::FETCH_ASSOC);

// Transformer les chaînes en tableaux
foreach ($options as &$option) {
    $option['images'] = $option['images'] ? explode(',', $option['images']) : [];
    $option['types_prix'] = $option['types_prix'] ? array_reduce(
        explode(',', $option['types_prix']),
        function($carry, $item) {
            list($type, $prix) = explode(':', $item);
            $carry[$type] = $prix;
            return $carry;
        },
        []
    ) : [];
}

// Récupérer la liste des types de carrosserie pour le formulaire
$types_carrosserie = $pdo->query("SELECT DISTINCT type_carrosserie FROM option_vehicule_compatibilite ORDER BY type_carrosserie")->fetchAll(PDO::FETCH_ASSOC);
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

<?php if ($action === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter une option</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom de l'option</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Types de carrosserie compatibles</label>
                    <div class="row">
                        <?php foreach ($types_carrosserie as $type): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input vehicule-check" 
                                           id="type_<?php echo md5($type['type_carrosserie']); ?>"
                                           name="types[]"
                                           value="<?php echo htmlspecialchars($type['type_carrosserie']); ?>"
                                           onchange="togglePrixInput(this)">
                                    <label class="form-check-label" for="type_<?php echo md5($type['type_carrosserie']); ?>">
                                        <?php echo htmlspecialchars($type['type_carrosserie']); ?>
                                    </label>
                                </div>
                                <div class="input-group mt-1">
                                    <input type="text" class="form-control prix-input"
                                           name="prix_<?php echo md5($type['type_carrosserie']); ?>"
                                           placeholder="Prix"
                                           disabled>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="images" class="form-label">Images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
                    <div id="imagePreview" class="mt-2"></div>
                </div>
                
                <button type="submit" class="btn btn-primary">Ajouter</button>
                <a href="options.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
<?php elseif ($action === 'edit' && isset($_GET['id'])): ?>
    <?php
    $stmt = $pdo->prepare("
        SELECT o.*, 
               GROUP_CONCAT(DISTINCT ovc.type_carrosserie) as types_carrosserie,
               GROUP_CONCAT(DISTINCT CONCAT(ovc.type_carrosserie, ':', ovc.prix)) as types_prix,
               GROUP_CONCAT(DISTINCT oi.image_path) as images
        FROM options o
        LEFT JOIN option_vehicule_compatibilite ovc ON o.id = ovc.id_option
        LEFT JOIN option_images oi ON o.id = oi.id_option
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$_GET['id']]);
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        die("Option non trouvée");
    }
    
    // Transformer les données
    $option['types_carrosserie'] = $option['types_carrosserie'] ? explode(',', $option['types_carrosserie']) : [];
    $types_prix = [];
    if ($option['types_prix']) {
        foreach (explode(',', $option['types_prix']) as $type_prix) {
            list($type, $prix) = explode(':', $type_prix);
            $types_prix[$type] = $prix;
        }
    }
    $option['types_prix'] = $types_prix;
    $option['images'] = $option['images'] ? explode(',', $option['images']) : [];
    ?>
    
    <h2>Modifier l'option</h2>
    <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?php echo $option['id']; ?>">
        
        <div class="mb-3">
            <label for="nom" class="form-label">Nom de l'option</label>
            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($option['nom']); ?>" required>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($option['description']); ?></textarea>
        </div>
        
        <div class="mb-3">
            <label for="categorie" class="form-label">Catégorie</label>
            <select class="form-select" id="categorie" name="id_categorie" required>
                <option value="">Sélectionner une catégorie</option>
                <?php
                $categories = $pdo->query("SELECT id, nom FROM categories_options ORDER BY ordre, nom")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($categories as $categorie) {
                    $selected = ($categorie['id'] == $option['id_categorie']) ? 'selected' : '';
                    echo '<option value="' . $categorie['id'] . '" ' . $selected . '>' . htmlspecialchars($categorie['nom']) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Types de carrosserie compatibles</label>
            <div class="row">
                <?php foreach ($types_carrosserie as $type): ?>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input vehicule-check" 
                                   id="type_<?php echo md5($type['type_carrosserie']); ?>" 
                                   name="types[]" 
                                   value="<?php echo htmlspecialchars($type['type_carrosserie']); ?>"
                                   <?php echo in_array($type['type_carrosserie'], $option['types_carrosserie']) ? 'checked' : ''; ?>
                                   onchange="togglePrixInput(this)">
                            <label class="form-check-label" for="type_<?php echo md5($type['type_carrosserie']); ?>">
                                <?php echo htmlspecialchars($type['type_carrosserie']); ?>
                            </label>
                        </div>
                        <div class="input-group mt-1">
                            <input type="text" class="form-control prix-input" 
                                   name="prix_<?php echo md5($type['type_carrosserie']); ?>" 
                                   placeholder="Prix" 
                                   value="<?php echo isset($option['types_prix'][$type['type_carrosserie']]) ? htmlspecialchars($option['types_prix'][$type['type_carrosserie']]) : ''; ?>"
                                   <?php echo in_array($type['type_carrosserie'], $option['types_carrosserie']) ? '' : 'disabled'; ?>>
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Images actuelles</label>
            <div class="row g-2">
                <?php foreach ($option['images'] as $image): ?>
                    <div class="col-auto">
                        <div class="position-relative">
                            <img src="../images/options/<?php echo htmlspecialchars($image); ?>" 
                                 class="img-thumbnail" 
                                 style="height: 100px;">
                            <button type="button" 
                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image" 
                                    data-id="<?php echo $option['id']; ?>" 
                                    data-image="<?php echo htmlspecialchars($image); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="images" class="form-label">Ajouter des images</label>
            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
            <div id="imagePreview" class="mt-2"></div>
        </div>
        
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="options.php" class="btn btn-secondary">Annuler</a>
    </form>
    
    <script>
    // Fonction pour supprimer une image
    document.querySelectorAll('.delete-image').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                const id = this.dataset.id;
                const image = this.dataset.image;
                
                fetch('delete_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&image=${image}&type=option`
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
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th style="width: 15%">Nom</th>
                    <th style="width: 25%">Description</th>
                    <th style="width: 15%">Catégorie</th>
                    <th style="width: 20%">Types de carrosserie compatibles</th>
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
                        <?php
                        $stmt = $pdo->prepare("SELECT nom FROM categories_options WHERE id = ?");
                        $stmt->execute([$option['id_categorie']]);
                        $categorie = $stmt->fetch(PDO::FETCH_COLUMN);
                        echo $categorie ? htmlspecialchars($categorie) : '<span class="text-muted">Non catégorisé</span>';
                        ?>
                    </td>
                    <td>
                        <?php if (!empty($option['types_prix'])): ?>
                            <div class="types-list" style="max-height: 100px; overflow-y: auto;">
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($option['types_prix'] as $type => $prix): ?>
                                        <li>
                                            <span class="fw-medium"><?= htmlspecialchars($type) ?></span>
                                            <span class="text-success"><?= number_format($prix, 2, ',', ' ') ?> €</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Aucun type de carrosserie</span>
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
                                    onclick="deleteOption(<?= $option['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
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

.types-list {
    font-size: 0.9rem;
}

.types-list li {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    padding: 0.25rem;
    border-bottom: 1px solid #eee;
    }

.types-list li:last-child {
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

.price-input {
    margin-top: 0.5rem;
}

.form-check {
    margin-bottom: 0.5rem;
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

.badge {
    font-size: 0.875em;
}

.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
</style>

<script>
// Attendre que le DOM soit complètement chargé
window.addEventListener('load', function() {
    // Gestion des checkboxes de types
    const checkboxes = document.querySelectorAll('.type-check');
    checkboxes.forEach(checkbox => {
        // Initialiser l'état initial
        const prixInput = checkbox.parentElement.nextElementSibling.querySelector('input');
        const prixInputGroup = checkbox.parentElement.nextElementSibling;
        if (prixInput && prixInputGroup) {
            prixInput.disabled = !checkbox.checked;
            prixInputGroup.style.display = checkbox.checked ? 'flex' : 'none';
        }

        // Ajouter l'écouteur d'événement
        checkbox.addEventListener('change', function() {
            const prixInput = this.parentElement.nextElementSibling.querySelector('input');
            const prixInputGroup = this.parentElement.nextElementSibling;
            if (prixInput && prixInputGroup) {
                prixInput.disabled = !this.checked;
                prixInputGroup.style.display = this.checked ? 'flex' : 'none';
            }
        });
    });

    // Gestion des modals
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
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            
            focusableElements.forEach(element => {
                if (!modal.contains(element)) {
                    element.removeAttribute('tabindex');
                }
            });
        });
    });
});

function deleteOption(optionId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette option ?')) {
        fetch('delete-option.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: optionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'options.php?success=delete';
            } else {
                alert('Erreur lors de la suppression : ' + data.message);
            }
        })
        .catch(error => {
            alert('Erreur lors de la suppression : ' + error);
        });
    }
}

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

<?php endif; ?>
<?php require 'footer.php'; ?> 