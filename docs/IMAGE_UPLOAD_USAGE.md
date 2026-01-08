# ImageUpload Component & Hook

Composants réutilisables pour la gestion des uploads d'images dans l'application.

## Composants créés

1. **`ImageUpload.jsx`** - Composant UI complet avec drag & drop
2. **`useImageUpload.js`** - Hook personnalisé pour la gestion d'état

## Utilisation

### Exemple 1 : Utilisation basique avec useForm (Inertia)

```jsx
import { useForm } from '@inertiajs/react';
import ImageUpload from '@/Components/ImageUpload';

export default function MyForm() {
    const { data, setData, post, errors } = useForm({
        title: '',
        image: null,
    });

    const handleImageChange = (file, previewUrl) => {
        setData('image', file);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('my.route'));
    };

    return (
        <form onSubmit={submit}>
            <ImageUpload
                label="Image du raid"
                name="raid_image"
                onChange={handleImageChange}
                error={errors.image}
                required={true}
                helperText="Image principale qui sera affichée sur la page du raid"
            />
            {/* Autres champs */}
        </form>
    );
}
```

### Exemple 2 : Mode édition avec image existante

```jsx
import { useForm } from '@inertiajs/react';
import ImageUpload from '@/Components/ImageUpload';

export default function EditForm({ raid }) {
    const { data, setData, put, errors } = useForm({
        title: raid.title,
        image: null,
    });

    return (
        <form onSubmit={submit}>
            <ImageUpload
                label="Image du raid"
                name="raid_image"
                onChange={(file) => setData('image', file)}
                currentImage={raid.raid_image} // Image existante
                error={errors.image}
            />
        </form>
    );
}
```

### Exemple 3 : Avec le hook useImageUpload

```jsx
import { useForm } from '@inertiajs/react';
import ImageUpload from '@/Components/ImageUpload';
import { useImageUpload } from '@/Hooks/useImageUpload';

export default function MyForm({ existingRaid = null }) {
    const { data, setData, post, errors } = useForm({
        title: '',
        image: null,
    });

    const {
        preview,
        file,
        handleImageChange,
        resetImage,
        hasChanged
    } = useImageUpload(existingRaid?.image_url);

    const handleChange = (file, previewUrl) => {
        handleImageChange(file, previewUrl);
        setData('image', file);
    };

    return (
        <form onSubmit={submit}>
            <ImageUpload
                label="Image"
                onChange={handleChange}
                currentImage={preview}
                error={errors.image}
            />

            {hasChanged() && (
                <button type="button" onClick={resetImage}>
                    Annuler les modifications
                </button>
            )}
        </form>
    );
}
```

## Props du composant ImageUpload

| Prop | Type | Défaut | Description |
|------|------|--------|-------------|
| `label` | string | 'Image' | Label affiché au-dessus du composant |
| `name` | string | 'image' | Attribut name de l'input |
| `onChange` | function | - | Callback appelée avec (file, previewUrl) |
| `currentImage` | string | null | URL de l'image actuelle (mode édition) |
| `error` | string | null | Message d'erreur à afficher |
| `required` | boolean | false | Si le champ est requis |
| `accept` | string | 'image/*' | Types de fichiers acceptés |
| `maxSize` | number | 5 | Taille max en MB |
| `className` | string | '' | Classes CSS supplémentaires |
| `helperText` | string | null | Texte d'aide sous le label |

## Fonctionnalités

✅ Drag & drop d'images  
✅ Prévisualisation en temps réel  
✅ Validation de type et taille  
✅ Boutons hover pour changer/supprimer  
✅ Mode dark compatible  
✅ Messages d'erreur intégrés  
✅ Support Tailwind CSS  
✅ Compatible Inertia useForm  

## Migration des formulaires existants

### Avant (NewRace.jsx)
```jsx
const [imagePreview, setImagePreview] = useState(null);

const handleImageChange = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        setData('image', file);
        setImagePreview(URL.createObjectURL(file));
    }
};

// Dans le JSX
<input type="file" onChange={handleImageChange} />
{imagePreview && <img src={imagePreview} />}
```

### Après (avec ImageUpload)
```jsx
// Plus besoin de state séparé !

// Dans le JSX
<ImageUpload
    label="Image de la course"
    name="race_image"
    onChange={(file) => setData('image', file)}
    error={errors.image}
/>
```

## Backend (Laravel)

Le composant fonctionne avec le code Laravel existant :

```php
public function store(Request $request)
{
    $request->validate([
        'image' => 'nullable|image|max:5120', // 5MB
    ]);

    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('images', 'public');
        $data['image_url'] = $path;
    }

    // ...
}
```
