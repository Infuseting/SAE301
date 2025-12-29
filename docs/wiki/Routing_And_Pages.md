# Créer une Nouvelle Page et sa Route

Ajouter une nouvelle page implique généralement trois étapes : la Route, le Contrôleur et la Vue.

## 1. Définir la Route

Allez dans le fichier `routes/web.php`. Ajoutez une nouvelle définition de route.

Exemple :

```php
use App\Http\Controllers\MaPageController;

// Route simple qui appelle une méthode de contrôleur
Route::get('/ma-page', [MaPageController::class, 'afficher']);

// Ou route directe vers une vue (si pas de logique complexe)
Route::view('/a-propos', 'pages.about');
```

## 2. Créer le Contrôleur (Optionnel mais recommandé)

Si votre page a besoin de charger des données (ex: liste de produits), passez par un contrôleur.

```bash
php artisan make:controller MaPageController
```

Dans `app/Http/Controllers/MaPageController.php` :

```php
public function afficher()
{
    $titre = "Bienvenue sur ma page";
    return view('pages.ma-page', ['titre' => $titre]);
}
```

## 3. Créer la Vue

Créez le fichier `.blade.php` dans `resources/views/`.
Exemple : `resources/views/pages/ma-page.blade.php`.

```html
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $titre }}</h1>
        <p>Ceci est le contenu de ma nouvelle page.</p>
    </div>
@endsection
```

## Vérification

1. Lancez le serveur : `php artisan serve`
2. Accédez à `http://127.0.0.1:8000/ma-page`

## Cas des Routes API

Si vous créez une route pour une API (JSON, pas de HTML), utilisez `routes/api.php`.
Ces routes sont automatiquement préfixées par `/api`.

```php
// routes/api.php
Route::get('/produits', [ProduitController::class, 'index']);
```
URL correspondante : `http://127.0.0.1:8000/api/produits`
