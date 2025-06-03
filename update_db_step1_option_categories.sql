CREATE TABLE option_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE options
ADD COLUMN id_categorie INT NULL AFTER description; -- Permettre NULL temporairement si des options existent déjà sans catégorie

ALTER TABLE options
ADD CONSTRAINT fk_option_categorie
FOREIGN KEY (id_categorie) REFERENCES option_categories(id)
ON DELETE SET NULL; -- Ou ON DELETE CASCADE selon la règle souhaitée

-- Optionnel: Ajouter les catégories existantes manuellement
-- INSERT INTO option_categories (nom) VALUES ('Ouvertures');
-- INSERT INTO option_categories (nom) VALUES ('Isolation');
-- INSERT INTO option_categories (nom) VALUES ('Habillage');
-- INSERT INTO option_categories (nom) VALUES ('Finitions');
-- INSERT INTO option_categories (nom) VALUES ('Marchandise');

-- N'oubliez pas de faire une sauvegarde de votre base de données avant d'exécuter ce script ! 