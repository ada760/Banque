#!/bin/bash

# Script de démarrage pour Render (Production)
# Ce script configure Laravel pour la production

echo "🚀 Démarrage de l'application Laravel en production..."

# Vérifier si les variables d'environnement sont définies
if [ -z "$APP_KEY" ]; then
    echo "❌ APP_KEY non définie, génération automatique..."
    # Générer une clé de 32 caractères pour AES-256-CBC
    export APP_KEY="base64:$(openssl rand -base64 32)"
    echo "✅ APP_KEY générée: ${APP_KEY}"
else
    echo "✅ APP_KEY déjà définie: ${APP_KEY:0:10}..."
fi

# Générer APP_KEY seulement si elle n'est pas définie
if [ -z "$APP_KEY" ]; then
    echo "🔄 Génération APP_KEY..."
    export APP_KEY="base64:$(openssl rand -base64 32)"
    echo "✅ APP_KEY générée: ${APP_KEY:0:15}..."
else
    echo "✅ APP_KEY déjà définie"
fi

# Variables d'environnement essentielles pour la production
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}
export APP_URL=${APP_URL:-https://banque-20br.onrender.com}

# Configuration base de données PostgreSQL
export DB_CONNECTION=${DB_CONNECTION:-pgsql}
# Ne pas définir DB_HOST etc. si DATABASE_URL est fourni (cas de Render)
if [ -z "$DATABASE_URL" ]; then
    export DB_HOST=${DB_HOST:-localhost}
    export DB_PORT=${DB_PORT:-5432}
    export DB_DATABASE=${DB_DATABASE:-laravel}
    export DB_USERNAME=${DB_USERNAME:-laravel}
    export DB_PASSWORD=${DB_PASSWORD:-password}
fi

# Configuration MongoDB (optionnel)
export MONGODB_URI=${MONGODB_URI:-}
export MONGODB_DATABASE=${MONGODB_DATABASE:-om_pay_db}

# Configuration Redis (optionnel)
export REDIS_HOST=${REDIS_HOST:-localhost}
export REDIS_PASSWORD=${REDIS_PASSWORD:-}
export REDIS_PORT=${REDIS_PORT:-6379}
export REDIS_DB=${REDIS_DB:-0}
export REDIS_CACHE_DB=${REDIS_CACHE_DB:-1}
# Utiliser sync queue en production (pas de worker persistant sur Render)
export QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

# Configuration Mail (Log pour les tests - pas d'envoi réel)
export MAIL_MAILER=${MAIL_MAILER:-log}
export MAIL_HOST=${MAIL_HOST:-smtp.gmail.com}
export MAIL_PORT=${MAIL_PORT:-587}
export MAIL_USERNAME=${MAIL_USERNAME:-your-email@gmail.com}
export MAIL_PASSWORD=${MAIL_PASSWORD:-your-app-password}
export MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
export MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-noreply@banque-20br.onrender.com}
export MAIL_FROM_NAME=${MAIL_FROM_NAME:-"Banque OM Pay"}

# Configuration Swagger
export L5_SWAGGER_GENERATE_ALWAYS=${L5_SWAGGER_GENERATE_ALWAYS:-true}
export L5_SWAGGER_BASE_PATH=${L5_SWAGGER_BASE_PATH:-https://banque-20br.onrender.com}

echo "🔧 Variables d'environnement configurées:"
echo "  - APP_ENV: $APP_ENV"
echo "  - DB_CONNECTION: $DB_CONNECTION"
echo "  - REDIS_HOST: $REDIS_HOST"
echo "  - MAIL_MAILER: $MAIL_MAILER"
echo "  - MONGODB_URI: $MONGODB_URI"
echo "  - L5_SWAGGER_GENERATE_ALWAYS: $L5_SWAGGER_GENERATE_ALWAYS"

# Nettoyer les caches
echo "🧹 Nettoyage des caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Supprimer les caches compilés
rm -f bootstrap/cache/*.php
rm -f storage/framework/cache/data/*.php

# Régénérer l'autoload
echo "📦 Régénération autoload..."
composer dump-autoload --no-dev --optimize

# Configuration de la base de données
echo "🗄️ Configuration de la base de données..."
echo "🔍 Test connexion base de données..."

# Attendre que la base de données soit prête (jusqu'à 30 secondes)
DB_READY=false
for i in {1..30}; do
    if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'OK'; } catch(Exception \$e) { echo 'FAIL'; }" 2>/dev/null | grep -q "OK"; then
        DB_READY=true
        break
    fi
    echo "⏳ Attente base de données... ($i/30)"
    sleep 1
done

if [ "$DB_READY" = false ]; then
    echo "❌ Base de données non disponible après 30 secondes"
    exit 1
fi

echo "✅ Connexion base de données réussie"

if php artisan migrate --force; then
    echo "✅ Migrations exécutées"
else
    echo "❌ Échec des migrations"
    exit 1
fi

# Exécuter les seeders en production (données de test)
echo "🌱 Exécution des seeders..."
if php artisan db:seed --force; then
    echo "✅ Seeders exécutés"
else
    echo "⚠️ Échec des seeders (continuer quand même)"
fi

# Générer les clés Passport si elles n'existent pas
echo "🔐 Configuration Passport..."
if [ ! -f storage/oauth-public.key ]; then
    php artisan passport:keys --force
fi

# Générer les clés Passport client si nécessaire
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force
fi

# Créer le client Passport si nécessaire
CLIENT_EXISTS=$(php artisan tinker --execute="echo App\Models\Passport\Client::count();" 2>/dev/null || echo "0")
if [ "$CLIENT_EXISTS" = "0" ]; then
    echo "🔑 Création du client Passport..."
    php artisan passport:client --personal --name="Banque API Personal Access Client" --no-interaction
fi

# Générer la documentation Swagger (avec gestion d'erreur)
echo "📚 Génération de la documentation Swagger..."
if php artisan l5-swagger:generate; then
    echo "✅ Documentation Swagger générée"
else
    echo "⚠️ Échec génération Swagger, continuation..."
fi

# Cacher les configurations pour la production
echo "⚡ Mise en cache des configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Donner les permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

echo "✅ Application prête ! Démarrage du serveur web..."

# Démarrer le serveur web
php artisan serve --host=0.0.0.0 --port=$PORT