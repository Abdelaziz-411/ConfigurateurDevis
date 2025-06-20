<?php
require 'header.php';

// Récupérer tous les kits de base
$kits = $pdo->query("SELECT * FROM kits ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    
// Pour chaque kit, récupérer ses types de carrosserie/prix et ses images
    foreach ($kits as &$kit) {
    // Types de carrosserie et prix
    $stmt = $pdo->prepare("SELECT type_carrosserie, prix FROM kit_vehicule_compatibilite WHERE id_kit = ?");
    $stmt->execute([$kit['id']]);
    $kit['types_carrosserie'] = [];
    $kit['types_prix'] = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $kit['types_carrosserie'][] = $row['type_carrosserie'];
        $kit['types_prix'][$row['type_carrosserie']] = $row['prix'];
            }
    // Images
    $stmt = $pdo->prepare("SELECT image_path FROM kit_images WHERE id_kit = ?");
    $stmt->execute([$kit['id']]);
    $kit['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($kit);

// Récupérer la liste des types de carrosserie pour le formulaire
$types_carrosserie = [
    ['type_carrosserie' => 'L1H1'],
    ['type_carrosserie' => 'L2H1'],
    ['type_carrosserie' => 'L2H2'],
    ['type_carrosserie' => 'L3H2'],
    ['type_carrosserie' => 'L3H3'],
    ['type_carrosserie' => 'L4H3']
];
error_log("Types de carrosserie récupérés : " . print_r($types_carrosserie, true));

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
                    if (empty($_POST['types']) || !is_array($_POST['types'])) {
                        throw new Exception("Veuillez sélectionner au moins un type de carrosserie compatible");
                    }

                    // Insérer le kit
                    $stmt = $pdo->prepare("INSERT INTO kits (nom, description) VALUES (?, ?)");
                    $stmt->execute([$nom, $description]);
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
                        $stmt = $pdo->prepare("INSERT INTO kit_vehicule_compatibilite (id_kit, type_carrosserie, prix) VALUES (?, ?, ?)");
                        $stmt->execute([$id, $type, $prix]);
                    }
                    
                    // Gestion des images
                    if (!empty($_FILES['images']['name'][0])) {
                        // Créer le dossier s'il n'existe pas
                        $upload_dir = '../images/kits/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                            $file = $_FILES['images']['name'][$key];
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
                                $filename = uniqid() . '.' . $ext;
                                $path = $upload_dir . $filename;
                                
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
                    error_log("Erreur SQL lors de l'ajout du kit : " . $e->getMessage());
                    die("Une erreur est survenue lors de l'ajout du kit : " . $e->getMessage());
                } catch (Exception $e) {
                    error_log("Erreur lors de l'ajout du kit : " . $e->getMessage());
                    die("Une erreur est survenue : " . $e->getMessage());
                }
            } elseif ($_POST['action'] === 'edit') {
                try {
                $id = $_POST['id'];
                    $nom = $_POST['nom'];
                    $description = $_POST['description'];
                    
                    if (empty($_POST['types']) || !is_array($_POST['types'])) {
                        throw new Exception("Veuillez sélectionner au moins un type de carrosserie compatible");
                    }
                    
                    // Mettre à jour le kit
                $stmt = $pdo->prepare("UPDATE kits SET nom = ?, description = ? WHERE id = ?");
                $stmt->execute([$nom, $description, $id]);
                
                    // Supprimer les anciennes compatibilités
                $stmt = $pdo->prepare("DELETE FROM kit_vehicule_compatibilite WHERE id_kit = ?");
                $stmt->execute([$id]);
                
                    // Ajouter les nouvelles compatibilités
                    foreach ($_POST['types'] as $type) {
                        $prix_key = 'prix_' . md5($type);
                    
                    // Vérifier si le prix est défini et le convertir en nombre
                        $prix = 0.00; // Valeur par défaut
                        if (isset($_POST[$prix_key]) && $_POST[$prix_key] !== '') {
                            $prix = str_replace(',', '.', $_POST[$prix_key]);
                        $prix = floatval($prix);
                    }

                    // Insérer avec une requête préparée
                        $stmt = $pdo->prepare("INSERT INTO kit_vehicule_compatibilite (id_kit, type_carrosserie, prix) VALUES (?, ?, ?)");
                        $stmt->execute([$id, $type, $prix]);
                }
                
                // Gestion des images
                if (!empty($_FILES['images']['name'][0])) {
                        // Créer le dossier s'il n'existe pas
                        $upload_dir = '../images/kits/';
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
                                $stmt = $pdo->prepare("INSERT INTO kit_images (id_kit, image_path) VALUES (?, ?)");
                                $stmt->execute([$id, $filename]);
                            }
                        }
                    }
                }
                
                header('Location: kits.php?success=edit');
                exit;
                } catch (Exception $e) {
                    error_log("Erreur lors de la modification du kit : " . $e->getMessage());
                    die("Une erreur est survenue lors de la modification du kit : " . $e->getMessage());
            }
        } elseif ($_POST['action'] === 'delete') {
                try {
            $id = $_POST['id'];
            error_log("Tentative de suppression du kit ID: " . $id);
            
                    // Récupérer les images du kit
            $stmt = $pdo->prepare("SELECT image_path FROM kit_images WHERE id_kit = ?");
            $stmt->execute([$id]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Images trouvées pour le kit: " . print_r($images, true));
            
                    // Supprimer les fichiers physiques
            foreach ($images as $image) {
                        $path = "../images/kits/" . $image;
                if (file_exists($path)) {
                    unlink($path);
                    error_log("Image supprimée: " . $path);
                } else {
                    error_log("Image non trouvée: " . $path);
                }
            }
            
                    // Supprimer les enregistrements de la base de données
                    $pdo->beginTransaction();
                    error_log("Début de la transaction");
                    
                    // Supprimer les images
                    $stmt = $pdo->prepare("DELETE FROM kit_images WHERE id_kit = ?");
                    $stmt->execute([$id]);
                    error_log("Images supprimées de la base de données");
                    
                    // Supprimer les compatibilités
                    $stmt = $pdo->prepare("DELETE FROM kit_vehicule_compatibilite WHERE id_kit = ?");
                    $stmt->execute([$id]);
                    error_log("Compatibilités supprimées de la base de données");
                    
                    // Supprimer le kit
            $stmt = $pdo->prepare("DELETE FROM kits WHERE id = ?");
            $stmt->execute([$id]);
            error_log("Kit supprimé de la base de données");
                    
                    $pdo->commit();
            error_log("Transaction validée");
            
            header('Location: kits.php?success=delete');
            exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                        error_log("Transaction annulée");
                    }
                    error_log("Erreur lors de la suppression du kit : " . $e->getMessage());
                    die("Une erreur est survenue lors de la suppression du kit : " . $e->getMessage());
                }
            }
        }
    }
}

// Récupérer la liste des types de carrosserie pour le formulaire
    $types_carrosserie = [
    ['type_carrosserie' => 'L1H1'],
    ['type_carrosserie' => 'L2H1'],
    ['type_carrosserie' => 'L2H2'],
    ['type_carrosserie' => 'L3H2'],
    ['type_carrosserie' => 'L3H3'],
    ['type_carrosserie' => 'L4H3']
];
?>

<?php if ($action === 'list'): ?>
<div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Liste des kits</h2>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter un kit
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
        switch ($_GET['success']) {
            case 'add':
                    echo "Le kit a été ajouté avec succès.";
                break;
            case 'edit':
                    echo "Le kit a été modifié avec succès.";
                break;
            case 'delete':
                    echo "Le kit a été supprimé avec succès.";
                break;
        }
        ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Types de carrosserie</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kits as $kit): ?>
                    <?php error_log("Affichage du kit ID: " . $kit['id'] . " - Nom: " . $kit['nom']); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($kit['id']); ?></td>
                        <td><?php echo htmlspecialchars($kit['nom']); ?></td>
                        <td><?php echo htmlspecialchars($kit['description']); ?></td>
                        <td>
                            <?php if (!empty($kit['types_carrosserie'])): ?>
                                <ul class="list-unstyled mb-0">
                                    <?php foreach ($kit['types_carrosserie'] as $type): ?>
                                        <li>
                                            <?php echo htmlspecialchars($type); ?>
                                            <?php if (isset($kit['types_prix'][$type])): ?>
                                                <span class="badge bg-primary">
                                                    <?php echo number_format($kit['types_prix'][$type], 2, ',', ' '); ?> €
                                                </span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-muted">Aucun type de carrosserie</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($kit['images'])): ?>
                                <div class="d-flex gap-2">
                                    <?php foreach ($kit['images'] as $image): ?>
                                        <img src="../images/kits/<?php echo htmlspecialchars($image); ?>" 
                                             alt="Image du kit" 
                                             class="img-thumbnail" 
                                             style="max-width: 50px;">
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Aucune image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="?action=edit&id=<?php echo $kit['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-danger" 
                                        onclick="deleteKit(<?php echo $kit['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function deleteKit(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce kit ?')) {
            console.log('Suppression du kit ID:', id);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'kits.php';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            console.log('Formulaire créé:', form);
            form.submit();
            console.log('Formulaire soumis');
        }
    }

    // Fonction pour gérer les champs de prix
    function togglePrixInput(checkbox) {
        const prixInput = checkbox.parentElement.nextElementSibling.querySelector('.prix-input');
        if (checkbox.checked) {
            prixInput.disabled = false;
            prixInput.required = true;
            if (!prixInput.value) {
                prixInput.value = '0.00';
            }
        } else {
            prixInput.disabled = true;
            prixInput.required = false;
            prixInput.value = '';
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
                };
                reader.readAsDataURL(file);
            });
        }
    }

    // Initialiser les champs de prix pour les types de carrosserie déjà sélectionnés
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.vehicule-check').forEach(checkbox => {
            if (checkbox.checked) {
                togglePrixInput(checkbox);
            }
        });
    });
    </script>
<?php elseif ($action === 'edit' && isset($_GET['id'])): ?>
    <?php
    $stmt = $pdo->prepare("
        SELECT k.*, 
               GROUP_CONCAT(DISTINCT kvc.type_carrosserie) as types_carrosserie,
               GROUP_CONCAT(DISTINCT CONCAT(kvc.type_carrosserie, ':', kvc.prix)) as types_prix,
               GROUP_CONCAT(DISTINCT ki.image_path) as images
        FROM kits k
        LEFT JOIN kit_vehicule_compatibilite kvc ON k.id = kvc.id_kit
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
        WHERE k.id = ?
        GROUP BY k.id
    ");
    $stmt->execute([$_GET['id']]);
    $kit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kit) {
        die("Kit non trouvé");
    }
    
    // Transformer les données
    $kit['types_carrosserie'] = $kit['types_carrosserie'] ? explode(',', $kit['types_carrosserie']) : [];
    $types_prix = [];
    if ($kit['types_prix']) {
        foreach (explode(',', $kit['types_prix']) as $type_prix) {
            list($type, $prix) = explode(':', $type_prix);
            $types_prix[$type] = $prix;
        }
    }
    $kit['types_prix'] = $types_prix;
    $kit['images'] = $kit['images'] ? explode(',', $kit['images']) : [];
    ?>
    
    <h2>Modifier le kit</h2>
    <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?php echo $kit['id']; ?>">
        
        <div class="mb-3">
            <label for="nom" class="form-label">Nom du kit</label>
            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($kit['nom']); ?>" required>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($kit['description']); ?></textarea>
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
                                   <?php echo in_array($type['type_carrosserie'], $kit['types_carrosserie']) ? 'checked' : ''; ?>
                                   onchange="togglePrixInput(this)">
                            <label class="form-check-label" for="type_<?php echo md5($type['type_carrosserie']); ?>">
                                <?php echo htmlspecialchars($type['type_carrosserie']); ?>
                            </label>
                        </div>
                        <div class="input-group mt-1">
                            <input type="text" class="form-control prix-input" 
                                   name="prix_<?php echo md5($type['type_carrosserie']); ?>" 
                                   placeholder="Prix" 
                                   value="<?php echo isset($kit['types_prix'][$type['type_carrosserie']]) ? htmlspecialchars($kit['types_prix'][$type['type_carrosserie']]) : ''; ?>"
                                   <?php echo in_array($type['type_carrosserie'], $kit['types_carrosserie']) ? '' : 'disabled'; ?>>
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Images actuelles</label>
            <div class="row g-2">
                <?php foreach ($kit['images'] as $image): ?>
                    <div class="col-auto">
                        <div class="position-relative">
                            <img src="../images/kits/<?php echo htmlspecialchars($image); ?>" 
                                 class="img-thumbnail" 
                                 style="height: 100px;">
                            <button type="button" 
                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image" 
                                    data-id="<?php echo $kit['id']; ?>" 
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
            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif" onchange="previewImages(this)">
            <div id="imagePreview" class="mt-2"></div>
        </div>
        
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="kits.php" class="btn btn-secondary">Annuler</a>
    </form>
    
    <script>
    // Fonction pour supprimer une image
    document.querySelectorAll('.delete-image').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                const id = this.dataset.id;
                const image = this.dataset.image;
                
                fetch('delete-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&image=${image}&type=kit`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.col-auto').remove();
                    } else {
                        alert('Erreur lors de la suppression de l'image');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression de l'image');
                });
            }
        });
    });
    </script>
<?php elseif ($action === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter un kit</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du kit</label>
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
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif" onchange="previewImages(this)">
                    <div id="imagePreview" class="mt-2"></div>
                </div>
                
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="kits.php" class="btn btn-secondary">Annuler</a>
            </form>
                    </div>
                </div>
                <?php endif; ?>
                
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

.description-cell {
    max-height: 100px;
    overflow-y: auto;
}

.types-list {
    max-height: 100px;
    overflow-y: auto;
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
// Fonction pour gérer les champs de prix
    function togglePrixInput(checkbox) {
    const prixInput = checkbox.parentElement.nextElementSibling.querySelector('.prix-input');
    if (checkbox.checked) {
        prixInput.disabled = false;
        prixInput.required = true;
        if (!prixInput.value) {
            prixInput.value = '0.00';
        }
    } else {
        prixInput.disabled = true;
        prixInput.required = false;
        prixInput.value = '';
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

// Initialiser les champs de prix pour les types de carrosserie déjà sélectionnés
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.vehicule-check').forEach(checkbox => {
        if (checkbox.checked) {
            togglePrixInput(checkbox);
        }
    });
});
</script>

<?php require 'footer.php'; ?> 