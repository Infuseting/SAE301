# Guide Complet des Tests (PHPUnit & Laravel)

Ce guide répertorie les outils et assertions disponibles pour tester l'application.

## 1. Structure et Commandes

### Création
```bash
php artisan make:test UserTest          # Feature (dossier tests/Feature)
php artisan make:test HelperTest --unit # Unit (dossier tests/Unit)
```

### Exécution
```bash
php artisan test                        # Tout lancer
php artisan test --filter UserTest      # Un seul fichier
php artisan test --filter test_login    # Une seule méthode
```

---

## 2. Les Assertions PHPUnit (Tests Unitaires)

Utilisées principalement dans `tests/Unit` pour vérifier la logique pure (sans BDD/HTTP).

```php
$this->assertTrue($condition);          // Vérifie que c'est vrai
$this->assertFalse($condition);         // Vérifie que c'est faux
$this->assertEquals($attendu, $reçut);  // Vérifie l'égalité de valeur
$this->assertSame($attendu, $reçut);    // Vérifie l'égalité de type ET valeur
$this->assertNull($variable);           // Vérifie que c'est null
$this->assertCount(3, $array);          // Vérifie la taille d'un tableau
$this->assertContains('valeur', $arr);  // Vérifie si une valeur est dans le tableau
$this->assertEmpty($array);             // Vérifie si c'est vide
$this->assertGreaterThan(10, $val);     // Vérifie si > 10
```

---

## 3. Tests HTTP et Réponses (Feature Tests)

Pour tester les contrôleurs, les routes API et les vues.

### Faire une requête
```php
$response = $this->get('/');
$response = $this->post('/users', ['name' => 'John']); // Avec données
$response = $this->put('/users/1', ['name' => 'Jane']);
$response = $this->delete('/users/1');
```

### Vérifier la réponse (Assertions HTTP)
```php
// Status Code
$response->assertStatus(200);           // OK
$response->assertStatus(404);           // Not Found
$response->assertForbidden();           // 403
$response->assertUnauthorized();        // 401
$response->assertRedirect('/home');     // Vérifie la redirection

// Contenu HTML/Texte
$response->assertSee('Bienvenue');      // Le texte est présent
$response->assertDontSee('Erreur');     // Le texte n'est pas présent
$response->assertViewIs('home.index');  // Vérifie quelle vue est retournée

// Données de Vue
$response->assertViewHas('users');      // La vue a reçu la variable $users
```

### Vérifier le JSON (Pour les API)
```php
$response->assertJson(['created' => true]); // Vérifie un fragment de JSON
$response->assertJsonPath('data.id', 5);    // Vérifie une valeur précise
$response->assertJsonCount(3, 'data');      // Vérifie le nombre d'éléments
$response->assertJsonStructure([            // Vérifie la structure
    'data' => [
        '*' => ['id', 'name', 'email']
    ]
]);
```

---

## 4. Base de Données et Factories

Pour tester des interactions avec la BDD, utilisez le trait `RefreshDatabase` au début de votre classe de test. Cela annule les modifications après chaque test.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase; // Indispensable pour ne pas polluer la BDD
    // ...
}
```

### Utiliser les Factories (Générer des fausses données)
```php
// Créer un utilisateur et le sauvegarder en BDD
$user = User::factory()->create();

// Créer 3 utilisateurs
$users = User::factory()->count(3)->create();

// Créer un utilisateur sans le sauvegarder (instance PHP seulement)
$user = User::factory()->make();

// États personnalisés (si définis dans la factory)
$admin = User::factory()->admin()->create();
```

### Assertions BDD
```php
// Vérifie qu'une ligne existe dans la table 'users'
$this->assertDatabaseHas('users', [
    'email' => 'john@example.com'
]);

// Vérifie qu'une ligne N'existe PAS
$this->assertDatabaseMissing('users', [
    'email' => 'deleted@example.com'
]);

// Vérifie le nombre total de lignes
$this->assertDatabaseCount('users', 5);
```

---

## 5. Authentification et Sessions

### Tester en tant qu'utilisateur connecté
```php
$user = User::factory()->create();

// Simuler la connexion de cet utilisateur
$response = $this->actingAs($user)->get('/dashboard');
```

### Assertions de Session et Validation
```php
// Vérifie qu'il y a des erreurs de validation (ex: formulaire invalide)
$response->assertSessionHasErrors(['email', 'password']);

// Vérifie qu'il n'y a PAS d'erreurs
$response->assertSessionHasNoErrors();

// Vérifie une variable de session (ex: message flash)
$response->assertSessionHas('success', 'Utilisateur créé !');
```

---

## 6. Mocks et Appels Externes (Avancé)

Si votre code appelle une API externe (Google, Stripe...), ne faites pas la vraie requête en test. Simulez-la (Mock).

```php
use Illuminate\Support\Facades\Http;

// Intercepter tous les appels HTTP sortants
Http::fake([
    'google.com/*' => Http::response(['foo' => 'bar'], 200),
]);

// Votre code qui appelle google.com recevra la fausse réponse
$response = $this->get('/mon-controller-qui-appelle-google');
```

---

## Résumé "Cheatsheet" Rapide

| Action | Commande / Méthode |
| :--- | :--- |
| **Lancer les tests** | `php artisan test` |
| **Créer un test** | `php artisan make:test NomTest` |
| **Vérifier égalité** | `$this->assertEquals($a, $b)` |
| **Vérifier BDD** | `$this->assertDatabaseHas('table', [...])` |
| **Vérifier Status HTTP** | `$response->assertStatus(200)` |
| **Vérifier JSON** | `$response->assertJson([...])` |
| **Utilisateur connecté** | `$this->actingAs($user)` |
| **Reset BDD** | `use RefreshDatabase;` |
