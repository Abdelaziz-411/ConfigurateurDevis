<?php
session_start();
require_once '../config.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

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
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Devis - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'header.php'; ?>

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
                        <div class="modal fade" id="devisModal<?= $d['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Devis #<?= $d['id'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 