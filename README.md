# Bona GSM

Plateforme e-commerce B2B (Laravel 11) pour grossiste. Le fournisseur gère son catalogue, ses clients et ses commandes ; les clients passent commande via le site ou l'API mobile.

- **Nom du site :** Bona GSM
- **URL de production :** https://bonagsm.com
- **Framework :** Laravel 11 (PHP 8.2+)
- **Base de données :** MySQL / MariaDB

## Démarrage (Laravel)

### Prérequis
- PHP 8.2+
- Composer
- MySQL / MariaDB (XAMPP, Laragon, etc.)
- Node.js + npm (pour Vite)

### Installation
```bash
# Cloner le dépôt et se placer dans le dossier
git clone https://github.com/boulouzanacer/bonagsm.com.git
cd bonagsm.com

# Dépendances PHP
composer install

# Fichier d'environnement
copy .env.example .env      # Windows
# cp .env.example .env      # Linux/macOS

php artisan key:generate
```

Configurer ensuite la base de données dans `.env` (voir section Variables d'environnement).

```bash
# Migrations + seeders (crée le fournisseur Bona GSM par défaut)
php artisan migrate --seed

# Lien symbolique vers le stockage public (images produits)
php artisan storage:link

# Assets frontend
npm install
npm run dev

# Serveur de développement
php artisan serve
```

Application web : `http://127.0.0.1:8000`
API v1 : `http://127.0.0.1:8000/api/v1`

### Compte fournisseur par défaut
Créé par le seeder `FournisseurSeeder` :
- **Email :** ``
- **Mot de passe :** ``

### Queue (notifications)
Les notifications (database) sont envoyées via queue.
```bash
php artisan queue:work
```

## Structure de la base de données

Tables principales :

| Table | Description |
|-------|-------------|
| `admin` | Administrateurs de la plateforme |
| `frs` | Fournisseurs (grossistes) |
| `frs_users` | Utilisateurs secondaires d'un fournisseur |
| `client` | Clients (type `abonne` ou `simple`) |
| `categories` / `sous_categories` | Catégories et sous-catégories de produits |
| `marques` | Marques |
| `produit` | Produits du catalogue |
| `produit_images` | Images des produits |
| `produit_quantity_prices` | Paliers de prix (prix dégressif par quantité) |
| `cmd1` | En-têtes de commandes |
| `cmd2` | Lignes de commandes |
| `frais_livraison` | Frais de livraison par wilaya/commune |
| `wilaya` / `commune` | Découpage administratif (Algérie) |
| `fcm_tokens` | Tokens Firebase Cloud Messaging (notifications push) |
| `notifications` | Notifications (base de données) |
| `client_wishlist` | Liste de souhaits des clients |
| `personal_access_tokens` | Tokens API Sanctum |

## API v1

Base : `http://127.0.0.1:8000/api/v1`

Endpoints principaux :
- `POST /auth/register` — Inscription client
- `POST /auth/verify-email` — Vérification email (code)
- `POST /auth/login` — Connexion client
- `POST /auth/logout` — Déconnexion
- `GET /auth/me` — Profil client
- `GET /boutiques` — Liste des fournisseurs
- `GET /boutiques/{id}` — Détail + produits d'une boutique
- `GET /produits` — Catalogue produits (avec filtres)
- `GET /produits/{id}` — Détail produit
- `POST /commandes` — Passer une commande
- `GET /commandes` — Commandes du client
- `GET /notifications` — Notifications
- `POST /fcm/token` — Enregistrer un token FCM

Endpoints PME Pro (token fournisseur) :
- `POST /pme/clients` — Synchroniser les clients
- `POST /pme/produits` — Synchroniser les produits
- `GET /pme/commandes` — Récupérer les commandes
- `POST /pme/commandes/{id}/sync` — Marquer une commande synchronisée

## Postman

Collection : `docs/postman_collection.json`

Variables Postman :
- `baseUrl` (ex: `http://127.0.0.1:8000`)
- `clientToken` (Bearer token Sanctum)
- `pmeToken` (token fournisseur — champ `token` de la table `frs`)

## Variables d'environnement (Laravel)

Minimum recommandé (MySQL) :
```env
APP_NAME="Bona GSM"
APP_URL=https://bonagsm.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bonagsm
DB_USERNAME=root
DB_PASSWORD=

# ID du fournisseur principal (single-tenant)
SINGLE_FRS_ID=1

# Sessions & cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Mail (vérification email clients) :
- Par défaut `MAIL_MAILER=log` (développement)
- En production : configurer SMTP ou Resend (`RESEND_API_KEY`)

CORS :
- Config dans `config/cors.php` (par défaut permissif pour les clients mobile/web).

## Déploiement (VPS)

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
npm install
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Fonctionnalités principales

### Espace Fournisseur (`/fournisseur`)
- Tableau de bord (statistiques, commandes récentes)
- Catalogue produits (CRUD, images, paliers de prix, promotions, TVA)
- Multisélection produits (supprimer / cacher / afficher en masse)
- Gestion catégories, sous-catégories, marques
- Gestion clients (avec approbation admin optionnelle)
- Commandes (changement de statut, notifications)
- Paramètres du site (logo, pixels Meta/TikTok, visibilité des prix/stock, approbation clients)
- Frais de livraison par wilaya/commune
- Gestion des utilisateurs du fournisseur
- Token PME Pro (synchronisation externe)
- Import produits (Excel/CSV)

### Site Client (`/`)
- Catalogue avec filtres (catégorie, sous-catégorie, marque, recherche)
- Fiche produit avec galerie d'images
- Panier multi-fournisseur
- Tunnel de commande (vérification email, frais de livraison)
- Espace client (commandes, profil, wishlist)
- Paiement à la livraison

### API Mobile
- Authentification client (Sanctum)
- Catalogue et détail produits
- Commandes
- Notifications push (Firebase)

## Tests manuels (checklist)

### Auth
- [ ] Login fournisseur → accès espace fournisseur
- [ ] Inscription client → email de vérification reçu
- [ ] Vérification email → accès compte
- [ ] Option approbation admin activée → client bloqué jusqu'à approbation
- [ ] Token invalide API → 401 retourné

### Fournisseur
- [ ] Créer produit avec images → ordre correct
- [ ] Multisélection → cacher/afficher/supprimer en masse
- [ ] Changer statut commande → notification client
- [ ] Approuver un client en attente → accès autorisé
- [ ] Import Excel → produits créés/mis à jour

### Client
- [ ] Ajouter produits de 2 fournisseurs → avertissement panier
- [ ] Passer commande → notification fournisseur
- [ ] Statut commande mis à jour → notification client
