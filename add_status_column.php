<?php
require_once 'config.php';

try {
    // Vérifier si la colonne status existe déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM modeles LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        // La colonne n'existe pas, on l'ajoute
        $sql = "ALTER TABLE modeles ADD COLUMN status VARCHAR(10) DEFAULT NULL";
        $pdo->exec($sql);
        echo "La colonne status a été ajoutée avec succès à la table modeles.";
    } else {
        echo "La colonne status existe déjà dans la table modeles.";
    }
} catch (PDOException $e) {
    echo "Erreur lors de l'ajout de la colonne status : " . $e->getMessage();
}
?> 