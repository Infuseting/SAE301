# SAE301

This project is a school project build with Laravel / React / TailwindCSS

## Hosting

The project is actually available on [SAE301](https://sae301.infuseting.fr)

## How Run

```bash
composer install
```
```bash
npm install
```
</br>

> [!NOTE]
> Then copy the .env.example to .env and configure your database access.

</br>

```bash
php artisan migrate
```
```bash
php artisan key:generate
```
```bash
php artisan run
```

</br>

> [!TIP]
> ```php artisan run``` is a homemade custom command that launch both Laravel server and React dev server.

## How Contribute

1. Create new branch for each feature you want to add.
2. Do your features
3. Launch tests with `php artisan test:build`. If tests are not passed, fix your code.
4. Let your duo review your code. And create test related to your feature if needed.
5. Tell manager your branch is ready to merge.
6. Manager will merge your branch and launch tests on it. 
7. If it's ok, your branch will be launch on server.

## API

API of project is build with OpenAPI Format. You can find it on [API](https://sae301.infuseting.fr/api) 

You can find Swagger UI on [Swagger](https://sae301.infuseting.fr/api/documentation) 

## Wiki

- [Tests Unitaires et Fonctionnels](https://github.com/Infuseting/SAE301/wiki/Test) : Comment écrire et exécuter des tests PHPUnit.
- [Workflow Git et Bonnes Pratiques](https://github.com/Infuseting/SAE301/wiki/Workflow-Git) : Comment nommer ses commits et lier les Issues.
- [Structure du Projet](https://github.com/Infuseting/SAE301/wiki/Project-Structure) : Où créer ses fichiers (Modèles, Contrôleurs, Vues).
- [Documentation et Swagger](https://github.com/Infuseting/SAE301/wiki/Swagger-And-Comments) : Comment commenter son code pour générer la documentation API.
- [Création de Pages et Routage](https://github.com/Infuseting/SAE301/wiki/Routing-And-Page) : Comment ajouter une nouvelle page et définir sa route.
- [React et Page](https://github.com/Infuseting/SAE301/wiki/React-And-Page) : Comment ajouter une nouvelle page et définir sa route.

## Teams

Manager & Supervisor : [Infuseting](https://github.com/Infuseting/)

Duo 1 : [Antoine Matter](https://github.com/Antoin9-e) - [Rémy Leber](https://github.com/Remynder0) 

Duo 2 : [Marin Jabet](https://github.com/Mzrbt) - [Nathan Le Biez](https://github.com/nathan-lbz) 

Duo 3 : [Arthur Langlois](https://github.com/FxBam) - [ar7dx](https://github.com/aR7dx) 

Duo 4 : [Come GP](https://github.com/come-gp) - [Ewen Babin](https://github.com/EwenBabin) 

## License

MIT