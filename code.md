

### 1. Imports et Classe API

```dart
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;

class OmPayAPI {
  final String baseUrl = 'http://localhost:8000/api';
  String? _token;

  // Headers avec token JWT
  Map<String, String> get _headers => {
    'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  // Gestion d'erreurs
  void _handleError(http.Response response) {
    if (response.statusCode != 200 && response.statusCode != 201) {
      try {
        final data = jsonDecode(response.body);
        throw Exception(data['message'] ?? 'Erreur API');
      } catch (e) {
        throw Exception('Erreur HTTP ${response.statusCode}');
      }
    }
  }

  // 1. Demander OTP (comme dans Swagger)
  Future<Map<String, dynamic>> requestOtp(String phone) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/request-otp'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'phone': phone}),
    );

    _handleError(response);
    return jsonDecode(response.body);
  }

  // 2. Vérifier OTP (comme dans Swagger)
  Future<Map<String, dynamic>> verifyOtp(String otp) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/verify-otp'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'otp': otp}),
    );

    _handleError(response);
    return jsonDecode(response.body);
  }

  // 3. Se connecter (comme dans Swagger)
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
    _handleError(response);

    // Sauvegarder le token
    if (data['success'] == true && data['data'] != null) {
      _token = data['data']['token'];
    }

    return data;
  }

  // 4. Dashboard client (comme dans Swagger)
  Future<Map<String, dynamic>> getDashboard() async {
    final response = await http.get(
      Uri.parse('$baseUrl/clients/dashboard'),
      headers: _headers,
    );

    _handleError(response);
    return jsonDecode(response.body);
  }

  // 5. Transactions d'un compte (comme dans Swagger)
  Future<Map<String, dynamic>> getTransactionsByCompte(String compteId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/ompay/comptes/$compteId/transactions'),
      headers: _headers,
    );

    _handleError(response);
    return jsonDecode(response.body);
  }

  // 6. Créer un compte (comme dans Swagger)
  Future<Map<String, dynamic>> createAccount(Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/comptes'),
      headers: _headers,
      body: jsonEncode(data),
    );

    _handleError(response);
    return jsonDecode(response.body);
  }

  // 7. Lister les comptes (comme dans Swagger)
  Future<Map<String, dynamic>> listAccounts() async {
    final response = await http.get(
      Uri.parse('$baseUrl/comptes'),
      headers: _headers,
    );

    _handleError(response);
    return jsonDecode(response.body);
  }

  // 8. Créer une transaction (transfert/paiement)
  Future<Map<String, dynamic>> createTransaction(Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/ompay/transactions'),
      headers: _headers,
      body: jsonEncode(data),
    );

    _handleError(response);
    return jsonDecode(response.body);
  }
}
```

### 2. Fonctions Utilitaires

```dart
// Affichage du menu principal
void displayMenu() {
  print('\n🏦 MENU PRINCIPAL OM PAY');
  print('========================');
  print('1. Créer Compte');
  print('2. Liste de Comptes');
  print('3. Faire un TransFert');
  print('4. Lister les Transactions des Comptes');
  print('5. Quitter');
  stdout.write('Choix : ');
}

// Lecture du choix utilisateur
int getUserChoice() {
  while (true) {
    final input = stdin.readLineSync();
    final choice = int.tryParse(input ?? '');
    if (choice != null && choice >= 1 && choice <= 5) {
      return choice;
    }
    stdout.write('Choix invalide (1-5) : ');
  }
}

// Lecture d'une chaîne
String getUserInput(String prompt) {
  stdout.write('$prompt : ');
  return stdin.readLineSync() ?? '';
}

// Lecture d'un nombre
double? getUserAmount(String prompt) {
  while (true) {
    final input = getUserInput(prompt);
    final amount = double.tryParse(input);
    if (amount != null && amount > 0) {
      return amount;
    }
    print('Montant invalide. Réessayez.');
  }
}
```

### 3. Authentification OmPay (Flux Complet)

```dart
// Authentification complète comme dans Swagger
Future<void> authenticateUser(OmPayAPI api) async {
  print('🔐 Authentification OmPay');
  print('========================');

  // Étape 1: Demander OTP
  final phone = getUserInput('📱 Numéro de téléphone');
  print('📤 Demande d\'OTP en cours...');

  try {
    final otpResponse = await api.requestOtp(phone);
    print('✅ ${otpResponse['message']}');
  } catch (e) {
    print('❌ Erreur OTP: $e');
    exit(1);
  }

  // Étape 2: Vérifier OTP
  final otp = getUserInput('🔐 Code OTP reçu par email');
  print('🔍 Vérification de l\'OTP...');

  try {
    final verifyResponse = await api.verifyOtp(otp);
    if (verifyResponse['success'] == true) {
      print('✅ ${verifyResponse['message']}');
    } else {
      print('❌ ${verifyResponse['message']}');
      exit(1);
    }
  } catch (e) {
    print('❌ Erreur vérification: $e');
    exit(1);
  }

  // Étape 3: Se connecter
  final secretCode = getUserInput('🔑 Code secret (4 chiffres)');
  print('🔐 Connexion en cours...');

  try {
    final loginResponse = await api.login(phone, secretCode);
    if (loginResponse['success'] == true) {
      print('✅ ${loginResponse['message']}');
      print('👤 Bienvenue ${loginResponse['data']['client']['user']['titulaire']}!');
      print('💰 Solde: ${loginResponse['data']['compte_actif']['solde']} ${loginResponse['data']['compte_actif']['devise']}');
    } else {
      print('❌ ${loginResponse['message']}');
      exit(1);
    }
  } catch (e) {
    print('❌ Erreur connexion: $e');
    exit(1);
  }
}
```

### 4. Fonctions des Options du Menu

```dart
// Option 1: Créer un compte
Future<void> createAccountFlow(OmPayAPI api) async {
  print('\n📝 CRÉATION DE COMPTE');
  print('===================');

  final phone = getUserInput('📱 Numéro de téléphone');
  final devise = getUserInput('💱 Devise (USD, EUR, XOF)');

  try {
    final result = await api.createAccount({
      'phone': phone,
      'devise': devise,
      'type': 'epargne' // ou autre type
    });
    print('✅ Compte créé avec succès!');
    print('🆔 ID: ${result['data']['id']}');
    print('🏦 Numéro: ${result['data']['num_compte']}');
  } catch (e) {
    print('❌ Erreur création compte: $e');
  }
}

// Option 2: Lister les comptes
Future<void> listAccountsFlow(OmPayAPI api) async {
  print('\n📋 LISTE DES COMPTES');
  print('==================');

  try {
    final result = await api.listAccounts();
    if (result['data'] != null && result['data'].isNotEmpty) {
      print('📊 Comptes trouvés: ${result['data'].length}');
      for (var compte in result['data']) {
        print('• ID: ${compte['id']} | Numéro: ${compte['num_compte']} | Solde: ${compte['solde']} ${compte['devise']} | Status: ${compte['status']}');
      }
    } else {
      print('📭 Aucun compte trouvé');
    }
  } catch (e) {
    print('❌ Erreur liste comptes: $e');
  }
}

// Option 3: Faire un transfert
Future<void> transferFlow(OmPayAPI api) async {
  print('\n💸 FAIRE UN TRANSFERT');
  print('===================');

  final recipientPhone = getUserInput('📱 Numéro destinataire');
  final amount = getUserAmount('💰 Montant à transférer');

  if (amount == null) return;

  try {
    final result = await api.createTransaction({
      'type': 'transfer',
      'amount': amount,
      'recipient_phone': recipientPhone,
      'description': 'Transfert via app console'
    });
    print('✅ Transfert réussi!');
    print('🆔 Référence: ${result['data']['id']}');
    print('💰 Montant: ${result['data']['amount']}');
  } catch (e) {
    print('❌ Erreur transfert: $e');
  }
}

// Option 4: Lister les transactions
Future<void> listTransactionsFlow(OmPayAPI api) async {
  print('\n📜 HISTORIQUE DES TRANSACTIONS');
  print('=============================');

  final compteId = getUserInput('🆔 ID du compte');

  try {
    final result = await api.getTransactionsByCompte(compteId);
    if (result['data'] != null && result['data'].isNotEmpty) {
      print('📊 Transactions trouvées: ${result['data'].length}');
      for (var tx in result['data']) {
        print('• ${tx['type']} | ${tx['amount']} | ${tx['status']} | ${tx['transaction_date']}');
        if (tx['description'] != null) {
          print('  └─ ${tx['description']}');
        }
      }
    } else {
      print('📭 Aucune transaction trouvée');
    }
  } catch (e) {
    print('❌ Erreur transactions: $e');
  }
}

// Option 5: Quitter
void quitApp() {
  print('\n👋 Au revoir ! Merci d\'avoir utilisé OmPay.');
  exit(0);
}
```

### 5. Fonction Main (Point d'entrée)

```dart
void main() async {
  print('🚀 Application Console OmPay');
  print('===========================');
  print('Connexion à l\'API Laravel: http://localhost:8000');

  final api = OmPayAPI();

  // Authentification complète (comme dans Swagger)
  await authenticateUser(api);

  // Menu principal en boucle
  while (true) {
    displayMenu();
    final choice = getUserChoice();

    switch (choice) {
      case 1:
        await createAccountFlow(api);
        break;
      case 2:
        await listAccountsFlow(api);
        break;
      case 3:
        await transferFlow(api);
        break;
      case 4:
        await listTransactionsFlow(api);
        break;
      case 5:
        quitApp();
        break;
    }

    // Pause avant de revenir au menu
    print('\nAppuyez sur Entrée pour continuer...');
    stdin.readLineSync();
  }
}
```

## 🚀 Comment Utiliser

1. **Créer un projet Dart séparé** :
```bash
mkdir ompay_console
cd ompay_console
dart create -t console ompay_console
cd ompay_console
dart pub add http
```
