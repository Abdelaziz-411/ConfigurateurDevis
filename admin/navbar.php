<?php
// Déterminer la page active
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
    <div class="nav-links">
    <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Tableau de bord</a>
    
    <a href="kits.php" class="<?= $current_page === 'kits.php' ? 'active' : '' ?>">Kits</a>
    <a href="options.php" class="<?= $current_page === 'options.php' ? 'active' : '' ?>">Options</a>
        <a href="devis.php" class="<?= $current_page === 'devis.php' ? 'active' : '' ?>">Devis</a>
        <a href="logout.php">Déconnexion</a>
</div> 
</nav> 