# Structure du Projet et Emplacement des Fichiers

Savoir où placer son code est essentiel pour respecter l'architecture MVC de Laravel.

## Vue d'ensemble (MVC)

- **M**odel (Modèle) : Données et logique métier (accès BDD).
- **V**iew (Vue) : Interface utilisateur (HTML/Blade).
- **C**ontroller (Contrôleur) : Orchestre la logique entre le Modèle et la Vue.

## Où créer mes fichiers ?

### 1. Contrôleurs (Logique)
**Dossier :** `app/Http/Controllers/`

Si vous créez une nouvelle logique pour une page ou une API, créez un contrôleur ici.
Commande : `php artisan make:controller NomController`

### 2. Modèles (Données)
**Dossier :** `app/Models/`

Représente une table de la base de données.
Commande : `php artisan make:model NomModele`

### 3. Vues (Interface)
**Dossier :** `resources/views/`

Fichiers `.blade.php`. Vous pouvez créer des sous-dossiers pour organiser vos vues (ex: `resources/views/auth/login.blade.php`).

### 4. Routes (URLs)
**Dossier :** `routes/`

- `web.php` : Routes pour l'interface web (retourne des vues HTML).
- `api.php` : Routes pour l'API (retourne du JSON, stateless).

### 5. Services et Logique Métier Complexe
Si votre contrôleur devient trop gros, vous pouvez créer :
- **Services** : `app/Services/` (à créer manuellement si besoin).
- **Request (Validation)** : `app/Http/Requests/` pour valider les formulaires (`php artisan make:request StoreUserRequest`).

## Résumé

| Je veux... | fichier à créer/modifier dans... |
| :--- | :--- |
| Créer une page visible | `resources/views/` + `routes/web.php` + `Controllers` |
| Créer une route API | `routes/api.php` + `Controllers` |
| Modifier la structure BDD | `database/migrations/` + `app/Models/` |
| Valider un formulaire | `app/Http/Requests/` |
