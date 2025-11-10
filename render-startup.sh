#!/bin/bash

# Script de démarrage pour Render (Production)
# Ce script configure Laravel pour la production

echo "🚀 Démarrage de l'application Laravel en production..."

# Vérifier si les variables d'environnement sont définies
if [ -z "$APP_KEY" ]; then
    echo "❌ APP_KEY non définie, génération automatique..."
    export APP_KEY=$(php artisan key:generate --show)
fi

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