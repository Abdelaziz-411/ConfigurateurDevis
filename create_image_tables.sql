CREATE TABLE marque_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_marque INT,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_marque) REFERENCES marques(id) ON DELETE CASCADE
);

CREATE TABLE modele_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_modele INT,
    image_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (id_modele) REFERENCES modeles(id) ON DELETE CASCADE
); 