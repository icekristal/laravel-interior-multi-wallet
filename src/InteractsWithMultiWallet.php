<?php

namespace Icekristal\LaravelInteriorMultiWallet;

use Carbon\Carbon;
use Exception;
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
     * @param ?Carbon $dateAt
     * @param string|null $balanceType
     * @return HigherOrderBuilderProxy|int|mixed
     */
    public function balance(string $codeCurrency = 'YE', string|null $balanceType = null, Carbon $dateAt = null): mixed
    {
        if (is_null($balanceType)) {
            $balanceType = config('im_wallet.balance_required_type') ?? 'main';
        }

        return $this->balanceTransaction()
            ->when(!is_null($dateAt), fn($q) => $q->where('created_at', '<=', $dateAt))
            ->where('balance_type', $balanceType)
            ->where('code_currency', $codeCurrency)->select(
                DB::raw('SUM(CASE WHEN type < 200 THEN amount ELSE amount*-1 END) as amount')
            )->first()?->amount ?? 0;
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
        $this->validation([
            'type_debit' => $typeDebit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
        ]);

        if($this->isBlockTransaction($typeDebit, $codeCurrency, $balanceType)) return null;

        $commission = $this->calcCommission($typeDebit, $amount);
        $amount -= $commission;
        return $this->balanceTransaction()->create([
            'type' => $typeDebit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
            'commission' => $commission,
            'other' => $otherInfo,
            'who_type' => !is_null($who) ? get_class($who) : null,
            'who_id' => !is_null($who) ? $who->id : null,
        ]);
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
        $this->validation([
            'type_credit' => $typeCredit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
        ]);

        if($this->isBlockTransaction($typeCredit, $codeCurrency, $balanceType)) return null;

        $commission = $this->calcCommission($typeCredit, $amount);
        $amount -= $commission;
        return $this->balanceTransaction()->create([
            'type' => $typeCredit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
            'other' => $otherInfo,
            'commission' => $commission,
            'who_type' => !is_null($who) ? get_class($who) : null,
            'who_id' => !is_null($who) ? $who->id : null,
        ]);
    }

    /**
     * calc commission
     *
     * @param int $type
     * @param float|int $amount
     * @return float|int
     */
    private function calcCommission(int $type, float|int $amount): float|int
    {
        if ($amount <= 0) return 0;
        $commissionDefault = config('im_wallet.commission_default') ?? 0;
        $additionalCommission = config("im_wallet.commission.$type") ?? null;
        if (!is_null($additionalCommission)) {
            $commission = $amount / 100 * $additionalCommission;
        } else {
            $commission = $amount / 100 * $commissionDefault;
        }
        return $commission;
    }

    /**
     * @throws Exception
     */
    private function validation($params): void
    {
        $validator = Validator::make($params, [
            'type_debit' => ['required_without:type_credit', 'numeric', 'min:100', 'max:199'],
            'type_credit' => ['required_without:type_debit', 'numeric', 'min:200', 'max:255'],
            'code_currency' => ['required', Rule::in(array_keys(config('im_wallet.code_currency')))],
            'balance_type' => ['required', Rule::in(array_keys(config('im_wallet.balance_type')))],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first() ?? "error validation");
        }
    }

    /**
     * @param Builder $query
     * @param string|null $codeCurrency
     * @param string|null $balanceType
     * @param string|null $nameColumn
     * @return Builder
     */
    public function scopeBalance($query, string|null $codeCurrency = null, string|null $balanceType = null, string|null $nameColumn = 'amount')
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
                DB::raw("SUM(CASE WHEN multi_wallets.type < 200 THEN {$nameColumn} ELSE {$nameColumn}*-1 END) as {$nameColumn}"),
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
     * @return bool
     */
    public function isBlockTransaction(int $type = null, string|int|null $codeCurrency = null, string|null $balanceType = null): bool
    {
        if (!config('im_wallet.is_enable_restrictions', false)) return false;
        return MultiWalletRestriction::query()
            ->where('target_type', self::class)
            ->where('target_id', $this->id)
            ->where('until_at', '>', now())
            ->where(
                fn($v) => $v->where(
                    fn($permanent) => $permanent->whereNull('code_currency')->whereNull('balance_type')->whereNull('type')
                )->orWhere(
                    fn($wType) => $wType->where('type', $type)->whereNull('code_currency')->whereNull('balance_type')
                )->orWhere(
                    fn($wType) => $wType->where('type', $type)->where('code_currency', $codeCurrency)->whereNull('balance_type')
                )->orWhere(
                    fn($wType) => $wType->where('type', $type)->whereNull('code_currency')->where('balance_type', $balanceType)
                )->orWhere(
                    fn($wCurrency) => $wCurrency->where('code_currency', $codeCurrency)->whereNull('type')->whereNull('balance_type')
                )->orWhere(
                    fn($wCurrency) => $wCurrency->where('code_currency', $codeCurrency)->where('type', $type)->whereNull('balance_type')
                )->orWhere(
                    fn($wCurrency) => $wCurrency->where('code_currency', $codeCurrency)->whereNull('type')->where('balance_type', $balanceType)
                )->orWhere(
                    fn($wBalanceType) => $wBalanceType->where('balance_type', $balanceType)->whereNull('type')->whereNull('code_currency')
                )->orWhere(
                    fn($wBalanceType) => $wBalanceType->where('balance_type', $balanceType)->where('type', $type)->whereNull('code_currency')
                )->orWhere(
                    fn($wBalanceType) => $wBalanceType->where('balance_type', $balanceType)->whereNull('type')->where('code_currency', $codeCurrency)
                )
                    ->orWhere(
                        fn($wAll) => $wAll->where('type', $type)->where('code_currency', $codeCurrency)->where('balance_type', $balanceType)
                    )
            )
            ->exists();
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
        $this->unblockTransaction($type, $codeCurrency, $balanceType);
        return $this->multiWalletRestriction()->create([
            'type' => $type,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'until_at' => $untilAt ?? now()->addYears(100),
            'other' => $other,
        ]);
    }

    /**
     * @param int|null $type
     * @param string|int|null $codeCurrency
     * @param string|null $balanceType
     * @return mixed
     */
    public function unblockTransaction(?int $type, string|int|null $codeCurrency, ?string $balanceType): mixed
    {
        if (is_null($type) && is_null($codeCurrency) && is_null($balanceType)) return $this->multiWalletRestriction()->delete();

        return $this->multiWalletRestriction()
            ->where('type', $type)
            ->where('code_currency', $codeCurrency)
            ->where('balance_type', $balanceType)
            ->delete();
    }
}
