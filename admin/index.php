<?php
require 'header.php';
require 'check_auth.php';

// Récupérer quelques statistiques
$stats = [
    'vehicules' => $pdo->query("SELECT COUNT(*) FROM vehicules")->fetchColumn(),
    'kits' => $pdo->query("SELECT COUNT(*) FROM kits")->fetchColumn(),
    'options' => $pdo->query("SELECT COUNT(*) FROM options")->fetchColumn()
];
?>

<div class="stats">
    <div class="stat-card">
        <h3>Véhicules</h3>
        <p><?= $stats['vehicules'] ?></p>
    </div>
    <div class="stat-card">
        <h3>Kits</h3>
        <p><?= $stats['kits'] ?></p>
    </div>
    <div class="stat-card">
        <h3>Options</h3>
        <p><?= $stats['options'] ?></p>
    </div>
</div>

<div class="actions">
    <h2>Actions rapides</h2>
    <a href="modeles.php" class="btn btn-success">Ajouter un véhicule</a>
    <a href="kits.php?action=add" class="btn btn-success">Ajouter un kit</a>
    <a href="options.php?action=add" class="btn btn-success">Ajouter une option</a>
</div>

<?php require 'footer.php'; ?>