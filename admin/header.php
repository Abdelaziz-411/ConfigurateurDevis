<?php
require 'check_auth.php';
require '../config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Configurateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="admin-style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f6f9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: rgb(88, 0, 189);
            color: white;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .nav {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .nav a {
            color: #2c3e50;
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav a:hover {
            background: #f8f9fa;
            color: rgb(88, 0, 189);
            transform: translateY(-2px);
        }
        .nav a.active {
            background: rgb(88, 0, 189);
            color: white;
        }
        .nav a.active:hover {
            background: rgb(98, 10, 199);
        }
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                align-items: stretch;
            }
            .nav a {
                text-align: center;
                justify-content: center;
            }
        }
        .preview-image {
            display: inline-block;
            margin: 5px;
            text-align: center;
        }
        .preview-image img {
            max-width: 100px;
            height: auto;
        }
    </style>
    <script>
    // Fonction pour gérer les champs de prix
    function togglePrixInput(checkbox) {
        const prixInput = checkbox.parentElement.nextElementSibling.querySelector('.prix-input');
        if (checkbox.checked) {
            prixInput.disabled = false;
            prixInput.required = true;
            prixInput.value = '0.00';
        } else {
            prixInput.disabled = true;
            prixInput.required = false;
            prixInput.value = '';
        }
    }

    // Fonction pour afficher les images en prévisualisation
    function previewImages(input) {
        const previewContainer = document.getElementById('imagePreview');
        previewContainer.innerHTML = '';
        
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-image';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" style="height: 100px;">
                        <button type="button" class="btn btn-sm btn-danger mt-1" onclick="this.parentElement.remove()">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                    previewContainer.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }
    }

    // Initialiser les champs de prix pour les véhicules déjà sélectionnés
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.vehicule-check').forEach(checkbox => {
            if (checkbox.checked) {
                togglePrixInput(checkbox);
            }
        });

        // Gestion de la suppression des images
        document.querySelectorAll('.delete-image').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                    const id = this.dataset.id;
                    const type = this.dataset.type;
                    
                    fetch('delete_image.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `image_id=${id}&type=${type}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.col-auto').remove();
                        } else {
                            alert('Erreur lors de la suppression de l\'image');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la suppression de l\'image');
                    });
                }
            });
        });
    });
    </script>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>Administration du Configurateur</h1>
                <div class="user-info">
                    <span>Bienvenue, <?php echo htmlspecialchars($_SESSION['utilisateur_nom']); ?></span>
                    <a href="logout.php" class="logout-btn">Déconnexion</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="nav">
            <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-speedometer2"></i> Tableau de bord
            </a>
            <a href="vehicules.php" <?php echo basename($_SERVER['PHP_SELF']) == 'vehicules.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-truck"></i> Véhicules
            </a>
            <a href="kits.php" <?php echo basename($_SERVER['PHP_SELF']) == 'kits.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-box"></i> Kits
            </a>
            <a href="options.php" <?php echo basename($_SERVER['PHP_SELF']) == 'options.php' ? 'class="active"' : ''; ?> >
                <i class="bi bi-gear"></i> Options
            </a>
<?php /*
            <a href="gestion_images.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_images.php' ? 'class="active"' : ''; ?> >
                <i class="bi bi-images"></i> Gestion des Images
            </a>
*/ ?>
            <a href="devis.php" <?php echo basename($_SERVER['PHP_SELF']) == 'devis.php' ? 'class="active"' : ''; ?> >
                <i class="bi bi-file-earmark-text"></i> Devis
            </a>
            <a href="../" target="_blank">
                <i class="bi bi-eye"></i> Voir le site
            </a>
        </div>
    </div>
</body>
</html> 