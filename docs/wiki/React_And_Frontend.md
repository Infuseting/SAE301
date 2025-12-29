# Développement Frontend avec React et Inertia

Le projet utilise **React** comme framework frontend, couplé avec **Inertia.js** pour faire le lien avec Laravel. Cela permet de créer une Single Page App (SPA) tout en gardant le routing et les contrôleurs "classiques" de Laravel.

## Structure des Dossiers (`resources/js/`)

Tout le code frontend se trouve dans `resources/js/`.

- **Pages/** : Correspond aux "Vues" de votre application. Chaque fichier ici correspond généralement à une route Laravel.
- **Components/** : Composants réutilisables (Boutons, Inputs, Cartes...). Ils ne sont pas liés à une route spécifique.
- **Layouts/** : Structures de pages (Barre de navigation, Footer, Sidebar) qui entourent vos pages.

## Comment créer une nouvelle Page ?

### 1. Créer le fichier React
Créez un fichier `.jsx` dans `resources/js/Pages/`.
Exemple : `resources/js/Pages/MonProfil.jsx`

```jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function MonProfil({ auth, user }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Mon Profil</h2>}
        >
            <Head title="Mon Profil" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            Bonjour {user.name} !
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

### 2. Retourner la Page depuis le Contrôleur
Dans votre contrôleur Laravel, utilisez `Inertia::render` au lieu de `view()`.

```php
use Inertia\Inertia;

public function show()
{
    return Inertia::render('MonProfil', [
        'user' => Auth::user(), // Passer des données au composant React
    ]);
}
```
*Note : Le nom 'MonProfil' doit correspondre au nom du fichier dans `Pages` (sans .jsx).*

## Comment créer un Composant ?

Si vous avez un élément graphique que vous réutilisez (ex: un bouton spécial), créez-le dans `resources/js/Components/`.

Exemple : `resources/js/Components/PrimaryButton.jsx`

```jsx
export default function PrimaryButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 ${
                    disabled ? 'opacity-25' : ''
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
```

Ensuite, importez-le dans vos Pages :
```jsx
import PrimaryButton from '@/Components/PrimaryButton';

<PrimaryButton onClick={maFonction}>Cliquez ici</PrimaryButton>
```

## Compilation (Vite)

Pour que vos changements soient pris en compte, le serveur de développement Vite doit tourner :

```bash
npm run dev
```

Si vous modifiez un fichier `.jsx`, la page se rechargera automatiquement (Hot Module Replacement).
Pour la production (déploiement), les assets seront compilés via `npm run build`.
