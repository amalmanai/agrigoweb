<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Culture;
use App\Entity\MouvementStock;
use App\Entity\Parcelle;
use App\Entity\Produit;
use App\Entity\Tache;
use App\Entity\User;
use App\Entity\Vente;
use App\Entity\Recolte;
use Doctrine\ORM\EntityManagerInterface;

/**
 * 🎤🧠 AgriGo Voice Task AI - Command Execution Engine
 * 
 * Service intelligent qui exécute les actions détectées par l'IA vocale.
 * Supporte: tâches, cultures, stock, ventes, navigation, et plus.
 */
class VoiceCommandService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MeteoService $meteoService,
        private readonly RiskAnalysisService $riskService,
    ) {
    }

    /**
     * Exécute une commande vocale détectée par l'IA
     * 
     * @param User $user L'utilisateur connecté
     * @param array $intent L'intention détectée avec paramètres
     * @return array Résultat de l'exécution
     */
    public function executeCommand(User $user, array $intent): array
    {
        $action = $intent['action'] ?? 'unknown';
        
        try {
            return match ($action) {
                // 📋 Gestion des Tâches (CRUD complet)
                'create_task' => $this->handleCreateTask($user, $intent),
                'update_task' => $this->handleUpdateTask($user, $intent),
                'delete_task' => $this->handleDeleteTask($user, $intent),
                'list_tasks' => $this->handleListTasks($user, $intent),
                'complete_task' => $this->handleCompleteTask($user, $intent),
                
                // 🌱 Gestion des Cultures (CRUD complet)
                'create_culture' => $this->handleCreateCulture($user, $intent),
                'update_culture' => $this->handleUpdateCulture($user, $intent),
                'update_culture_status' => $this->handleUpdateCultureStatus($user, $intent),
                'delete_culture' => $this->handleDeleteCulture($user, $intent),
                'list_cultures' => $this->handleListCultures($user, $intent),
                
                // 🏞️ Gestion des Parcelles (CRUD complet)
                'create_parcelle' => $this->handleCreateParcelle($user, $intent),
                'update_parcelle' => $this->handleUpdateParcelle($user, $intent),
                'delete_parcelle' => $this->handleDeleteParcelle($user, $intent),
                'list_parcelles' => $this->handleListParcelles($user, $intent),
                
                // 🌾 Gestion des Récoltes (CRUD complet)
                'create_recolte' => $this->handleCreateRecolte($user, $intent),
                'update_recolte' => $this->handleUpdateRecolte($user, $intent),
                'delete_recolte' => $this->handleDeleteRecolte($user, $intent),
                'list_recoltes' => $this->handleListRecoltes($user, $intent),
                
                // 📦 Gestion du Stock & Produits (CRUD complet)
                'create_produit' => $this->handleCreateProduit($user, $intent),
                'update_produit' => $this->handleUpdateProduit($user, $intent),
                'delete_produit' => $this->handleDeleteProduit($user, $intent),
                'list_produits' => $this->handleListProduits($user, $intent),
                'add_stock' => $this->handleAddStock($user, $intent),
                'remove_stock' => $this->handleRemoveStock($user, $intent),
                'check_stock' => $this->handleCheckStock($user, $intent),
                'list_low_stock' => $this->handleListLowStock($user),
                
                // 💰 Gestion des Ventes (CRUD complet)
                'create_vente' => $this->handleCreateVente($user, $intent),
                'update_vente' => $this->handleUpdateVente($user, $intent),
                'delete_vente' => $this->handleDeleteVente($user, $intent),
                'list_ventes' => $this->handleListVentes($user, $intent),
                
                // 🧭 Navigation
                'navigate' => $this->handleNavigation($intent),
                
                // 📊 Informations & Analyses
                'get_stats' => $this->handleGetStats($user),
                'get_weather' => $this->handleGetWeather($user),
                'get_risks' => $this->handleGetRisks($user),
                'get_recommendations' => $this->handleGetRecommendations($user),
                
                // 🚿 Irrigation
                'schedule_irrigation' => $this->handleScheduleIrrigation($user, $intent),
                
                // ❓ Aide
                'help' => $this->handleHelp(),
                
                default => [
                    'success' => false,
                    'message' => "Désolé, je ne comprends pas cette commande. Dites 'aide' pour voir les commandes disponibles.",
                    'action' => 'unknown'
                ]
            };
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'exécution: ' . $e->getMessage(),
                'action' => $action
            ];
        }
    }

    // ==================== 📋 TÂCHES ====================

    private function handleCreateTask(User $user, array $intent): array
    {
        $title = $intent['title'] ?? 'Nouvelle tâche';
        $description = $intent['description'] ?? '';
        $dateStr = $intent['date'] ?? null;
        $type = $intent['type'] ?? 'Général';
        $priority = $intent['priority'] ?? 'Normale';

        $tache = new Tache();
        $tache->setTittreTache($title);
        $tache->setDescriptionTache($description);
        $tache->setTypeTache($type);
        $tache->setStatusTache('En attente');
        $tache->setIdUser($user->getId());
        
        if ($dateStr) {
            try {
                $date = new \DateTime($dateStr);
                $tache->setDateTache($date);
            } catch (\Exception $e) {
                // Date invalide, on ignore
            }
        }

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "✅ Tâche créée: \"{$title}\"",
            'action' => 'create_task',
            'data' => [
                'id' => $tache->getId(),
                'title' => $title,
                'redirect' => '/tache'
            ]
        ];
    }

    private function handleUpdateTask(User $user, array $intent): array
    {
        $taskId = $intent['task_id'] ?? null;
        $status = $intent['status'] ?? null;

        if (!$taskId) {
            return [
                'success' => false,
                'message' => 'Veuillez préciser le numéro ou le nom de la tâche.',
                'action' => 'update_task'
            ];
        }

        $tache = $this->entityManager->getRepository(Tache::class)->find($taskId);
        
        if (!$tache || $tache->getIdUser() !== $user->getId()) {
            return [
                'success' => false,
                'message' => 'Tâche non trouvée.',
                'action' => 'update_task'
            ];
        }

        if ($status) {
            $tache->setStatusTache($status);
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "✏️ Tâche \"{$tache->getTittreTache()}\" mise à jour.",
            'action' => 'update_task'
        ];
    }

    private function handleDeleteTask(User $user, array $intent): array
    {
        $taskId = $intent['task_id'] ?? null;

        if (!$taskId) {
            return [
                'success' => false,
                'message' => 'Veuillez préciser le numéro de la tâche à supprimer.',
                'action' => 'delete_task'
            ];
        }

        $tache = $this->entityManager->getRepository(Tache::class)->find($taskId);
        
        if (!$tache || $tache->getIdUser() !== $user->getId()) {
            return [
                'success' => false,
                'message' => 'Tâche non trouvée.',
                'action' => 'delete_task'
            ];
        }

        $title = $tache->getTittreTache();
        $this->entityManager->remove($tache);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🗑️ Tâche \"{$title}\" supprimée.",
            'action' => 'delete_task'
        ];
    }

    private function handleCompleteTask(User $user, array $intent): array
    {
        $taskId = $intent['task_id'] ?? null;

        if (!$taskId) {
            // Chercher par titre si pas d'ID
            $title = $intent['title'] ?? null;
            if ($title) {
                $taches = $this->entityManager->getRepository(Tache::class)
                    ->findBy(['id_user' => $user->getId(), 'tittre_tache' => $title]);
                if (count($taches) === 1) {
                    $tache = $taches[0];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Plusieurs tâches trouvées avec ce nom. Précisez le numéro.',
                        'action' => 'complete_task'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Veuillez préciser la tâche à terminer.',
                    'action' => 'complete_task'
                ];
            }
        } else {
            $tache = $this->entityManager->getRepository(Tache::class)->find($taskId);
        }

        if (!$tache || $tache->getIdUser() !== $user->getId()) {
            return [
                'success' => false,
                'message' => 'Tâche non trouvée.',
                'action' => 'complete_task'
            ];
        }

        $tache->setStatusTache('Terminée');
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "✅ Tâche \"{$tache->getTittreTache()}\" terminée ! Bravo !",
            'action' => 'complete_task',
            'data' => ['redirect' => '/tache']
        ];
    }

    private function handleListTasks(User $user, array $intent): array
    {
        $status = $intent['status'] ?? null;
        
        $criteria = ['id_user' => $user->getId()];
        if ($status) {
            $criteria['status_tache'] = $status;
        }

        $taches = $this->entityManager->getRepository(Tache::class)
            ->findBy($criteria, ['date_tache' => 'DESC'], 10);

        $count = count($taches);
        $taskList = array_map(fn($t) => [
            'id' => $t->getId(),
            'title' => $t->getTittreTache(),
            'status' => $t->getStatusTache()
        ], $taches);

        $statusText = $status ? "({$status})" : "(toutes)";

        return [
            'success' => true,
            'message' => "📋 {$count} tâches trouvées {$statusText}",
            'action' => 'list_tasks',
            'data' => [
                'tasks' => $taskList,
                'count' => $count,
                'redirect' => '/tache'
            ]
        ];
    }

    // ==================== 🌱 CULTURES ====================

    private function handleCreateCulture(User $user, array $intent): array
    {
        $nom = $intent['nom'] ?? $intent['name'] ?? 'Nouvelle culture';
        $parcelleId = $intent['parcelle_id'] ?? null;
        $etat = $intent['etat'] ?? 'Semis';
        $rendement = $intent['rendement'] ?? 0;

        $culture = new Culture();
        $culture->setNomCulture($nom);
        $culture->setEtatCroissance($etat);
        $culture->setRendementPrevu((float) $rendement);
        $culture->setDateSemis(new \DateTime());
        $culture->setOwner($user);

        if ($parcelleId) {
            $parcelle = $this->entityManager->getRepository(Parcelle::class)->find($parcelleId);
            if ($parcelle && $parcelle->getOwner() === $user) {
                $culture->setParcelle($parcelle);
            }
        }

        $this->entityManager->persist($culture);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🌱 Culture \"{$nom}\" créée avec succès !",
            'action' => 'create_culture',
            'data' => [
                'id' => $culture->getId(),
                'nom' => $nom,
                'redirect' => '/culture'
            ]
        ];
    }

    private function handleUpdateCultureStatus(User $user, array $intent): array
    {
        $cultureId = $intent['culture_id'] ?? null;
        $newStatus = $intent['new_status'] ?? null;

        if (!$cultureId || !$newStatus) {
            return [
                'success' => false,
                'message' => 'Veuillez préciser la culture et le nouvel état.',
                'action' => 'update_culture_status'
            ];
        }

        $culture = $this->entityManager->getRepository(Culture::class)->find($cultureId);
        
        if (!$culture || $culture->getOwner() !== $user) {
            return [
                'success' => false,
                'message' => 'Culture non trouvée.',
                'action' => 'update_culture_status'
            ];
        }

        $oldStatus = $culture->getEtatCroissance();
        $culture->setEtatCroissance($newStatus);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🌾 Culture \"{$culture->getNomCulture()}\" : {$oldStatus} → {$newStatus}",
            'action' => 'update_culture_status'
        ];
    }

    private function handleListCultures(User $user, array $intent): array
    {
        $cultures = $this->entityManager->getRepository(Culture::class)
            ->findBy(['owner' => $user], ['dateSemis' => 'DESC']);

        $count = count($cultures);
        $cultureList = array_map(fn($c) => [
            'id' => $c->getId(),
            'nom' => $c->getNomCulture(),
            'etat' => $c->getEtatCroissance(),
            'parcelle' => $c->getParcelle()?->getNomParcelle()
        ], $cultures);

        return [
            'success' => true,
            'message' => "🌱 {$count} cultures enregistrées",
            'action' => 'list_cultures',
            'data' => [
                'cultures' => $cultureList,
                'count' => $count,
                'redirect' => '/culture'
            ]
        ];
    }

    // ==================== 📦 STOCK ====================

    private function handleAddStock(User $user, array $intent): array
    {
        $produitId = $intent['produit_id'] ?? $intent['produit'] ?? null;
        $quantite = $intent['quantite'] ?? $intent['quantity'] ?? 0;
        $motif = $intent['motif'] ?? 'Entrée de stock par commande vocale';

        if (!$produitId || $quantite <= 0) {
            return [
                'success' => false,
                'message' => 'Veuillez préciser le produit et la quantité.',
                'action' => 'add_stock'
            ];
        }

        $produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
        
        if (!$produit || $produit->getOwner() !== $user) {
            // Essayer de trouver par nom
            $produits = $this->entityManager->getRepository(Produit::class)
                ->createQueryBuilder('p')
                ->where('p.nom_produit LIKE :nom')
                ->andWhere('p.owner = :user')
                ->setParameter('nom', '%' . $produitId . '%')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
            
            if (count($produits) === 1) {
                $produit = $produits[0];
            } else {
                return [
                    'success' => false,
                    'message' => 'Produit non trouvé.',
                    'action' => 'add_stock'
                ];
            }
        }

        // Créer le mouvement
        $mouvement = new MouvementStock();
        $mouvement->setTypeMouvement('Entrée');
        $mouvement->setQuantite((int) $quantite);
        $mouvement->setMotif($motif);
        $mouvement->setDateMouvement(new \DateTime());
        $mouvement->setProduit($produit);
        $mouvement->setIdUser($user->getId());

        // Mettre à jour le stock
        $nouvelleQuantite = $produit->getQuantiteDisponible() + (int) $quantite;
        $produit->setQuantiteDisponible($nouvelleQuantite);

        $this->entityManager->persist($mouvement);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "📦 +{$quantite} {$produit->getNomProduit()} (Stock: {$nouvelleQuantite})",
            'action' => 'add_stock',
            'data' => [
                'produit' => $produit->getNomProduit(),
                'quantite' => $nouvelleQuantite,
                'redirect' => '/mouvement-stock'
            ]
        ];
    }

    private function handleRemoveStock(User $user, array $intent): array
    {
        $produitId = $intent['produit_id'] ?? $intent['produit'] ?? null;
        $quantite = $intent['quantite'] ?? $intent['quantity'] ?? 0;
        $motif = $intent['motif'] ?? 'Sortie de stock par commande vocale';

        if (!$produitId || $quantite <= 0) {
            return [
                'success' => false,
                'message' => 'Veuillez préciser le produit et la quantité.',
                'action' => 'remove_stock'
            ];
        }

        $produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
        
        if (!$produit || $produit->getOwner() !== $user) {
            return [
                'success' => false,
                'message' => 'Produit non trouvé.',
                'action' => 'remove_stock'
            ];
        }

        $stockActuel = $produit->getQuantiteDisponible();
        if ($stockActuel < $quantite) {
            return [
                'success' => false,
                'message' => "Stock insuffisant ! Disponible: {$stockActuel}",
                'action' => 'remove_stock'
            ];
        }

        // Créer le mouvement
        $mouvement = new MouvementStock();
        $mouvement->setTypeMouvement('Sortie');
        $mouvement->setQuantite((int) $quantite);
        $mouvement->setMotif($motif);
        $mouvement->setDateMouvement(new \DateTime());
        $mouvement->setProduit($produit);
        $mouvement->setIdUser($user->getId());

        // Mettre à jour le stock
        $nouvelleQuantite = $stockActuel - (int) $quantite;
        $produit->setQuantiteDisponible($nouvelleQuantite);

        $this->entityManager->persist($mouvement);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "📦 -{$quantite} {$produit->getNomProduit()} (Stock: {$nouvelleQuantite})",
            'action' => 'remove_stock',
            'data' => [
                'produit' => $produit->getNomProduit(),
                'quantite' => $nouvelleQuantite,
                'redirect' => '/mouvement-stock'
            ]
        ];
    }

    private function handleCheckStock(User $user, array $intent): array
    {
        $produitId = $intent['produit_id'] ?? $intent['produit'] ?? null;

        if ($produitId) {
            $produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
            
            if (!$produit || $produit->getOwner() !== $user) {
                return [
                    'success' => false,
                    'message' => 'Produit non trouvé.',
                    'action' => 'check_stock'
                ];
            }

            return [
                'success' => true,
                'message' => "📦 {$produit->getNomProduit()}: {$produit->getQuantiteDisponible()} {$produit->getUnite()}",
                'action' => 'check_stock',
                'data' => [
                    'produit' => $produit->getNomProduit(),
                    'quantite' => $produit->getQuantiteDisponible(),
                    'unite' => $produit->getUnite(),
                    'seuil' => $produit->getSeuilAlerte()
                ]
            ];
        }

        // Lister tous les stocks
        $produits = $this->entityManager->getRepository(Produit::class)
            ->findBy(['owner' => $user]);

        $stockList = array_map(fn($p) => [
            'nom' => $p->getNomProduit(),
            'quantite' => $p->getQuantiteDisponible(),
            'unite' => $p->getUnite(),
            'alerte' => $p->getQuantiteDisponible() <= $p->getSeuilAlerte()
        ], $produits);

        return [
            'success' => true,
            'message' => "📦 Stock de " . count($produits) . " produits",
            'action' => 'check_stock',
            'data' => [
                'stocks' => $stockList,
                'redirect' => '/produit'
            ]
        ];
    }

    private function handleListLowStock(User $user): array
    {
        $produits = $this->entityManager->getRepository(Produit::class)
            ->createQueryBuilder('p')
            ->where('p.owner = :user')
            ->andWhere('p.quantite_disponible <= p.seuil_alerte')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $count = count($produits);
        $list = array_map(fn($p) => [
            'nom' => $p->getNomProduit(),
            'quantite' => $p->getQuantiteDisponible(),
            'seuil' => $p->getSeuilAlerte()
        ], $produits);

        return [
            'success' => true,
            'message' => $count > 0 
                ? "⚠️ {$count} produits en alerte stock !" 
                : "✅ Tous les stocks sont au niveau normal.",
            'action' => 'list_low_stock',
            'data' => [
                'produits' => $list,
                'count' => $count,
                'redirect' => '/produit'
            ]
        ];
    }

    // ==================== 💰 VENTES ====================

    private function handleCreateVente(User $user, array $intent): array
    {
        $description = $intent['description'] ?? 'Vente enregistrée par voix';
        $acheteur = $intent['acheteur'] ?? $intent['buyer'] ?? 'Client';
        $recolteId = $intent['recolte_id'] ?? null;

        $vente = new Vente();
        $vente->setDescription($description);
        $vente->setBuyerName($acheteur);
        $vente->setStatus('En cours');

        if ($recolteId) {
            $recolte = $this->entityManager->getRepository(Recolte::class)->find($recolteId);
            if ($recolte) {
                $vente->setRecolte($recolte);
            }
        }

        $this->entityManager->persist($vente);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "💰 Vente enregistrée pour {$acheteur}",
            'action' => 'create_vente',
            'data' => [
                'id' => $vente->getId(),
                'acheteur' => $acheteur,
                'redirect' => '/vente'
            ]
        ];
    }

    private function handleListVentes(User $user, array $intent): array
    {
        $ventes = $this->entityManager->getRepository(Vente::class)
            ->findBy([], ['id' => 'DESC'], 10);

        $count = count($ventes);

        return [
            'success' => true,
            'message' => "💰 {$count} dernières ventes",
            'action' => 'list_ventes',
            'data' => [
                'count' => $count,
                'redirect' => '/vente'
            ]
        ];
    }

    // ==================== 🧭 NAVIGATION ====================

    private function handleNavigation(array $intent): array
    {
        $destination = $intent['destination'] ?? $intent['page'] ?? 'home';
        
        $routes = [
            // 🏠 Accueil & Dashboard
            'accueil' => '/',
            'home' => '/',
            'dashboard' => '/',
            
            // 📋 Tâches
            'taches' => '/tache',
            'task' => '/tache',
            'tache' => '/tache',
            'tache/new' => '/tache/new',
            
            // 🌱 Cultures
            'cultures' => '/culture',
            'culture' => '/culture',
            'culture/new' => '/culture/new',
            
            // 🏞️ Parcelles
            'parcelles' => '/parcelle',
            'parcelle' => '/parcelle',
            'champs' => '/parcelle',
            'parcelle/new' => '/parcelle/new',
            
            // 📦 Stock & Produits
            'stock' => '/produit',
            'produits' => '/produit',
            'produit' => '/produit',
            'produit/new' => '/produit/new',
            'mouvements' => '/mouvement-stock',
            'mouvement' => '/mouvement-stock',
            'mouvement/new' => '/mouvement-stock/new',
            
            // 🌾 Récoltes
            'recoltes' => '/recolte',
            'recolte' => '/recolte',
            'moisson' => '/recolte',
            'recolte/new' => '/recolte/new',
            
            // 💰 Ventes
            'ventes' => '/vente',
            'vente' => '/vente',
            'vente/new' => '/vente/new',
            
            // 🚿 Irrigation
            'irrigation' => '/systeme-irrigation',
            'systeme-irrigation' => '/systeme-irrigation',
            'arrosage' => '/systeme-irrigation',
            
            // 🌤️ Météo
            'meteo' => '/meteo-irrigation',
            'meteo-irrigation' => '/meteo-irrigation',
            'temps' => '/meteo-irrigation',
            
            // ⚠️ Alertes & Risques
            'alertes' => '/alerte-risque',
            'alerte-risque' => '/alerte-risque',
            'risques' => '/alerte-risque',
            
            // 👤 Profil & Utilisateur
            'profil' => '/user',
            'user' => '/user',
            'profile' => '/user',
            'parametres' => '/user/parametres',
            'settings' => '/user/parametres',
            
            // 🛒 Marketplace
            'marketplace' => '/marketplace',
            'marche' => '/marketplace',
            'boutique' => '/marketplace',
            'orders' => '/marketplace/order',
            'commandes' => '/marketplace/order',
            'cart' => '/marketplace/cart',
            'panier' => '/marketplace/cart',
            
            // ⚙️ Admin
            'admin' => '/admin',
            'administration' => '/admin',
            'backoffice' => '/admin',
            'stats' => '/admin/dashboard',
            'statistics' => '/admin/dashboard',
            'dashboard_admin' => '/admin/dashboard',
            
            // 👥 Utilisateurs
            'users' => '/admin/users',
            'utilisateurs' => '/admin/users',
            'add_user' => '/register',
            'new_user' => '/register',
            'register' => '/register',
            
            // 🔐 Auth
            'login' => '/login',
            'connexion' => '/login',
            'logout' => '/logout',
            'deconnexion' => '/logout',
            
            // 📊 Stats utilisateur
            'statistiques' => '/statistiques',
            'statistique' => '/statistiques',
        ];

        $route = $routes[mb_strtolower($destination)] ?? '/';

        return [
            'success' => true,
            'message' => "🧭 Navigation vers {$destination}...",
            'action' => 'navigate',
            'data' => [
                'redirect' => $route,
                'destination' => $destination
            ]
        ];
    }

    // ==================== 📊 INFORMATIONS ====================

    private function handleGetStats(User $user): array
    {
        $cultures = count($user->getCultures());
        $parcelles = count($user->getParcelles());
        $produits = count($user->getProduits());
        
        $tachesRepo = $this->entityManager->getRepository(Tache::class);
        $tachesEnCours = count($tachesRepo->findBy([
            'id_user' => $user->getId(),
            'status_tache' => 'En attente'
        ]));

        return [
            'success' => true,
            'message' => "📊 Votre ferme: {$cultures} cultures, {$parcelles} parcelles, {$tachesEnCours} tâches en attente",
            'action' => 'get_stats',
            'data' => [
                'cultures' => $cultures,
                'parcelles' => $parcelles,
                'produits' => $produits,
                'taches_en_attente' => $tachesEnCours
            ]
        ];
    }

    private function handleGetWeather(User $user): array
    {
        // Récupérer la première parcelle pour la localisation
        $parcelles = $user->getParcelles();
        if ($parcelles->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Ajoutez d\'abord une parcelle avec coordonnées GPS.',
                'action' => 'get_weather'
            ];
        }

        $parcelle = $parcelles->first();
        if (!$parcelle instanceof \App\Entity\Parcelle) {
            return [
                'success' => false,
                'message' => 'Aucune parcelle valide trouvée.',
                'action' => 'get_weather'
            ];
        }
        $coords = $parcelle->getCoordonneesGps();
        
        if (!$coords) {
            return [
                'success' => false,
                'message' => 'Coordonnées GPS manquantes pour la météo.',
                'action' => 'get_weather'
            ];
        }

        [$lat, $lon] = array_map('trim', explode(',', $coords));
        
        try {
            $weather = $this->meteoService->getWeatherData((float) $lat, (float) $lon);
            
            return [
                'success' => true,
                'message' => "🌤️ Météo: {$weather['temperature']}°C, {$weather['description']}",
                'action' => 'get_weather',
                'data' => $weather
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Impossible de récupérer la météo.',
                'action' => 'get_weather'
            ];
        }
    }

    private function handleGetRisks(User $user): array
    {
        $risks = $this->riskService->analyzeRisksForUser($user);
        $count = count($risks);

        return [
            'success' => true,
            'message' => $count > 0 
                ? "⚠️ {$count} risques détectés sur vos cultures" 
                : "✅ Aucun risque majeur détecté",
            'action' => 'get_risks',
            'data' => [
                'risks' => $risks,
                'count' => $count,
                'redirect' => '/alerte-risque'
            ]
        ];
    }

    private function handleGetRecommendations(User $user): array
    {
        // Analyse simple basée sur les données
        $cultures = $user->getCultures();
        $recommendations = [];

        foreach ($cultures as $culture) {
            $etat = $culture->getEtatCroissance();
            $nom = $culture->getNomCulture();
            
            if ($etat === 'Semis') {
                $recommendations[] = "🌱 {$nom}: Surveiller l'arrosage régulier";
            } elseif ($etat === 'Croissance') {
                $recommendations[] = "🌾 {$nom}: Vérifier les nutriments du sol";
            } elseif ($etat === 'Floraison') {
                $recommendations[] = "🌸 {$nom}: Protection contre les intempéries";
            } elseif ($etat === 'Recolte') {
                $recommendations[] = "🌾 {$nom}: Prêt pour la récolte !";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = "Ajoutez des cultures pour recevoir des recommandations personnalisées.";
        }

        return [
            'success' => true,
            'message' => "💡 " . count($recommendations) . " recommandations pour votre ferme",
            'action' => 'get_recommendations',
            'data' => [
                'recommendations' => $recommendations
            ]
        ];
    }

    // ==================== 🚿 IRRIGATION ====================

    private function handleScheduleIrrigation(User $user, array $intent): array
    {
        $parcelleId = $intent['parcelle_id'] ?? null;
        $heure = $intent['heure'] ?? '06:00';
        $duree = $intent['duree'] ?? 30;

        if (!$parcelleId) {
            return [
                'success' => false,
                'message' => 'Veuillez préciser la parcelle à irriguer.',
                'action' => 'schedule_irrigation'
            ];
        }

        $parcelle = $this->entityManager->getRepository(Parcelle::class)->find($parcelleId);
        
        if (!$parcelle || $parcelle->getOwner() !== $user) {
            return [
                'success' => false,
                'message' => 'Parcelle non trouvée.',
                'action' => 'schedule_irrigation'
            ];
        }

        // Créer une tâche d'irrigation
        $tache = new Tache();
        $tache->setTittreTache("Irrigation {$parcelle->getNomParcelle()}");
        $tache->setDescriptionTache("Irrigation programmée à {$heure} pendant {$duree} minutes");
        $tache->setTypeTache('Irrigation');
        $tache->setStatusTache('En attente');
        $tache->setIdUser($user->getId());
        $tache->setDateTache(new \DateTime('tomorrow'));

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🚿 Irrigation de {$parcelle->getNomParcelle()} programmée à {$heure}",
            'action' => 'schedule_irrigation',
            'data' => [
                'parcelle' => $parcelle->getNomParcelle(),
                'heure' => $heure,
                'duree' => $duree,
                'redirect' => '/systeme-irrigation'
            ]
        ];
    }

    // ==================== 🏞️ PARCELLES ====================

    private function handleCreateParcelle(User $user, array $intent): array
    {
        $nom = $intent['nom'] ?? 'Nouvelle parcelle';
        $surface = $intent['surface'] ?? 0;
        $typeSol = $intent['type_sol'] ?? 'Terreau';
        $coords = $intent['coordonnees'] ?? null;

        $parcelle = new Parcelle();
        $parcelle->setNomParcelle($nom);
        $parcelle->setSuperficie((float) $surface);
        $parcelle->setTypeSol($typeSol);
        $parcelle->setCoordonneesGps($coords);
        $parcelle->setOwner($user);
        $parcelle->setDateAjout(new \DateTime());

        $this->entityManager->persist($parcelle);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🏞️ Parcelle \"{$nom}\" créée ({$surface} hectares)",
            'action' => 'create_parcelle',
            'data' => [
                'id' => $parcelle->getId(),
                'nom' => $nom,
                'surface' => $surface,
                'redirect' => '/parcelle'
            ]
        ];
    }

    private function handleUpdateParcelle(User $user, array $intent): array
    {
        $parcelleId = $intent['parcelle_id'] ?? $intent['id'] ?? null;
        if (!$parcelleId) {
            return ['success' => false, 'message' => 'ID de parcelle requis'];
        }

        $parcelle = $this->entityManager->getRepository(Parcelle::class)->find($parcelleId);
        if (!$parcelle || $parcelle->getOwner() !== $user) {
            return ['success' => false, 'message' => 'Parcelle non trouvée'];
        }

        if (isset($intent['nom'])) $parcelle->setNomParcelle($intent['nom']);
        if (isset($intent['surface'])) $parcelle->setSuperficie((float) $intent['surface']);
        if (isset($intent['type_sol'])) $parcelle->setTypeSol($intent['type_sol']);
        if (isset($intent['coordonnees'])) $parcelle->setCoordonneesGps($intent['coordonnees']);

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🏞️ Parcelle #{$parcelleId} mise à jour",
            'action' => 'update_parcelle',
            'data' => ['redirect' => '/parcelle']
        ];
    }

    private function handleDeleteParcelle(User $user, array $intent): array
    {
        $parcelleId = $intent['parcelle_id'] ?? $intent['id'] ?? null;
        if (!$parcelleId) {
            return ['success' => false, 'message' => 'ID de parcelle requis'];
        }

        $parcelle = $this->entityManager->getRepository(Parcelle::class)->find($parcelleId);
        if (!$parcelle || $parcelle->getOwner() !== $user) {
            return ['success' => false, 'message' => 'Parcelle non trouvée'];
        }

        $nom = $parcelle->getNomParcelle();
        $this->entityManager->remove($parcelle);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🏞️ Parcelle \"{$nom}\" supprimée",
            'action' => 'delete_parcelle',
            'data' => ['redirect' => '/parcelle']
        ];
    }

    private function handleListParcelles(User $user, array $intent): array
    {
        $parcelles = $this->entityManager->getRepository(Parcelle::class)
            ->findBy(['owner' => $user], ['dateAjout' => 'DESC']);

        $count = count($parcelles);
        $list = array_map(fn($p) => [
            'id' => $p->getId(),
            'nom' => $p->getNomParcelle(),
            'surface' => $p->getSuperficie(),
            'type_sol' => $p->getTypeSol()
        ], $parcelles);

        return [
            'success' => true,
            'message' => "🏞️ {$count} parcelles enregistrées",
            'action' => 'list_parcelles',
            'data' => [
                'parcelles' => $list,
                'count' => $count,
                'redirect' => '/parcelle'
            ]
        ];
    }

    // ==================== � RÉCOLTES ====================

    private function handleCreateRecolte(User $user, array $intent): array
    {
        $cultureId = $intent['culture_id'] ?? $intent['culture'] ?? null;
        $quantite = $intent['quantite'] ?? 0;
        $date = $intent['date'] ?? 'now';
        $qualite = $intent['qualite'] ?? 'Bonne';

        if (!$cultureId) {
            return ['success' => false, 'message' => 'Culture requise pour la récolte'];
        }

        $culture = $this->entityManager->getRepository(Culture::class)->find($cultureId);
        if (!$culture || $culture->getOwner() !== $user) {
            return ['success' => false, 'message' => 'Culture non trouvée'];
        }

        $recolte = new Recolte();
        $recolte->setIdCulture($culture);
        $recolte->setQuantiteRecolte((float) $quantite);
        $recolte->setDateRecolte(new \DateTime($date));
        $recolte->setQualiteRecolte($qualite);
        $recolte->setStatut('Recoltée');

        $this->entityManager->persist($recolte);
        
        // Mettre à jour le statut de la culture
        $culture->setEtatCroissance('Récolte');
        
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🌾 Récolte: {$quantite}kg de {$culture->getNomCulture()}",
            'action' => 'create_recolte',
            'data' => [
                'id' => $recolte->getId(),
                'culture' => $culture->getNomCulture(),
                'quantite' => $quantite,
                'redirect' => '/recolte'
            ]
        ];
    }

    private function handleUpdateRecolte(User $user, array $intent): array
    {
        $recolteId = $intent['recolte_id'] ?? $intent['id'] ?? null;
        if (!$recolteId) {
            return ['success' => false, 'message' => 'ID de récolte requis'];
        }

        $recolte = $this->entityManager->getRepository(Recolte::class)->find($recolteId);
        if (!$recolte) {
            return ['success' => false, 'message' => 'Récolte non trouvée'];
        }

        if (isset($intent['quantite'])) $recolte->setQuantiteRecolte((float) $intent['quantite']);
        if (isset($intent['qualite'])) $recolte->setQualiteRecolte($intent['qualite']);
        if (isset($intent['statut'])) $recolte->setStatut($intent['statut']);

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🌾 Récolte #{$recolteId} mise à jour",
            'action' => 'update_recolte',
            'data' => ['redirect' => '/recolte']
        ];
    }

    private function handleDeleteRecolte(User $user, array $intent): array
    {
        $recolteId = $intent['recolte_id'] ?? $intent['id'] ?? null;
        if (!$recolteId) {
            return ['success' => false, 'message' => 'ID de récolte requis'];
        }

        $recolte = $this->entityManager->getRepository(Recolte::class)->find($recolteId);
        if (!$recolte) {
            return ['success' => false, 'message' => 'Récolte non trouvée'];
        }

        $this->entityManager->remove($recolte);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🌾 Récolte #{$recolteId} supprimée",
            'action' => 'delete_recolte',
            'data' => ['redirect' => '/recolte']
        ];
    }

    private function handleListRecoltes(User $user, array $intent): array
    {
        $recoltes = $this->entityManager->getRepository(Recolte::class)
            ->createQueryBuilder('r')
            ->join('r.id_culture', 'c')
            ->where('c.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('r.date_recolte', 'DESC')
            ->getQuery()
            ->getResult();

        $count = count($recoltes);
        $list = array_map(fn($r) => [
            'id' => $r->getId(),
            'culture' => $r->getIdCulture()?->getNomCulture(),
            'quantite' => $r->getQuantiteRecolte(),
            'date' => $r->getDateRecolte()?->format('d/m/Y'),
            'qualite' => $r->getQualiteRecolte()
        ], $recoltes);

        return [
            'success' => true,
            'message' => "🌾 {$count} récoltes enregistrées",
            'action' => 'list_recoltes',
            'data' => [
                'recoltes' => $list,
                'count' => $count,
                'redirect' => '/recolte'
            ]
        ];
    }

    // ==================== 📦 PRODUITS (CRUD complet) ====================

    private function handleCreateProduit(User $user, array $intent): array
    {
        $nom = $intent['nom'] ?? 'Nouveau produit';
        $categorie = $intent['categorie'] ?? 'Général';
        $quantite = $intent['quantite'] ?? 0;
        $unite = $intent['unite'] ?? 'kg';
        $seuil = $intent['seuil'] ?? 10;
        $prix = $intent['prix'] ?? 0;

        $produit = new Produit();
        $produit->setNomProduit($nom);
        $produit->setCategorie($categorie);
        $produit->setQuantiteDisponible((int) $quantite);
        $produit->setUnite($unite);
        $produit->setSeuilAlerte((int) $seuil);
        $produit->setPrixUnitaire((int) $prix);
        $produit->setOwner($user);

        $this->entityManager->persist($produit);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "📦 Produit \"{$nom}\" créé ({$quantite} {$unite})",
            'action' => 'create_produit',
            'data' => [
                'id' => $produit->getId(),
                'nom' => $nom,
                'redirect' => '/produit'
            ]
        ];
    }

    private function handleUpdateProduit(User $user, array $intent): array
    {
        $produitId = $intent['produit_id'] ?? $intent['id'] ?? null;
        if (!$produitId) {
            return ['success' => false, 'message' => 'ID produit requis'];
        }

        $produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
        if (!$produit || $produit->getOwner() !== $user) {
            return ['success' => false, 'message' => 'Produit non trouvé'];
        }

        if (isset($intent['nom'])) $produit->setNomProduit($intent['nom']);
        if (isset($intent['categorie'])) $produit->setCategorie($intent['categorie']);
        if (isset($intent['quantite'])) $produit->setQuantiteDisponible((int) $intent['quantite']);
        if (isset($intent['prix'])) $produit->setPrixUnitaire((int) $intent['prix']);
        if (isset($intent['seuil'])) $produit->setSeuilAlerte((int) $intent['seuil']);

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "📦 Produit #{$produitId} mis à jour",
            'action' => 'update_produit',
            'data' => ['redirect' => '/produit']
        ];
    }

    private function handleDeleteProduit(User $user, array $intent): array
    {
        $produitId = $intent['produit_id'] ?? $intent['id'] ?? null;
        if (!$produitId) {
            return ['success' => false, 'message' => 'ID produit requis'];
        }

        $produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
        if (!$produit || $produit->getOwner() !== $user) {
            return ['success' => false, 'message' => 'Produit non trouvé'];
        }

        $nom = $produit->getNomProduit();
        $this->entityManager->remove($produit);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "📦 Produit \"{$nom}\" supprimé",
            'action' => 'delete_produit',
            'data' => ['redirect' => '/produit']
        ];
    }

    private function handleListProduits(User $user, array $intent): array
    {
        $produits = $this->entityManager->getRepository(Produit::class)
            ->findBy(['owner' => $user], ['nom_produit' => 'ASC']);

        $count = count($produits);
        $list = array_map(fn($p) => [
            'id' => $p->getId(),
            'nom' => $p->getNomProduit(),
            'categorie' => $p->getCategorie(),
            'quantite' => $p->getQuantiteDisponible(),
            'unite' => $p->getUnite()
        ], $produits);

        return [
            'success' => true,
            'message' => "📦 {$count} produits en stock",
            'action' => 'list_produits',
            'data' => [
                'produits' => $list,
                'count' => $count,
                'redirect' => '/produit'
            ]
        ];
    }

    // ==================== 💰 VENTES (CRUD complet) ====================

    private function handleUpdateVente(User $user, array $intent): array
    {
        $venteId = $intent['vente_id'] ?? $intent['id'] ?? null;
        if (!$venteId) {
            return ['success' => false, 'message' => 'ID vente requis'];
        }

        $vente = $this->entityManager->getRepository(Vente::class)->find($venteId);
        if (!$vente) {
            return ['success' => false, 'message' => 'Vente non trouvée'];
        }

        if (isset($intent['acheteur'])) $vente->setNomAcheteur($intent['acheteur']);
        if (isset($intent['quantite'])) $vente->setQuantite((float) $intent['quantite']);
        if (isset($intent['prix'])) $vente->setPrixTotal((float) $intent['prix']);

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "💰 Vente #{$venteId} modifiée",
            'action' => 'update_vente',
            'data' => ['redirect' => '/vente']
        ];
    }

    private function handleDeleteVente(User $user, array $intent): array
    {
        $venteId = $intent['vente_id'] ?? $intent['id'] ?? null;
        if (!$venteId) {
            return ['success' => false, 'message' => 'ID vente requis'];
        }

        $vente = $this->entityManager->getRepository(Vente::class)->find($venteId);
        if (!$vente) {
            return ['success' => false, 'message' => 'Vente non trouvée'];
        }

        $this->entityManager->remove($vente);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "💰 Vente #{$venteId} supprimée",
            'action' => 'delete_vente',
            'data' => ['redirect' => '/vente']
        ];
    }

    // ==================== 🌱 CULTURES (CRUD complet) ====================

    private function handleDeleteCulture(User $user, array $intent): array
    {
        $cultureId = $intent['culture_id'] ?? $intent['id'] ?? null;
        if (!$cultureId) {
            return ['success' => false, 'message' => 'ID culture requis'];
        }

        $culture = $this->entityManager->getRepository(Culture::class)->find($cultureId);
        if (!$culture || $culture->getOwner() !== $user) {
            return ['success' => false, 'message' => 'Culture non trouvée'];
        }

        $nom = $culture->getNomCulture();
        $this->entityManager->remove($culture);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🌱 Culture \"{$nom}\" supprimée",
            'action' => 'delete_culture',
            'data' => ['redirect' => '/culture']
        ];
    }

    private function handleUpdateCulture(User $user, array $intent): array
    {
        $cultureId = $intent['culture_id'] ?? $intent['id'] ?? null;
        if (!$cultureId) {
            return ['success' => false, 'message' => 'ID culture requis'];
        }

        $culture = $this->entityManager->getRepository(Culture::class)->find($cultureId);
        if (!$culture || $culture->getOwner() !== $user) {
            return ['success' => false, 'message' => 'Culture non trouvée'];
        }

        if (isset($intent['nom'])) $culture->setNomCulture($intent['nom']);
        if (isset($intent['rendement'])) $culture->setRendementEstime((float) $intent['rendement']);
        if (isset($intent['date_semis'])) {
            $culture->setDateSemis(new \DateTime($intent['date_semis']));
        }

        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => "🌱 Culture #{$cultureId} mise à jour",
            'action' => 'update_culture',
            'data' => ['redirect' => '/culture']
        ];
    }

    // ==================== ❓ AIDE ====================

    private function handleHelp(): array
    {
        $commands = [
            '📋 Tâches: "Créer une tâche Arroser" / "Lister tâches" / "Terminer tâche 5" / "Supprimer tâche 3"',
            '🌱 Cultures: "Nouvelle culture Blé parcelle 3" / "Modifier culture 2" / "Supprimer culture 5"',
            '🏞️ Parcelles: "Créer parcelle Champ Nord 10 hectares" / "Lister parcelles" / "Supprimer parcelle 2"',
            '🌾 Récoltes: "Nouvelle récolte culture 3 500kg" / "Lister récoltes" / "Supprimer récolte 1"',
            '� Stock: "Créer produit Engrais 100kg" / "Ajouter stock 50kg" / "Alertes stock"',
            '💰 Ventes: "Nouvelle vente 100kg blé à Dupont" / "Modifier vente 3" / "Supprimer vente 2"',
            '🧭 Navigation: "Aller aux cultures" / "Ouvrir stock" / "Dashboard" / "Page ventes"',
            '📊 Info: "Statistiques" / "Météo" / "Risques" / "Conseils"',
            '🚿 Irrigation: "Programmer irrigation parcelle 1 à 6h"',
            '❓ Aide: "Aide" ou "Commandes" pour cette liste',
        ];

        return [
            'success' => true,
            'message' => '🎤 Commandes vocales disponibles:',
            'action' => 'help',
            'data' => [
                'commands' => $commands
            ]
        ];
    }
}
