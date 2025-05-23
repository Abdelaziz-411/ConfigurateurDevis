<?php
require '../config.php';

// Récupérer la liste des kits
$kits = $pdo->query("SELECT k.id, k.nom FROM kits k")->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kit_id'])) {
    $kit_id = intval($_POST['kit_id']);
    
    // Upload des images
    foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
        if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['images']['name'][$index]);
            $targetPath = '../images/' . $fileName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                // Enregistrement dans la base de données
                $stmt = $pdo->prepare("INSERT INTO kit_images (kit_id, filename) VALUES (?, ?)");
                $stmt->execute([$kit_id, $fileName]);
            }
        }
    }
    
    echo "<div style='color: green; margin-bottom: 20px;'>Images ajoutées avec succès !</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Images - Administration</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Gestion des Images</h1>
        </div>
    </div>

    <div class="container">
        <?php require 'navbar.php'; ?>

        <?php if (isset($message)): ?>
            <div class="message message-<?= $success ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label for="kit_id">Sélectionner un kit :</label>
                <select name="kit_id" id="kit_id" required>
                    <option value="">-- Choisir un kit --</option>
                    <?php foreach ($kits as $kit): ?>
                        <option value="<?= $kit['id'] ?>"><?= htmlspecialchars($kit['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="images">Sélectionner les images :</label>
                <input type="file" name="images[]" id="images" multiple accept="image/*" required>
                <small class="form-text">Formats acceptés : JPG, PNG, GIF. Taille maximale : 5MB par image.</small>
            </div>
            
            <button type="submit" class="btn btn-success">Ajouter les images</button>
        </form>
    </div>

    <style>
        .upload-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        .form-text {
            display: block;
            margin-top: 0.25rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
    </style>
</body>
</html> 