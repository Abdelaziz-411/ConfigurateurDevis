<?php
require 'header.php';

try {
    // Vérifier si les tables existent
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required_tables = ['vehicules', 'vehicle_images', 'kits', 'kit_images', 'options', 'option_images'];
    $missing_tables = array_diff($required_tables, $tables);
    
    if (!empty($missing_tables)) {
        throw new Exception("Tables manquantes : " . implode(', ', $missing_tables));
    }
    
// Récupérer toutes les images avec leurs informations associées
$images = $pdo->query("
    SELECT 
            'vehicule' COLLATE utf8mb4_unicode_ci as type,
            v.nom COLLATE utf8mb4_unicode_ci as element_nom,
            vi.image_path COLLATE utf8mb4_unicode_ci as image_path,
        vi.id as image_id,
        v.id as element_id
    FROM vehicules v
        LEFT JOIN vehicle_images vi ON v.id = vi.id_vehicule
    UNION ALL
    SELECT 
            'kit' COLLATE utf8mb4_unicode_ci as type,
            k.nom COLLATE utf8mb4_unicode_ci as element_nom,
            ki.image_path COLLATE utf8mb4_unicode_ci as image_path,
        ki.id as image_id,
        k.id as element_id
    FROM kits k
        LEFT JOIN kit_images ki ON k.id = ki.id_kit
    UNION ALL
    SELECT 
            'option' COLLATE utf8mb4_unicode_ci as type,
            o.nom COLLATE utf8mb4_unicode_ci as element_nom,
            oi.image_path COLLATE utf8mb4_unicode_ci as image_path,
        oi.id as image_id,
        o.id as element_id
    FROM options o
        LEFT JOIN option_images oi ON o.id = oi.id_option
    ORDER BY type, element_nom
")->fetchAll(PDO::FETCH_ASSOC);

// Grouper les images par type
$images_grouped = [];
foreach ($images as $image) {
        if ($image['image_path'] !== null) {  // Ne prendre que les éléments avec des images
    $images_grouped[$image['type']][] = $image;
        }
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<h4 class="alert-heading">Erreur lors de la récupération des images</h4>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    if (isset($pdo) && $pdo->errorInfo()[2]) {
        echo '<p>Erreur SQL : ' . htmlspecialchars($pdo->errorInfo()[2]) . '</p>';
    }
    echo '</div>';
    $images_grouped = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gestion des Images</h2>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        switch ($_GET['success']) {
            case 'delete':
                echo 'Image supprimée avec succès.';
                break;
        }
        ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Véhicules</h3>
            </div>
            <div class="card-body">
                <?php if (isset($images_grouped['vehicule'])): ?>
                    <div class="row g-2">
                        <?php foreach ($images_grouped['vehicule'] as $image): ?>
                            <div class="col-6">
                                <div class="position-relative">
                                    <img src="../images/vehicules/<?= $image['image_path'] ?>" 
                                         class="img-thumbnail w-100" 
                                         style="height: 150px; object-fit: cover;"
                                         title="<?= htmlspecialchars($image['element_nom']) ?>">
                                    <button type="button" 
                                            class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image"
                                            data-id="<?= $image['image_id'] ?>"
                                            data-type="vehicule">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <small class="d-block text-truncate mt-1">
                                    <?= htmlspecialchars($image['element_nom']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Aucune image de véhicule</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Kits</h3>
            </div>
            <div class="card-body">
                <?php if (isset($images_grouped['kit'])): ?>
                    <div class="row g-2">
                        <?php foreach ($images_grouped['kit'] as $image): ?>
                            <div class="col-6">
                                <div class="position-relative">
                                    <img src="../images/kits/<?= $image['image_path'] ?>" 
                                         class="img-thumbnail w-100" 
                                         style="height: 150px; object-fit: cover;"
                                         title="<?= htmlspecialchars($image['element_nom']) ?>">
                                    <button type="button" 
                                            class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image"
                                            data-id="<?= $image['image_id'] ?>"
                                            data-type="kit">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <small class="d-block text-truncate mt-1">
                                    <?= htmlspecialchars($image['element_nom']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Aucune image de kit</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Options</h3>
            </div>
            <div class="card-body">
                <?php if (isset($images_grouped['option'])): ?>
                    <div class="row g-2">
                        <?php foreach ($images_grouped['option'] as $image): ?>
                            <div class="col-6">
                                <div class="position-relative">
                                    <img src="../images/options/<?= $image['image_path'] ?>" 
                                         class="img-thumbnail w-100" 
                                         style="height: 150px; object-fit: cover;"
                                         title="<?= htmlspecialchars($image['element_nom']) ?>">
                                    <button type="button" 
                                            class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-image"
                                            data-id="<?= $image['image_id'] ?>"
                                            data-type="option">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <small class="d-block text-truncate mt-1">
                                    <?= htmlspecialchars($image['element_nom']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Aucune image d'option</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
                    this.closest('.col-6').remove();
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