<?php
require 'header.php';
require 'check_auth.php';

// Récupérer les marques avec leurs images
try {
    // Récupérer les marques de base
    $stmt = $pdo->query("SELECT * FROM marques ORDER BY nom");
    $marques = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque marque, récupérer ses images
    foreach ($marques as &$marque) {
        $stmt = $pdo->prepare("SELECT image_path FROM marque_images WHERE id_marque = ?");
        $stmt->execute([$marque['id']]);
        $marque['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($marque); // Important : détacher la référence
} catch (PDOException $e) {
    die("Erreur lors de la récupération des marques : " . $e->getMessage());
}

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';

// --- Traitement du formulaire (Ajout/Modification/Suppression) --- 
// Cette partie sera implémentée plus tard dans des fichiers séparés (save-marque.php, update-marque.php, delete-marque.php)
// pour suivre la structure existante du projet.

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Marques</h2>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter une marque
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 'add':
                echo 'Marque ajoutée avec succès.';
                break;
            case 'edit':
                echo 'Marque modifiée avec succès.';
                break;
            case 'delete':
                echo 'Marque supprimée avec succès.';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter une marque</h3>
        </div>
        <div class="card-body">
            <form action="save-marque.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom de la marque</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                
                <div class="mb-3">
                    <label for="logo" class="form-label">Logo de la marque</label>
                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="marques.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($action === 'edit' && $id): ?>
    <?php
    $stmt = $pdo->prepare("SELECT m.*, GROUP_CONCAT(mi.image_path) as images FROM marques m LEFT JOIN marque_images mi ON m.id = mi.id_marque WHERE m.id = ? GROUP BY m.id");
    $stmt->execute([$id]);
    $marque = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($marque) {
         $marque['images'] = $marque['images'] ? explode(',', $marque['images']) : [];
    }
    ?>
    <?php if ($marque): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifier la marque : <?= htmlspecialchars($marque['nom']) ?></h3>
        </div>
        <div class="card-body">
            <form action="update-marque.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $marque['id'] ?>">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom de la marque</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($marque['nom']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="logo" class="form-label">Modifier le logo</label>
                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                </div>

                <?php if (!empty($marque['images'])): ?>
                <div class="mb-3">
                    <label class="form-label">Logo actuel</label>
                    <div class="row g-2" id="current-logo-<?= $marque['id'] ?>">
                        <?php foreach ($marque['images'] as $image_path): ?>
                        <div class="col-auto image-preview-item">
                            <div class="position-relative">
                                <img src="../images/marques/<?= htmlspecialchars($image_path) ?>" class="img-thumbnail" style="height: 100px;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image" 
                                        data-id="<?= $marque['id'] ?>" data-type="marque" data-image="<?= htmlspecialchars($image_path) ?>">
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
                    <a href="marques.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger">Marque non trouvée.</div>
    <?php endif; ?>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Logo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($marques as $marque): ?>
                <tr>
                    <td><?= htmlspecialchars($marque['nom']) ?></td>
                    <td>
                        <?php if (!empty($marque['images'])): ?>
                            <img src="../images/marques/<?= htmlspecialchars($marque['images'][0]) ?>" class="img-thumbnail" style="height: 50px;">
                        <?php else: ?>
                            <span class="text-muted">Aucun logo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="?action=edit&id=<?= $marque['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                             <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette marque ?')) { 
                                        document.getElementById('delete-form-<?= $marque['id'] ?>').submit(); 
                                    }">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <form id="delete-form-<?= $marque['id'] ?>" action="delete-marque.php" method="POST" style="display: none;">
                            <input type="hidden" name="id" value="<?= $marque['id'] ?>">
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
            const imageId = this.dataset.id;
            const type = this.dataset.type;
            const imageName = this.dataset.image; // Assurez-vous d'avoir cette donnée dans le HTML
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                fetch('delete-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded', // Utiliser ce type pour le formulaire URL encodé
                    },
                    body: `id=${imageId}&type=${type}&image=${imageName}` // Envoyer l'ID de la marque et le nom de l'image
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Option 1: Supprimer l'élément de l'interface
                         this.closest('.image-preview-item').remove();
                        // Option 2: Recharger la page pour voir l'état mis à jour
                        // window.location.reload(); 
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