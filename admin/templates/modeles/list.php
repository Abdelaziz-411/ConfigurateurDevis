<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Gestion des modèles</h1>
        <a href="?action=add" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Ajouter un modèle
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 'add':
                    echo "Le modèle a été ajouté avec succès.";
                    break;
                case 'edit':
                    echo "Le modèle a été modifié avec succès.";
                    break;
                case 'delete':
                    echo "Le modèle a été supprimé avec succès.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Marque</th>
                            <th>Nom</th>
                            <th>Statuts</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modeles as $modele): ?>
                            <tr>
                                <td><?php echo $modele['id']; ?></td>
                                <td><?php echo htmlspecialchars($modele['marque_nom']); ?></td>
                                <td><?php echo htmlspecialchars($modele['nom']); ?></td>
                                <td>
                                    <?php if (!empty($modele['statuts'])): ?>
                                        <div class="statut-tags">
                                            <?php foreach ($modele['statuts'] as $statut): ?>
                                                <span><?php echo htmlspecialchars($statut); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Aucun statut</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($modele['images'])): ?>
                                        <div class="d-flex gap-2">
                                            <?php foreach ($modele['images'] as $image): ?>
                                                <img src="../images/modeles/<?php echo htmlspecialchars($image); ?>" 
                                                     alt="Image du modèle" 
                                                     class="img-thumbnail" 
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Aucune image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?action=edit&id=<?php echo $modele['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="deleteModele(<?php echo $modele['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteModele(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script> 