# Variables d'environnement Render - Production

Copiez-collez ces variables dans votre dashboard Render (Environment) :

```
APP_NAME=Banque OM Pay
APP_ENV=production
APP_KEY=base64:QGi5EjML2MxXX8G0LM2amcRXAvUB9/SaQGi5muwVbKc=
APP_DEBUG=false
APP_URL=https://banque-20br.onrender.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=dpg-d447cjjipnbc73cpjufg-a.oregon-postgres.render.com
DB_PORT=5432
DB_DATABASE=banque_mjif
DB_USERNAME=banque_mjif_user
DB_PASSWORD=SAOJrUlFPMIT8TeqUaRcvZiPeoz0AFTQ
DB_SSLMODE=require

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seckmoustapha238@gmail.com
MAIL_PASSWORD=hduxmelbsferflrl
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seckmoustapha238@gmail.com
MAIL_FROM_NAME=Banque OM Pay

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MEMCACHED_HOST=127.0.0.1

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME=Banque OM Pay
VITE_PUSHER_APP_KEY=
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
VITE_PUSHER_APP_CLUSTER=mt1

L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_BASE_PATH=https://banque-20br.onrender.com
```

## Instructions d'utilisation :

1. Allez dans votre dashboard Render
2. Sélectionnez votre service "banque-20br"
3. Cliquez sur "Environment"
4. Copiez-collez toutes les variables ci-dessus
5. Cliquez sur "Save Changes"
6. Le service va redémarrer automatiquement avec la nouvelle configuration

## Variables critiques pour le fonctionnement :

- **DB_SSLMODE=require** : Essentiel pour la connexion PostgreSQL Render
- **APP_KEY** : Clé d'application générée
- **APP_ENV=production** : Mode production
- **LOG_LEVEL=error** : Réduit les logs en production

## Test après déploiement :

Une fois redémarré, testez :
- `GET https://banque-20br.onrender.com/api/health`
- `POST /api/auth/request-otp` avec `{"phone": "772687847"}`