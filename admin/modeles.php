<?php
require 'header.php';
require 'check_auth.php';

// Récupérer les modèles avec leurs images et marques associées
try {
    $stmt = $pdo->query("SELECT m.*, ma.nom as marque_nom, GROUP_CONCAT(CONCAT('../images/modeles/', mi.image_path)) as images FROM modeles m LEFT JOIN marques ma ON m.id_marque = ma.id LEFT JOIN modele_images mi ON m.id = mi.id_modele GROUP BY m.id ORDER BY ma.nom, m.nom");
    $modeles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Données modèles récupérées (admin/modeles.php) : " . print_r($modeles, true));
    foreach ($modeles as &$modele) {
        $modele['images'] = $modele['images'] ? explode(',', $modele['images']) : [];
        error_log("Images traitées pour modèle " . $modele['id'] . " : " . print_r($modele['images'], true));
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération des modèles : " . $e->getMessage());
}

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';

// Statuts de véhicule possibles
$statuts = ['L1H1', 'L2H1', 'L2H2', 'L3H2', 'L3H3', 'L4H3'];

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Modèles</h2>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter un modèle
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 'add':
                echo 'Modèle ajouté avec succès.';
                break;
            case 'edit':
                echo 'Modèle modifié avec succès.';
                break;
            case 'delete':
                echo 'Modèle supprimé avec succès.';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter un modèle</h3>
        </div>
        <div class="card-body">
            <form action="save-modele.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du modèle</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                
                <div class="mb-3">
                    <label for="id_marque" class="form-label">Marque</label>
                    <select class="form-select" id="id_marque" name="id_marque" required>
                        <option value="">Sélectionner une marque</option>
                        <?php
                        $marques = $pdo->query("SELECT id, nom FROM marques ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($marques as $marque) {
                            echo '<option value="' . $marque['id'] . '">' . htmlspecialchars($marque['nom']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                 <div class="mb-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Sélectionner un statut (Optionnel)</option>
                        <?php foreach ($statuts as $statut): ?>
                            <option value="<?= $statut ?>"><?= $statut ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="images" class="form-label">Photos du modèle</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="modeles.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($action === 'edit' && $id): ?>
    <?php
    $stmt = $pdo->prepare("SELECT m.*, GROUP_CONCAT(mi.image_path) as images FROM modeles m LEFT JOIN modele_images mi ON m.id = mi.id_modele WHERE m.id = ? GROUP BY m.id");
    $stmt->execute([$id]);
    $modele = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($modele) {
         $modele['images'] = $modele['images'] ? explode(',', $modele['images']) : [];
    }
    ?>
    <?php if ($modele): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifier le modèle : <?= htmlspecialchars($modele['nom']) ?></h3>
        </div>
        <div class="card-body">
            <form action="update-modele.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $modele['id'] ?>">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du modèle</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($modele['nom']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="id_marque" class="form-label">Marque</label>
                    <select class="form-select" id="id_marque" name="id_marque" required>
                         <option value="">Sélectionner une marque</option>
                        <?php
                        $marques = $pdo->query("SELECT id, nom FROM marques ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($marques as $marque) {
                            $selected = ($marque['id'] == $modele['id_marque']) ? 'selected' : '';
                            echo '<option value="' . $marque['id'] . '" ' . $selected . '>' . htmlspecialchars($marque['nom']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Sélectionner un statut (Optionnel)</option>
                        <?php foreach ($statuts as $statut): ?>
                            <option value="<?= $statut ?>" <?= ($modele['status'] === $statut) ? 'selected' : '' ?>><?= $statut ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="images" class="form-label">Ajouter des photos</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                </div>

                <?php if (!empty($modele['images'])): ?>
                <div class="mb-3">
                    <label class="form-label">Photos actuelles</label>
                    <div class="row g-2" id="current-images-<?= $modele['id'] ?>">
                        <?php foreach ($modele['images'] as $image_path): ?>
                        <div class="col-auto image-preview-item">
                            <div class="position-relative">
                                <img src="<?= htmlspecialchars($image_path) ?>" class="img-thumbnail" style="height: 100px;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image" 
                                        data-id="<?= $modele['id'] ?>" data-type="modele" data-image="<?= htmlspecialchars($image_path) ?>">
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
                    <a href="modeles.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger">Modèle non trouvé.</div>
    <?php endif; ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Marque</th>
                    <th>Statut</th>
                    <th>Photos</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modeles as $modele): ?>
                <tr>
                    <td><?= htmlspecialchars($modele['nom']) ?></td>
                    <td><?= htmlspecialchars($modele['marque_nom']) ?></td>
                     <td><?= htmlspecialchars($modele['status'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($modele['images'])): ?>
                            <div class="d-flex gap-1">
                                <?php foreach (array_slice($modele['images'], 0, 3) as $image_path): ?>
                                    <img src="<?= htmlspecialchars($image_path) ?>" class="img-thumbnail" style="height: 50px;">
                                <?php endforeach; ?>
                                <?php if (count($modele['images']) > 3): ?>
                                    <span class="badge bg-secondary">+<?= count($modele['images']) - 3 ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Aucune image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?action=edit&id=<?= $modele['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                             <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')) { 
                                        document.getElementById('delete-form-<?= $modele['id'] ?>').submit(); 
                                    }">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <form id="delete-form-<?= $modele['id'] ?>" action="delete-modele.php" method="POST" style="display: none;">
                            <input type="hidden" name="id" value="<?= $modele['id'] ?>">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-image').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.dataset.id; // ID du modèle dans ce cas pour le script actuel
            const type = this.dataset.type;
            const imageName = this.dataset.image;
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                // Note: Le script delete_image.php a été mis à jour pour gérer le type 'modele'
                fetch('delete_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${imageId}&type=${type}&image=${imageName}` // Envoyer l'ID et le nom de l'image
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.image-preview-item').remove();
                    } else {
                        alert('Erreur lors de la suppression de l\'image : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression de l\'image');
                });
            }
        });
    });
});
</script>

<?php endif; ?>

<?php require 'footer.php'; ?> 