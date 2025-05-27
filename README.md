# Configurateur de Véhicules Aménagés

Application web permettant de configurer des véhicules aménagés avec différents kits et options.

## Fonctionnalités

- Sélection de véhicules
- Configuration avec kits d'aménagement
- Ajout d'options supplémentaires
- Calcul automatique des prix
- Génération de devis
- Interface d'administration

## Installation

1. Cloner le repository
```bash
git clone [URL_DU_REPO]
```

2. Créer la base de données et configurer l'accès
- Copier `config.php.example` vers `config.php`
- Modifier les informations de connexion dans `config.php`

3. Initialiser la base de données
```bash
php admin/fix_database.php
```

4. Créer les dossiers d'images nécessaires
```bash
mkdir -p images/{vehicules,kits,options}
```

## Configuration

Copier le fichier `config.php.example` vers `config.php` et modifier les paramètres :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nom_de_la_base');
define('DB_USER', 'utilisateur');
define('DB_PASS', 'mot_de_passe');
```

## Utilisation

1. Accéder à l'interface d'administration :
   - URL : `/admin`
   - Identifiants par défaut : admin@admin.com / admin123

2. Configurer les éléments de base :
   - Ajouter des véhicules
   - Créer des kits d'aménagement
   - Définir les options disponibles
   - Gérer les compatibilités et les prix

## Structure de la base de données

- `vehicules` : Liste des véhicules disponibles
- `kits` : Kits d'aménagement
- `options` : Options supplémentaires
- `kit_vehicule_compatibilite` : Compatibilité et prix des kits par véhicule
- `option_vehicule_compatibilite` : Compatibilité et prix des options par véhicule

## Sécurité

- Les mots de passe sont hashés avec PASSWORD_DEFAULT
- Protection contre les injections SQL avec PDO
- Validation des entrées utilisateur
- Gestion des droits d'accès administrateur

## Restauration de la base de données

1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Créez une nouvelle base de données nommée "configurateur"
3. Cliquez sur l'onglet "Importer"
4. Sélectionnez le fichier `configurateur.sql`
5. **Important** : Dans la section "Options spécifiques à l'importation", cochez "Désactiver la vérification des clés étrangères"
6. Cliquez sur "Exécuter"

## Accès à l'administration

- URL : http://localhost/mon-configurateur/admin/
- Email : admin@example.com
- Mot de passe : admin123

## Structure du projet

- `index.php` : Page principale du configurateur
- `admin/` : Interface d'administration
  - `vehicules.php` : Gestion des véhicules
  - `kits.php` : Gestion des kits
  - `options.php` : Gestion des options
  - `gestion_images.php` : Gestion des images
  - `devis.php` : Gestion des devis

## Sauvegarde de la base de données

1. Ouvrez phpMyAdmin
2. Sélectionnez la base de données "configurateur"
3. Cliquez sur "Exporter"
4. Options recommandées :
   - Format : SQL
   - Ajouter CREATE DATABASE : Oui
   - Ajouter DROP TABLE : Oui
   - Ajouter les contraintes de clés étrangères : Oui
5. Cliquez sur "Exécuter"
6. Renommez le fichier exporté en `configurateur.sql`
7. Placez-le à la racine du projet

## Dépendances

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- TCPDF (inclus dans le projet) 