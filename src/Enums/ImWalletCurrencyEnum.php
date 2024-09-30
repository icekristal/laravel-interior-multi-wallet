<?php

namespace Icekristal\LaravelInteriorMultiWallet\Enums;


enum ImWalletCurrencyEnum: string
{

    case YE = 'ye';

    /**
     * @return string
     */
    public function translate(): string
    {
        return match ($this) {
            self::YE => __('multi_wallet.currency.ye'),
            default => 'Unknown',
        };
    }
}



