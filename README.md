# projet_test

API Laravel modulaire centrée sur l’authentification (inscription, connexion, profil courant, déconnexion) via **Laravel Sanctum**, avec une page web d’accueil Laravel par défaut.

## 1) Aperçu du projet

`projet_test` est une base Laravel 13 avec une architecture modulaire (`app/Modules/*`) où le module `Administration` expose des routes API d’authentification sous `/api/v1/auth/*`. Le README actuel est quasi vide (titre seul).  
**Références :** `README.md:1`, `app/Providers/ModulesServiceProvider.php:13-26`, `app/Modules/Administration/Routes/api.php:6-13`, `composer.json:10-12`

## 2) Fonctionnalités implémentées

### API Authentification
- `POST /api/v1/auth/signup` : crée un utilisateur + retourne un token Sanctum.  
- `POST /api/v1/auth/login` : authentifie par email/mot de passe + retourne un token.  
- `GET /api/v1/auth/me` : retourne l’utilisateur authentifié (protégé Sanctum).  
- `POST /api/v1/auth/logout` : révoque le token courant (protégé Sanctum).  
**Références :** `app/Modules/Administration/Routes/api.php:6-13`, `app/Modules/Administration/Controllers/AuthController.php:16-57`

### Validation
- Inscription : `name` requis, `email` valide et unique, `password` confirmée, min 8, maj/min + chiffres.  
- Connexion : `email` et `password` requis.  
**Références :** `app/Modules/Administration/Requests/SignupRequest.php:23-30`, `app/Modules/Administration/Requests/LoginRequest.php:19-22`

### Modèle utilisateur et sécurité
- Modèle `User` custom dans le module Administration.
- Hash automatique du mot de passe via cast `hashed`.
- Champs sensibles cachés (`password`, `remember_token`).
- Génération de tokens via `HasApiTokens` (Sanctum).  
**Références :** `app/Modules/Administration/Models/User.php:14-33`, `config/auth.php:64-68`

### Ressource API
- `UserResource` expose seulement `id`, `name`, `email`.  
**Références :** `app/Modules/Administration/Resources/UserResource.php:13-20`

### Front web
- Route `/` affiche `welcome.blade.php` (template Laravel standard).  
- La vue contient des liens login/register conditionnels mais ces routes web ne sont pas définies ici.  
**Références :** `routes/web.php:5-7`, `resources/views/welcome.blade.php:22-44`, `resources/views/welcome.blade.php:219-221`

## 3) Stack technique

- **Backend** : PHP `^8.3`, Laravel Framework `^13.17`.  
- **Auth API** : Laravel Sanctum `^4.0`.  
- **Frontend build** : Vite `^8`, Tailwind CSS `^4`, `laravel-vite-plugin`.  
- **Tests** : PHPUnit `^12.5`.  
**Références :** `composer.json:9-12`, `composer.json:21`, `package.json:10-14`, `vite.config.js:7-18`

## 4) Installation & setup environnement

### Prérequis
- PHP 8.3+, Composer, Node.js/npm, base SQLite (par défaut).

### Installation rapide (inférée des scripts)
1. `composer install`
2. Copier `.env.example` vers `.env`
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install --ignore-scripts`
6. `npm run build`  
**Références :** `composer.json:36-43`

### Variables d’environnement importantes
- DB par défaut : `sqlite`
- Session : `database`
- Queue : `database`
- Cache : `database`  
**Références :** `.env.example:23`, `.env.example:30`, `.env.example:38`, `.env.example:40`

## 5) Base de données (migrations/seed)

### Migrations présentes
- `users`, `password_reset_tokens`, `sessions`
- `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`
- `personal_access_tokens` (Sanctum)  
**Références :** `database/migrations/0001_01_01_000000_create_users_table.php:14-37`, `database/migrations/0001_01_01_000001_create_cache_table.php:14-24`, `database/migrations/0001_01_01_000002_create_jobs_table.php:14-47`, `database/migrations/2026_08_24_135946_create_personal_access_tokens_table.php:14-23`

### Seed
- Un utilisateur de test : `test@example.com`.  
**Références :** `database/seeders/DatabaseSeeder.php:20-23`

## 6) Utilisation (API)

Base URL locale (selon `.env.example`) : `http://localhost:8000`  
**Référence :** `.env.example:5`

Endpoints :
- `POST /api/v1/auth/signup`
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me` (******
- `POST /api/v1/auth/logout` (******  
**Référence :** `app/Modules/Administration/Routes/api.php:6-13`

## 7) Structure du projet (résumé)

- `app/Modules/Administration/Controllers/AuthController.php` : logique auth
- `app/Modules/Administration/Requests/*` : validation
- `app/Modules/Administration/Models/User.php` : modèle utilisateur
- `app/Modules/Administration/Resources/UserResource.php` : sérialisation API
- `app/Modules/Administration/Routes/api.php` : routes module
- `app/Providers/ModulesServiceProvider.php` : auto-chargement des routes modules sous `/api`
- `routes/web.php` : route web `/`
- `resources/views/welcome.blade.php` : vue d’accueil
- `resources/js/app.js` : vide
- `resources/css/app.css` : Tailwind
- `tests/Feature/AuthenticationApiTest.php` : tests API auth complets  
**Références :** fichiers ci-dessus, notamment `app/Providers/ModulesServiceProvider.php:19-25`, `resources/js/app.js:1`, `resources/css/app.css:1`

## 8) Tests & état de couverture

Le projet inclut des tests feature détaillés pour signup/login/me/logout, erreurs de validation, rejet token invalide, et révocation du token courant.  
**Références :** `tests/Feature/AuthenticationApiTest.php:16-227`, `tests/Feature/AuthenticationFoundationTest.php:13-30`

## 9) Notes / limitations actuelles

- Le frontend est essentiellement le template Laravel par défaut (pas d’UI métier dédiée).  
- `resources/js/app.js` est vide.  
- Pas d’autres modules métier visibles que `Administration`.  
- Le README existant n’est pas documenté.  
**Références :** `resources/views/welcome.blade.php:20-70`, `resources/js/app.js:1`, `app/Modules/Administration/...`, `README.md:1`
