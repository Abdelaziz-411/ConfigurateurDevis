// Variables globales
let selectedVehicule = null;
let selectedKit = null;
let selectedOptions = new Set();
let total = 0;
let hasVehicle = null;
let selectedTypeCarrosserie = null; // Nouvelle variable pour stocker le type de carrosserie
let kitPrix = 0;
const TVA = 0.20; // TVA à 20%
let selectedMarqueId = null; // Nouvelle variable pour stocker l'ID de la marque sélectionnée

// Fonction pour calculer le prix HT à partir du prix TTC
function calculerPrixHT(prixTTC) {
    return parseFloat(prixTTC) / (1 + TVA); // Conversion TTC vers HT
}

// Fonction pour formater le prix
function formatPrix(prix, isTTC = true) {
    // S'assurer que prix est un nombre
    const prixNum = parseFloat(prix) || 0;
    const valeur = isTTC ? prixNum : calculerPrixHT(prixNum);
    return `${valeur.toFixed(2).replace('.', ',')} € ${isTTC ? 'TTC' : 'HT'}`;
}

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
async function loadKits() {
    if (!selectedModeleId || !selectedTypeCarrosserie) {
        console.error('Modèle ou type de carrosserie non sélectionné');
        return;
    }

    try {
        const response = await fetch(`get-kits.php?type_carrosserie=${selectedTypeCarrosserie}`);
        
        if (!response.ok) {
            const errorData = await response.json(); 
            console.error('Erreur du serveur lors du chargement des kits:', errorData);
            throw new Error('Erreur du serveur: ' + (errorData.error || response.statusText));
        }

        const kits = await response.json();
        console.log('Kits reçus du serveur:', kits);
        
        const kitGallery = document.getElementById('kit-gallery');
        kitGallery.innerHTML = '';
        
        kits.forEach(kit => {
            const col = document.createElement('div');
            col.className = 'col-md-4 col-lg-3 mb-4';
            
            const card = document.createElement('div');
            card.className = 'card h-100 kit-card';
            card.dataset.kitId = kit.id;
            
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
            
            if (kit.images && kit.images.length > 0) {
                img.src = kit.images[0];
            } else {
                img.src = 'images/kits/default-kit.png';
        }

            imgContainer.appendChild(img);
            
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body text-center';
            
            const title = document.createElement('h5');
            title.className = 'card-title';
            title.textContent = kit.nom;
            
            const price = document.createElement('p');
            price.className = 'card-text';
            price.textContent = `Prix HT : ${formatPrix(kit.prix, false)}`;
            
            cardBody.appendChild(title);
            cardBody.appendChild(price);
            card.appendChild(imgContainer);
            card.appendChild(cardBody);
            col.appendChild(card);
            kitGallery.appendChild(col);
            
            card.addEventListener('click', function() {
                const kitId = this.dataset.kitId;
                if (kitId) {
                    selectedKit = kit;
                    kitPrix = parseFloat(kit.prix);
                    updateRecap();
                }
            });
        });
    } catch (error) {
        console.error('Erreur lors du chargement des kits:', error);
    }
}

// Fonction pour charger les options
async function loadOptions() {
    if (!selectedModeleId || !selectedTypeCarrosserie) {
        console.error('Modèle ou type de carrosserie non sélectionné');
        return;
    }

    try {
        const optionsContainer = document.getElementById('options-container');
        if (!optionsContainer) {
            console.warn('Élément #options-container non trouvé.');
            return;
        }

        // Vider le conteneur
        optionsContainer.innerHTML = '';

        // Récupérer les options depuis le serveur
        const url = `get-options.php?type_carrosserie=${selectedTypeCarrosserie}`;
        console.log('Fetching options from URL:', url); // Log the URL
        const response = await fetch(url);
        
        if (!response.ok) {
            const errorData = await response.json();
            console.error('Erreur du serveur lors du chargement des options:', errorData);
            throw new Error('Erreur du serveur: ' + (errorData.error || response.statusText));
        }

        const options = await response.json();

        // Grouper les options par catégorie
        const optionsByCategory = {};
        options.forEach(option => {
            if (!optionsByCategory[option.categorie_nom]) {
                optionsByCategory[option.categorie_nom] = [];
            }
            optionsByCategory[option.categorie_nom].push(option);
        });

        // Créer une section pour chaque catégorie
        for (const [categorie, optionsList] of Object.entries(optionsByCategory)) {
            const categorySection = document.createElement('div');
            categorySection.className = 'category-section mb-4';

            const categoryTitle = document.createElement('h3');
            categoryTitle.className = 'h4 mb-3';
            categoryTitle.textContent = categorie;
            categorySection.appendChild(categoryTitle);

            const rowContainer = document.createElement('div');
            rowContainer.className = 'row';
            categorySection.appendChild(rowContainer);

            optionsList.forEach(option => {
                const col = document.createElement('div');
                col.className = 'col-md-4 col-lg-3 mb-4';
                
                const card = document.createElement('div');
                card.className = 'card h-100 option-card';
                card.dataset.optionId = option.id;
                card.dataset.prix = option.prix; // Stocker le prix dans l'attribut data-prix
                
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
                
                if (option.images && option.images.length > 0) {
                    img.src = option.images[0];
                } else {
                    img.src = 'images/options/default-option.png';
                }
                
                imgContainer.appendChild(img);
                
                const cardBody = document.createElement('div');
                cardBody.className = 'card-body text-center';
                
                const title = document.createElement('h5');
                title.className = 'card-title';
                title.textContent = option.nom;
                
                const price = document.createElement('p');
                price.className = 'card-text';
                price.textContent = `Prix HT : ${formatPrix(option.prix, false)}`;
                
                cardBody.appendChild(title);
                cardBody.appendChild(price);
                card.appendChild(imgContainer);
                card.appendChild(cardBody);
                col.appendChild(card);
                rowContainer.appendChild(col);
                
                card.addEventListener('click', function() {
                    const optionId = this.dataset.optionId;
                    console.log('Clic sur option. ID:', optionId);
                    console.log('État actuel de selectedOptions:', Array.from(selectedOptions));
                    console.log('Élément cliqué:', this);

                    if (optionId) {
                        if (selectedOptions.has(optionId)) {
                            selectedOptions.delete(optionId);
                            this.classList.remove('border-primary');
                            console.log('Option désélectionnée. Nouvel état:', Array.from(selectedOptions));
                        } else {
                            selectedOptions.add(optionId);
                            this.classList.add('border-primary');
                            console.log('Option sélectionnée. Nouvel état:', Array.from(selectedOptions));
                        }
                        
                        // Sauvegarder la configuration après chaque modification
                        saveConfiguration();
                        
                        // Mettre à jour le récapitulatif
                        updateRecap();
                    }
                });
            });

            optionsContainer.appendChild(categorySection);
        }
    } catch (error) {
        console.error('Erreur lors du chargement des options:', error);
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
    // Utiliser la variable globale selectedModeleId qui est déjà mise à jour
    const idModele = selectedModeleId;
    const typeCarrosserie = document.getElementById('type-carrosserie').value;
    const annee = document.getElementById('annee').value;

    console.log('Validation du véhicule...');
    console.log('ID Modèle:', idModele);
    console.log('Type de Carrosserie:', typeCarrosserie);
    console.log('Année:', annee);

    if (!idModele || !typeCarrosserie) {
        alert('Veuillez sélectionner un modèle et un type de carrosserie');
        return;
    }

    // Le nom du modèle et l'ID sont déjà définis dans selectedVehicule lors du clic sur la carte
    // Cette fonction met à jour le type de carrosserie et l'année du véhicule sélectionné.
    if (selectedVehicule) {
        selectedVehicule.type_carrosserie = typeCarrosserie;
        selectedVehicule.annee = annee;
        selectedModeleId = idModele; 
        selectedTypeCarrosserie = typeCarrosserie;
        console.log('validerSelectionVehicule: selectedVehicule mis à jour:', selectedVehicule);
        console.log('validerSelectionVehicule: selectedTypeCarrosserie:', selectedTypeCarrosserie);
    } else {
        console.error('validerSelectionVehicule: selectedVehicule est null. Cela ne devrait pas arriver ici pour un modèle existant.');
        alert('Une erreur est survenue lors de la sélection du véhicule. Veuillez réessayer.');
        return;
    }
    
    // Masquer la sélection de véhicule
    const existingVehicleSelection = document.getElementById('existing-vehicle-selection');
    if (existingVehicleSelection) {
        existingVehicleSelection.style.display = 'none';
    } else {
        console.error('Erreur: Élément #existing-vehicle-selection non trouvé.');
    }

    // Afficher le configurateur
    const configurateur = document.getElementById('configurateur');
    if (configurateur) {
        configurateur.style.display = 'block';
        // Rendre les sections des kits et options visibles avec l'animation
        document.getElementById('step-kit').classList.add('is-visible');
        document.getElementById('step-options').classList.add('is-visible');
        document.getElementById('recap').classList.add('is-visible'); // Rendre la section récap visible
    } else {
        console.error('Erreur: Élément #configurateur non trouvé.');
    }
    
    // Charger les kits et options compatibles
    loadKits();
    loadOptions();

    // Mettre à jour le récapitulatif initialement
    updateRecap();
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
                    selectedMarqueId = idMarque;
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
            
            // Afficher les types de carrosserie disponibles
            if (modele.types_carrosserie && modele.types_carrosserie.length > 0) {
                const typesContainer = document.createElement('div');
                typesContainer.className = 'mt-2';
                modele.types_carrosserie.forEach(type => {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary me-1';
                    badge.textContent = type;
                    typesContainer.appendChild(badge);
                });
                cardBody.appendChild(typesContainer);
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
                    selectedModeleId = idModele;
                    document.getElementById('modeles-list').style.display = 'none';
                    document.getElementById('modeles-title').style.display = 'none';
                    
                    // Find the actual model object to get its name
                    const clickedModele = modeles.find(m => m.id == idModele);
                    if (clickedModele) {
                        selectedVehicule = {
                            id: clickedModele.id,
                            nom: clickedModele.nom, // Store the model name directly
                            type_carrosserie: null, // Will be updated by validerSelectionVehicule
                            annee: null // Will be updated by validerSelectionVehicule
                        };
                        console.log('loadModeles: selectedVehicule initialisé pour modèle existant:', selectedVehicule);
                    } else {
                        console.error('Erreur: Modèle cliqué non trouvé dans la liste des modèles chargée:', idModele);
                        selectedVehicule = { id: idModele, nom: 'Modèle non spécifié', type_carrosserie: null, annee: null };
                        console.log('loadModeles: selectedVehicule initialisé avec erreur pour modèle existant:', selectedVehicule);
                    }
                    
                    // This section creates the dynamic form for type de carrosserie and year.
                    const typeCarrosserieContainer = document.createElement('div');
                    typeCarrosserieContainer.className = 'col-md-4 mx-auto mt-4';
                    
                    const selectElement = document.createElement('select');
                    selectElement.className = 'form-select';
                    selectElement.id = 'type-carrosserie';
                    selectElement.required = true;

                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Sélectionnez un type';
                    selectElement.appendChild(defaultOption);

                    // Ensure clickedModele.types_carrosserie is an array before using forEach
                    if (clickedModele && Array.isArray(clickedModele.types_carrosserie)) {
                        clickedModele.types_carrosserie.forEach(type => {
                            const option = document.createElement('option');
                            option.value = type;
                            option.textContent = type;
                            selectElement.appendChild(option);
                        });
                    }

                    typeCarrosserieContainer.innerHTML = `
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Type de carrosserie</h5>
                                <div class="mb-3" id="select-type-carrosserie-placeholder"></div>
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
                    typeCarrosserieContainer.querySelector('#select-type-carrosserie-placeholder').appendChild(selectElement);
                    document.getElementById('existing-vehicle-selection').appendChild(typeCarrosserieContainer);
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
            selectedModeleId = 'autre'; // Indicate that a custom model is being used
            selectedVehicule = null; // Reset selectedVehicule for custom input, will be set on form submit
            console.log('loadModeles: selectedVehicule réinitialisé pour modèle personnalisé (Autre).');
            // Hide the existing vehicle selection form if it was visible
            const existingVehicleSelection = document.getElementById('existing-vehicle-selection');
            if (existingVehicleSelection) {
                existingVehicleSelection.style.display = 'none';
            }
            // Show the custom vehicle form
            document.getElementById('vehicle-selection-form').style.display = 'block';
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

    // Fonctions de sauvegarde de la configuration
    function saveConfiguration() {
    console.log('Sauvegarde de la configuration...');
    console.log('État actuel:', {
        vehicule: selectedVehicule,
        kit: selectedKit,
        options: Array.from(selectedOptions),
        hasVehicle: hasVehicle
    });

        const config = {
            vehicule: selectedVehicule,
            kit: selectedKit,
            options: Array.from(selectedOptions),
            hasVehicle: hasVehicle
        };

    try {
        localStorage.setItem('configurateur-state', JSON.stringify(config));
        console.log('Configuration sauvegardée avec succès');
    } catch (error) {
        console.error('Erreur lors de la sauvegarde de la configuration:', error);
    }
}

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

// Fonction pour charger la configuration sauvegardée
function loadConfiguration() {
        const savedConfig = localStorage.getItem('configurateur-state');
        if (savedConfig) {
            try {
                const config = JSON.parse(savedConfig);
                if (config.vehicule) {
                        selectedVehicule = config.vehicule;
                selectedModeleId = config.vehicule.id;
                selectedTypeCarrosserie = config.vehicule.type_carrosserie;
            }
                        if (config.kit) {
                selectedKit = config.kit;
                kitPrix = parseFloat(config.kit.prix) || 0;
            }
            if (config.options) {
                selectedOptions = new Set(config.options);
            }
            if (config.hasVehicle !== undefined) {
                hasVehicle = config.hasVehicle;
            }
            
            // Mettre à jour l'interface
                        updateRecap();
            } catch (error) {
                console.error('Erreur lors du chargement de la configuration:', error);
                localStorage.removeItem('configurateur-state');
            }
        }
    }

// Fonction pour mettre à jour le total
function updateTotal() {
    let totalTTC = 0;

    // Ajouter le prix du kit sélectionné
    if (selectedKit) {
        totalTTC += parseFloat(selectedKit.prix) || 0;
    }

    // Ajouter les prix des options sélectionnées
    selectedOptions.forEach(optionId => {
        const optionCard = document.querySelector(`.option-card[data-option-id="${optionId}"]`);
        if (optionCard) {
            const prix = parseFloat(optionCard.dataset.prix) || 0;
            totalTTC += prix;
        }
    });

    // Calculer le total HT
    const totalHT = calculerPrixHT(totalTTC);

    // Mettre à jour l'affichage des totaux
    const recapTotalHT = document.getElementById('recap-total-ht');
    const recapTotalTTC = document.getElementById('recap-total-ttc');

    if (recapTotalHT) {
        recapTotalHT.textContent = `${formatPrix(totalHT, false)}`;
    }
    if (recapTotalTTC) {
        recapTotalTTC.textContent = `${formatPrix(totalTTC, true)}`;
    }
}

    // Fonction pour mettre à jour le récapitulatif
    function updateRecap() {
        const recapDetails = document.getElementById('recap-details');
    if (!recapDetails) {
        console.warn('Élément #recap-details non trouvé.');
        return;
    }

    console.log('updateRecap: selectedVehicule:', selectedVehicule);
    console.log('Mise à jour du récapitulatif...');
    console.log('Options sélectionnées:', Array.from(selectedOptions));

        let html = '';

    // Ajouter les informations du véhicule sélectionné
                html += `
                    <div class="recap-section">
            <h4>Véhicule sélectionné</h4>
            <div id="recap-vehicule">
                `;
        if (selectedVehicule) {
            html += `
                <ul>
                    <li>Modèle: ${selectedVehicule.nom || 'Non spécifié'}</li>
                    <li>Type de carrosserie: ${selectedVehicule.type_carrosserie || 'Non spécifié'}</li>
                    ${selectedVehicule.annee ? `<li>Année: ${selectedVehicule.annee}</li>` : ''}
                </ul>
        `;
    } else {
        html += `
                <em>Aucun véhicule sélectionné</em>
        `;
    }
    html += `
            </div>
        </div>
    `;

    // Ajouter le kit sélectionné
            html += `
                <div class="recap-section">
            <h4>Kit sélectionné</h4>
            <div id="recap-kit">
    `;
    if (selectedKit) {
        html += `
                <ul>
                    <li>${selectedKit.nom}</li>
                    <li>Prix: ${formatPrix(selectedKit.prix, document.getElementById('prixTTC')?.checked)}</li>
                </ul>
        `;
    } else {
        html += `
                <em>Aucun kit sélectionné</em>
        `;
    }
    html += `
            </div>
        </div>
    `;

    // Ajouter les options sélectionnées
            html += `
                <div class="recap-section">
                    <h4>Options sélectionnées</h4>
            <div id="recap-options">
    `;
    if (selectedOptions.size > 0) {
        let listItems = '';
        selectedOptions.forEach(optionId => {
            const optionCard = document.querySelector(`.option-card[data-option-id="${optionId}"]`);
            console.log('Recherche de l\'option:', optionId);
            console.log('Carte trouvée:', optionCard);
            
            if (optionCard) {
                const nom = optionCard.querySelector('.card-title')?.textContent || 'Option inconnue';
                const prix = optionCard.dataset.prix;
                console.log('Détails de l\'option:', { id: optionId, nom, prix });
                listItems += `<li>${nom} - ${formatPrix(prix, document.getElementById('prixTTC')?.checked)}</li>`;
            } else {
                console.warn('Carte non trouvée pour l\'option:', optionId);
            }
        });
        html += `<ul class="list-unstyled">${listItems}</ul>`;
        console.log('HTML final des options:', html);
    } else {
                html += `
                <em>Aucune option sélectionnée</em>
                `;
    }
            html += `
            </div>
                </div>
            `;

    // Section des totaux (toujours présente)
        html += `
            <div class="recap-section border-top pt-3 mt-3">
                <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Total HT</h4>
                <p class="h3 mb-0" id="recap-total-ht">0,00 € HT</p>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                <h4 class="mb-0">Total TTC</h4>
                <p class="h3 mb-0" id="recap-total-ttc">0,00 € TTC</p>
                </div>
            </div>
        `;

        recapDetails.innerHTML = html;
    console.log('Récapitulatif mis à jour avec succès');

    // Mettre à jour le total (les valeurs réelles) après que les éléments aient été créés dans le DOM
    updateTotal();
}

// Rendre les variables et fonctions accessibles globalement
window.selectedVehicule = selectedVehicule;
window.selectedKit = selectedKit;
window.selectedOptions = selectedOptions;
window.total = total;
window.hasVehicle = hasVehicle;
window.selectedTypeCarrosserie = selectedTypeCarrosserie;
window.kitPrix = kitPrix;
window.TVA = TVA;
window.selectedMarqueId = selectedMarqueId;

window.calculerPrixHT = calculerPrixHT;
window.formatPrix = formatPrix;
window.resetKitsUI = resetKitsUI;
window.resetOptionsUI = resetOptionsUI;
window.loadKits = loadKits;
window.loadOptions = loadOptions;
window.createOptionCard = createOptionCard;
window.createKitCard = createKitCard;
window.validerSelectionVehicule = validerSelectionVehicule;
window.showVehicleSelection = showVehicleSelection;
window.loadMarques = loadMarques;
window.loadModeles = loadModeles;
window.displayModeleImages = displayModeleImages;
window.saveConfiguration = saveConfiguration;
window.selectKit = selectKit;
window.updateTotal = updateTotal;
    window.updateRecap = updateRecap;
window.loadConfiguration = loadConfiguration;

document.addEventListener('DOMContentLoaded', function() {
    // Rendre la première étape de sélection de véhicule visible au chargement de la page
    const stepVehicule = document.getElementById('step-vehicule');
    if (stepVehicule) {
        stepVehicule.classList.add('is-visible');
    }

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
            const marqueInput = document.getElementById('marque');
            const marquePersonnalisee = document.getElementById('marque-personnalisee').value.trim();
            const modeleInput = document.getElementById('modele');
            const modelePersonnalise = document.getElementById('modele-personnalise').value.trim();
            const annee = document.getElementById('annee').value;
            const typeCarrosseriePersonalise = document.getElementById('type-carrosserie-personnalise').value;

            // Construire le nom du véhicule
            let vehiculeNom = '';
            let selectedTypeCarrosserieForCustom = typeCarrosseriePersonalise; // Utiliser la valeur du nouveau champ

            if (selectedMarqueId === 'autre' && marquePersonnalisee) {
                vehiculeNom += marquePersonnalisee;
            } else if (marqueInput && marqueInput.value && marqueInput.selectedIndex !== -1) {
                vehiculeNom += marqueInput.options[marqueInput.selectedIndex].text;
            }

            if (selectedModeleId === 'autre' && modelePersonnalise) {
                vehiculeNom += ' ' + modelePersonnalise;
            } else if (modeleInput && modeleInput.value && modeleInput.selectedIndex !== -1) {
                vehiculeNom += ' ' + modeleInput.options[modeleInput.selectedIndex].text;
            }

            if (annee) {
                vehiculeNom += ' (' + annee + ')';
            }
                
                // Mettre à jour le véhicule sélectionné
            selectedVehicule = {
                id: vehiculeId,
                nom: vehiculeNom.trim() || 'Véhicule personnalisé',
                type_carrosserie: selectedTypeCarrosserieForCustom, // Utiliser la valeur du champ personnalisé
                annee: annee || null
            };
            selectedModeleId = vehiculeId; // Utiliser l'ID personnalisé pour la suite du flux
            selectedTypeCarrosserie = selectedTypeCarrosserieForCustom; // Mettre à jour la variable globale

            console.log('vehicleForm submit: selectedVehicule après soumission personnalisé:', selectedVehicule);
            console.log('vehicleForm submit: selectedTypeCarrosserie global:', selectedTypeCarrosserie); // Ajout d'un log
            
            // Réinitialiser les sélections précédentes (kits et options)
            selectedKit = null;
            kitPrix = 0;
            selectedOptions = new Set();
            total = 0;
            
            // Mettre à jour l'interface
            document.querySelectorAll('.vehicule-card').forEach(c => c.classList.remove('border-primary'));
            const vehiculeCard = document.createElement('div');
            vehiculeCard.className = 'col-md-4 mb-4';
            vehiculeCard.innerHTML = `
                <div class="card vehicule-card" data-id="${vehiculeId}">
                    <div class="card-body">
                        <h5 class="card-title">${vehiculeNom}</h5>
                        <p class="card-text">Véhicule personnalisé</p>
                        ${modeleInput.status ? `<p class="card-text"><small class="text-muted">Statut: ${modeleInput.status}</small></p>` : ''}
                    </div>
                </div>
            `;
            const vehiculeContainer = document.getElementById('step-vehicule').querySelector('.row') || document.createElement('div');
            vehiculeContainer.appendChild(vehiculeCard);
            
                // Réinitialiser l'interface des kits et options
                resetKitsUI();
                resetOptionsUI();
            
            // Afficher les sections
            document.getElementById('step-kit').classList.add('is-visible');
            document.getElementById('step-options').classList.add('is-visible');

            // Charger les kits et options pour ce véhicule
            loadKits();
            loadOptions();

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

    // Charger la configuration sauvegardée au chargement de la page
    loadConfiguration();

    // Écouter le changement de switch HT/TTC
    prixTTCSwitch.addEventListener('change', function() {
            updateRecap();
    });

    // Gestionnaire d'événements pour le bouton "Demander un devis"
    document.getElementById('btnDemandeDevis').addEventListener('click', function() {
        const devisModal = new bootstrap.Modal(document.getElementById('devisModal'));
        devisModal.show();
    });

    // Gestionnaire d'événements pour le bouton "Réinitialiser"
    document.getElementById('btnResetConfig').addEventListener('click', function() {
        if (confirm('Êtes-vous sûr de vouloir réinitialiser la configuration ? Toutes les sélections seront effacées.')) {
            // Réinitialiser les variables globales
            selectedVehicule = null;
            selectedKit = null;
            selectedOptions = new Set();
            total = 0;
            hasVehicle = null;
            selectedTypeCarrosserie = null;
            selectedMarqueId = null;

            // Effacer le localStorage
            localStorage.removeItem('configurateur-state');

            // Réinitialiser l'interface utilisateur
            const initialVehicleSelection = document.getElementById('initial-vehicle-selection');
            const existingVehicleSelection = document.getElementById('existing-vehicle-selection');
            const vehicleForm = document.getElementById('vehicle-selection-form');
            const configurateurDiv = document.getElementById('configurateur');

            if (initialVehicleSelection) initialVehicleSelection.style.display = 'block';
            if (existingVehicleSelection) existingVehicleSelection.style.display = 'none';
            if (vehicleForm) vehicleForm.style.display = 'none';
            if (configurateurDiv) configurateurDiv.style.display = 'none';

            // Masquer les étapes de kits et options
            const stepKit = document.getElementById('step-kit');
            const stepOptions = document.getElementById('step-options');
            const recap = document.getElementById('recap');

            if (stepKit) stepKit.classList.remove('is-visible');
            if (stepOptions) stepOptions.classList.remove('is-visible');
            if (recap) recap.classList.remove('is-visible');

            // Réinitialiser le contenu du récapitulatif (appeler updateRecap pour re-générer l'état initial)
            updateRecap();
        }
    });
            
    // Soumission du formulaire de devis
    const formDevis = document.getElementById('formDevis');
    if (formDevis) {
        formDevis.addEventListener('submit', async function(e) {
            console.log('Début de la soumission du formulaire');
            e.preventDefault();
            
            if (!this.checkValidity()) {
                console.log('Formulaire invalide');
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            console.log('Formulaire valide, vérification des sélections');
            console.log('formDevis submit: selectedVehicule:', selectedVehicule);
            console.log('formDevis submit: selectedKit:', selectedKit);

            if (!selectedVehicule || !selectedKit) {
                console.log('Véhicule ou kit manquant');
                alert("Veuillez sélectionner un véhicule et un kit avant de soumettre le devis.");
                return;
            }

            console.log('Sélections validées, préparation des données');
            // Récupérer les données du formulaire et les convertir en objet JSON
            const formData = new FormData(formDevis);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            console.log('Données du formulaire récupérées:', data);
            
            // Calculer totalHT et totalTTC juste avant la soumission pour s'assurer des valeurs à jour
            let currentTotalHT = 0;
            if (selectedKit) {
                currentTotalHT += parseFloat(selectedKit.prix) || 0;
            }
            selectedOptions.forEach(optionId => {
                const optionCard = document.querySelector(`.option-card[data-option-id="${optionId}"]`);
                if (optionCard) {
                    const prix = parseFloat(optionCard.dataset.prix) || 0;
                    currentTotalHT += prix;
                }
            });
            const currentTotalTTC = calculerPrixHT(currentTotalHT);
            
            console.log('Totaux calculés:', { currentTotalHT, currentTotalTTC });
            
            // Construire la configuration détaillée pour la BDD
            let configurationDetails = `Véhicule: ${selectedVehicule.nom} (Type: ${selectedVehicule.type_carrosserie}, Année: ${selectedVehicule.annee || 'Non spécifiée'})\n`;
            configurationDetails += `Kit: ${selectedKit.nom} (Prix HT: ${formatPrix(selectedKit.prix, false)})\n`;

            if (selectedOptions.size > 0) {
                configurationDetails += 'Options:\n';
                selectedOptions.forEach(optionId => {
                    const optionCard = document.querySelector(`.option-card[data-option-id="${optionId}"]`);
                    if (optionCard) {
                        const nom = optionCard.querySelector('.card-title')?.textContent || 'Option inconnue';
                        const prix = optionCard.dataset.prix || '0';
                        configurationDetails += `- ${nom} (Prix HT: ${formatPrix(prix, false)})\n`;
                    }
                });
            }
            
            console.log('Configuration détaillée construite:', configurationDetails);
            
            // Ajouter les détails de la configuration
            data.vehicule_id = selectedVehicule.id;
            data.type_carrosserie = selectedVehicule.type_carrosserie;
            data.kit_id = selectedKit ? selectedKit.id : null;
            data.prix_ht = currentTotalHT;
            data.prix_ttc = currentTotalTTC;
            data.configuration = configurationDetails;

            console.log('Données finales prêtes à être envoyées:', data);

            try {
                console.log('Tentative d\'envoi des données au serveur...');
                const response = await fetch('save-devis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                console.log('Réponse brute du serveur:', response);
                const result = await response.json();
                console.log('Données de la réponse:', result);
                
                if (result.success) {
                    console.log('Succès de l\'envoi');
                    alert("Votre demande de devis a été envoyée avec succès !");
                    const devisModal = bootstrap.Modal.getInstance(document.getElementById('devisModal'));
                    if (devisModal) devisModal.hide();
                    formDevis.reset();
                    formDevis.classList.remove('was-validated');
                    document.getElementById('btnResetConfig').click();
                } else {
                    console.error('Erreur du serveur:', result);
                    alert("Erreur lors de l'envoi de votre demande de devis: " + (result.message || "Erreur inconnue"));
                }
            } catch (error) {
                console.error("Erreur détaillée:", error);
                alert("Une erreur est survenue lors de l'envoi de votre demande. Veuillez réessayer.");
            }
        });
    }
});