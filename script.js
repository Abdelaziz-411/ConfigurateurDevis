// Variables globales
let selectedVehicule = null;
let selectedKit = null; // selectedKit est maintenant l'ID du kit, pas l'objet entier
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

// Fonction pour formater le prix (prend maintenant un prix TTC en entrée)
function formatPrix(basePriceTTC, displayAsTTC = true, includeSuffix = true) {
    const prixNum = parseFloat(basePriceTTC) || 0;
    let valeurAffichee;
    let suffix = '';

    if (displayAsTTC) {
        valeurAffichee = prixNum;
        suffix = 'TTC';
    } else {
        valeurAffichee = calculerPrixHT(prixNum);
        suffix = 'HT';
    }
    
    let formatted = `${valeurAffichee.toFixed(2).replace('.', ',')} €`;
    if (includeSuffix) {
        formatted += ` ${suffix}`;
    }
    return formatted;
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
        console.error('Modele ID ou type de carrosserie non défini');
        return;
    }

    const kitGallery = document.getElementById('kit-gallery');
    if (!kitGallery) {
        console.error('Kit gallery not found');
        return;
    }

    try {
        const response = await fetch(`get-kits.php?type_carrosserie=${selectedTypeCarrosserie}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const kits = await response.json();
        
        // Vider la galerie
        kitGallery.innerHTML = '';

        if (kits.length === 0) {
            kitGallery.innerHTML = '<div class="col-12 text-center"><p>Aucun kit disponible pour ce type de carrosserie.</p></div>';
            return;
        }

        // Créer et ajouter les cartes
        kits.forEach(kit => {
            const kitCard = createKitCard(kit);
            kitGallery.appendChild(kitCard);
        });

    } catch (error) {
        console.error('Erreur lors du chargement des kits:', error);
        kitGallery.innerHTML = '<div class="col-12 text-center"><p>Erreur lors du chargement des kits.</p></div>';
    }
}

// Fonction pour charger les options
async function loadOptions() {
    if (!selectedModeleId || !selectedTypeCarrosserie) {
        console.error('Modele ID ou type de carrosserie non défini');
        return;
    }

    const optionsContainer = document.querySelector('.option-container');
    if (!optionsContainer) {
        console.error('Options container not found');
        return;
    }

    try {
        const response = await fetch(`get-options.php?type_carrosserie=${selectedTypeCarrosserie}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const options = await response.json();
        
        // Vider le conteneur
        optionsContainer.innerHTML = '';

        if (options.length === 0) {
            optionsContainer.innerHTML = '<div class="col-12 text-center"><p>Aucune option disponible pour ce type de carrosserie.</p></div>';
            return;
        }

        // Grouper les options par catégorie
        const optionsByCategory = options.reduce((acc, option) => {
            const category = option.categorie || 'Sans catégorie';
            if (!acc[category]) {
                acc[category] = [];
            }
            acc[category].push(option);
            return acc;
        }, {});

        // Créer une section pour chaque catégorie
        Object.entries(optionsByCategory).forEach(([category, categoryOptions]) => {
            const categorySection = document.createElement('div');
            categorySection.className = 'category-section mb-4';
            categorySection.innerHTML = `
                    <h3 class="h4 mb-3">${category}</h3>
                    <div class="row g-4">
                    ${categoryOptions.map(option => {
                        const optionCard = createOptionCard(option);
                        return optionCard.outerHTML;
                    }).join('')}
                </div>
            `;
            optionsContainer.appendChild(categorySection);
        });

        // Réattacher les écouteurs d'événements
        document.querySelectorAll('.select-option-btn').forEach(button => {
            const optionId = button.dataset.optionId;
            const optionCard = button.closest('.option-card');
            if (optionCard) {
                const prix = optionCard.dataset.prix;
                button.addEventListener('click', () => selectOption(optionId, prix));
            }
        });

    } catch (error) {
        console.error('Erreur lors du chargement des options:', error);
        optionsContainer.innerHTML = '<div class="col-12 text-center"><p>Erreur lors du chargement des options.</p></div>';
    }
}

// Fonction pour créer une carte d'option
function createOptionCard(option) {
            const col = document.createElement('div');
            col.className = 'col-md-4 col-lg-3 mb-4';
            
            const card = document.createElement('div');
    card.className = 'card h-100 option-card';
    card.dataset.id = option.id;
    card.dataset.prix = option.prix;
    
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
    
    const selectButton = document.createElement('button');
    selectButton.className = 'btn btn-primary mt-2 select-option-btn';
    selectButton.dataset.optionId = option.id;
    selectButton.textContent = 'Sélectionner';
    selectButton.addEventListener('click', (e) => {
        e.stopPropagation();
        selectOption(option.id, option.prix);
    });
            
            cardBody.appendChild(title);
    cardBody.appendChild(price);
    cardBody.appendChild(selectButton);
            card.appendChild(imgContainer);
            card.appendChild(cardBody);
            col.appendChild(card);
    
    return col;
}

// Fonction pour créer une carte de kit
function createKitCard(kit) {
    const col = document.createElement('div');
        col.className = 'col-md-4 col-lg-3 mb-4';
    
    const card = document.createElement('div');
    card.className = 'card h-100 kit-card';
    card.dataset.id = kit.id;
    card.dataset.prix = kit.prix;
        
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
    
    const selectButton = document.createElement('button');
    selectButton.className = 'btn btn-primary mt-2 select-kit-btn';
    selectButton.dataset.kitId = kit.id;
    selectButton.textContent = 'Sélectionner';
    selectButton.addEventListener('click', (e) => {
        e.stopPropagation();
        selectKit(kit.id, kit.prix);
    });
        
        cardBody.appendChild(title);
    cardBody.appendChild(price);
    cardBody.appendChild(selectButton);
        card.appendChild(imgContainer);
        card.appendChild(cardBody);
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

// Fonction pour sauvegarder la configuration
    function saveConfiguration() {
        const config = {
            vehicule: selectedVehicule,
        kit: selectedKit, // selectedKit est maintenant un ID
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
    const kitCard = document.querySelector(`.kit-card[data-id="${kitId}"]`);
    const selectButton = kitCard?.querySelector('.select-kit-btn');
    
    if (!kitCard || !selectButton) {
        console.error(`Kit card or select button not found for kit: ${kitId}`);
        return;
    }

    // Si le kit est déjà sélectionné, on le désélectionne
    if (selectedKit === kitId) {
            selectedKit = null;
            kitPrix = 0;
        kitCard.classList.remove('border-primary');
        selectButton.textContent = 'Sélectionner';
        selectButton.classList.remove('btn-success');
        selectButton.classList.add('btn-primary');
    } else {
        // Désélectionner le kit précédent s'il existe
        if (selectedKit) {
            const previousKitCard = document.querySelector(`.kit-card[data-id="${selectedKit}"]`);
            const previousButton = previousKitCard?.querySelector('.select-kit-btn');
            if (previousKitCard && previousButton) {
                previousKitCard.classList.remove('border-primary');
                previousButton.textContent = 'Sélectionner';
                previousButton.classList.remove('btn-success');
                previousButton.classList.add('btn-primary');
            }
        }

        // Sélectionner le nouveau kit
        selectedKit = kitId;
            kitPrix = prix;
        kitCard.classList.add('border-primary');
        selectButton.textContent = 'Sélectionné';
        selectButton.classList.remove('btn-primary');
        selectButton.classList.add('btn-success');
    }

            updateRecap();
    saveConfiguration(); // Remettre l'appel ici
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
            if (config.kit !== undefined && config.kit !== null) {
                // Si config.kit est un objet (ancien format), utilisez son id, sinon utilisez-le directement (nouveau format)
                selectedKit = typeof config.kit === 'object' ? config.kit.id : config.kit;
            }
            if (config.options) {
                selectedOptions = new Set(config.options);
            }
            if (config.hasVehicle !== undefined) {
                hasVehicle = config.hasVehicle;
            }
            
            // updateRecap() sera appelé après le chargement des kits et options dans validerSelectionVehicule
            // et dans DOMContentLoaded si le configurateur est directement visible.

            } catch (error) {
                console.error('Erreur lors du chargement de la configuration:', error);
                localStorage.removeItem('configurateur-state');
            }
        }
    }

// Fonction pour mettre à jour le total
function updateTotal() {
    let currentTotalTTC = 0; // Cette variable accumule le total TTC
    
    // Ajouter le prix du kit (qui est déjà TTC)
    if (selectedKit) {
        const kitCard = document.querySelector(`.kit-card[data-id="${selectedKit}"]`);
        if (kitCard) {
            currentTotalTTC += parseFloat(kitCard.dataset.prix) || 0; // dataset.prix est déjà TTC
        }
    }
    
    // Ajouter les prix des options (qui sont déjà TTC)
    selectedOptions.forEach(optionId => {
        const optionCard = document.querySelector(`.option-card[data-id="${optionId}"]`);
        if (optionCard) {
            currentTotalTTC += parseFloat(optionCard.dataset.prix) || 0; // dataset.prix est déjà TTC
        }
    });
    
    // Mettre à jour l'affichage des totaux
    const totalHTElement = document.getElementById('recap-total-ht');
    const totalTTCElement = document.getElementById('recap-total-ttc');
    const prixTTCSwitch = document.getElementById('prixTTC');
    const displayTTC = prixTTCSwitch ? prixTTCSwitch.checked : true; // Par défaut TTC

    if (totalHTElement && totalTTCElement) {
        const totalHT = calculerPrixHT(currentTotalTTC); // Calculer le HT à partir du total TTC

        console.log('updateTotal: currentTotalTTC avant affichage:', currentTotalTTC);
        console.log('updateTotal: totalHT calculé avant affichage:', totalHT);
        
        totalHTElement.textContent = formatPrix(currentTotalTTC, false, false); // Afficher le HT à partir du total TTC
        totalTTCElement.textContent = formatPrix(currentTotalTTC, true, false);  // Afficher le TTC (le total de base), sans suffixe

        // Changer l'affichage des prix selon le switch
        if (displayTTC) {
            totalHTElement.style.display = 'none';
            totalTTCElement.style.display = '';
        } else {
            totalHTElement.style.display = '';
            totalTTCElement.style.display = 'none';
        }
    }
}

    // Fonction pour mettre à jour le récapitulatif
    function updateRecap() {
        const recapDetails = document.getElementById('recap-details');
    if (!recapDetails) {
        console.error('Élément #recap-details non trouvé');
        return;
    }

    const prixTTCSwitch = document.getElementById('prixTTC');
    const displayTTC = prixTTCSwitch ? prixTTCSwitch.checked : true; // Par défaut TTC

        let html = '';
    
    // Récapitulatif du véhicule
    if (selectedModeleId) {
        const modeleCard = document.querySelector(`.modele-card[data-modele-id="${selectedModeleId}"]`); // Utiliser data-modele-id
        const modeleNom = modeleCard?.querySelector('.card-title')?.textContent || selectedVehicule?.nom || 'Modèle sélectionné';

            html += `
                <div class="recap-section">
                    <h4>Véhicule</h4>
                <ul>
                    <li>${modeleNom}</li>
                    <li>Type de carrosserie : ${selectedTypeCarrosserie || 'Non spécifié'}</li>
                    ${selectedVehicule?.annee ? `<li>Année : ${selectedVehicule.annee}</li>` : ''}
                </ul>
                </div>
            `;
    } else {
        html += '<div class="recap-section"><h4>Véhicule</h4><p>Aucun véhicule sélectionné.</p></div>';
    }

    // Récapitulatif du kit
    if (selectedKit) {
        const kitCard = document.querySelector(`.kit-card[data-id="${selectedKit}"]`);
        if (kitCard) {
            const kitNom = kitCard.querySelector('.card-title')?.textContent || 'Kit sélectionné';
            const kitPrix = kitCard?.dataset.prix || '0';
            html += `
                <div class="recap-section">
                    <h4>Kit</h4>
                    <ul>
                        <li>${kitNom}</li>
                        <li>Prix : ${formatPrix(kitPrix, displayTTC, true)}</li>
                    </ul>
                </div>
            `;
        }
    } else {
        html += '<div class="recap-section"><h4>Kit</h4><p>Aucun kit sélectionné.</p></div>';
    }

    // Récapitulatif des options
    if (selectedOptions.size > 0) {
        html += '<div class="recap-section"><h4>Options</h4><ul>';
        selectedOptions.forEach(optionId => {
            const optionCard = document.querySelector(`.option-card[data-id="${optionId}"]`);
            if (optionCard) {
                const optionNom = optionCard.querySelector('.card-title')?.textContent || 'Option sélectionnée';
                const optionPrix = optionCard.dataset.prix || '0';
            html += `
                    <li>
                        ${optionNom}
                        <span class="text-muted">(${formatPrix(optionPrix, displayTTC, true)})</span>
                    </li>
                `;
            }
        });
        html += '</ul></div>';
    } else {
        html += '<div class="recap-section"><h4>Options</h4><p>Aucune option sélectionnée.</p></div>';
    }

    // Section des totaux
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

    // Mettre à jour le contenu du récapitulatif
        recapDetails.innerHTML = html;
    
    // Mettre à jour le total
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

    // Chargement de la configuration sauvegardée
    // loadConfiguration(); // Supprimé pour assurer un démarrage vide

    // Écouter le changement de switch HT/TTC
    const prixTTCSwitch = document.getElementById('prixTTC');
    if (prixTTCSwitch) {
        prixTTCSwitch.addEventListener('change', function() {
            updateTotal(); // Appeler updateTotal pour mettre à jour l'affichage des totaux
        });
    }

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

            // Ajustement ici : Ne pas alerter si kit non sélectionné pour permettre la demande de devis sans kit
            if (!selectedVehicule) {
                console.log('Véhicule manquant');
                alert("Veuillez sélectionner un véhicule avant de soumettre le devis.");
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
            
            // Calculer totalTTC et totalHT juste avant la soumission pour s'assurer des valeurs à jour
            let currentTotalTTC = 0; 
            if (selectedKit) {
                const kitCard = document.querySelector(`.kit-card[data-id="${selectedKit}"]`);
                if (kitCard) {
                    currentTotalTTC += parseFloat(kitCard.dataset.prix) || 0;
                }
            }
            selectedOptions.forEach(optionId => {
                const optionCard = document.querySelector(`.option-card[data-id="${optionId}"]`);
                if (optionCard) {
                    const prix = parseFloat(optionCard.dataset.prix) || 0; 
                    currentTotalTTC += prix;
                }
            });
            const finalTotalHT = calculerPrixHT(currentTotalTTC); // Calculer le HT final
            const finalTotalTTC = currentTotalTTC; // Correction du calcul TTC pour le devis
            
            console.log('Totaux calculés:', { finalTotalHT, finalTotalTTC });
            
            // Construire la configuration détaillée pour la BDD
            let configurationDetails = `Véhicule: ${selectedVehicule.nom} (Type: ${selectedVehicule.type_carrosserie}, Année: ${selectedVehicule.annee || 'Non spécifiée'})\n`;

            if (selectedKit) {
                const kitCard = document.querySelector(`.kit-card[data-id="${selectedKit}"]`);
                const kitNom = kitCard?.querySelector('.card-title')?.textContent || 'Kit sélectionné';
                const kitPrix = kitCard?.dataset.prix || '0';
                configurationDetails += `Kit: ${kitNom} (Prix HT: ${formatPrix(kitPrix, false, true)})\n`;
            }

            if (selectedOptions.size > 0) {
                configurationDetails += 'Options:\n';
                selectedOptions.forEach(optionId => {
                    const optionCard = document.querySelector(`.option-card[data-id="${optionId}"]`);
                    if (optionCard) {
                        const nom = optionCard.querySelector('.card-title')?.textContent || 'Option inconnue';
                        const prix = optionCard.dataset.prix || '0';
                        configurationDetails += `- ${nom} (Prix HT: ${formatPrix(prix, false, true)})\n`;
                    }
                });
            }
            
            console.log('Configuration détaillée construite:', configurationDetails);
            
            // Ajouter les détails de la configuration
            data.vehicule_id = selectedVehicule.id;
            data.type_carrosserie = selectedVehicule.type_carrosserie;
            data.kit_id = selectedKit ?? null; // Si selectedKit est null, envoyer null
            data.prix_ht = finalTotalHT; // Ajouter le prix HT calculé ici
            data.prix_ttc = finalTotalTTC;
            data.configuration = configurationDetails;

            console.log('Données finales prêtes à être envoyées:', data);

            try {
                console.log("Tentative d'envoi des données au serveur...");
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
                    console.log("Succès de l'envoi");
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

function selectOption(optionId, prix) {
    const optionCard = document.querySelector(`.option-card[data-id="${optionId}"]`);
    const selectButton = optionCard?.querySelector('.select-option-btn');
    
    if (!optionCard || !selectButton) {
        console.error(`Option card or select button not found for option: ${optionId}`);
        return;
    }
    
    if (selectedOptions.has(optionId)) {
        // Désélectionner l'option
        selectedOptions.delete(optionId);
        optionCard.classList.remove('border-primary');
        selectButton.textContent = 'Sélectionner';
        selectButton.classList.remove('btn-success');
        selectButton.classList.add('btn-primary');
    } else {
        // Sélectionner l'option
        selectedOptions.add(optionId);
        optionCard.classList.add('border-primary');
        selectButton.textContent = 'Sélectionné';
        selectButton.classList.remove('btn-primary');
        selectButton.classList.add('btn-success');
    }
    
    updateRecap();
    saveConfiguration(); // Remettre l'appel ici
}