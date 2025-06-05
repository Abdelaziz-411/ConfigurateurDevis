-- Ajout de la colonne type_carrosserie à la table vehicules
ALTER TABLE vehicules ADD COLUMN type_carrosserie VARCHAR(10) NULL AFTER description;

-- Renommage de la colonne statut en type_carrosserie dans kit_vehicule_compatibilite
ALTER TABLE kit_vehicule_compatibilite CHANGE COLUMN statut type_carrosserie VARCHAR(10) NOT NULL;

-- Renommage de la colonne statut en type_carrosserie dans option_vehicule_compatibilite
ALTER TABLE option_vehicule_compatibilite CHANGE COLUMN statut type_carrosserie VARCHAR(10) NOT NULL;

-- Si nécessaire, mise à jour des données existantes (exemple : définir un type par défaut)
-- UPDATE vehicules SET type_carrosserie = 'Inconnu' WHERE type_carrosserie IS NULL;

-- Mise à jour des index pour utiliser le nouveau nom de colonne
ALTER TABLE kit_vehicule_compatibilite DROP INDEX idx_kit_statut;
CREATE INDEX idx_kit_type_carrosserie ON kit_vehicule_compatibilite(type_carrosserie);

ALTER TABLE option_vehicule_compatibilite DROP INDEX idx_option_statut;
CREATE INDEX idx_option_type_carrosserie ON option_vehicule_compatibilite(type_carrosserie); 