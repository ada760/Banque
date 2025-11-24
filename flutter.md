# Guide d'Intégration Flutter - API OM Pay

## ✅ Problèmes Résolus et Testés avec Succès

J'ai identifié et corrigé les problèmes dans l'API Laravel. L'API fonctionne maintenant parfaitement.

### 🔍 Problèmes Identifiés et Corrigés

1. **Problème 1 : User ID incohérent**
   ```php
   // AVANT (PROBLÉMATIQUE) :
   $validated['user_id'] = $request->input('user_id', '459c5dba-b6d7-33c0-a2da-cbcbf0662512');

   // APRÈS (CORRIGÉ) :
   $validated['user_id'] = auth()->id();
   ```

2. **Problème 2 : Endpoint `/me` manquant**
   - Ajouté la route `GET /api/me` pour récupérer les infos utilisateur
   - Format de réponse adapté à Flutter

### ✅ Tests Réussis

J'ai testé le flux complet avec cURL :

1. **Authentification** : ✅ Réussie
2. **Récupération profil** (`/me`) : ✅ Réussie
3. **Création de transaction** : ✅ Réussie
4. **Récupération historique** : ✅ Réussie (transactions visibles)

---

## 🚀 Guide Complet d'Intégration Flutter

### 1. Flux d'Authentification Complet

```dart
class AuthService {
  final String baseUrl = 'http://localhost:8000/api';

  // Étape 1: Demander OTP
  Future<Map<String, dynamic>> requestOtp(String phone) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/request-otp'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'phone': phone}),
    );
    return jsonDecode(response.body);
  }

  // Étape 2: Vérifier OTP
  Future<Map<String, dynamic>> verifyOtp(String phone, String otp) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/verify-otp'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'phone': phone, 'otp': otp}),
    );
    return jsonDecode(response.body);
  }

  // Étape 3: Connexion avec code secret
  Future<Map<String, dynamic>> login(String phone, String secretCode) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'phone': phone,
        'secret_code': secretCode
      }),
    );

    final data = jsonDecode(response.body);
    if (data['success']) {
      // Stocker le token
      final token = data['data']['token'];
      await storage.write(key: 'auth_token', value: token);
    }
    return data;
  }
}
```

### 1.1. Récupération des Informations Utilisateur

#### Endpoint Backend
```
GET /api/me
Authorization: Bearer {token}
```

#### Réponse Attendue (Testée et Confirmée)
```json
{
  "success": true,
  "data": {
    "user": {
      "id": "22bb1cde-a1aa-3582-bbe0-f79a1b2aa22f",
      "name": "Moustapha",
      "phone_number": "772687847",
      "email": "seckmoustapha238@gmail.com"
    },
    "account": {
      "id": "6bb20559-39d8-3947-87c9-4e616bb21ff9",
      "balance": 6205.5,
      "currency": "USD",
      "status": "actif"
    }
  }
}
```

#### Code Flutter - Service Profil Utilisateur

```dart
class UserService {
  final String baseUrl = 'http://localhost:8000/api';

  Future<String?> getToken() async {
    return await storage.read(key: 'auth_token');
  }

  // Récupérer les informations de l'utilisateur connecté
  Future<Map<String, dynamic>> getUserProfile() async {
    final token = await getToken();
    if (token == null) throw Exception('Non authentifié');

    final response = await http.get(
      Uri.parse('$baseUrl/me'),
      headers: {'Authorization': 'Bearer $token'},
    );

    final data = jsonDecode(response.body);
    if (!data['success']) {
      throw Exception(data['message'] ?? 'Erreur de récupération du profil');
    }
    return data['data'];
  }
}
```

#### Code Flutter - Provider pour les Infos Utilisateur

```dart
class UserProvider extends ChangeNotifier {
  Map<String, dynamic>? _userData;
  bool _isLoading = false;

  Map<String, dynamic>? get userData => _userData;
  bool get isLoading => _isLoading;

  String? get userName => _userData?['user']?['name'];
  double get balance => _userData?['account']?['balance'] ?? 0.0;
  String get currency => _userData?['account']?['currency'] ?? 'XOF';

  // Charger le profil utilisateur
  Future<void> loadUserProfile() async {
    _isLoading = true;
    notifyListeners();

    try {
      final userService = UserService();
      _userData = await userService.getUserProfile();
      notifyListeners();
    } catch (e) {
      print('Erreur chargement profil: $e');
    }

    _isLoading = false;
    notifyListeners();
  }

  // Actualiser le profil
  Future<void> refreshProfile() async {
    await loadUserProfile();
  }
}
```

### 2. Service de Transactions

```dart
class TransactionService {
  final String baseUrl = 'http://localhost:8000/api';

  Future<String?> getToken() async {
    return await storage.read(key: 'auth_token');
  }

  // Créer un transfert
  Future<Map<String, dynamic>> createTransfer({
    required double amount,
    required String recipientPhone,
    String? description,
  }) async {
    final token = await getToken();
    if (token == null) throw Exception('Non authentifié');

    final response = await http.post(
      Uri.parse('$baseUrl/ompay/transactions'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'type': 'transfer',
        'amount': amount,
        'recipient_phone': recipientPhone,
        'description': description ?? 'Transfert OM Pay',
      }),
    );

    final data = jsonDecode(response.body);
    if (!data['success']) {
      throw Exception(data['message'] ?? 'Erreur de transfert');
    }
    return data;
  }

  // Créer un paiement
  Future<Map<String, dynamic>> createPayment({
    required double amount,
    required String merchantId,
    String? serviceId,
    String? description,
  }) async {
    final token = await getToken();
    if (token == null) throw Exception('Non authentifié');

    final response = await http.post(
      Uri.parse('$baseUrl/ompay/transactions'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'type': 'payment',
        'amount': amount,
        'merchant_id': merchantId,
        'service_id': serviceId,
        'description': description ?? 'Paiement OM Pay',
      }),
    );

    final data = jsonDecode(response.body);
    if (!data['success']) {
      throw Exception(data['message'] ?? 'Erreur de paiement');
    }
    return data;
  }

  // Récupérer les transactions
  Future<Map<String, dynamic>> getTransactions({
    int page = 1,
    int perPage = 10,
    String? type,
    String? status,
  }) async {
    final token = await getToken();
    if (token == null) throw Exception('Non authentifié');

    final queryParams = {
      'page': page.toString(),
      'per_page': perPage.toString(),
      if (type != null) 'type': type,
      if (status != null) 'status': status,
    };

    final uri = Uri.parse('$baseUrl/ompay/transactions').replace(queryParameters: queryParams);

    final response = await http.get(
      uri,
      headers: {'Authorization': 'Bearer $token'},
    );

    final data = jsonDecode(response.body);
    if (!data['success']) {
      throw Exception(data['message'] ?? 'Erreur de récupération');
    }
    return data;
  }

  // Obtenir les statistiques
  Future<Map<String, dynamic>> getStats() async {
    final token = await getToken();
    if (token == null) throw Exception('Non authentifié');

    final response = await http.get(
      Uri.parse('$baseUrl/ompay/transactions/stats'),
      headers: {'Authorization': 'Bearer $token'},
    );

    final data = jsonDecode(response.body);
    if (!data['success']) {
      throw Exception(data['message'] ?? 'Erreur de récupération des stats');
    }
    return data;
  }
}
```

### 3. Gestion des Erreurs

```dart
// Extension pour gérer les erreurs HTTP
extension HttpResponseExtension on http.Response {
  bool get isSuccess => statusCode >= 200 && statusCode < 300;

  Map<String, dynamic> get jsonData {
    try {
      return jsonDecode(body);
    } catch (e) {
      return {'success': false, 'message': 'Erreur de parsing JSON'};
    }
  }
}

// Classe d'exception personnalisée
class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, [this.statusCode]);

  @override
  String toString() => 'ApiException: $message';
}

// Méthode utilitaire pour gérer les réponses
Map<String, dynamic> handleApiResponse(http.Response response) {
  if (!response.isSuccess) {
    final data = response.jsonData;
    throw ApiException(
      data['message'] ?? 'Erreur HTTP ${response.statusCode}',
      response.statusCode
    );
  }

  final data = response.jsonData;
  if (!data['success']) {
    throw ApiException(data['message'] ?? 'Erreur API');
  }

  return data;
}
```

### 4. Utilisation dans les Widgets Flutter

```dart
class TransactionScreen extends StatefulWidget {
  @override
  _TransactionScreenState createState() => _TransactionScreenState();
}

class _TransactionScreenState extends State<TransactionScreen> {
  final TransactionService _transactionService = TransactionService();
  List<dynamic> _transactions = [];
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadTransactions();
  }

  Future<void> _loadTransactions() async {
    setState(() => _isLoading = true);
    try {
      final result = await _transactionService.getTransactions();
      setState(() {
        _transactions = result['data'];
      });
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _createTransfer() async {
    // Afficher un dialog pour saisir les détails
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) => TransferDialog(),
    );

    if (result != null) {
      try {
        await _transactionService.createTransfer(
          amount: result['amount'],
          recipientPhone: result['recipientPhone'],
          description: result['description'],
        );

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Transfert réussi!')),
        );

        _loadTransactions(); // Recharger la liste
      } catch (e) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Transactions OM Pay'),
        actions: [
          IconButton(
            icon: Icon(Icons.add),
            onPressed: _createTransfer,
          ),
        ],
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator())
          : ListView.builder(
              itemCount: _transactions.length,
              itemBuilder: (context, index) {
                final transaction = _transactions[index];
                return ListTile(
                  title: Text('${transaction['type']} - ${transaction['amount']} XOF'),
                  subtitle: Text(transaction['description'] ?? ''),
                  trailing: Text(transaction['status']),
                );
              },
            ),
    );
  }
}
```

### 5. Points Critiques pour Flutter

1. **Toujours vérifier l'authentification** avant les appels API
2. **Gérer les erreurs HTTP** (401, 422, 500, etc.)
3. **Parser correctement les réponses JSON**
4. **Utiliser les bons headers** (`Authorization: Bearer {token}`)
5. **Valider les données** côté client avant envoi
6. **Gérer les états de chargement** et erreurs dans l'UI

### 6. Structure des Données Attendues

#### Transfert :
```json
{
  "type": "transfer",
  "amount": 1000.00,
  "recipient_phone": "770123456",
  "description": "Transfert d'argent"
}
```

#### Paiement :
```json
{
  "type": "payment",
  "amount": 2500.00,
  "merchant_id": "merchant_123",
  "description": "Paiement de facture"
}
```

#### Réponse de récupération :
```json
{
  "success": true,
  "data": [
    {
      "id": "69239b6fe2d238d4170014b2",
      "type": "payment",
      "amount": "500.00",
      "status": "success",
      "description": "Test paiement",
      "transaction_date": "2025-11-23T23:40:31.293000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 1,
    "last_page": 1
  }
}
```

### 7. Dépendances Flutter Nécessaires

Ajoutez ces dépendances dans votre `pubspec.yaml` :

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  flutter_secure_storage: ^9.0.0
  provider: ^6.0.5
```

### 8. Configuration du Stockage Sécurisé

```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

const storage = FlutterSecureStorage();
```

### 9. Gestion d'État avec Provider (Optionnel mais Recommandé)

```dart
class AuthProvider extends ChangeNotifier {
  String? _token;

  String? get token => _token;

  Future<void> login(String phone, String secretCode) async {
    final authService = AuthService();
    final result = await authService.login(phone, secretCode);

    if (result['success']) {
      _token = result['data']['token'];
      await storage.write(key: 'auth_token', value: _token);
      notifyListeners();
    }
  }

  Future<void> logout() async {
    _token = null;
    await storage.delete(key: 'auth_token');
    notifyListeners();
  }

  Future<void> checkAuth() async {
    _token = await storage.read(key: 'auth_token');
    notifyListeners();
  }
}
```

---

## 📋 Checklist d'Intégration

- [ ] Ajouter les dépendances HTTP et stockage sécurisé
- [ ] Implémenter le service d'authentification
- [ ] Implémenter le service de transactions
- [ ] Créer les écrans UI pour les transferts et paiements
- [ ] Gérer les états de chargement et erreurs
- [ ] Tester tous les flux (auth + transactions)
- [ ] Implémenter la gestion des tokens expirés

Maintenant que l'API est corrigée, Flutter devrait pouvoir créer et récupérer les transactions sans problème. Le développeur Flutter peut utiliser ce code comme base pour l'intégration complète.