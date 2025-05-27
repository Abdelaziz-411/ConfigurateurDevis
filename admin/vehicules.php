<?php
require 'header.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $nom = $_POST['nom'];
            $description = $_POST['description'];
            
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO vehicules (nom, description) VALUES (?, ?)");
                $stmt->execute([$nom, $description]);
                $id = $pdo->lastInsertId();
                
                // Gestion des images
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        $file = $_FILES['images']['name'][$key];
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            $filename = uniqid() . '.' . $ext;
                            $path = '../images/vehicules/' . $filename;
                            
                            if (move_uploaded_file($tmp_name, $path)) {
                                $stmt = $pdo->prepare("INSERT INTO vehicle_images (id_vehicule, image_path) VALUES (?, ?)");
                                $stmt->execute([$id, $filename]);
                            }
                        }
                    }
                }
                
                header('Location: vehicules.php?success=add');
                exit;
            } else {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE vehicules SET nom = ?, description = ? WHERE id = ?");
                $stmt->execute([$nom, $description, $id]);
                
                // Gestion des images
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        $file = $_FILES['images']['name'][$key];
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                            $filename = uniqid() . '.' . $ext;
                            $path = '../images/vehicules/' . $filename;
                            
                            if (move_uploaded_file($tmp_name, $path)) {
                                $stmt = $pdo->prepare("INSERT INTO vehicle_images (id_vehicule, image_path) VALUES (?, ?)");
                                $stmt->execute([$id, $filename]);
                            }
                        }
                    }
                }
                
                header('Location: vehicules.php?success=edit');
                exit;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            
            // Supprimer les images physiques
            $stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE id_vehicule = ?");
            $stmt->execute([$id]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($images as $image) {
                $path = '../images/vehicules/' . $image;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            
            // Supprimer le véhicule (les images seront supprimées automatiquement grâce à ON DELETE CASCADE)
            $stmt = $pdo->prepare("DELETE FROM vehicules WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: vehicules.php?success=delete');
            exit;
        }
    }
}

// Récupérer les véhicules
$vehicules = $pdo->query("
    SELECT v.*, GROUP_CONCAT(vi.image_path) as images
    FROM vehicules v
    LEFT JOIN vehicle_images vi ON v.id = vi.id_vehicule
    GROUP BY v.id
    ORDER BY v.nom
")->fetchAll(PDO::FETCH_ASSOC);

// Transformer la chaîne d'images en tableau
foreach ($vehicules as &$vehicule) {
    $vehicule['images'] = $vehicule['images'] ? explode(',', $vehicule['images']) : [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Véhicules</h2>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter un véhicule
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 'add':
                echo 'Véhicule ajouté avec succès.';
                break;
            case 'edit':
                echo 'Véhicule modifié avec succès.';
                break;
            case 'delete':
                echo 'Véhicule supprimé avec succès.';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter un véhicule</h3>
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
                    <label for="images" class="form-label">Images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="vehicules.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM vehicules WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $vehicule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehicule):
    ?>
    <script>
    function updateVehicule(event, vehiculeId) {
        event.preventDefault();
        const form = document.getElementById(`editVehiculeForm${vehiculeId}`);
        const formData = new FormData(form);

        fetch('update-vehicule.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'vehicules.php?success=edit';
            } else {
                alert('Erreur lors de la modification : ' + data.message);
            }
        })
        .catch(error => {
            alert('Erreur lors de la modification : ' + error);
        });

        return false;
    }
    </script>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifier le véhicule</h3>
        </div>
        <div class="card-body">
            <form id="editVehiculeForm<?= $vehicule['id'] ?>" onsubmit="return updateVehicule(event, <?= $vehicule['id'] ?>)" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $vehicule['id'] ?>">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($vehicule['nom']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($vehicule['description']) ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="images" class="form-label">Ajouter des images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                </div>
                
                <?php
                $stmt = $pdo->prepare("SELECT * FROM vehicle_images WHERE id_vehicule = ?");
                $stmt->execute([$vehicule['id']]);
                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($images):
                ?>
                <div class="mb-3">
                    <label class="form-label">Images actuelles</label>
                    <div class="row g-2">
                        <?php foreach ($images as $image): ?>
                        <div class="col-auto">
                            <div class="position-relative">
                                <img src="../images/vehicules/<?= $image['image_path'] ?>" class="img-thumbnail" style="height: 100px;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image" 
                                        data-id="<?= $image['id'] ?>" data-type="vehicle">
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
                    <a href="vehicules.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicules as $vehicule): ?>
                <tr>
                    <td><?= htmlspecialchars($vehicule['nom']) ?></td>
                    <td><?= htmlspecialchars($vehicule['description']) ?></td>
                    <td>
                        <?php if (!empty($vehicule['images'])): ?>
                            <div class="d-flex gap-1">
                                <?php foreach (array_slice($vehicule['images'], 0, 3) as $image): ?>
                                    <img src="../images/vehicules/<?= $image ?>" class="img-thumbnail" style="height: 50px;">
                                <?php endforeach; ?>
                                <?php if (count($vehicule['images']) > 3): ?>
                                    <span class="badge bg-secondary">+<?= count($vehicule['images']) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Aucune image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?action=edit&id=<?= $vehicule['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ?')) { 
                                        document.getElementById('delete-form-<?= $vehicule['id'] ?>').submit(); 
                                    }">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <form id="delete-form-<?= $vehicule['id'] ?>" method="POST" style="display: none;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $vehicule['id'] ?>">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
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

<?php require 'footer.php'; ?> 