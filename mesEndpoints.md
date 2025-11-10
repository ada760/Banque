# API OM Pay - Documentation des Endpoints
genration code qr le code qr c'est pour un compte

## Base URL
```
http://localhost:8000/api
```

## Authentification
Tous les endpoints OM Pay nécessitent une authentification via Bearer Token.

### 1. Connexion (Login)
**Endpoint:** `POST /login`

**Description:** Authentifier un utilisateur et obtenir un token JWT.

**Corps de la requête - Admin:**
```json
{
  "email": "admin@gmail.com",
  "password": "password"
}
```

**Corps de la requête - Client sénégalais:**
```json
{
  "email": "aïssatou.diallo543@gmail.com",
  "password": "password"
}
```

**Exemple de réponse réussie - Admin:**
```json
{
  "data": {
    "id": "uuid-admin",
    "titulaire": "Administrateur Principal",
    "email": "admin@gmail.com",
    "role": "admin"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer"
}
```

**Exemple de réponse réussie - Client sénégalais:**
```json
{
  "data": {
    "id": "uuid-client",
    "titulaire": "Aïssatou Diallo",
    "email": "aïssatou.diallo543@gmail.com",
    "role": "client"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer"
}
```

---

## Endpoints OM Pay

### Headers requis pour tous les endpoints OM Pay
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

### 2. Créer une Transaction OM Pay
**Endpoint:** `POST /ompay/transactions`

**Description:** Créer une nouvelle transaction OM Pay. Nécessite un compte bancaire actif.

**Types de transactions supportés:**
- `transfer` : Transfert d'argent vers un autre numéro
- `payment` : Paiement de services/marchands
- `recharge` : Recharge de compte

**Corps de la requête - Transfert:**
```json
{
  "type": "transfer",
  "amount": 500,
  "recipient_phone": "795058521",
  "description": "Transfert d'argent"
}
```

**Corps de la requête - Paiement:**
```json
{
  "type": "payment",
  "amount": 2500,
  "merchant_id": "merchant_123",
  "service_id": "service_456",
  "description": "Paiement de facture"
}
```

**Corps de la requête - Recharge:**
```json
{
  "type": "recharge",
  "amount": 1000,
  "description": "Recharge de compte"
}
```

**Paramètres optionnels:**
- `reference` : Référence personnalisée (string)
- `qr_metadata` : Métadonnées QR (array)

**Exemple de réponse réussie:**
```json
{
  "success": true,
  "message": "Transaction OM Pay créée avec succès",
  "data": {
    "user_id": "4f571fff-16ea-3689-b136-54e579cc3afe",
    "recipient_phone": "795058521",
    "type": "transfer",
    "amount": "500.00",
    "status": "success",
    "fee": "2.50",
    "description": "Transfert d'argent",
    "transaction_date": "2025-11-09T18:50:36.636000Z",
    "id": "6910e27c84c75dbcda0797f2"
  }
}
```

---

### 3. Lister les Transactions de l'Utilisateur
**Endpoint:** `GET /ompay/transactions`

**Description:** Récupérer la liste des transactions de l'utilisateur connecté.

**Paramètres de requête (optionnels):**
- `limit` : Nombre maximum de transactions (défaut: 10)

**Exemple:** `GET /ompay/transactions?limit=20`

**Exemple de réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": "6910e27c84c75dbcda0797f2",
      "user_id": "4f571fff-16ea-3689-b136-54e579cc3afe",
      "recipient_phone": "795058521",
      "type": "transfer",
      "amount": "500.00",
      "status": "success",
      "fee": "2.50",
      "description": "Transfert d'argent",
      "transaction_date": "2025-11-09T18:50:36.636000Z"
    }
  ]
}
```

---

### 4. Statistiques des Transactions
**Endpoint:** `GET /ompay/transactions/stats`

**Description:** Obtenir les statistiques des transactions de l'utilisateur.

**Exemple de réponse:**
```json
{
  "success": true,
  "data": {
    "total_transactions": 5,
    "total_amount": 2500.00,
    "successful_transactions": 5
  }
}
```

---

## Clients de Test Disponibles

Voici quelques clients avec comptes actifs pour vos tests :

### Admin
- **Email:** `admin@gmail.com`
- **Mot de passe:** `password`
- **Rôle:** Administrateur principal

### Exemples de clients sénégalais (générés automatiquement)
Les clients sont maintenant générés avec des données 100% sénégalaises :

#### Caractéristiques des données :
- **Prénoms sénégalais :** Moussa, Moustapha, Babacar, Abdoulaye, Ibrahima, Cheikh, Omar, Amadou, Saliou, Modou, Pape, Serigne, El Hadji, Mamadou, Alioune, Samba, Lamine, Souleymane, Malick, Thierno (hommes)
- **Prénoms sénégalais :** Fatou, Aïssatou, Khady, Mariama, Aminata, Adama, Ndeye, Seynabou, Mame, Astou, Penda, Dior, Sokhna, Maimouna, Hawa, Rokhaya, Yacine, Awa, Oumou, Khadija (femmes)
- **Noms de famille :** Ndiaye, Diop, Sarr, Fall, Seck, Gueye, Ba, Sow, Kane, Sy, Mbaye, Thiam, Dia, Wade, Diagne, Faye, Sall, Niang, Cisse, Traore, Diallo, Camara, Barry, Keita
- **Numéros OM :** Format 77XXXXXXX ou 78XXXXXXX (9 chiffres)
- **Villes :** Dakar, Thiès, Saint-Louis, Kaolack, Mbour, Ziguinchor, Diourbel, Louga, Tambacounda, Kolda, Matam, Kaffrine, Fatick, Sédhiou, Kédougou, Keur Massar, Guédiawaye, Rufisque, Bargny, Joal-Fadiouth
- **Emails :** prénom.nomXXX@domaine (avec suffixe numérique pour unicité)

#### Exemples de clients réels dans votre base :
- **Aïssatou Diallo** - `aïssatou.diallo543@gmail.com` - `778245544` - 13,278 XOF
- **Penda Traore** - `penda.traore179@outlook.com` - `778213196` - 20,562 XOF
- **Thierno Camara** - `thierno.camara968@outlook.com` - `788604922` - 19,675 XOF
- **Awa Ba** - `awa.ba235@hotmail.com` - `778763537` - 42,303 XOF
- **Yacine Faye** - `yacine.faye127@outlook.com` - `771387167` - 20,028 XOF

**Note :** Les données exactes varient à chaque génération car elles sont créées aléatoirement à partir des listes sénégalaises.

---

## Codes d'Erreur

### Erreurs d'Authentification
- `401 Unauthorized` : Token manquant ou invalide

### Erreurs de Validation
- `422 Unprocessable Entity` : Données invalides
- `400 Bad Request` : Requête malformée

### Erreurs Métier
- `403 Forbidden` : Compte non actif ou permissions insuffisantes
- `404 Not Found` : Ressource non trouvée

### Erreurs Système
- `500 Internal Server Error` : Erreur serveur

---

## Règles Métier

### Conditions pour créer une transaction :
1. L'utilisateur doit être authentifié
2. L'utilisateur doit avoir un compte bancaire actif
3. **Le solde disponible doit être suffisant** (montant + frais)
4. Pour les transferts : le destinataire doit exister et avoir un compte actif
5. Le montant minimum est de 100 XOF
6. L'utilisateur ne peut pas se transférer à lui-même
7. **Le solde est automatiquement débité après transaction réussie**

### Frais de transaction :
- **Transfert :** 0.5% du montant (débité de l'expéditeur)
- **Paiement :** 1% du montant (débité de l'expéditeur)
- **Recharge :** Gratuit (0%) - **AJOUTE** au solde du compte

### Mise à jour des soldes :
- **Transfert :** Expéditeur débité (montant + frais), destinataire crédité (montant seulement)
- **Paiement :** Expéditeur débité (montant + frais)
- **Recharge :** Expéditeur crédité (montant seulement, pas de frais)

### Statuts de transaction :
- `pending` : En attente
- `success` : Réussie
- `failed` : Échouée

---

## Exemples de Tests avec cURL

### 1. Connexion Admin
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@gmail.com","password":"password"}'
```

### 2. Connexion Client (utiliser un client réel de votre base)
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"aïssatou.diallo543@gmail.com","password":"password"}'
```

### 3. Créer un transfert (utiliser un numéro réel de votre base)
```bash
curl -X POST http://localhost:8000/api/ompay/transactions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"type":"transfer","amount":5000,"recipient_phone":"778213196","description":"Transfert OM Pay"}'
```

### 4. Créer une recharge (AJOUTE au solde)
```bash
curl -X POST http://localhost:8000/api/ompay/transactions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"type":"recharge","amount":5000,"description":"Recharge OM Pay"}'
```

### 3. Lister les transactions
```bash
curl -X GET http://localhost:8000/api/ompay/transactions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 4. Obtenir les statistiques
```bash
curl -X GET http://localhost:8000/api/ompay/transactions/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Configuration Postman

### Variables d'environnement recommandées :
- `base_url` : `http://localhost:8000/api`
- `token` : (à définir après la connexion)

### Collection Postman :
1. **Login** : POST `{{base_url}}/login`
2. **Create Transaction** : POST `{{base_url}}/ompay/transactions`
3. **List Transactions** : GET `{{base_url}}/ompay/transactions`
4. **Transaction Stats** : GET `{{base_url}}/ompay/transactions/stats`

Utilisez le token obtenu lors du login dans l'header `Authorization: Bearer {{token}}` pour tous les endpoints OM Pay.