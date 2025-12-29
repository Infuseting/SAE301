# Bonnes Pratiques Git et Commits

Pour maintenir un historique propre et faciliter la collaboration, nous suivons certaines conventions pour les messages de commit et l'utilisation des Issues.

## Format d'un Message de Commit

Un bon message de commit doit être clair et descriptif. Nous recommandons la convention **Conventional Commits** :

```text
type(portée): description courte

[Corps du message optionnel : détails supplémentaires]

[Pied de page optionnel : Issues liées]
```

### Types courants

- **feat** : Nouvelle fonctionnalité.
- **fix** : Correction de bug.
- **docs** : Changements dans la documentation.
- **style** : Formatage, point-virgules manquants (pas de changement de code fonctionnel).
- **refactor** : Refactoring du code (ni fix, ni feat).
- **test** : Ajout ou correction de tests.
- **chore** : Tâches de maintenance (ex: mise à jour de dépendances).

### Exemples

```text
feat(auth): ajout de la connexion via Google

Ajoute le contrôleur SocialiteController et les routes nécessaires.
```

```text
fix(api): correction du crash lors de l'upload d'image

Vérifie maintenant si le fichier est présent avant de tenter l'upload.
```

## Lier les Commits aux Issues

GitHub permet de fermer automatiquement les issues ou de les lier via des mots-clés dans le message de commit.

### Mots-clés

Utilisez `Fixes #Numéro`, `Closes #Numéro`, ou `Resolves #Numéro`.

### Exemple

Si vous travaillez sur l'Issue #42 (Bug de login) :

```text
fix(login): correction de la validation du mot de passe

Le mot de passe nécessite maintenant 8 caractères minimum.

Fixes #42
```

Lors du push de ce commit sur la branche principale, l'issue #42 sera automatiquement fermée.
