<?php

namespace Icekristal\LaravelInteriorMultiWallet\Services;

use App\Enums\ImWalletBalanceTypeEnum;
use App\Enums\ImWalletCurrencyEnum;
use App\Enums\ImWalletTypeEnum;
use Carbon\Carbon;
use Exception;
use Icekristal\LaravelInteriorMultiWallet\Casts\BalanceTypeCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Casts\CurrencyCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Casts\TypeCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Models\MultiWallet;
use Icekristal\LaravelInteriorMultiWallet\Models\MultiWalletRestriction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImWalletService
{
    public object|null $owner = null;
    public float|int $amount = 0;
    public mixed $type;
    public mixed $currency;
    public mixed $balanceType;
    public object|null $who = null;
    public array|null $other = [];

    public mixed $modelImWallet = MultiWallet::class;
    public mixed $modelRestrictionImWallet = MultiWalletRestriction::class;

    public function __construct()
    {
        $this->modelImWallet = config('im_wallet.multi_wallet_model', MultiWallet::class);
        $this->modelRestrictionImWallet = config('im_wallet.multi_wallet_restriction_model', MultiWalletRestriction::class);
        $this->balanceType = is_string(config('im_wallet.balance_required_type')) ? BalanceTypeCustomCast::setEnum(config('im_wallet.balance_required_type')) : null;
        $this->currency = is_string(config('im_wallet.default_code_currency')) ? CurrencyCustomCast::setEnum(config('im_wallet.default_code_currency')) : null;
    }

    /**
     * @param Carbon|null $dateAt
     * @return float|int
     */
    public function getBalance(Carbon $dateAt = null): float|int
    {
        if(is_null($this->owner)) return 0;
        return $this->modelImWallet::query()
            ->where('owner_type', get_class($this->owner))
            ->where('owner_id', $this->owner->id)
            ->where('code_currency', $this->currency->value)
            ->when(!is_null($dateAt), fn($q) => $q->where('created_at', '<=', $dateAt))
            ->where('balance_type', $this->balanceType->value)
            ->select(DB::raw('SUM(CASE WHEN type < 200 THEN amount*1 ELSE amount*-1 END) as amount'))
            ->value('amount') ?? 0;
    }

    /**
     * @throws Exception
     */
    public function executeTransaction(): mixed
    {
        if (!$this->isValid()) {
            throw new Exception('Invalid data');
        }
        return $this->type?->value < 200 ? $this->debit() : $this->credit();
    }

    /**
     * @return mixed
     */
    private function debit(): mixed
    {
        return $this->saveTransaction();
    }

    /**
     * @return mixed
     */
    private function credit(): mixed
    {
        return $this->saveTransaction();
    }

    /**
     * @return mixed
     */
    private function saveTransaction(): mixed
    {
        $commission = $this->calcCommission();
        $finalAmount = $this->amount - $commission;
        return $this->modelImWallet::query()->create([
            'owner_type' => get_class($this->owner),
            'owner_id' => $this->owner?->id,
            'amount' => $finalAmount,
            'commission' => $commission,
            'type' => $this->type?->value,
            'code_currency' => $this->currency?->value,
            'balanceType' => $this->balanceType?->value,
            'who_type' => !is_null($this->who) ? get_class($this->who) : null,
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
            'type' => $this->type?->value,
            'currency' => $this->currency?->value,
            'balanceType' => $this->balanceType?->value,
            'who_id' => $this->who?->id ?? null,
            'other' => $this->other,
            'is_block_transaction' => $this->isBlockTransaction()
        ], [
            'owner_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'type' => ['required', Rule::enum(config('im_wallet.types_enum', ImWalletTypeEnum::class)), 'numeric', 'min:100', 'max:255'],
            'currency' => ['required', Rule::enum(config('im_wallet.currency_enum', ImWalletCurrencyEnum::class))],
            'balanceType' => ['required', Rule::enum(config('im_wallet.balance_type_enum', ImWalletBalanceTypeEnum::class))],
            'who' => ['nullable', 'numeric'],
            'other' => ['nullable', 'array'],
            'is_block_transaction' => ['required', 'accepted']
        ])->passes();
    }

    /**
     * @param Carbon|null $untilAt
     * @param array|null $other
     * @return mixed
     */
    public function blockTransaction(Carbon $untilAt = null, ?array $other = null): mixed
    {
        $this->unBlockTransaction();
        return $this->modelRestrictionImWallet::query()->create([
            'target_type' => get_class($this->owner),
            'target_id' => $this->owner?->id,
            'type' => $this->type?->value,
            'code_currency' => $this->currency?->value,
            'balance_type' => $this->balanceType?->value,
            'until_at' => $untilAt ?? now()->addYears(100),
            'other' => $other
        ]);
    }

    /**
     * @return mixed
     */
    public function unBlockTransaction(): mixed
    {
        return $this->modelRestrictionImWallet::query()
            ->where('target_type', get_class($this->owner))
            ->where('target_id', $this->owner?->id)
            ->where('type', $this->type?->value)
            ->where('code_currency', $this->currency?->value)
            ->where('balance_type', $this->balanceType?->value)
            ->delete();
    }

    /**
     * @return bool
     */
    private function isBlockTransaction(): bool
    {
        if (!config('im_wallet.is_enable_restrictions', false)) return true;

        $type = $this->type?->value;
        $codeCurrency = $this->currency?->value;
        $balanceType = $this->balanceType?->value;

        return !$this->modelRestrictionImWallet::query()
            ->where('target_type', get_class($this->owner))
            ->where('target_id', $this->owner?->id)
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
