-- Vérifier si la colonne type_carrosserie existe déjà dans la table vehicules
SET @dbname = DATABASE();
SET @tablename = "vehicules";
SET @columnname = "type_carrosserie";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = @columnname
  ) = 0,
  "ALTER TABLE vehicules ADD COLUMN type_carrosserie VARCHAR(10) NULL AFTER description",
  "SELECT 'La colonne type_carrosserie existe déjà dans vehicules'"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Vérifier si la colonne status existe dans modeles et la renommer si nécessaire
SET @tablename = "modeles";
SET @oldcolumn = "status";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = @oldcolumn
  ) > 0,
  "ALTER TABLE modeles CHANGE COLUMN status type_carrosserie VARCHAR(50) DEFAULT NULL",
  "SELECT 'La colonne status n''existe pas dans modeles'"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Vérifier si la colonne statut existe dans kit_vehicule_compatibilite et la renommer si nécessaire
SET @tablename = "kit_vehicule_compatibilite";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'statut'
  ) > 0,
  "ALTER TABLE kit_vehicule_compatibilite CHANGE COLUMN statut type_carrosserie VARCHAR(10) NOT NULL",
  "SELECT 'La colonne statut n''existe pas dans kit_vehicule_compatibilite'"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Vérifier si la colonne statut existe dans option_vehicule_compatibilite et la renommer si nécessaire
SET @tablename = "option_vehicule_compatibilite";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = @tablename
    AND COLUMN_NAME = 'statut'
  ) > 0,
  "ALTER TABLE option_vehicule_compatibilite CHANGE COLUMN statut type_carrosserie VARCHAR(10) NOT NULL",
  "SELECT 'La colonne statut n''existe pas dans option_vehicule_compatibilite'"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Suppression des anciens index s'ils existent
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'kit_vehicule_compatibilite'
    AND INDEX_NAME = 'idx_kit_statut'
  ) > 0,
  "ALTER TABLE kit_vehicule_compatibilite DROP INDEX idx_kit_statut",
  "SELECT 'L''index idx_kit_statut n''existe pas'"
));
PREPARE dropIndex FROM @preparedStatement;
EXECUTE dropIndex;
DEALLOCATE PREPARE dropIndex;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'option_vehicule_compatibilite'
    AND INDEX_NAME = 'idx_option_statut'
  ) > 0,
  "ALTER TABLE option_vehicule_compatibilite DROP INDEX idx_option_statut",
  "SELECT 'L''index idx_option_statut n''existe pas'"
));
PREPARE dropIndex FROM @preparedStatement;
EXECUTE dropIndex;
DEALLOCATE PREPARE dropIndex;

-- Création des nouveaux index s'ils n'existent pas
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'kit_vehicule_compatibilite'
    AND INDEX_NAME = 'idx_kit_type_carrosserie'
  ) = 0,
  "CREATE INDEX idx_kit_type_carrosserie ON kit_vehicule_compatibilite(type_carrosserie)",
  "SELECT 'L''index idx_kit_type_carrosserie existe déjà'"
));
PREPARE createIndex FROM @preparedStatement;
EXECUTE createIndex;
DEALLOCATE PREPARE createIndex;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'option_vehicule_compatibilite'
    AND INDEX_NAME = 'idx_option_type_carrosserie'
  ) = 0,
  "CREATE INDEX idx_option_type_carrosserie ON option_vehicule_compatibilite(type_carrosserie)",
  "SELECT 'L''index idx_option_type_carrosserie existe déjà'"
));
PREPARE createIndex FROM @preparedStatement;
EXECUTE createIndex;
DEALLOCATE PREPARE createIndex;

-- Migration des données de status vers type_carrosserie
UPDATE modeles SET type_carrosserie = status WHERE status IS NOT NULL AND type_carrosserie IS NULL;

-- Suppression de la colonne status
ALTER TABLE modeles DROP COLUMN status;

-- Création de la table modele_type_carrosserie_compatibilite si elle n'existe pas
CREATE TABLE IF NOT EXISTS modele_type_carrosserie_compatibilite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_modele INT NOT NULL,
    type_carrosserie VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_modele) REFERENCES modeles(id) ON DELETE CASCADE,
    UNIQUE (id_modele, type_carrosserie)
);

-- Migration des données de type_carrosserie vers la table de compatibilité
INSERT IGNORE INTO modele_type_carrosserie_compatibilite (id_modele, type_carrosserie)
SELECT id, type_carrosserie 
FROM modeles 
WHERE type_carrosserie IS NOT NULL;

-- Mise à jour des index sur les tables de compatibilité
ALTER TABLE kit_vehicule_compatibilite 
DROP INDEX IF EXISTS idx_kit_statut,
ADD INDEX IF NOT EXISTS idx_kit_type_carrosserie (type_carrosserie);

ALTER TABLE option_vehicule_compatibilite 
DROP INDEX IF EXISTS idx_option_statut,
ADD INDEX IF NOT EXISTS idx_option_type_carrosserie (type_carrosserie); 