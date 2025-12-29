# Documentation Swagger et Lisibilité du Code

Une bonne documentation est cruciale, surtout pour l'API. Nous utilisons **L5-Swagger** (basé sur OpenAPI/Swagger) pour générer la documentation automatiquement à partir des commentaires du code.

## Commenter pour Swagger

Utilisez les annotations `OpenApi` (souvent aliasé en `OA`) dans les blocs de commentaires PHPDoc au-dessus de vos méthodes de contrôleur.

### Importation
Assurez-vous d'importer les annotations en haut de votre fichier contrôleur :

```php
use OpenApi\Annotations as OA;
```

### Exemple d'Annotation (Route GET)

```php
/**
 * Récupérer la liste des utilisateurs.
 *
 * @OA\Get(
 *     path="/api/users",
 *     tags={"Utilisateurs"},
 *     summary="Liste des utilisateurs",
 *     description="Retourne une liste paginée des utilisateurs enregistrés.",
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Numéro de la page",
 *         required=false,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Opération réussie",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé"
 *     )
 * )
 */
public function index() { ... }
```

### Éléments Clés
- **path** : L'URL de la route.
- **tags** : Catégorie pour regrouper les routes dans l'interface Swagger.
- **summary** : Titre court.
- **description** : Explication détaillée.
- **@OA\Parameter** : Paramètres d'entrée (query, path, header).
- **@OA\Response** : Réponses possibles (200, 400, 404, etc.) avec leur structure (JsonContent).

## Générer la Documentation
Une fois les commentaires ajoutés, lancez la commande pour mettre à jour le fichier Swagger JSON :

```bash
php artisan l5-swagger:generate
```

La doc sera accessible via `/api/documentation`.

## Lisibilité du Code (Clean Code)

- **Commentaires Utiles** : Ne commentez pas le "quoi" (évident à la lecture du code), mais le "pourquoi" (la raison métier, une complexité particulière).
- **Nommage Explicite** : `getUserById($id)` est mieux que `get($id)`.
- **Petites Fonctions** : Une fonction doit faire une seule chose.
- **Type Hinting** : Typage des arguments et retours de fonction (ex: `public function index(): JsonResponse`).
