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
            options: Array.from(selectedOptions)
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
                        await loadKits(config.vehicule);
                        await loadOptions(config.vehicule);

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

    // Modifier la fonction loadKits pour gérer la réinitialisation
    async function loadKits(vehiculeId) {
        console.log('Chargement des kits pour le véhicule:', vehiculeId);
        try {
            const response = await fetch(`get-kits.php?vehicule_id=${vehiculeId}`);
            const data = await response.json();
            console.log('Données reçues du serveur:', data);

            const kitGallery = document.getElementById('kit-gallery');
            if (!kitGallery) {
                console.error('Élément kit-gallery non trouvé');
                return;
            }

            if (data && data.success && data.kits) {
                kitGallery.innerHTML = '';
                if (data.kits.length > 0) {
                    console.log('Création des cartes pour', data.kits.length, 'kits');
                    data.kits.forEach(kit => {
                        if (kit && kit.id) {
                            const kitCard = createKitCard(kit);
                            kitGallery.appendChild(kitCard);
                        }
                    });
                } else {
                    console.log('Aucun kit disponible');
                    kitGallery.innerHTML = '<div class="col-12"><div class="alert alert-info">Aucun kit disponible pour ce véhicule.</div></div>';
                }
            } else {
                console.error('Erreur dans les données:', data?.message || 'Réponse invalide du serveur');
                kitGallery.innerHTML = '<div class="col-12"><div class="alert alert-danger">Erreur lors du chargement des kits.</div></div>';
            }
        } catch (error) {
            console.error('Erreur lors du chargement des kits:', error);
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

            if (data && data.success && data.options) {
                optionsContainer.innerHTML = '';

                if (data.options.length > 0) {
                    // Grouper les options par catégorie
                    const optionsByCategory = data.options.reduce((acc, option) => {
                        const categoryId = option.categorie_id || 'sans-categorie';
                        const categoryName = option.categorie_nom || 'Sans catégorie';
                        if (!acc[categoryId]) {
                            acc[categoryId] = {
                                name: categoryName,
                                options: []
                            };
                        }
                        acc[categoryId].options.push(option);
                        return acc;
                    }, {});

                    // Ajouter les filtres
                    const filterSection = document.createElement('div');
                    filterSection.className = 'mb-4';
                    filterSection.innerHTML = `
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="optionSearch" placeholder="Rechercher une option...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2 justify-content-end">
                                    <select class="form-select" id="sortOptions" style="max-width: 200px;">
                                        <option value="default">Trier par défaut</option>
                                        <option value="price-asc">Prix croissant</option>
                                        <option value="price-desc">Prix décroissant</option>
                                        <option value="name">Nom</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                    optionsContainer.appendChild(filterSection);

                    // Créer les sections pour chaque catégorie
                    Object.entries(optionsByCategory).forEach(([categoryId, category]) => {
                        const categorySection = document.createElement('div');
                        categorySection.className = 'mb-4 category-section';
                        categorySection.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h4 mb-0">${category.name}</h3>
                                <span class="badge bg-primary">${category.options.length} option(s)</span>
                            </div>
                            <div class="row g-4" id="category-${categoryId}">
                            </div>
                        `;
                        optionsContainer.appendChild(categorySection);

                        const categoryRow = categorySection.querySelector(`#category-${categoryId}`);
                        category.options.forEach(option => {
                            if (option && option.id) {
                                const optionCard = createOptionCard(option);
                                categoryRow.appendChild(optionCard);
                            }
                        });
                    });

                    // Ajouter les écouteurs d'événements pour les filtres
                    const searchInput = document.getElementById('optionSearch');
                    const sortSelect = document.getElementById('sortOptions');

                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        document.querySelectorAll('.option-card').forEach(card => {
                            const title = card.querySelector('.card-title').textContent.toLowerCase();
                            const description = card.querySelector('.card-text').textContent.toLowerCase();
                            const category = card.closest('.category-section').querySelector('h3').textContent.toLowerCase();
                            
                            const isVisible = title.includes(searchTerm) || 
                                            description.includes(searchTerm) || 
                                            category.includes(searchTerm);
                            
                            card.style.display = isVisible ? '' : 'none';
                        });

                        // Masquer les catégories vides
                        document.querySelectorAll('.category-section').forEach(section => {
                            const visibleCards = section.querySelectorAll('.option-card:not([style*="display: none"])');
                            section.style.display = visibleCards.length > 0 ? '' : 'none';
                        });
                    });

                    sortSelect.addEventListener('change', function() {
                        const sortValue = this.value;
                        document.querySelectorAll('.category-section').forEach(section => {
                            const categoryRow = section.querySelector('.row');
                            const cards = Array.from(categoryRow.children);

                            cards.sort((a, b) => {
                                const priceA = parseFloat(a.querySelector('.option-checkbox').dataset.prix);
                                const priceB = parseFloat(b.querySelector('.option-checkbox').dataset.prix);
                                const nameA = a.querySelector('.card-title').textContent;
                                const nameB = b.querySelector('.card-title').textContent;

                                switch(sortValue) {
                                    case 'price-asc':
                                        return priceA - priceB;
                                    case 'price-desc':
                                        return priceB - priceA;
                                    case 'name':
                                        return nameA.localeCompare(nameB);
                                    default:
                                        return 0;
                                }
                            });

                            cards.forEach(card => categoryRow.appendChild(card));
                        });
                    });
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
        console.log('Création d\'une carte pour le kit:', kit);
        const card = document.createElement('div');
        card.className = 'col-md-6 col-lg-4 mb-4';
        card.innerHTML = `
            <div class="card h-100 kit-card" data-id="${kit.id}">
                ${kit.images && kit.images.length > 0
                    ? `<div id="kitCarousel${kit.id}" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            ${kit.images.map((image, index) => `
                                <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                    <img src="${image}" class="card-img-top" alt="${kit.nom}">
                                </div>
                            `).join('')}
                        </div>
                        ${kit.images.length > 1 ? `
                            <button class="carousel-control-prev" type="button" data-bs-target="#kitCarousel${kit.id}" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#kitCarousel${kit.id}" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        ` : ''}
                    </div>`
                    : `<img src="images/kits/default.jpg" class="card-img-top" alt="${kit.nom}">`
                }
                <div class="card-body">
                    <h5 class="card-title">${kit.nom}</h5>
                    <p class="card-text">${kit.description}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0">${kit.prix.toFixed(2)} € TTC</span>
                        <button class="btn btn-primary select-kit" onclick="selectKit(Number(${kit.id}), ${kit.prix})">Sélectionner</button>
                    </div>
                </div>
            </div>
        `;

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
});