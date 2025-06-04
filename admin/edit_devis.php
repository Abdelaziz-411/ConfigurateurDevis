<?php
require 'header.php';
require 'check_auth.php';

// Vérifier si l'ID du devis est fourni
if (!isset($_GET['id'])) {
    header('Location: devis.php?error=id_missing');
    exit;
}

$devis_id = $_GET['id'];

// Récupérer les informations du devis
try {
    $stmt = $pdo->prepare("
        SELECT d.*, v.nom as vehicule_nom, k.nom as kit_nom
        FROM devis d
        LEFT JOIN vehicules v ON d.id_vehicule = v.id
        LEFT JOIN kits k ON d.id_kit = k.id
        WHERE d.id = ?
    ");
    $stmt->execute([$devis_id]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis) {
        header('Location: devis.php?error=devis_not_found');
        exit;
    }
} catch (PDOException $e) {
    header('Location: devis.php?error=database_error');
    exit;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Modifier le devis #<?= $devis_id ?></h1>
        <a href="devis.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="update-devis.php" method="POST">
                <input type="hidden" name="devis_id" value="<?= $devis_id ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($devis['nom']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($devis['prenom']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($devis['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($devis['telephone']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="prix_ht" class="form-label">Prix HT</label>
                            <input type="number" step="0.01" class="form-control" id="prix_ht" name="prix_ht" value="<?= $devis['prix_ht'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="prix_ttc" class="form-label">Prix TTC</label>
                            <input type="number" step="0.01" class="form-control" id="prix_ttc" name="prix_ttc" value="<?= $devis['prix_ttc'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut" required>
                                <option value="nouveau" <?= $devis['statut'] === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                                <option value="en_cours" <?= $devis['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="traite" <?= $devis['statut'] === 'traite' ? 'selected' : '' ?>>Traité</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="3"><?= htmlspecialchars($devis['message']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="configuration" class="form-label">Configuration</label>
                    <textarea class="form-control" id="configuration" name="configuration" rows="5" required><?= htmlspecialchars($devis['configuration']) ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                    <a href="devis.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?> 