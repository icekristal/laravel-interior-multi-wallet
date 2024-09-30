<?php

namespace Icekristal\LaravelInteriorMultiWallet\Services;

use Exception;
use Icekristal\LaravelInteriorMultiWallet\Casts\BalanceTypeCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Casts\CurrencyCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Casts\TypeCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletBalanceTypeEnum;
use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletCurrencyEnum;
use Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletTypeEnum;
use Icekristal\LaravelInteriorMultiWallet\Models\MultiWallet;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImWalletService
{
    public object $owner;
    public float|int $amount = 0;
    public ImWalletTypeEnum $type;
    public ImWalletCurrencyEnum $currency;
    public ImWalletBalanceTypeEnum $balanceType;
    public object|null $who;
    public array|null $other = [];

    public function __construct()
    {

    }

    /**
     * @throws Exception
     */
    public function executeTransaction(): void
    {
        if (!$this->isValid()) {
            throw new Exception('Invalid data');
        }
        $this->type?->value < 200 ? $this->debit() : $this->credit();
    }

    private function debit(): void
    {
        $this->saveTransaction();
    }

    private function credit()
    {
        $this->saveTransaction();
    }

    /**
     * @return mixed
     */
    private function saveTransaction(): mixed
    {
        $model = config('im_wallet.multi_wallet_model', MultiWallet::class);
        $commission = $this->calcCommission();
        $finalAmount = $this->amount - $commission;
        return $model::query()->create([
            'owner_type' => get_class($this->owner),
            'owner_id' => $this->owner?->id,
            'amount' => $finalAmount,
            'commission' => $commission,
            'type' => $this->type?->value,
            'currency' => $this->currency?->value,
            'balanceType' => $this->balanceType?->value,
            'who_type' => get_class($this->who) ?? null,
            'who_id' => $this->who?->id,
            'other' => $this->other
        ]);
    }

    /**
     * calc commission
     *
     * @return float|int
     */
    private function calcCommission(): float|int
    {
        if ($this->amount <= 0) return 0;
        $commissionDefault = config('im_wallet.commission_default') ?? 0;
        $additionalCommission = config("im_wallet.commission." . $this->type?->value) ?? null;
        if (!is_null($additionalCommission)) {
            $commission = $this->amount / 100 * $additionalCommission;
        } else {
            $commission = $this->amount / 100 * $commissionDefault;
        }
        return $commission;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return Validator::make([
            'owner_id' => $this->owner?->id,
            'amount' => $this->amount,
            'type' => $this->type,
            'currency' => $this->currency,
            'balanceType' => $this->balanceType,
            'who' => $this->who,
            'other' => $this->other
        ], [
            'owner_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'type' => ['required', Rule::enum(config('im_wallet.types_enum', ImWalletTypeEnum::class)), 'numeric', 'min:100', 'max:255'],
            'currency' => ['required', Rule::enum(config('im_wallet.currency_enum', ImWalletCurrencyEnum::class))],
            'balanceType' => ['required', Rule::enum(config('im_wallet.balance_type_enum', ImWalletBalanceTypeEnum::class))],
            'who' => ['nullable'],
            'other' => ['nullable', 'array']
        ])->passes();
    }

    /**
     * @param object $owner
     * @return $this
     */
    public function setOwner(object $owner): ImWalletService
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @param float|int $amount
     * @return $this
     * @throws Exception
     */
    public function setAmount(float|int $amount): ImWalletService
    {
        if ($amount < 0) {
            throw new Exception('Amount must be greater than 0');
        }
        $this->amount = $amount;
        return $this;
    }

    /**
     * @param int|ImWalletTypeEnum $type
     * @return $this
     */
    public function setType(int|ImWalletTypeEnum $type): ImWalletService
    {
        $this->type = TypeCustomCast::setEnum($type);
        return $this;
    }

    /**
     * @param object|null $who
     * @return $this
     */
    public function setWho(?object $who): ImWalletService
    {
        $this->who = $who;
        return $this;
    }

    /**
     * @param array|null $other
     * @return $this
     */
    public function setOther(?array $other): ImWalletService
    {
        $this->other = $other;
        return $this;
    }

    /**
     * @param string|ImWalletCurrencyEnum $currency
     * @return $this
     */
    public function setCurrency(string|ImWalletCurrencyEnum $currency): ImWalletService
    {
        $this->currency = CurrencyCustomCast::setEnum($currency);
        return $this;
    }

    /**
     * @param string|ImWalletBalanceTypeEnum $balanceType
     * @return $this
     */
    public function setBalanceType(string|ImWalletBalanceTypeEnum $balanceType): ImWalletService
    {
        $this->balanceType = BalanceTypeCustomCast::setEnum($balanceType);
        return $this;
    }
}
