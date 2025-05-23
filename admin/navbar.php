<?php
// Déterminer la page active
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="nav">
    <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Tableau de bord</a>
    <a href="vehicules.php" class="<?= $current_page === 'vehicules.php' ? 'active' : '' ?>">Véhicules</a>
    <a href="kits.php" class="<?= $current_page === 'kits.php' ? 'active' : '' ?>">Kits</a>
    <a href="options.php" class="<?= $current_page === 'options.php' ? 'active' : '' ?>">Options</a>
    <a href="add-images.php" class="<?= $current_page === 'add-images.php' ? 'active' : '' ?>">Gestion des Images</a>
    <a href="../" target="_blank">Voir le site</a>
</div> 