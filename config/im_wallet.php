<?php
return [
    'code_currency' => [
        'YE' => 'YE', //default
    ],
    'code_currency_name' => [
        'YE' => 'conventional unit of measurement', //default
    ],

    'default_code_currency' => 'YE',

    'balance_required_type' => 'main',
    'balance_type' => [
        'main' => 'main',
        'demo' => 'demo',
        'bonus' => 'bonus'
    ],

    'debit' => [
        'put' => 101,
        'transfer' => 105,
    ],

    'debit_names' => [
        101 => 'multi_wallet.debit_put',
        102 => 'multi_wallet.debit_transfer',
    ],


    'credit' => [
        'withdrawal' => 201,
        'transfer' => 202,
        'buy' => 203,
    ],

    'credit_names' => [
        201 => 'multi_wallet.credit_withdrawal',
        202 => 'multi_wallet.credit_transfer',
        203 => 'multi_wallet.credit_buy',
    ],

    'commission_default' => 0, // percentage commission (%)
    'commission' => [
        201 => 0, // set commission on withdrawal
    ],

    'is_enable_restrictions' => true, // enable restrictions


    'multi_wallet_model' => \Icekristal\LaravelInteriorMultiWallet\Models\MultiWallet::class,
    'multi_wallet_restriction_model' => \Icekristal\LaravelInteriorMultiWallet\Models\MultiWalletRestriction::class,
];
