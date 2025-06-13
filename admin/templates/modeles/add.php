<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Ajouter un modèle</h1>
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
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label for="id_marque" class="form-label">Marque</label>
                    <select class="form-select" id="id_marque" name="id_marque" required>
                        <option value="">Sélectionnez une marque</option>
                        <?php foreach ($marques as $marque): ?>
                            <option value="<?php echo $marque['id']; ?>">
                                <?php echo htmlspecialchars($marque['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                                           value="<?php echo htmlspecialchars($statut); ?>">
                                    <label class="form-check-label" for="statut_<?php echo htmlspecialchars($statut); ?>">
                                        <?php echo htmlspecialchars($statut); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du modèle</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>

                <div class="mb-3">
                    <label for="images" class="form-label">Images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
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