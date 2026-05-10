<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AiChatService
{
    private const GROQ_MODEL = 'llama-3.1-70b-versatile';
    private const OPENAI_MODEL = 'gpt-4o-mini';

    private ?string $openaiApiKey;
    private ?string $groqApiKey;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RequestStack $requestStack,
        ?string $openaiApiKey = null,
        ?string $groqApiKey = null
    ) {
        $this->openaiApiKey = $openaiApiKey;
        $this->groqApiKey = $groqApiKey;
    }

    public function getChatResponse(User $user, string $userMessage): string
    {
        $session = $this->requestStack->getSession();
        $history = $session->get('ai_chat_history', []);

        // Priority to Real AI if keys are present
        if ($this->isKeyValid($this->groqApiKey)) {
            return $this->callAiProvider('https://api.groq.com/openai/v1/chat/completions', (string) $this->groqApiKey, self::GROQ_MODEL, $user, $userMessage);
        }

        if ($this->isKeyValid($this->openaiApiKey)) {
            return $this->callAiProvider('https://api.openai.com/v1/chat/completions', (string) $this->openaiApiKey, self::OPENAI_MODEL, $user, $userMessage);
        }

        // FALLBACK: Automatic Intelligent Assistant (No Key Required)
        return $this->getMockResponse($userMessage, $user);
    }

    private function isKeyValid(?string $key): bool
    {
        return $key && !str_contains($key, 'YOUR_') && !str_contains($key, 'votre_');
    }

    private function callAiProvider(string $url, string $key, string $model, User $user, string $userMessage): string
    {
        $session = $this->requestStack->getSession();
        $history = $session->get('ai_chat_history', []);
        $contextSummary = $this->buildUserContext($user);

        $systemPrompt = "Tu es l'assistant IA d'AgriGo. Tu aides " . $user->getFullName() . " avec ses parcelles et cultures. " . $contextSummary;

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach (array_slice($history, -5) as $msg) {
            $messages[] = $msg;
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['model' => $model, 'messages' => $messages, 'temperature' => 0.7],
            ]);

            $data = $response->toArray();
            $aiResponse = $data['choices'][0]['message']['content'] ?? null;

            if ($aiResponse) {
                $this->saveToHistory($userMessage, $aiResponse);
                return $aiResponse;
            }
        } catch (\Exception $e) {
        }

        return $this->getMockResponse($userMessage, $user);
    }

    private function getMockResponse(string $message, User $user): string
    {
        $msg = mb_strtolower($message);

        if (str_contains($msg, 'bonjour') || str_contains($msg, 'salut')) {
            return "Bonjour " . $user->getPrenomUser() . " ! Je suis votre assistant AgriGo en mode local. Comment puis-je vous aider aujourd'hui ? ";
        }

        if (str_contains($msg, 'culture') || str_contains($msg, 'plant') || str_contains($msg, 'tomate') || str_contains($msg, 'olive')) {
            return "Pour vos cultures, je vous conseille de surveiller l'humidité du sol via l'onglet 'Culture'. AgriGo recommande une irrigation régulière le matin pour éviter le gaspillage d'eau.";
        }

        if (str_contains($msg, 'stock') || str_contains($msg, 'engrais')) {
            return "Vous pouvez gérer vos stocks dans l'onglet 'Stock'. AgriGo vous alertera automatiquement si une ressource devient critique.";
        }

        if (str_contains($msg, 'aide') || str_contains($msg, 'marche') || str_contains($msg, 'comment')) {
            return "AgriGo vous permet de suivre vos parcelles, vos cultures et vos stocks. Utilisez le menu latéral pour naviguer. Si vous avez une question spécifique, je suis là !";
        }

        return "C'est noté ! En tant qu'assistant AgriGo, je vous recommande d'utiliser les outils de planification pour optimiser votre rendement.";
    }

    private function saveToHistory(string $userMsg, string $aiMsg): void
    {
        $session = $this->requestStack->getSession();
        $history = $session->get('ai_chat_history', []);
        $history[] = ['role' => 'user', 'content' => $userMsg];
        $history[] = ['role' => 'assistant', 'content' => $aiMsg];
        $session->set('ai_chat_history', array_slice($history, -10));
    }

    private function buildUserContext(User $user): string
    {
        $parcelles = $user->getParcelles();
        $cultures = $user->getCultures();
        $summary = "Données : ";
        foreach ($parcelles as $p) {
            $summary .= $p->getNomParcelle() . ", ";
        }
        foreach ($cultures as $c) {
            $summary .= $c->getNomCulture() . "(" . $c->getEtatCroissance() . "), ";
        }
        return $summary;
    }

    public function getUserInsight(User $user): string
    {
        $contextSummary = $this->buildUserContext($user);
        $userMessage = "Veuillez analyser le profil de l'agriculteur " . $user->getFullName() . " et me faire un résumé court avec 1 ou 2 recommandations pertinentes concernant ses parcelles et cultures. " . $contextSummary;

        if ($this->isKeyValid($this->groqApiKey)) {
            return $this->callAiProviderWithoutHistory('https://api.groq.com/openai/v1/chat/completions', (string) $this->groqApiKey, self::GROQ_MODEL, $userMessage);
        }

        if ($this->isKeyValid($this->openaiApiKey)) {
            return $this->callAiProviderWithoutHistory('https://api.openai.com/v1/chat/completions', (string) $this->openaiApiKey, self::OPENAI_MODEL, $userMessage);
        }

        return "Analyse automatique (Mode hors-ligne) : L'utilisateur " . $user->getFullName() . " a actuellement plusieurs parcelles et cultures enregistrées. En surveillant régulièrement l'humidité et en optimisant l'irrigation sur les phases critiques, le rendement pourra être amélioré de 15%.";
    }

    private function callAiProviderWithoutHistory(string $url, string $key, string $model, string $userMessage): string
    {
        $systemPrompt = "Tu es un expert agronome et un conseiller AgriGo. Tu parles directement à l'administrateur de l'application AgriGo. Fais un résumé pertinent en 3-4 phrases max.";
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['model' => $model, 'messages' => $messages, 'temperature' => 0.5],
            ]);

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? "Impossible de générer l'analyse.";
        } catch (\Exception $e) {
            return "Une erreur est survenue lors de l'appel au service IA : " . $e->getMessage();
        }
    }

    public function parseVoiceCommand(User $user, string $text): ?array
    {
        $contextSummary = $this->buildUserContext($user);
        $userMessage = "La commande vocale transcrite est : \"" . $text . "\". Contexte de l'utilisateur : " . $contextSummary;

        $systemPrompt = <<<PROMPT
Tu es 🎤🧠 AgriGo Voice Task AI, un assistant vocal intelligent pour agriculteurs.
Tu dois analyser la commande vocale et retourner UNIQUEMENT un objet JSON valide, sans aucun texte autour.

📋 ACTIONS SUPPORTÉES (CRUD COMPLET):

1. 📋 TÂCHES (create_task, update_task, delete_task, list_tasks, complete_task):
   - "Créer une tâche [titre] pour [date]" → {"action": "create_task", "title": "...", "date": "demain"}
   - "Modifier la tâche 5" → {"action": "update_task", "task_id": 5, "title": "..."}
   - "Supprimer la tâche 3" → {"action": "delete_task", "task_id": 3}
   - "Lister mes tâches" → {"action": "list_tasks"}
   - "Terminer la tâche 5" → {"action": "complete_task", "task_id": 5}

2. 🌱 CULTURES (create_culture, update_culture, update_culture_status, delete_culture, list_cultures):
   - "Nouvelle culture [nom] dans parcelle [id]" → {"action": "create_culture", "nom": "Blé", "parcelle_id": 3}
   - "Modifier culture 2" → {"action": "update_culture", "id": 2, "nom": "..."}
   - "Supprimer culture 5" → {"action": "delete_culture", "id": 5}
   - "Changer état culture 2 à Floraison" → {"action": "update_culture_status", "culture_id": 2, "new_status": "Floraison"}
   - "Lister les cultures" → {"action": "list_cultures"}

3. 🏞️ PARCELLES (create_parcelle, update_parcelle, delete_parcelle, list_parcelles):
   - "Créer parcelle [nom] [surface] hectares" → {"action": "create_parcelle", "nom": "Champ Nord", "surface": 10}
   - "Modifier parcelle 2" → {"action": "update_parcelle", "id": 2, "nom": "..."}
   - "Supprimer parcelle 2" → {"action": "delete_parcelle", "id": 2}
   - "Lister les parcelles" → {"action": "list_parcelles"}

4. 🌾 RÉCOLTES (create_recolte, update_recolte, delete_recolte, list_recoltes):
   - "Nouvelle récolte culture [id] [quantité]kg" → {"action": "create_recolte", "culture_id": 3, "quantite": 500}
   - "Modifier récolte 1" → {"action": "update_recolte", "id": 1, "quantite": 600}
   - "Supprimer récolte 1" → {"action": "delete_recolte", "id": 1}
   - "Lister les récoltes" → {"action": "list_recoltes"}

5. 📦 PRODUITS (create_produit, update_produit, delete_produit, list_produits):
   - "Créer produit [nom] [quantité]kg" → {"action": "create_produit", "nom": "Engrais", "quantite": 100, "categorie": "..."}
   - "Modifier produit 3" → {"action": "update_produit", "id": 3, "prix": 50}
   - "Supprimer produit 3" → {"action": "delete_produit", "id": 3}
   - "Lister les produits" → {"action": "list_produits"}

6. 📦 MOUVEMENTS STOCK (add_stock, remove_stock, check_stock, list_low_stock):
   - "Ajouter 50 kg d'engrais" → {"action": "add_stock", "produit": "engrais", "quantite": 50}
   - "Retirer 10 unités de tomates" → {"action": "remove_stock", "produit": "tomates", "quantite": 10}
   - "Stock de pommes" → {"action": "check_stock", "produit": "pommes"}
   - "Alertes stock" → {"action": "list_low_stock"}

7. 💰 VENTES (create_vente, update_vente, delete_vente, list_ventes):
   - "Nouvelle vente 100kg blé à [client]" → {"action": "create_vente", "quantite": 100, "acheteur": "..."}
   - "Modifier vente 3" → {"action": "update_vente", "id": 3, "prix": 500}
   - "Supprimer vente 2" → {"action": "delete_vente", "id": 2}
   - "Lister les ventes" → {"action": "list_ventes"}

8. 🧭 NAVIGATION (navigate):
   - "Aller aux cultures" → {"action": "navigate", "destination": "cultures"}
   - "Ouvrir stock" → {"action": "navigate", "destination": "stock"}
   - "Dashboard" → {"action": "navigate", "destination": "home"}
   - "Page ventes" → {"action": "navigate", "destination": "ventes"}
   - Routes: accueil, dashboard, cultures, parcelles, recoltes, stock, produits, ventes, taches, irrigation, meteo, alertes, profil, admin, marketplace, parametres

9. 📊 INFORMATIONS (get_stats, get_weather, get_risks, get_recommendations):
   - "Statistiques" → {"action": "get_stats"}
   - "Météo" → {"action": "get_weather"}
   - "Risques" → {"action": "get_risks"}
   - "Conseils" → {"action": "get_recommendations"}

10. 🚿 IRRIGATION (schedule_irrigation):
    - "Programmer irrigation parcelle [id] à [heure]" → {"action": "schedule_irrigation", "parcelle_id": 1, "heure": "06:00"}

11. ❓ AIDE (help):
    - "Aide" / "Commandes" / "Que puis-je faire" → {"action": "help"}

Si la commande n'est pas claire: {"action": "unknown", "message": "Je n'ai pas compris. Essayez: 'Créer une tâche...', 'Ajouter du stock...', 'Aide'"}

Extrais les entités: dates (demain, après-demain, lundi...), quantités, nombres, IDs, noms de produits/cultures/parcelles.
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage],
        ];

        try {
            $response = null;
            if ($this->isKeyValid($this->groqApiKey)) {
                $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => ['Authorization' => 'Bearer ' . $this->groqApiKey, 'Content-Type' => 'application/json'],
                    'json' => ['model' => self::GROQ_MODEL, 'messages' => $messages, 'temperature' => 0.1, 'response_format' => ['type' => 'json_object']],
                ]);
            } elseif ($this->isKeyValid($this->openaiApiKey)) {
                $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                    'headers' => ['Authorization' => 'Bearer ' . $this->openaiApiKey, 'Content-Type' => 'application/json'],
                    'json' => ['model' => self::OPENAI_MODEL, 'messages' => $messages, 'temperature' => 0.1, 'response_format' => ['type' => 'json_object']],
                ]);
            }

            if ($response) {
                $data = $response->toArray();
                $content = $data['choices'][0]['message']['content'] ?? null;
                if ($content) {
                    return json_decode($content, true);
                }
            }
        } catch (\Exception $e) {
            // Silence
        }

        // Fallback intelligent avec pattern matching
        return $this->parseVoiceCommandFallback($text);
    }

    /**
     * Fallback local quand l'API IA n'est pas disponible
     */
    private function parseVoiceCommandFallback(string $text): array
    {
        $lower = mb_strtolower($text);

        // Pattern: tâche/rappel + contenu
        if (preg_match('/(créer|nouvelle?|ajouter|faire).*?(tâche|rappel|todo)/', $lower) ||
            preg_match('/(tâche|rappel|todo).*?(créer|nouvelle?|ajouter)/', $lower)) {
            
            // Extraire un titre
            $title = preg_replace('/.*?(tâche|rappel|todo)[\s:]*(pour|de|à)?\s*/i', '', $text);
            $title = trim((string) $title);
            $title = ucfirst($title ?: 'Nouvelle tâche');
            
            return [
                "action" => "create_task",
                "title" => $title,
                "description" => $text,
                "_fallback" => true
            ];
        }

        // Pattern: lister/afficher/mes tâches
        if (preg_match('/(lister?|afficher|voir|mes|quelles).*?(tâches?|todo)/', $lower)) {
            return ["action" => "list_tasks", "_fallback" => true];
        }

        // Pattern: terminer/valider/faire une tâche
        if (preg_match('/(terminer|valider|faire|compléter|check).*?(tâche|numéro)?\s*(\d+)/', $lower, $m)) {
            return ["action" => "complete_task", "task_id" => (int) $m[3], "_fallback" => true];
        }

        // Pattern: nouvelle culture
        if (preg_match('/(nouvelle?|créer|ajouter).*?(culture|planter)/', $lower)) {
            preg_match('/(culture|planter)\s+(?:de\s+)?([a-zàâäéèêëïîôùûüç\s]+)/i', $text, $m);
            $nom = trim($m[2] ?? 'Nouvelle culture');
            return [
                "action" => "create_culture",
                "nom" => $nom,
                "_fallback" => true
            ];
        }

        // Pattern: ajouter du stock
        if (preg_match('/(ajouter|ajoute|entrée|recevoir).*?(stock|produit)?/', $lower)) {
            preg_match('/(\d+)\s*(kg|litre|l|unité|sac|carton)?\s+(?:de\s+)?([a-zàâäéèêëïîôùûüç\s]+)/i', $text, $m);
            return [
                "action" => "add_stock",
                "produit" => trim($m[3] ?? 'produit'),
                "quantite" => (int)($m[1] ?? 0),
                "unite" => trim($m[2] ?? 'unité'),
                "_fallback" => true
            ];
        }

        // Pattern: retirer du stock
        if (preg_match('/(retirer|sortie|enlever|utiliser|prendre).*?(stock|produit)?/', $lower)) {
            preg_match('/(\d+)\s*(kg|litre|l|unité|sac|carton)?\s+(?:de\s+)?([a-zàâäéèêëïîôùûüç\s]+)/i', $text, $m);
            return [
                "action" => "remove_stock",
                "produit" => trim($m[3] ?? 'produit'),
                "quantite" => (int)($m[1] ?? 0),
                "unite" => trim($m[2] ?? 'unité'),
                "_fallback" => true
            ];
        }

        // Pattern: vérifier/consulter stock
        if (preg_match('/(stock|reste|combien|disponible|vérifier).*?(produit|stock)?/', $lower)) {
            preg_match('/(?:de\s+)?([a-zàâäéèêëïîôùûüç\s]+)(?:\?|$)/i', $text, $m);
            return [
                "action" => "check_stock",
                "produit" => trim($m[1] ?? ''),
                "_fallback" => true
            ];
        }

        // Pattern: alertes stock
        if (preg_match('/(alerte|critique|manque|bientôt|rupture).*?(stock|produit)?/', $lower)) {
            return ["action" => "list_low_stock", "_fallback" => true];
        }

        // Pattern: navigation - ALL ROUTES
        $navPatterns = [
            // 🏠 Home
            'accueil' => 'accueil',
            'home' => 'accueil',
            'dashboard' => 'accueil',
            
            // 📋 Tâches
            'tâches?' => 'taches',
            'tasks?' => 'taches',
            
            // 🌱 Cultures
            'cultures?' => 'cultures',
            'culture' => 'cultures',
            
            // 🏞️ Parcelles
            'parcelles?' => 'parcelles',
            'champs?' => 'parcelles',
            'fields?' => 'parcelles',
            
            // 📦 Stock
            'stock' => 'stock',
            'produits?' => 'stock',
            'mouvements?' => 'mouvements',
            'inventaire' => 'stock',
            
            // 🌾 Récoltes
            'récoltes?' => 'recoltes',
            'moisson' => 'recoltes',
            'recoltes?' => 'recoltes',
            
            // 💰 Ventes
            'ventes?' => 'ventes',
            'sales' => 'ventes',
            
            // 🚿 Irrigation
            'irrigation' => 'irrigation',
            'arrosage' => 'irrigation',
            'arroser' => 'irrigation',
            
            // 🌤️ Météo
            'météo' => 'meteo',
            'temps' => 'meteo',
            'weather' => 'meteo',
            
            // ⚠️ Alertes
            'alertes?' => 'alertes',
            'risques?' => 'alertes',
            
            // 👤 Profil
            'profil' => 'profil',
            'profile' => 'profil',
            'paramètres' => 'parametres',
            'settings' => 'parametres',
            
            // 🛒 Marketplace
            'marketplace' => 'marketplace',
            'marché' => 'marketplace',
            'boutique' => 'marketplace',
            'shop' => 'marketplace',
            'panier' => 'cart',
            'cart' => 'cart',
            'commandes?' => 'orders',
            'orders' => 'orders',
            
            // ⚙️ Admin
            'admin' => 'admin',
            'administration' => 'admin',
            'backoffice' => 'admin',
            'stats?' => 'stats',
            'statistics' => 'stats',
            'statistiques?' => 'statistiques',
            'bilans?' => 'bilans',
            
            // 👥 Users
            'users?' => 'users',
            'utilisateurs?' => 'users',
            'register' => 'register',
            'inscription' => 'register',
            'login' => 'login',
            'connexion' => 'login',
        ];
        
        foreach ($navPatterns as $pattern => $dest) {
            if (preg_match('/(aller|ouvrir|voir|afficher|page|naviguer|vers).*?' . $pattern . '/', $lower) ||
                preg_match('/' . $pattern . '.*?(aller|ouvrir|voir|afficher|naviguer)/', $lower) ||
                preg_match('/^(page\s+)?' . $pattern . '$/', $lower)) {
                return [
                    "action" => "navigate",
                    "destination" => $dest,
                    "_fallback" => true
                ];
            }
        }

        // Pattern: Ajouter un utilisateur
        if (preg_match('/(ajouter|nouveau|créer).*?(user|utilisateur)/', $lower)) {
            return [
                "action" => "navigate",
                "destination" => "add_user",
                "_fallback" => true
            ];
        }

        // Pattern: statistiques
        if (preg_match('/(stats?|statistiques?|bilan|résumé|chiffres?)/', $lower)) {
            return ["action" => "get_stats", "_fallback" => true];
        }

        // Pattern: météo
        if (preg_match('/(météo|temps|prévisions?|pluie|soleil)/', $lower)) {
            return ["action" => "get_weather", "_fallback" => true];
        }

        // Pattern: risques
        if (preg_match('/(risques?|alertes?|danger|problème)/', $lower)) {
            return ["action" => "get_risks", "_fallback" => true];
        }

        // ========== CRUD PATTERNS ==========

        // Pattern: supprimer/delete + ID
        if (preg_match('/(supprimer|effacer|retirer|enlever|delete|remove).*?(tâche|culture|parcelle|récolte|produit|vente)?\s*(\d+)/', $lower, $m)) {
            $entity = $m[2] !== '' ? $m[2] : 'tache';
            $id = (int) $m[3];
            
            $actionMap = [
                'tâche' => 'delete_task',
                'culture' => 'delete_culture',
                'parcelle' => 'delete_parcelle',
                'récolte' => 'delete_recolte',
                'produit' => 'delete_produit',
                'vente' => 'delete_vente',
            ];
            
            return [
                "action" => $actionMap[$entity] ?? 'delete_task',
                "id" => $id,
                "task_id" => $id, // pour backward compat
                "_fallback" => true
            ];
        }

        // Pattern: modifier/update + ID
        if (preg_match('/(modifier|éditer|changer|update|mettre à jour).*?(tâche|culture|parcelle|récolte|produit|vente)?\s*(\d+)/', $lower, $m)) {
            $entity = $m[2] !== '' ? $m[2] : 'tache';
            $id = (int) $m[3];
            
            $actionMap = [
                'tâche' => 'update_task',
                'culture' => 'update_culture',
                'parcelle' => 'update_parcelle',
                'récolte' => 'update_recolte',
                'produit' => 'update_produit',
                'vente' => 'update_vente',
            ];
            
            return [
                "action" => $actionMap[$entity] ?? 'update_task',
                "id" => $id,
                "task_id" => $id,
                "_fallback" => true
            ];
        }

        // Pattern: lister/afficher + entité
        if (preg_match('/(lister?|afficher|voir|mes|toutes).*?(tâches?|cultures?|parcelles?|récoltes?|produits?|ventes?)/', $lower, $m)) {
            $entity = $m[2];
            
            $actionMap = [
                'tâches' => 'list_tasks',
                'cultures' => 'list_cultures',
                'parcelles' => 'list_parcelles',
                'récoltes' => 'list_recoltes',
                'produits' => 'list_produits',
                'ventes' => 'list_ventes',
            ];
            
            return [
                "action" => $actionMap[$entity] ?? 'list_tasks',
                "_fallback" => true
            ];
        }

        // Pattern: nouvelle parcelle
        if (preg_match('/(nouvelle?|créer|ajouter).*?(parcelle|champ)/', $lower)) {
            preg_match('/(?:parcelle|champ)\s+(?:nommée?\s+)?["\']?([a-zàâäéèêëïîôùûüç0-9\s]+)/i', $text, $m);
            $nom = trim($m[1] ?? 'Nouvelle parcelle');
            
            // Extraire surface
            preg_match('/(\d+)\s*(hectare|ha|mètre|m)/', $lower, $mSurface);
            $surface = (float)($mSurface[1] ?? 0);
            
            return [
                "action" => "create_parcelle",
                "nom" => $nom,
                "surface" => $surface,
                "_fallback" => true
            ];
        }

        // Pattern: nouvelle récolte
        if (preg_match('/(nouvelle?|créer|ajouter|enregistrer).*?(récolte|moisson)/', $lower)) {
            // Extraire quantité
            preg_match('/(\d+)\s*(kg|kilos?|tonnes?|quintaux?)/', $lower, $m);
            $quantite = (float)($m[1] ?? 0);
            
            // Extraire culture ID si présent
            preg_match('/culture\s*(\d+)/', $lower, $mCulture);
            $cultureId = (int)($mCulture[1] ?? 0);
            
            return [
                "action" => "create_recolte",
                "quantite" => $quantite,
                "culture_id" => $cultureId,
                "_fallback" => true
            ];
        }

        // Pattern: nouveau produit
        if (preg_match('/(nouveau|créer|ajouter).*?(produit|article|item)/', $lower)) {
            preg_match('/(?:produit|article)\s+(?:nommé?\s+)?["\']?([a-zàâäéèêëïîôùûüç0-9\s]+)/i', $text, $m);
            $nom = trim($m[1] ?? 'Nouveau produit');
            
            // Extraire quantité
            preg_match('/(\d+)\s*(kg|litre|l|unité|sac)/', $lower, $mQty);
            $quantite = (float)($mQty[1] ?? 0);
            
            return [
                "action" => "create_produit",
                "nom" => $nom,
                "quantite" => $quantite,
                "_fallback" => true
            ];
        }

        // Pattern: nouvelle vente
        if (preg_match('/(nouvelle?|créer|enregistrer|vendre).*?(vente|vendu)/', $lower)) {
            // Extraire quantité et produit
            preg_match('/(\d+)\s*(kg|kilos?)?\s+(?:de\s+)?([a-zàâäéèêëïîôùûüç]+)/', $lower, $m);
            $quantite = (float)($m[1] ?? 0);
            $produit = trim($m[3] ?? '');
            
            return [
                "action" => "create_vente",
                "quantite" => $quantite,
                "produit" => $produit,
                "_fallback" => true
            ];
        }

        // Pattern: conseils/recommandations
        if (preg_match('/(conseils?|recommandations?|suggestions?|aide|que faire)/', $lower)) {
            return ["action" => "get_recommendations", "_fallback" => true];
        }

        // Pattern: aide
        if (preg_match('/^(aide|help|commandes?|comment|\.\?)$/', $lower)) {
            return ["action" => "help", "_fallback" => true];
        }

        return ["action" => "unknown", "message" => "Mode hors-ligne. J'ai entendu : " . $text, "_fallback" => true];
    }
}

