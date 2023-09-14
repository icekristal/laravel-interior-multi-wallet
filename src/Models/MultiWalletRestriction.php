<?php

namespace Icekristal\LaravelInteriorMultiWallet\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property integer $id
 * @property boolean $is_blocked
 * @property string $target_type
 * @property integer $target_id
 * @property integer $type
 * @property string $code_currency
 * @property string $balance_type
 * @property string $author_type
 * @property integer $author_id
 * @property object $other
 * @property Carbon $until_at
 */
class MultiWalletRestriction extends Model
{
    /**
     * @var string
     */
    protected $table = 'multi_wallet_restrictions';

    protected $fillable = [
        'target_id', 'target_type', 'type', 'code_currency', 'balance_type', 'author_type', 'author_id', 'other', 'until_at'
    ];

    protected $casts = [
        'until_at' => 'datetime',
        'type' => 'integer',
        'target_id' => 'integer',
        'author_id' => 'integer',
        'other' => 'object',
    ];

    /**
     * attribute: is_blocked
     * @return bool
     */
    public function getIsBlockedAttribute(): bool
    {
        return $this->until_at->isFuture();
    }

    /**
     * Target object block
     *
     * @return MorphTo
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Author action
     *
     * @return MorphTo
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
