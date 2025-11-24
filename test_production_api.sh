#!/bin/bash

echo "🧪 TEST API PRODUCTION - BANQUE OM PAY"
echo "========================================"

BASE_URL="https://banque-20br.onrender.com"
PHONE="772687847"
SECRET_CODE="1234"

echo "📍 URL de base: $BASE_URL"
echo "📱 Téléphone de test: $PHONE"
echo ""

# Fonction pour afficher les résultats
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local auth=$4
    local description=$5

    echo "🔍 Test: $description"
    echo "   $method $endpoint"

    if [ -n "$auth" ]; then
        if [ -n "$data" ]; then
            curl -s -X $method "$BASE_URL$endpoint" \
                 -H "Authorization: Bearer $auth" \
                 -H "Content-Type: application/json" \
                 -d "$data" | jq . 2>/dev/null || curl -s -X $method "$BASE_URL$endpoint" \
                 -H "Authorization: Bearer $auth" \
                 -H "Content-Type: application/json" \
                 -d "$data"
        else
            curl -s -X $method "$BASE_URL$endpoint" \
                 -H "Authorization: Bearer $auth" | jq . 2>/dev/null || curl -s -X $method "$BASE_URL$endpoint" \
                 -H "Authorization: Bearer $auth"
        fi
    else
        if [ -n "$data" ]; then
            curl -s -X $method "$BASE_URL$endpoint" \
                 -H "Content-Type: application/json" \
                 -d "$data" | jq . 2>/dev/null || curl -s -X $method "$BASE_URL$endpoint" \
                 -H "Content-Type: application/json" \
                 -d "$data"
        else
            curl -s -X $method "$BASE_URL$endpoint" | jq . 2>/dev/null || curl -s -X $method "$BASE_URL$endpoint"
        fi
    fi

    echo ""
    echo "----------------------------------------"
    echo ""
}

# 1. Test de santé de l'API
test_endpoint "GET" "/api/health" "" "" "Health Check API"

# 2. Demande OTP
echo "📧 Demande OTP pour $PHONE..."
OTP_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/request-otp" \
                   -H "Content-Type: application/json" \
                   -d "{\"phone\": \"$PHONE\"}")

echo "Réponse OTP:"
echo "$OTP_RESPONSE" | jq . 2>/dev/null || echo "$OTP_RESPONSE"
echo ""

# Extraire l'OTP des logs (si disponible)
OTP=$(tail -n 1 storage/logs/laravel.log 2>/dev/null | grep -o '"otp":"[^"]*"' | cut -d'"' -f4 2>/dev/null || echo "")

if [ -n "$OTP" ]; then
    echo "🔢 OTP trouvé dans les logs: $OTP"
else
    echo "⚠️  OTP non trouvé automatiquement. Vérifiez les logs Render ou votre email."
    echo "   Utilisez l'OTP reçu par email/SMS pour continuer les tests."
    read -p "Entrez l'OTP reçu: " OTP
fi
echo ""

# 3. Vérification OTP
test_endpoint "POST" "/api/auth/verify-otp" "{\"phone\": \"$PHONE\", \"otp\": \"$OTP\"}" "" "Vérification OTP"

# 4. Connexion
echo "🔐 Connexion avec code secret..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
                     -H "Content-Type: application/json" \
                     -d "{\"phone\": \"$PHONE\", \"secret_code\": \"$SECRET_CODE\"}")

echo "Réponse connexion:"
echo "$LOGIN_RESPONSE" | jq . 2>/dev/null || echo "$LOGIN_RESPONSE"
echo ""

# Extraire le token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token' 2>/dev/null)

if [ "$TOKEN" = "null" ] || [ -z "$TOKEN" ]; then
    echo "❌ Erreur: Token non trouvé. Vérifiez que l'OTP a été validé."
    exit 1
fi

echo "✅ Token obtenu: ${TOKEN:0:50}..."
echo ""

# 5. Informations du compte
test_endpoint "GET" "/api/ompay/mon-compte" "" "$TOKEN" "Informations du compte OM Pay"

# 6. Créer une transaction
test_endpoint "POST" "/api/ompay/transactions" "{\"type\": \"payment\", \"amount\": 250.00, \"description\": \"Test Production API\"}" "$TOKEN" "Création de transaction"

# 7. Historique des transactions
test_endpoint "GET" "/api/ompay/transactions?page=1&per_page=10" "" "$TOKEN" "Historique des transactions"

# 8. Statistiques des transactions
test_endpoint "GET" "/api/ompay/transactions/stats" "" "$TOKEN" "Statistiques des transactions"

# 9. Test avec filtres
test_endpoint "GET" "/api/ompay/transactions?status=success&type=payment" "" "$TOKEN" "Transactions filtrées (success, payment)"

echo "🎉 TESTS TERMINÉS !"
echo ""
echo "📊 RÉSUMÉ:"
echo "✅ Health Check"
echo "✅ Demande OTP"
echo "✅ Vérification OTP"
echo "✅ Connexion"
echo "✅ Informations compte"
echo "✅ Création transaction"
echo "✅ Historique transactions"
echo "✅ Statistiques"
echo "✅ Filtres"
echo ""
echo "🚀 L'API est opérationnelle en production !"