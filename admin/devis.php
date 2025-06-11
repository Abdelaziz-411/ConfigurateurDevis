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
    SELECT d.*, d.type_carrosserie, k.nom as kit_nom
    FROM devis d
    LEFT JOIN kits k ON d.id_kit = k.id
    ORDER BY d.date_creation DESC
");
$devis = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h1>Gestion des Devis</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['success']) {
                case 'edit':
                    echo 'Le devis a été modifié avec succès.';
                    break;
                case 'email':
                    echo 'L\'email a été envoyé avec succès.';
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            switch ($_GET['error']) {
                case 'email':
                    echo 'Une erreur est survenue lors de l\'envoi de l\'email.';
                    break;
                default:
                    echo htmlspecialchars($_GET['error']);
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" placeholder="Nom, email, téléphone...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status">
                        <option value="">Tous les statuts</option>
                        <option value="nouveau">Nouveau</option>
                        <option value="en_cours">En cours</option>
                        <option value="traite">Traité</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateRange" class="form-label">Période</label>
                    <select class="form-select" id="dateRange">
                        <option value="all">Toutes les dates</option>
                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary w-100" onclick="exportDevis()">
                        <i class="bi bi-download"></i> Exporter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive mt-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Type de carrosserie</th>
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
                        <td><?= htmlspecialchars($d['type_carrosserie']) ?></td>
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
                            <div class="d-flex gap-2">
                                <form action="send_email.php" method="POST" class="d-inline">
                                    <input type="hidden" name="devis_id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-info btn-sm" title="Envoyer par email">
                                        <i class="bi bi-envelope"></i>
                            </button>
                                </form>
                                <a href="edit_devis.php?id=<?= $d['id'] ?>" class="btn btn-primary btn-sm" title="Modifier">
                                <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteDevis(<?= $d['id'] ?>)" title="Supprimer">
                                <i class="bi bi-trash"></i>
                            </button>
                                <a href="../generate-pdf.php?devis_id=<?= $d['id'] ?>" class="btn btn-secondary btn-sm" target="_blank" title="Télécharger PDF">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                            </div>
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
                                                <strong>Type de carrosserie :</strong> <?= htmlspecialchars($d['type_carrosserie']) ?><br>
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

                    <!-- Modal modification -->
                    <div class="modal fade" id="editDevisModal<?= $d['id'] ?>" tabindex="-1" role="dialog">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Modifier le devis #<?= $d['id'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="editDevisForm<?= $d['id'] ?>" onsubmit="return updateDevis(event, <?= $d['id'] ?>)">
                                        <input type="hidden" name="devis_id" value="<?= $d['id'] ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="edit_nom<?= $d['id'] ?>" class="form-label">Nom</label>
                                                    <input type="text" class="form-control" id="edit_nom<?= $d['id'] ?>" name="nom" value="<?= htmlspecialchars($d['nom']) ?>" required autocomplete="family-name">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_prenom<?= $d['id'] ?>" class="form-label">Prénom</label>
                                                    <input type="text" class="form-control" id="edit_prenom<?= $d['id'] ?>" name="prenom" value="<?= htmlspecialchars($d['prenom']) ?>" required autocomplete="given-name">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_email<?= $d['id'] ?>" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="edit_email<?= $d['id'] ?>" name="email" value="<?= htmlspecialchars($d['email']) ?>" required autocomplete="email">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_telephone<?= $d['id'] ?>" class="form-label">Téléphone</label>
                                                    <input type="tel" class="form-control" id="edit_telephone<?= $d['id'] ?>" name="telephone" value="<?= htmlspecialchars($d['telephone']) ?>" required autocomplete="tel">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="edit_prix_ht<?= $d['id'] ?>" class="form-label">Prix HT</label>
                                                    <input type="number" step="0.01" class="form-control" id="edit_prix_ht<?= $d['id'] ?>" name="prix_ht" value="<?= $d['prix_ht'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_prix_ttc<?= $d['id'] ?>" class="form-label">Prix TTC</label>
                                                    <input type="number" step="0.01" class="form-control" id="edit_prix_ttc<?= $d['id'] ?>" name="prix_ttc" value="<?= $d['prix_ttc'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_statut<?= $d['id'] ?>" class="form-label">Statut</label>
                                                    <select class="form-select" id="edit_statut<?= $d['id'] ?>" name="statut" required>
                                                        <option value="nouveau" <?= $d['statut'] === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                                                        <option value="en_cours" <?= $d['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                                        <option value="traite" <?= $d['statut'] === 'traite' ? 'selected' : '' ?>>Traité</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_message<?= $d['id'] ?>" class="form-label">Message</label>
                                            <textarea class="form-control" id="edit_message<?= $d['id'] ?>" name="message" rows="3"><?= htmlspecialchars($d['message']) ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="edit_configuration<?= $d['id'] ?>" class="form-label">Configuration</label>
                                            <textarea class="form-control" id="edit_configuration<?= $d['id'] ?>" name="configuration" rows="5" required><?= htmlspecialchars($d['configuration']) ?></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" form="editDevisForm<?= $d['id'] ?>" class="btn btn-primary">Enregistrer</button>
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

// Fonction pour supprimer un devis
function deleteDevis(devisId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce devis ?')) {
        fetch('delete-devis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ devis_id: devisId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression : ' + data.message);
            }
        })
        .catch(error => {
            alert('Erreur lors de la suppression : ' + error);
        });
    }
}

// Fonction pour mettre à jour un devis
function updateDevis(event, devisId) {
    event.preventDefault();
    const form = document.getElementById(`editDevisForm${devisId}`);
    const formData = new FormData(form);

    fetch('update-devis.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modalElement = document.getElementById(`editDevisModal${devisId}`);
            const modal = new bootstrap.Modal(modalElement);
            modal.hide();
            // Recharger la page pour voir les modifications
            location.reload();
        } else {
            alert('Erreur lors de la modification : ' + data.message);
        }
    })
    .catch(error => {
        alert('Erreur lors de la modification : ' + error);
    });

    return false;
}

// Fonction pour filtrer les devis
function filterDevis() {
    const searchTerm = document.getElementById('search').value.toLowerCase();
    const statusFilter = document.getElementById('status').value;
    const dateRange = document.getElementById('dateRange').value;
    
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.querySelector('select[name="statut"]').value;
        const date = new Date(row.querySelector('td:nth-child(2)').textContent);
        
        let showRow = true;
        
        // Filtre de recherche
        if (searchTerm && !text.includes(searchTerm)) {
            showRow = false;
        }
        
        // Filtre de statut
        if (statusFilter && status !== statusFilter) {
            showRow = false;
        }
        
        // Filtre de date
        if (dateRange !== 'all') {
            const today = new Date();
            const diffTime = Math.abs(today - date);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            switch(dateRange) {
                case 'today':
                    if (diffDays > 1) showRow = false;
                    break;
                case 'week':
                    if (diffDays > 7) showRow = false;
                    break;
                case 'month':
                    if (diffDays > 30) showRow = false;
                    break;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Ajouter les écouteurs d'événements pour les filtres
document.getElementById('search').addEventListener('input', filterDevis);
document.getElementById('status').addEventListener('change', filterDevis);
document.getElementById('dateRange').addEventListener('change', filterDevis);

// Fonction pour exporter les devis
function exportDevis() {
    const rows = Array.from(document.querySelectorAll('tbody tr')).filter(row => row.style.display !== 'none');
    const csvContent = [
        ['ID', 'Date', 'Client', 'Contact', 'Type de carrosserie', 'Kit', 'Prix TTC', 'Statut'].join(','),
        ...rows.map(row => {
            const cells = row.querySelectorAll('td');
            return [
                cells[0].textContent,
                cells[1].textContent,
                cells[2].textContent,
                cells[3].textContent,
                cells[4].textContent,
                cells[5].textContent,
                cells[6].textContent,
                cells[7].querySelector('select').value
            ].join(',');
        })
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'devis_' + new Date().toISOString().split('T')[0] + '.csv';
    link.click();
}
</script>

<style>
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-control:focus, .form-select:focus {
    border-color: rgb(88, 0, 189);
    box-shadow: 0 0 0 0.2rem rgba(88, 0, 189, 0.25);
}

.btn-primary {
    background-color: rgb(88, 0, 189);
    border-color: rgb(88, 0, 189);
}

.btn-primary:hover {
    background-color: rgb(98, 10, 199);
    border-color: rgb(98, 10, 199);
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

.status-form select {
    min-width: 120px;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .col-md-2 {
        margin-top: 1rem;
    }
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #117a8b;
    color: white;
}

.gap-2 {
    gap: 0.5rem !important;
}
</style>

<?php require 'footer.php'; ?> 