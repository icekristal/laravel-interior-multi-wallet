<?php

namespace Icekristal\LaravelInteriorMultiWallet\Facades;

use Carbon\Carbon;
use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletBalanceTypeEnum;
use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletCurrencyEnum;
use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletTypeEnum;
use Icekristal\LaravelInteriorMultiWallet\Services\ImWalletService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ImWalletService setOwner(object $owner)
 * @method static ImWalletService setAmount(float|int $amount)
 * @method static ImWalletService setType(int|ImWalletTypeEnum $type)
 * @method static ImWalletService setWho(?object $who)
 * @method static ImWalletService setOther(?array $other)
 * @method static ImWalletService setCurrency(string|ImWalletCurrencyEnum $currency)
 * @method static ImWalletService setBalanceType(string|ImWalletBalanceTypeEnum $balanceType)
 * @method static void executeTransaction()
 * @method static bool isValid()
 * @method static mixed blockTransaction(Carbon $untilAt = null, ?array $other = null)
 * @method static mixed unBlockTransaction()
 * @method static float|int getBalance(Carbon $dateAt = null)
 */
class ImWallet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'im_wallet.service';
    }
}
