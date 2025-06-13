-- Suppression des anciennes tables de compatibilité
DROP TABLE IF EXISTS kit_vehicule_compatibilite;
DROP TABLE IF EXISTS option_vehicule_compatibilite;

-- Création de la nouvelle table pour les prix des kits par statut
CREATE TABLE kit_vehicule_compatibilite (
    id_kit INT NOT NULL,
    statut VARCHAR(50) NOT NULL,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id_kit, statut),
    FOREIGN KEY (id_kit) REFERENCES kits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création de la nouvelle table pour les prix des options par statut
CREATE TABLE option_vehicule_compatibilite (
    id_option INT NOT NULL,
    statut VARCHAR(50) NOT NULL,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id_option, statut),
    FOREIGN KEY (id_option) REFERENCES options(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajout d'un index sur le statut pour optimiser les recherches
CREATE INDEX idx_kit_statut ON kit_vehicule_compatibilite(statut);
CREATE INDEX idx_option_statut ON option_vehicule_compatibilite(statut); 