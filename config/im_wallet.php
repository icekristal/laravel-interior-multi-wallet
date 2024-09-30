<?php
return [

    'balance_type_enum' => \Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletBalanceTypeEnum::class,
    'types_enum' => \Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletTypeEnum::class,
    'currency_enum' => \Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletCurrencyEnum::class,

    'default_code_currency' => \Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletCurrencyEnum::YE->value,

    'balance_required_type' => \Icekristal\LaravelInteriorMultiWallet\Enums\ImWalletBalanceTypeEnum::MAIN->value,


    'commission_default' => 0, // percentage commission (%)
    'commission' => [
        201 => 0, // set commission on withdrawal
    ],

    'is_enable_restrictions' => true, // enable restrictions

    'multi_wallet_model' => \Icekristal\LaravelInteriorMultiWallet\Models\MultiWallet::class,
    'multi_wallet_restriction_model' => \Icekristal\LaravelInteriorMultiWallet\Models\MultiWalletRestriction::class,
];
