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

# Forcer la génération d'une nouvelle APP_KEY à chaque démarrage
echo "🔄 Forçage génération nouvelle APP_KEY..."
export APP_KEY="base64:$(openssl rand -base64 32)"
echo "✅ Nouvelle APP_KEY générée: ${APP_KEY:0:15}..."

# Variables d'environnement essentielles pour la production
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}
export APP_URL=${APP_URL:-https://banque-20br.onrender.com}

# Configuration base de données PostgreSQL
export DB_CONNECTION=${DB_CONNECTION:-pgsql}
export DB_HOST=${DB_HOST:-localhost}
export DB_PORT=${DB_PORT:-5432}
export DB_DATABASE=${DB_DATABASE:-laravel}
export DB_USERNAME=${DB_USERNAME:-laravel}
export DB_PASSWORD=${DB_PASSWORD:-password}

# Configuration MongoDB
export MONGODB_URI=${MONGODB_URI:-mongodb://localhost:27017}
export MONGODB_DATABASE=${MONGODB_DATABASE:-om_pay_db}

# Configuration Redis (optionnel)
export REDIS_HOST=${REDIS_HOST:-localhost}
export REDIS_PASSWORD=${REDIS_PASSWORD:-}
export REDIS_PORT=${REDIS_PORT:-6379}
export REDIS_DB=${REDIS_DB:-0}
export REDIS_CACHE_DB=${REDIS_CACHE_DB:-1}
# Utiliser database queue si Redis non disponible
export QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}

# Configuration Mail (Gmail SMTP)
export MAIL_MAILER=${MAIL_MAILER:-smtp}
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

# Configuration de la base de données
echo "🗄️ Configuration de la base de données..."
php artisan migrate --force

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

# Générer la documentation Swagger
echo "📚 Génération de la documentation Swagger..."
php artisan l5-swagger:generate

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