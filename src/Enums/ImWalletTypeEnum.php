<?php

namespace Icekristal\LaravelInteriorMultiWallet\Enums;

use Icekristal\LaravelInteriorMultiWallet\Trait\BalanceNameTrait;

enum ImWalletTypeEnum: int
{
    use BalanceNameTrait;

    /** 100-199 push transactions */
    case debit = 101;


    /** 200-255 credit transaction */
    case credit = 201;


    /** 999 for unknown */
    case unknown = 999;


    /**
     * @return string
     */
    public function translate(): string
    {
        return match ($this) {
            self::debit => __('multi_wallet.type.debit'),

            self::credit => __('multi_wallet.type.credit'),
            default => 'Unknown',
        };
    }
}



