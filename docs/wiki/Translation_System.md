# Système de Traduction (i18n)

Le projet utilise le système de localisation natif de Laravel couplé à Inertia.js pour gérer les traductions côté frontend et backend.

## Structure des Dossiers

Les fichiers de traduction se trouvent dans le dossier `lang/` à la racine du projet. Chaque langue possède son propre sous-dossier :

*   `lang/en/` (Anglais)
*   `lang/fr/` (Français)
*   `lang/es/` (Espagnol)

À l'intérieur de ces dossiers, vous pouvez créer des fichiers PHP qui retournent un tableau de traductions. Par exemple, `lang/fr/messages.php` :

```php
<?php

return [
    'welcome' => 'Bienvenue sur notre application',
    'login' => 'Se connecter',
];
```

## Fonctionnement Backend -> Frontend

Le middleware `App\Http\Middleware\HandleInertiaRequests` se charge de charger automatiquement les fichiers de traduction de la langue courante et de les envoyer au frontend via les props Inertia.

```php
// HandleInertiaRequests.php
public function share(Request $request): array
{
    // ...
    return [
        // ...
        'translations' => $translations, // Contient toutes les clés de traduction
        'locale' => $locale, // La langue actuelle (ex: 'fr')
    ];
}
```

## Utilisation côté Frontend (React)

Les traductions sont accessibles via la prop `translations` de la page.

### 1. Accès direct dans un composant (Page)

Vous pouvez récupérer les traductions via le hook `usePage`.

```jsx
import { usePage } from '@inertiajs/react';

export default function Welcome() {
    // Récupère les traductions du fichier 'messages.php'
    const { translations } = usePage().props;
    const messages = translations.messages || {}; 

    return (
        <h1>{messages.welcome || 'Welcome'}</h1>
    );
}
```

### 2. Le composant LanguageSwitcher

Un composant `Resources/js/Components/LanguageSwitcher.jsx` est déjà disponible pour permettre aux utilisateurs de changer de langue. Il utilise des routes nommées `lang.switch`.

```jsx
import LanguageSwitcher from '@/Components/LanguageSwitcher';

// ...
<LanguageSwitcher />
```

## Ajouter une nouvelle langue

1.  Créez un nouveau dossier dans `lang/` (ex: `de` pour Allemand).
2.  Copiez les fichiers existants (ex: `messages.php`, `auth.php`) dans ce nouveau dossier.
3.  Traduisez les valeurs dans les tableaux PHP.
4.  Assurez-vous que votre sélecteur de langue (LanguageSwitcher) inclut cette nouvelle option.
