<?php
// Récupérer les données du modèle
$stmt = $pdo->prepare("SELECT m.*, 
                          GROUP_CONCAT(DISTINCT mi.image_path) as images,
                          GROUP_CONCAT(ms.statut) as statuts -- Récupérer les statuts associés
                          FROM modeles m
                          LEFT JOIN modele_images mi ON m.id = mi.id_modele
                          LEFT JOIN modele_statuts ms ON m.id = ms.id_modele
                          WHERE m.id = ?
                          GROUP BY m.id");
$stmt->execute([$id]);
$modele_edit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$modele_edit) {
    header('Location: modeles.php');
    exit;
}

// Transformer les données (images et statuts)
$modele_edit['images'] = $modele_edit['images'] ? explode(',', $modele_edit['images']) : [];
$modele_edit['statuts'] = $modele_edit['statuts'] ? explode(',', $modele_edit['statuts']) : [];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Modifier le modèle</h1>
        <a href="?action=list" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $modele_edit['id']; ?>">
                
                <div class="mb-3">
                    <label for="id_marque" class="form-label">Marque</label>
                    <select class="form-select" id="id_marque" name="id_marque" required>
                        <option value="">Sélectionnez une marque</option>
                        <?php foreach ($marques as $marque): ?>
                            <option value="<?php echo $marque['id']; ?>" <?php echo $marque['id'] == $modele_edit['id_marque'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($marque['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du modèle</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($modele_edit['nom']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Statuts compatibles</label>
                    <div class="row">
                        <?php foreach ($statuts_possibles as $statut): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="statut_<?php echo htmlspecialchars($statut); ?>" 
                                           name="statuts[]" 
                                           value="<?php echo htmlspecialchars($statut); ?>"
                                           <?php echo in_array($statut, $modele_edit['statuts']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="statut_<?php echo htmlspecialchars($statut); ?>">
                                        <?php echo htmlspecialchars($statut); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($modele_edit['images'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Images actuelles</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($modele_edit['images'] as $image): ?>
                                <div class="position-relative">
                                    <img src="../images/modeles/<?php echo htmlspecialchars($image); ?>" 
                                         alt="Image du modèle" 
                                         class="img-thumbnail" 
                                         style="width: 100px; height: 100px; object-fit: cover;">
                                    <button type="button" 
                                            class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                            onclick="deleteImage('<?php echo htmlspecialchars($image); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="images" class="form-label">Ajouter des images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif" onchange="previewImages(this)">
                    <div id="imagePreview" class="mt-2"></div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteImage(image_path) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
        fetch('delete-image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=<?php echo $modele_edit['id']; ?>&image=' + encodeURIComponent(image_path) + '&type=modele'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression de l\'image : ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la suppression de l\'image');
        });
    }
}
</script> 