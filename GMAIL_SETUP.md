# Configuration Gmail SMTP - Instructions

## 🔧 Problème identifié
Le mot de passe Gmail normal ne fonctionne pas avec les applications tierces. Vous devez utiliser une **"App Password"**.

## 📋 Étapes pour configurer Gmail SMTP

### 1. Activer la vérification en 2 étapes (2FA)
- Allez sur https://myaccount.google.com/security
- Activez "Authentification à deux facteurs"

### 2. Générer une App Password
- Allez sur https://myaccount.google.com/apppasswords
- Sélectionnez:
  - App: "Autre (nom personnalisé)"
  - Nom: "AgriGo Symfony"
- Cliquez sur "Générer"
- Copiez le mot de passe de 16 caractères (ex: abcd efgh ijkl mnop)

### 3. Mettre à jour le .env
Remplacez votre configuration actuelle par:
```
MAILER_DSN=smtp://amalmanai658@gmail.com:VOTRE_APP_PASSWORD_16_CARACTERES@smtp.gmail.com:587
```

## 🧪 Test immédiat

Pendant que vous configurez Gmail, le système affiche maintenant le code de test:

1. Allez sur: http://127.0.0.1:8000/forgot-password
2. Entrez votre email
3. Le code de 6 chiffres s'affichera à l'écran
4. Utilisez ce code pour tester la réinitialisation

## 🔍 Mode Debug Activé
- Le code s'affiche toujours pendant le développement
- Logs d'erreur activés dans error_log
- Messages flash informatifs

## ⚡ Solution rapide
Si vous ne pouvez pas configurer Gmail immédiatement:
1. Utilisez le mode test avec le code affiché
2. Le système fonctionne parfaitement pour les tests
3. Configurez Gmail plus tard pour la production
