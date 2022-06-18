<?php

namespace Icekristal\LaravelInteriorMultiWallet;

use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

trait InteractsWithMultiWallet
{
    /**
     *
     * @return MorphMany
     */
    public function owner(): MorphMany
    {
        return $this->morphMany(config('im_wallet.multi_wallet_model'), 'owner');
    }

    /**
     *
     * @return MorphMany
     */
    public function who(): MorphMany
    {
        return $this->morphMany(config('im_wallet.multi_wallet_model'), 'who');
    }

    /**
     * get balance user
     *
     * @param string $codeCurrency
     * @return HigherOrderBuilderProxy|int|mixed
     */
    public function balance(string $codeCurrency = 'YE'): mixed
    {
        return $this->owner()->where('code_currency', $codeCurrency)->select(
                DB::raw('SUM(CASE WHEN type < 200 THEN amount ELSE amount*-1 END) as amount')
            )->first()?->amount ?? 0;
    }

    /**
     * @param float|int $amount
     * @param int $typeDebit
     * @param string $codeCurrency
     * @param string $balanceType
     * @param null $who
     * @return Model
     */
    public function debitBalance(float|int $amount, int $typeDebit = 101, string $codeCurrency = 'YE', string $balanceType = 'main', $who = null): Model
    {
        $commission = $this->calcCommission($typeDebit, $amount);
        $amount -= $commission;
        return $this->owner()->create([
            'type' => $typeDebit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
            'commission' => $commission,
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
     * @return Model
     */
    public function creditBalance(float|int $amount, int $typeCredit = 203, string $codeCurrency = 'YE', string $balanceType = 'main', $who = null): Model
    {
        $commission = $this->calcCommission($typeCredit, $amount);
        $amount -= $commission;
        return $this->owner()->create([
            'type' => $typeCredit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
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
}
