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
            $ordre = $_POST['ordre'] ?? 0;
            
            if ($_POST['action'] === 'add') {
                try {
                    $stmt = $pdo->prepare("INSERT INTO categories_options (nom, ordre) VALUES (?, ?)");
                    $stmt->execute([$nom, $ordre]);
                    header('Location: categories-options.php?success=add');
                    exit;
                } catch (PDOException $e) {
                    die("Une erreur est survenue lors de l'ajout de la catégorie : " . $e->getMessage());
                }
            } else {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE categories_options SET nom = ?, ordre = ? WHERE id = ?");
                $stmt->execute([$nom, $ordre, $id]);
                header('Location: categories-options.php?success=edit');
                exit;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            
            // Vérifier si la catégorie est utilisée
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM options WHERE id_categorie = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                header('Location: categories-options.php?error=used');
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM categories_options WHERE id = ?");
            $stmt->execute([$id]);
            header('Location: categories-options.php?success=delete');
            exit;
        }
    }
}

// Récupérer les catégories
$categories = $pdo->query("
    SELECT c.*, COUNT(o.id) as nb_options
    FROM categories_options c
    LEFT JOIN options o ON c.id = o.id_categorie
    GROUP BY c.id
    ORDER BY c.ordre, c.nom
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Catégories d'Options</h2>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Ajouter une catégorie
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 'add':
                echo 'Catégorie ajoutée avec succès.';
                break;
            case 'edit':
                echo 'Catégorie modifiée avec succès.';
                break;
            case 'delete':
                echo 'Catégorie supprimée avec succès.';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'used'): ?>
    <div class="alert alert-danger">
        Cette catégorie ne peut pas être supprimée car elle est utilisée par des options.
    </div>
<?php endif; ?>

<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ajouter une catégorie</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                
                <div class="mb-3">
                    <label for="ordre" class="form-label">Ordre d'affichage</label>
                    <input type="number" class="form-control" id="ordre" name="ordre" value="0" min="0">
                    <small class="text-muted">Les catégories seront triées par ordre croissant</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <a href="categories-options.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])): ?>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM categories_options WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $categorie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($categorie):
    ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifier la catégorie</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $categorie['id'] ?>">
                
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($categorie['nom']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="ordre" class="form-label">Ordre d'affichage</label>
                    <input type="number" class="form-control" id="ordre" name="ordre" value="<?= $categorie['ordre'] ?>" min="0">
                    <small class="text-muted">Les catégories seront triées par ordre croissant</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="categories-options.php" class="btn btn-secondary">Annuler</a>
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
                    <th style="width: 5%">Ordre</th>
                    <th style="width: 35%">Nom</th>
                    <th style="width: 20%">Nombre d'options</th>
                    <th style="width: 40%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $categorie): ?>
                <tr>
                    <td><?= $categorie['ordre'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($categorie['nom']) ?></td>
                    <td><?= $categorie['nb_options'] ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="?action=edit&id=<?= $categorie['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($categorie['nb_options'] == 0): ?>
                            <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                    onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) { 
                                        document.getElementById('delete-form-<?= $categorie['id'] ?>').submit(); 
                                    }">
                                <i class="bi bi-trash"></i>
                            </button>
                            <form id="delete-form-<?= $categorie['id'] ?>" method="POST" style="display: none;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $categorie['id'] ?>">
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require 'footer.php'; ?> 