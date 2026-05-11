# 🌾 AgriGo - Application de Gestion Agricole

[![Symfony](https://img.shields.io/badge/Symfony-7.x-black.svg)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://mysql.com)

AgriGo est une application web de gestion agricole complète permettant aux agriculteurs de gérer leurs parcelles, cultures, stocks, ventes et plus encore, avec assistance vocale IA intégrée.

## ✨ Fonctionnalités Principales

### 🎤 Commande Vocale IA
- **Reconnaissance vocale** pour créer des tâches, cultures, stocks
- **Pattern matching intelligent** pour comprendre les commandes naturelles
- **Fallback IA** (Groq/OpenAI) pour analyses avancées
- **Support des commandes CRUD** : créer, lister, modifier, supprimer

### 📊 Gestion Agricole
- **Parcelles** : Géolocalisation GPS, type de sol, historique
- **Cultures** : Suivi des cycles de culture, alertes de risques
- **Stocks** : Mouvements de stock, alertes de seuil
- **Ventes** : Marketplace intégré, gestion des commandes
- **Irrigation** : Systèmes d'irrigation, historique

### 🤖 Intelligence Artificielle
- **Chat IA** : Assistant agricole personnalisé
- **Analyse de risques** : Alerte météo et maladies
- **Rapports PDF** : Génération automatisée
- **Recommandations** : Conseils basés sur les données

### 🔐 Sécurité & Utilisateurs
- **Authentification** : Email/Google OAuth
- **Rôles** : Admin, Agriculteur, Client
- **Vérification KYC** : Validation des utilisateurs
- **Sessions sécurisées** : Tokens JWT

## 🚀 Installation

### Prérequis
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js (pour les assets)

### Étapes d'installation

```bash
# 1. Cloner le projet
git clone https://github.com/votre-org/agrigoweb.git
cd agrigoweb

# 2. Installer les dépendances
composer install
npm install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres de base de données

# 4. Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Charger les fixtures (optionnel)
php bin/console doctrine:fixtures:load

# 6. Compiler les assets
npm run build

# 7. Démarrer le serveur
symfony server:start
```

L'application est accessible sur `http://localhost:8000`

## 🗂️ Architecture

### Structure des Dossiers
```
agrigoweb/
├── src/
│   ├── Controller/     # Contrôleurs (Front, Admin, API)
│   ├── Entity/         # Entités Doctrine
│   ├── Repository/     # Requêtes personnalisées
│   ├── Service/        # Logique métier (AI, Voice, Stock)
│   ├── Form/           # Formulaires Symfony
│   └── Security/       # Authentification
├── templates/          # Templates Twig
├── public/             # Assets publics
├── migrations/         # Migrations Doctrine
├── config/             # Configuration Symfony
└── docs/               # Documentation
```

### Entités Principales
| Entité | Description |
|--------|-------------|
| `User` | Utilisateurs avec rôles (Admin, Farmer, Client) |
| `Parcelle` | Parcelles agricoles avec coordonnées GPS |
| `Culture` | Cultures associées aux parcelles |
| `Produit` | Produits du stock |
| `MouvementStock` | Entrées/sorties de stock |
| `Vente` | Ventes et marketplace |
| `Tache` | Tâches et rappels |
| `AlerteRisque` | Alertes météo et risques |

## 🎙️ Utilisation de la Commande Vocale

### Exemples de Commandes

**Gestion des Tâches**
- *"Créer une tâche arroser les tomates"*
- *"Lister mes tâches"*
- *"Terminer la tâche 5"*

**Gestion des Cultures**
- *"Nouvelle culture de blé dans parcelle 3"*
- *"Supprimer culture 12"*
- *"Modifier culture 5 nom: maïs"*

**Gestion du Stock**
- *"Ajouter 50 kg de tomates au stock"*
- *"Alerte stock faible"*

**Navigation**
- *"Aller aux cultures"*
- *"Page d'accueil"*

## 🔧 Configuration

### Variables d'Environnement
```env
# Base de données
DATABASE_URL="mysql://user:password@localhost:3306/agri_go_db?serverVersion=8.0"

# Email (Gmail SMTP)
MAILER_DSN=gmail+smtp://EMAIL:PASSWORD@default

# API IA (optionnel)
GROQ_API_KEY=your_groq_key
OPENAI_API_KEY=your_openai_key
GOOGLE_API_KEY=your_google_key

# OAuth Google
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
```

## 📱 API Endpoints

### Voice API
| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/voice/process` | Traiter commande vocale |
| GET | `/api/voice/history` | Historique des commandes |
| GET | `/api/voice/test` | Test du service |

### Chat AI API
| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/ai/chat` | Envoyer message au chat IA |
| POST | `/api/ai/voice-chat` | Chat avec input vocal |
| GET | `/api/ai/insight` | Analyse du profil |

## 🧪 Tests

```bash
# Tests unitaires
php bin/phpunit

# Analyse statique
php vendor/bin/phpstan analyse

# Vérification des dépendances
composer audit
```

## 🛠️ Maintenance

### Commandes Symfony Utiles
```bash
# Vider le cache
php bin/console cache:clear

# Vérifier la configuration
php bin/console config:dump-reference

# Statut des migrations
php bin/console doctrine:migrations:status

# Valider le schéma
php bin/console doctrine:schema:validate
```

## 👥 Contributeurs

- **Amal Manai** - Développeur Principal

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🆘 Support

Pour toute question ou problème :
- 📧 Email : amalmanai658@gmail.com
- 🐛 Issues : [GitHub Issues](https://github.com/votre-org/agrigoweb/issues)

---

<p align="center">🌾 <strong>AgriGo</strong> - L'agriculture intelligente à portée de voix 🎤</p>