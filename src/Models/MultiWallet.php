<?php

namespace Icekristal\LaravelInteriorMultiWallet\Models;

use App\Enums\ImWalletBalanceTypeEnum;
use App\Enums\ImWalletCurrencyEnum;
use App\Enums\ImWalletTypeEnum;
use Carbon\Carbon;
use Icekristal\LaravelInteriorMultiWallet\Casts\BalanceTypeCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Casts\CurrencyCustomCast;
use Icekristal\LaravelInteriorMultiWallet\Casts\TypeCustomCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property integer $id
 * @property string $owner_type
 * @property integer $owner_id
 * @property ImWalletTypeEnum $type
 * @property float $amount
 * @property float $commission
 * @property string $who_type
 * @property integer $who_id
 * @property string $other
 * @property ImWalletCurrencyEnum $code_currency
 * @property string $signed_amount
 * @property string $named_type
 * @property ImWalletBalanceTypeEnum $balance_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MultiWallet extends Model
{
    /**
     *
     * Name table
     * @var string
     */
    protected $table = 'multi_wallets';


    protected $fillable = [
        'owner_type', 'owner_id', 'amount', 'who_type', 'who_id', 'type', 'code_currency', 'balance_type', 'commission', 'other'
    ];

    /**
     *
     * Mutation
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'commission' => 'float',
        'who_id' => 'integer',
        'owner_id' => 'integer',
        'other' => 'object',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'type' => TypeCustomCast::class,
        'balance_type' => BalanceTypeCustomCast::class,
        'code_currency' => CurrencyCustomCast::class
    ];


    /**
     * Owner transaction
     *
     * @return MorphTo
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Addition user for transaction
     *
     * @return MorphTo
     */
    public function who(): MorphTo
    {
        return $this->morphTo();
    }


    /**
     *
     * return name type transaction
     *
     * @return string
     */
    public function getNamedTypeAttribute(): string
    {
        return $this->type?->translate();
    }

    /**
     *  attribute signed_amount
     * return amount with signed
     *
     * @return string
     */
    public function getSignedAmountAttribute(): string
    {
        return (($this->type?->value ?? $this->type) < 200 ? '+' : '-') . number_format($this->amount, 2);
    }
}
