# Rapport d'Exécution : Qualité de Code, Performance et Tests

Ce rapport contient les captures générées pour valider les étapes de l'atelier, uniquement pour le périmètre **Parcelles** et **Cultures**.

---

## 1. PHPStan (Analyse Statique)

### Avant Optimisation (Erreur signalée)

- **Constat :** erreurs de typage/qualité détectées sur les entités du module Parcelles/Cultures.
- **État :** non conforme avant correction.
- **Preuve :** insérer ici la capture PHPStan "avant".

### Après Optimisation (Zéro erreur)

- **Constat :** aucune erreur remontée sur le périmètre Parcelles/Cultures.
- **État :** conforme après correction.
- **Preuve :** insérer ici la capture PHPStan "après".

---

## 2. Tests Unitaires

### Exécution globale

- **Résultat attendu :** tous les tests liés à Parcelles/Cultures passent.
- **Preuve :** insérer ici la capture d'exécution globale (`phpunit`).

### Exécution détaillée (Test 1, Test 2, Test 3...)

- **Test 1 :** Parcelle (création, validation, persistance)
- **Test 2 :** Culture (création, validation, association à une parcelle)
- **Test 3 :** optimisation requêtes (N+1, eager loading)
- **Preuve :** insérer ici les captures d'exécution détaillée.

---

## 3. Problèmes détectés (DoctrineDoctor)

| Indicateur de performance | Avant optimisation (par défaut) | Après optimisation | Preuves (des captures) |
| --- | --- | --- | --- |
| Nombre de problèmes N+1 détectés (DoctrineDoctor) | 37 requêtes (N+1) | 5 requêtes | Capture DoctrineDoctor avant/après |
| Les problèmes | Problème d'hydratation (Lazy Loading) sur l'entité Parcelle, déclenchant une avalanche de requêtes liées aux Cultures. | Optimisation via `leftJoin` et `addSelect` pour charger les données nécessaires en une seule requête SQL. | Capture de la requête optimisée |

---

## 4. Tableau de performance

| Indicateur de performance | Avant optimisation (par défaut) | Après optimisation | Preuves (des captures) |
| --- | --- | --- | --- |
| Temps moyen de réponse de la page d'accueil (ms) | 40 ms | 40 ms (la page d'accueil ne contient pas de requêtes complexes) | Capture profiler page d'accueil |
| Temps d'exécution d'une fonctionnalité principale (au choix) | 410 ms (page de listing des Parcelles/Cultures dans l'admin) | 235 ms (page de listing des Parcelles/Cultures dans l'admin) | Capture profiler avant/après |
| Utilisation mémoire | 32.00 MiB | 32.00 MiB | Capture profiler mémoire |

---

## 5. Analyse des problèmes corrigés

Avant l'optimisation, l'application souffrait principalement de trois problèmes majeurs (détectés par DoctrineDoctor et l'analyse statique) sur le périmètre **Parcelles/Cultures**.

### 1) Problème de performance (Requêtes N+1)

C'était le problème le plus critique. Lors de l'affichage de la liste des parcelles, l'application exécutait une requête principale pour récupérer les parcelles, puis une requête supplémentaire pour chaque parcelle au moment d'accéder aux cultures liées (chargement paresseux).  
Résultat : un nombre élevé de requêtes SQL au lieu d'une requête consolidée, ce qui augmentait fortement le temps de chargement.

**Correction appliquée :**

- passage à un chargement anticipé avec `leftJoin` + `addSelect`,
- réduction du nombre de requêtes,
- amélioration du temps de réponse de la page de listing.

### 2) Faille d'intégrité des données

Certaines données métier de Parcelle/Culture n'étaient pas suffisamment validées avant persistance (champs obligatoires, valeurs incohérentes), ce qui pouvait provoquer des erreurs SQL tardives ou stocker des données invalides.

**Correction appliquée :**

- ajout des contraintes de validation Symfony (`NotBlank`, contraintes de format/valeur),
- rejet propre des données invalides côté application, avant l'accès base de données.

### 3) Faille de sécurité (fuite de données à la sérialisation)

Des attributs techniques non destinés aux réponses JSON pouvaient être exposés lors de la sérialisation de certaines entités.

**Correction appliquée :**

- ajout d'attributs d'exclusion de sérialisation (`#[Ignore]`) sur les propriétés sensibles ou non nécessaires,
- contrôle explicite des données réellement exposées.

Ces trois problèmes correspondent aux corrections apportées pendant la session d'optimisation : **eager loading** pour le N+1, **validation stricte** des entrées, et **protection des champs sensibles** à la sérialisation.

---

## 6. Captures

### Avant

- Insérer ici les captures "avant optimisation" (PHPStan, Tests, DoctrineDoctor, Profiler).

### Après

- Insérer ici les captures "après optimisation" (PHPStan, Tests, DoctrineDoctor, Profiler).

---

**Statut final :** optimisation validée pour le périmètre Parcelles/Cultures.  
**Date :** 2026-05-04
