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