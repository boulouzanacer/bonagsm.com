# Bona GSM

Monorepo:
- Backend Laravel: `safesoft-g2d`
- Mobile Flutter: `safesoft_g2d_mobile`

## Démarrage (Laravel)

### Prérequis
- PHP 8.2+
- Composer
- MySQL/MariaDB (XAMPP)
- Node.js + npm (pour Vite)

### Installation
```bash
cd safesoft-g2d
composer install
copy .env.example .env
php artisan key:generate
```

Configurer ensuite la base de données dans `.env` (voir section Variables).

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run dev
php artisan serve
```

API v1: `http://127.0.0.1:8000/api/v1`

### Queue (notifications)
Les notifications (database) sont envoyées via queue.
```bash
php artisan queue:work
```

## Démarrage (Flutter)

```bash
cd safesoft_g2d_mobile
flutter pub get
flutter run
```

### Base URL API
- Android Emulator: `http://10.0.2.2:8000/api/v1` (déjà configuré dans [api_constants.dart](file:///d:/xampp/htdocs/G2D/safesoft_g2d_mobile/lib/core/constants/api_constants.dart))
- Appareil physique: remplacer par l’IP LAN de la machine (ex: `http://192.168.1.10:8000/api/v1`)

## Firebase (FCM) – configuration Flutter

1. Créer un projet Firebase.
2. Ajouter une application Android (package name identique à `applicationId`).
3. Télécharger `google-services.json` et le placer dans `android/app/`.
4. Activer Firebase Cloud Messaging dans la console Firebase.
5. Lancer l’app et vérifier l’enregistrement du token via `POST /api/v1/fcm/token`.

## Postman

Collection: `safesoft-g2d/docs/postman_collection.json`

Variables Postman:
- `baseUrl` (ex: `http://127.0.0.1:8000`)
- `clientToken` (Bearer token Sanctum)
- `pmeToken` (token fournisseur PME)

## Variables d’environnement (Laravel)

Minimum recommandé (MySQL):
- `APP_URL`
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

Queue:
- `QUEUE_CONNECTION=database`

CORS:
- Config dans `config/cors.php` (par défaut permissif pour Flutter).

## Tests manuels (checklist)

### Auth
- [ ] Login admin → accès dashboard admin
- [ ] Login fournisseur → accès espace fournisseur uniquement
- [ ] Login client abonné mobile → voit produits de son fournisseur
- [ ] Client simple mobile → voit toutes boutiques
- [ ] Token invalide API → 401 retourné

### Fournisseur
- [ ] Créer produit avec 3 images → ordre correct
- [ ] Supprimer image → fichier supprimé du storage
- [ ] Changer statut commande → notification reçue sur mobile
- [ ] Token PME Pro affiché masqué + copie correcte

### Client Mobile
- [ ] Ajouter produits de 2 fournisseurs → dialog avertissement
- [ ] Passer commande → notification reçue par fournisseur web
- [ ] Statut commande mis à jour → push notification reçue
- [ ] Dark mode toggle persistant après fermeture app

### PME Pro API
- [ ] Sync clients avec token valide → clients créés avec type abonné
- [ ] Sync produits → upsert correct par référence
- [ ] Récupérer commandes non synchronisées
- [ ] Marquer commande synchronisée → synced_pme = 1
- [ ] Token invalide → 401
