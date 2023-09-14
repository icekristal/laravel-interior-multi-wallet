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

lang:
```php
php artisan vendor:publish --provider="Icekristal\LaravelInteriorMultiWallet\IceInteriorMultiWalletServiceProvider" --tag="translations"
```


use:
```php
use Icekristal\LaravelInteriorMultiWallet\InteractsWithMultiWallet;

class User extends Model
{
    use InteractsWithMultiWallet;
}
```

get balance user:
```php
$balanceDefaultCurrency = $modelUser->balance();
$balanceOtherCurrency = $modelUser->balance('key_other_currency'); //default balanceType = main
$balanceOtherCurrencyAndTypeBalance = $modelUser->balance('key_currency', 'demo'); //default balanceType = main
```

set debit balance:
```php
$modelUser->debitBalance($amount, config('im_wallet.debit.put')); //Debit default currency
$modelUser->debitBalance($amount, config('im_wallet.debit.put'), 'key_other_currency'); //Debit other currency
$modelUser->debitBalance($amount, config('im_wallet.debit.put'), 'key_currency', 'type_balance'); //Debit other type_balance, see config im_wallet
```

set credit balance:
```php
$modelUser->creditBalance($amount, config('im_wallet.credit.withdrawal')); //Credit default currency
$modelUser->creditBalance($amount, config('im_wallet.credit.withdrawal'), 'key_other_currency'); //Credit other currency
$modelUser->creditBalance($amount, config('im_wallet.credit.withdrawal'), 'key_currency', 'type_balance'); //Credit other type_balance, see config im_wallet
```


see transaction user
```php
$modelUser->balanceTransaction()->get(); //All transaction
$modelUser->balanceTransaction($codeCurrency, $balanceType)->get(); //All transaction only codeCurrency and balanceType
```

block/unblock transaction user
```php
$modelUser->blockTransaction($typeCredit, $codeCurrency, $balanceType); //Block transaction all params (permanent)
$modelUser->blockTransaction($typeCredit, $codeCurrency); //Block transaction only typeCredit and codeCurrency
$modelUser->blockTransaction(null, $codeCurrency, $balanceType); //Block transaction only codeCurrency and balanceType

$modelUser->unblockTransaction($typeCredit, $codeCurrency, $balanceType); //Unblock transaction all params
$modelUser->unblockTransaction($typeCredit, null, $balanceType); //Unblock only typeCredit and balanceType
$modelUser->unblockTransaction($typeCredit, $codeCurrency); //Unblock only typeCredit and codeCurrency
```
