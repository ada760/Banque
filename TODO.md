# TODO - Correction Production Banque_Api

## ✅ 1. APP_KEY / Chiffrement
- [x] Modifier render-startup.sh pour générer une APP_KEY valide (32 caractères)
- [x] Vérifier que le cipher AES-256-CBC est compatible

## ✅ 2. Configuration Email OTP
- [x] Configurer les variables MAIL_* dans render-startup.sh
- [x] Modifier OtpService pour envoyer emails synchrones
- [x] Améliorer la gestion d'erreur email avec logging temporaire

## ✅ 3. Queues / Redis
- [x] Modifier config/queue.php pour utiliser Redis en production
- [x] Configurer Redis dans render-startup.sh

## ✅ 4. MongoDB
- [x] Vérifier configuration MongoDB dans render-startup.sh

## ✅ 5. Swagger Documentation
- [x] Générer la documentation Swagger dans render-startup.sh
- [x] Configurer l'accès public

## ✅ 6. Tests finaux
- [ ] Tester endpoint /api/login
- [ ] Tester endpoint /api/auth/request-otp
- [ ] Vérifier Swagger accessible
- [ ] Vérifier queues fonctionnelles
