<?php

namespace Icekristal\LaravelInteriorMultiWallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @property integer $id
 * @property string $owner_type
 * @property integer $owner_id
 * @property integer $type
 * @property float $amount
 * @property float $commission
 * @property string $who_type
 * @property integer $who_id
 * @property string $other
 * @property string $code_currency
 * @property string $signed_amount
 * @property string $named_type
 * @property string $balance_type
 * @property string $created_at
 * @property string $updated_at
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
        'type' => 'integer',
        'who_id' => 'integer',
        'owner_id' => 'integer',
        'other' => 'object',
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
        return $this->type < 200
            ? __(config('im_wallet.debit_names')[$this->type] ?? 'multi_wallet.debit_transaction')
            : __(config('im_wallet.credit_names')[$this->type] ?? 'multi_wallet.credit_transaction');
    }

    /**
     *
     * return amount with signed
     *
     * @return string
     */
    public function getSignedAmountAttribute(): string
    {
        return ($this->type < 200 ? '+' : '-') . number_format($this->amount, 2);
    }
}
