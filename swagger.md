# Documentation Swagger - API OM Pay

## Vue d'ensemble

Cette documentation explique l'implémentation complète de Swagger pour documenter l'API OM Pay de l'application bancaire Laravel.

## Architecture Swagger

### 1. Installation et Configuration

#### Installation du package
```bash
composer require "darkaonline/l5-swagger"
```

#### Publication des fichiers de configuration
```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### 2. Configuration dans `config/l5-swagger.php`

#### Configuration principale
```php
return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'OM Pay API',
                'description' => 'API de paiement mobile OM Pay pour la gestion bancaire',
                'version' => '1.0.0',
            ],
            'routes' => [
                'api' => 'api/documentation',
            ],
            'paths' => [
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
                'annotations' => [
                    base_path('app/Http/Controllers'),
                ],
            ],
        ],
    ],
    // ... autres configurations
];
```

#### Configuration de sécurité Bearer
```php
'securityDefinitions' => [
    'securitySchemes' => [
        'bearerAuth' => [
            'type' => 'apiKey',
            'description' => 'Token JWT obtenu après authentification OM Pay. Format: Bearer {token}',
            'name' => 'Authorization',
            'in' => 'header',
        ],
    ],
    'security' => [
        [
            'bearerAuth' => [],
        ],
    ],
],
```

### 3. Annotations Swagger dans les Contrôleurs

#### Annotations dans OmAuthController

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *     title="OM Pay API",
 *     description="API de paiement mobile OM Pay pour la gestion bancaire",
 *     version="1.0.0"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Token JWT obtenu après authentification OM Pay. Format: Bearer {token}"
 * )
 */
class OmAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/request-otp",
     *     tags={"Authentification"},
     *     summary="Demander un OTP pour l'authentification",
     *     description="Envoie un code OTP par email pour l'authentification OM Pay",
     *     operationId="requestOtp",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone Orange Money")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé par email"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expires_in", type="integer", example=360)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou compte bloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Numéro de téléphone invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function requestOtp(Request $request): JsonResponse
    {
        // Logique d'envoi d'OTP
    }

    /**
     * @OA\Post(
     *     path="/auth/verify-otp",
     *     tags={"Authentification"},
     *     summary="Vérifier le code OTP",
     *     description="Vérifie le code OTP reçu par email et indique l'étape suivante",
     *     operationId="verifyOtp",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","otp"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone"),
     *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP à 6 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP vérifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP vérifié avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="has_secret_code", type="boolean", example=false),
     *                 @OA\Property(property="next_step", type="string", example="set_secret_code")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Code OTP incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code OTP incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        // Logique de vérification d'OTP
    }

    /**
     * @OA\Post(
     *     path="/auth/set-secret-code",
     *     tags={"Authentification"},
     *     summary="Définir le code secret OM Pay",
     *     description="Définit le code secret à 4 chiffres pour l'authentification OM Pay (première connexion ou reconnexion)",
     *     operationId="setSecretCode",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","secret_code"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone"),
     *             @OA\Property(property="secret_code", type="string", example="1234", description="Code secret à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code secret défini avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code secret défini avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="string", example="10281d44-9f2d-3008-8c61-7d55a71b538f"),
     *                     @OA\Property(property="telephone", type="string", example="772687847"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                         @OA\Property(property="titulaire", type="string", example="Abdoulaye Seck"),
     *                         @OA\Property(property="email", type="string", example="seckmoustapha238@gmail.com")
     *                     )
     *                 ),
     *                 @OA\Property(property="is_first_login", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur lors de la définition du code secret",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Un code secret OM Pay est déjà défini pour ce numéro. Utilisez la connexion normale.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function setSecretCode(Request $request): JsonResponse
    {
        // Logique de définition du code secret
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentification"},
     *     summary="Connexion avec code secret OM Pay",
     *     description="Authentification avec le code secret à 4 chiffres pour les connexions suivantes",
     *     operationId="login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","secret_code"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone"),
     *             @OA\Property(property="secret_code", type="string", example="1234", description="Code secret à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="string", example="10281d44-9f2d-3008-8c61-7d55a71b538f"),
     *                     @OA\Property(property="telephone", type="string", example="772687847"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                         @OA\Property(property="titulaire", type="string", example="Abdoulaye Seck"),
     *                         @OA\Property(property="email", type="string", example="seckmoustapha238@gmail.com")
     *                     )
     *                 ),
     *                 @OA\Property(property="compte_actif", type="object",
     *                     @OA\Property(property="id", type="string", example="62028976-381e-3a70-9073-df6de0fd5e74"),
     *                     @OA\Property(property="num_compte", type="string", example="C0081999796"),
     *                     @OA\Property(property="solde", type="number", format="float", example=8728),
     *                     @OA\Property(property="devise", type="string", example="XOF")
     *                 ),
     *                 @OA\Property(property="is_first_login", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Code secret incorrect ou compte bloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code secret incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        // Logique de connexion
    }
}
```

#### Annotations dans TransactionController

```php
<?php

namespace App\Http\Controllers\OmPay;

use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Transactions OM Pay",
 *     description="Gestion des transactions OM Pay"
 * )
 */
class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/ompay/transactions",
     *     tags={"Transactions OM Pay"},
     *     summary="Récupérer les transactions de l'utilisateur",
     *     description="Retourne la liste des transactions OM Pay de l'utilisateur authentifié",
     *     operationId="getTransactions",
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre maximum de transactions à retourner",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="user_id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                 @OA\Property(property="recipient_phone", type="string", nullable=true, example=null),
     *                 @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment"),
     *                 @OA\Property(property="amount", type="number", format="float", example=500),
     *                 @OA\Property(property="status", type="string", enum={"pending", "success", "failed"}, example="success"),
     *                 @OA\Property(property="reference", type="string", nullable=true, example=null),
     *                 @OA\Property(property="merchant_id", type="string", example="merchant_123"),
     *                 @OA\Property(property="service_id", type="string", example="service_456"),
     *                 @OA\Property(property="fee", type="number", format="float", example=5),
     *                 @OA\Property(property="description", type="string", example="Paiement de facture"),
     *                 @OA\Property(property="transaction_date", type="string", format="date-time", example="2025-11-10T15:35:00.373000Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="id", type="string", example="69120624c02cc5e99b075eb2")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="bearerAuth",
     *         type="apiKey",
     *         name="Authorization",
     *         in="header"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Logique de récupération des transactions
    }

    /**
     * @OA\Post(
     *     path="/ompay/transactions",
     *     tags={"Transactions OM Pay"},
     *     summary="Créer une nouvelle transaction OM Pay",
     *     description="Crée une nouvelle transaction OM Pay (transfert ou paiement)",
     *     operationId="createTransaction",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","amount"},
     *             @OA\Property(property="recipient_phone", type="string", description="Numéro du destinataire pour les transferts", nullable=true, example=null),
     *             @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment", description="Type de transaction"),
     *             @OA\Property(property="amount", type="number", format="float", minimum=100, example=500, description="Montant de la transaction (minimum 100)"),
     *             @OA\Property(property="reference", type="string", description="Référence de la transaction", nullable=true, example=null),
     *             @OA\Property(property="merchant_id", type="string", description="ID du marchand", nullable=true, example="merchant_123"),
     *             @OA\Property(property="service_id", type="string", description="ID du service", nullable=true, example="service_456"),
     *             @OA\Property(property="qr_metadata", type="object", description="Métadonnées QR code", nullable=true),
     *             @OA\Property(property="description", type="string", description="Description de la transaction", nullable=true, example="Paiement de facture")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction OM Pay créée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                 @OA\Property(property="recipient_phone", type="string", nullable=true, example=null),
     *                 @OA\Property(property="type", type="string", enum={"transfer", "payment"}, example="payment"),
     *                 @OA\Property(property="amount", type="number", format="float", example=500),
     *                 @OA\Property(property="status", type="string", enum={"pending", "success", "failed"}, example="success"),
     *                 @OA\Property(property="reference", type="string", nullable=true, example=null),
     *                 @OA\Property(property="merchant_id", type="string", example="merchant_123"),
     *                 @OA\Property(property="service_id", type="string", example="service_456"),
     *                 @OA\Property(property="fee", type="number", format="float", example=5),
     *                 @OA\Property(property="description", type="string", example="Paiement de facture"),
     *                 @OA\Property(property="transaction_date", type="string", format="date-time", example="2025-11-10T15:35:00.373000Z"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="id", type="string", example="69120624c02cc5e99b075eb2")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création de la transaction OM Pay"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="bearerAuth",
     *         type="apiKey",
     *         name="Authorization",
     *         in="header"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Logique de création de transaction
    }

    /**
     * @OA\Get(
     *     path="/ompay/transactions/stats",
     *     tags={"Transactions OM Pay"},
     *     summary="Récupérer les statistiques des transactions",
     *     description="Retourne les statistiques des transactions OM Pay de l'utilisateur",
     *     operationId="getTransactionStats",
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_transactions", type="integer", example=25),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=12500),
     *                 @OA\Property(property="successful_transactions", type="integer", example=23),
     *                 @OA\Property(property="failed_transactions", type="integer", example=2),
     *                 @OA\Property(property="pending_transactions", type="integer", example=0),
     *                 @OA\Property(property="average_transaction", type="number", format="float", example=500),
     *                 @OA\Property(property="monthly_stats", type="object",
     *                     @OA\Property(property="current_month", type="object",
     *                         @OA\Property(property="count", type="integer", example=5),
     *                         @OA\Property(property="amount", type="number", format="float", example=2500)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="bearerAuth",
     *         type="apiKey",
     *         name="Authorization",
     *         in="header"
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        // Logique de récupération des statistiques
    }
}
```

### 4. Génération automatique de la documentation

#### Commande de génération
```bash
php artisan l5-swagger:generate
```

Cette commande :
- Analyse tous les fichiers dans `app/Http/Controllers`
- Extrait les annotations Swagger
- Génère le fichier `storage/api-docs/api-docs.json`
- Génère optionnellement le fichier YAML

### 5. Interface Swagger UI

#### Accès à l'interface
L'interface Swagger UI est accessible via l'URL configurée :
```
http://localhost:8000/api/documentation
```

#### Fonctionnalités de l'interface
- **Exploration interactive** : Tous les endpoints sont listés avec leurs paramètres
- **Test en temps réel** : Possibilité de tester les endpoints directement depuis l'interface
- **Authentification Bearer** : Bouton "Authorize" pour entrer le token JWT
- **Documentation détaillée** : Descriptions, exemples de requêtes/réponses, codes de statut

### 6. Scripts personnalisés dans la vue Swagger

#### Auto-ouverture de l'autorisation
```javascript
onComplete: function() {
    console.log('=== SWAGGER UI DEBUG ===');
    console.log('Swagger UI chargé, recherche du bouton Authorize...');

    if (ui.spec().security && ui.spec().security.length > 0) {
        console.log('Sécurité définie, recherche du bouton...');

        setTimeout(function() {
            console.log('=== RECHERCHE BOUTON AUTHORIZE ===');

            // Recherche automatique du bouton Authorize
            var selectors = [
                '.btn.authorize',
                '.authorize',
                // ... autres sélecteurs
            ];

            var authBtn = null;
            for (var i = 0; i < selectors.length; i++) {
                authBtn = document.querySelector(selectors[i]);
                if (authBtn) {
                    console.log('Bouton trouvé avec sélecteur:', selectors[i]);
                    break;
                }
            }

            if (authBtn) {
                console.log('Bouton Authorize trouvé, clic automatique');
                authBtn.click();
            } else {
                console.log('Bouton Authorize non trouvé, recherche manuelle...');
                // Recherche manuelle de tous les boutons
            }
        }, 3000);
    }
}
```

### 7. Sécurité et authentification

#### Configuration Bearer Token
```php
'securityDefinitions' => [
    'securitySchemes' => [
        'bearerAuth' => [
            'type' => 'apiKey',
            'description' => 'Token JWT obtenu après authentification OM Pay. Format: Bearer {token}',
            'name' => 'Authorization',
            'in' => 'header',
        ],
    ],
    'security' => [
        [
            'bearerAuth' => [],
        ],
    ],
],
```

#### Application de la sécurité
Les endpoints protégés utilisent l'annotation :
```php
@OA\SecurityScheme(
    securityScheme="bearerAuth",
    type="apiKey",
    name="Authorization",
    in="header"
)
```

### 8. Structure des fichiers générés

#### Fichier JSON de documentation
Le fichier `storage/api-docs/api-docs.json` contient :
- Informations générales de l'API
- Liste de tous les serveurs
- Définition de tous les chemins (endpoints)
- Schémas de sécurité
- Composants réutilisables

#### Format OpenAPI 3.0
La documentation suit le standard OpenAPI 3.0 avec :
- `openapi: "3.0.0"`
- `info`, `servers`, `paths`, `components`
- Support complet des schémas JSON
- Validation automatique des requêtes/réponses

### 9. Avantages de cette implémentation

#### Pour les développeurs
- **Documentation automatique** : Mise à jour en temps réel avec le code
- **Interface interactive** : Test des endpoints sans outils externes
- **Validation** : Schémas stricts pour les requêtes/réponses
- **Standardisation** : Format OpenAPI reconnu par tous les outils

#### Pour l'API OM Pay
- **Authentification documentée** : Flux OTP → Code secret → Token JWT
- **Transactions détaillées** : Tous les paramètres et réponses documentés
- **Sécurité intégrée** : Authentification Bearer obligatoire
- **Exemples concrets** : Valeurs réelles pour les tests

### 10. Commandes utiles

#### Régénération de la documentation
```bash
php artisan l5-swagger:generate
```

#### Publication des vues (si nécessaire)
```bash
php artisan vendor:publish --tag=l5-swagger-views
```

#### Nettoyage du cache
```bash
php artisan config:clear
php artisan cache:clear
```

Cette implémentation fournit une documentation complète, interactive et maintenable pour l'API OM Pay, facilitant le développement, les tests et l'intégration pour tous les consommateurs de l'API.