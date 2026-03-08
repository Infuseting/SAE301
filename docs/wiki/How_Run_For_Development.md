## How run (Development)

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