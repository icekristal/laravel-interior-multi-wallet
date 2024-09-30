<?php

namespace Icekristal\LaravelInteriorMultiWallet\Enums;


use Icekristal\LaravelInteriorMultiWallet\Trait\BalanceNameTrait;

enum ImWalletBalanceTypeEnum: string
{

    case MAIN = 'main';
    case DEMO = 'demo';
    case BONUS = 'bonus';


    /**
     * @return string
     */
    public function translate(): string
    {
        return match ($this) {
            self::MAIN => __('multi_wallet.balance_type.main'),

            self::DEMO => __('multi_wallet.balance_type.demo'),
            self::BONUS => __('multi_wallet.balance_type.bonus'),
            default => 'Unknown',
        };
    }
}



