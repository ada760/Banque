# Installation et Configuration de Laravel Passport avec UUIDs

Ce document explique les étapes effectuées pour installer et configurer Laravel Passport avec les UUIDs dans l'application Laravel "Banque_Api".

## Étapes Réalisées

### 1. Installation du Package Laravel Passport
- Commande exécutée : `composer require laravel/passport`
- Cela a ajouté Laravel Passport à la liste des dépendances dans `composer.json` et installé le package.

### 2. Configuration de la Base de Données
- Modification du fichier `.env` pour configurer la connexion à PostgreSQL sur Render :
  - Changement de `DB_CONNECTION` de `mysql` à `pgsql`
  - Mise à jour de `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` avec les informations fournies
- Modification de `config/database.php` :
  - Changement de `sslmode` de `'prefer'` à `'require'` pour la connexion PostgreSQL afin de forcer SSL/TLS

### 3. Installation de Passport avec UUIDs
- Commande exécutée : `php artisan passport:install --uuids --force`
- Cette commande :
  - Génère les clés de chiffrement pour Passport
  - Publie les migrations Passport dans `database/migrations/`
  - Publie le fichier de configuration `config/passport.php`
  - Crée les clients OAuth par défaut (accès personnel et subvention de mot de passe) avec des UUIDs

### 4. Configuration de l'Authentification
- Modification de `config/auth.php` :
  - Ajout du guard `api` utilisant le driver `passport`
- Modification de `app/Models/User.php` :
  - Remplacement de `use Laravel\Sanctum\HasApiTokens;` par `use Laravel\Passport\HasApiTokens;`
  - Cela permet au modèle User d'utiliser les fonctionnalités d'API tokens de Passport

### 5. Clients OAuth Créés
- Client d'accès personnel :
  - ID : a044a90e-d450-472f-8494-80ea9736974a
  - Secret : xvzszNObDyYZ6EWr3C9tMW1aEhvYTvKlWkNznVXh
- Client de subvention de mot de passe :
  - ID : a044a911-f665-42bb-a931-d48b8a93483a
  - Secret : BVuRwwMU7whZsY1K6eKDiGdmlJR16ppdeqzqLyRS

## Fichiers Modifiés
- `.env` : Configuration de la base de données PostgreSQL
- `config/database.php` : Configuration SSL pour PostgreSQL
- `config/auth.php` : Ajout du guard API Passport
- `app/Models/User.php` : Changement vers HasApiTokens de Passport

## Fichiers Ajoutés par Passport
- `config/passport.php` : Configuration de Passport
- Migrations dans `database/migrations/` :
  - `2016_06_01_000001_create_oauth_auth_codes_table.php`
  - `2016_06_01_000002_create_oauth_access_tokens_table.php`
  - `2016_06_01_000003_create_oauth_refresh_tokens_table.php`
  - `2016_06_01_000004_create_oauth_clients_table.php`
  - `2016_06_01_000005_create_oauth_personal_access_clients_table.php`

## Utilisation
Passport est maintenant configuré avec les UUIDs. Vous pouvez utiliser les routes d'authentification OAuth2 standard de Passport pour gérer l'authentification API.

Les tokens générés utiliseront des UUIDs au lieu d'entiers auto-incrémentés, ce qui améliore la sécurité et l'unicité.