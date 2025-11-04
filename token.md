# Documentation Technique - Authentification et Autorisation avec Passport

## Vue d'ensemble
Ce document décrit l'implémentation de l'authentification et de l'autorisation dans l'API bancaire utilisant Laravel Passport avec OAuth 2.0.

## Configuration Passport

### Installation et Configuration
1. **Installation de Passport** :
   ```bash
   composer require laravel/passport
   php artisan migrate
   php artisan passport:install
   ```

2. **Configuration des clients OAuth** :
   - Client personnel (Personal Access Client)
   - Client de mot de passe (Password Grant Client)

### Modèles et Relations

#### User Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'id',
        'titulaire',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function client(): HasOne {
        return $this->hasOne(Client::class);
    }

    public function admin(): HasOne {
        return $this->hasOne(Admin::class);
    }
}
```

#### Admin Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
```

#### Client Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'telephone',
        'cni',
        'adresse',
        'user_id'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function compte(): HasMany {
        return $this->hasMany(Compte::class);
    }
}
```

## Middleware d'Autorisation

### RoleMiddleware
Le middleware `RoleMiddleware` vérifie les rôles des utilisateurs connectés.

```php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Vérification du rôle admin
        if (in_array('admin', $roles) && $user->admin) {
            return $next($request);
        }

        // Vérification du rôle client
        if (in_array('client', $roles) && $user->client) {
            return $next($request);
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }
}
```

### Enregistrement du Middleware
Dans `app/Http/Kernel.php` :
```php
protected $middlewareAliases = [
    // ... autres middlewares
    'role' => \App\Http\Middleware\RoleMiddleware::class,
];
```

## Politiques (Policies)

### ComptePolicy
La politique `ComptePolicy` définit les autorisations pour les opérations sur les comptes.

```php
<?php
namespace App\Policies;

use App\Models\Compte;
use App\Models\User;

class ComptePolicy
{
    public function viewAny(User $user): bool
    {
        // Admin peut voir tous les comptes, client peut voir ses comptes
        return $user->admin || $user->client;
    }

    public function view(User $user, Compte $compte): bool
    {
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Seuls les admins peuvent créer des comptes
        return $user->admin;
    }

    public function update(User $user, Compte $compte): bool
    {
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }

    public function delete(User $user, Compte $compte): bool
    {
        if ($user->admin) {
            return true;
        }

        if ($user->client) {
            return $compte->client_id === $user->client->id;
        }

        return false;
    }
}
```

## Contrôleurs

### AuthController
Gère l'authentification des utilisateurs.

```php
<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Création du token
            $token = $user->createToken('Personal Access Token')->accessToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
```

### CompteController
Gère les opérations CRUD sur les comptes avec autorisation via les politiques.

```php
<?php
namespace App\Http\Controllers;

use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompteController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Compte::class);

        $user = $request->user();

        if ($user->admin) {
            $comptes = Compte::all();
        } else {
            $comptes = Compte::where('client_id', $user->client->id)->get();
        }

        return response()->json($comptes);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Compte::class);

        $validated = $request->validate([
            'num_compte' => 'required|string|unique:comptes',
            'devise' => 'required|string',
            'type' => 'required|in:cheque,epargne',
        ]);

        $validated['client_id'] = $request->user()->client->id;
        $validated['status'] = 'active';

        $compte = Compte::create($validated);

        return response()->json($compte, 201);
    }

    public function show(Request $request, Compte $compte)
    {
        Gate::authorize('view', $compte);
        return response()->json($compte);
    }

    public function update(Request $request, Compte $compte)
    {
        Gate::authorize('update', $compte);

        $validated = $request->validate([
            'num_compte' => 'sometimes|string|unique:comptes,num_compte,' . $compte->id,
            'devise' => 'sometimes|string',
            'type' => 'sometimes|in:cheque,epargne',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $compte->update($validated);

        return response()->json($compte);
    }

    public function destroy(Request $request, Compte $compte)
    {
        Gate::authorize('delete', $compte);
        $compte->delete();
        return response()->json(['message' => 'Account deleted successfully']);
    }
}
```

## Requêtes de Validation

### LoginRequest
```php
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
```

## Routes API

### Configuration des Routes
Dans `routes/api.php` :
```php
<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use Illuminate\Support\Facades\Route;

// Routes d'authentification
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:api');

// Routes des comptes (protégées par authentification)
Route::middleware(['auth:api'])->group(function () {
    Route::apiResource('comptes', CompteController::class);
});
```

## Utilisation de l'API

### 1. Connexion (Login)
```bash
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

**Réponse réussie :**
```json
{
    "user": {
        "id": "uuid",
        "titulaire": "John Doe",
        "email": "user@example.com",
        "created_at": "...",
        "updated_at": "..."
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer"
}
```

### 2. Utilisation du Token
Pour toutes les requêtes suivantes, inclure le token dans l'en-tête Authorization :
```
Authorization: Bearer {token}
```

### 3. Récupération des Comptes
```bash
GET /api/comptes
Authorization: Bearer {token}
```

**Admin :** Voit tous les comptes
**Client :** Voit uniquement ses comptes

### 4. Création d'un Compte (Client uniquement)
```bash
POST /api/comptes
Authorization: Bearer {token}
Content-Type: application/json

{
    "num_compte": "123456789",
    "devise": "XOF",
    "type": "cheque"
}
```

### 5. Déconnexion (Logout)
```bash
POST /api/logout
Authorization: Bearer {token}
```

## Permissions par Rôle

### Administrateur (Admin)
- Peut voir tous les comptes
- Peut modifier tous les comptes
- Peut supprimer tous les comptes

### Client
- Peut voir ses propres comptes
- Peut modifier ses propres comptes
- Peut supprimer ses propres comptes

## Sécurité
- Utilisation d'OAuth 2.0 avec Passport
- Tokens JWT pour l'authentification
- Politiques pour l'autorisation granulaire
- Middleware pour la vérification des rôles
- Validation des données d'entrée
- Hachage des mots de passe

## Notes Techniques
- Les UUID sont utilisés pour les identifiants des utilisateurs et clients
- Soft delete activé pour les comptes
- Relations Eloquent définies entre User, Admin, Client et Compte
- Utilisation des Gates et Policies pour l'autorisation
- Middleware personnalisé pour la gestion des rôles
