<?php
require 'header.php';
require 'check_auth.php';

// Mise à jour du statut si demandé
if (isset($_POST['devis_id']) && isset($_POST['statut'])) {
    $stmt = $pdo->prepare("UPDATE devis SET statut = ? WHERE id = ?");
    $stmt->execute([$_POST['statut'], $_POST['devis_id']]);
}

// Récupération des devis
$stmt = $pdo->query("
    SELECT d.*, v.nom as vehicule_nom, k.nom as kit_nom
    FROM devis d
    LEFT JOIN vehicules v ON d.id_vehicule = v.id
    LEFT JOIN kits k ON d.id_kit = k.id
    ORDER BY d.date_creation DESC
");
$devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h1>Gestion des Devis</h1>

    <div class="table-responsive mt-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Véhicule</th>
                    <th>Kit</th>
                    <th>Prix TTC</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devis as $d): ?>
                    <tr>
                        <td><?= $d['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($d['date_creation'])) ?></td>
                        <td><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></td>
                        <td>
                            <a href="mailto:<?= htmlspecialchars($d['email']) ?>"><?= htmlspecialchars($d['email']) ?></a><br>
                            <small><?= htmlspecialchars($d['telephone']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($d['vehicule_nom']) ?></td>
                        <td><?= $d['kit_nom'] ? htmlspecialchars($d['kit_nom']) : '-' ?></td>
                        <td><?= number_format($d['prix_ttc'], 2, ',', ' ') ?> €</td>
                        <td>
                            <form method="post" class="status-form">
                                <input type="hidden" name="devis_id" value="<?= $d['id'] ?>">
                                <select name="statut" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="nouveau" <?= $d['statut'] === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                                    <option value="en_cours" <?= $d['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                    <option value="traite" <?= $d['statut'] === 'traite' ? 'selected' : '' ?>>Traité</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#devisModal<?= $d['id'] ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="../generate-pdf.php?devis_id=<?= $d['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                        </td>
                    </tr>

                    <!-- Modal détails -->
                    <div class="modal fade" id="devisModal<?= $d['id'] ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Devis #<?= $d['id'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" tabindex="0"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Informations client</h6>
                                            <p>
                                                <strong>Nom :</strong> <?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?><br>
                                                <strong>Email :</strong> <?= htmlspecialchars($d['email']) ?><br>
                                                <strong>Téléphone :</strong> <?= htmlspecialchars($d['telephone']) ?><br>
                                                <?php if ($d['message']): ?>
                                                    <strong>Message :</strong><br>
                                                    <?= nl2br(htmlspecialchars($d['message'])) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Détails de la configuration</h6>
                                            <p>
                                                <strong>Véhicule :</strong> <?= htmlspecialchars($d['vehicule_nom']) ?><br>
                                                <strong>Kit :</strong> <?= $d['kit_nom'] ? htmlspecialchars($d['kit_nom']) : '-' ?><br>
                                                <strong>Prix HT :</strong> <?= number_format($d['prix_ht'], 2, ',', ' ') ?> €<br>
                                                <strong>Prix TTC :</strong> <?= number_format($d['prix_ttc'], 2, ',', ' ') ?> €
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <h6>Configuration complète</h6>
                                        <pre class="bg-light p-3"><?= htmlspecialchars($d['configuration']) ?></pre>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <a href="../generate-pdf.php?devis_id=<?= $d['id'] ?>" class="btn btn-primary" target="_blank">
                                        <i class="bi bi-file-pdf"></i> Générer PDF
                                    </a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" tabindex="0">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des modals
    const modals = document.querySelectorAll('.modal');
    let lastFocusedElement = null;

    modals.forEach(modal => {
        // Gestion de l'ouverture du modal
        modal.addEventListener('show.bs.modal', function() {
            lastFocusedElement = document.activeElement;
            
            // Stocker tous les éléments focusables en dehors du modal
            const focusableElements = document.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            
            // Désactiver temporairement le focus sur les éléments en dehors du modal
            focusableElements.forEach(element => {
                if (!modal.contains(element)) {
                    element.setAttribute('tabindex', '-1');
                }
            });
        });

        // Gestion de la fermeture du modal
        modal.addEventListener('hidden.bs.modal', function() {
            // Restaurer le focus sur l'élément précédent
            if (lastFocusedElement) {
                lastFocusedElement.focus();
            }
            
            // Restaurer le tabindex des éléments
            const focusableElements = document.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="-1"]'
            );
            
            focusableElements.forEach(element => {
                if (!modal.contains(element)) {
                    element.removeAttribute('tabindex');
                }
            });
        });

        // Gestion de la touche Escape
        modal.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });

        // Gestion du focus dans le modal
        modal.addEventListener('shown.bs.modal', function() {
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        });
    });

    // Modifier le comportement par défaut de Bootstrap pour les modals
    const originalModal = bootstrap.Modal.prototype.constructor;
    bootstrap.Modal = function(element, config) {
        const modal = new originalModal(element, config);
        const originalShow = modal.show;
        
        modal.show = function() {
            element.removeAttribute('aria-hidden');
            return originalShow.call(this);
        };
        
        return modal;
    };
});
</script>

<?php require 'footer.php'; ?> 