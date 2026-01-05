# Système de Tracking (Logs d'Activité)

Nous utilisons le package `spatie/laravel-activitylog` pour surveiller et enregistrer les activités des utilisateurs au sein de l'application. Cela permet de garder une trace des créations, modifications et suppressions de modèles, ainsi que d'autres actions manuelles.

## Installation et Configuration

Le package est déjà installé et configuré.
*   Fichier de configuration : `config/activitylog.php`
*   Migration : La table `activity_log` est déjà présente en base de données.

## Logging Automatique sur les Modèles

Pour enregistrer automatiquement les changements sur un modèle (ex: User, Product), vous devez utiliser le trait `LogsActivity` et implémenter l'interface `Loggable` (optionnel mais recommandé pour la configuration) ou définir la méthode `getActivitylogOptions`.

### Ajouter le tracking à un modèle

Exemple avec le modèle `User` :

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Model
{
    use LogsActivity;

    // Configuration des logs pour ce modèle
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role']) // Attributs à surveiller
            ->logOnlyDirty() // Ne log que ce qui a changé
            ->dontSubmitEmptyLogs(); // N'enregistre rien s'il n'y a pas de changement réel
    }
}
```

Avec cette configuration, chaque fois qu'un utilisateur est créé, modifié ou supprimé, une ligne sera ajoutée dans la table `activity_log`.

## Logging Manuel

Vous pouvez également créer des logs manuellement depuis n'importe où dans le code (Contrôleurs, Services...) :

```php
activity()
   ->causedBy($user) // L'utilisateur qui fait l'action
   ->performedOn($order) // L'objet concerné
   ->withProperties(['customProperty' => 'customValue']) // Métadonnées
   ->log('Order processed'); // Description du log
```

## Visualisation des Logs

Une interface d'administration est disponible pour consulter les logs :
**Route :** `/admin/logs`
**Fichier React :** `resources/js/Pages/Admin/Logs.jsx`

Cette page permet de :
*   Voir la liste des activités (qui a fait quoi, quand).
*   Filtrer par niveau de log ou recherche (selon implémentation).
*   Voir les détails des changements (JSON des propriétés modifiées).

## Bonnes Pratiques

*   **Sécurité** : Ne loggez jamais de données sensibles comme des mots de passe ou des tokens complets. Utilisez `$hidden` sur vos modèles ou configurez `logOnly` soigneusement.
*   **Performance** : Logger absolument tout peut remplir la base de données rapidement. Ciblez les actions critiques (changements de permissions, actions financières, modifications de profil).
