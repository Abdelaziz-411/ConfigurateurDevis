-- Ajout de la table des marques
CREATE TABLE IF NOT EXISTS marques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ajout de la table des modèles
CREATE TABLE IF NOT EXISTS modeles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_marque INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    status VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_marque) REFERENCES marques(id) ON DELETE CASCADE
);

-- Modification de la table vehicules pour ajouter les relations
ALTER TABLE vehicules 
ADD COLUMN id_marque INT,
ADD COLUMN id_modele INT,
ADD COLUMN annee VARCHAR(4),
ADD COLUMN marque_personnalisee VARCHAR(100),
ADD COLUMN modele_personnalise VARCHAR(100),
ADD FOREIGN KEY (id_marque) REFERENCES marques(id) ON DELETE SET NULL,
ADD FOREIGN KEY (id_modele) REFERENCES modeles(id) ON DELETE SET NULL;

-- Ajout de quelques marques courantes
INSERT INTO marques (nom) VALUES 
('Renault'),
('Peugeot'),
('Citroën'),
('Fiat'),
('Mercedes-Benz'),
('Volkswagen'),
('Ford'),
('Toyota'),
('Nissan'),
('Opel');

-- Ajout de quelques modèles pour Renault
INSERT INTO modeles (id_marque, nom) VALUES 
(1, 'Master'),
(1, 'Trafic'),
(1, 'Kangoo');

-- Ajout de quelques modèles pour Peugeot
INSERT INTO modeles (id_marque, nom) VALUES 
(2, 'Boxer'),
(2, 'Expert'),
(2, 'Partner');

-- Ajout de quelques modèles pour Citroën
INSERT INTO modeles (id_marque, nom) VALUES 
(3, 'Jumper'),
(3, 'Dispatch'),
(3, 'Berlingo'); 