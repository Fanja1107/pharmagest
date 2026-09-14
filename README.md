# 💊 PharmaGest

Application web de gestion de pharmacie — projet pédagogique (L2 Informatique).

## 📋 Présentation

PharmaGest permet à une pharmacie de gérer l'ensemble de son activité : médicaments, catégories, unités, conditionnements, fournisseurs, lots, stock, approvisionnements, clients, ventes, factures, utilisateurs et statistiques.

Le projet applique la règle métier **FEFO** (First Expired, First Out) : lors d'une vente, le système consomme automatiquement en priorité les lots dont la date d'expiration est la plus proche.

## ✨ Fonctionnalités

- Authentification sécurisée avec gestion des rôles (admin / pharmacien / vendeur)
- Gestion complète des référentiels : catégories, unités, médicaments, conditionnements, fournisseurs, clients
- Gestion du stock par lots, avec calcul dynamique (jamais stocké en dur)
- Alertes automatiques : stock faible, rupture, expiration proche, lots expirés
- Approvisionnements en deux temps : brouillon (modifiable/annulable) puis validation (création des lots, mise à jour du stock)
- Interface de caisse avec panier, application automatique de la règle FEFO
- Génération de factures imprimables
- Dashboard avec statistiques et graphiques (Chart.js)
- Traçabilité complète : chaque vente enregistre exactement quels lots ont été consommés

## 🛠️ Technologies

**Front-end**
- HTML5, CSS3, JavaScript Vanilla
- SweetAlert2 (messages et confirmations)
- Chart.js (graphiques du dashboard)

**Back-end**
- PHP 8.3 (procédural organisé par dossiers fonctionnels)
- PDO avec requêtes préparées
- Sessions PHP natives

**Base de données**
- MySQL / MariaDB
- Vues SQL (`vue_stock_medicaments`, `vue_expirations`)

## 📦 Installation

### Prérequis

- PHP ≥ 8.1 avec l'extension `pdo_mysql` activée
- MySQL ou MariaDB
- Un serveur web (Apache via WAMP/XAMPP, ou le serveur intégré PHP)

Vérifier son environnement :

```bash
php -v
php -m | grep -i pdo    # ou, sous PowerShell : php -m | Select-String "pdo"
mysql --version
```

### 1. Récupérer le projet

Copier le dossier `pharmagest/` à l'emplacement de son choix.

### 2. Créer la base de données

Dans phpMyAdmin (ou en ligne de commande) :

- Créer une base nommée `pharmagest`
- Interclassement : `utf8mb4_unicode_ci`

### 3. Importer la structure

Importer le fichier `database/pharmagest.sql` dans la base `pharmagest` (onglet **Importer** de phpMyAdmin).

Ce fichier crée les 13 tables ainsi que les deux vues SQL (`vue_stock_medicaments`, `vue_expirations`).

### 4. Configurer la connexion à la base

Éditer `config/database.php` si besoin (nom d'utilisateur/mot de passe MySQL différents des valeurs par défaut) :

```php
$host = 'localhost';
$dbname = 'pharmagest';
$username = 'root';
$password = '';
```

### 5. Lancer le serveur

Avec le serveur intégré PHP, depuis le dossier `pharmagest/` :

```bash
php -S localhost:8080
```

Puis ouvrir : `http://localhost:8080`

## 🔑 Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Admin | admin@pharmagest.mg | admin123 |
| Vendeur | vendeur@pharmagest.mg | vendeur123 |

⚠️ Ces mots de passe sont fournis à titre de démonstration uniquement — à changer avant toute mise en production réelle.

## 📁 Structure du projet

```text
pharmagest/
│
├── config/              → connexion PDO à la base
├── includes/            → auth, header, sidebar, footer, fonctions utilitaires
├── assets/               → CSS, JS (SweetAlert2, Chart.js)
├── auth/                 → login, logout
├── dashboard/            → tableau de bord et statistiques
├── categories/, unites/, medicaments/, conditionnements/
│                        → référentiels (CRUD)
├── fournisseurs/, clients/
│                        → tiers (CRUD)
├── lots/, stock/         → gestion du stock physique
├── approvisionnements/   → achats fournisseurs (brouillon → validation)
├── ventes/, factures/    → caisse, historique, facturation
├── utilisateurs/         → administration des comptes (admin uniquement)
├── database/             → script SQL complet (pharmagest.sql)
└── index.php
```

## 📐 Règles métier

1. Un médicament appartient à une catégorie ; une catégorie peut avoir plusieurs médicaments.
2. Un médicament peut avoir plusieurs conditionnements (comprimé, plaquette, boîte...) ; chaque conditionnement définit un **prix de vente** propre.
3. Le stock n'est **jamais stocké directement** sur le médicament : il est calculé dynamiquement comme la somme des quantités des lots actifs.
4. Chaque lot possède son propre numéro, sa date d'expiration et son prix d'achat (par unité de base).
5. Lors d'une vente, les lots sont consommés selon la règle **FEFO** : le lot qui expire le plus tôt est vidé en premier.
6. Un lot expiré ne peut jamais être vendu.
7. Le prix de vente utilisé au moment d'une vente est copié dans l'historique (`vente_details.prix_unitaire`) — un changement de prix ultérieur ne modifie jamais les anciennes factures.
8. Un approvisionnement passe par un statut `brouillon` (modifiable, annulable) avant sa **validation**, seul moment où le stock est réellement mis à jour.
9. Les stocks faibles et les expirations proches sont signalés automatiquement (dashboard, page stock, alerte à la connexion).
10. Les accès dépendent du rôle de l'utilisateur (voir section Sécurité).
11. La suppression physique est évitée pour tout élément lié à l'historique (médicaments, fournisseurs, conditionnements) — on utilise un statut `actif/inactif` à la place.

## 🔐 Sécurité

- Mots de passe hashés avec `password_hash()` / vérifiés avec `password_verify()`
- Toutes les requêtes SQL utilisent PDO avec requêtes préparées (aucune concaténation de données utilisateur)
- Affichage systématiquement échappé avec `htmlspecialchars()`
- Contrôle d'accès par rôle, vérifié côté serveur sur chaque page sensible (`requireRole()`), pas seulement dans l'interface
- Opérations critiques (validation d'achat, validation de vente) protégées par des transactions PDO (`beginTransaction` / `commit` / `rollBack`)
- Verrouillage des lignes de lots (`FOR UPDATE`) lors d'une vente, pour éviter les conflits de stock en cas d'accès concurrent
- Messages d'erreur techniques jamais affichés à l'utilisateur final (erreurs SQL capturées et reformulées)

### Rôles et permissions

| Rôle | Référentiels (médicaments, stock...) | Fournisseurs / Achats | Ventes / Clients | Utilisateurs |
|---|---|---|---|---|
| Admin | Gérer | Gérer | Gérer | Gérer |
| Pharmacien | Gérer | Gérer | Gérer | — |
| Vendeur | Consulter seulement | — | Gérer | — |

## 🧪 Tests réalisés

L'application a été testée sur les cas suivants : quantités et prix invalides, identifiants inexistants, stock insuffisant (y compris tentative de contournement via JavaScript), suppression d'éléments liés à l'historique, doubles soumissions, formulaires incomplets, emails invalides, injection SQL, accès non autorisés par rôle, responsive (mobile/tablette/desktop).

## 👤 Auteur

Projet réalisé dans le cadre d'un cursus L2 Informatique.