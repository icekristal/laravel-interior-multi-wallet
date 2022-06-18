install:
```php
composer require icekristal/laravel-interior-multi-wallet
```
migration:
```php
php artisan vendor:publish --provider="Icekristal\LaravelInteriorMultiWallet\IceInteriorMultiWalletServiceProvider" --tag="migrations"
```

config:
```php
php artisan vendor:publish --provider="Icekristal\LaravelInteriorMultiWallet\IceInteriorMultiWalletServiceProvider" --tag="config"
```
