<?php

namespace Icekristal\LaravelInteriorMultiWallet;

use Exception;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        return $this->morphMany(config('im_wallet.multi_wallet_model'), 'owner')
            ->when(!is_null($codeCurrency), fn($q) => $q->where('code_currency', $codeCurrency))
            ->when(!is_null($balanceType), fn($q) => $q->where('balance_type', $balanceType));
    }

    /**
     *
     * @return MorphMany
     */
    public function balanceWhoTransaction(): MorphMany
    {
        return $this->morphMany(config('im_wallet.multi_wallet_model'), 'who');
    }

    /**
     * get balance user
     *
     * @param string $codeCurrency
     * @param string|null $balanceType
     * @return HigherOrderBuilderProxy|int|mixed
     */
    public function balance(string $codeCurrency = 'YE', string|null $balanceType = null): mixed
    {
        if (is_null($balanceType)) {
            $balanceType = config('im_wallet.balance_required_type') ?? 'main';
        }

        return $this->balanceTransaction()
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
     * @return Model
     * @throws Exception
     */
    public function debitBalance(float|int $amount, int $typeDebit = 101, string $codeCurrency = 'YE', string $balanceType = 'main', $who = null): Model
    {
        $this->validation([
            'type_debit' => $typeDebit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
        ]);

        $commission = $this->calcCommission($typeDebit, $amount);
        $amount -= $commission;
        return $this->balanceTransaction()->create([
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
     * @throws Exception
     */
    public function creditBalance(float|int $amount, int $typeCredit = 203, string $codeCurrency = 'YE', string $balanceType = 'main', $who = null): Model
    {
        $this->validation([
            'type_credit' => $typeCredit,
            'code_currency' => $codeCurrency,
            'balance_type' => $balanceType,
            'amount' => $amount,
        ]);

        $commission = $this->calcCommission($typeCredit, $amount);
        $amount -= $commission;
        return $this->balanceTransaction()->create([
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

    /**
     * @throws Exception
     */
    private function validation($params)
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
}
