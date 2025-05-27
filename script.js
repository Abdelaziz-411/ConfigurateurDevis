document.addEventListener('DOMContentLoaded', function() {
    const vehiculeSelect = document.getElementById('vehicule');
    const kitSelect = document.getElementById('kit');
    const optionsContainer = document.querySelector('.option-container');
    const totalSpan = document.getElementById('total-price');
    const galleryDiv = document.getElementById('kit-gallery');
    const recapDiv = document.getElementById('recap');
    const vehiculeCards = document.querySelectorAll('.vehicule-card');
    const prixTTCSwitch = document.getElementById('prixTTC');

    let kitPrix = 0;
    let selectedVehicule = null;
    let selectedKit = null;
    let selectedOptions = new Set();
    let total = 0;
    const TVA = 0.20; // TVA à 20%

    // Fonctions de sauvegarde de la configuration
    function saveConfiguration() {
        const config = {
            vehicule: selectedVehicule,
            kit: selectedKit,
            options: Array.from(selectedOptions)
        };
        localStorage.setItem('configurateur-state', JSON.stringify(config));
    }

    // Fonction de restauration de la configuration
    async function loadConfiguration() {
        const savedConfig = localStorage.getItem('configurateur-state');
        if (savedConfig) {
            try {
                const config = JSON.parse(savedConfig);
                
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
                        document.getElementById('step-kit').style.display = 'block';
                        document.getElementById('step-options').style.display = 'block';
                        
                        // Charger les kits et options pour ce véhicule
                        await loadKits(config.vehicule);
                        await loadOptions(config.vehicule);
                        
                        // Une fois que les kits et options sont chargés, on peut restaurer les sélections
                        if (config.kit) {
                            const kitCard = document.querySelector(`.kit-card[data-id="${config.kit.id}"]`);
                            if (kitCard) {
                                const selectButton = kitCard.querySelector('.select-kit');
                                if (selectButton) {
                                    selectButton.click();
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
                                        checkbox.dispatchEvent(new Event('change'));
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
        return `${valeur.toFixed(2)} € ${isTTC ? 'TTC' : 'HT'}`;
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

        // Ajouter le véhicule
        if (selectedVehicule) {
            const vehiculeCard = document.querySelector(`.vehicule-card[data-id="${selectedVehicule}"]`);
                if (vehiculeCard) {
                    const vehiculeNom = vehiculeCard.querySelector('.card-title')?.textContent || 'Véhicule sélectionné';
            html += `
                <div class="recap-section">
                    <h4>Véhicule</h4>
                    <p>${vehiculeNom}</p>
                </div>
            `;
                }
        }

            // Ajouter le kit seulement s'il est sélectionné et valide
            if (selectedKit && selectedKit.id && selectedKit.prix) {
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
                option.prix && 
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
                    <p class="h3 mb-0">${totalTTC.toFixed(2)} €</p>
                </div>
                ${isTTC ? `
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">Total HT</small>
                    <small class="text-muted">${total.toFixed(2)} €</small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">TVA (20%)</small>
                    <small class="text-muted">${(totalTTC - total).toFixed(2)} €</small>
                </div>
                ` : ''}
            </div>
        `;
            }

        recapDetails.innerHTML = html;
            const recapElement = document.getElementById('recap');
            if (recapElement) {
                recapElement.style.display = selectedVehicule ? 'block' : 'none';
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
            selectedVehicule = vehiculeId;
            
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
                    await loadKits(vehiculeId);
                    await loadOptions(vehiculeId);
            
            // Afficher les sections
            document.getElementById('step-kit').style.display = 'block';
            document.getElementById('step-options').style.display = 'block';
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

    // Modifier la fonction loadKits pour gérer la réinitialisation
    async function loadKits(vehiculeId) {
        try {
            const response = await fetch(`get-kits.php?vehicule_id=${vehiculeId}`);
            const data = await response.json();
            
                    const kitGallery = document.getElementById('kit-gallery');
            if (!kitGallery) return;

            // Vérifier si data existe et contient la propriété success
            if (data && data.success && data.kits) {
                    kitGallery.innerHTML = '';
                if (data.kits.length > 0) {
                        data.kits.forEach(kit => {
                        if (kit && kit.id) { // Vérifier que le kit est valide
                            const kitCard = createKitCard(kit);
                            kitGallery.appendChild(kitCard);
                        }
                        });
                    } else {
                        kitGallery.innerHTML = '<div class="col-12"><div class="alert alert-info">Aucun kit disponible pour ce véhicule.</div></div>';
                    }
                } else {
                console.error('Erreur:', data?.message || 'Réponse invalide du serveur');
                    kitGallery.innerHTML = '<div class="col-12"><div class="alert alert-danger">Erreur lors du chargement des kits.</div></div>';
                }
        } catch (error) {
                console.error('Erreur:', error);
                const kitGallery = document.getElementById('kit-gallery');
            if (kitGallery) {
                kitGallery.innerHTML = '<div class="col-12"><div class="alert alert-danger">Erreur lors du chargement des kits.</div></div>';
            }
        }
    }

    // Modifier la fonction loadOptions pour inclure l'ID du kit
    async function loadOptions(vehiculeId) {
        try {
            const url = new URL('get-options.php', window.location.href);
            url.searchParams.append('vehicule_id', vehiculeId);

            const response = await fetch(url.toString());
            const data = await response.json();
            
            if (!optionsContainer) return;

            // Vérifier si data existe et contient la propriété success
            if (data && data.success && data.options) {
                optionsContainer.innerHTML = '';
                
                if (data.options.length > 0) {
                    const row = document.createElement('div');
                    row.className = 'row g-4';
                    
                    data.options.forEach(option => {
                        if (option && option.id) {
                            const optionCard = createOptionCard(option);
                            row.appendChild(optionCard);
                        }
                    });
                    
                    optionsContainer.appendChild(row);
                } else {
                    optionsContainer.innerHTML = '<div class="col-12"><div class="alert alert-info">Aucune option disponible pour ce véhicule.</div></div>';
                }
            } else {
                console.error('Erreur:', data?.message || 'Réponse invalide du serveur');
                optionsContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Erreur lors du chargement des options.</div></div>';
            }
        } catch (error) {
            console.error('Erreur:', error);
            if (optionsContainer) {
                optionsContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Erreur lors du chargement des options.</div></div>';
            }
        }
    }

    // Créer une carte de kit
    function createKitCard(kit) {
        const card = document.createElement('div');
        card.className = 'col-md-6 col-lg-4';
        
        // Créer une version courte de la description (150 caractères)
        const shortDesc = kit.description.length > 150 
            ? kit.description.substring(0, 150) + '...'
            : kit.description;

        const isSelected = selectedKit && selectedKit.id === kit.id;
        
        card.innerHTML = `
            <div class="card h-100 kit-card ${isSelected ? 'border-primary' : ''}" data-id="${kit.id}">
                <div id="kitCarousel${kit.id}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        ${kit.images && kit.images.length > 0 
                            ? kit.images.map((image, index) => `
                            <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                    <img src="${image}" class="card-img-top" alt="${kit.nom}">
                            </div>
                            `).join('')
                            : `<div class="carousel-item active">
                                <img src="images/kits/default.jpg" class="card-img-top" alt="${kit.nom}">
                            </div>`
                        }
                    </div>
                    ${kit.images && kit.images.length > 1 ? `
                        <button class="carousel-control-prev" type="button" data-bs-target="#kitCarousel${kit.id}" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#kitCarousel${kit.id}" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    ` : ''}
                </div>
                <div class="card-body">
                    <h5 class="card-title">${kit.nom}</h5>
                    <p class="card-text description-preview">${shortDesc}</p>
                    ${kit.description.length > 150 
                        ? `<button type="button" class="btn btn-link p-0 voir-plus" data-bs-toggle="modal" data-bs-target="#kitModal${kit.id}">
                            Voir plus <i class="bi bi-arrow-right"></i>
                           </button>` 
                        : ''
                    }
                    <p class="card-text mt-2">
                        <strong>Prix : ${formatPrix(kit.prix)}</strong>
                    </p>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn ${isSelected ? 'btn-danger' : 'btn-primary'} w-100 select-kit" data-id="${kit.id}" data-prix="${kit.prix}">
                        ${isSelected ? 'Désélectionner' : 'Sélectionner'}
                    </button>
                </div>
            </div>

            ${kit.description.length > 150 ? `
                <div class="modal fade" id="kitModal${kit.id}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${kit.nom}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div id="kitModalCarousel${kit.id}" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                ${kit.images && kit.images.length > 0 
                                                    ? kit.images.map((image, index) => `
                                                        <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                                            <img src="${image}" class="img-fluid rounded" alt="${kit.nom}">
                                                        </div>
                                                    `).join('')
                                                    : `<div class="carousel-item active">
                                                        <img src="images/kits/default.jpg" class="img-fluid rounded" alt="${kit.nom}">
                                                    </div>`
                                                }
                                            </div>
                                            ${kit.images && kit.images.length > 1 ? `
                                                <button class="carousel-control-prev" type="button" data-bs-target="#kitModalCarousel${kit.id}" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon"></span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#kitModalCarousel${kit.id}" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon"></span>
                                                </button>
                                            ` : ''}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-3">Description détaillée :</h6>
                                        <p style="white-space: pre-line;">${kit.description}</p>
                                        <p class="mt-3">
                                            <strong>Prix : ${formatPrix(kit.prix)}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                <button type="button" class="btn ${isSelected ? 'btn-danger' : 'btn-primary'} select-kit-modal" data-id="${kit.id}" data-prix="${kit.prix}" data-bs-dismiss="modal">
                                    ${isSelected ? 'Désélectionner' : 'Sélectionner ce kit'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            ` : ''}
        `;

        // Ajouter les gestionnaires d'événements pour les boutons de sélection
        const selectButtons = card.querySelectorAll('.select-kit, .select-kit-modal');
        selectButtons.forEach(button => {
            button.addEventListener('click', function() {
            const kitId = this.dataset.id;
                const prix = parseFloat(this.dataset.prix);
                const isCurrentlySelected = selectedKit && selectedKit.id === kitId;

                if (isCurrentlySelected) {
                // Désélection du kit
                selectedKit = null;
                    kitPrix = 0;

                    // Réinitialiser l'apparence du bouton
                    document.querySelectorAll(`.kit-card[data-id="${kitId}"]`).forEach(k => {
                        k.classList.remove('border-primary');
                        const selectBtn = k.querySelector('button.select-kit');
                        selectBtn.classList.remove('btn-danger');
                        selectBtn.classList.add('btn-primary');
                        selectBtn.textContent = 'Sélectionner';
                    });

                    // Masquer la section des options et réinitialiser les options
                    document.getElementById('step-options').style.display = 'none';
                    selectedOptions = new Set();
                    const optionsContainer = document.querySelector('.option-container');
                    if (optionsContainer) {
                        optionsContainer.innerHTML = '';
                    }
                } else {
                    // Réinitialiser tous les kits
                    document.querySelectorAll('.kit-card').forEach(k => {
                        k.classList.remove('border-primary');
                        const selectBtn = k.querySelector('button.select-kit');
                        selectBtn.classList.remove('btn-danger');
                        selectBtn.classList.add('btn-primary');
                        selectBtn.textContent = 'Sélectionner';
                    });

                    // Sélection du nouveau kit
                    selectedKit = { id: kitId, prix: prix };
                    kitPrix = prix;

                    // Mettre en évidence le kit sélectionné
                    const selectedCard = document.querySelector(`.kit-card[data-id="${kitId}"]`);
                    selectedCard.classList.add('border-primary');
                    const selectBtn = selectedCard.querySelector('button.select-kit');
                    selectBtn.classList.remove('btn-primary');
                    selectBtn.classList.add('btn-danger');
                    selectBtn.textContent = 'Désélectionner';

                    // Afficher la section des options
                    document.getElementById('step-options').style.display = 'block';

                    // Charger les options compatibles
                    loadOptions(selectedVehicule);
                }

                // Mettre à jour le récapitulatif
                updateRecap();

                // Sauvegarder la configuration
                saveConfiguration();
            });
        });

        return card;
    }

    // Simplifier la fonction createOptionCard
    function createOptionCard(option) {
        const col = document.createElement('div');
        col.className = 'col-md-6 col-lg-4';
        
        col.innerHTML = `
            <div class="card h-100 option-card" data-id="${option.id}">
                <div id="optionCarousel${option.id}" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        ${option.images && option.images.length > 0 ? 
                            option.images.map((image, index) => `
                                <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                    <img src="${image}" class="card-img-top" alt="${option.nom}">
                                </div>
                            `).join('') :
                            `<div class="carousel-item active">
                                <img src="images/options/default.jpg" class="card-img-top" alt="${option.nom}">
                            </div>`
                        }
                    </div>
                    ${option.images && option.images.length > 1 ? `
                        <button class="carousel-control-prev" type="button" data-bs-target="#optionCarousel${option.id}" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#optionCarousel${option.id}" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    ` : ''}
                </div>
                <div class="card-body">
                    <h5 class="card-title">${option.nom || 'Option sans nom'}</h5>
                    <p class="card-text">${option.description || ''}</p>
                    <p class="card-text">
                        <strong>Prix : ${formatPrix(option.prix)}</strong>
                    </p>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="form-check">
                        <input class="form-check-input option-checkbox" type="checkbox" 
                               id="option${option.id}" 
                               data-id="${option.id}" 
                               data-prix="${option.prix}"
                               data-nom="${option.nom || 'Option sans nom'}">
                        <label class="form-check-label" for="option${option.id}">
                            Sélectionner cette option
                        </label>
                    </div>
                </div>
            </div>
        `;

        // Ajouter l'événement de sélection
        const checkbox = col.querySelector('.option-checkbox');
        checkbox.addEventListener('change', function() {
            const optionId = this.dataset.id;
            const optionPrix = parseFloat(this.dataset.prix);
            const optionCard = this.closest('.option-card');
            const optionNom = optionCard.querySelector('.card-title').textContent;

            if (this.checked) {
                selectedOptions.add({
                    id: optionId,
                    prix: optionPrix,
                    nom: optionNom
                });
                optionCard.classList.add('selected');
            } else {
                selectedOptions.forEach(opt => {
                    if (opt.id === optionId) {
                        selectedOptions.delete(opt);
                    }
                });
                optionCard.classList.remove('selected');
            }

            updateRecap();
            saveConfiguration();
        });

        return col;
    }

    // Gestion du formulaire de devis
    const btnDemandeDevis = document.getElementById('btnDemandeDevis');
    const devisModal = new bootstrap.Modal(document.getElementById('devisModal'));
    const btnEnvoyerDevis = document.getElementById('btnEnvoyerDevis');
    const formDevis = document.getElementById('formDevis');
    let lastFocusedElement = null;
    const modalElement = document.getElementById('devisModal');
    const mainContent = document.querySelector('main') || document.body;

    btnDemandeDevis.addEventListener('click', () => {
        if (!selectedVehicule) {
            alert('Veuillez sélectionner un véhicule');
            return;
        }
        lastFocusedElement = document.activeElement;
        mainContent.setAttribute('inert', '');
        modalElement.removeAttribute('inert');
        devisModal.show();
    });

    // Gestion du focus lors de l'ouverture du modal
    modalElement.addEventListener('shown.bs.modal', function () {
        document.getElementById('nom').focus();
    });

    // Gestion du focus lors de la fermeture du modal
    modalElement.addEventListener('hidden.bs.modal', function () {
        mainContent.removeAttribute('inert');
        modalElement.setAttribute('inert', '');
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    });

    // Gestion de la touche Escape
    modalElement.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            mainContent.removeAttribute('inert');
            modalElement.setAttribute('inert', '');
        }
    });

    btnEnvoyerDevis.addEventListener('click', async () => {
        if (!formDevis.checkValidity()) {
            formDevis.reportValidity();
            return;
        }

        const formData = {
            nom: document.getElementById('nom').value,
            prenom: document.getElementById('prenom').value,
            email: document.getElementById('email').value,
            telephone: document.getElementById('telephone').value,
            message: document.getElementById('message').value,
            vehicule_id: selectedVehicule,
            kit_id: selectedKit ? selectedKit.id : null,
            configuration: document.getElementById('recap-details').innerText,
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
                devisModal.hide();
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
});
