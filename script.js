// Variables globales
let selectedVehicule = null;
let selectedKit = null;
let selectedOptions = new Set();
let total = 0;
let hasVehicle = null;
let selectedTypeCarrosserie = null; // Nouvelle variable pour stocker le type de carrosserie

// Fonction pour réinitialiser l'interface des kits
function resetKitsUI() {
    const kitGallery = document.getElementById('kit-gallery');
    if (kitGallery) {
        kitGallery.innerHTML = '<div class="col-12 text-center"><p>Chargement des kits...</p></div>';
    }
}

// Fonction pour réinitialiser l'interface des options
function resetOptionsUI() {
    const optionsContainer = document.querySelector('.option-container');
    if (optionsContainer) {
        optionsContainer.innerHTML = '<div class="col-12 text-center"><p>Chargement des options...</p></div>';
    }
}

// Fonction pour charger les kits
async function loadKits(typeCarrosserie) {
    console.log('Chargement des kits pour le type de carrosserie:', typeCarrosserie);
    try {
        const response = await fetch(`get-kits.php?type_carrosserie=${encodeURIComponent(typeCarrosserie)}`);
        if (!response.ok) {
            throw new Error('Erreur lors du chargement des kits');
        }
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Erreur lors du chargement des kits');
        }
        const kits = Array.isArray(data.kits) ? data.kits : [];
        
        const kitGallery = document.getElementById('kit-gallery');
        if (!kitGallery) return;

        if (kits.length === 0) {
            kitGallery.innerHTML = '<div class="col-12 text-center"><p>Aucun kit disponible pour ce type de carrosserie</p></div>';
            return;
        }

        kitGallery.innerHTML = '';
        kits.forEach(kit => {
            const kitCard = createKitCard(kit);
            kitGallery.appendChild(kitCard);
        });
    } catch (error) {
        console.error('Erreur:', error);
        const kitGallery = document.getElementById('kit-gallery');
        if (kitGallery) {
            kitGallery.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Erreur lors du chargement des kits</p></div>';
        }
    }
}

// Fonction pour charger les options
async function loadOptions(typeCarrosserie) {
    try {
        const response = await fetch(`get-options.php?type_carrosserie=${encodeURIComponent(typeCarrosserie)}`);
        if (!response.ok) {
            throw new Error('Erreur lors du chargement des options');
        }
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Erreur lors du chargement des options');
        }
        const options = Array.isArray(data.options) ? data.options : [];
        
        const optionsContainer = document.querySelector('.option-container');
        if (!optionsContainer) return;

        if (options.length === 0) {
            optionsContainer.innerHTML = '<div class="col-12 text-center"><p>Aucune option disponible pour ce type de carrosserie</p></div>';
            return;
        }

        // Grouper les options par catégorie
        const optionsByCategory = options.reduce((acc, option) => {
            const category = option.categorie_nom || 'Autres';
            if (!acc[category]) {
                acc[category] = [];
            }
            acc[category].push(option);
            return acc;
        }, {});

        // Créer l'interface pour chaque catégorie
        optionsContainer.innerHTML = '';
        Object.entries(optionsByCategory).forEach(([category, categoryOptions]) => {
            const categorySection = document.createElement('div');
            categorySection.className = 'col-12 mb-4';
            categorySection.innerHTML = `
                <div class="category-section p-3 bg-light rounded">
                    <h3 class="h4 mb-3">${category}</h3>
                    <div class="row g-4">
                        ${categoryOptions.map(option => createOptionCard(option)).join('')}
                    </div>
                </div>
            `;
            optionsContainer.appendChild(categorySection);
        });
    } catch (error) {
        console.error('Erreur:', error);
        const optionsContainer = document.querySelector('.option-container');
        if (optionsContainer) {
            optionsContainer.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Erreur lors du chargement des options</p></div>';
        }
    }
}

// Fonction pour créer une carte d'option
function createOptionCard(option) {
    return `
        <div class="col-md-4">
            <div class="card option-card h-100">
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="option${option.id}" 
                               value="${option.id}" data-prix="${option.prix}">
                        <label class="form-check-label" for="option${option.id}">
                            <h5 class="card-title">${option.nom}</h5>
                            <p class="card-text">${option.description}</p>
                            <strong class="text-primary">${formatPrix(option.prix)}</strong>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Fonction pour créer une carte de kit
function createKitCard(kit) {
    const col = document.createElement('div');
    col.className = 'col-md-4 mb-4';
    
    const card = document.createElement('div');
    card.className = 'card kit-card h-100';
    card.dataset.id = kit.id;
    
    let imagesHtml = '';
    if (kit.images && kit.images.length > 0) {
        imagesHtml = `
            <div id="kitCarousel${kit.id}" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    ${kit.images.map((image, index) => `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}">
                            <img src="images/kits/${image}" class="d-block w-100" alt="${kit.nom}">
                        </div>
                    `).join('')}
                </div>
                ${kit.images.length > 1 ? `
                    <button class="carousel-control-prev" type="button" data-bs-target="#kitCarousel${kit.id}" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Précédent</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#kitCarousel${kit.id}" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Suivant</span>
                    </button>
                ` : ''}
            </div>
        `;
    } else {
        imagesHtml = `
            <div class="no-image-placeholder">
                <i class="bi bi-image"></i>
                <p>Aucune image disponible</p>
            </div>
        `;
    }

    card.innerHTML = `
        ${imagesHtml}
        <div class="card-body">
            <h5 class="card-title">${kit.nom}</h5>
            <p class="card-text description-preview">${kit.description}</p>
            <div class="d-flex justify-content-between align-items-center">
                <span class="h5 mb-0">${formatPrix(kit.prix)}</span>
                <button class="btn btn-primary" onclick="selectKit(${kit.id}, ${kit.prix})">
                    Sélectionner
                </button>
            </div>
        </div>
    `;

    col.appendChild(card);
    return col;
}

// Fonction pour valider la sélection du véhicule
function validerSelectionVehicule() {
    const marque = document.getElementById('marque').value;
    const modele = document.getElementById('modele').value;
    const annee = document.getElementById('annee').value;

    // Construire le nom du véhicule
    let vehiculeNom = '';
    let typeCarrosserie = '';
    
    // Récupérer le nom de la marque et le type de carrosserie
    if (marque === 'autre') {
        vehiculeNom = document.getElementById('marque-personnalisee').value;
        typeCarrosserie = document.getElementById('type-carrosserie').value;
    } else {
        const marqueCard = document.querySelector(`.marque-card[data-id="${marque}"]`);
        if (marqueCard) {
            vehiculeNom = marqueCard.querySelector('.card-title').textContent;
        }
        
        const modeleCard = document.querySelector(`.modele-card[data-id="${modele}"]`);
        if (modeleCard) {
            typeCarrosserie = modeleCard.dataset.typeCarrosserie || '';
        }
    }

    if (!typeCarrosserie) {
        alert('Veuillez sélectionner un type de carrosserie valide');
        return;
    }

    // Stocker le type de carrosserie sélectionné
    selectedTypeCarrosserie = typeCarrosserie;

    // Mettre à jour l'interface
    document.getElementById('vehicle-selection-form').style.display = 'none';
    document.getElementById('annee-container').style.display = 'block';
    
    // Charger les kits et options avec le type de carrosserie
    loadKits(typeCarrosserie);
    loadOptions(typeCarrosserie);
}

// Fonction pour afficher le formulaire de sélection du véhicule
function showVehicleSelection(hasVehicleValue) {
    hasVehicle = hasVehicleValue;
    document.getElementById('vehicle-selection-form').style.display = 'none';
    document.getElementById('existing-vehicle-selection').style.display = 'block';
    loadMarques();
}

// Charger les marques depuis le serveur
async function loadMarques() {
    try {
        const response = await fetch('get-marques-modeles.php?action=get_marques');
        const marques = await response.json();
        
        const marquesList = document.getElementById('marques-list');
        marquesList.innerHTML = '';
        
        marques.forEach(marque => {
            const col = document.createElement('div');
            col.className = 'col-md-4 col-lg-3 mb-4';
            
            const card = document.createElement('div');
            card.className = 'card h-100 marque-card';
            card.style.cursor = 'pointer';
            card.dataset.marqueId = marque.id;
            
            // Ajouter l'image de la marque
            const imgContainer = document.createElement('div');
            imgContainer.className = 'card-img-container';
            imgContainer.style.height = '200px';
            imgContainer.style.overflow = 'hidden';
            imgContainer.style.display = 'flex';
            imgContainer.style.alignItems = 'center';
            imgContainer.style.justifyContent = 'center';
            imgContainer.style.backgroundColor = '#f8f9fa';
            
            const img = document.createElement('img');
            img.className = 'card-img-top';
            img.style.objectFit = 'contain';
            img.style.height = '100%';
            img.style.width = '100%';
            img.style.padding = '1rem';
            
            if (marque.images && marque.images.length > 0) {
                img.src = marque.images[0];
            } else {
                img.src = 'images/marques/autre-marque.png';
            }
            
            imgContainer.appendChild(img);
            
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body text-center';
            
            const title = document.createElement('h5');
            title.className = 'card-title';
            title.textContent = marque.nom;
            
            cardBody.appendChild(title);
            card.appendChild(imgContainer);
            card.appendChild(cardBody);
            col.appendChild(card);
            marquesList.appendChild(col);
            
            // Ajouter l'événement de clic sur la carte
            card.addEventListener('click', function() {
                const idMarque = this.dataset.marqueId;
                if (idMarque) {
                    loadModeles(idMarque);
                    document.getElementById('modeles-title').style.display = 'block';
                    document.getElementById('marques-list').style.display = 'none';
                }
            });
        });
        
        // Ajouter une carte "Autre"
        const col = document.createElement('div');
        col.className = 'col-md-4 col-lg-3 mb-4';
        
        const card = document.createElement('div');
        card.className = 'card h-100 marque-card';
        card.style.cursor = 'pointer';
        card.dataset.marqueId = 'autre';
        
        const imgContainer = document.createElement('div');
        imgContainer.className = 'card-img-container';
        imgContainer.style.height = '200px';
        imgContainer.style.overflow = 'hidden';
        imgContainer.style.display = 'flex';
        imgContainer.style.alignItems = 'center';
        imgContainer.style.justifyContent = 'center';
        imgContainer.style.backgroundColor = '#f8f9fa';
        
        const img = document.createElement('img');
        img.className = 'card-img-top';
        img.style.objectFit = 'contain';
        img.style.height = '100%';
        img.style.width = '100%';
        img.style.padding = '1rem';
        img.src = 'images/marques/autre-marque.png';
        
        imgContainer.appendChild(img);
        
        const cardBody = document.createElement('div');
        cardBody.className = 'card-body text-center';
        
        const title = document.createElement('h5');
        title.className = 'card-title';
        title.textContent = 'Autre';
        
        cardBody.appendChild(title);
        card.appendChild(imgContainer);
        card.appendChild(cardBody);
        col.appendChild(card);
        marquesList.appendChild(col);
        
        // Ajouter l'événement de clic sur la carte "Autre"
        card.addEventListener('click', function() {
            document.getElementById('marque-personnalisee-group').style.display = 'block';
            document.getElementById('marques-list').style.display = 'none';
            document.getElementById('modeles-title').style.display = 'none';
        });
        
    } catch (error) {
        console.error('Erreur lors du chargement des marques:', error);
    }
}

// Charger les modèles en fonction de la marque sélectionnée
async function loadModeles(idMarque) {
    try {
        const response = await fetch(`get-marques-modeles.php?action=get_modeles&id_marque=${idMarque}`);
        const modeles = await response.json();
        
        const modelesList = document.getElementById('modeles-list');
        modelesList.innerHTML = '';
        
        modeles.forEach(modele => {
            const col = document.createElement('div');
            col.className = 'col-md-4 col-lg-3 mb-4';
            
            const card = document.createElement('div');
            card.className = 'card h-100 modele-card';
            card.style.cursor = 'pointer';
            card.dataset.modeleId = modele.id;
            
            // Ajouter l'image du modèle
            const imgContainer = document.createElement('div');
            imgContainer.className = 'card-img-container';
            imgContainer.style.height = '200px';
            imgContainer.style.overflow = 'hidden';
            imgContainer.style.display = 'flex';
            imgContainer.style.alignItems = 'center';
            imgContainer.style.justifyContent = 'center';
            imgContainer.style.backgroundColor = '#f8f9fa';
            
            const img = document.createElement('img');
            img.className = 'card-img-top';
            img.style.objectFit = 'cover';
            img.style.height = '100%';
            img.style.width = '100%';
            
            if (modele.images && modele.images.length > 0) {
                img.src = modele.images[0];
            } else {
                img.src = 'images/modeles/autre-modele.png';
            }
            
            imgContainer.appendChild(img);
            
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body text-center';
            
            const title = document.createElement('h5');
            title.className = 'card-title';
            title.textContent = modele.nom;
            
            if (modele.status) {
                const status = document.createElement('span');
                status.className = 'badge bg-primary ms-2';
                status.textContent = modele.status;
                title.appendChild(status);
            }
            
            cardBody.appendChild(title);
            card.appendChild(imgContainer);
            card.appendChild(cardBody);
            col.appendChild(card);
            modelesList.appendChild(col);
            
            // Ajouter l'événement de clic sur la carte
            card.addEventListener('click', function() {
                const idModele = this.dataset.modeleId;
                if (idModele) {
                    document.getElementById('modele').value = idModele;
                    document.getElementById('modeles-list').style.display = 'none';
                    document.getElementById('modeles-title').style.display = 'none';
                    
                    // Créer et afficher le champ d'année
                    const anneeContainer = document.createElement('div');
                    anneeContainer.className = 'col-md-4 mx-auto mt-4';
                    anneeContainer.innerHTML = `
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Année du véhicule</h5>
                                <div class="mb-3">
                                    <input type="number" class="form-control" id="annee" min="1900" max="2024" placeholder="Année du véhicule">
                                    <div class="form-text">Optionnel</div>
                                </div>
                                <button class="btn btn-primary w-100" onclick="validerSelectionVehicule()">
                                    Valider la sélection
                                </button>
                            </div>
                        </div>
                    `;
                    document.getElementById('existing-vehicle-selection').appendChild(anneeContainer);
                }
            });
        });
        
        // Ajouter une carte "Autre"
        const col = document.createElement('div');
        col.className = 'col-md-4 col-lg-3 mb-4';
        
        const card = document.createElement('div');
        card.className = 'card h-100 modele-card';
        card.style.cursor = 'pointer';
        card.dataset.modeleId = 'autre';
        
        const imgContainer = document.createElement('div');
        imgContainer.className = 'card-img-container';
        imgContainer.style.height = '200px';
        imgContainer.style.overflow = 'hidden';
        imgContainer.style.display = 'flex';
        imgContainer.style.alignItems = 'center';
        imgContainer.style.justifyContent = 'center';
        imgContainer.style.backgroundColor = '#f8f9fa';
        
        const img = document.createElement('img');
        img.className = 'card-img-top';
        img.style.objectFit = 'contain';
        img.style.height = '100%';
        img.style.width = '100%';
        img.style.padding = '1rem';
        img.src = 'images/modeles/autre-modele.png';
        
        imgContainer.appendChild(img);
        
        const cardBody = document.createElement('div');
        cardBody.className = 'card-body text-center';
        
        const title = document.createElement('h5');
        title.className = 'card-title';
        title.textContent = 'Autre';
        
        cardBody.appendChild(title);
        card.appendChild(imgContainer);
        card.appendChild(cardBody);
        col.appendChild(card);
        modelesList.appendChild(col);
        
        // Ajouter l'événement de clic sur la carte "Autre"
        card.addEventListener('click', function() {
            document.getElementById('modele-personnalise-group').style.display = 'block';
            document.getElementById('modeles-list').style.display = 'none';
            document.getElementById('modeles-title').style.display = 'none';
        });
        
    } catch (error) {
        console.error('Erreur lors du chargement des modèles:', error);
    }
}

// Fonction pour afficher les images d'un modèle
function displayModeleImages(images) {
    const modelesList = document.getElementById('modeles-list');
    if (!modelesList) return;

    modelesList.innerHTML = '';
    if (images && images.length > 0) {
        const row = document.createElement('div');
        row.className = 'row g-4';
        
        images.forEach(image => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            
            const card = document.createElement('div');
            card.className = 'card h-100';
            
            const img = document.createElement('img');
            img.src = image;
            img.className = 'card-img-top';
            img.style.height = '200px';
            img.style.objectFit = 'cover';
            
            card.appendChild(img);
            col.appendChild(card);
            row.appendChild(col);
        });
        
        modelesList.appendChild(row);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire de soumission du formulaire de sélection du véhicule
    const vehicleForm = document.getElementById('vehicleForm');
    if (vehicleForm) {
        vehicleForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Vérifier la validité du formulaire
            if (!this.checkValidity()) {
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            // Récupérer les valeurs du formulaire
            const marque = document.getElementById('marque').value;
            const marquePersonnalisee = document.getElementById('marque-personnalisee').value;
            const modele = document.getElementById('modele').value;
            const modelePersonnalise = document.getElementById('modele-personnalise').value;
            const annee = document.getElementById('annee').value;

            // Construire le nom du véhicule
            let vehiculeNom = '';
            if (marque === 'autre') {
                vehiculeNom = marquePersonnalisee;
            } else {
                const marqueSelect = document.getElementById('marque');
                vehiculeNom = marqueSelect.options[marqueSelect.selectedIndex].text;
            }

            if (modele === 'autre') {
                vehiculeNom += ' ' + modelePersonnalise;
            } else {
                const modeleSelect = document.getElementById('modele');
                vehiculeNom += ' ' + modeleSelect.options[modeleSelect.selectedIndex].text;
            }

            if (annee) {
                vehiculeNom += ' (' + annee + ')';
            }

            // Créer un nouvel élément de carte de véhicule
            const vehiculeSection = document.getElementById('step-vehicule');
            const vehiculeContainer = vehiculeSection.querySelector('.row') || document.createElement('div');
            if (!vehiculeContainer.classList.contains('row')) {
                vehiculeContainer.className = 'row g-4 mt-4';
                vehiculeSection.appendChild(vehiculeContainer);
            }

            // Supprimer toutes les anciennes cartes de véhicules personnalisés
            vehiculeContainer.querySelectorAll('.vehicule-card[data-id^="custom_"]').forEach(card => {
                card.closest('.col-md-4').remove();
            });

            const vehiculeId = 'custom_' + Date.now();
            const vehiculeCard = document.createElement('div');
            vehiculeCard.className = 'col-md-4 mb-4';
            vehiculeCard.innerHTML = `
                <div class="card vehicule-card" data-id="${vehiculeId}">
                    <div class="card-body">
                        <h5 class="card-title">${vehiculeNom}</h5>
                        <p class="card-text">Véhicule personnalisé</p>
                        ${modele.status ? `<p class="card-text"><small class="text-muted">Statut: ${modele.status}</small></p>` : ''}
                    </div>
                </div>
            `;

            // Ajouter la carte à la section des véhicules
            vehiculeContainer.appendChild(vehiculeCard);

            // Réinitialiser les sélections précédentes
            selectedKit = null;
            kitPrix = 0;
            selectedOptions = new Set();
            total = 0;

            // Mettre à jour le véhicule sélectionné
            selectedVehicule = {
                id: vehiculeId,
                nom: vehiculeNom,
                status: modele.status
            };

            // Mettre à jour l'interface
            document.querySelectorAll('.vehicule-card').forEach(c => c.classList.remove('border-primary'));
            vehiculeCard.querySelector('.vehicule-card').classList.add('border-primary');

            // Réinitialiser l'interface des kits et options
            resetKitsUI();
            resetOptionsUI();

            // Afficher les sections
            document.getElementById('step-kit').classList.add('is-visible');
            document.getElementById('step-options').classList.add('is-visible');

            // Charger les kits et options pour ce véhicule
            loadKits(selectedTypeCarrosserie);
            loadOptions(selectedTypeCarrosserie);

            // Mettre à jour le récapitulatif
            updateRecap();

            // Sauvegarder la configuration
            saveConfiguration();

            // Cacher le formulaire
            document.getElementById('vehicle-selection-form').style.display = 'none';

            // Faire défiler jusqu'à la section des kits
            document.getElementById('step-kit').scrollIntoView({ behavior: 'smooth' });
        });
    }

    const vehiculeSelect = document.getElementById('vehicule');
    const kitSelect = document.getElementById('kit');
    const optionsContainer = document.querySelector('.option-container');
    const totalSpan = document.getElementById('total-price');
    const galleryDiv = document.getElementById('kit-gallery');
    const recapDiv = document.getElementById('recap');
    const vehiculeCards = document.querySelectorAll('.vehicule-card');
    const prixTTCSwitch = document.getElementById('prixTTC');

    let kitPrix = 0;
    const TVA = 0.20; // TVA à 20%

    // Rendre les variables et fonctions accessibles globalement
    window.kitPrix = kitPrix;
    window.selectedVehicule = selectedVehicule;
    window.selectedKit = selectedKit;
    window.selectedOptions = selectedOptions;
    window.total = total;
    window.TVA = TVA;
    window.selectKit = selectKit; // Rendre selectKit accessible globalement


    // Fonctions de sauvegarde de la configuration
    function saveConfiguration() {
        const config = {
            vehicule: selectedVehicule,
            kit: selectedKit,
            options: Array.from(selectedOptions),
            hasVehicle: hasVehicle
        };
        localStorage.setItem('configurateur-state', JSON.stringify(config));
    }
    window.saveConfiguration = saveConfiguration;

    // Fonction pour sélectionner un kit
    function selectKit(kitId, prix) {
        console.log('Kit sélectionné:', { id: kitId, prix: prix });

        // Si le kit cliqué est déjà sélectionné, on le désélectionne
        if (selectedKit && selectedKit.id === kitId) {
            document.querySelectorAll('.kit-card').forEach(card => {
                card.classList.remove('border-primary');
            });
            selectedKit = null;
            kitPrix = 0;
            updateRecap();
            saveConfiguration();
            console.log('Kit désélectionné. selectedKit est maintenant :', selectedKit);
            return;
        }

        // Désélectionner tous les kits
        document.querySelectorAll('.kit-card').forEach(card => {
            card.classList.remove('border-primary');
        });

        // Sélectionner le kit choisi
        const selectedCard = document.querySelector(`.kit-card[data-id="${kitId}"]`);
        if (selectedCard) {
            console.log('Carte du kit trouvée, mise à jour de l\'interface');
            selectedCard.classList.add('border-primary');
            selectedKit = {
                id: kitId,
                prix: prix
            };
            kitPrix = prix;
            updateRecap();
            saveConfiguration();
        } else {
            console.error('Carte du kit non trouvée pour l\'ID:', kitId);
        }
    }

    // Fonction de restauration de la configuration
    async function loadConfiguration() {
        const savedConfig = localStorage.getItem('configurateur-state');
        if (savedConfig) {
            try {
                const config = JSON.parse(savedConfig);
                
                // Restaurer l'information sur la possession du véhicule
                if (config.hasVehicle !== undefined) {
                    hasVehicle = config.hasVehicle;
                }
                
                // Restaurer la sélection du véhicule
                if (config.vehicule) {
                    const vehiculeCard = document.querySelector(`.vehicule-card[data-id="${config.vehicule}"]`);
                    if (vehiculeCard) {
                        selectedVehicule = config.vehicule;
                        vehiculeCards.forEach(c => c.classList.remove('border-primary'));
                        vehiculeCard.classList.add('border-primary');
                        
                        // Réinitialiser les sélections précédentes
                        selectedKit = null;
                        kitPrix = 0;
                        selectedOptions = new Set();
                        
                        // Afficher les sections
                        document.getElementById('step-kit').classList.add('is-visible');
                        document.getElementById('step-options').classList.add('is-visible');
                        
                        // Charger les kits et options pour ce véhicule
                        await loadKits(selectedTypeCarrosserie);
                        await loadOptions(selectedTypeCarrosserie);
                        
                        // Une fois que les kits et options sont chargés, on peut restaurer les sélections
                        if (config.kit) {
                            const kitCard = document.querySelector(`.kit-card[data-id="${config.kit.id}"]`);
                            if (kitCard) {
                                // Utiliser directement la fonction selectKit si elle est chargée
                                if (typeof selectKit === 'function') {
                                     selectKit(config.kit.id, config.kit.prix);
                                } else {
                                    console.error('La fonction selectKit n\'est pas encore disponible.');
                                }
                            }
                        }
                        
                        if (config.options && Array.isArray(config.options)) {
                            config.options.forEach(savedOption => {
                                const optionCard = document.querySelector(`.option-card[data-id="${savedOption.id}"]`);
                                if (optionCard) {
                                    const checkbox = optionCard.querySelector('.option-checkbox');
                                    if (checkbox) {
                                        checkbox.checked = true;
                                        // Déclencher l'événement change manuellement
                                        const event = new Event('change');
                                        checkbox.dispatchEvent(event);
                                    }
                                }
                            });
                        }
                        
                        // Mettre à jour l'affichage
                        updateRecap();
                        updateTotal();
                    } else {
                        // Si le véhicule sauvegardé n'existe plus, on efface la sauvegarde
                        localStorage.removeItem('configurateur-state');
                    }
                }
            } catch (error) {
                console.error('Erreur lors du chargement de la configuration:', error);
                // En cas d'erreur, on efface la sauvegarde corrompue
                localStorage.removeItem('configurateur-state');
            }
        }
    }

    // Charger la configuration sauvegardée au chargement de la page
    loadConfiguration();

    // Fonction pour calculer le prix TTC
    function calculerPrixTTC(prixHT) {
        return prixHT * (1 + TVA);
    }

    // Fonction pour formater le prix
    function formatPrix(prix, isTTC = true) {
        const valeur = isTTC ? calculerPrixTTC(prix) : prix;
        return `${valeur.toFixed(2).replace('.', ',')} € ${isTTC ? 'TTC' : 'HT'}`;
    }


    // Écouter le changement de switch HT/TTC
    prixTTCSwitch.addEventListener('change', function() {
        updateRecap();
    });

    // Fonction pour mettre à jour le récapitulatif
    function updateRecap() {
        const recapDetails = document.getElementById('recap-details');
        if (!recapDetails) return;

        const isTTC = prixTTCSwitch && prixTTCSwitch.checked;
        let html = '';
        total = 0;

        try {
            // Réinitialiser le récapitulatif
            html = '';

            // Ajouter l'information sur la possession du véhicule
            if (hasVehicle !== null) {
                html += `
                    <div class="recap-section">
                        <h4>Possession du véhicule</h4>
                        <p>${hasVehicle ? 'Oui' : 'Non'}</p>
                    </div>
                `;
            }

        // Ajouter le véhicule
        if (selectedVehicule) {
            const vehiculeCard = document.querySelector(`.vehicule-card[data-id="${selectedVehicule}"]`);
                if (vehiculeCard) {
                    const vehiculeNom = vehiculeCard.querySelector('.card-title')?.textContent || 'Véhicule sélectionné';
            html += `
                <div class="recap-section">
                    <h4>Véhicule</h4>
                            <p>${vehiculeNom}${selectedVehicule.status ? ` (${selectedVehicule.status})` : ''}</p>
                </div>
            `;
                }
        }

            // Ajouter le kit seulement s'il est sélectionné et valide
            if (selectedKit && selectedKit.id && selectedKit.prix !== undefined && selectedKit.prix !== null) {
            const prixHT = parseFloat(selectedKit.prix);
                if (!isNaN(prixHT)) {
            total += prixHT;
            const kitCard = document.querySelector(`.kit-card[data-id="${selectedKit.id}"]`);
                    const kitNom = kitCard ? kitCard.querySelector('.card-title')?.textContent : 'Kit sélectionné';
            html += `
                <div class="recap-section">
                    <h4>Kit d'aménagement</h4>
                    <p>${kitNom} - ${formatPrix(prixHT, isTTC)}</p>
                </div>
            `;
        }
            }

            // Ajouter les options seulement si elles sont valides
            const validOptions = Array.from(selectedOptions).filter(option => 
                option && 
                option.id && 
                option.prix !== undefined && option.prix !== null &&
                !isNaN(parseFloat(option.prix))
            );


            if (validOptions.length > 0) {
            html += `
                <div class="recap-section">
                    <h4>Options sélectionnées</h4>
                    <ul class="list-unstyled">
            `;
            
                validOptions.forEach(option => {
                const prixHT = parseFloat(option.prix);
                total += prixHT;
                html += `
                        <li>${option.nom || 'Option'} - ${formatPrix(prixHT, isTTC)}</li>
                `;
            });
            
            html += `
                    </ul>
                </div>
            `;
        }

            // Ajouter le total seulement s'il est valide
            if (!isNaN(total) && total >= 0) {
        const totalTTC = isTTC ? calculerPrixTTC(total) : total;
        html += `
            <div class="recap-section border-top pt-3 mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Total ${isTTC ? 'TTC' : 'HT'}</h4>
                                    <p class="h3 mb-0">${totalTTC.toFixed(2).replace('.', ',')} €</p>
                </div>
                ${isTTC ? `
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">Total HT</small>
                                    <small class="text-muted">${total.toFixed(2).replace('.', ',')} €</small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">TVA (20%)</small>
                                    <small class="text-muted">${(totalTTC - total).toFixed(2).replace('.', ',')} €</small>
                </div>
                ` : ''}
            </div>
        `;
            }

        recapDetails.innerHTML = html;
            const recapElement = document.getElementById('recap');
            if (recapElement) {
                if (selectedVehicule) {
                    recapElement.classList.add('is-visible');
                } else {
                    recapElement.classList.remove('is-visible');
                }
            }
        } catch (error) {
            console.error('Erreur dans updateRecap:', error);
            recapDetails.innerHTML = `
                <div class="alert alert-danger">
                    Une erreur est survenue lors de la mise à jour du récapitulatif. 
                    Veuillez rafraîchir la page.
                </div>
            `;
        }
    }
    window.updateRecap = updateRecap;

    // Fonction pour mettre à jour le total
    function updateTotal() {
        // Au lieu de mettre à jour un élément total-price qui n'existe pas,
        // on met simplement à jour le récapitulatif qui contient déjà le total
        updateRecap();
    }

    // Gestion des cartes de véhicules
    vehiculeCards.forEach(card => {
        card.addEventListener('click', async () => {
            const vehiculeId = card.dataset.id;
            
            // Si on change de véhicule, on réinitialise tout
            if (selectedVehicule !== vehiculeId) {
                // Supprimer la configuration précédente du localStorage
                localStorage.removeItem('configurateur-state');
                
                // Réinitialiser complètement l'état
                selectedKit = null;
                kitPrix = 0;
                selectedOptions = new Set();
                
                // Mettre à jour le véhicule sélectionné
            selectedVehicule = {
                id: vehiculeId,
                nom: vehiculeNom,
                status: modele.status
            };
            
            // Mettre à jour l'interface
            vehiculeCards.forEach(c => c.classList.remove('border-primary'));
            card.classList.add('border-primary');
            
                // Réinitialiser l'interface des kits et options
                resetKitsUI();
                resetOptionsUI();
                
                // Forcer la réinitialisation du récapitulatif
                const recapDetails = document.getElementById('recap-details');
                if (recapDetails) {
                    recapDetails.innerHTML = `
                        <div class="recap-section">
                            <h4>Véhicule</h4>
                            <p>${card.querySelector('.card-title')?.textContent || 'Véhicule sélectionné'}</p>
                        </div>
                    `;
                }
                
                // Réinitialiser le total
                total = 0;
                updateTotal();
                
                try {
                    // Charger les nouveaux kits et options
                    await loadKits(selectedTypeCarrosserie);
                    await loadOptions(selectedTypeCarrosserie);
            
            // Afficher les sections
            document.getElementById('step-kit').classList.add('is-visible');
            document.getElementById('step-options').classList.add('is-visible');
            document.getElementById('step-kit').scrollIntoView({ behavior: 'smooth' });
            
                    // Sauvegarder la nouvelle configuration
                    saveConfiguration();
                } catch (error) {
                    console.error('Erreur lors du chargement des données:', error);
                    alert('Une erreur est survenue lors du chargement des données. Veuillez réessayer.');
                }
            }
        });
    });

    // Fonction pour réinitialiser l'interface des kits
    function resetKitsUI() {
        const kitGallery = document.getElementById('kit-gallery');
        if (kitGallery) {
            kitGallery.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
        }
    }

    // Fonction pour réinitialiser l'interface des options
    function resetOptionsUI() {
        const optionsContainer = document.querySelector('.option-container');
        if (optionsContainer) {
            optionsContainer.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
        }
    }

    // Gestion du formulaire de devis
    const btnDemandeDevis = document.getElementById('btnDemandeDevis');
    const devisModal = new bootstrap.Modal(document.getElementById('devisModal'), {
        keyboard: true,
        backdrop: 'static'
    });
    const btnEnvoyerDevis = document.getElementById('btnEnvoyerDevis');

    // Ajouter un écouteur pour la fermeture du modal
    devisModal._element.addEventListener('hidden.bs.modal', function (e) {
        // Réinitialiser le formulaire
        document.getElementById('formDevis').reset();
    });
    const formDevis = document.getElementById('formDevis');

    btnDemandeDevis.addEventListener('click', () => {
        if (!selectedVehicule) {
            alert('Veuillez sélectionner un véhicule');
            return;
        }
        devisModal.show();
    });

    // Met le focus sur le champ "Nom" à l'ouverture du modal (si le champ existe)
    document.getElementById('devisModal').addEventListener('shown.bs.modal', function () {
        const nomInput = document.getElementById('nom');
        if (nomInput) nomInput.focus();
    });


    btnEnvoyerDevis.addEventListener('click', async () => {
        if (!formDevis.checkValidity()) {
            formDevis.reportValidity();
            return;
        }

        const formData = {
            nom: document.getElementById('nom')?.value || '',
            prenom: document.getElementById('prenom')?.value || '',
            email: document.getElementById('email')?.value || '',
            telephone: document.getElementById('telephone')?.value || '',
            message: document.getElementById('message')?.value || '',
            vehicule_id: selectedVehicule,
            kit_id: selectedKit ? selectedKit.id : null,
            configuration: document.getElementById('recap-details')?.innerText || '',
            prix_ht: total,
            prix_ttc: calculerPrixTTC(total)
        };

        try {
            btnEnvoyerDevis.disabled = true;
            btnEnvoyerDevis.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Envoi en cours...';

            const response = await fetch('save-devis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                // Toujours recréer l'instance du modal pour garantir la fermeture
                const modalElement = document.getElementById('devisModal');
                const modalInstance = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modalInstance.hide();
                formDevis.reset();
                alert('Votre demande de devis a été envoyée avec succès. Nous vous contacterons prochainement.');
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            alert('Erreur lors de l\'envoi du devis : ' + error.message);
        } finally {
            btnEnvoyerDevis.disabled = false;
            btnEnvoyerDevis.textContent = 'Envoyer';
        }
    });

    // Gestion du bouton de réinitialisation
    const btnResetConfig = document.getElementById('btnResetConfig');
    if (btnResetConfig) {
        btnResetConfig.addEventListener('click', () => {
            // Réinitialiser les variables d'état
            selectedVehicule = null;
            selectedKit = null;
            kitPrix = 0;
            selectedOptions = new Set();
            total = 0;

            // Réinitialiser l'interface
            vehiculeCards.forEach(card => card.classList.remove('border-primary'));
            const kitGallery = document.getElementById('kit-gallery');
            if (kitGallery) kitGallery.innerHTML = ''; // Vider la galerie de kits
            const optionsContainer = document.querySelector('.option-container');
            if (optionsContainer) optionsContainer.innerHTML = ''; // Vider le conteneur d'options

            // Cacher les sections Kit et Options
            document.getElementById('step-kit').classList.remove('is-visible');
            document.getElementById('step-options').classList.remove('is-visible');
            // document.getElementById('recap').style.display = 'none'; // Cacher le récapitulatif

            // Réinitialiser et cacher le récapitulatif
            const recapDetails = document.getElementById('recap-details');
            if (recapDetails) recapDetails.innerHTML = '';

            // Supprimer l'état sauvegardé dans le localStorage
            localStorage.removeItem('configurateur-state');

            // Remonter en haut de la page ou vers la section véhicule
            document.getElementById('step-vehicule').scrollIntoView({ behavior: 'smooth' });

            console.log('Configuration réinitialisée.');
        });
    }

    // Rendre la section véhicule visible au chargement de la page
    const vehiculeSection = document.getElementById('step-vehicule');
    if (vehiculeSection) {
        vehiculeSection.classList.add('is-visible');
    }

    function resetVehicleSelection() {
        document.getElementById('existing-vehicle-selection').style.display = 'none';
        document.getElementById('vehicle-selection-form').style.display = 'none';
        document.getElementById('marques-list').style.display = 'block';
        document.getElementById('modeles-list').style.display = 'none';
        document.getElementById('modeles-title').style.display = 'none';
        document.getElementById('marque-personnalisee-group').style.display = 'none';
        document.getElementById('modele-personnalise-group').style.display = 'none';
        loadMarques();
    }
});