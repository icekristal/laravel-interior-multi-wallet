<?php

namespace Icekristal\LaravelInteriorMultiWallet;

use Carbon\Carbon;
use Exception;
use Icekristal\LaravelInteriorMultiWallet\Facades\ImWallet;
use Icekristal\LaravelInteriorMultiWallet\Models\MultiWalletRestriction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait InteractsWithMultiWallet
{
    /**
     *
     * @param string|null $codeCurrency
     * @param string|null $balanceType
     * @return MorphMany
     */
    public function balanceTransaction(string|null $codeCurrency = null, string|null $balanceType = null): MorphMany
    {
        return $this->morphMany(config('im_wallet.multi_wallet_model', \Icekristal\LaravelInteriorMultiWallet\Models\MultiWallet::class), 'owner')
            ->when(!is_null($codeCurrency), fn($q) => $q->where('code_currency', $codeCurrency))
            ->when(!is_null($balanceType), fn($q) => $q->where('balance_type', $balanceType));
    }

    /**
     *
     * @return MorphMany
     */
    public function balanceWhoTransaction(): MorphMany
    {
        return $this->morphMany(config('im_wallet.multi_wallet_model', \Icekristal\LaravelInteriorMultiWallet\Models\MultiWallet::class), 'who');
    }

    /**
     * get balance user
     *
     * @param string $codeCurrency
     * @param string|null $balanceType
     * @param ?Carbon $dateAt
     * @return float|int
     */
    public function balance(string $codeCurrency = 'YE', string|null $balanceType = null, Carbon $dateAt = null): float|int
    {
        if (is_null($balanceType)) {
            $balanceType = config('im_wallet.balance_required_type') ?? 'main';
        }
        return ImWallet::setOwner($this)->setCurrency($codeCurrency)->setBalanceType($balanceType)->getBalance($dateAt) ?? 0;
    }

    /**
     * @param float|int $amount
     * @param int $typeDebit
     * @param string $codeCurrency
     * @param string $balanceType
     * @param null $who
     * @param array|null $otherInfo
     * @return Model|null
     * @throws Exception
     */
    public function debitBalance(float|int $amount, int $typeDebit = 101, string $codeCurrency = 'YE', string $balanceType = 'main', $who = null, array $otherInfo = null): ?Model
    {
        return ImWallet::setOwner($this)
            ->setType($typeDebit)->setCurrency($codeCurrency)->setBalanceType($balanceType)
            ->setAmount($amount)->setWho($who)->setOther($otherInfo)->executeTransaction();
    }

    /**
     * @param float|int $amount
     * @param int $typeCredit
     * @param string $codeCurrency
     * @param string $balanceType
     * @param null $who
     * @param array|null $otherInfo
     * @return Model|null
     * @throws Exception
     */
    public function creditBalance(float|int $amount, int $typeCredit = 203, string $codeCurrency = 'YE', string $balanceType = 'main', $who = null, array $otherInfo = null): ?Model
    {
        return ImWallet::setOwner($this)
            ->setType($typeCredit)->setCurrency($codeCurrency)->setBalanceType($balanceType)
            ->setAmount($amount)->setWho($who)->setOther($otherInfo)->executeTransaction();
    }

    /**
     * @param Builder $query
     * @param string|null $codeCurrency
     * @param string|null $balanceType
     * @param string|null $nameColumn
     * @return Builder
     */
    public function scopeBalance(Builder $query, string|null $codeCurrency = null, string|null $balanceType = null, string|null $nameColumn = 'amount'): Builder
    {
        if (is_null($balanceType)) {
            $balanceType = config('im_wallet.balance_required_type') ?? 'main';
        }

        if (is_null($codeCurrency)) {
            $codeCurrency = config('im_wallet.default_code_currency') ?? 'YE';
        }

        if (is_null($nameColumn)) {
            $nameColumn = 'amount_' . $codeCurrency;
        }

        return $query
            ->select(
                $this->getTable() . '.*',
                DB::raw("SUM(CASE WHEN multi_wallets.type < 200 THEN {$nameColumn}*1 ELSE {$nameColumn}*-1 END) as {$nameColumn}"),
            )
            ->join('multi_wallets', function (JoinClause $join) use ($codeCurrency, $balanceType) {
                $join->on($this->getTable() . '.id', '=', 'multi_wallets.owner_id')
                    ->where('multi_wallets.owner_type', '=', self::class)
                    ->where('multi_wallets.balance_type', $balanceType)
                    ->where('multi_wallets.code_currency', $codeCurrency);
            })
            ->groupBy('multi_wallets.owner_id');
    }


    /**
     *
     * @return MorphMany
     */
    public function multiWalletRestriction(): MorphMany
    {
        return $this->morphMany(config('im_wallet.multi_wallet_restriction_model', \Icekristal\LaravelInteriorMultiWallet\Models\MultiWalletRestriction::class), 'target');
    }


    /**
     * @param int|null $type
     * @param string|int|null $codeCurrency
     * @param string|null $balanceType
     * @param null $untilAt
     * @param array|null $other
     * @return Model
     */
    public function blockTransaction(?int $type = null, string|int|null $codeCurrency = null, string|null $balanceType = null, $untilAt = null, ?array $other = null): Model
    {
        return ImWallet::setOwner($this)->setType($type)->setCurrency($codeCurrency)->setBalanceType($balanceType)->blockTransaction($untilAt, $other);
    }

    /**
     * @param int|null $type
     * @param string|int|null $codeCurrency
     * @param string|null $balanceType
     * @return mixed
     */
    public function unblockTransaction(?int $type, string|int|null $codeCurrency, ?string $balanceType): mixed
    {
        return ImWallet::setOwner($this)->setType($type)->setCurrency($codeCurrency)->setBalanceType($balanceType)->unBlockTransaction();
    }
}
