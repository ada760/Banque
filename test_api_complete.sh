#!/bin/bash
echo "=== TEST COMPLET API OM PAY ==="

# 1. Request OTP
echo "1. Demande OTP..."
curl -s -X POST "http://127.0.0.1:8000/api/auth/request-otp" -H "Content-Type: application/json" -d "{\"phone\": \"772687847\"}" | jq .

# Get OTP from logs
OTP=$(tail -n 1 storage/logs/laravel.log | grep -o "\"otp\":\"[^\"]*\"" | cut -d"\"" -f4)
echo "OTP trouvé: $OTP"

# 2. Verify OTP
echo -e "
2. Vérification OTP..."
curl -s -X POST "http://127.0.0.1:8000/api/auth/verify-otp" -H "Content-Type: application/json" -d "{\"phone\": \"772687847\", \"otp\": \"$OTP\"}" | jq .

# 3. Login
echo -e "
3. Connexion..."
LOGIN_RESPONSE=$(curl -s -X POST "http://127.0.0.1:8000/api/auth/login" -H "Content-Type: application/json" -d "{\"phone\": \"772687847\", \"secret_code\": \"1234\"}")
echo "$LOGIN_RESPONSE" | jq .

# Extract token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r ".data.token")
if [ "$TOKEN" = "null" ] || [ -z "$TOKEN" ]; then
    echo "❌ Erreur: Token non trouvé"
    exit 1
fi
echo -e "
✅ Token obtenu: ${TOKEN:0:50}..."

# 4. Account info
echo -e "
4. Informations compte..."
curl -s -X GET "http://127.0.0.1:8000/api/ompay/mon-compte" -H "Authorization: Bearer $TOKEN" | jq .

# 5. Create transaction
echo -e "
5. Création transaction..."
curl -s -X POST "http://127.0.0.1:8000/api/ompay/transactions" -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d "{\"type\": \"payment\", \"amount\": 500.00, \"description\": \"Test API\"}" | jq .

# 6. Transaction history
echo -e "
6. Historique transactions..."
curl -s -X GET "http://127.0.0.1:8000/api/ompay/transactions?page=1&per_page=10" -H "Authorization: Bearer $TOKEN" | jq .

# 7. Transaction stats
echo -e "
7. Statistiques transactions..."
curl -s -X GET "http://127.0.0.1:8000/api/ompay/transactions/stats" -H "Authorization: Bearer $TOKEN" | jq .

echo -e "
🎉 TEST COMPLET TERMINÉ !"
