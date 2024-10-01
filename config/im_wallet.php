<?php
return [
    'balance_type_enum' => \App\Enums\ImWalletBalanceTypeEnum::class,
    'types_enum' => \App\Enums\ImWalletTypeEnum::class,
    'currency_enum' => \App\Enums\ImWalletCurrencyEnum::class,

    'default_code_currency' => 'ye',
    'balance_required_type' => 'main',


    'commission_default' => 0, // percentage commission (%)
    'commission' => [
        201 => 0, // set commission on withdrawal
    ],

    'is_enable_restrictions' => true, // enable restrictions

    'multi_wallet_model' => \Icekristal\LaravelInteriorMultiWallet\Models\MultiWallet::class,
    'multi_wallet_restriction_model' => \Icekristal\LaravelInteriorMultiWallet\Models\MultiWalletRestriction::class,
];
