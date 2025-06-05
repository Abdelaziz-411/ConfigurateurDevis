<?php
session_start();
require 'config.php';

// Récupérer la liste des véhicules
$vehicules = $pdo->query("SELECT * FROM vehicules ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurateur de Véhicule Aménagé</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Configurateur</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['utilisateur_id']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/"><i class="bi bi-gear"></i> Administration</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- En-tête -->
    <div class="bg-light py-5 mb-4">
        <div class="container">
            <h1 class="display-4">Configurez Votre Véhicule Aménagé</h1>
            <p class="lead">Personnalisez votre véhicule selon vos besoins</p>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="container">
        <!-- Étape 1 : Sélection du véhicule -->
        <section id="step-vehicule" class="fade-in-section">
            <div class="container">
                <h2 class="text-center mb-4">Sélectionnez votre véhicule</h2>
                
                <!-- Question sur la possession du véhicule -->
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h3 class="card-title mb-4">Possédez-vous déjà un véhicule ?</h3>
                                <div class="d-flex gap-3 justify-content-center">
                                    <button class="btn btn-outline-primary btn-lg" onclick="showVehicleSelection(true)" id="btnExistingVehicle">
                                        <i class="bi bi-check-circle"></i> Oui
                                    </button>
                                    <button class="btn btn-outline-primary btn-lg" onclick="showVehicleSelection(false)" id="btnNewVehicle">
                                        <i class="bi bi-x-circle"></i> Non
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de sélection du véhicule existant -->
                <div id="existing-vehicle-selection" class="row justify-content-center" style="display: none;">
                     <div class="col-12">
                         <h3 class="mb-3">Choisissez une marque</h3>
                         <div id="marques-list" class="row g-4 mb-4">
                            <!-- Les cartes de marques seront chargées ici -->
                         </div>
                         
                         <h3 class="mb-3" id="modeles-title" style="display: none;">Choisissez un modèle</h3>
                         <div id="modeles-list" class="row g-4 mb-4">
                            <!-- Les cartes de modèles seront chargées ici -->
                         </div>

                         <button class="btn btn-secondary mt-3" onclick="resetVehicleSelection()">Annuler la sélection</button>
                     </div>
                </div>

                <!-- Formulaire de sélection du véhicule personnalisé -->
                <div id="vehicle-selection-form" class="row justify-content-center" style="display: none;">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <form id="vehicleForm" class="needs-validation" novalidate>
                                    <!-- Sélection de la marque -->
                                    <div class="mb-4">
                                        <label for="marque" class="form-label">Marque du véhicule *</label>
                                        <select class="form-select" id="marque" name="marque" required>
                                            <option value="">Sélectionnez une marque</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Veuillez sélectionner une marque
                                        </div>
                                    </div>

                                    <!-- Champ pour marque personnalisée -->
                                    <div id="marque-personnalisee-group" class="mb-4" style="display: none;">
                                        <label for="marque-personnalisee" class="form-label">Précisez la marque *</label>
                                        <input type="text" class="form-control" id="marque-personnalisee" name="marque-personnalisee">
                                        <div class="invalid-feedback">
                                            Veuillez préciser la marque
                                        </div>
                                    </div>

                                    <!-- Sélection du modèle -->
                                    <div class="mb-4">
                                        <label for="modele" class="form-label">Modèle du véhicule *</label>
                                        <select class="form-select" id="modele" name="modele" required>
                                            <option value="">Sélectionnez d'abord une marque</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Veuillez sélectionner un modèle
                                        </div>
                                    </div>

                                    <!-- Champ pour modèle personnalisé -->
                                    <div id="modele-personnalise-group" class="mb-4" style="display: none;">
                                        <label for="modele-personnalise" class="form-label">Précisez le modèle *</label>
                                        <input type="text" class="form-control" id="modele-personnalise" name="modele-personnalise">
                                        <div class="invalid-feedback">
                                            Veuillez préciser le modèle
                                        </div>
                                    </div>

                                    <!-- Année du véhicule -->
                                    <div class="mb-4">
                                        <label for="annee" class="form-label">Année du véhicule</label>
                                        <input type="number" class="form-control" id="annee" name="annee" min="1900" max="2024">
                                        <div class="form-text">Optionnel</div>
                            </div>

                                    <div class="d-flex justify-content-center">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            Valider la sélection
                                </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Étape 2 : Sélection du kit -->
        <section id="step-kit" class="mb-5 fade-in-section">
            <h2 class="mb-4"><i class="bi bi-2-circle"></i> Choisissez votre kit d'aménagement</h2>
            <div id="kit-gallery" class="row g-4">
                <!-- Les kits seront chargés dynamiquement ici -->
            </div>
        </section>

        <!-- Étape 3 : Options supplémentaires -->
        <section id="step-options" class="mb-5 fade-in-section">
            <h2 class="mb-4"><i class="bi bi-3-circle"></i> Personnalisez avec des options</h2>
            <div class="option-container row g-4">
                <!-- Les options seront chargées dynamiquement ici -->
            </div>
        </section>

        <!-- Section récapitulatif -->
        <div id="recap" class="mt-4 fade-in-section">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Récapitulatif</h3>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="prixTTC" checked>
                        <label class="form-check-label" for="prixTTC">Prix TTC</label>
                    </div>
                </div>
                <div class="card-body">
                    <div id="recap-details"></div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-success w-100" id="btnDemandeDevis">
                        Demander un devis
                    </button>
                    <button class="btn btn-secondary w-100 mt-2" id="btnResetConfig">
                        Réinitialiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Formulaire de Contact -->
        <div class="modal fade" id="devisModal" tabindex="-1" aria-labelledby="devisModalLabel" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="devisModalLabel">Demande de devis</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" tabindex="0"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formDevis">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="nom" name="nom" required autocomplete="family-name">
                            </div>
                            <div class="mb-3">
                                <label for="prenom" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" required autocomplete="given-name">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                            </div>
                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone *</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" required autocomplete="tel">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="3" autocomplete="off"></textarea>
                            </div>
                            <p class="text-muted small">* Champs obligatoires</p>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Annuler" tabindex="0">Annuler</button>
                        <button type="button" class="btn btn-primary" id="btnEnvoyerDevis" aria-label="Envoyer la demande" tabindex="0">Envoyer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="text-end">
                <?php if (!isset($_SESSION['utilisateur_id'])): ?>
                    <a href="admin/login.php" class="text-muted text-decoration-none">
                        <i class="bi bi-gear"></i> Espace administration
                    </a>
                <?php else: ?>
                    <a href="admin/logout.php" class="text-muted text-decoration-none">
                        <i class="bi bi-box-arrow-right"></i> Déconnexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS et Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html> 